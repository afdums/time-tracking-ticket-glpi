<?php

if (!defined('GLPI_ROOT')) {
   define('GLPI_ROOT', dirname(__DIR__, 3));
}

include_once('../../../inc/includes.php');
require_once __DIR__ . '/../inc/timetracker.class.php';

Session::checkLoginUser();

$ticket_id = (int)($_GET['tickets_id'] ?? 0);
$ticket = new Ticket();
if (!$ticket->getFromDB($ticket_id)) {
   Html::displayErrorAndDie(__('Chamado n?o encontrado.', 'time_tracking_ticket_glpi'));
}
$ticket->check($ticket_id, READ);

$errors = [];
$self_url = PluginTimeTrackerTicketTime::getPluginBaseUrl()
   . '/front/time_register.php?tickets_id=' . $ticket_id;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_time_entry'])) {
   Session::checkCSRF();
   $errors = PluginTimeTrackerTicketTime::addFromPost($ticket_id, $_POST);

   if (empty($errors)) {
      Session::addMessageAfterRedirect(__('Apontamento registrado com sucesso.', 'time_tracking_ticket_glpi'));
      Html::redirect($self_url);
   }
}

Html::header(__('Registro de Tempo', 'time_tracking_ticket_glpi'), '', 'plugins', 'time_tracking_ticket_glpi');

echo '<div class="timeline_card">';
echo '<h2>' . sprintf(__('Registro de Tempo do chamado #%s', 'time_tracking_ticket_glpi'), $ticket_id) . '</h2>';

echo '<div class="mb-2">';
echo '<strong>' . sprintf(
   __('Total apontado: %s h', 'time_tracking_ticket_glpi'),
   PluginTimeTrackerTicketTime::formatHours(
      PluginTimeTrackerTicketTime::getTotalMinutes($ticket_id)
   )
) . '</strong>';
echo '</div>';

echo '<h3>' . __('Registros existentes', 'time_tracking_ticket_glpi') . '</h3>';
PluginTimeTrackerTicketTime::renderEntriesTable($ticket_id);

if (count($errors) > 0) {
   echo '<div class="red"><ul>';
   foreach ($errors as $error) {
      echo '<li>' . $error . '</li>';
   }
   echo '</ul></div>';
}

echo '<hr/>';
echo '<h3>' . __('Novo apontamento', 'time_tracking_ticket_glpi') . '</h3>';
echo "<form method='post' action='" . htmlspecialchars($self_url, ENT_QUOTES) . "'>";
echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);

echo '<table class="tab_cadre_fixe">';
echo '<tr><th>' . __('Data', 'time_tracking_ticket_glpi') . '</th><td><input type="date" name="work_date" value="' . date('Y-m-d') . '" required></td></tr>';
echo '<tr><th>' . __('In?cio (HH:mm)', 'time_tracking_ticket_glpi') . '</th><td><input type="time" name="start_time" placeholder="HH:mm"></td></tr>';
echo '<tr><th>' . __('Fim (HH:mm)', 'time_tracking_ticket_glpi') . '</th><td><input type="time" name="end_time" placeholder="HH:mm"></td></tr>';
echo '<tr><th>' . __('Ou horas trabalhadas', 'time_tracking_ticket_glpi') . '</th><td><input type="number" name="duration_hours" step="0.25" min="0" placeholder="2.5"></td></tr>';
echo '<tr><th>' . __('Descri??o', 'time_tracking_ticket_glpi') . '</th><td><textarea rows="3" cols="60" maxlength="255" name="description"></textarea></td></tr>';
echo '<tr><td colspan="2" class="center"><button type="submit" name="add_time_entry" class="vsubmit">' . __('Salvar apontamento', 'time_tracking_ticket_glpi') . '</button></td></tr>';
echo '</table>';
echo '</form>';

echo '</div>';
Html::footer();
