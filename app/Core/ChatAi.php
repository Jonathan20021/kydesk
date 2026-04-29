<?php
namespace App\Core;

/**
 * Orquestador IA del Live Chat.
 *
 * Sustituye al agente humano cuando no está disponible. Reusa la infraestructura
 * Enterprise (api key, modelo y cuota gestionados por el super admin en ai_settings)
 * y agrega:
 *
 *   - Tool use (Anthropic tools API)         · create_ticket / escalate_to_human
 *   - Prompt caching (cache_control)         · system + KB grounding cacheados 5min
 *   - Grounding contra kb_articles del tenant (solo published + visibility=public)
 *   - Tope de turnos por conversación (ai_max_turns) → fuerza escalación
 *   - Audit log por cada acción de la IA
 *
 * Decisión de respuesta:
 *   - mode=off                  → la IA nunca responde
 *   - mode=no_agent             → responde si convo.assigned_to IS NULL
 *   - mode=always               → siempre responde, hasta que escala o cierra
 *
 * Si la cuota se agotó o falla la API, la conversación se escala automáticamente
 * (se crea ticket con el contexto y se avisa al visitante).
 */
class ChatAi
{
    /** Modelo por defecto: barato, rápido, suficiente para chat de soporte. */
    public const DEFAULT_MODEL = 'claude-haiku-4-5';

    /** Tope duro de iteraciones del bucle tool-use (anti-loop runaway). */
    protected const MAX_TOOL_ITERATIONS = 4;

    /** Tope duro de tokens por respuesta (mantiene replies concisos y costo controlado). */
    protected const MAX_OUTPUT_TOKENS = 600;

    /**
     * Decide si la IA debe responder a este nuevo mensaje del visitante.
     *
     * @return array{respond:bool, reason:string}
     */
    public static function shouldRespond(array $widget, array $convo): array
    {
        $mode = (string)($widget['ai_fallback_mode'] ?? 'off');
        if (($convo['status'] ?? 'open') === 'closed') return ['respond' => false, 'reason' => 'closed'];

        $takeover = (int)($convo['ai_takeover'] ?? 0) === 1;

        // mode=off bloquea solo el comportamiento automático.
        // El takeover explícito por un agente sigue funcionando.
        if ($mode === 'off' && !$takeover) return ['respond' => false, 'reason' => 'mode_off'];

        // Sin takeover explícito, una escalación previa bloquea respuestas IA.
        if (!$takeover && !empty($convo['ai_escalated_at'])) {
            return ['respond' => false, 'reason' => 'escalated'];
        }

        $maxTurns = (int)($widget['ai_max_turns'] ?? 6);
        if ($maxTurns > 0 && (int)($convo['ai_turns'] ?? 0) >= $maxTurns) {
            return ['respond' => false, 'reason' => 'max_turns'];
        }

        // Takeover explícito por un agente: la IA responde aunque la convo esté asignada
        // y aunque el widget esté en mode=off.
        if ($takeover) return ['respond' => true, 'reason' => 'takeover'];

        if ($mode === 'no_agent') {
            if (!empty($convo['assigned_to'])) return ['respond' => false, 'reason' => 'assigned_to_human'];
        }

        return ['respond' => true, 'reason' => 'ok'];
    }

