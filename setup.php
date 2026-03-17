<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

require_once __DIR__ . '/inc/timetracker.class.php';

function plugin_time_tracking_ticket_glpi_check_prerequisites(): bool {
   return true;
}

function plugin_time_tracking_ticket_glpi_check_config(): bool {
   return true;
}

function plugin_time_tracking_ticket_glpi_install(): bool {
   return PluginTimeTrackerTicketTime::install();
}

function plugin_time_tracking_ticket_glpi_uninstall(): bool {
   return PluginTimeTrackerTicketTime::uninstall();
}

function plugin_time_tracking_ticket_glpi_activate(): bool {
   return true;
}

function plugin_time_tracking_ticket_glpi_deactivate(): bool {
   return true;
}

function plugin_init_time_tracking_ticket_glpi(): void {
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['csrf_compliant']['time_tracking_ticket_glpi'] = true;
   Plugin::registerClass('PluginTimeTrackerTicketTime', ['addtabon' => ['Ticket']]);
   $PLUGIN_HOOKS['post_item_form']['time_tracking_ticket_glpi'] = 'plugin_time_tracking_ticket_glpi_postItemForm';
}

function plugin_version_time_tracking_ticket_glpi(): array {
   return [
      'name'         => 'Registro de Tempo de Ticket',
      'version'      => '1.0.0',
      'author'       => 'GLPI Plugin',
      'license'      => 'GPLv2+',
      'homepage'     => 'https://glpi-project.org',
      'requirements' => [
         'glpi' => [
            'min' => '10.0.0',
            'max' => '11.999.999',
         ],
      ],
   ];
}

function plugin_time_tracking_ticket_glpi_postItemForm(array $params): void {
   if (!isset($params['item']) || !($params['item'] instanceof Ticket)) {
      return;
   }

   $ticket = $params['item'];
   $ticket_id = (int) $ticket->getID();
   if ($ticket_id <= 0) {
      return;
   }

   $url = PluginTimeTrackerTicketTime::getPluginBaseUrl()
      . '/front/time_register.php?tickets_id=' . $ticket_id;

   echo "<div class='plugin_time_tracking_ticket_glpi_button mt-1 mb-1 me-2'>";
   echo "<a class='vsubmit' href='" . htmlspecialchars($url, ENT_QUOTES) . "'>"
      . __('Registro de Tempo', 'time_tracking_ticket_glpi') . '</a>';
   echo '</div>';
}