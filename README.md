# Plugin GLPI - Registro de Tempo do Ticket

Este plugin cria e gerencia um controle pr?prio de apontamentos de tempo por chamado.

## O que foi implementado

- Tabela pr?pria em banco: `glpi_plugin_time_tracking_ticket_times`
  - `tickets_id`, `users_id`, `work_date`, `start_time`, `end_time`, `duration_minutes`, `description`
- Bot?o **Registro de Tempo** injetado no formul?rio do Ticket (`post_item_form`).
- Aba no ticket com resumo e acesso ? p?gina completa.
- P?gina completa em `front/time_register.php` com:
  - listagem dos apontamentos do chamado,
  - total de horas apontadas,
  - formul?rio para novo apontamento com:
    - data,
    - in?cio + fim (c?lculo autom?tico em minutos), ou
    - horas manuais.

## Instala??o

1. Copie esta pasta para `glpi/plugins/time_tracking_ticket_glpi`.
2. No GLPI, acesse **Configurar > Plugins**.
3. Instale e ative o plugin.
4. A nova op??o ?Registro de Tempo? aparecer? no chamado.

## Compatibilidade

- Testado para GLPI com classe de plugins da vers?o 10+.

## Observa??es

- A tabela ? independente das tasks padr?o de ticket (controle total pr?prio).
