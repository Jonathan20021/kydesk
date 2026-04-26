<?php
namespace App\Controllers\Api;

class ExportController extends BaseApiController
{
    public function ticketsCsv(): void
    {
        $this->authenticate('read');
        $where = ['t.tenant_id = ?']; $args = [$this->tid()];
        if ($s = $_GET['status'] ?? null)   { $where[] = 't.status = ?'; $args[] = $s; }
        if ($p = $_GET['priority'] ?? null) { $where[] = 't.priority = ?'; $args[] = $p; }
        if ($a = $_GET['created_after'] ?? null)  { $where[] = 't.created_at >= ?'; $args[] = $a; }
        if ($b = $_GET['created_before'] ?? null) { $where[] = 't.created_at <= ?'; $args[] = $b; }
        $whereSql = implode(' AND ', $where);
        $rows = $this->db->all(
            "SELECT t.code, t.subject, t.status, t.priority, t.channel,
                    t.requester_name, t.requester_email, t.created_at, t.resolved_at,
                    t.satisfaction_rating, t.tags
             FROM tickets t WHERE $whereSql
             ORDER BY t.id DESC LIMIT 5000",
            $args
        );

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="kydesk-tickets-' . date('Y-m-d') . '.csv"');
        $f = fopen('php://output', 'w');
        fputcsv($f, ['code','subject','status','priority','channel','requester_name','requester_email','created_at','resolved_at','satisfaction_rating','tags']);
        foreach ($rows as $r) {
            fputcsv($f, [$r['code'], $r['subject'], $r['status'], $r['priority'], $r['channel'], $r['requester_name'], $r['requester_email'], $r['created_at'], $r['resolved_at'], $r['satisfaction_rating'], $r['tags']]);
        }
        fclose($f);
        exit;
    }

    public function companiesCsv(): void
    {
        $this->authenticate('read');
        $rows = $this->db->all('SELECT name, industry, size, tier, website, phone, address, created_at FROM companies WHERE tenant_id=? ORDER BY id DESC', [$this->tid()]);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="kydesk-companies-' . date('Y-m-d') . '.csv"');
        $f = fopen('php://output', 'w');
        fputcsv($f, ['name','industry','size','tier','website','phone','address','created_at']);
        foreach ($rows as $r) fputcsv($f, $r);
        fclose($f);
        exit;
    }

    public function usersCsv(): void
    {
        $this->authenticate('read');
        $rows = $this->db->all('SELECT name, email, title, phone, is_technician, is_active, created_at, last_login_at FROM users WHERE tenant_id=? ORDER BY id DESC', [$this->tid()]);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="kydesk-users-' . date('Y-m-d') . '.csv"');
        $f = fopen('php://output', 'w');
        fputcsv($f, ['name','email','title','phone','is_technician','is_active','created_at','last_login_at']);
        foreach ($rows as $r) fputcsv($f, $r);
        fclose($f);
        exit;
    }
}
