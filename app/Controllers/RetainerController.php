<?php
namespace App\Controllers;

use App\Core\Controller;

class RetainerController extends Controller
{
    /** Unidades válidas para items / categorías. */
    public const UNITS = ['hour','ticket','user','license','project','month','custom'];

    public function index(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('retainers');
        $this->requireCan('retainers.view');

        $q = trim((string)$this->input('q', ''));
        $status = (string)$this->input('status', '');
        $categoryId = (int)$this->input('category_id', 0);

        $where = ['r.tenant_id = ?'];
        $args = [$tenant->id];
        if ($q !== '') {
            $where[] = '(r.name LIKE ? OR r.code LIKE ? OR r.client_name LIKE ? OR c.name LIKE ?)';
            $like = "%$q%";
            $args[] = $like; $args[] = $like; $args[] = $like; $args[] = $like;
        }
        if (in_array($status, ['draft','active','paused','cancelled','expired'], true)) {
            $where[] = 'r.status = ?';
            $args[] = $status;
        }
        if ($categoryId > 0) { $where[] = 'r.category_id = ?'; $args[] = $categoryId; }

        $retainers = $this->db->all(
            "SELECT r.*, c.name AS company_name, cat.name AS category_name, cat.icon AS category_icon, cat.color AS category_color,
                    (SELECT COUNT(*) FROM retainer_periods p WHERE p.retainer_id = r.id) AS periods,
                    (SELECT COUNT(*) FROM retainer_items i WHERE i.retainer_id = r.id) AS items_count,
                    (SELECT IFNULL(SUM(rc.hours), 0) FROM retainer_consumptions rc
                       JOIN retainer_periods rp ON rp.id = rc.period_id
                       WHERE rc.retainer_id = r.id AND rp.status = 'open') AS consumed_open
             FROM retainers r
             LEFT JOIN companies c ON c.id = r.company_id
             LEFT JOIN retainer_categories cat ON cat.id = r.category_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY r.status='active' DESC, r.created_at DESC
             LIMIT 200",
            $args
        );

        $stats = [
            'total'    => (int)$this->db->val('SELECT COUNT(*) FROM retainers WHERE tenant_id = ?', [$tenant->id]),
            'active'   => (int)$this->db->val("SELECT COUNT(*) FROM retainers WHERE tenant_id = ? AND status='active'", [$tenant->id]),
            'paused'   => (int)$this->db->val("SELECT COUNT(*) FROM retainers WHERE tenant_id = ? AND status='paused'", [$tenant->id]),
            'mrr'      => (float)$this->db->val(
                "SELECT IFNULL(SUM(CASE billing_cycle
                            WHEN 'monthly' THEN amount
                            WHEN 'quarterly' THEN amount/3
                            WHEN 'yearly' THEN amount/12
                            ELSE 0 END), 0)
                 FROM retainers WHERE tenant_id = ? AND status='active'",
                [$tenant->id]
            ),
        ];

        $companies = $this->db->all('SELECT id, name FROM companies WHERE tenant_id = ? ORDER BY name', [$tenant->id]);
        $categories = $this->db->all('SELECT * FROM retainer_categories WHERE tenant_id = ? AND is_active = 1 ORDER BY sort_order, name', [$tenant->id]);

        $this->render('retainers/index', [
            'title' => 'Igualas',
            'retainers' => $retainers,
            'stats' => $stats,
            'companies' => $companies,
            'categories' => $categories,
            'q' => $q,
            'status' => $status,
            'categoryId' => $categoryId,
        ]);
    }

    public function create(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('retainers');
        $this->requireCan('retainers.create');

        $companies = $this->db->all('SELECT id, name FROM companies WHERE tenant_id = ? ORDER BY name', [$tenant->id]);
        $categories = $this->db->all('SELECT * FROM retainer_categories WHERE tenant_id = ? AND is_active = 1 ORDER BY sort_order, name', [$tenant->id]);
        $templates = $this->db->all('SELECT * FROM retainer_templates WHERE tenant_id = ? AND is_active = 1 ORDER BY sort_order, name', [$tenant->id]);

        // Si vienen con ?template_id=X cargar items del template
        $template = null; $templateItems = [];
        $tplId = (int)$this->input('template_id', 0);
        if ($tplId > 0) {
            $template = $this->db->one('SELECT * FROM retainer_templates WHERE id=? AND tenant_id=?', [$tplId, $tenant->id]);
            if ($template) {
                $templateItems = $this->db->all('SELECT * FROM retainer_template_items WHERE template_id=? ORDER BY sort_order, id', [$tplId]);
            }
        }

        $this->render('retainers/create', [
            'title' => 'Nueva iguala',
            'companies' => $companies,
            'categories' => $categories,
            'templates' => $templates,
            'template' => $template,
            'templateItems' => $templateItems,
            'units' => self::UNITS,
        ]);
    }

    public function store(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('retainers');
        $this->requireCan('retainers.create');
        $this->validateCsrf();

        $name = trim((string)$this->input('name', ''));
        if ($name === '') {
            $this->session->flash('error', 'El nombre de la iguala es obligatorio.');
            $this->redirect('/t/' . $tenant->slug . '/retainers/create');
        }
        $clientType = (string)$this->input('client_type', 'company');
        if (!in_array($clientType, ['company','individual'], true)) $clientType = 'company';

        $companyId = (int)$this->input('company_id', 0) ?: null;
        if ($clientType === 'company' && !$companyId) {
            $this->session->flash('error', 'Selecciona una empresa para la iguala.');
            $this->redirect('/t/' . $tenant->slug . '/retainers/create');
        }
        if ($clientType === 'individual' && trim((string)$this->input('client_name','')) === '') {
            $this->session->flash('error', 'Ingresa el nombre del cliente individual.');
            $this->redirect('/t/' . $tenant->slug . '/retainers/create');
        }

        $cycle = (string)$this->input('billing_cycle', 'monthly');
        if (!in_array($cycle, ['monthly','quarterly','yearly'], true)) $cycle = 'monthly';

        $startsOn = (string)$this->input('starts_on', date('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startsOn)) $startsOn = date('Y-m-d');
        $endsOn = (string)$this->input('ends_on', '');
        $endsOn = preg_match('/^\d{4}-\d{2}-\d{2}$/', $endsOn) ? $endsOn : null;

        $code = $this->generateCode($tenant->id);

        $categoryId = (int)$this->input('category_id', 0) ?: null;
        if ($categoryId) {
            $valid = $this->db->val('SELECT id FROM retainer_categories WHERE id=? AND tenant_id=?', [$categoryId, $tenant->id]);
            if (!$valid) $categoryId = null;
        }
        $templateId = (int)$this->input('template_id', 0) ?: null;

        $id = $this->db->insert('retainers', [
            'tenant_id'             => $tenant->id,
            'code'                  => $code,
            'name'                  => $name,
            'category_id'           => $categoryId,
            'template_id'           => $templateId,
            'client_type'           => $clientType,
            'company_id'            => $clientType === 'company' ? $companyId : null,
            'contact_id'            => null,
            'client_name'           => $clientType === 'individual' ? trim((string)$this->input('client_name','')) : null,
            'client_email'          => (string)$this->input('client_email','') ?: null,
            'client_phone'          => (string)$this->input('client_phone','') ?: null,
            'client_doc'            => (string)$this->input('client_doc','') ?: null,
            'description'           => (string)$this->input('description','') ?: null,
            'scope'                 => (string)$this->input('scope','') ?: null,
            'billing_cycle'         => $cycle,
            'amount'                => (float)$this->input('amount', 0),
            'currency'              => substr((string)$this->input('currency', 'USD'), 0, 8) ?: 'USD',
            'tax_pct'               => max(0, min(100, (float)$this->input('tax_pct', 0))),
            'payment_terms'         => (string)$this->input('payment_terms','') ?: null,
            'included_hours'        => (float)$this->input('included_hours', 0),
            'included_tickets'      => (int)$this->input('included_tickets', 0),
            'overage_hour_rate'     => (float)$this->input('overage_hour_rate', 0),
            'response_sla_minutes'  => ((int)$this->input('response_sla_minutes', 0)) ?: null,
            'resolve_sla_minutes'   => ((int)$this->input('resolve_sla_minutes', 0)) ?: null,
            'starts_on'             => $startsOn,
            'ends_on'               => $endsOn,
            'next_invoice_on'       => $startsOn,
            'auto_renew'            => (int)($this->input('auto_renew') ? 1 : 0),
            'status'                => in_array($this->input('status'), ['draft','active','paused'], true) ? (string)$this->input('status') : 'active',
            'notes'                 => (string)$this->input('notes','') ?: null,
            'custom_fields'         => null,
            'created_by'            => $this->auth->userId(),
        ]);

        // Cargar items: desde template o desde el form
        $items = (array)$this->input('items', []);
        if (empty($items) && $templateId) {
            $tplItems = $this->db->all('SELECT * FROM retainer_template_items WHERE template_id = ?', [$templateId]);
            foreach ($tplItems as $ti) {
                $items[] = [
                    'category_id' => $ti['category_id'],
                    'title'       => $ti['title'],
                    'description' => $ti['description'],
                    'quantity'    => $ti['quantity'],
                    'unit'        => $ti['unit'],
                    'unit_label'  => $ti['unit_label'],
                    'unit_rate'   => $ti['unit_rate'],
                    'amount'      => $ti['amount'],
                    'is_recurring'=> $ti['is_recurring'],
                    'is_billable' => $ti['is_billable'],
                ];
            }
        }
        $this->saveItems((int)$id, $tenant->id, $items);

        // Crear primer período si está activa
        if ($this->input('status', 'active') !== 'draft') {
            $this->openPeriod((int)$id, $tenant->id, $cycle, $startsOn, (float)$this->input('amount',0), (float)$this->input('included_hours',0));
        }

        $this->logAudit('retainer.created', 'retainer', (int)$id, ['name' => $name, 'code' => $code]);
        $this->session->flash('success', 'Iguala creada correctamente.');
        $this->redirect('/t/' . $tenant->slug . '/retainers/' . $id);
    }

    public function show(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('retainers');
        $this->requireCan('retainers.view');

        $id = (int)$params['id'];
        $r = $this->db->one(
            "SELECT r.*, c.name AS company_name, u.name AS creator_name,
                    cat.name AS category_name, cat.icon AS category_icon, cat.color AS category_color,
                    tpl.name AS template_name
             FROM retainers r
             LEFT JOIN companies c ON c.id = r.company_id
             LEFT JOIN users u ON u.id = r.created_by
             LEFT JOIN retainer_categories cat ON cat.id = r.category_id
             LEFT JOIN retainer_templates tpl ON tpl.id = r.template_id
             WHERE r.id = ? AND r.tenant_id = ?",
            [$id, $tenant->id]
        );
        if (!$r) {
            $this->session->flash('error', 'Iguala no encontrada.');
            $this->redirect('/t/' . $tenant->slug . '/retainers');
        }

        $items = $this->db->all(
            "SELECT i.*, c.name AS category_name, c.icon AS category_icon, c.color AS category_color
             FROM retainer_items i
             LEFT JOIN retainer_categories c ON c.id = i.category_id
             WHERE i.retainer_id = ? ORDER BY i.sort_order, i.id",
            [$id]
        );
        $periods = $this->db->all(
            'SELECT * FROM retainer_periods WHERE retainer_id = ? ORDER BY period_start DESC LIMIT 24',
            [$id]
        );
        $consumptions = $this->db->all(
            "SELECT rc.*, t.code AS ticket_code, t.subject AS ticket_subject, u.name AS user_name
             FROM retainer_consumptions rc
             LEFT JOIN tickets t ON t.id = rc.ticket_id
             LEFT JOIN users u ON u.id = rc.user_id
             WHERE rc.retainer_id = ?
             ORDER BY rc.consumed_at DESC LIMIT 50",
            [$id]
        );
        $companies = $this->db->all('SELECT id, name FROM companies WHERE tenant_id = ? ORDER BY name', [$tenant->id]);
        $categories = $this->db->all('SELECT * FROM retainer_categories WHERE tenant_id = ? AND is_active = 1 ORDER BY sort_order, name', [$tenant->id]);
        $tickets = [];
        if ($r['company_id']) {
            $tickets = $this->db->all(
                "SELECT id, code, subject FROM tickets WHERE tenant_id = ? AND company_id = ? ORDER BY created_at DESC LIMIT 100",
                [$tenant->id, (int)$r['company_id']]
            );
        }
        $currentPeriod = $this->db->one(
            "SELECT * FROM retainer_periods WHERE retainer_id = ? AND status='open' ORDER BY period_start DESC LIMIT 1",
            [$id]
        );
        $totalConsumed = (float)$this->db->val(
            'SELECT IFNULL(SUM(hours),0) FROM retainer_consumptions WHERE retainer_id = ?',
            [$id]
        );

        $this->render('retainers/show', [
            'title' => $r['name'],
            'r' => $r,
            'items' => $items,
            'periods' => $periods,
            'consumptions' => $consumptions,
            'companies' => $companies,
            'categories' => $categories,
            'tickets' => $tickets,
            'currentPeriod' => $currentPeriod,
            'totalConsumed' => $totalConsumed,
            'units' => self::UNITS,
        ]);
    }

    public function update(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('retainers');
        $this->requireCan('retainers.edit');
        $this->validateCsrf();

        $id = (int)$params['id'];
        $r = $this->db->one('SELECT * FROM retainers WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if (!$r) {
            $this->session->flash('error', 'Iguala no encontrada.');
            $this->redirect('/t/' . $tenant->slug . '/retainers');
        }

        $cycle = (string)$this->input('billing_cycle', $r['billing_cycle']);
        if (!in_array($cycle, ['monthly','quarterly','yearly'], true)) $cycle = $r['billing_cycle'];
        $clientType = (string)$this->input('client_type', $r['client_type']);
        if (!in_array($clientType, ['company','individual'], true)) $clientType = $r['client_type'];

        $endsOn = (string)$this->input('ends_on', '');
        $endsOn = preg_match('/^\d{4}-\d{2}-\d{2}$/', $endsOn) ? $endsOn : null;

        $categoryId = (int)$this->input('category_id', 0) ?: null;
        if ($categoryId) {
            $valid = $this->db->val('SELECT id FROM retainer_categories WHERE id=? AND tenant_id=?', [$categoryId, $tenant->id]);
            if (!$valid) $categoryId = null;
        }

        $data = [
            'name'                  => trim((string)$this->input('name', $r['name'])),
            'category_id'           => $categoryId,
            'client_type'           => $clientType,
            'company_id'            => $clientType === 'company' ? ((int)$this->input('company_id', 0) ?: null) : null,
            'client_name'           => $clientType === 'individual' ? trim((string)$this->input('client_name','')) : null,
            'client_email'          => (string)$this->input('client_email','') ?: null,
            'client_phone'          => (string)$this->input('client_phone','') ?: null,
            'client_doc'            => (string)$this->input('client_doc','') ?: null,
            'description'           => (string)$this->input('description','') ?: null,
            'scope'                 => (string)$this->input('scope','') ?: null,
            'billing_cycle'         => $cycle,
            'amount'                => (float)$this->input('amount', $r['amount']),
            'currency'              => substr((string)$this->input('currency', $r['currency']), 0, 8) ?: 'USD',
            'tax_pct'               => max(0, min(100, (float)$this->input('tax_pct', (float)$r['tax_pct']))),
            'payment_terms'         => (string)$this->input('payment_terms','') ?: null,
            'included_hours'        => (float)$this->input('included_hours', $r['included_hours']),
            'included_tickets'      => (int)$this->input('included_tickets', $r['included_tickets']),
            'overage_hour_rate'     => (float)$this->input('overage_hour_rate', $r['overage_hour_rate']),
            'response_sla_minutes'  => ((int)$this->input('response_sla_minutes', 0)) ?: null,
            'resolve_sla_minutes'   => ((int)$this->input('resolve_sla_minutes', 0)) ?: null,
            'ends_on'               => $endsOn,
            'auto_renew'            => (int)($this->input('auto_renew') ? 1 : 0),
            'status'                => in_array($this->input('status'), ['draft','active','paused','cancelled','expired'], true) ? (string)$this->input('status') : $r['status'],
            'notes'                 => (string)$this->input('notes','') ?: null,
        ];
        $this->db->update('retainers', $data, 'id=? AND tenant_id=?', [$id, $tenant->id]);

        // Items: si el form envió 'items', reemplazamos completamente
        if ($this->input('items') !== null) {
            $this->db->delete('retainer_items', 'retainer_id = ? AND tenant_id = ?', [$id, $tenant->id]);
            $this->saveItems($id, $tenant->id, (array)$this->input('items', []));
        }

        $this->logAudit('retainer.updated', 'retainer', $id);
        $this->session->flash('success', 'Iguala actualizada.');
        $this->redirect('/t/' . $tenant->slug . '/retainers/' . $id);
    }

    public function delete(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('retainers');
        $this->requireCan('retainers.delete');
        $this->validateCsrf();

        $id = (int)$params['id'];
        $r = $this->db->one('SELECT * FROM retainers WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if (!$r) { $this->redirect('/t/' . $tenant->slug . '/retainers'); }

        $this->db->delete('retainer_consumptions', 'retainer_id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->db->delete('retainer_periods', 'retainer_id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->db->delete('retainer_items', 'retainer_id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->db->delete('retainers', 'id=? AND tenant_id=?', [$id, $tenant->id]);

        $this->logAudit('retainer.deleted', 'retainer', $id, ['name' => $r['name']]);
        $this->session->flash('success', 'Iguala eliminada.');
        $this->redirect('/t/' . $tenant->slug . '/retainers');
    }

    public function logConsumption(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('retainers');
        $this->requireCan('retainers.bill');
        $this->validateCsrf();

        $id = (int)$params['id'];
        $r = $this->db->one('SELECT * FROM retainers WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if (!$r) { $this->redirect('/t/' . $tenant->slug . '/retainers'); }

        $period = $this->db->one("SELECT * FROM retainer_periods WHERE retainer_id=? AND status='open' ORDER BY period_start DESC LIMIT 1", [$id]);
        if (!$period) {
            $period = ['id' => $this->openPeriod($id, $tenant->id, $r['billing_cycle'], date('Y-m-d'), (float)$r['amount'], (float)$r['included_hours'])];
        }

        $hours = (float)$this->input('hours', 0);
        if ($hours <= 0) {
            $this->session->flash('error', 'Las horas consumidas deben ser mayores a cero.');
            $this->redirect('/t/' . $tenant->slug . '/retainers/' . $id);
        }

        $consumedAt = (string)$this->input('consumed_at', date('Y-m-d H:i:s'));
        $consumedAt = $consumedAt !== '' ? str_replace('T', ' ', $consumedAt) : date('Y-m-d H:i:s');
        if (strlen($consumedAt) === 16) $consumedAt .= ':00';

        $this->db->insert('retainer_consumptions', [
            'retainer_id' => $id,
            'period_id'   => (int)$period['id'],
            'tenant_id'   => $tenant->id,
            'ticket_id'   => ((int)$this->input('ticket_id', 0)) ?: null,
            'user_id'     => $this->auth->userId(),
            'consumed_at' => $consumedAt,
            'hours'       => $hours,
            'description' => (string)$this->input('description','') ?: null,
            'billable'    => (int)($this->input('billable', 1) ? 1 : 0),
        ]);

        $consumed = (float)$this->db->val('SELECT IFNULL(SUM(hours),0) FROM retainer_consumptions WHERE period_id = ?', [(int)$period['id']]);
        $included = (float)$period['included_hours'];
        $rate = (float)$r['overage_hour_rate'];
        $overage = max(0.0, $consumed - $included) * $rate;

        $this->db->update('retainer_periods', [
            'consumed_hours' => $consumed,
            'overage_amount' => $overage,
        ], 'id = :id', ['id' => (int)$period['id']]);

        $this->logAudit('retainer.consumption_logged', 'retainer', $id, ['hours' => $hours, 'period_id' => (int)$period['id']]);
        $this->session->flash('success', 'Consumo registrado.');
        $this->redirect('/t/' . $tenant->slug . '/retainers/' . $id);
    }

    public function closePeriod(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('retainers');
        $this->requireCan('retainers.bill');
        $this->validateCsrf();

        $id = (int)$params['id'];
        $periodId = (int)$params['periodId'];
        $r = $this->db->one('SELECT * FROM retainers WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        $p = $this->db->one('SELECT * FROM retainer_periods WHERE id=? AND retainer_id=? AND tenant_id=?', [$periodId, $id, $tenant->id]);
        if (!$r || !$p) { $this->redirect('/t/' . $tenant->slug . '/retainers/' . $id); }

        $this->db->update('retainer_periods', [
            'status'      => 'closed',
            'invoiced_at' => date('Y-m-d H:i:s'),
        ], 'id = :id', ['id' => $periodId]);

        if ($r['status'] === 'active' && (int)$r['auto_renew'] === 1) {
            $next = $this->advanceDate($p['period_end'], $r['billing_cycle']);
            $this->openPeriod($id, $tenant->id, $r['billing_cycle'], $next, (float)$r['amount'], (float)$r['included_hours']);
            $this->db->update('retainers', ['next_invoice_on' => $next], 'id=? AND tenant_id=?', [$id, $tenant->id]);
        }

        $this->logAudit('retainer.period_closed', 'retainer', $id, ['period_id' => $periodId]);
        $this->session->flash('success', 'Período cerrado.');
        $this->redirect('/t/' . $tenant->slug . '/retainers/' . $id);
    }

    /* ───────── Settings UI: Categorías + Plantillas ───────── */

    public function settings(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('retainers');
        $this->requireCan('retainers.config');

        $tab = (string)$this->input('tab', 'categories');
        $categories = $this->db->all(
            'SELECT c.*, (SELECT COUNT(*) FROM retainers r WHERE r.category_id = c.id) AS retainers_count
             FROM retainer_categories c WHERE c.tenant_id = ? ORDER BY c.sort_order, c.name',
            [$tenant->id]
        );
        $templates = $this->db->all(
            'SELECT t.*, c.name AS category_name, c.color AS category_color, c.icon AS category_icon,
                    (SELECT COUNT(*) FROM retainer_template_items i WHERE i.template_id = t.id) AS items_count
             FROM retainer_templates t LEFT JOIN retainer_categories c ON c.id = t.category_id
             WHERE t.tenant_id = ? ORDER BY t.sort_order, t.name',
            [$tenant->id]
        );

        $this->render('retainers/settings', [
            'title' => 'Configuración de Igualas',
            'tab' => in_array($tab, ['categories','templates'], true) ? $tab : 'categories',
            'categories' => $categories,
            'templates' => $templates,
            'units' => self::UNITS,
        ]);
    }

    public function categoryStore(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('retainers');
        $this->requireCan('retainers.config');
        $this->validateCsrf();

        $name = trim((string)$this->input('name', ''));
        if ($name === '') {
            $this->session->flash('error', 'El nombre es obligatorio.');
            $this->redirect('/t/' . $tenant->slug . '/retainers/settings?tab=categories');
        }
        $slug = $this->uniqueCategorySlug($tenant->id, $name);
        $unit = (string)$this->input('default_unit', 'hour');
        if (!in_array($unit, self::UNITS, true)) $unit = 'hour';
        $color = (string)$this->input('color', '#10b981');
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) $color = '#10b981';

        $this->db->insert('retainer_categories', [
            'tenant_id'          => $tenant->id,
            'slug'               => $slug,
            'name'               => $name,
            'description'        => (string)$this->input('description','') ?: null,
            'icon'               => (string)$this->input('icon', 'briefcase') ?: 'briefcase',
            'color'              => $color,
            'default_unit'       => $unit,
            'default_unit_label' => (string)$this->input('default_unit_label','') ?: null,
            'is_active'          => (int)($this->input('is_active', 1) ? 1 : 0),
            'sort_order'         => (int)$this->input('sort_order', 0),
        ]);
        $this->logAudit('retainer.category_created', 'retainer_category', 0, ['name' => $name]);
        $this->session->flash('success', 'Categoría creada.');
        $this->redirect('/t/' . $tenant->slug . '/retainers/settings?tab=categories');
    }

    public function categoryUpdate(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('retainers');
        $this->requireCan('retainers.config');
        $this->validateCsrf();

        $id = (int)$params['id'];
        $cat = $this->db->one('SELECT * FROM retainer_categories WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if (!$cat) { $this->redirect('/t/' . $tenant->slug . '/retainers/settings?tab=categories'); }

        $name = trim((string)$this->input('name', $cat['name']));
        $unit = (string)$this->input('default_unit', $cat['default_unit']);
        if (!in_array($unit, self::UNITS, true)) $unit = $cat['default_unit'];
        $color = (string)$this->input('color', $cat['color']);
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) $color = $cat['color'];

        $this->db->update('retainer_categories', [
            'name'               => $name,
            'description'        => (string)$this->input('description','') ?: null,
            'icon'               => (string)$this->input('icon', $cat['icon']) ?: 'briefcase',
            'color'              => $color,
            'default_unit'       => $unit,
            'default_unit_label' => (string)$this->input('default_unit_label','') ?: null,
            'is_active'          => (int)($this->input('is_active', 0) ? 1 : 0),
            'sort_order'         => (int)$this->input('sort_order', 0),
        ], 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->logAudit('retainer.category_updated', 'retainer_category', $id);
        $this->session->flash('success', 'Categoría actualizada.');
        $this->redirect('/t/' . $tenant->slug . '/retainers/settings?tab=categories');
    }

    public function categoryDelete(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('retainers');
        $this->requireCan('retainers.config');
        $this->validateCsrf();

        $id = (int)$params['id'];
        $this->db->run('UPDATE retainers SET category_id = NULL WHERE category_id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->db->run('UPDATE retainer_items SET category_id = NULL WHERE category_id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->db->delete('retainer_categories', 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->logAudit('retainer.category_deleted', 'retainer_category', $id);
        $this->session->flash('success', 'Categoría eliminada.');
        $this->redirect('/t/' . $tenant->slug . '/retainers/settings?tab=categories');
    }

    public function templateStore(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('retainers');
        $this->requireCan('retainers.config');
        $this->validateCsrf();

        $name = trim((string)$this->input('name', ''));
        if ($name === '') {
            $this->session->flash('error', 'El nombre es obligatorio.');
            $this->redirect('/t/' . $tenant->slug . '/retainers/settings?tab=templates');
        }
        $cycle = (string)$this->input('billing_cycle', 'monthly');
        if (!in_array($cycle, ['monthly','quarterly','yearly'], true)) $cycle = 'monthly';

        $tplId = $this->db->insert('retainer_templates', [
            'tenant_id'         => $tenant->id,
            'category_id'       => ((int)$this->input('category_id', 0)) ?: null,
            'name'              => $name,
            'description'       => (string)$this->input('description','') ?: null,
            'billing_cycle'     => $cycle,
            'amount'            => (float)$this->input('amount', 0),
            'currency'          => substr((string)$this->input('currency', 'USD'), 0, 8) ?: 'USD',
            'included_hours'    => (float)$this->input('included_hours', 0),
            'included_tickets'  => (int)$this->input('included_tickets', 0),
            'overage_hour_rate' => (float)$this->input('overage_hour_rate', 0),
            'tax_pct'           => max(0, min(100, (float)$this->input('tax_pct', 0))),
            'payment_terms'     => (string)$this->input('payment_terms','') ?: null,
            'scope'             => (string)$this->input('scope','') ?: null,
            'is_active'         => (int)($this->input('is_active', 1) ? 1 : 0),
            'sort_order'        => (int)$this->input('sort_order', 0),
        ]);

        $items = (array)$this->input('items', []);
        $this->saveTemplateItems((int)$tplId, $items);
        $this->logAudit('retainer.template_created', 'retainer_template', (int)$tplId, ['name' => $name]);
        $this->session->flash('success', 'Plantilla creada.');
        $this->redirect('/t/' . $tenant->slug . '/retainers/settings?tab=templates');
    }

    public function templateUpdate(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('retainers');
        $this->requireCan('retainers.config');
        $this->validateCsrf();

        $id = (int)$params['id'];
        $tpl = $this->db->one('SELECT * FROM retainer_templates WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if (!$tpl) { $this->redirect('/t/' . $tenant->slug . '/retainers/settings?tab=templates'); }

        $cycle = (string)$this->input('billing_cycle', $tpl['billing_cycle']);
        if (!in_array($cycle, ['monthly','quarterly','yearly'], true)) $cycle = $tpl['billing_cycle'];

        $this->db->update('retainer_templates', [
            'category_id'       => ((int)$this->input('category_id', 0)) ?: null,
            'name'              => trim((string)$this->input('name', $tpl['name'])),
            'description'       => (string)$this->input('description','') ?: null,
            'billing_cycle'     => $cycle,
            'amount'            => (float)$this->input('amount', $tpl['amount']),
            'currency'          => substr((string)$this->input('currency', $tpl['currency']), 0, 8) ?: 'USD',
            'included_hours'    => (float)$this->input('included_hours', $tpl['included_hours']),
            'included_tickets'  => (int)$this->input('included_tickets', $tpl['included_tickets']),
            'overage_hour_rate' => (float)$this->input('overage_hour_rate', $tpl['overage_hour_rate']),
            'tax_pct'           => max(0, min(100, (float)$this->input('tax_pct', (float)$tpl['tax_pct']))),
            'payment_terms'     => (string)$this->input('payment_terms','') ?: null,
            'scope'             => (string)$this->input('scope','') ?: null,
            'is_active'         => (int)($this->input('is_active', 0) ? 1 : 0),
            'sort_order'        => (int)$this->input('sort_order', 0),
        ], 'id=? AND tenant_id=?', [$id, $tenant->id]);

        if ($this->input('items') !== null) {
            $this->db->delete('retainer_template_items', 'template_id=?', [$id]);
            $this->saveTemplateItems($id, (array)$this->input('items', []));
        }
        $this->logAudit('retainer.template_updated', 'retainer_template', $id);
        $this->session->flash('success', 'Plantilla actualizada.');
        $this->redirect('/t/' . $tenant->slug . '/retainers/settings?tab=templates');
    }

    public function templateDelete(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('retainers');
        $this->requireCan('retainers.config');
        $this->validateCsrf();

        $id = (int)$params['id'];
        $this->db->delete('retainer_template_items', 'template_id=?', [$id]);
        $this->db->delete('retainer_templates', 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->logAudit('retainer.template_deleted', 'retainer_template', $id);
        $this->session->flash('success', 'Plantilla eliminada.');
        $this->redirect('/t/' . $tenant->slug . '/retainers/settings?tab=templates');
    }

    /* ───────── Helpers privados ───────── */

    /** Persiste una lista de items (de un retainer). Reemplaza por completo. */
    protected function saveItems(int $retainerId, int $tenantId, array $items): void
    {
        $i = 0;
        foreach ($items as $row) {
            if (!is_array($row)) continue;
            $title = trim((string)($row['title'] ?? ''));
            if ($title === '') continue;
            $unit = (string)($row['unit'] ?? 'hour');
            if (!in_array($unit, self::UNITS, true)) $unit = 'hour';
            $qty = (float)($row['quantity'] ?? 1);
            $rate = (float)($row['unit_rate'] ?? 0);
            $amount = isset($row['amount']) && $row['amount'] !== '' ? (float)$row['amount'] : ($qty * $rate);
            $this->db->insert('retainer_items', [
                'tenant_id'    => $tenantId,
                'retainer_id'  => $retainerId,
                'category_id'  => ((int)($row['category_id'] ?? 0)) ?: null,
                'title'        => $title,
                'description'  => trim((string)($row['description'] ?? '')) ?: null,
                'quantity'     => $qty,
                'unit'         => $unit,
                'unit_label'   => trim((string)($row['unit_label'] ?? '')) ?: null,
                'unit_rate'    => $rate,
                'amount'       => $amount,
                'is_recurring' => (int)(!empty($row['is_recurring']) ? 1 : 0),
                'is_billable'  => (int)(!empty($row['is_billable'])  ? 1 : 0),
                'sort_order'   => $i++,
            ]);
        }
    }

    protected function saveTemplateItems(int $templateId, array $items): void
    {
        $i = 0;
        foreach ($items as $row) {
            if (!is_array($row)) continue;
            $title = trim((string)($row['title'] ?? ''));
            if ($title === '') continue;
            $unit = (string)($row['unit'] ?? 'hour');
            if (!in_array($unit, self::UNITS, true)) $unit = 'hour';
            $qty = (float)($row['quantity'] ?? 1);
            $rate = (float)($row['unit_rate'] ?? 0);
            $amount = isset($row['amount']) && $row['amount'] !== '' ? (float)$row['amount'] : ($qty * $rate);
            $this->db->insert('retainer_template_items', [
                'template_id'  => $templateId,
                'category_id'  => ((int)($row['category_id'] ?? 0)) ?: null,
                'title'        => $title,
                'description'  => trim((string)($row['description'] ?? '')) ?: null,
                'quantity'     => $qty,
                'unit'         => $unit,
                'unit_label'   => trim((string)($row['unit_label'] ?? '')) ?: null,
                'unit_rate'    => $rate,
                'amount'       => $amount,
                'is_recurring' => (int)(!empty($row['is_recurring']) ? 1 : 0),
                'is_billable'  => (int)(!empty($row['is_billable'])  ? 1 : 0),
                'sort_order'   => $i++,
            ]);
        }
    }

    protected function uniqueCategorySlug(int $tenantId, string $name, ?int $exceptId = null): string
    {
        $base = preg_replace('/[^a-z0-9]+/', '-', strtolower($name));
        $base = trim($base, '-');
        if ($base === '') $base = 'cat';
        $slug = substr($base, 0, 70);
        $i = 1;
        while (true) {
            $sql = 'SELECT id FROM retainer_categories WHERE tenant_id=? AND slug=?';
            $args = [$tenantId, $slug];
            if ($exceptId) { $sql .= ' AND id<>?'; $args[] = $exceptId; }
            if (!$this->db->val($sql, $args)) return $slug;
            $i++;
            $slug = substr($base, 0, 70 - strlen((string)$i) - 1) . '-' . $i;
        }
    }

    /** Crea un nuevo período abierto y devuelve su id. */
    protected function openPeriod(int $retainerId, int $tenantId, string $cycle, string $start, float $amount, float $includedHours): int
    {
        $end = $this->advanceDate($start, $cycle);
        $endDate = date('Y-m-d', strtotime($end . ' -1 day'));
        return $this->db->insert('retainer_periods', [
            'retainer_id'    => $retainerId,
            'tenant_id'      => $tenantId,
            'period_start'   => $start,
            'period_end'     => $endDate,
            'amount'         => $amount,
            'included_hours' => $includedHours,
            'consumed_hours' => 0,
            'overage_amount' => 0,
            'status'         => 'open',
        ]);
    }

    protected function advanceDate(string $date, string $cycle): string
    {
        $modifiers = ['monthly' => '+1 month', 'quarterly' => '+3 months', 'yearly' => '+1 year'];
        $mod = $modifiers[$cycle] ?? '+1 month';
        return date('Y-m-d', strtotime($date . ' ' . $mod));
    }

    protected function generateCode(int $tenantId): string
    {
        for ($i = 0; $i < 6; $i++) {
            $code = 'IGL-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
            $exists = $this->db->val('SELECT id FROM retainers WHERE tenant_id=? AND code=?', [$tenantId, $code]);
            if (!$exists) return $code;
        }
        return 'IGL-' . substr((string)time(), -6);
    }

    protected function logAudit(string $action, string $entity, int $entityId, array $meta = []): void
    {
        $tenant = $this->app->tenant;
        if (!$tenant) return;
        try {
            $this->db->insert('audit_logs', [
                'tenant_id' => $tenant->id,
                'user_id'   => $this->auth->userId(),
                'action'    => $action,
                'entity'    => $entity,
                'entity_id' => $entityId,
                'meta'      => json_encode($meta, JSON_UNESCAPED_UNICODE),
                'ip'        => $_SERVER['REMOTE_ADDR'] ?? null,
                'ua'        => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            ]);
        } catch (\Throwable $_e) { /* tabla auditoría puede no existir */ }
    }
}
