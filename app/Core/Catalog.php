<?php
namespace App\Core;

/**
 * Service Catalog helpers compartidos por los 3 puntos de creación de ticket
 * (portal público, portal de empresa, form interno del agente).
 *
 * Provee:
 *   - listFor*(): obtiene items según contexto (público / autenticado / agente)
 *   - findItem(): busca un item validado por tenant
 *   - applyToTicket(): mete catalog_item_id, copia SLA y arma approval state
 *   - notifyApprover(): manda email al aprobador cuando un ticket queda pending
 */
class Catalog
{
    /** Items visibles en el portal público (is_public=1). */
    public static function listPublic(int $tenantId): array
    {
        $db = Application::get()->db;
        return $db->all(
            "SELECT s.*, c.name AS category_name FROM service_catalog_items s
             LEFT JOIN ticket_categories c ON c.id = s.category_id
             WHERE s.tenant_id = ? AND s.is_active = 1 AND s.is_public = 1
             ORDER BY s.sort_order, s.name",
            [$tenantId]
        );
    }

    /** Items visibles en el portal de empresa autenticado: todos los activos. */
    public static function listForCompany(int $tenantId): array
    {
        $db = Application::get()->db;
        return $db->all(
            "SELECT s.*, c.name AS category_name FROM service_catalog_items s
             LEFT JOIN ticket_categories c ON c.id = s.category_id
             WHERE s.tenant_id = ? AND s.is_active = 1
             ORDER BY s.sort_order, s.name",
            [$tenantId]
        );
    }

    /** Items para el form interno del agente: todos los activos. */
    public static function listForAgent(int $tenantId): array
    {
        return self::listForCompany($tenantId);
    }

    public static function findItem(int $tenantId, int $itemId, bool $publicOnly = false): ?array
    {
        if ($itemId <= 0) return null;
        $db = Application::get()->db;
        $where = 'id=? AND tenant_id=? AND is_active=1';
        $args = [$itemId, $tenantId];
        if ($publicOnly) { $where .= ' AND is_public=1'; }
        $row = $db->one("SELECT * FROM service_catalog_items WHERE $where", $args);
        return $row ?: null;
    }

    /**
     * Construye los campos a sumar al INSERT de tickets desde un item del catálogo.
     * Mergea con la data ya armada por el caller (no pisa subject/description si ya vienen).
     *
     * @return array ['data' => array, 'item' => array, 'pending_approval' => bool]
     */
    public static function buildTicketFields(array $item, array $base = []): array
    {
        $data = $base;
        // Si la categoría no fue elegida explícitamente, usar la del item
        if (empty($data['category_id']) && !empty($item['category_id'])) {
            $data['category_id'] = (int)$item['category_id'];
        }
        // Trazabilidad
        $data['catalog_item_id'] = (int)$item['id'];

        // Aplicar SLA del item: convertir minutos a sla_due_at desde NOW()
        if (!empty($item['sla_minutes'])) {
            $data['sla_due_at'] = date('Y-m-d H:i:s', time() + ((int)$item['sla_minutes']) * 60);
        }

        // Estado de aprobación
        $pending = false;
        if (!empty($item['requires_approval'])) {
            $data['approval_status']  = 'pending';
            $data['approval_user_id'] = !empty($item['approver_user_id']) ? (int)$item['approver_user_id'] : null;
            $data['status']           = 'on_hold';
            $pending = true;
        }

        return ['data' => $data, 'item' => $item, 'pending_approval' => $pending];
    }

    /** Notifica al aprobador (si existe) que un ticket espera su decisión. */
    public static function notifyApprover(Tenant $tenant, array $item, array $ticket): void
    {
        if (empty($item['requires_approval'])) return;
        $approverId = (int)($item['approver_user_id'] ?? 0);
        if (!$approverId) return;

        $db = Application::get()->db;
        $approver = $db->one('SELECT id, name, email FROM users WHERE id=? AND tenant_id=? AND is_active=1', [$approverId, $tenant->id]);
        if (!$approver || empty($approver['email'])) return;

        try {
            $appUrl = rtrim(Application::get()->config['app']['url'] ?? '', '/');
            $url = $appUrl . '/t/' . $tenant->slug . '/tickets/' . (int)$ticket['id'];

            $inner = '<p>Hola <strong>' . htmlspecialchars($approver['name']) . '</strong>,</p>'
                . '<p>Se solicitó el servicio <strong>' . htmlspecialchars($item['name']) . '</strong> y necesita tu aprobación antes de continuar.</p>'
                . '<p><strong>Ticket:</strong> ' . htmlspecialchars($ticket['code']) . '<br>'
                . '<strong>Asunto:</strong> ' . htmlspecialchars($ticket['subject']) . '<br>'
                . '<strong>Solicitante:</strong> ' . htmlspecialchars($ticket['requester_name']) . ' &lt;' . htmlspecialchars($ticket['requester_email']) . '&gt;</p>'
                . '<p>Desde el ticket podés aprobar o rechazar la solicitud.</p>';

            (new Mailer())->send(
                ['email' => $approver['email'], 'name' => $approver['name']],
                '[' . $ticket['code'] . '] Aprobación requerida · ' . $item['name'],
                Mailer::template('Aprobación requerida', $inner, 'Revisar y decidir', $url)
            );
        } catch (\Throwable $e) { /* ignore */ }
    }
}
