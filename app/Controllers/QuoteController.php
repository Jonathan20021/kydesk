<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Mailer;
use App\Core\Pdf;

class QuoteController extends Controller
{
    public const STATUSES = ['draft','sent','viewed','accepted','rejected','expired','revised','converted'];
    public const UNITS    = ['hour','unit','license','service','project','month','custom'];

    /* ════════════════════════════════════════════════════════════════════
     * INDEX / LIST
     * ════════════════════════════════════════════════════════════════════ */
    public function index(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('quotes');
        $this->requireCan('quotes.view');

        $q = trim((string)$this->input('q', ''));
        $status = (string)$this->input('status', '');
        $clientType = (string)$this->input('client_type', '');

        $where = ['q.tenant_id=?'];
        $args = [$tenant->id];
        if ($q !== '') {
            $where[] = '(q.code LIKE ? OR q.title LIKE ? OR q.client_name LIKE ?)';
            $like = "%$q%"; $args[] = $like; $args[] = $like; $args[] = $like;
        }
        if (in_array($status, self::STATUSES, true)) { $where[] = 'q.status=?'; $args[] = $status; }
        if (in_array($clientType, ['company','individual','lead'], true)) { $where[] = 'q.client_type=?'; $args[] = $clientType; }

        $quotes = $this->db->all(
            "SELECT q.*, u.name AS owner_name
             FROM quotes q
             LEFT JOIN users u ON u.id=q.owner_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY q.created_at DESC LIMIT 300",
            $args
        );

        $stats = [
            'total'    => (int)$this->db->val('SELECT COUNT(*) FROM quotes WHERE tenant_id=?', [$tenant->id]),
            'draft'    => (int)$this->db->val("SELECT COUNT(*) FROM quotes WHERE tenant_id=? AND status='draft'", [$tenant->id]),
            'sent'     => (int)$this->db->val("SELECT COUNT(*) FROM quotes WHERE tenant_id=? AND status IN ('sent','viewed')", [$tenant->id]),
            'accepted' => (int)$this->db->val("SELECT COUNT(*) FROM quotes WHERE tenant_id=? AND status='accepted'", [$tenant->id]),
            'pipeline_value' => (float)$this->db->val("SELECT IFNULL(SUM(total),0) FROM quotes WHERE tenant_id=? AND status IN ('sent','viewed')", [$tenant->id]),
            'won_value'      => (float)$this->db->val("SELECT IFNULL(SUM(total),0) FROM quotes WHERE tenant_id=? AND status='accepted'", [$tenant->id]),
        ];

        $this->markExpiredIfDue($tenant->id);

        $this->render('quotes/index', [
            'title' => 'Cotizaciones',
            'quotes' => $quotes,
            'stats' => $stats,
            'q' => $q,
            'status' => $status,
            'clientType' => $clientType,
        ]);
    }

    /* ════════════════════════════════════════════════════════════════════
     * CREATE / STORE
     * ════════════════════════════════════════════════════════════════════ */
    public function create(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('quotes');
        $this->requireCan('quotes.create');

        $settings = $this->getSettings($tenant->id);
        $companies = $this->db->all('SELECT id, name, phone, address FROM companies WHERE tenant_id=? ORDER BY name', [$tenant->id]);
        $taxes = $this->db->all('SELECT * FROM quote_taxes WHERE tenant_id=? AND is_active=1 ORDER BY sort_order, name', [$tenant->id]);
        $templates = $this->db->all('SELECT * FROM quote_templates WHERE tenant_id=? AND is_active=1 ORDER BY name', [$tenant->id]);
        $catalog = $this->db->all('SELECT id, name, description, color FROM service_catalog_items WHERE tenant_id=? AND is_active=1 ORDER BY name', [$tenant->id]);

        // Si vienen con ?lead_id= prefijo cargar lead + sus deals
        $prefilledLead = null; $prefilledItems = [];
        $leadId = (int)$this->input('lead_id', 0);
        if ($leadId > 0) {
            $prefilledLead = $this->db->one('SELECT * FROM crm_leads WHERE id=? AND tenant_id=?', [$leadId, $tenant->id]);
        }

        $template = null; $templateItems = [];
        $tplId = (int)$this->input('template_id', 0);
        if ($tplId > 0) {
            $template = $this->db->one('SELECT * FROM quote_templates WHERE id=? AND tenant_id=?', [$tplId, $tenant->id]);
            if ($template) {
                $templateItems = $this->db->all('SELECT * FROM quote_template_items WHERE template_id=? ORDER BY sort_order, id', [$tplId]);
            }
        }

        $this->render('quotes/create', [
            'title' => 'Nueva cotización',
            'settings' => $settings,
            'companies' => $companies,
            'taxes' => $taxes,
            'templates' => $templates,
            'catalog' => $catalog,
            'prefilledLead' => $prefilledLead,
            'template' => $template,
            'templateItems' => $templateItems,
        ]);
    }

