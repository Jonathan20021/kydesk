<?php
namespace App\Core;

/**
 * Helper compartido para llamadas IA del módulo de Reuniones.
 * Reusa la infraestructura central (api key, modelo y cuota gestionados por el super admin).
 *
 *   - guard()     : valida que el tenant tenga IA asignada y con cuota
 *   - call()      : ejecuta una completion contra el provider configurado y registra en ai_completions
 *   - cheapModel(): elige el modelo más barato/rápido (haiku) por defecto
 */
class MeetingAi
{
    /**
     * Valida si el tenant puede usar IA en este momento.
     * Gate de Enterprise: Plan::has('ai_assist') solo es true en plan Enterprise
     * con asignación explícita del super admin (vía tenant_module_overrides).
     *
     * @return array{ok:bool, error?:string, cfg?:array, global?:array}
     */
    public static function guard(Tenant $tenant): array
    {
        $db = Application::get()->db;

        // 1) Plan gate — ai_assist solo está en Plan::FEATURES['enterprise']
        if (!Plan::has($tenant, 'ai_assist')) {
            return ['ok' => false, 'error' => 'IA disponible solo en plan Enterprise'];
        }

        // 2) Asignación explícita por el super admin
        $cfg = $db->one('SELECT * FROM ai_settings WHERE tenant_id = ?', [$tenant->id]);
        if (!$cfg || !(int)$cfg['is_assigned']) {
            return ['ok' => false, 'error' => 'IA no está asignada a este workspace · contactá al equipo Kydesk'];
        }
        if (!(int)$cfg['is_enabled']) {
            return ['ok' => false, 'error' => 'IA está pausada por el workspace'];
        }

        // 3) Configuración global del super admin
        $global = self::loadGlobal($db);
        if (($global['ai_global_enabled'] ?? '0') !== '1') {
            return ['ok' => false, 'error' => 'IA globalmente deshabilitada'];
        }
        if (empty($global['ai_api_key'])) {
            return ['ok' => false, 'error' => 'API key no configurada'];
        }

        // 4) Cuota: enforcement en requests + en tokens (si token_quota_monthly > 0)
        if ((int)$cfg['monthly_quota'] > 0 && (int)$cfg['used_this_month'] >= (int)$cfg['monthly_quota']) {
            return ['ok' => false, 'error' => 'Cuota mensual de requests alcanzada'];
        }
        $tokenQuota = (int)($cfg['token_quota_monthly'] ?? 0);
        if ($tokenQuota > 0) {
            $tokensUsed = (int)($cfg['tokens_in_this_month'] ?? 0) + (int)($cfg['tokens_out_this_month'] ?? 0);
            if ($tokensUsed >= $tokenQuota) {
                return ['ok' => false, 'error' => 'Cuota mensual de tokens alcanzada'];
            }
        }

        return ['ok' => true, 'cfg' => $cfg, 'global' => $global];
    }

