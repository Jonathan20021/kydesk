<?php
namespace App\Controllers;

use App\Core\Controller;

class CrmController extends Controller
{
    public const LEAD_STATUSES = ['new','contacted','qualified','proposal','negotiation','customer','lost','archived'];
    public const RATINGS = ['cold','warm','hot'];
    public const ACTIVITY_TYPES = ['call','email','meeting','task','whatsapp','sms'];
    public const ACTIVITY_OUTCOMES = ['pending','completed','no_answer','rescheduled','cancelled'];

    /* ─────────────────────────────────────────────────────────────────── */
    /* DASHBOARD                                                            */
    /* ─────────────────────────────────────────────────────────────────── */

    public function dashboard(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('crm');
        $this->requireCan('crm.view');

        $tid = $tenant->id;

        $stats = [
            'total_leads'    => (int)$this->db->val('SELECT COUNT(*) FROM crm_leads WHERE tenant_id=?', [$tid]),
            'open_leads'     => (int)$this->db->val("SELECT COUNT(*) FROM crm_leads WHERE tenant_id=? AND status NOT IN ('customer','lost','archived')", [$tid]),
            'customers'      => (int)$this->db->val("SELECT COUNT(*) FROM crm_leads WHERE tenant_id=? AND status='customer'", [$tid]),
            'lost'           => (int)$this->db->val("SELECT COUNT(*) FROM crm_leads WHERE tenant_id=? AND status='lost'", [$tid]),
            'open_deals'     => (int)$this->db->val("SELECT COUNT(*) FROM crm_deals d JOIN crm_stages s ON s.id=d.stage_id WHERE d.tenant_id=? AND s.is_won=0 AND s.is_lost=0", [$tid]),
            'pipeline_value' => (float)$this->db->val("SELECT IFNULL(SUM(d.amount),0) FROM crm_deals d JOIN crm_stages s ON s.id=d.stage_id WHERE d.tenant_id=? AND s.is_won=0 AND s.is_lost=0", [$tid]),
            'won_amount'     => (float)$this->db->val("SELECT IFNULL(SUM(amount),0) FROM crm_deals WHERE tenant_id=? AND won_at IS NOT NULL", [$tid]),
            'overdue_followups' => (int)$this->db->val("SELECT COUNT(*) FROM crm_leads WHERE tenant_id=? AND next_followup_at IS NOT NULL AND next_followup_at < NOW() AND status NOT IN ('customer','lost','archived')", [$tid]),
        ];

        $statusCounts = [];
        foreach (self::LEAD_STATUSES as $st) {
            $statusCounts[$st] = (int)$this->db->val('SELECT COUNT(*) FROM crm_leads WHERE tenant_id=? AND status=?', [$tid, $st]);
        }

        $bySource = $this->db->all(
            "SELECT s.name, s.color, s.icon, COUNT(l.id) AS total
             FROM crm_sources s
             LEFT JOIN crm_leads l ON l.source_id=s.id
             WHERE s.tenant_id=?
             GROUP BY s.id
             ORDER BY total DESC, s.sort_order",
            [$tid]
        );

        $recentLeads = $this->db->all(
            "SELECT l.*, u.name AS owner_name, src.name AS source_name, src.color AS source_color, src.icon AS source_icon
             FROM crm_leads l
             LEFT JOIN users u ON u.id=l.owner_id
             LEFT JOIN crm_sources src ON src.id=l.source_id
             WHERE l.tenant_id=?
             ORDER BY l.created_at DESC LIMIT 8",
            [$tid]
        );

        $upcomingActivities = $this->db->all(
            "SELECT a.*, l.first_name, l.last_name, l.code AS lead_code
             FROM crm_activities a
             LEFT JOIN crm_leads l ON l.id=a.lead_id
             WHERE a.tenant_id=? AND a.outcome='pending' AND a.scheduled_at IS NOT NULL
             ORDER BY a.scheduled_at ASC LIMIT 10",
            [$tid]
        );

        $hotLeads = $this->db->all(
            "SELECT l.*, u.name AS owner_name
             FROM crm_leads l
             LEFT JOIN users u ON u.id=l.owner_id
             WHERE l.tenant_id=? AND l.rating='hot' AND l.status NOT IN ('customer','lost','archived')
             ORDER BY l.score DESC, l.created_at DESC LIMIT 6",
            [$tid]
        );

        $this->render('crm/dashboard', [
            'title' => 'CRM',
            'stats' => $stats,
            'statusCounts' => $statusCounts,
            'bySource' => $bySource,
            'recentLeads' => $recentLeads,
            'upcomingActivities' => $upcomingActivities,
            'hotLeads' => $hotLeads,
        ]);
    }

    /* ─────────────────────────────────────────────────────────────────── */
    /* LEADS                                                                */
    /* ─────────────────────────────────────────────────────────────────── */

