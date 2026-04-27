<?php
namespace App\Core;

/**
 * Registry central de proveedores de integración.
 * Cada proveedor define: slug, name, icon, color, category, description,
 * config (campos del form de configuración), event_support, handler.
 */
class IntegrationRegistry
{
    /**
     * Provee la lista de proveedores soportados.
     * @return array<string, array>
     */
    public static function all(): array
    {
        return [
            'slack' => [
                'slug' => 'slack',
                'name' => 'Slack',
                'icon' => 'slack',
                'color' => '#4A154B',
                'category' => 'chat',
                'short' => 'Notifica eventos a un canal de Slack',
                'description' => 'Envía mensajes a un canal de Slack cada vez que ocurra un evento en Kydesk. Usa un Incoming Webhook configurado en tu workspace.',
                'docs_url' => 'https://api.slack.com/messaging/webhooks',
                'config' => [
                    ['key' => 'webhook_url', 'label' => 'Webhook URL', 'type' => 'url', 'required' => true, 'placeholder' => 'https://hooks.slack.com/services/...', 'help' => 'Crea un Incoming Webhook en api.slack.com/apps'],
                    ['key' => 'channel', 'label' => 'Canal (opcional)', 'type' => 'text', 'required' => false, 'placeholder' => '#soporte', 'help' => 'Sobreescribe el canal del webhook'],
                    ['key' => 'username', 'label' => 'Bot username', 'type' => 'text', 'required' => false, 'placeholder' => 'Kydesk', 'default' => 'Kydesk'],
                ],
                'handler' => 'sendSlackLike',
            ],
            'discord' => [
                'slug' => 'discord',
                'name' => 'Discord',
                'icon' => 'message-square',
                'color' => '#5865F2',
                'category' => 'chat',
                'short' => 'Notifica eventos a un canal de Discord',
                'description' => 'Envía mensajes ricos (embeds) a un canal de Discord vía webhook.',
                'docs_url' => 'https://support.discord.com/hc/en-us/articles/228383668',
                'config' => [
                    ['key' => 'webhook_url', 'label' => 'Webhook URL', 'type' => 'url', 'required' => true, 'placeholder' => 'https://discord.com/api/webhooks/...', 'help' => 'En Discord: Server Settings → Integrations → Webhooks → New'],
                    ['key' => 'username', 'label' => 'Bot username', 'type' => 'text', 'required' => false, 'placeholder' => 'Kydesk', 'default' => 'Kydesk'],
                ],
                'handler' => 'sendDiscord',
            ],
            'telegram' => [
                'slug' => 'telegram',
                'name' => 'Telegram',
                'icon' => 'send',
                'color' => '#0088CC',
                'category' => 'chat',
                'short' => 'Notifica eventos a un chat de Telegram',
                'description' => 'Envía mensajes a un chat individual, grupo o canal de Telegram usando un bot.',
                'docs_url' => 'https://core.telegram.org/bots#how-do-i-create-a-bot',
                'config' => [
                    ['key' => 'bot_token', 'label' => 'Bot token', 'type' => 'password', 'required' => true, 'placeholder' => '123456:ABC-DEF...', 'help' => 'Obtén un bot de @BotFather'],
                    ['key' => 'chat_id', 'label' => 'Chat ID', 'type' => 'text', 'required' => true, 'placeholder' => '-1001234567890 o @canal', 'help' => 'ID numérico (grupo) o @username (canal)'],
                ],
                'handler' => 'sendTelegram',
            ],
            'teams' => [
                'slug' => 'teams',
                'name' => 'Microsoft Teams',
                'icon' => 'users-2',
                'color' => '#5059C9',
                'category' => 'chat',
                'short' => 'Notifica eventos a un canal de Teams',
                'description' => 'Envía MessageCards adaptativas a un canal de Microsoft Teams vía Incoming Webhook.',
                'docs_url' => 'https://learn.microsoft.com/en-us/microsoftteams/platform/webhooks-and-connectors/how-to/add-incoming-webhook',
                'config' => [
                    ['key' => 'webhook_url', 'label' => 'Incoming Webhook URL', 'type' => 'url', 'required' => true, 'placeholder' => 'https://outlook.office.com/webhook/...'],
                ],
                'handler' => 'sendTeams',
            ],
            'webhook' => [
                'slug' => 'webhook',
                'name' => 'Webhook genérico',
                'icon' => 'webhook',
                'color' => '#16151b',
                'category' => 'devops',
                'short' => 'Envía POST con JSON a cualquier URL',
                'description' => 'Webhook genérico HTTP POST con payload JSON. Soporta firma HMAC-SHA256 opcional.',
                'docs_url' => null,
                'config' => [
                    ['key' => 'url', 'label' => 'URL destino', 'type' => 'url', 'required' => true, 'placeholder' => 'https://api.tu-app.com/webhook'],
                    ['key' => 'secret', 'label' => 'Secret HMAC (opcional)', 'type' => 'password', 'required' => false, 'help' => 'Firma con SHA256 en header X-Kydesk-Signature'],
                    ['key' => 'method', 'label' => 'Método HTTP', 'type' => 'select', 'options' => ['POST'=>'POST','PUT'=>'PUT','PATCH'=>'PATCH'], 'default' => 'POST'],
                ],
                'handler' => 'sendGenericWebhook',
            ],
            'zapier' => [
                'slug' => 'zapier',
                'name' => 'Zapier',
                'icon' => 'zap',
                'color' => '#FF4A00',
                'category' => 'automation',
                'short' => 'Conecta con miles de apps vía Zapier',
                'description' => 'Envía eventos a un Zap Trigger configurado con "Webhooks by Zapier" → "Catch Hook".',
                'docs_url' => 'https://zapier.com/apps/webhook/integrations',
                'config' => [
                    ['key' => 'hook_url', 'label' => 'Catch Hook URL', 'type' => 'url', 'required' => true, 'placeholder' => 'https://hooks.zapier.com/hooks/catch/...'],
                ],
                'handler' => 'sendGenericWebhook',
            ],
            'n8n' => [
                'slug' => 'n8n',
                'name' => 'n8n',
                'icon' => 'workflow',
                'color' => '#EA4B71',
                'category' => 'automation',
                'short' => 'Workflows automáticos open-source',
                'description' => 'Conecta con un workflow de n8n usando un webhook node.',
                'docs_url' => 'https://docs.n8n.io/integrations/builtin/core-nodes/n8n-nodes-base.webhook/',
                'config' => [
                    ['key' => 'webhook_url', 'label' => 'Webhook URL del workflow', 'type' => 'url', 'required' => true, 'placeholder' => 'https://tu-n8n.com/webhook/...'],
                    ['key' => 'auth_header', 'label' => 'Header de auth (opcional)', 'type' => 'text', 'required' => false, 'placeholder' => 'Bearer xxxxx'],
                ],
                'handler' => 'sendGenericWebhook',
            ],
            'make' => [
                'slug' => 'make',
                'name' => 'Make (Integromat)',
                'icon' => 'cpu',
                'color' => '#6D00CC',
                'category' => 'automation',
                'short' => 'Automatización visual con Make',
                'description' => 'Dispara escenarios de Make (anteriormente Integromat) vía webhook.',
                'docs_url' => 'https://www.make.com/en/help/tools/webhooks',
                'config' => [
                    ['key' => 'webhook_url', 'label' => 'Webhook URL del escenario', 'type' => 'url', 'required' => true, 'placeholder' => 'https://hook.eu1.make.com/...'],
                ],
                'handler' => 'sendGenericWebhook',
            ],
            'email' => [
                'slug' => 'email',
                'name' => 'Email forwarding',
                'icon' => 'mail',
                'color' => '#0EA5E9',
                'category' => 'notify',
                'short' => 'Reenvía eventos a un email',
                'description' => 'Envía un email vía Resend cada vez que ocurra un evento. Útil para alertas de seguimiento.',
                'docs_url' => null,
                'config' => [
                    ['key' => 'to', 'label' => 'Email destino', 'type' => 'email', 'required' => true, 'placeholder' => 'alertas@tu-empresa.com'],
                    ['key' => 'subject_prefix', 'label' => 'Prefijo del asunto', 'type' => 'text', 'required' => false, 'placeholder' => '[Kydesk]', 'default' => '[Kydesk]'],
                ],
                'handler' => 'sendEmail',
            ],
            'pushover' => [
                'slug' => 'pushover',
                'name' => 'Pushover',
                'icon' => 'bell',
                'color' => '#249DF1',
                'category' => 'notify',
                'short' => 'Notificaciones push a tu móvil',
                'description' => 'Envía push notifications a tu teléfono usando la app Pushover.',
                'docs_url' => 'https://pushover.net/api',
                'config' => [
                    ['key' => 'api_token', 'label' => 'API token (app)', 'type' => 'password', 'required' => true],
                    ['key' => 'user_key', 'label' => 'User key', 'type' => 'password', 'required' => true],
                    ['key' => 'priority', 'label' => 'Prioridad', 'type' => 'select', 'options' => ['-1'=>'Silenciosa','0'=>'Normal','1'=>'Alta','2'=>'Emergencia'], 'default' => '0'],
                ],
                'handler' => 'sendPushover',
            ],
            'mattermost' => [
                'slug' => 'mattermost',
                'name' => 'Mattermost',
                'icon' => 'message-circle',
                'color' => '#0058CC',
                'category' => 'chat',
                'short' => 'Alternativa open-source a Slack',
                'description' => 'Envía mensajes a un canal de Mattermost usando Incoming Webhook (compatible con formato Slack).',
                'docs_url' => 'https://docs.mattermost.com/developer/webhooks-incoming.html',
                'config' => [
                    ['key' => 'webhook_url', 'label' => 'Webhook URL', 'type' => 'url', 'required' => true, 'placeholder' => 'https://tu-mattermost.com/hooks/...'],
                    ['key' => 'channel', 'label' => 'Canal (opcional)', 'type' => 'text', 'required' => false],
                    ['key' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => false, 'default' => 'Kydesk'],
                ],
                'handler' => 'sendSlackLike',
            ],
            'rocketchat' => [
                'slug' => 'rocketchat',
                'name' => 'Rocket.Chat',
                'icon' => 'rocket',
                'color' => '#F5455C',
                'category' => 'chat',
                'short' => 'Plataforma de chat enterprise',
                'description' => 'Envía mensajes a un canal de Rocket.Chat usando Incoming Webhook.',
                'docs_url' => 'https://docs.rocket.chat/use-rocket.chat/workspace-administration/integrations',
                'config' => [
                    ['key' => 'webhook_url', 'label' => 'Webhook URL', 'type' => 'url', 'required' => true],
                    ['key' => 'channel', 'label' => 'Canal (opcional)', 'type' => 'text', 'required' => false],
                ],
                'handler' => 'sendSlackLike',
            ],
        ];
    }