    /**
     * Punto de entrada principal: corre el ciclo completo (build context → call → tools → persist).
     *
     * @return array{ok:bool, action:string, error?:string, message?:string, ticket_id?:int}
     */
    public static function respond(Tenant $tenant, array $widget, array $convo): array
    {
        $db = Application::get()->db;

        $guard = self::guard($tenant);
        if (!$guard['ok']) {
            // Sin cuota / sin asignación → escalamos inmediatamente.
            return self::escalateOnFailure($tenant, $widget, $convo, $guard['error'] ?? 'IA no disponible');
        }

        $cfg    = $guard['cfg'];
        $global = $guard['global'];
        $apiKey = (string)$global['ai_api_key'];
        $model  = (string)($cfg['model'] ?: ($global['ai_default_model'] ?? self::DEFAULT_MODEL));

        // Reconstruimos el historial de la conversación.
        $history = self::loadHistory($db, (int)$convo['id']);
        if (empty($history)) {
            return ['ok' => false, 'action' => 'noop', 'error' => 'empty_history'];
        }

        // System prompt + KB cacheable (estable durante la conversación).
        $systemBlocks = self::buildSystemBlocks($tenant, $widget, $convo);
        $tools = self::tools();

        // Bucle tool-use (límite duro para evitar loops).
        $messages = self::historyToMessages($history);
        $totalIn = 0; $totalOut = 0;
        $finalText = '';
        $createdTicketId = null;
        $escalateReason = null;
        $start = microtime(true);

        for ($i = 0; $i < self::MAX_TOOL_ITERATIONS; $i++) {
            $resp = self::callAnthropic($apiKey, $model, [
                'system'     => $systemBlocks,
                'messages'   => $messages,
                'tools'      => $tools,
                'max_tokens' => self::MAX_OUTPUT_TOKENS,
            ]);

            if (!$resp['ok']) {
                self::logCompletion($db, $tenant, $convo, $widget, 'chat_ai_error', '', null, 0, 0, (int)((microtime(true)-$start)*1000), $resp['error'] ?? '');
                return self::escalateOnFailure($tenant, $widget, $convo, $resp['error'] ?? 'API error');
            }

            $totalIn  += (int)($resp['tokens_in']  ?? 0);
            $totalOut += (int)($resp['tokens_out'] ?? 0);

            $stopReason  = (string)($resp['stop_reason'] ?? '');
            $contentBlocks = $resp['content_blocks'] ?? [];
            $textCollected = '';
            $toolCalls = [];

            foreach ($contentBlocks as $block) {
                $type = $block['type'] ?? '';
                if ($type === 'text') {
                    $textCollected .= (string)($block['text'] ?? '');
                } elseif ($type === 'tool_use') {
                    $toolCalls[] = $block;
                }
            }

            // Si no hay tool calls, el modelo terminó con texto.
            if ($stopReason !== 'tool_use' || empty($toolCalls)) {
                $finalText = trim($textCollected);
                break;
            }

            // Sino, ejecutamos cada tool y devolvemos los resultados al modelo.
            $messages[] = ['role' => 'assistant', 'content' => $contentBlocks];
            $toolResults = [];
            foreach ($toolCalls as $tc) {
                $name = (string)($tc['name'] ?? '');
                $tid  = (string)($tc['id']   ?? '');
                $input = is_array($tc['input'] ?? null) ? $tc['input'] : [];

                if ($name === 'create_ticket') {
                    $r = self::executeCreateTicket($db, $tenant, $widget, $convo, $input);
                    if ($r['ok']) $createdTicketId = $r['ticket_id'];
                    $toolResults[] = ['type' => 'tool_result', 'tool_use_id' => $tid, 'content' => json_encode($r)];
                } elseif ($name === 'escalate_to_human') {
                    $escalateReason = (string)($input['reason'] ?? '');
                    $toolResults[] = ['type' => 'tool_result', 'tool_use_id' => $tid, 'content' => json_encode(['ok' => true])];
                } else {
                    $toolResults[] = ['type' => 'tool_result', 'tool_use_id' => $tid, 'content' => json_encode(['ok' => false, 'error' => 'unknown tool']), 'is_error' => true];
                }
            }
            $messages[] = ['role' => 'user', 'content' => $toolResults];
            // Continuamos el loop para que el modelo cierre con un texto final.
        }

        $duration = (int)((microtime(true) - $start) * 1000);

        // Si el bucle se agotó sin texto final, generamos uno mínimo.
        if ($finalText === '') {
            $finalText = $escalateReason
                ? 'Voy a derivarte con un agente humano para que pueda ayudarte mejor.'
                : 'Disculpá, tuve un problema procesando tu consulta. Te derivo con un agente.';
        }

        // Persistimos: cuota + completion log + mensaje IA + counters de la convo.
        self::deductQuota($db, $tenant, $totalIn, $totalOut);
        self::logCompletion($db, $tenant, $convo, $widget, 'chat_ai', '', $finalText, $totalIn, $totalOut, $duration, null);

        $msgId = $db->insert('chat_messages', [
            'tenant_id'       => $tenant->id,
            'conversation_id' => (int)$convo['id'],
            'sender_type'     => 'agent',
            'user_id'         => null,
            'is_ai'           => 1,
            'body'            => $finalText,
        ]);

        try {
            $db->run(
                'UPDATE chat_conversations
                    SET ai_handled = 1,
                        ai_turns = ai_turns + 1,
                        ai_tokens_in = ai_tokens_in + ?,
                        ai_tokens_out = ai_tokens_out + ?,
                        last_message_at = ?
                  WHERE id = ?',
                [$totalIn, $totalOut, date('Y-m-d H:i:s'), (int)$convo['id']]
            );
        } catch (\Throwable $e) { /* no bloquea */ }

        // Si la IA decidió escalar, marcamos la convo y notificamos al visitante.
        if ($escalateReason !== null) {
            self::markEscalated($db, (int)$convo['id'], $escalateReason);
            self::audit($db, $tenant, $convo, 'chat.ai.escalated', ['reason' => $escalateReason, 'ticket_id' => $createdTicketId]);
            return ['ok' => true, 'action' => 'escalated', 'message' => $finalText, 'ticket_id' => $createdTicketId];
        }

        if ($createdTicketId) {
            self::audit($db, $tenant, $convo, 'chat.ai.ticket_created', ['ticket_id' => $createdTicketId]);
        }
        self::audit($db, $tenant, $convo, 'chat.ai.replied', ['msg_id' => $msgId, 'tokens_in' => $totalIn, 'tokens_out' => $totalOut]);

        return ['ok' => true, 'action' => 'replied', 'message' => $finalText, 'ticket_id' => $createdTicketId];
    }