    public function store(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('quotes');
        $this->requireCan('quotes.create');
        $this->validateCsrf();

        $settings = $this->getSettings($tenant->id);
        $user = $this->auth->user();

        $clientType = (string)$this->input('client_type', 'company');
        $companyId = ((int)$this->input('company_id', 0)) ?: null;
        $leadId = ((int)$this->input('lead_id', 0)) ?: null;

        $clientName = trim((string)$this->input('client_name', ''));
        if ($clientName === '') {
            $this->session->flash('error', 'El nombre del cliente es obligatorio.');
            $this->redirect('/t/' . $tenant->slug . '/quotes/create');
        }

        $items = $this->parseItemsInput();
        if (empty($items)) {
            $this->session->flash('error', 'Agregá al menos un ítem a la cotización.');
            $this->redirect('/t/' . $tenant->slug . '/quotes/create');
        }

        $code = $this->generateQuoteCode($tenant->id, $settings);
        $taxRate = (float)$this->input('tax_rate', $settings['default_tax_id'] ? (float)$this->db->val('SELECT rate FROM quote_taxes WHERE id=?', [$settings['default_tax_id']]) : 0);
        $taxLabel = trim((string)$this->input('tax_label', 'ITBIS')) ?: 'ITBIS';
        $discountPct = max(0, min(100, (float)$this->input('discount_pct', 0)));
        $shipping = max(0, (float)$this->input('shipping_amount', 0));
        $otherAmount = max(0, (float)$this->input('other_charges_amount', 0));
        $otherLabel = trim((string)$this->input('other_charges_label', '')) ?: null;

        $totals = $this->computeTotals($items, $discountPct, $taxRate, $shipping, $otherAmount);

        $validityDays = (int)($this->input('validity_days', $settings['validity_days'] ?? 15)) ?: 15;
        $issuedAt = $this->normalizeDate($this->input('issued_at')) ?: date('Y-m-d');
        $validUntil = $this->normalizeDate($this->input('valid_until')) ?: date('Y-m-d', strtotime("+{$validityDays} days", strtotime($issuedAt)));

        $publicToken = bin2hex(random_bytes(16));
        $currency = trim((string)$this->input('currency', $settings['currency'] ?? 'DOP')) ?: 'DOP';
        $currencySymbol = trim((string)$this->input('currency_symbol', $settings['currency_symbol'] ?? 'RD$')) ?: 'RD$';

        $quoteId = $this->db->insert('quotes', [
            'tenant_id'      => $tenant->id,
            'code'           => $code,
            'title'          => trim((string)$this->input('title', '')) ?: null,
            'client_type'    => in_array($clientType, ['company','individual','lead'], true) ? $clientType : 'company',
            'company_id'     => $companyId,
            'lead_id'        => $leadId,
            'deal_id'        => ((int)$this->input('deal_id', 0)) ?: null,
            'client_name'    => $clientName,
            'client_doc'     => trim((string)$this->input('client_doc', '')) ?: null,
            'client_email'   => trim((string)$this->input('client_email', '')) ?: null,
            'client_phone'   => trim((string)$this->input('client_phone', '')) ?: null,
            'client_address' => trim((string)$this->input('client_address', '')) ?: null,
            'client_contact' => trim((string)$this->input('client_contact', '')) ?: null,
            'currency'       => $currency,
            'currency_symbol'=> $currencySymbol,
            'exchange_rate'  => max(0.0001, (float)$this->input('exchange_rate', 1)),

            'subtotal'             => $totals['subtotal'],
            'discount_pct'         => $discountPct,
            'discount_amount'      => $totals['discount_amount'],
            'taxable_subtotal'     => $totals['taxable_subtotal'],
            'tax_rate'             => $taxRate,
            'tax_label'            => $taxLabel,
            'tax_amount'           => $totals['tax_amount'],
            'shipping_amount'      => $shipping,
            'other_charges_amount' => $otherAmount,
            'other_charges_label'  => $otherLabel,
            'total'                => $totals['total'],

            'intro'        => trim((string)$this->input('intro', $settings['intro_text'] ?? '')) ?: null,
            'terms'        => trim((string)$this->input('terms', $settings['terms_text'] ?? '')) ?: null,
            'notes'        => trim((string)$this->input('notes', '')) ?: null,
            'status'       => 'draft',
            'issued_at'    => $issuedAt,
            'valid_until'  => $validUntil,
            'public_token' => $publicToken,
            'owner_id'     => ((int)$this->input('owner_id', 0)) ?: (int)$user['id'],
            'created_by'   => (int)$user['id'],
        ]);

        // Insert items
        $this->insertItems($tenant->id, $quoteId, $items);

        $this->logEvent($tenant->id, $quoteId, 'created', 'agent', $user['name'] ?? null, $user['email'] ?? null);
        $this->session->flash('success', 'Cotización ' . $code . ' creada.');
        $this->redirect('/t/' . $tenant->slug . '/quotes/' . $quoteId);
    }

