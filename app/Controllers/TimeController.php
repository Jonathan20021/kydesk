<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Plan;

class TimeController extends Controller
{
    public function index(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('time_tracking');
        $this->requireCan('time.view');

        $userId = (int)$this->input('user_id', 0);
        $from = (string)$this->input('from', date('Y-m-01'));
        $to = (string)$this->input('to', date('Y-m-d'));
        $billable = (string)$this->input('billable', '');

        $where = ['te.tenant_id = ?', "te.started_at >= ?", "te.started_at < DATE_ADD(?, INTERVAL 1 DAY)"];
        $args = [$tenant->id, $from . ' 00:00:00', $to];
        if ($userId) { $where[] = 'te.user_id = ?'; $args[] = $userId; }
        if ($billable === 'yes') $where[] = 'te.billable = 1';
        if ($billable === 'no')  $where[] = 'te.billable = 0';

        $entries = $this->db->all(
            "SELECT te.*, t.code AS ticket_code, t.subject AS ticket_subject, u.name AS user_name, r.code AS retainer_code, r.name AS retainer_name
             FROM time_entries te
             LEFT JOIN tickets t ON t.id = te.ticket_id
             LEFT JOIN users u ON u.id = te.user_id
             LEFT JOIN retainers r ON r.id = te.retainer_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY te.started_at DESC LIMIT 200",
            $args
        );

        $totalHours = (float)$this->db->val("SELECT IFNULL(SUM(hours),0) FROM time_entries te WHERE " . implode(' AND ', $where), $args);
        $billableHours = (float)$this->db->val("SELECT IFNULL(SUM(hours),0) FROM time_entries te WHERE " . implode(' AND ', $where) . ' AND te.billable = 1', $args);
        $totalAmount = (float)$this->db->val("SELECT IFNULL(SUM(amount),0) FROM time_entries te WHERE " . implode(' AND ', $where), $args);

        $running = $this->db->one('SELECT te.*, t.code AS ticket_code, t.subject AS ticket_subject FROM time_entries te LEFT JOIN tickets t ON t.id = te.ticket_id WHERE te.tenant_id=? AND te.user_id=? AND te.is_running=1 LIMIT 1', [$tenant->id, $this->auth->userId()]);

        $users = $this->db->all('SELECT id, name FROM users WHERE tenant_id = ? AND is_active = 1 ORDER BY name', [$tenant->id]);

        $this->render('time/index', [
            'title' => 'Time Tracking',
            'entries' => $entries,
            'running' => $running,
            'users' => $users,
            'userId' => $userId,
            'from' => $from,
            'to' => $to,
            'billable' => $billable,
            'totalHours' => $totalHours,
            'billableHours' => $billableHours,
            'totalAmount' => $totalAmount,
        ]);
    }

    /** Start a new running timer (one per user). */
    public function start(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('time_tracking');
        $this->requireCan('time.track');
        $this->validateCsrf();

        $userId = $this->auth->userId();
        // Stop any existing running timer
        $running = $this->db->one('SELECT * FROM time_entries WHERE tenant_id=? AND user_id=? AND is_running=1', [$tenant->id, $userId]);
        if ($running) $this->stopEntry((int)$running['id']);

        $ticketId = ((int)$this->input('ticket_id', 0)) ?: null;
        $ticket = $ticketId ? $this->db->one('SELECT * FROM tickets WHERE id=? AND tenant_id=?', [$ticketId, $tenant->id]) : null;

        $retainerId = ((int)$this->input('retainer_id', 0)) ?: null;
        // Auto-detect retainer from ticket's company if not provided
        if (!$retainerId && $ticket && $ticket['company_id'] && Plan::has($tenant, 'retainers')) {
            $rid = $this->db->val("SELECT id FROM retainers WHERE tenant_id=? AND company_id=? AND status='active' ORDER BY created_at DESC LIMIT 1", [$tenant->id, (int)$ticket['company_id']]);
            if ($rid) $retainerId = (int)$rid;
        }

        $rate = (float)$this->input('rate', 0);
        $billable = (int)($this->input('billable', 1) ? 1 : 0);

        $id = $this->db->insert('time_entries', [
            'tenant_id'   => $tenant->id,
            'ticket_id'   => $ticketId,
            'user_id'     => $userId,
            'retainer_id' => $retainerId,
            'started_at'  => date('Y-m-d H:i:s'),
            'description' => (string)$this->input('description', '') ?: null,
            'billable'    => $billable,
            'rate'        => $rate,
            'is_running'  => 1,
        ]);
        $this->session->flash('success','Timer iniciado.');
        $this->backToTicketOrTime($tenant->slug, $ticketId);
    }