    /* ───────────────────────── guard / quota ───────────────────────── */

    /** @return array{ok:bool,error?:string,cfg?:array,global?:array} */
    public static function guard(Tenant $tenant): array
    {
        $db = Application::get()->db;

        if (!Plan::has($tenant, 'ai_assist')) {
            return ['ok' => false, 'error' => 'IA disponible solo en plan Enterprise'];
        }
        $cfg = $db->one('SELECT * FROM ai_settings WHERE tenant_id = ?', [$tenant->id]);
        if (!$cfg || !(int)$cfg['is_assigned']) {
            return ['ok' => false, 'error' => 'IA no está asignada a este workspace'];
        }
        if (!(int)$cfg['is_enabled']) {
            return ['ok' => false, 'error' => 'IA pausada por el workspace'];
        }
        $global = self::loadGlobal($db);
        if (($global['ai_global_enabled'] ?? '0') !== '1') {
            return ['ok' => false, 'error' => 'IA globalmente deshabilitada'];
        }
        if (empty($global['ai_api_key'])) {
            return ['ok' => false, 'error' => 'API key no configurada'];
        }
        if ((int)$cfg['monthly_quota'] > 0 && (int)$cfg['used_this_month'] >= (int)$cfg['monthly_quota']) {
            return ['ok' => false, 'error' => 'Cuota mensual de requests alcanzada'];
        }
        $tokenQuota = (int)($cfg['token_quota_monthly'] ?? 0);
        if ($tokenQuota > 0) {
            $used = (int)($cfg['tokens_in_this_month'] ?? 0) + (int)($cfg['tokens_out_this_month'] ?? 0);
            if ($used >= $tokenQuota) return ['ok' => false, 'error' => 'Cuota mensual de tokens alcanzada'];
        }
        return ['ok' => true, 'cfg' => $cfg, 'global' => $global];
    }