    /* ════════════════════════════════════════════════════════════════════
     * SHOW / EDIT
     * ════════════════════════════════════════════════════════════════════ */
    public function show(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('quotes');
        $this->requireCan('quotes.view');

        $id = (int)$params['id'];
        $quote = $this->db->one('SELECT * FROM quotes WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if (!$quote) $this->redirect('/t/' . $tenant->slug . '/quotes');

        $items = $this->db->all('SELECT * FROM quote_items WHERE quote_id=? AND tenant_id=? ORDER BY sort_order, id', [$id, $tenant->id]);
        $events = $this->db->all('SELECT * FROM quote_events WHERE quote_id=? AND tenant_id=? ORDER BY created_at DESC LIMIT 80', [$id, $tenant->id]);
        $companies = $this->db->all('SELECT id, name FROM companies WHERE tenant_id=? ORDER BY name', [$tenant->id]);
        $taxes = $this->db->all('SELECT * FROM quote_taxes WHERE tenant_id=? AND is_active=1 ORDER BY sort_order, name', [$tenant->id]);
        $catalog = $this->db->all('SELECT id, name, description FROM service_catalog_items WHERE tenant_id=? AND is_active=1 ORDER BY name', [$tenant->id]);
        $settings = $this->getSettings($tenant->id);
        $owners = $this->db->all('SELECT id, name FROM users WHERE tenant_id=? ORDER BY name', [$tenant->id]);

        $publicUrl = rtrim($this->app->config['app']['url'] ?? '', '/') . '/q/' . $quote['public_token'];

        $this->render('quotes/show', [
            'title'    => $quote['code'],
            'quote'    => $quote,
            'items'    => $items,
            'events'   => $events,
            'companies'=> $companies,
            'taxes'    => $taxes,
            'catalog'  => $catalog,
            'settings' => $settings,
            'owners'   => $owners,
            'publicUrl'=> $publicUrl,
        ]);
    }

    public function update(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('quotes');
        $this->requireCan('quotes.edit');
        $this->validateCsrf();

        $id = (int)$params['id'];
        $quote = $this->db->one('SELECT * FROM quotes WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if (!$quote) $this->redirect('/t/' . $tenant->slug . '/quotes');

        if (in_array($quote['status'], ['accepted','rejected','converted'], true)) {
            $this->session->flash('error', 'No se puede editar una cotización en estado "' . $quote['status'] . '".');
            $this->redirect('/t/' . $tenant->slug . '/quotes/' . $id);
        }

        $items = $this->parseItemsInput();
        if (empty($items)) {
            $this->session->flash('error', 'La cotización debe tener al menos un ítem.');
            $this->redirect('/t/' . $tenant->slug . '/quotes/' . $id);
        }

        $taxRate = (float)$this->input('tax_rate', 0);
        $taxLabel = trim((string)$this->input('tax_label', $quote['tax_label'])) ?: 'ITBIS';
        $discountPct = max(0, min(100, (float)$this->input('discount_pct', 0)));
        $shipping = max(0, (float)$this->input('shipping_amount', 0));
        $otherAmount = max(0, (float)$this->input('other_charges_amount', 0));
        $otherLabel = trim((string)$this->input('other_charges_label', '')) ?: null;
        $totals = $this->computeTotals($items, $discountPct, $taxRate, $shipping, $otherAmount);

        $validityDays = (int)($this->input('validity_days', 15)) ?: 15;
        $issuedAt = $this->normalizeDate($this->input('issued_at')) ?: ($quote['issued_at'] ?: date('Y-m-d'));
        $validUntil = $this->normalizeDate($this->input('valid_until')) ?: date('Y-m-d', strtotime("+{$validityDays} days", strtotime($issuedAt)));

        $newStatus = $quote['status'];
        if (in_array($newStatus, ['expired'], true)) $newStatus = 'revised';

        $this->db->update('quotes', [
            'title'         => trim((string)$this->input('title', '')) ?: null,
            'client_type'   => (string)$this->input('client_type', $quote['client_type']),
            'company_id'    => ((int)$this->input('company_id', 0)) ?: null,
            'lead_id'       => ((int)$this->input('lead_id', 0)) ?: null,
            'client_name'   => trim((string)$this->input('client_name', $quote['client_name'])),
            'client_doc'    => trim((string)$this->input('client_doc', '')) ?: null,
            'client_email'  => trim((string)$this->input('client_email', '')) ?: null,
            'client_phone'  => trim((string)$this->input('client_phone', '')) ?: null,
            'client_address'=> trim((string)$this->input('client_address', '')) ?: null,
            'client_contact'=> trim((string)$this->input('client_contact', '')) ?: null,
            'currency'      => trim((string)$this->input('currency', $quote['currency'])) ?: $quote['currency'],
            'currency_symbol' => trim((string)$this->input('currency_symbol', $quote['currency_symbol'])) ?: $quote['currency_symbol'],
            'exchange_rate' => max(0.0001, (float)$this->input('exchange_rate', $quote['exchange_rate'])),

            'subtotal'             => $totals['subtotal'],
            'discount_pct'         => $discountPct,
            'discount_amount'      => $totals['discount_amount'],
            'taxable_subtotal'     => $totals['taxable_subtotal'],
            'tax_rate'             => $taxRate,
            'tax_label'            => $taxLabel,
            'tax_amount'           => $totals['tax_amount'],
            'shipping_amount'      => $shipping,
            'other_charges_amount' => $otherAmount,
            'other_charges_label'  => $otherLabel,
            'total'                => $totals['total'],

            'intro'        => trim((string)$this->input('intro', '')) ?: null,
            'terms'        => trim((string)$this->input('terms', '')) ?: null,
            'notes'        => trim((string)$this->input('notes', '')) ?: null,
            'issued_at'    => $issuedAt,
            'valid_until'  => $validUntil,
            'owner_id'     => ((int)$this->input('owner_id', 0)) ?: null,
            'status'       => $newStatus,
        ], 'id=? AND tenant_id=?', [$id, $tenant->id]);

        // Reemplazar items
        $this->db->delete('quote_items', 'quote_id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->insertItems($tenant->id, $id, $items);

        $user = $this->auth->user();
        $this->logEvent($tenant->id, $id, 'updated', 'agent', $user['name'] ?? null, $user['email'] ?? null);

        $this->session->flash('success', 'Cotización actualizada.');
        $this->redirect('/t/' . $tenant->slug . '/quotes/' . $id);
    }

    public function delete(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('quotes');
        $this->requireCan('quotes.delete');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $this->db->delete('quote_items', 'quote_id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->db->delete('quote_events', 'quote_id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->db->delete('quotes', 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->session->flash('success', 'Cotización eliminada.');
        $this->redirect('/t/' . $tenant->slug . '/quotes');
    }

    public function duplicate(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('quotes');
        $this->requireCan('quotes.create');
        $this->validateCsrf();

        $id = (int)$params['id'];
        $original = $this->db->one('SELECT * FROM quotes WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if (!$original) $this->redirect('/t/' . $tenant->slug . '/quotes');

        $settings = $this->getSettings($tenant->id);
        $newCode = $this->generateQuoteCode($tenant->id, $settings);
        unset($original['id']);
        $original['code'] = $newCode;
        $original['public_token'] = bin2hex(random_bytes(16));
        $original['status'] = 'draft';
        $original['sent_at'] = $original['viewed_at'] = $original['accepted_at'] = $original['rejected_at'] = null;
        $original['accepted_by_name'] = $original['accepted_by_email'] = $original['rejected_reason'] = null;
        $original['issued_at'] = date('Y-m-d');
        $original['valid_until'] = date('Y-m-d', strtotime("+{$settings['validity_days']} days"));
        $original['created_at'] = date('Y-m-d H:i:s');
        $newId = $this->db->insert('quotes', $original);

        $items = $this->db->all('SELECT * FROM quote_items WHERE quote_id=?', [$id]);
        foreach ($items as $it) {
            unset($it['id']);
            $it['quote_id'] = $newId;
            $this->db->insert('quote_items', $it);
        }

        $user = $this->auth->user();
        $this->logEvent($tenant->id, $newId, 'created', 'agent', $user['name'] ?? null, $user['email'] ?? null, ['cloned_from' => $id]);
        $this->session->flash('success', 'Cotización duplicada como ' . $newCode . '.');
        $this->redirect('/t/' . $tenant->slug . '/quotes/' . $newId);
    }

    /* ════════════════════════════════════════════════════════════════════
     * SEND / STATUS CHANGES
     * ════════════════════════════════════════════════════════════════════ */
    public function send(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('quotes');
        $this->requireCan('quotes.send');
        $this->validateCsrf();

        $id = (int)$params['id'];
        $quote = $this->db->one('SELECT * FROM quotes WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if (!$quote) $this->redirect('/t/' . $tenant->slug . '/quotes');

        $email = trim((string)$this->input('email', $quote['client_email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->session->flash('error', 'Email inválido para enviar la cotización.');
            $this->redirect('/t/' . $tenant->slug . '/quotes/' . $id);
        }

        $settings = $this->getSettings($tenant->id);
        $publicUrl = rtrim($this->app->config['app']['url'] ?? '', '/') . '/q/' . $quote['public_token'];
        $name = $quote['client_contact'] ?: $quote['client_name'];

        $intro = '<p>Hola <strong>' . htmlspecialchars($name) . '</strong>,</p>'
            . '<p>Te compartimos la cotización <strong>' . htmlspecialchars($quote['code']) . '</strong> de <strong>' . htmlspecialchars($settings['business_name'] ?: $tenant->name) . '</strong>.</p>'
            . '<p style="font-size:13px;color:#475569">Total: <strong style="color:#16a34a">' . htmlspecialchars($quote['currency_symbol']) . ' ' . number_format((float)$quote['total'], 2) . '</strong> · Validez hasta el <strong>' . htmlspecialchars((string)$quote['valid_until']) . '</strong></p>'
            . '<p>Podés revisarla, descargar el PDF y aceptarla desde el link abajo.</p>';

        try {
            (new Mailer())->send(
                ['email' => $email, 'name' => $name],
                'Cotización ' . $quote['code'] . ' · ' . ($settings['business_name'] ?: $tenant->name),
                Mailer::template('Cotización ' . $quote['code'], $intro, 'Ver cotización', $publicUrl)
            );
        } catch (\Throwable $e) { /* no bloquear */ }

        $this->db->update('quotes', [
            'status' => 'sent',
            'sent_at' => date('Y-m-d H:i:s'),
            'client_email' => $email,
        ], 'id=? AND tenant_id=?', [$id, $tenant->id]);

        $user = $this->auth->user();
        $this->logEvent($tenant->id, $id, 'sent', 'agent', $user['name'] ?? null, $user['email'] ?? null, ['to' => $email]);

        $this->session->flash('success', 'Cotización enviada a ' . $email . '.');
        $this->redirect('/t/' . $tenant->slug . '/quotes/' . $id);
    }

    public function markStatus(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('quotes');
        $this->requireCan('quotes.edit');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $newStatus = (string)$this->input('status', '');
        if (!in_array($newStatus, self::STATUSES, true)) $this->back();

        $update = ['status' => $newStatus];
        if ($newStatus === 'accepted') {
            $update['accepted_at'] = date('Y-m-d H:i:s');
            $update['accepted_by_name'] = trim((string)$this->input('accepted_by_name', '')) ?: null;
            $update['accepted_by_email'] = trim((string)$this->input('accepted_by_email', '')) ?: null;
        } elseif ($newStatus === 'rejected') {
            $update['rejected_at'] = date('Y-m-d H:i:s');
            $update['rejected_reason'] = trim((string)$this->input('rejected_reason', '')) ?: null;
        }
        $this->db->update('quotes', $update, 'id=? AND tenant_id=?', [$id, $tenant->id]);

        $user = $this->auth->user();
        $this->logEvent($tenant->id, $id, $newStatus, 'agent', $user['name'] ?? null, $user['email'] ?? null);

        $this->session->flash('success', 'Estado actualizado a ' . $newStatus . '.');
        $this->redirect('/t/' . $tenant->slug . '/quotes/' . $id);
    }

    /* ════════════════════════════════════════════════════════════════════
     * PDF EXPORT
     * ════════════════════════════════════════════════════════════════════ */
    public function pdf(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('quotes');
        $this->requireCan('quotes.view');

        $id = (int)$params['id'];
        $quote = $this->db->one('SELECT * FROM quotes WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if (!$quote) $this->redirect('/t/' . $tenant->slug . '/quotes');

        $items = $this->db->all('SELECT * FROM quote_items WHERE quote_id=? AND tenant_id=? ORDER BY sort_order, id', [$id, $tenant->id]);
        $settings = $this->getSettings($tenant->id);

        $html = $this->view->render('quotes/pdf', [
            'quote' => $quote,
            'items' => $items,
            'settings' => $settings,
            'tenant' => $tenant,
        ], null);

        $user = $this->auth->user();
        $this->logEvent($tenant->id, $id, 'pdf_downloaded', 'agent', $user['name'] ?? null, $user['email'] ?? null);

        $filename = 'Cotizacion-' . preg_replace('/[^A-Za-z0-9_\-]/', '', $quote['code']) . '.pdf';
        Pdf::stream($html, $filename, 'portrait', 'A4', false);
    }

    /* ════════════════════════════════════════════════════════════════════
     * PUBLIC VIEW (cliente)
     * ════════════════════════════════════════════════════════════════════ */
    public function publicShow(array $params): void
    {
        $token = (string)$params['token'];
        $quote = $this->db->one('SELECT * FROM quotes WHERE public_token=?', [$token]);
        if (!$quote) {
            http_response_code(404);
            echo 'Cotización no encontrada.';
            exit;
        }
        $tenant = \App\Core\Tenant::find((int)$quote['tenant_id']);
        if (!$tenant) {
            http_response_code(404);
            echo 'Cotización no disponible.';
            exit;
        }
        $items = $this->db->all('SELECT * FROM quote_items WHERE quote_id=? ORDER BY sort_order, id', [(int)$quote['id']]);
        $settings = $this->getSettings($tenant->id);

        // Marcar viewed (solo la primera vez)
        if (in_array($quote['status'], ['sent'], true)) {
            $this->db->update('quotes', ['status' => 'viewed', 'viewed_at' => date('Y-m-d H:i:s')], 'id=?', [(int)$quote['id']]);
            $this->logEvent($tenant->id, (int)$quote['id'], 'viewed', 'client');
        }

        echo $this->view->render('quotes/public', [
            'title'    => 'Cotización ' . $quote['code'],
            'quote'    => $quote,
            'items'    => $items,
            'settings' => $settings,
            'tenant'   => $tenant,
        ], null);
    }

    public function publicPdf(array $params): void
    {
        $token = (string)$params['token'];
        $quote = $this->db->one('SELECT * FROM quotes WHERE public_token=?', [$token]);
        if (!$quote) { http_response_code(404); exit('No encontrada.'); }
        $tenant = \App\Core\Tenant::find((int)$quote['tenant_id']);
        if (!$tenant) { http_response_code(404); exit('No disponible.'); }

        $items = $this->db->all('SELECT * FROM quote_items WHERE quote_id=? ORDER BY sort_order, id', [(int)$quote['id']]);
        $settings = $this->getSettings($tenant->id);

        $html = $this->view->render('quotes/pdf', [
            'quote' => $quote, 'items' => $items, 'settings' => $settings, 'tenant' => $tenant,
        ], null);

        $this->logEvent($tenant->id, (int)$quote['id'], 'pdf_downloaded', 'client');

        $filename = 'Cotizacion-' . preg_replace('/[^A-Za-z0-9_\-]/', '', $quote['code']) . '.pdf';
        Pdf::stream($html, $filename, 'portrait', 'A4', true);
    }

    public function publicAccept(array $params): void
    {
        $token = (string)$params['token'];
        $quote = $this->db->one('SELECT * FROM quotes WHERE public_token=?', [$token]);
        if (!$quote) { http_response_code(404); exit; }
        if (!in_array($quote['status'], ['sent','viewed','revised'], true)) {
            $this->session->flash('error', 'Esta cotización ya no puede aceptarse.');
            $this->redirect('/q/' . $token);
        }
        $tenant = \App\Core\Tenant::find((int)$quote['tenant_id']);
        if (!$tenant) { http_response_code(404); exit; }

        $name = trim((string)$this->input('name', ''));
        $email = trim((string)$this->input('email', ''));
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->session->flash('error', 'Necesitamos tu nombre y email válido para registrar la aceptación.');
            $this->redirect('/q/' . $token);
        }

        $this->db->update('quotes', [
            'status' => 'accepted',
            'accepted_at' => date('Y-m-d H:i:s'),
            'accepted_by_name' => $name,
            'accepted_by_email' => $email,
        ], 'id=?', [(int)$quote['id']]);

        $this->logEvent($tenant->id, (int)$quote['id'], 'accepted', 'client', $name, $email);

        // Notificar al equipo si configuraron email de notificación
        $settings = $this->getSettings($tenant->id);
        $notifyEmail = $settings['notify_email'] ?: $tenant->data['support_email'] ?? null;
        if ($notifyEmail && (int)$settings['notify_on_accept'] === 1) {
            try {
                $panelUrl = rtrim($this->app->config['app']['url'] ?? '', '/') . '/t/' . $tenant->slug . '/quotes/' . (int)$quote['id'];
                $inner = '<p>La cotización <strong>' . htmlspecialchars($quote['code']) . '</strong> fue <strong style="color:#16a34a">aceptada</strong>.</p>'
                       . '<p>Aceptada por: <strong>' . htmlspecialchars($name) . '</strong> &lt;' . htmlspecialchars($email) . '&gt;</p>'
                       . '<p>Total: <strong>' . htmlspecialchars($quote['currency_symbol']) . ' ' . number_format((float)$quote['total'], 2) . '</strong></p>';
                (new Mailer())->send(
                    ['email' => $notifyEmail, 'name' => $tenant->name],
                    '✓ Cotización aceptada · ' . $quote['code'],
                    Mailer::template('Cotización aceptada', $inner, 'Abrir en panel', $panelUrl)
                );
            } catch (\Throwable $e) { /* ignore */ }
        }

        $this->session->flash('success', '¡Gracias! La cotización fue aceptada y nuestro equipo fue notificado.');
        $this->redirect('/q/' . $token);
    }

    public function publicReject(array $params): void
    {
        $token = (string)$params['token'];
        $quote = $this->db->one('SELECT * FROM quotes WHERE public_token=?', [$token]);
        if (!$quote) { http_response_code(404); exit; }
        if (!in_array($quote['status'], ['sent','viewed','revised'], true)) {
            $this->session->flash('error', 'Esta cotización ya no puede modificarse.');
            $this->redirect('/q/' . $token);
        }
        $tenant = \App\Core\Tenant::find((int)$quote['tenant_id']);
        $reason = trim((string)$this->input('reason', '')) ?: null;
        $this->db->update('quotes', [
            'status' => 'rejected',
            'rejected_at' => date('Y-m-d H:i:s'),
            'rejected_reason' => $reason,
        ], 'id=?', [(int)$quote['id']]);
        $this->logEvent($tenant->id, (int)$quote['id'], 'rejected', 'client', null, null, ['reason' => $reason]);
        $this->session->flash('success', 'Tu respuesta fue registrada. Gracias.');
        $this->redirect('/q/' . $token);
    }

    /* ════════════════════════════════════════════════════════════════════
     * SETTINGS · BRANDING + DEFAULTS + TAXES + TEMPLATES
     * ════════════════════════════════════════════════════════════════════ */
    public function settings(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('quotes');
        $this->requireCan('quotes.config');

        $settings = $this->getSettings($tenant->id);
        $taxes = $this->db->all('SELECT * FROM quote_taxes WHERE tenant_id=? ORDER BY sort_order, name', [$tenant->id]);
        $templates = $this->db->all('SELECT * FROM quote_templates WHERE tenant_id=? ORDER BY name', [$tenant->id]);

        $this->render('quotes/settings', [
            'title'    => 'Cotizaciones · Configuración',
            'settings' => $settings,
            'taxes'    => $taxes,
            'templates'=> $templates,
        ]);
    }

    public function settingsUpdate(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('quotes');
        $this->requireCan('quotes.config');
        $this->validateCsrf();

        // Logo upload (optional)
        $logoUrl = (string)$this->input('logo_url', '');
        if (!empty($_FILES['logo_file']) && ($_FILES['logo_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $f = $_FILES['logo_file'];
            $mime = mime_content_type($f['tmp_name']) ?: ($f['type'] ?? '');
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/svg+xml' => 'svg'];
            if (isset($allowed[$mime])) {
                $relDir = '/public/uploads/quote_logos';
                $absDir = BASE_PATH . $relDir;
                if (!is_dir($absDir)) @mkdir($absDir, 0755, true);
                $name = 'logo_t' . $tenant->id . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
                if (move_uploaded_file($f['tmp_name'], $absDir . '/' . $name)) {
                    $logoUrl = $relDir . '/' . $name;
                }
            } else {
                $this->session->flash('error', 'Solo se aceptan logos en JPG, PNG, WebP o SVG.');
                $this->redirect('/t/' . $tenant->slug . '/quotes/settings');
            }
        }

        $payload = [
            'business_name'        => trim((string)$this->input('business_name', '')) ?: null,
            'business_doc'         => trim((string)$this->input('business_doc', '')) ?: null,
            'business_address'     => trim((string)$this->input('business_address', '')) ?: null,
            'business_phone'       => trim((string)$this->input('business_phone', '')) ?: null,
            'business_email'       => trim((string)$this->input('business_email', '')) ?: null,
            'business_website'     => trim((string)$this->input('business_website', '')) ?: null,
            'logo_url'             => $logoUrl ?: null,
            'primary_color'        => substr((string)$this->input('primary_color', '#7c5cff'), 0, 20),
            'accent_color'         => substr((string)$this->input('accent_color', '#16a34a'), 0, 20),
            'currency'             => substr((string)$this->input('currency', 'DOP'), 0, 8) ?: 'DOP',
            'currency_symbol'      => substr((string)$this->input('currency_symbol', 'RD$'), 0, 10) ?: 'RD$',
            'decimals'             => max(0, min(4, (int)$this->input('decimals', 2))),
            'prefix'               => substr((string)$this->input('prefix', 'COT-'), 0, 20) ?: 'COT-',
            'next_number'          => max(1, (int)$this->input('next_number', 1)),
            'validity_days'        => max(1, (int)$this->input('validity_days', 15)),
            'default_tax_id'       => ((int)$this->input('default_tax_id', 0)) ?: null,
            'default_discount_pct' => max(0, min(100, (float)$this->input('default_discount_pct', 0))),
            'show_signature'       => (int)$this->input('show_signature', 0) === 1 ? 1 : 0,
            'signature_name'       => trim((string)$this->input('signature_name', '')) ?: null,
            'signature_role'       => trim((string)$this->input('signature_role', '')) ?: null,
            'intro_text'           => trim((string)$this->input('intro_text', '')) ?: null,
            'terms_text'           => trim((string)$this->input('terms_text', '')) ?: null,
            'footer_text'          => trim((string)$this->input('footer_text', '')) ?: null,
            'bank_info'            => trim((string)$this->input('bank_info', '')) ?: null,
            'notify_on_accept'     => (int)$this->input('notify_on_accept', 1) === 1 ? 1 : 0,
            'notify_email'         => trim((string)$this->input('notify_email', '')) ?: null,
        ];

        $exists = $this->db->one('SELECT tenant_id FROM quote_settings WHERE tenant_id=?', [$tenant->id]);
        if ($exists) {
            $this->db->update('quote_settings', $payload, 'tenant_id=?', [$tenant->id]);
        } else {
            $payload['tenant_id'] = $tenant->id;
            $this->db->insert('quote_settings', $payload);
        }

        $this->session->flash('success', 'Configuración guardada.');
        $this->redirect('/t/' . $tenant->slug . '/quotes/settings');
    }

    public function taxStore(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('quotes');
        $this->requireCan('quotes.config');
        $this->validateCsrf();
        $name = trim((string)$this->input('name', ''));
        if ($name === '') $this->back();
        $slug = $this->slugify($name);
        if ($this->db->val('SELECT id FROM quote_taxes WHERE tenant_id=? AND slug=?', [$tenant->id, $slug])) {
            $slug .= '-' . substr(bin2hex(random_bytes(2)), 0, 4);
        }
        $isDefault = (int)$this->input('is_default', 0) === 1 ? 1 : 0;
        if ($isDefault) {
            $this->db->update('quote_taxes', ['is_default' => 0], 'tenant_id=?', [$tenant->id]);
        }
        $this->db->insert('quote_taxes', [
            'tenant_id'    => $tenant->id,
            'slug'         => $slug,
            'name'         => $name,
            'rate'         => max(0, min(100, (float)$this->input('rate', 0))),
            'is_inclusive' => (int)$this->input('is_inclusive', 0) === 1 ? 1 : 0,
            'is_default'   => $isDefault,
            'is_active'    => 1,
        ]);
        $this->redirect('/t/' . $tenant->slug . '/quotes/settings');
    }

    public function taxUpdate(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('quotes');
        $this->requireCan('quotes.config');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $isDefault = (int)$this->input('is_default', 0) === 1 ? 1 : 0;
        if ($isDefault) {
            $this->db->update('quote_taxes', ['is_default' => 0], 'tenant_id=? AND id<>?', [$tenant->id, $id]);
        }
        $this->db->update('quote_taxes', [
            'name'         => trim((string)$this->input('name', '')),
            'rate'         => max(0, min(100, (float)$this->input('rate', 0))),
            'is_inclusive' => (int)$this->input('is_inclusive', 0) === 1 ? 1 : 0,
            'is_default'   => $isDefault,
            'is_active'    => (int)$this->input('is_active', 1) === 1 ? 1 : 0,
        ], 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->redirect('/t/' . $tenant->slug . '/quotes/settings');
    }

    public function taxDelete(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('quotes');
        $this->requireCan('quotes.config');
        $this->validateCsrf();
        $this->db->delete('quote_taxes', 'id=? AND tenant_id=?', [(int)$params['id'], $tenant->id]);
        $this->redirect('/t/' . $tenant->slug . '/quotes/settings');
    }

    public function templateStore(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('quotes');
        $this->requireCan('quotes.config');
        $this->validateCsrf();
        $name = trim((string)$this->input('name', ''));
        if ($name === '') $this->back();
        $tplId = $this->db->insert('quote_templates', [
            'tenant_id'    => $tenant->id,
            'name'         => $name,
            'description'  => trim((string)$this->input('description', '')) ?: null,
            'intro'        => trim((string)$this->input('intro', '')) ?: null,
            'terms'        => trim((string)$this->input('terms', '')) ?: null,
            'currency'     => substr((string)$this->input('currency', 'DOP'), 0, 8) ?: 'DOP',
            'validity_days'=> max(1, (int)$this->input('validity_days', 15)),
        ]);
        $items = $this->parseItemsInput();
        foreach ($items as $i => $it) {
            $this->db->insert('quote_template_items', [
                'template_id'   => $tplId,
                'title'         => $it['title'],
                'description'   => $it['description'],
                'quantity'      => $it['quantity'],
                'unit'          => $it['unit'],
                'unit_label'    => $it['unit_label'],
                'unit_price'    => $it['unit_price'],
                'discount_pct'  => $it['discount_pct'],
                'is_taxable'    => $it['is_taxable'],
                'sort_order'    => $i,
            ]);
        }
        $this->session->flash('success', 'Plantilla creada.');
        $this->redirect('/t/' . $tenant->slug . '/quotes/settings');
    }

    public function templateDelete(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('quotes');
        $this->requireCan('quotes.config');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $this->db->delete('quote_template_items', 'template_id=?', [$id]);
        $this->db->delete('quote_templates', 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->redirect('/t/' . $tenant->slug . '/quotes/settings');
    }

    /* ════════════════════════════════════════════════════════════════════
     * HELPERS
     * ════════════════════════════════════════════════════════════════════ */
    protected function getSettings(int $tenantId): array
    {
        $row = $this->db->one('SELECT * FROM quote_settings WHERE tenant_id=?', [$tenantId]);
        if ($row) return $row;
        // Defaults vacíos
        return [
            'tenant_id' => $tenantId,
            'business_name' => null, 'business_doc' => null, 'business_address' => null,
            'business_phone' => null, 'business_email' => null, 'business_website' => null,
            'logo_url' => null, 'primary_color' => '#7c5cff', 'accent_color' => '#16a34a',
            'currency' => 'DOP', 'currency_symbol' => 'RD$', 'decimals' => 2,
            'prefix' => 'COT-', 'next_number' => 1, 'validity_days' => 15,
            'default_tax_id' => null, 'default_discount_pct' => 0,
            'show_signature' => 1, 'signature_name' => null, 'signature_role' => null,
            'intro_text' => null, 'terms_text' => null, 'footer_text' => null,
            'bank_info' => null, 'notify_on_accept' => 1, 'notify_email' => null,
        ];
    }

    protected function generateQuoteCode(int $tenantId, array $settings): string
    {
        $prefix = $settings['prefix'] ?: 'COT-';
        $next = max(1, (int)($settings['next_number'] ?? 1));
        $code = $prefix . date('Y') . '-' . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
        // Garantizar unicidad
        while ($this->db->val('SELECT id FROM quotes WHERE tenant_id=? AND code=?', [$tenantId, $code])) {
            $next++;
            $code = $prefix . date('Y') . '-' . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
        }
        // Avanzar contador
        $this->db->update('quote_settings', ['next_number' => $next + 1], 'tenant_id=?', [$tenantId]);
        return $code;
    }

    /**
     * Lee items[] del POST y devuelve un array normalizado.
     */
    protected function parseItemsInput(): array
    {
        $rows = $_POST['items'] ?? [];
        $out = [];
        if (!is_array($rows)) return $out;
        foreach ($rows as $r) {
            $title = trim((string)($r['title'] ?? ''));
            if ($title === '') continue;
            $qty = max(0.001, (float)($r['quantity'] ?? 1));
            $price = max(0, (float)($r['unit_price'] ?? 0));
            $discPct = max(0, min(100, (float)($r['discount_pct'] ?? 0)));
            $unit = (string)($r['unit'] ?? 'unit');
            if (!in_array($unit, self::UNITS, true)) $unit = 'unit';
            $line = round($qty * $price * (1 - $discPct / 100), 2);
            $out[] = [
                'catalog_item_id' => ((int)($r['catalog_item_id'] ?? 0)) ?: null,
                'title'           => $title,
                'description'     => trim((string)($r['description'] ?? '')) ?: null,
                'quantity'        => $qty,
                'unit'            => $unit,
                'unit_label'      => trim((string)($r['unit_label'] ?? '')) ?: null,
                'unit_price'      => $price,
                'discount_pct'    => $discPct,
                'discount_amount' => round($qty * $price * ($discPct / 100), 2),
                'line_subtotal'   => $line,
                'is_taxable'      => (int)($r['is_taxable'] ?? 1) === 1 ? 1 : 0,
            ];
        }
        return $out;
    }

    protected function insertItems(int $tenantId, int $quoteId, array $items): void
    {
        foreach ($items as $i => $it) {
            $this->db->insert('quote_items', [
                'tenant_id'       => $tenantId,
                'quote_id'        => $quoteId,
                'catalog_item_id' => $it['catalog_item_id'],
                'title'           => $it['title'],
                'description'     => $it['description'],
                'quantity'        => $it['quantity'],
                'unit'            => $it['unit'],
                'unit_label'      => $it['unit_label'],
                'unit_price'      => $it['unit_price'],
                'discount_pct'    => $it['discount_pct'],
                'discount_amount' => $it['discount_amount'],
                'line_subtotal'   => $it['line_subtotal'],
                'is_taxable'      => $it['is_taxable'],
                'sort_order'      => $i,
            ]);
        }
    }

    /**
     * Cálculo de totales. Retorna arr con subtotal, discount_amount, taxable_subtotal,
     * tax_amount y total finales.
     */
    protected function computeTotals(array $items, float $discountPct, float $taxRate, float $shipping, float $other): array
    {
        $subtotal = 0;
        $taxableBase = 0;
        foreach ($items as $it) {
            $subtotal += (float)$it['line_subtotal'];
            if ((int)$it['is_taxable'] === 1) {
                $taxableBase += (float)$it['line_subtotal'];
            }
        }
        $discountAmount = round($subtotal * ($discountPct / 100), 2);
        // Aplicar descuento global proporcionalmente sólo a la base imponible
        $taxableAfterDiscount = $taxableBase > 0
            ? round($taxableBase - ($taxableBase / max(0.0001, $subtotal)) * $discountAmount, 2)
            : 0;
        $taxAmount = round($taxableAfterDiscount * ($taxRate / 100), 2);
        $total = round($subtotal - $discountAmount + $taxAmount + $shipping + $other, 2);

        return [
            'subtotal'         => round($subtotal, 2),
            'discount_amount'  => $discountAmount,
            'taxable_subtotal' => $taxableAfterDiscount,
            'tax_amount'       => $taxAmount,
            'total'            => $total,
        ];
    }

    protected function logEvent(int $tenantId, int $quoteId, string $type, string $actorType = 'system', ?string $name = null, ?string $email = null, ?array $meta = null): void
    {
        try {
            $this->db->insert('quote_events', [
                'tenant_id'  => $tenantId,
                'quote_id'   => $quoteId,
                'event_type' => $type,
                'actor_type' => $actorType,
                'actor_name' => $name,
                'actor_email'=> $email,
                'meta'       => $meta ? json_encode($meta) : null,
            ]);
        } catch (\Throwable $e) { /* ignore */ }
    }

    protected function markExpiredIfDue(int $tenantId): void
    {
        try {
            $this->db->run(
                "UPDATE quotes SET status='expired'
                 WHERE tenant_id=? AND status IN ('sent','viewed','revised')
                   AND valid_until IS NOT NULL AND valid_until < CURDATE()",
                [$tenantId]
            );
        } catch (\Throwable $e) { /* ignore */ }
    }

    protected function normalizeDate($v): ?string
    {
        $v = trim((string)$v);
        if ($v === '') return null;
        $ts = strtotime($v);
        return $ts ? date('Y-m-d', $ts) : null;
    }

    protected function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9\s-]/u', '', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text);
        $text = preg_replace('/[\s-]+/', '-', $text);
        return trim($text, '-') ?: 'item';
    }
}
