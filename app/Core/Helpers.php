<?php
namespace App\Core;

class Helpers
{
    public static function slug(string $t): string
    {
        $t = strtolower(trim($t));
        $t = preg_replace('/[^a-z0-9\s-]/', '', $t);
        $t = preg_replace('/[\s-]+/', '-', $t);
        return trim($t, '-');
    }

    public static function ago(string $datetime): string
    {
        $ts = strtotime($datetime);
        if (!$ts) return '';
        $diff = time() - $ts;
        if ($diff < 60) return 'hace unos segundos';
        if ($diff < 3600) return 'hace ' . floor($diff/60) . ' min';
        if ($diff < 86400) return 'hace ' . floor($diff/3600) . ' h';
        if ($diff < 604800) return 'hace ' . floor($diff/86400) . ' d';
        return date('d/m/Y', $ts);
    }

    public static function randomCode(int $len = 10): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $out = '';
        for ($i = 0; $i < $len; $i++) {
            $out .= $alphabet[random_int(0, strlen($alphabet)-1)];
        }
        return $out;
    }

    public static function ticketCode(int $tenantId, int $id): string
    {
        return 'TK-' . str_pad((string)$tenantId, 2, '0', STR_PAD_LEFT) . '-' . str_pad((string)$id, 5, '0', STR_PAD_LEFT);
    }

    public static function colorFor(string $s): string
    {
        $palette = ['#6366f1','#8b5cf6','#ec4899','#f43f5e','#f59e0b','#10b981','#14b8a6','#0ea5e9','#3b82f6','#22c55e'];
        $sum = 0;
        for ($i = 0; $i < strlen($s); $i++) $sum += ord($s[$i]);
        return $palette[$sum % count($palette)];
    }

    public static function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name));
        $out = '';
        foreach ($parts as $p) { if ($p !== '') $out .= strtoupper(mb_substr($p, 0, 1)); if (mb_strlen($out) >= 2) break; }
        return $out ?: 'U';
    }

    public static function priorityBadge(string $p): string
    {
        $labels = ['low'=>'Baja','medium'=>'Media','high'=>'Alta','urgent'=>'Urgente'];
        $lbl = $labels[$p] ?? ucfirst($p);
        return '<span class="status-pill priority-' . $p . '">' . $lbl . '</span>';
    }

    public static function statusBadge(string $s): string
    {
        $map = [
            'open' => ['Abierto','status-open'],
            'in_progress' => ['En progreso','status-progress'],
            'on_hold' => ['En espera','status-hold'],
            'resolved' => ['Resuelto','status-resolved'],
            'closed' => ['Cerrado','status-closed'],
        ];
        [$l, $c] = $map[$s] ?? [ucfirst($s), 'status-hold'];
        return '<span class="status-pill ' . $c . '">' . $l . '</span>';
    }
}
