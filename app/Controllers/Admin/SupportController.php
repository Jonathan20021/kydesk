<?php
namespace App\Controllers\Admin;

class SupportController extends AdminController
{
    public function index(): void
    {
        $this->requireCan('support.view');
        $status = (string)$this->input('status', '');
        $where = ['1=1']; $params = [];
        if ($status) { $where[] = 's.status = ?'; $params[] = $status; }

        $tickets = $this->db->all(
            "SELECT s.*, t.name AS tenant_name, t.slug AS tenant_slug
             FROM saas_support_tickets s JOIN tenants t ON t.id = s.tenant_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY s.created_at DESC LIMIT 200",
            $params
        );

        $stats = [
            'open' => (int)$this->db->val("SELECT COUNT(*) FROM saas_support_tickets WHERE status='open'"),
            'in_progress' => (int)$this->db->val("SELECT COUNT(*) FROM saas_support_tickets WHERE status='in_progress'"),
            'resolved' => (int)$this->db->val("SELECT COUNT(*) FROM saas_support_tickets WHERE status IN ('resolved','closed')"),
        ];

        $this->render('admin/support/index', [
            'title' => 'Soporte',
            'pageHeading' => 'Tickets de Soporte SaaS',
            'tickets' => $tickets,
            'status' => $status,
            'stats' => $stats,
        ]);
    }

    public function update(array $params): void
    {
        $this->requireCan('support.edit');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $data = [
            'status' => (string)$this->input('status', 'open'),
            'priority' => (string)$this->input('priority', 'medium'),
        ];
        if ($data['status'] === 'resolved') $data['resolved_at'] = date('Y-m-d H:i:s');
        $this->db->update('saas_support_tickets', $data, 'id = :id', ['id' => $id]);
        $this->superAuth->log('support.update', 'support', $id);
        $this->session->flash('success', 'Ticket actualizado.');
        $this->back();
    }
}