    public function stop(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('time_tracking');
        $this->requireCan('time.track');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $entry = $this->db->one('SELECT * FROM time_entries WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if (!$entry) { $this->redirect('/t/' . $tenant->slug . '/time'); }
        $this->stopEntry($id);
        $this->session->flash('success','Timer detenido y registrado.');
        $this->backToTicketOrTime($tenant->slug, $entry['ticket_id'] ? (int)$entry['ticket_id'] : null);
    }

    public function manualStore(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('time_tracking');
        $this->requireCan('time.track');
        $this->validateCsrf();

        $hours = (float)$this->input('hours', 0);
        if ($hours <= 0) { $this->session->flash('error','Las horas deben ser mayores a 0.'); $this->redirect('/t/' . $tenant->slug . '/time'); }

        $startedAt = (string)$this->input('started_at', date('Y-m-d H:i:s'));
        $startedAt = str_replace('T',' ', $startedAt);
        if (strlen($startedAt) === 16) $startedAt .= ':00';
        $endedAt = date('Y-m-d H:i:s', strtotime($startedAt) + (int)round($hours * 3600));

        $rate = (float)$this->input('rate', 0);
        $billable = (int)($this->input('billable', 1) ? 1 : 0);
        $amount = $billable ? round($hours * $rate, 2) : 0;
        $ticketId = ((int)$this->input('ticket_id', 0)) ?: null;
        $retainerId = ((int)$this->input('retainer_id', 0)) ?: null;

        $id = $this->db->insert('time_entries', [
            'tenant_id'  => $tenant->id,
            'ticket_id'  => $ticketId,
            'user_id'    => $this->auth->userId(),
            'retainer_id'=> $retainerId,
            'started_at' => $startedAt,
            'ended_at'   => $endedAt,
            'duration_seconds' => (int)round($hours * 3600),
            'hours'      => $hours,
            'description'=> (string)$this->input('description','') ?: null,
            'billable'   => $billable,
            'rate'       => $rate,
            'amount'     => $amount,
            'is_running' => 0,
        ]);
        $this->maybeApplyToRetainer((int)$id);
        $this->session->flash('success','Tiempo registrado manualmente.');
        $this->backToTicketOrTime($tenant->slug, $ticketId);
    }

    public function delete(array $params): void
    {
        $tenant = $this->requireTenant($params['slug']);
        $this->requireFeature('time_tracking');
        $this->requireCan('time.track');
        $this->validateCsrf();
        $id = (int)$params['id'];
        $e = $this->db->one('SELECT * FROM time_entries WHERE id=? AND tenant_id=?', [$id, $tenant->id]);
        if (!$e) { $this->redirect('/t/' . $tenant->slug . '/time'); }
        // If this entry was applied to a retainer consumption, remove it too
        if ($e['consumption_id']) {
            $this->db->delete('retainer_consumptions', 'id=? AND tenant_id=?', [(int)$e['consumption_id'], $tenant->id]);
        }
        $this->db->delete('time_entries', 'id=? AND tenant_id=?', [$id, $tenant->id]);
        $this->backToTicketOrTime($tenant->slug, $e['ticket_id'] ? (int)$e['ticket_id'] : null);
    }

    /* ─────── helpers ─────── */

    protected function stopEntry(int $entryId): void
    {
        $entry = $this->db->one('SELECT * FROM time_entries WHERE id = ?', [$entryId]);
        if (!$entry || !$entry['is_running']) return;
        $endedAt = date('Y-m-d H:i:s');
        $duration = max(1, strtotime($endedAt) - strtotime($entry['started_at']));
        $hours = round($duration / 3600, 2);
        $rate = (float)$entry['rate'];
        $billable = (int)$entry['billable'];
        $amount = $billable ? round($hours * $rate, 2) : 0;

        $this->db->update('time_entries', [
            'ended_at' => $endedAt,
            'duration_seconds' => $duration,
            'hours' => $hours,
            'amount' => $amount,
            'is_running' => 0,
        ], 'id = :id', ['id' => $entryId]);
        $this->maybeApplyToRetainer($entryId);
    }

    /** Crea consumo en la iguala asociada y actualiza el período abierto. */
    protected function maybeApplyToRetainer(int $entryId): void
    {
        $entry = $this->db->one('SELECT * FROM time_entries WHERE id = ?', [$entryId]);
        if (!$entry || !$entry['retainer_id'] || !$entry['hours']) return;
        $retainer = $this->db->one('SELECT * FROM retainers WHERE id=? AND tenant_id=?', [(int)$entry['retainer_id'], (int)$entry['tenant_id']]);
        if (!$retainer || $retainer['status'] !== 'active') return;

        $period = $this->db->one("SELECT * FROM retainer_periods WHERE retainer_id=? AND status='open' ORDER BY period_start DESC LIMIT 1", [(int)$entry['retainer_id']]);
        if (!$period) return;

        $consumptionId = $this->db->insert('retainer_consumptions', [
            'retainer_id' => (int)$entry['retainer_id'],
            'period_id'   => (int)$period['id'],
            'tenant_id'   => (int)$entry['tenant_id'],
            'ticket_id'   => $entry['ticket_id'],
            'user_id'     => (int)$entry['user_id'],
            'consumed_at' => $entry['started_at'],
            'hours'       => (float)$entry['hours'],
            'description' => $entry['description'] ?: 'Time tracking',
            'billable'    => (int)$entry['billable'],
        ]);

        $this->db->update('time_entries', [
            'period_id' => (int)$period['id'],
            'consumption_id' => $consumptionId,
        ], 'id = :id', ['id' => $entryId]);

        // Recalcular período
        $consumed = (float)$this->db->val('SELECT IFNULL(SUM(hours),0) FROM retainer_consumptions WHERE period_id = ?', [(int)$period['id']]);
        $included = (float)$period['included_hours'];
        $rate = (float)$retainer['overage_hour_rate'];
        $overage = max(0.0, $consumed - $included) * $rate;
        $this->db->update('retainer_periods', [
            'consumed_hours' => $consumed,
            'overage_amount' => $overage,
        ], 'id = :id', ['id' => (int)$period['id']]);
    }

    protected function backToTicketOrTime(string $slug, ?int $ticketId): void
    {
        if ($ticketId) $this->redirect('/t/' . $slug . '/tickets/' . $ticketId);
        else $this->redirect('/t/' . $slug . '/time');
    }
}