    public static function get(string $slug): ?array
    {
        return self::all()[$slug] ?? null;
    }

    public static function categories(): array
    {
        return [
            'chat'       => ['label' => 'Chat & mensajería',     'icon' => 'message-square'],
            'automation' => ['label' => 'Automatización',         'icon' => 'workflow'],
            'devops'     => ['label' => 'Desarrollo & DevOps',    'icon' => 'code-2'],
            'notify'     => ['label' => 'Notificaciones',         'icon' => 'bell'],
        ];
    }

    /**
     * Eventos disponibles que el usuario puede suscribir.
     */
    public static function availableEvents(): array
    {
        return [
            'ticket.created'   => 'Ticket creado',
            'ticket.updated'   => 'Ticket actualizado',
            'ticket.assigned'  => 'Ticket asignado',
            'ticket.resolved'  => 'Ticket resuelto',
            'ticket.escalated' => 'Ticket escalado',
            'ticket.deleted'   => 'Ticket eliminado',
            'comment.created'  => 'Comentario nuevo',
            'sla.breach'       => 'SLA vencido',
            'company.created'  => 'Empresa creada',
            'kb.published'     => 'Artículo KB publicado',
            'todo.created'     => 'Tarea creada',
            'todo.completed'   => 'Tarea completada',
        ];
    }
}