    public function leadsIndex(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('crm');
        $this->requireCan('crm.view');

        $q = trim((string)$this->input('q', ''));
        $status = (string)$this->input('status', '');
        $rating = (string)$this->input('rating', '');
        $sourceId = (int)$this->input('source_id', 0);
        $ownerId = (int)$this->input('owner_id', 0);
        $tagId = (int)$this->input('tag_id', 0);

        $where = ['l.tenant_id=?'];
        $args = [$tenant->id];
        if ($q !== '') {
            $where[] = '(l.first_name LIKE ? OR l.last_name LIKE ? OR l.email LIKE ? OR l.phone LIKE ? OR l.company_name LIKE ? OR l.code LIKE ?)';
            $like = "%$q%";
            $args[] = $like; $args[] = $like; $args[] = $like; $args[] = $like; $args[] = $like; $args[] = $like;
        }
        if (in_array($status, self::LEAD_STATUSES, true)) { $where[] = 'l.status=?'; $args[] = $status; }
        if (in_array($rating, self::RATINGS, true)) { $where[] = 'l.rating=?'; $args[] = $rating; }
        if ($sourceId > 0) { $where[] = 'l.source_id=?'; $args[] = $sourceId; }
        if ($ownerId > 0) { $where[] = 'l.owner_id=?'; $args[] = $ownerId; }

        $join = '';
        if ($tagId > 0) {
            $join = ' INNER JOIN crm_lead_tags lt ON lt.lead_id=l.id AND lt.tag_id=' . (int)$tagId;
        }

        $leads = $this->db->all(
            "SELECT l.*, u.name AS owner_name, src.name AS source_name, src.color AS source_color, src.icon AS source_icon,
                    (SELECT COUNT(*) FROM crm_deals d WHERE d.lead_id=l.id) AS deals_count,
                    (SELECT IFNULL(SUM(d.amount),0) FROM crm_deals d WHERE d.lead_id=l.id) AS deals_amount
             FROM crm_leads l
             LEFT JOIN users u ON u.id=l.owner_id
             LEFT JOIN crm_sources src ON src.id=l.source_id
             $join
             WHERE " . implode(' AND ', $where) . "
             ORDER BY l.created_at DESC LIMIT 300",
            $args
        );

        $sources = $this->db->all('SELECT * FROM crm_sources WHERE tenant_id=? AND is_active=1 ORDER BY sort_order, name', [$tenant->id]);
        $owners = $this->db->all('SELECT id, name FROM users WHERE tenant_id=? ORDER BY name', [$tenant->id]);
        $tags = $this->db->all('SELECT * FROM crm_tags WHERE tenant_id=? ORDER BY name', [$tenant->id]);

        $this->render('crm/leads_index', [
            'title' => 'CRM · Leads',
            'leads' => $leads,
            'sources' => $sources,
            'owners' => $owners,
            'tags' => $tags,
            'q' => $q,
            'status' => $status,
            'rating' => $rating,
            'sourceId' => $sourceId,
            'ownerId' => $ownerId,
            'tagId' => $tagId,
        ]);
    }

    public function leadCreate(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('crm');
        $this->requireCan('crm.create');

        $sources = $this->db->all('SELECT * FROM crm_sources WHERE tenant_id=? AND is_active=1 ORDER BY sort_order, name', [$tenant->id]);
        $owners = $this->db->all('SELECT id, name FROM users WHERE tenant_id=? ORDER BY name', [$tenant->id]);
        $companies = $this->db->all('SELECT id, name FROM companies WHERE tenant_id=? ORDER BY name', [$tenant->id]);
        $tags = $this->db->all('SELECT * FROM crm_tags WHERE tenant_id=? ORDER BY name', [$tenant->id]);

        $this->render('crm/lead_create', [
            'title' => 'Nuevo lead',
            'sources' => $sources,
            'owners' => $owners,
            'companies' => $companies,
            'tags' => $tags,
        ]);
    }

    public function leadStore(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('crm');
        $this->requireCan('crm.create');
        $this->validateCsrf();

        $first = trim((string)$this->input('first_name'));
        $email = trim((string)$this->input('email', ''));
        if ($first === '' && $email === '') {
            $this->session->flash('error', 'Nombre o email son obligatorios.');
            $this->redirect('/t/' . $tenant->slug . '/crm/leads/create');
        }

        $code = $this->generateLeadCode($tenant->id);
        $tagIds = array_filter(array_map('intval', (array)$this->input('tag_ids', [])));
        $user = $this->auth->user();

        $leadId = $this->db->insert('crm_leads', [
            'tenant_id'         => $tenant->id,
            'code'              => $code,
            'first_name'        => $first ?: 'Lead',
            'last_name'         => trim((string)$this->input('last_name', '')) ?: null,
            'email'             => $email !== '' ? $email : null,
            'phone'             => trim((string)$this->input('phone', '')) ?: null,
            'whatsapp'          => trim((string)$this->input('whatsapp', '')) ?: null,
            'job_title'         => trim((string)$this->input('job_title', '')) ?: null,
            'company_id'        => ((int)$this->input('company_id', 0)) ?: null,
            'company_name'      => trim((string)$this->input('company_name', '')) ?: null,
            'website'           => trim((string)$this->input('website', '')) ?: null,
            'industry'          => trim((string)$this->input('industry', '')) ?: null,
            'country'           => trim((string)$this->input('country', '')) ?: null,
            'city'              => trim((string)$this->input('city', '')) ?: null,
            'address'           => trim((string)$this->input('address', '')) ?: null,
            'source_id'         => ((int)$this->input('source_id', 0)) ?: null,
            'source_detail'     => trim((string)$this->input('source_detail', '')) ?: null,
            'owner_id'          => ((int)$this->input('owner_id', 0)) ?: (int)$user['id'],
            'status'            => $this->validStatus($this->input('status', 'new')),
            'rating'            => $this->validRating($this->input('rating', 'warm')),
            'score'             => max(0, min(100, (int)$this->input('score', 0))),
            'estimated_value'   => (float)$this->input('estimated_value', 0),
            'currency'          => substr((string)$this->input('currency', 'USD'), 0, 8) ?: 'USD',
            'expected_close_on' => $this->normalizeDate($this->input('expected_close_on')),
            'next_followup_at'  => $this->normalizeDateTime($this->input('next_followup_at')),
            'consent_marketing' => (int)$this->input('consent_marketing', 0) === 1 ? 1 : 0,
            'notes'             => trim((string)$this->input('notes', '')) ?: null,
            'created_by'        => (int)$user['id'],
        ]);

        $this->syncLeadTags($leadId, $tagIds);

        $this->session->flash('success', 'Lead creado correctamente.');
        $this->redirect('/t/' . $tenant->slug . '/crm/leads/' . $leadId);
    }