    /**
     * Ejecuta la llamada al provider y registra el completion en ai_completions.
     * @param array $prompt ['system' => string, 'user' => string]
     * @param array $opts   ['action' => string, 'meeting_id' => int|null, 'user_id' => int|null,
     *                       'max_tokens' => int, 'temperature' => float]
     * @return array{ok:bool, text?:string, tokens_in?:int, tokens_out?:int, error?:string, duration_ms?:int}
     */
    public static function call(Tenant $tenant, array $prompt, array $opts = []): array
    {
        $guard = self::guard($tenant);
        if (!$guard['ok']) return $guard;

        $cfg = $guard['cfg'];
        $global = $guard['global'];
        $apiKey = (string)$global['ai_api_key'];
        $model  = (string)($cfg['model'] ?: ($global['ai_default_model'] ?? 'claude-haiku-4-5'));
        $action = (string)($opts['action'] ?? 'meeting_ai');

        $start = microtime(true);
        $result = self::callAnthropic($apiKey, $model, $prompt, $opts);
        $duration = (int)((microtime(true) - $start) * 1000);
        $result['duration_ms'] = $duration;

        $db = Application::get()->db;
        try {
            $db->insert('ai_completions', [
                'tenant_id'   => $tenant->id,
                'user_id'     => $opts['user_id'] ?? null,
                'ticket_id'   => null,
                'action'      => substr($action, 0, 40),
                'input_text'  => mb_substr($prompt['user'] ?? '', 0, 5000),
                'output_text' => $result['ok'] ? ($result['text'] ?? '') : null,
                'tokens_in'   => $result['tokens_in'] ?? 0,
                'tokens_out'  => $result['tokens_out'] ?? 0,
                'duration_ms' => $duration,
                'status'      => $result['ok'] ? 'ok' : 'error',
                'error'       => $result['ok'] ? null : substr((string)($result['error'] ?? ''), 0, 500),
            ]);
        } catch (\Throwable $e) { /* tabla puede tener schema distinto, no bloquea */ }

        if ($result['ok']) {
            // Deducir cuota: +1 request + tokens reales (in/out separados)
            $tin  = (int)($result['tokens_in']  ?? 0);
            $tout = (int)($result['tokens_out'] ?? 0);
            try {
                $db->run(
                    'UPDATE ai_settings
                       SET used_this_month       = used_this_month + 1,
                           tokens_in_this_month  = tokens_in_this_month + ?,
                           tokens_out_this_month = tokens_out_this_month + ?
                     WHERE tenant_id = ?',
                    [$tin, $tout, $tenant->id]
                );
            } catch (\Throwable $e) { /* tabla puede no tener las columnas de tokens si el migration falló */ }
        }
        return $result;
    }

    public static function cheapModel(?array $cfg = null): string
    {
        // Haiku es el más rápido + barato — perfecto para análisis cortos
        return 'claude-haiku-4-5';
    }

    /* ─────────── internos ─────────── */

    protected static function loadGlobal(Database $db): array
    {
        $rows = $db->all("SELECT `key`,`value` FROM saas_settings WHERE `key` LIKE 'ai_%'");
        $map = [];
        foreach ($rows as $r) $map[$r['key']] = $r['value'];
        return $map + [
            'ai_provider' => 'anthropic',
            'ai_api_key' => '',
            'ai_default_model' => 'claude-haiku-4-5',
            'ai_global_enabled' => '0',
        ];
    }

    protected static function callAnthropic(string $apiKey, string $model, array $prompt, array $opts): array
    {
        $payload = [
            'model'      => $model,
            'max_tokens' => (int)($opts['max_tokens'] ?? 1024),
            'system'     => (string)($prompt['system'] ?? ''),
            'messages'   => [
                ['role' => 'user', 'content' => (string)($prompt['user'] ?? '')],
            ],
        ];
        if (isset($opts['temperature'])) $payload['temperature'] = (float)$opts['temperature'];

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => (int)($opts['timeout'] ?? 30),
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01',
            ],
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($resp === false) return ['ok' => false, 'error' => 'curl: ' . $err];
        $body = json_decode($resp, true);
        if ($code >= 400) return ['ok' => false, 'error' => $body['error']['message'] ?? "HTTP $code"];

        $text = '';
        if (!empty($body['content']) && is_array($body['content'])) {
            foreach ($body['content'] as $c) {
                if (($c['type'] ?? '') === 'text') $text .= $c['text'];
            }
        }
        return [
            'ok'         => true,
            'text'       => $text,
            'tokens_in'  => (int)($body['usage']['input_tokens'] ?? 0),
            'tokens_out' => (int)($body['usage']['output_tokens'] ?? 0),
        ];
    }

    /**
     * Intenta extraer un JSON válido del texto devuelto por la IA.
     * Acepta ```json\n{...}\n``` o {...} crudo.
     */
    public static function extractJson(string $text): ?array
    {
        $text = trim($text);
        // Strip markdown fences
        if (preg_match('/```(?:json)?\s*(\{.*?\}|\[.*?\])\s*```/s', $text, $m)) {
            $text = $m[1];
        }
        // First JSON-looking block
        if (preg_match('/(\{.*\}|\[.*\])/s', $text, $m)) {
            $text = $m[1];
        }
        $j = json_decode($text, true);
        return is_array($j) ? $j : null;
    }
}