    protected static function deductQuota(Database $db, Tenant $tenant, int $tin, int $tout): void
    {
        try {
            $db->run(
                'UPDATE ai_settings
                    SET used_this_month       = used_this_month + 1,
                        tokens_in_this_month  = tokens_in_this_month + ?,
                        tokens_out_this_month = tokens_out_this_month + ?
                  WHERE tenant_id = ?',
                [$tin, $tout, $tenant->id]
            );
        } catch (\Throwable $e) {
            try { $db->run('UPDATE ai_settings SET used_this_month = used_this_month + 1 WHERE tenant_id = ?', [$tenant->id]); }
            catch (\Throwable $ee) { /* no bloquea */ }
        }
    }

    protected static function loadGlobal(Database $db): array
    {
        $rows = $db->all("SELECT `key`,`value` FROM saas_settings WHERE `key` LIKE 'ai_%'");
        $map = [];
        foreach ($rows as $r) $map[$r['key']] = $r['value'];
        return $map + [
            'ai_provider' => 'anthropic',
            'ai_api_key' => '',
            'ai_default_model' => self::DEFAULT_MODEL,
            'ai_global_enabled' => '0',
        ];
    }

    /* ───────────────────────── prompt building ───────────────────────── */

    /**
     * System prompt como array de bloques con cache_control en el bloque pesado (KB).
     * Eso permite hits de cache cross-conversación durante 5min.
     */
    protected static function buildSystemBlocks(Tenant $tenant, array $widget, array $convo): array
    {
        $persona = trim((string)($widget['ai_persona_name'] ?? 'Asistente')) ?: 'Asistente';
        $tenantName = $tenant->name ?? 'la empresa';
        $extra = trim((string)($widget['ai_system_prompt'] ?? ''));

        $base = "Sos $persona, un asistente de soporte de $tenantName que atiende el chat en vivo.\n\n";
        $base .= "Reglas obligatorias:\n";
        $base .= "1. Respondé SOLO con base en la información de contexto que recibís más abajo. Si la respuesta no está, NO inventes; usá la herramienta escalate_to_human o create_ticket.\n";
        $base .= "2. Tono: cordial, breve, profesional. Máximo 3 párrafos cortos. Sin emojis salvo que el usuario los use.\n";
        $base .= "3. Identificate como asistente IA si el usuario pregunta. No simules ser humano.\n";
        $base .= "4. Si el usuario pide hablar con una persona, está enojado, reporta un bug serio o necesita acción concreta (cambios en su cuenta, reembolso, datos sensibles), llamá a escalate_to_human.\n";
        $base .= "5. Si la consulta amerita seguimiento (incidencia, pedido formal, algo que el equipo debe trabajar offline), llamá a create_ticket con un subject claro y priority adecuada.\n";
        $base .= "6. No prometas tiempos de respuesta concretos. No hagas promesas de descuentos, refunds ni términos comerciales.\n";
        $base .= "7. Idioma: respondé en el mismo idioma del usuario (default español rioplatense).\n";

        if ($extra !== '') {
            $base .= "\nInstrucciones específicas del workspace:\n" . $extra . "\n";
        }

        $blocks = [
            ['type' => 'text', 'text' => $base],
        ];

        // Bloque KB cacheable (estable durante 5min de actividad).
        if ((int)($widget['ai_use_kb'] ?? 1) === 1) {
            $kb = self::loadKbContext($tenant->id);
            if ($kb !== '') {
                $blocks[] = [
                    'type' => 'text',
                    'text' => "=== BASE DE CONOCIMIENTO ($tenantName) ===\n\n$kb\n\n=== FIN KB ===",
                    'cache_control' => ['type' => 'ephemeral'],
                ];
            }
        }
        return $blocks;
    }