    public function leadShow(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('crm');
        $this->requireCan('crm.view');

        $id = (int)$params['id'];
        $lead = $this->db->one(
            "SELECT l.*, u.name AS owner_name, u.email AS owner_email,
                    src.name AS source_name, src.color AS source_color, src.icon AS source_icon,
                    c.name AS linked_company_name
             FROM crm_leads l
             LEFT JOIN users u ON u.id=l.owner_id
             LEFT JOIN crm_sources src ON src.id=l.source_id
             LEFT JOIN companies c ON c.id=l.company_id
             WHERE l.id=? AND l.tenant_id=?",
            [$id, $tenant->id]
        );
        if (!$lead) $this->redirect('/t/' . $tenant->slug . '/crm/leads');

        $deals = $this->db->all(
            "SELECT d.*, p.name AS pipeline_name, p.color AS pipeline_color, s.name AS stage_name, s.color AS stage_color, s.is_won, s.is_lost, u.name AS owner_name
             FROM crm_deals d
             JOIN crm_pipelines p ON p.id=d.pipeline_id
             JOIN crm_stages s ON s.id=d.stage_id
             LEFT JOIN users u ON u.id=d.owner_id
             WHERE d.tenant_id=? AND d.lead_id=?
             ORDER BY d.created_at DESC",
            [$tenant->id, $id]
        );

        $activities = $this->db->all(
            "SELECT a.*, u.name AS owner_name
             FROM crm_activities a
             LEFT JOIN users u ON u.id=a.owner_id
             WHERE a.tenant_id=? AND a.lead_id=?
             ORDER BY COALESCE(a.scheduled_at, a.created_at) DESC LIMIT 50",
            [$tenant->id, $id]
        );

        $notes = $this->db->all(
            "SELECT n.*, u.name AS author_name
             FROM crm_notes n
             LEFT JOIN users u ON u.id=n.author_id
             WHERE n.tenant_id=? AND n.lead_id=?
             ORDER BY n.is_pinned DESC, n.created_at DESC",
            [$tenant->id, $id]
        );

        $tags = $this->db->all(
            'SELECT t.* FROM crm_tags t INNER JOIN crm_lead_tags lt ON lt.tag_id=t.id WHERE lt.lead_id=? ORDER BY t.name',
            [$id]
        );

        $tickets = $this->db->all(
            'SELECT id, code, subject, status, created_at FROM tickets WHERE tenant_id=? AND requester_email=? ORDER BY created_at DESC LIMIT 10',
            [$tenant->id, $lead['email'] ?? '']
        );

        $sources = $this->db->all('SELECT * FROM crm_sources WHERE tenant_id=? AND is_active=1 ORDER BY sort_order, name', [$tenant->id]);
        $owners = $this->db->all('SELECT id, name FROM users WHERE tenant_id=? ORDER BY name', [$tenant->id]);
        $companies = $this->db->all('SELECT id, name FROM companies WHERE tenant_id=? ORDER BY name', [$tenant->id]);
        $allTags = $this->db->all('SELECT * FROM crm_tags WHERE tenant_id=? ORDER BY name', [$tenant->id]);
        $pipelines = $this->db->all('SELECT * FROM crm_pipelines WHERE tenant_id=? AND is_active=1 ORDER BY sort_order', [$tenant->id]);
        $stages = $this->db->all('SELECT * FROM crm_stages WHERE tenant_id=? ORDER BY pipeline_id, sort_order', [$tenant->id]);

        $this->render('crm/lead_show', [
            'title' => trim($lead['first_name'] . ' ' . ($lead['last_name'] ?? '')),
            'lead' => $lead,
            'deals' => $deals,
            'activities' => $activities,
            'notes' => $notes,
            'tags' => $tags,
            'tickets' => $tickets,
            'sources' => $sources,
            'owners' => $owners,
            'companies' => $companies,
            'allTags' => $allTags,
            'pipelines' => $pipelines,
            'stages' => $stages,
        ]);
    }

