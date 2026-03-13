<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginTimeTrackerTicketTime extends CommonDBTM {

   private const TABLE = 'glpi_plugin_time_tracking_ticket_times';
   private const PLUGIN_SLUG = 'time_tracking_ticket_glpi';

   public static $rightname = 'ticket';

   public static function getTypeName($nb = 0): string {
      return __('Registro de Tempo', 'time_tracking_ticket_glpi');
   }

   public static function getTable($classname = null): string {
      return self::TABLE;
   }

   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0): string {
      if (!$item instanceof Ticket) {
         return '';
      }
      return __('Registro de Tempo', 'time_tracking_ticket_glpi');
   }

   public function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0): bool {
      if (!$item instanceof Ticket) {
         return false;
      }
      $ticket_id = (int)$item->getID();
      if ($ticket_id <= 0) {
         return false;
      }

      $url = self::getPluginBaseUrl()
         . '/front/time_register.php?tickets_id=' . $ticket_id;

      $total = self::formatHours(self::getTotalMinutes($ticket_id));
      echo '<p><strong>' . sprintf(
         __('Total apontado: %s h', 'time_tracking_ticket_glpi'),
         $total
      ) . '</strong></p>';
      echo "<a class='vsubmit' href='" . htmlspecialchars($url, ENT_QUOTES) . "'>"
         . __('Abrir p?gina completa', 'time_tracking_ticket_glpi') . '</a>';

      return true;
   }

   public static function install(): bool {
      global $DB;

      $table = self::TABLE;
      if ($DB->tableExists($table)) {
         return true;
      }

      $create_table = "
         CREATE TABLE IF NOT EXISTS `$table` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `tickets_id` int unsigned NOT NULL,
            `users_id` int unsigned NOT NULL,
            `work_date` date NOT NULL,
            `start_time` datetime DEFAULT NULL,
            `end_time` datetime DEFAULT NULL,
            `duration_minutes` int unsigned NOT NULL,
            `description` varchar(255) DEFAULT NULL,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `tickets_id` (`tickets_id`),
            KEY `users_id` (`users_id`),
            KEY `work_date` (`work_date`)
         ) ENGINE = InnoDB
         DEFAULT CHARACTER SET utf8mb4
         COLLATE utf8mb4_unicode_ci";

      $DB->queryOrDie($create_table, $DB->error());
      return true;
   }

   public static function uninstall(): bool {
      global $DB;

      $table = self::TABLE;
      $DB->queryOrDie("DROP TABLE IF EXISTS `$table`", $DB->error());
      return true;
   }

   public static function getPluginBaseUrl(): string {
      global $CFG_GLPI;
      return $CFG_GLPI['root_doc'] . '/plugins/' . basename(dirname(__DIR__)) ;
   }

   public static function getTotalMinutes(int $ticket_id): int {
      global $DB;

      $ticket_id = (int)$ticket_id;
      $table = self::TABLE;
      $result = $DB->query(
         "SELECT SUM(`duration_minutes`) AS total FROM `$table` WHERE `tickets_id` = $ticket_id"
      );
      if ($result && $row = $DB->fetchAssoc($result)) {
         return (int)$row['total'];
      }

      return 0;
   }

   public static function getEntries(int $ticket_id): array {
      global $DB;
      $rows = [];

      $ticket_id = (int)$ticket_id;
      $table = self::TABLE;
      $query = "
         SELECT e.*, CONCAT(u.firstname, ' ', u.realname) AS user_name
           FROM `$table` e
      LEFT JOIN `glpi_users` u ON u.id = e.users_id
          WHERE e.tickets_id = $ticket_id
          ORDER BY e.work_date DESC, e.created_at DESC";

      $result = $DB->query($query);
      if (!$result) {
         return $rows;
      }

      while ($row = $DB->fetchAssoc($result)) {
         if (trim((string)$row['user_name']) === '') {
            $row['user_name'] = sprintf(
               __('Usu?rio %1$s', 'time_tracking_ticket_glpi'),
               (string)$row['users_id']
            );
         }
         $rows[] = $row;
      }

      return $rows;
   }

   public static function addFromPost(int $ticket_id, array $post): array {
      global $DB;

      $errors = [];
      $ticket_id = (int)$ticket_id;

      if ($ticket_id <= 0) {
         $errors[] = __('Chamado inv?lido.', 'time_tracking_ticket_glpi');
      }

      $work_date = trim((string)($post['work_date'] ?? ''));
      if ($work_date === '' || !self::validateDate($work_date, 'Y-m-d')) {
         $errors[] = __('Informe uma data v?lida.', 'time_tracking_ticket_glpi');
      }

      $start_raw = trim((string)($post['start_time'] ?? ''));
      $end_raw = trim((string)($post['end_time'] ?? ''));
      $manual_raw = trim((string)($post['duration_hours'] ?? ''));
      $desc = trim((string)($post['description'] ?? ''));
      if (strlen($desc) > 255) {
         $desc = substr($desc, 0, 255);
      }

      $start_time = null;
      $end_time = null;
      $duration_minutes = null;
      $has_start = $start_raw !== '';
      $has_end = $end_raw !== '';

      if ($has_start xor $has_end) {
         $errors[] = __('Informe in?cio e fim juntos, ou deixe ambos em branco para digitar as horas.', 'time_tracking_ticket_glpi');
      } elseif ($has_start && $has_end) {
         if (!self::validateTime($start_raw) || !self::validateTime($end_raw)) {
            $errors[] = __('Use HH:mm nos campos de hor?rio.', 'time_tracking_ticket_glpi');
         } else {
            $start = DateTime::createFromFormat('Y-m-d H:i', $work_date . ' ' . $start_raw);
            $end   = DateTime::createFromFormat('Y-m-d H:i', $work_date . ' ' . $end_raw);

            if (!$start || !$end) {
               $errors[] = __('Erro ao interpretar in?cio/fim do per?odo.', 'time_tracking_ticket_glpi');
            } else {
               if ($end <= $start) {
                  $end = $end->modify('+1 day');
               }
               $duration_seconds = $end->getTimestamp() - $start->getTimestamp();
               $duration_minutes = (int)floor($duration_seconds / 60);
               if ($duration_minutes <= 0) {
                  $errors[] = __('Per?odo inv?lido. O fim deve ser maior que o in?cio.', 'time_tracking_ticket_glpi');
               } else {
                  $start_time = $start->format('Y-m-d H:i:s');
                  $end_time = $end->format('Y-m-d H:i:s');
               }
            }
         }
      } else {
         if ($manual_raw === '') {
            $errors[] = __('Informe in?cio/fim ou horas trabalhadas.', 'time_tracking_ticket_glpi');
         } else {
            $manual_hours = str_replace(',', '.', $manual_raw);
            if (!is_numeric($manual_hours)) {
               $errors[] = __('Horas trabalhadas deve ser num?rico (ex.: 1.5).', 'time_tracking_ticket_glpi');
            } else {
               $duration_minutes = (int)round(((float)$manual_hours) * 60);
               if ($duration_minutes <= 0) {
                  $errors[] = __('Informe um valor maior que 0.', 'time_tracking_ticket_glpi');
               }
            }
         }
      }

      if (!empty($errors)) {
         return $errors;
      }

      $description = addslashes($desc);
      $user_id = (int)Session::getLoginUserID();
      $work_date_sql = $DB->escape($work_date);
      $start_sql = $start_time !== null ? "'" . $DB->escape($start_time) . "'" : 'NULL';
      $end_sql = $end_time !== null ? "'" . $DB->escape($end_time) . "'" : 'NULL';
      $description_sql = $description !== '' ? "'" . $DB->escape($description) . "'" : 'NULL';
      $table = self::TABLE;

      $insert_query = "
         INSERT INTO `$table` (
            `tickets_id`, `users_id`, `work_date`, `start_time`,
            `end_time`, `duration_minutes`, `description`,
            `created_at`, `updated_at`
         ) VALUES (
            $ticket_id, $user_id, '$work_date_sql', $start_sql,
            $end_sql, $duration_minutes, $description_sql,
            NOW(), NOW()
         )";

      $DB->queryOrDie($insert_query, $DB->error());
      return [];
   }

   public static function renderEntriesTable(int $ticket_id): void {
      $entries = self::getEntries($ticket_id);
      echo '<table class="tab_cadre_fixe">';
      echo '<tr>';
      echo '<th>' . __('Data', 'time_tracking_ticket_glpi') . '</th>';
      echo '<th>' . __('Hor?rio', 'time_tracking_ticket_glpi') . '</th>';
      echo '<th>' . __('Horas', 'time_tracking_ticket_glpi') . '</th>';
      echo '<th>' . __('Usu?rio', 'time_tracking_ticket_glpi') . '</th>';
      echo '<th>' . __('Descri??o', 'time_tracking_ticket_glpi') . '</th>';
      echo '</tr>';

      if (count($entries) === 0) {
         echo '<tr>';
         echo '<td colspan="5" class="center">' . __('Nenhum registro encontrado.', 'time_tracking_ticket_glpi') . '</td>';
         echo '</tr>';
         echo '</table>';
         return;
      }

      foreach ($entries as $entry) {
         $time_range = self::formatTimeRange($entry);
         $hours = self::formatHours((int)$entry['duration_minutes']);
         $desc = htmlentities((string)$entry['description']);
         $date = Html::convDate($entry['work_date']);
         $user_name = htmlentities((string)$entry['user_name']);

         echo '<tr>';
         echo '<td>' . $date . '</td>';
         echo '<td>' . $time_range . '</td>';
         echo '<td>' . $hours . ' h</td>';
         echo '<td>' . $user_name . '</td>';
         echo '<td>' . $desc . '</td>';
         echo '</tr>';
      }
      echo '</table>';
   }

   public static function formatHours(int $minutes): string {
      return number_format($minutes / 60, 2, ',', '.');
   }

   public static function formatTimeRange(array $entry): string {
      if ((string)$entry['start_time'] === '' || (string)$entry['end_time'] === '') {
         return '-';
      }
      $start = date('H:i', strtotime($entry['start_time']));
      $end = date('H:i', strtotime($entry['end_time']));
      return $start . ' - ' . $end;
   }

   private static function validateDate(string $date, string $format): bool {
      $dt = DateTime::createFromFormat($format, $date);
      return $dt !== false && $dt->format($format) === $date;
   }

   private static function validateTime(string $time): bool {
      return self::validateDate($time, 'H:i');
   }
}