    /**
     * Carga la KB pública del tenant en un bloque de texto plano para grounding.
     * Trunca a ~30K chars como salvaguarda (≈ 7-8K tokens).
     */
    protected static function loadKbContext(int $tenantId): string
    {
        try {
            $rows = Application::get()->db->all(
                "SELECT title, excerpt, body FROM kb_articles
                 WHERE tenant_id = ? AND status = 'published' AND visibility = 'public'
                 ORDER BY updated_at DESC LIMIT 40",
                [$tenantId]
            );
        } catch (\Throwable $e) { return ''; }

        if (!$rows) return '';
        $out = '';
        $budget = 30000;
        foreach ($rows as $r) {
            $title = trim((string)$r['title']);
            $excerpt = trim((string)($r['excerpt'] ?? ''));
            $body = trim(strip_tags((string)($r['body'] ?? '')));
            if ($body === '' && $excerpt === '') continue;
            $chunk = "## $title\n";
            if ($excerpt !== '') $chunk .= "$excerpt\n";
            if ($body !== '') $chunk .= mb_substr($body, 0, 1500) . "\n";
            $chunk .= "\n---\n\n";
            if (strlen($out) + strlen($chunk) > $budget) break;
            $out .= $chunk;
        }
        return $out;
    }

    protected static function loadHistory(Database $db, int $convoId): array
    {
        return $db->all(
            'SELECT id, sender_type, body, is_ai, created_at FROM chat_messages WHERE conversation_id = ? ORDER BY id ASC LIMIT 60',
            [$convoId]
        );
    }

    /**
     * Convierte el historial a la lista de messages que espera Anthropic.
     * - sender_type=visitor          → role=user
     * - sender_type=agent            → role=assistant
     * - sender_type=system           → role=user con prefijo [sistema] (las API messages no aceptan role=system aquí)
     * Combina mensajes consecutivos del mismo rol.
     */
    protected static function historyToMessages(array $history): array
    {
        $messages = [];
        foreach ($history as $m) {
            $sender = (string)$m['sender_type'];
            $body = (string)$m['body'];
            if ($sender === 'visitor') {
                $role = 'user'; $content = $body;
            } elseif ($sender === 'agent') {
                $role = 'assistant'; $content = $body;
            } else {
                $role = 'user'; $content = "[sistema] $body";
            }

            $last = end($messages);
            if ($last && $last['role'] === $role && is_string($last['content'])) {
                $messages[count($messages)-1]['content'] .= "\n\n" . $content;
            } else {
                $messages[] = ['role' => $role, 'content' => $content];
            }
        }
        // Anthropic exige que el primer mensaje sea role=user.
        while (!empty($messages) && $messages[0]['role'] !== 'user') array_shift($messages);
        // Y que el último sea user (si la conversación termina en assistant, agregamos un placeholder).
        if (!empty($messages) && end($messages)['role'] !== 'user') {
            $messages[] = ['role' => 'user', 'content' => '(continúa la conversación)'];
        }
        return $messages;
    }

    /* ───────────────────────── tools ───────────────────────── */