    public function leadUpdate(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('crm');
        $this->requireCan('crm.edit');
        $this->validateCsrf();

        $id = (int)$params['id'];
        $lead = $this->db->one('SELECT * FROM crm_leads WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if (!$lead) $this->redirect('/t/' . $tenant->slug . '/crm/leads');

        $update = [
            'first_name'        => trim((string)$this->input('first_name', $lead['first_name'])),
            'last_name'         => trim((string)$this->input('last_name', '')) ?: null,
            'email'             => trim((string)$this->input('email', '')) ?: null,
            'phone'             => trim((string)$this->input('phone', '')) ?: null,
            'whatsapp'          => trim((string)$this->input('whatsapp', '')) ?: null,
            'job_title'         => trim((string)$this->input('job_title', '')) ?: null,
            'company_id'        => ((int)$this->input('company_id', 0)) ?: null,
            'company_name'      => trim((string)$this->input('company_name', '')) ?: null,
            'website'           => trim((string)$this->input('website', '')) ?: null,
            'industry'          => trim((string)$this->input('industry', '')) ?: null,
            'country'           => trim((string)$this->input('country', '')) ?: null,
            'city'              => trim((string)$this->input('city', '')) ?: null,
            'address'           => trim((string)$this->input('address', '')) ?: null,
            'source_id'         => ((int)$this->input('source_id', 0)) ?: null,
            'source_detail'     => trim((string)$this->input('source_detail', '')) ?: null,
            'owner_id'          => ((int)$this->input('owner_id', 0)) ?: null,
            'status'            => $this->validStatus($this->input('status', $lead['status'])),
            'rating'            => $this->validRating($this->input('rating', $lead['rating'])),
            'score'             => max(0, min(100, (int)$this->input('score', $lead['score']))),
            'estimated_value'   => (float)$this->input('estimated_value', 0),
            'currency'          => substr((string)$this->input('currency', $lead['currency']), 0, 8),
            'expected_close_on' => $this->normalizeDate($this->input('expected_close_on')),
            'next_followup_at'  => $this->normalizeDateTime($this->input('next_followup_at')),
            'consent_marketing' => (int)$this->input('consent_marketing', 0) === 1 ? 1 : 0,
            'notes'             => trim((string)$this->input('notes', '')) ?: null,
        ];
        $this->db->update('crm_leads', $update, 'id=? AND tenant_id=?', [$id, $tenant->id]);

        if (array_key_exists('tag_ids', $_POST) || array_key_exists('tag_ids', $_GET)) {
            $tagIds = array_filter(array_map('intval', (array)$this->input('tag_ids', [])));
            $this->syncLeadTags($id, $tagIds);
        }

        $this->session->flash('success', 'Lead actualizado.');
        $this->redirect('/t/' . $tenant->slug . '/crm/leads/' . $id);
    }

    public function leadDelete(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('crm');
        $this->requireCan('crm.delete');
        $this->validateCsrf();

        $id = (int)$params['id'];
        $this->db->delete('crm_lead_tags', 'lead_id=?', [$id]);
        $this->db->delete('crm_notes', 'lead_id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->db->delete('crm_activities', 'lead_id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->db->delete('crm_deals', 'lead_id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->db->delete('crm_leads', 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->session->flash('success', 'Lead eliminado.');
        $this->redirect('/t/' . $tenant->slug . '/crm/leads');
    }

    public function leadAssign(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('crm');
        $this->requireCan('crm.assign');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $ownerId = ((int)$this->input('owner_id', 0)) ?: null;
        $this->db->update('crm_leads', ['owner_id' => $ownerId], 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->session->flash('success', 'Owner actualizado.');
        $this->redirect('/t/' . $tenant->slug . '/crm/leads/' . $id);
    }

    public function leadConvert(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('crm');
        $this->requireCan('crm.convert');
        $this->validateCsrf();

        $id = (int)$params['id'];
        $lead = $this->db->one('SELECT * FROM crm_leads WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if (!$lead) $this->redirect('/t/' . $tenant->slug . '/crm/leads');

        $companyId = $lead['company_id'] ? (int)$lead['company_id'] : null;
        $createCompany = (int)$this->input('create_company', 0) === 1;
        if (!$companyId && $createCompany && !empty($lead['company_name'])) {
            $companyId = (int)$this->db->insert('companies', [
                'tenant_id' => $tenant->id,
                'name'      => $lead['company_name'],
                'industry'  => $lead['industry'] ?? '',
                'website'   => $lead['website'] ?? '',
                'phone'     => $lead['phone'] ?? '',
                'address'   => $lead['address'] ?? '',
                'tier'      => 'standard',
                'notes'     => 'Creada desde lead ' . $lead['code'],
            ]);
        }

        $portalUserId = $lead['portal_user_id'] ? (int)$lead['portal_user_id'] : null;
        $createPortal = (int)$this->input('create_portal_user', 0) === 1;
        if (!$portalUserId && $createPortal && !empty($lead['email']) && filter_var($lead['email'], FILTER_VALIDATE_EMAIL)) {
            $existing = $this->db->one('SELECT id FROM portal_users WHERE tenant_id=? AND email=?', [$tenant->id, $lead['email']]);
            if ($existing) {
                $portalUserId = (int)$existing['id'];
                if ($companyId) $this->db->update('portal_users', ['company_id' => $companyId], 'id=?', [$portalUserId]);
            } else {
                $tempPwd = bin2hex(random_bytes(6));
                $portalUserId = (int)$this->db->insert('portal_users', [
                    'tenant_id'  => $tenant->id,
                    'company_id' => $companyId,
                    'name'       => trim($lead['first_name'] . ' ' . ($lead['last_name'] ?? '')),
                    'email'      => $lead['email'],
                    'password'   => password_hash($tempPwd, PASSWORD_BCRYPT, ['cost' => 12]),
                    'phone'      => $lead['phone'] ?? null,
                    'is_active'  => 1,
                    'verify_token' => bin2hex(random_bytes(16)),
                ]);
                $this->session->flash('success', 'Cliente creado · contraseña temporal: ' . $tempPwd);
            }
        }

        $user = $this->auth->user();
        $this->db->update('crm_leads', [
            'status'        => 'customer',
            'company_id'    => $companyId,
            'portal_user_id' => $portalUserId,
            'converted_at'  => date('Y-m-d H:i:s'),
            'converted_by'  => (int)$user['id'],
        ], 'id=? AND tenant_id=?', [$id, $tenant->id]);

        if (empty($this->session->get('flash')['success'] ?? null)) {
            $this->session->flash('success', 'Lead convertido a cliente.');
        }
        $this->redirect('/t/' . $tenant->slug . '/crm/leads/' . $id);
    }

    /* ─────────────────────────────────────────────────────────────────── */
    /* PIPELINE / KANBAN DE DEALS                                          */
    /* ─────────────────────────────────────────────────────────────────── */

    public function pipeline(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('crm');
        $this->requireCan('crm.view');

        $pipelineId = (int)$this->input('pipeline_id', 0);
        $pipelines = $this->db->all('SELECT * FROM crm_pipelines WHERE tenant_id=? AND is_active=1 ORDER BY sort_order', [$tenant->id]);
        if (empty($pipelines)) {
            $this->session->flash('error', 'No hay pipelines configurados. Creá uno desde Configuración.');
            $this->redirect('/t/' . $tenant->slug . '/crm/settings');
        }
        if ($pipelineId === 0) {
            $default = null;
            foreach ($pipelines as $p) if ((int)$p['is_default'] === 1) { $default = $p; break; }
            $pipelineId = (int)($default ? $default['id'] : $pipelines[0]['id']);
        }

        $stages = $this->db->all(
            'SELECT * FROM crm_stages WHERE tenant_id=? AND pipeline_id=? ORDER BY sort_order, id',
            [$tenant->id, $pipelineId]
        );

        $deals = $this->db->all(
            "SELECT d.*, l.first_name, l.last_name, l.email, l.code AS lead_code, l.rating, u.name AS owner_name
             FROM crm_deals d
             JOIN crm_leads l ON l.id=d.lead_id
             LEFT JOIN users u ON u.id=d.owner_id
             WHERE d.tenant_id=? AND d.pipeline_id=?
             ORDER BY d.sort_order, d.created_at DESC",
            [$tenant->id, $pipelineId]
        );

        $byStage = [];
        foreach ($stages as $s) $byStage[(int)$s['id']] = [];
        foreach ($deals as $d) {
            $sid = (int)$d['stage_id'];
            if (!isset($byStage[$sid])) $byStage[$sid] = [];
            $byStage[$sid][] = $d;
        }

        $this->render('crm/pipeline', [
            'title' => 'CRM · Pipeline',
            'pipelines' => $pipelines,
            'pipelineId' => $pipelineId,
            'stages' => $stages,
            'byStage' => $byStage,
        ]);
    }

    /* ─────────────────────────────────────────────────────────────────── */
    /* DEALS                                                                */
    /* ─────────────────────────────────────────────────────────────────── */

    public function dealStore(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('crm');
        $this->requireCan('crm.create');
        $this->validateCsrf();

        $leadId = (int)$this->input('lead_id', 0);
        $pipelineId = (int)$this->input('pipeline_id', 0);
        $stageId = (int)$this->input('stage_id', 0);
        $title = trim((string)$this->input('title', ''));

        if (!$leadId || !$pipelineId || !$stageId || $title === '') {
            $this->session->flash('error', 'Lead, pipeline, etapa y título son obligatorios.');
            $this->back();
        }
        $lead = $this->db->one('SELECT id FROM crm_leads WHERE id=? AND tenant_id=?', [$leadId, $tenant->id]);
        if (!$lead) $this->back();

        $stage = $this->db->one('SELECT * FROM crm_stages WHERE id=? AND tenant_id=? AND pipeline_id=?', [$stageId, $tenant->id, $pipelineId]);
        if (!$stage) {
            $this->session->flash('error', 'Etapa inválida.');
            $this->back();
        }

        $dealId = $this->db->insert('crm_deals', [
            'tenant_id'         => $tenant->id,
            'lead_id'           => $leadId,
            'pipeline_id'       => $pipelineId,
            'stage_id'          => $stageId,
            'owner_id'          => ((int)$this->input('owner_id', 0)) ?: null,
            'title'             => $title,
            'description'       => trim((string)$this->input('description', '')) ?: null,
            'amount'            => (float)$this->input('amount', 0),
            'currency'          => substr((string)$this->input('currency', 'USD'), 0, 8) ?: 'USD',
            'probability'       => (float)($stage['probability'] ?? 0),
            'expected_close_on' => $this->normalizeDate($this->input('expected_close_on')),
            'retainer_template_id' => ((int)$this->input('retainer_template_id', 0)) ?: null,
        ]);

        if ((int)$stage['is_won'] === 1) {
            $this->db->update('crm_deals', ['won_at' => date('Y-m-d H:i:s'), 'actual_close_on' => date('Y-m-d')], 'id=?', [$dealId]);
        } elseif ((int)$stage['is_lost'] === 1) {
            $this->db->update('crm_deals', ['lost_at' => date('Y-m-d H:i:s'), 'actual_close_on' => date('Y-m-d')], 'id=?', [$dealId]);
        }

        $this->session->flash('success', 'Oportunidad creada.');
        $this->redirect('/t/' . $tenant->slug . '/crm/leads/' . $leadId);
    }

    public function dealUpdate(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('crm');
        $this->requireCan('crm.edit');
        $this->validateCsrf();

        $id = (int)$params['id'];
        $deal = $this->db->one('SELECT * FROM crm_deals WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if (!$deal) $this->back();

        $stageId = (int)$this->input('stage_id', $deal['stage_id']);
        $stage = $this->db->one('SELECT * FROM crm_stages WHERE id=? AND tenant_id=?', [$stageId, $tenant->id]);
        if (!$stage) $stage = ['probability' => $deal['probability'], 'is_won' => 0, 'is_lost' => 0];

        $update = [
            'stage_id'          => $stageId,
            'owner_id'          => ((int)$this->input('owner_id', 0)) ?: null,
            'title'             => trim((string)$this->input('title', $deal['title'])),
            'description'       => trim((string)$this->input('description', '')) ?: null,
            'amount'            => (float)$this->input('amount', $deal['amount']),
            'currency'          => substr((string)$this->input('currency', $deal['currency']), 0, 8),
            'probability'       => (float)($stage['probability'] ?? $deal['probability']),
            'expected_close_on' => $this->normalizeDate($this->input('expected_close_on')),
            'lost_reason'       => trim((string)$this->input('lost_reason', '')) ?: null,
        ];
        if ((int)$stage['is_won'] === 1 && empty($deal['won_at'])) {
            $update['won_at'] = date('Y-m-d H:i:s');
            $update['actual_close_on'] = date('Y-m-d');
            $update['lost_at'] = null;
        } elseif ((int)$stage['is_lost'] === 1 && empty($deal['lost_at'])) {
            $update['lost_at'] = date('Y-m-d H:i:s');
            $update['actual_close_on'] = date('Y-m-d');
            $update['won_at'] = null;
        } elseif ((int)$stage['is_won'] === 0 && (int)$stage['is_lost'] === 0) {
            $update['won_at'] = null;
            $update['lost_at'] = null;
            $update['actual_close_on'] = null;
        }
        $this->db->update('crm_deals', $update, 'id=? AND tenant_id=?', [$id, $tenant->id]);

        $this->session->flash('success', 'Oportunidad actualizada.');
        $this->redirect('/t/' . $tenant->slug . '/crm/leads/' . $deal['lead_id']);
    }

    public function dealMove(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('crm');
        $this->requireCan('crm.edit');
        $this->validateCsrf();

        $id = (int)$params['id'];
        $stageId = (int)$this->input('stage_id', 0);
        $deal = $this->db->one('SELECT * FROM crm_deals WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        $stage = $this->db->one('SELECT * FROM crm_stages WHERE id=? AND tenant_id=?', [$stageId, $tenant->id]);
        if (!$deal || !$stage) { $this->json(['ok' => false], 400); }

        $update = [
            'stage_id'    => $stageId,
            'probability' => (float)$stage['probability'],
        ];
        if ((int)$stage['is_won'] === 1) {
            $update['won_at'] = date('Y-m-d H:i:s');
            $update['actual_close_on'] = date('Y-m-d');
            $update['lost_at'] = null;
        } elseif ((int)$stage['is_lost'] === 1) {
            $update['lost_at'] = date('Y-m-d H:i:s');
            $update['actual_close_on'] = date('Y-m-d');
            $update['won_at'] = null;
        } else {
            $update['won_at'] = null;
            $update['lost_at'] = null;
            $update['actual_close_on'] = null;
        }
        $this->db->update('crm_deals', $update, 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->json(['ok' => true]);
    }

    public function dealDelete(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('crm');
        $this->requireCan('crm.delete');
        $this->validateCsrf();

        $id = (int)$params['id'];
        $deal = $this->db->one('SELECT lead_id FROM crm_deals WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->db->delete('crm_activities', 'deal_id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->db->delete('crm_deals', 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->session->flash('success', 'Oportunidad eliminada.');
        if ($deal) $this->redirect('/t/' . $tenant->slug . '/crm/leads/' . $deal['lead_id']);
        $this->redirect('/t/' . $tenant->slug . '/crm/pipeline');
    }

    /* ─────────────────────────────────────────────────────────────────── */
    /* ACTIVITIES                                                           */
    /* ─────────────────────────────────────────────────────────────────── */

    public function activityStore(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('crm');
        $this->requireCan('crm.edit');
        $this->validateCsrf();

        $leadId = ((int)$this->input('lead_id', 0)) ?: null;
        $dealId = ((int)$this->input('deal_id', 0)) ?: null;
        $type = (string)$this->input('type', 'task');
        if (!in_array($type, self::ACTIVITY_TYPES, true)) $type = 'task';

        $subject = trim((string)$this->input('subject', ''));
        if ($subject === '') {
            $this->session->flash('error', 'El asunto de la actividad es obligatorio.');
            $this->back();
        }

        $user = $this->auth->user();
        $this->db->insert('crm_activities', [
            'tenant_id'    => $tenant->id,
            'lead_id'      => $leadId,
            'deal_id'      => $dealId,
            'owner_id'     => ((int)$this->input('owner_id', 0)) ?: (int)$user['id'],
            'type'         => $type,
            'subject'      => $subject,
            'body'         => trim((string)$this->input('body', '')) ?: null,
            'scheduled_at' => $this->normalizeDateTime($this->input('scheduled_at')),
            'duration_min' => ((int)$this->input('duration_min', 0)) ?: null,
            'location'     => trim((string)$this->input('location', '')) ?: null,
            'outcome'      => 'pending',
            'created_by'   => (int)$user['id'],
        ]);

        if ($leadId) {
            $this->db->update('crm_leads', ['last_contacted_at' => date('Y-m-d H:i:s')], 'id=? AND tenant_id=?', [$leadId, $tenant->id]);
        }

        $this->session->flash('success', 'Actividad creada.');
        $this->back();
    }

    public function activityComplete(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('crm');
        $this->requireCan('crm.edit');
        $this->validateCsrf();

        $id = (int)$params['id'];
        $outcome = (string)$this->input('outcome', 'completed');
        if (!in_array($outcome, self::ACTIVITY_OUTCOMES, true)) $outcome = 'completed';

        $this->db->update('crm_activities', [
            'outcome'      => $outcome,
            'completed_at' => $outcome === 'completed' ? date('Y-m-d H:i:s') : null,
        ], 'id=? AND tenant_id=?', [$id, $tenant->id]);

        $this->session->flash('success', 'Actividad actualizada.');
        $this->back();
    }

    public function activityDelete(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('crm');
        $this->requireCan('crm.edit');
        $this->validateCsrf();
        $this->db->delete('crm_activities', 'id=? AND tenant_id=?', [(int)$params['id'], $tenant->id]);
        $this->back();
    }

    /* ─────────────────────────────────────────────────────────────────── */
    /* NOTES                                                                */
    /* ─────────────────────────────────────────────────────────────────── */

    public function noteStore(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('crm');
        $this->requireCan('crm.edit');
        $this->validateCsrf();

        $leadId = (int)$this->input('lead_id', 0);
        $body = trim((string)$this->input('body', ''));
        if ($leadId <= 0 || $body === '') $this->back();
        $lead = $this->db->one('SELECT id FROM crm_leads WHERE id=? AND tenant_id=?', [$leadId, $tenant->id]);
        if (!$lead) $this->back();
        $user = $this->auth->user();
        $this->db->insert('crm_notes', [
            'tenant_id' => $tenant->id,
            'lead_id'   => $leadId,
            'author_id' => (int)$user['id'],
            'body'      => $body,
            'is_pinned' => (int)$this->input('is_pinned', 0) === 1 ? 1 : 0,
        ]);
        $this->back();
    }

    public function noteDelete(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('crm');
        $this->requireCan('crm.edit');
        $this->validateCsrf();
        $this->db->delete('crm_notes', 'id=? AND tenant_id=?', [(int)$params['id'], $tenant->id]);
        $this->back();
    }

    /* ─────────────────────────────────────────────────────────────────── */
    /* SETTINGS · pipelines / stages / sources / tags                      */
    /* ─────────────────────────────────────────────────────────────────── */

    public function settings(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('crm');
        $this->requireCan('crm.config');

        $pipelines = $this->db->all('SELECT * FROM crm_pipelines WHERE tenant_id=? ORDER BY sort_order, id', [$tenant->id]);
        $stages = $this->db->all('SELECT * FROM crm_stages WHERE tenant_id=? ORDER BY pipeline_id, sort_order', [$tenant->id]);
        $sources = $this->db->all('SELECT * FROM crm_sources WHERE tenant_id=? ORDER BY sort_order, name', [$tenant->id]);
        $tags = $this->db->all('SELECT * FROM crm_tags WHERE tenant_id=? ORDER BY name', [$tenant->id]);

        $stagesByPipeline = [];
        foreach ($stages as $s) $stagesByPipeline[(int)$s['pipeline_id']][] = $s;

        $this->render('crm/settings', [
            'title' => 'CRM · Configuración',
            'pipelines' => $pipelines,
            'stagesByPipeline' => $stagesByPipeline,
            'sources' => $sources,
            'tags' => $tags,
        ]);
    }

    public function pipelineStore(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('crm');
        $this->requireCan('crm.config');
        $this->validateCsrf();
        $name = trim((string)$this->input('name', ''));
        if ($name === '') $this->back();
        $slug = $this->slugify($name);
        $exists = $this->db->val('SELECT id FROM crm_pipelines WHERE tenant_id=? AND slug=?', [$tenant->id, $slug]);
        if ($exists) $slug = $slug . '-' . substr(bin2hex(random_bytes(2)), 0, 4);
        $this->db->insert('crm_pipelines', [
            'tenant_id'  => $tenant->id,
            'slug'       => $slug,
            'name'       => $name,
            'description'=> trim((string)$this->input('description', '')) ?: null,
            'icon'       => (string)$this->input('icon', 'target'),
            'color'      => (string)$this->input('color', '#7c5cff'),
            'is_active'  => 1,
            'is_default' => (int)$this->input('is_default', 0) === 1 ? 1 : 0,
        ]);
        $this->session->flash('success', 'Pipeline creado.');
        $this->redirect('/t/' . $tenant->slug . '/crm/settings');
    }

    public function pipelineUpdate(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('crm');
        $this->requireCan('crm.config');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $update = [
            'name'        => trim((string)$this->input('name', '')),
            'description' => trim((string)$this->input('description', '')) ?: null,
            'icon'        => (string)$this->input('icon', 'target'),
            'color'       => (string)$this->input('color', '#7c5cff'),
            'is_active'   => (int)$this->input('is_active', 1) === 1 ? 1 : 0,
            'is_default'  => (int)$this->input('is_default', 0) === 1 ? 1 : 0,
        ];
        if ((int)$update['is_default'] === 1) {
            $this->db->update('crm_pipelines', ['is_default' => 0], 'tenant_id=? AND id<>?', [$tenant->id, $id]);
        }
        $this->db->update('crm_pipelines', $update, 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->redirect('/t/' . $tenant->slug . '/crm/settings');
    }

    public function pipelineDelete(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('crm');
        $this->requireCan('crm.config');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $deals = (int)$this->db->val('SELECT COUNT(*) FROM crm_deals WHERE pipeline_id=? AND tenant_id=?', [$id, $tenant->id]);
        if ($deals > 0) {
            $this->session->flash('error', 'No se puede eliminar: el pipeline tiene oportunidades.');
            $this->redirect('/t/' . $tenant->slug . '/crm/settings');
        }
        $this->db->delete('crm_stages', 'pipeline_id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->db->delete('crm_pipelines', 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->session->flash('success', 'Pipeline eliminado.');
        $this->redirect('/t/' . $tenant->slug . '/crm/settings');
    }

    public function stageStore(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('crm');
        $this->requireCan('crm.config');
        $this->validateCsrf();
        $pipelineId = (int)$this->input('pipeline_id', 0);
        $name = trim((string)$this->input('name', ''));
        if (!$pipelineId || $name === '') $this->back();
        $slug = $this->slugify($name);
        $exists = $this->db->val('SELECT id FROM crm_stages WHERE pipeline_id=? AND slug=?', [$pipelineId, $slug]);
        if ($exists) $slug = $slug . '-' . substr(bin2hex(random_bytes(2)), 0, 4);
        $maxOrder = (int)$this->db->val('SELECT IFNULL(MAX(sort_order),0)+1 FROM crm_stages WHERE pipeline_id=?', [$pipelineId]);
        $this->db->insert('crm_stages', [
            'tenant_id'   => $tenant->id,
            'pipeline_id' => $pipelineId,
            'slug'        => $slug,
            'name'        => $name,
            'probability' => max(0, min(100, (float)$this->input('probability', 0))),
            'color'       => (string)$this->input('color', '#94a3b8'),
            'is_won'      => (int)$this->input('is_won', 0) === 1 ? 1 : 0,
            'is_lost'     => (int)$this->input('is_lost', 0) === 1 ? 1 : 0,
            'sort_order'  => $maxOrder,
        ]);
        $this->redirect('/t/' . $tenant->slug . '/crm/settings');
    }

    public function stageUpdate(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('crm');
        $this->requireCan('crm.config');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $this->db->update('crm_stages', [
            'name'        => trim((string)$this->input('name', '')),
            'probability' => max(0, min(100, (float)$this->input('probability', 0))),
            'color'       => (string)$this->input('color', '#94a3b8'),
            'is_won'      => (int)$this->input('is_won', 0) === 1 ? 1 : 0,
            'is_lost'     => (int)$this->input('is_lost', 0) === 1 ? 1 : 0,
        ], 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->redirect('/t/' . $tenant->slug . '/crm/settings');
    }

    public function stageDelete(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('crm');
        $this->requireCan('crm.config');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $deals = (int)$this->db->val('SELECT COUNT(*) FROM crm_deals WHERE stage_id=? AND tenant_id=?', [$id, $tenant->id]);
        if ($deals > 0) {
            $this->session->flash('error', 'No se puede eliminar: la etapa tiene oportunidades.');
            $this->redirect('/t/' . $tenant->slug . '/crm/settings');
        }
        $this->db->delete('crm_stages', 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->redirect('/t/' . $tenant->slug . '/crm/settings');
    }

    public function sourceStore(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('crm');
        $this->requireCan('crm.config');
        $this->validateCsrf();
        $name = trim((string)$this->input('name', ''));
        if ($name === '') $this->back();
        $slug = $this->slugify($name);
        $exists = $this->db->val('SELECT id FROM crm_sources WHERE tenant_id=? AND slug=?', [$tenant->id, $slug]);
        if ($exists) $slug = $slug . '-' . substr(bin2hex(random_bytes(2)), 0, 4);
        $this->db->insert('crm_sources', [
            'tenant_id' => $tenant->id,
            'slug'      => $slug,
            'name'      => $name,
            'icon'      => (string)$this->input('icon', 'globe'),
            'color'     => (string)$this->input('color', '#6366f1'),
            'is_active' => 1,
        ]);
        $this->redirect('/t/' . $tenant->slug . '/crm/settings');
    }

    public function sourceUpdate(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('crm');
        $this->requireCan('crm.config');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $this->db->update('crm_sources', [
            'name'      => trim((string)$this->input('name', '')),
            'icon'      => (string)$this->input('icon', 'globe'),
            'color'     => (string)$this->input('color', '#6366f1'),
            'is_active' => (int)$this->input('is_active', 1) === 1 ? 1 : 0,
        ], 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->redirect('/t/' . $tenant->slug . '/crm/settings');
    }

    public function sourceDelete(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('crm');
        $this->requireCan('crm.config');
        $this->validateCsrf();
        $this->db->delete('crm_sources', 'id=? AND tenant_id=?', [(int)$params['id'], $tenant->id]);
        $this->redirect('/t/' . $tenant->slug . '/crm/settings');
    }

    public function tagStore(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('crm');
        $this->requireCan('crm.config');
        $this->validateCsrf();
        $name = trim((string)$this->input('name', ''));
        if ($name === '') $this->back();
        $slug = $this->slugify($name);
        $exists = $this->db->val('SELECT id FROM crm_tags WHERE tenant_id=? AND slug=?', [$tenant->id, $slug]);
        if ($exists) $slug = $slug . '-' . substr(bin2hex(random_bytes(2)), 0, 4);
        $this->db->insert('crm_tags', [
            'tenant_id' => $tenant->id,
            'slug'      => $slug,
            'name'      => $name,
            'color'     => (string)$this->input('color', '#7c5cff'),
        ]);
        $this->redirect('/t/' . $tenant->slug . '/crm/settings');
    }

    public function tagDelete(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('crm');
        $this->requireCan('crm.config');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $this->db->delete('crm_lead_tags', 'tag_id=?', [$id]);
        $this->db->delete('crm_tags', 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->redirect('/t/' . $tenant->slug . '/crm/settings');
    }

    /* ─────────────────────────────────────────────────────────────────── */
    /* HELPERS                                                              */
    /* ─────────────────────────────────────────────────────────────────── */

    protected function generateLeadCode(int $tenantId): string
    {
        $count = (int)$this->db->val('SELECT COUNT(*) FROM crm_leads WHERE tenant_id=?', [$tenantId]) + 1;
        $base = 'LD-' . str_pad((string)$count, 5, '0', STR_PAD_LEFT);
        while ($this->db->val('SELECT id FROM crm_leads WHERE tenant_id=? AND code=?', [$tenantId, $base])) {
            $count++;
            $base = 'LD-' . str_pad((string)$count, 5, '0', STR_PAD_LEFT);
        }
        return $base;
    }

    protected function syncLeadTags(int $leadId, array $tagIds): void
    {
        $this->db->delete('crm_lead_tags', 'lead_id=?', [$leadId]);
        foreach (array_unique($tagIds) as $tid) {
            $tid = (int)$tid;
            if ($tid > 0) {
                try {
                    $this->db->insert('crm_lead_tags', ['lead_id' => $leadId, 'tag_id' => $tid]);
                } catch (\Throwable $e) { /* ignore duplicates */ }
            }
        }
    }

    protected function validStatus($v): string
    {
        $v = (string)$v;
        return in_array($v, self::LEAD_STATUSES, true) ? $v : 'new';
    }

    protected function validRating($v): string
    {
        $v = (string)$v;
        return in_array($v, self::RATINGS, true) ? $v : 'warm';
    }

    protected function normalizeDate($v): ?string
    {
        $v = trim((string)$v);
        if ($v === '') return null;
        $ts = strtotime($v);
        return $ts ? date('Y-m-d', $ts) : null;
    }

    protected function normalizeDateTime($v): ?string
    {
        $v = trim((string)$v);
        if ($v === '') return null;
        $v = str_replace('T', ' ', $v);
        $ts = strtotime($v);
        return $ts ? date('Y-m-d H:i:s', $ts) : null;
    }

    protected function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9\s-]/u', '', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text);
        $text = preg_replace('/[\s-]+/', '-', $text);
        return trim($text, '-') ?: 'item';
    }
}
