<?php

/**
 * Vínculos do rádio com os Laudos (plugin Laudo) e com os Chamados do GLPI: duas abas no
 * formulário do rádio ("Laudos" e "Chamados"), com cada item abrindo em uma nova guia.
 *
 * - Laudos: os laudos que têm este rádio como "Ativo vinculado" (itemtype PluginRadiosRadio).
 *   Só lê as tabelas do plugin Laudo; a aba some se o plugin não estiver ativo ou se o perfil
 *   não tiver direito de ver laudos.
 * - Chamados: os chamados que o GLPI liga a este rádio (glpi_items_tickets) e os chamados
 *   informados nos laudos dele. O mesmo chamado aparece uma vez só, com todas as origens.
 *
 * Nada aqui grava no banco.
 */
class PluginRadiosVinculo extends CommonGLPI {

    const TAB_LAUDOS   = 1;
    const TAB_CHAMADOS = 2;

    const LAUDO_PLUGIN   = 'laudo';
    const LAUDO_CLASS    = 'GlpiPlugin\\Laudo\\Laudo';
    const LAUDO_RIGHT    = 'plugin_laudo_laudo';
    const TABLE_LAUDOS   = 'glpi_plugin_laudo_laudos';
    const TABLE_STATUSES = 'glpi_plugin_laudo_laudostatuses';

    static function getTypeName($nb = 0) {
        return __('Vínculos', 'radios');
    }

    /** Plugin Laudo ativo, com as tabelas que esta aba lê, e perfil com direito de ver laudos. */
    static function canSeeLaudos(): bool {
        global $DB;

        static $available = null;
        if ($available === null) {
            $available = Plugin::isPluginActive(self::LAUDO_PLUGIN)
                && $DB->tableExists(self::TABLE_LAUDOS)
                && $DB->tableExists(self::TABLE_STATUSES)
                && $DB->fieldExists(self::TABLE_LAUDOS, 'itemtype')
                && $DB->fieldExists(self::TABLE_LAUDOS, 'items_id');
        }

        return $available && Session::haveRight(self::LAUDO_RIGHT, READ);
    }

    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        if (!($item instanceof PluginRadiosRadio) || $item->getID() <= 0) {
            return '';
        }

        $radio_id = (int) $item->getID();
        $tabs     = [];

        if (self::canSeeLaudos()) {
            $tabs[self::TAB_LAUDOS] = self::createTabEntry(__('Laudos', 'radios'), count(self::getLaudos($radio_id)));
        }
        $tabs[self::TAB_CHAMADOS] = self::createTabEntry(__('Chamados', 'radios'), count(self::getTickets($radio_id)));