    protected static function tools(): array
    {
        return [
            [
                'name' => 'create_ticket',
                'description' => 'Crea un ticket de soporte cuando el usuario reporta un problema, pide algo que requiere acción del equipo, o necesita seguimiento offline. Usar SOLO cuando hay suficiente contexto.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'subject' => [
                            'type' => 'string',
                            'description' => 'Título corto y específico del ticket (máx 120 chars). Ej: "Error 500 al subir factura PDF".',
                        ],
                        'priority' => [
                            'type' => 'string',
                            'enum' => ['low','medium','high','urgent'],
                            'description' => 'Prioridad: urgent solo si bloquea operación crítica; high si bloquea feature importante; medium por default; low si es duda menor.',
                        ],
                        'summary' => [
                            'type' => 'string',
                            'description' => 'Resumen de 2-4 oraciones del problema y los pasos ya intentados, redactado para el agente humano.',
                        ],
                    ],
                    'required' => ['subject', 'priority', 'summary'],
                ],
            ],
            [
                'name' => 'escalate_to_human',
                'description' => 'Transfiere la conversación a un agente humano cuando el usuario lo pide explícitamente, está frustrado, o la consulta excede lo que la IA puede resolver con la KB disponible.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'reason' => [
                            'type' => 'string',
                            'description' => 'Motivo breve (máx 200 chars) de por qué se escala. Ej: "Usuario pide hablar con persona" / "Consulta sobre billing fuera de KB".',
                        ],
                    ],
                    'required' => ['reason'],
                ],
            ],
        ];
    }

    /** @return array{ok:bool, ticket_id?:int, code?:string, error?:string} */
    protected static function executeCreateTicket(Database $db, Tenant $tenant, array $widget, array $convo, array $input): array
    {
        $subject  = mb_substr(trim((string)($input['subject'] ?? '')), 0, 120);
        $priority = (string)($input['priority'] ?? 'medium');
        if (!in_array($priority, ['low','medium','high','urgent'], true)) $priority = 'medium';
        $summary  = trim((string)($input['summary'] ?? ''));
        if ($subject === '') return ['ok' => false, 'error' => 'subject requerido'];

        // Si la conversación ya tiene ticket, no creamos otro.
        if (!empty($convo['ticket_id'])) {
            $existing = $db->one('SELECT id, code FROM tickets WHERE id = ? AND tenant_id = ?', [(int)$convo['ticket_id'], $tenant->id]);
            if ($existing) return ['ok' => true, 'ticket_id' => (int)$existing['id'], 'code' => (string)$existing['code']];
        }

        // Armamos descripción con el historial completo + resumen IA.
        $msgs = $db->all('SELECT sender_type, body, is_ai, created_at FROM chat_messages WHERE conversation_id = ? ORDER BY created_at ASC', [(int)$convo['id']]);
        $description = "Ticket creado automáticamente por IA desde Live Chat.\n\n";
        if ($summary !== '') $description .= "Resumen IA:\n$summary\n\n";
        $description .= "Conversación:\n";
        foreach ($msgs as $m) {
            if ($m['sender_type'] === 'visitor') $who = 'Cliente';
            elseif ((int)($m['is_ai'] ?? 0) === 1) $who = 'IA';
            else $who = ucfirst((string)$m['sender_type']);
            $description .= "[$who · " . $m['created_at'] . "] " . $m['body'] . "\n\n";
        }

        try {
            $code = 'TKT-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
            $token = bin2hex(random_bytes(16));
            $ticketId = $db->insert('tickets', [
                'tenant_id'        => $tenant->id,
                'code'             => $code,
                'subject'          => $subject,
                'description'      => $description,
                'priority'         => $priority,
                'status'           => 'open',
                'channel'          => 'chat',
                'requester_name'   => $convo['visitor_name'] ?: 'Visitante',
                'requester_email'  => $convo['visitor_email'] ?: null,
                'public_token'     => $token,
            ]);
            $db->update('chat_conversations', ['ticket_id' => $ticketId], 'id = :id', ['id' => (int)$convo['id']]);
            return ['ok' => true, 'ticket_id' => (int)$ticketId, 'code' => $code];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'no se pudo crear ticket: ' . $e->getMessage()];
        }
    }

    /* ───────────────────────── escalation / fallback ───────────────────────── */

    /**
     * Escala la conversación cuando la IA no puede operar (cuota, error API, etc).
     * Crea un ticket mínimo con el contexto y avisa al visitante.
     */
    protected static function escalateOnFailure(Tenant $tenant, array $widget, array $convo, string $reason): array
    {
        $db = Application::get()->db;
        $ticketId = null;

        if (empty($convo['ticket_id'])) {
            $r = self::executeCreateTicket($db, $tenant, $widget, $convo, [
                'subject'  => 'Conversación de chat sin agente disponible',
                'priority' => 'medium',
                'summary'  => "El visitante escribió en el chat pero no hay agentes online y la IA no pudo responder ($reason). Requiere atención humana.",
            ]);
            if ($r['ok']) $ticketId = $r['ticket_id'];
        } else {
            $ticketId = (int)$convo['ticket_id'];
        }

        $awayMsg = trim((string)($widget['away_message'] ?? '')) ?: 'En este momento no hay agentes disponibles. Creamos un ticket con tu consulta y te respondemos por email lo antes posible.';
        try {
            $db->insert('chat_messages', [
                'tenant_id'       => $tenant->id,
                'conversation_id' => (int)$convo['id'],
                'sender_type'     => 'system',
                'user_id'         => null,
                'is_ai'           => 0,
                'body'            => $awayMsg,
            ]);
        } catch (\Throwable $e) { /* swallow */ }

        self::markEscalated($db, (int)$convo['id'], $reason);
        self::audit($db, $tenant, $convo, 'chat.ai.fallback_escalation', ['reason' => $reason, 'ticket_id' => $ticketId]);

        return ['ok' => true, 'action' => 'escalated', 'message' => $awayMsg, 'ticket_id' => $ticketId];
    }

    protected static function markEscalated(Database $db, int $convoId, string $reason): void
    {
        try {
            $db->run(
                'UPDATE chat_conversations
                    SET ai_escalated_at = ?, last_message_at = ?
                  WHERE id = ? AND ai_escalated_at IS NULL',
                [date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $convoId]
            );
        } catch (\Throwable $e) { /* no bloquea */ }
    }

    /* ───────────────────────── HTTP / Anthropic ───────────────────────── */

    /**
     * Llama a Anthropic con tools + prompt caching.
     * @param array{system:array,messages:array,tools:array,max_tokens:int} $payload
     * @return array{ok:bool, content_blocks?:array, stop_reason?:string, tokens_in?:int, tokens_out?:int, error?:string}
     */
    protected static function callAnthropic(string $apiKey, string $model, array $payload): array
    {
        $body = [
            'model'      => $model,
            'max_tokens' => (int)$payload['max_tokens'],
            'system'     => $payload['system'],
            'messages'   => $payload['messages'],
            'tools'      => $payload['tools'],
        ];

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 45,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($body),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01',
            ],
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($resp === false) return ['ok' => false, 'error' => 'curl: ' . $err];
        $j = json_decode($resp, true);
        if ($code >= 400) return ['ok' => false, 'error' => $j['error']['message'] ?? "HTTP $code"];

        $usage = $j['usage'] ?? [];
        $tokensIn = (int)($usage['input_tokens'] ?? 0)
            + (int)($usage['cache_creation_input_tokens'] ?? 0)
            + (int)($usage['cache_read_input_tokens'] ?? 0);

        return [
            'ok'             => true,
            'content_blocks' => $j['content'] ?? [],
            'stop_reason'    => (string)($j['stop_reason'] ?? ''),
            'tokens_in'      => $tokensIn,
            'tokens_out'     => (int)($usage['output_tokens'] ?? 0),
        ];
    }

    /* ───────────────────────── logging ───────────────────────── */

    protected static function logCompletion(
        Database $db, Tenant $tenant, array $convo, array $widget,
        string $action, string $input, ?string $output,
        int $tin, int $tout, int $duration, ?string $error
    ): void {
        try {
            $db->insert('ai_completions', [
                'tenant_id'   => $tenant->id,
                'user_id'     => null,
                'ticket_id'   => $convo['ticket_id'] ?? null,
                'action'      => substr($action, 0, 40),
                'input_text'  => mb_substr($input, 0, 5000),
                'output_text' => $output,
                'tokens_in'   => $tin,
                'tokens_out'  => $tout,
                'duration_ms' => $duration,
                'status'      => $error ? 'error' : 'ok',
                'error'       => $error ? substr($error, 0, 500) : null,
            ]);
        } catch (\Throwable $e) { /* tabla puede tener schema distinto */ }
    }

    protected static function audit(Database $db, Tenant $tenant, array $convo, string $action, array $meta): void
    {
        try {
            $db->insert('audit_logs', [
                'tenant_id' => $tenant->id,
                'user_id'   => null,
                'action'    => substr($action, 0, 100),
                'entity'    => 'chat_conversation',
                'entity_id' => (int)$convo['id'],
                'meta'      => json_encode($meta),
                'ip'        => $_SERVER['REMOTE_ADDR'] ?? null,
                'ua'        => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            ]);
        } catch (\Throwable $e) { /* swallow */ }
    }
}