        return $tabs;
    }

    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        if (!($item instanceof PluginRadiosRadio) || $item->getID() <= 0) {
            return false;
        }

        if ((int) $tabnum === self::TAB_LAUDOS && self::canSeeLaudos()) {
            self::showLaudos((int) $item->getID());
        } elseif ((int) $tabnum === self::TAB_CHAMADOS) {
            self::showTickets((int) $item->getID());
        }

        return true;
    }

    /**
     * Laudos que têm o rádio como ativo vinculado, mais recente primeiro. Os de lixeira ficam
     * de fora. Não restringe entidade: o laudo pode ter sido aberto por outro setor.
     *
     * @return array[] id, name, status_name, group_name, date, tickets_id, ticket_reference
     */
    static function getLaudos(int $radio_id): array {
        global $DB;

        static $cache = [];

        if ($radio_id <= 0 || !self::canSeeLaudos()) {
            return [];
        }

        if (!isset($cache[$radio_id])) {
            $laudos   = self::TABLE_LAUDOS;
            $statuses = self::TABLE_STATUSES;

            $iterator = $DB->request([
                'SELECT'    => [
                    "$laudos.id",
                    "$laudos.name",
                    "$laudos.date_start",
                    "$laudos.date_creation",
                    "$laudos.tickets_id",
                    "$laudos.ticket_reference",
                    "$statuses.name AS status_name",
                    'glpi_groups.completename AS group_name',
                ],
                'FROM'      => $laudos,
                'LEFT JOIN' => [
                    $statuses     => ['ON' => [$statuses => 'id', $laudos => 'plugin_laudo_laudostatuses_id']],
                    'glpi_groups' => ['ON' => ['glpi_groups' => 'id', $laudos => 'groups_id']],
                ],
                'WHERE'     => [
                    "$laudos.itemtype"   => 'PluginRadiosRadio',
                    "$laudos.items_id"   => $radio_id,
                    "$laudos.is_deleted" => 0,
                ],
                'ORDER'     => ["$laudos.id DESC"],
            ]);

            $cache[$radio_id] = [];
            foreach ($iterator as $row) {
                $cache[$radio_id][] = $row;
            }
        }

        return $cache[$radio_id];
    }

    /**
     * Chamados do rádio, mais recente primeiro, cada um uma vez só: os ligados ao rádio pelo
     * GLPI e os informados nos laudos dele.
     *
     * @return array[] id, name, status, date, is_deleted, from_item (bool), laudos (id => nome)
     */
    static function getTickets(int $radio_id): array {
        global $DB;

        static $cache = [];

        if ($radio_id <= 0) {
            return [];
        }

        if (isset($cache[$radio_id])) {
            return $cache[$radio_id];
        }

        $found = [];

        // Chamados ligados ao rádio pelo próprio GLPI (campo "Itens" do chamado)
        $linked = $DB->request([
            'SELECT' => ['tickets_id'],
            'FROM'   => 'glpi_items_tickets',
            'WHERE'  => ['itemtype' => 'PluginRadiosRadio', 'items_id' => $radio_id],
        ]);
        foreach ($linked as $row) {
            $found[(int) $row['tickets_id']]['from_item'] = true;
        }

        // Chamados informados nos laudos do rádio
        foreach (self::getLaudos($radio_id) as $laudo) {
            if ((int) $laudo['tickets_id'] > 0) {
                $found[(int) $laudo['tickets_id']]['laudos'][(int) $laudo['id']] = (string) $laudo['name'];
            }
        }

        $cache[$radio_id] = [];
        if (!count($found)) {
            return $cache[$radio_id];
        }

        $rows = $DB->request([
            'SELECT' => ['id', 'name', 'status', 'date', 'is_deleted'],
            'FROM'   => 'glpi_tickets',
            'WHERE'  => ['id' => array_keys($found)],
            'ORDER'  => ['date DESC', 'id DESC'],
        ]);
        foreach ($rows as $row) {
            $origin = $found[(int) $row['id']];
            $cache[$radio_id][] = [
                'id'         => (int) $row['id'],
                'name'       => (string) $row['name'],
                'status'     => (int) $row['status'],
                'date'       => $row['date'],
                'is_deleted' => (int) $row['is_deleted'],
                'from_item'  => !empty($origin['from_item']),
                'laudos'     => $origin['laudos'] ?? [],
            ];
        }

        return $cache[$radio_id];
    }

    /** Link para o laudo, só para quem pode ver laudos. */
    static function getLaudoUrl(int $laudos_id): ?string {
        if ($laudos_id <= 0 || !self::canSeeLaudos() || !class_exists(self::LAUDO_CLASS)) {
            return null;
        }

        $class = self::LAUDO_CLASS;

        return $class::getFormURLWithID($laudos_id);
    }

    /**
     * O chamado, mas só se o usuário pode abri-lo (senão o link levaria a "acesso negado" e o
     * título vazaria numa lista). Um objeto por chamado, na mesma requisição.
     */
    static function getViewableTicket(int $tickets_id): ?Ticket {
        static $cache = [];

        if ($tickets_id <= 0) {
            return null;
        }

        if (!array_key_exists($tickets_id, $cache)) {
            $ticket = new Ticket();
            $cache[$tickets_id] = ($ticket->getFromDB($tickets_id) && $ticket->can($tickets_id, READ)) ? $ticket : null;
        }

        return $cache[$tickets_id];
    }

    /** Texto do banco (o GLPI 10 guarda o HTML codificado) pronto para ir dentro de HTML. */
    static function text($value): string {
        return htmlspecialchars(html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES, 'UTF-8');
    }

    /** Link que abre em nova guia. $label já vem pronto para HTML. */
    static function newTabLink(string $url, string $label, string $title = ''): string {
        return "<a href='" . htmlspecialchars($url) . "' target='_blank' rel='noopener noreferrer'"
            . ($title !== '' ? " title='" . $title . "'" : '') . ">"
            . $label . "&nbsp;<i class='ti ti-external-link'></i></a>";
    }

    /** Data/hora do banco para exibir, ou "-" se vazia ou zerada. */
    private static function date($value): string {
        $value = trim((string) $value);
        if ($value === '' || substr($value, 0, 4) === '0000') {
            return '-';
        }

        return Html::convDateTime($value);
    }

    private static function showLaudos(int $radio_id): void {
        $laudos = self::getLaudos($radio_id);

        echo "<div class='spaced'>";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr class='tab_bg_2'>";
        echo "<th>" . __('Laudo', 'radios') . "</th>";
        echo "<th>" . __('Situação', 'radios') . "</th>";
        echo "<th>" . __('Setor', 'radios') . "</th>";
        echo "<th>" . __('Data', 'radios') . "</th>";
        echo "<th>" . __('Chamado', 'radios') . "</th>";
        echo "</tr>";

        if (!count($laudos)) {
            echo "<tr class='tab_bg_1'><td colspan='5' class='center'>"
                . __('Nenhum laudo vinculado a este rádio.', 'radios') . "</td></tr>";
        }

        foreach ($laudos as $laudo) {
            $name = self::text($laudo['name'] ?: sprintf(__('Laudo %d', 'radios'), $laudo['id']));
            $url  = self::getLaudoUrl((int) $laudo['id']);

            echo "<tr class='tab_bg_1'>";
            echo "<td>" . ($url !== null ? self::newTabLink($url, $name) : $name) . "</td>";
            echo "<td>" . self::text($laudo['status_name'] ?? '') . "</td>";
            echo "<td>" . self::text($laudo['group_name'] ?? '') . "</td>";
            // Data do laudo; sem ela (laudo antigo), a de criação
            $date = self::date($laudo['date_start']);
            if ($date === '-') {
                $date = self::date($laudo['date_creation']);
            }
            echo "<td>" . $date . "</td>";
            echo "<td>" . self::ticketCell((int) $laudo['tickets_id'], (string) ($laudo['ticket_reference'] ?? '')) . "</td>";
            echo "</tr>";
        }

        echo "</table>";
        echo "</div>";
    }

    /**
     * Célula do chamado de um laudo: o número como link para o chamado (nova guia); sem chamado
     * vinculado (laudo antigo), a referência em texto livre; sem direito de ver, só o número.
     */
    private static function ticketCell(int $tickets_id, string $reference): string {
        if ($tickets_id <= 0) {
            return self::text(trim($reference)) ?: '-';
        }

        $ticket = self::getViewableTicket($tickets_id);
        if ($ticket === null) {
            return (string) $tickets_id;
        }

        return self::newTabLink(Ticket::getFormURLWithID($tickets_id), (string) $tickets_id, self::text($ticket->fields['name'] ?? ''));
    }

    private static function showTickets(int $radio_id): void {
        $tickets = self::getTickets($radio_id);

        echo "<div class='spaced'>";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr class='tab_bg_2'>";
        echo "<th>" . __('ID') . "</th>";
        echo "<th>" . __('Título', 'radios') . "</th>";
        echo "<th>" . __('Status', 'radios') . "</th>";
        echo "<th>" . __('Abertura', 'radios') . "</th>";
        echo "<th>" . __('Origem', 'radios') . "</th>";
        echo "</tr>";

        if (!count($tickets)) {
            echo "<tr class='tab_bg_1'><td colspan='5' class='center'>"
                . __('Nenhum chamado vinculado a este rádio.', 'radios') . "</td></tr>";
        }

        foreach ($tickets as $row) {
            $ticket = self::getViewableTicket($row['id']);

            echo "<tr class='tab_bg_1'>";
            if ($ticket !== null) {
                echo "<td>" . self::newTabLink(Ticket::getFormURLWithID($row['id']), (string) $row['id']) . "</td>";
                echo "<td>" . self::text($row['name'])
                    . ($row['is_deleted'] ? " <span class='text-muted'>(" . __('na lixeira', 'radios') . ")</span>" : '') . "</td>";
                echo "<td>" . Ticket::getStatus($row['status']) . "</td>";
                echo "<td>" . self::date($row['date']) . "</td>";
            } else {
                // Sem direito de abrir o chamado: só o número, sem título nem link
                echo "<td>" . $row['id'] . "</td><td>-</td><td>-</td><td>-</td>";
            }
            echo "<td>" . self::originCell($row) . "</td>";
            echo "</tr>";
        }

        echo "</table>";
        echo "</div>";
    }

    /** De onde vem o vínculo: do campo "Itens" do chamado e/ou de laudos do rádio. */
    private static function originCell(array $row): string {
        $parts = [];

        if ($row['from_item']) {
            $parts[] = __('Item do chamado', 'radios');
        }
        foreach ($row['laudos'] as $laudos_id => $name) {
            $label = self::text($name ?: sprintf(__('Laudo %d', 'radios'), $laudos_id));
            $url   = self::getLaudoUrl((int) $laudos_id);
            $parts[] = $url !== null
                ? sprintf(__('Laudo %s', 'radios'), self::newTabLink($url, $label))
                : sprintf(__('Laudo %s', 'radios'), $label);
        }

        return implode('<br>', $parts);
    }
}
