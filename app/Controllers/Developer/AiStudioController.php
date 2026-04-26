<?php
namespace App\Controllers\Developer;

class AiStudioController extends DeveloperController
{
    public function index(): void
    {
        $this->requireDeveloper();
        $devId = $this->devAuth->id();
        $apps = $this->db->all('SELECT id, name, slug FROM dev_apps WHERE developer_id=? ORDER BY id DESC', [$devId]);

        $this->render('developers/ai/index', [
            'title' => 'AI Studio',
            'pageHeading' => 'AI Studio · Integra Kydesk con IA',
            'apps' => $apps,
        ]);
    }

    public function chat(): void
    {
        $this->requireDeveloper();
        $devId = $this->devAuth->id();
        $tokens = $this->db->all(
            "SELECT t.id, t.name, t.token_preview, a.name AS app_name, a.tenant_id
             FROM dev_api_tokens t JOIN dev_apps a ON a.id=t.app_id
             WHERE t.developer_id=? AND t.revoked_at IS NULL ORDER BY t.id DESC",
            [$devId]
        );
        $this->render('developers/ai/chat', [
            'title' => 'AI Chat',
            'pageHeading' => 'AI Chat · Habla con tu workspace',
            'tokens' => $tokens,
        ]);
    }

    /**
     * Returns a compact, AI-optimized API spec ready to paste into a system prompt.
     * Format: markdown digest of the API for agents.
     */
    public function digest(): void
    {
        $this->requireDeveloper();
        $base = rtrim($this->app->config['app']['url'], '/');
        $apiBase = $base . '/api/v1';

        header('Content-Type: text/markdown; charset=utf-8');
        echo "# Kydesk Helpdesk API — AI Digest\n\n";
        echo "Base URL: `$apiBase`\n";
        echo "Auth: `Authorization: Bearer kyd_*`\n";
        echo "Format: JSON. Pagination via `?page=N&per_page=K` (max 100). Sort with `?sort=field` or `?sort=-field`.\n\n";
        echo "## Resources\n\n";
        $resources = [
            'tickets' => 'CRUD + comments + escalate/assign/batch. Fields: subject, description, status[open|in_progress|on_hold|resolved|closed], priority[low|medium|high|urgent], channel, requester_email, company_id, category_id, assigned_to, tags',
            'companies' => 'CRUD. Fields: name, industry, tier[standard|premium|enterprise], website, phone, address',
            'categories' => 'CRUD. Fields: name, color, icon',
            'users' => 'CRUD. Fields: name, email, password (write-only), is_technician, is_active, role_id',
            'kb/articles' => 'CRUD. Fields: title, slug, excerpt, body, status[draft|published], visibility[internal|public], category_id',
            'sla' => 'CRUD. Fields: name, priority, response_minutes, resolve_minutes, active',
            'automations' => 'CRUD. Fields: name, trigger_event, conditions (json), actions (json), active',
            'assets' => 'CRUD. Fields: name, type, serial, status[active|maintenance|retired|lost], company_id, assigned_to',
        ];
        foreach ($resources as $r => $desc) {
            echo "- **$r** — $desc\n";
        }
        echo "\n## Common patterns\n\n";
        echo "- Idempotency: send `Idempotency-Key` header on POSTs. Same key in 24h returns same response.\n";
        echo "- Expansion: `?expand=company,assignee,comments`\n";
        echo "- Field selection: `?fields=id,subject,status`\n";
        echo "- Search global: `GET /search?q=...`\n";
        echo "- Stats: `GET /stats` returns aggregated KPIs.\n";
        echo "- Errors: `{ error: { type, message, status, request_id } }`\n";
        echo "- Rate limit headers: `X-RateLimit-Limit`, `X-Quota-Used`, `X-Quota-Limit`, `Retry-After`.\n\n";
        echo "## Webhooks events\n\n";
        echo "ticket.created, ticket.updated, ticket.assigned, ticket.resolved, ticket.escalated, sla.breach, comment.created\n\n";
        echo "## Full OpenAPI spec\n\n";
        echo "$apiBase/openapi.json\n";
        exit;
    }

    /**
     * Generates a ready-to-paste system prompt for any LLM (Claude, GPT, etc.)
     * including an embedded compact API digest.
     */
    public function systemPrompt(): void
    {
        $this->requireDeveloper();
        $base = rtrim($this->app->config['app']['url'], '/');
        $apiBase = $base . '/api/v1';
        $appName = (string)($_GET['app_name'] ?? 'Mi App');
        $tone = (string)($_GET['tone'] ?? 'profesional');

        header('Content-Type: text/plain; charset=utf-8');
        echo <<<PROMPT
You are an expert assistant helping a developer build "{$appName}" on top of the Kydesk Helpdesk REST API.

## API basics
- Base URL: `{$apiBase}`
- Auth: every request needs header `Authorization: Bearer kyd_<token>`
- Format: JSON in/out. UTF-8.
- Pagination: `?page=N&per_page=K` (max 100). Response includes `meta.total`, `meta.has_more`, `links.next/prev/first/last`.
- Sort: `?sort=field` or `?sort=-field` for desc.
- Expansion: `?expand=relation1,relation2`. For tickets: `company`, `category`, `assignee`, `comments`.
- Field selection: `?fields=id,subject,status`.
- Idempotency: POSTs accept `Idempotency-Key` header. Same key within 24h returns the cached response.
- Rate limit: each plan has per-minute limit. Response headers: `X-RateLimit-Limit`, `X-Quota-Used`, `X-Quota-Limit`, `X-Quota-Pct`. On 429, respect `Retry-After`.
- Errors are `{ error: { type, message, status, request_id, ...extra } }`.

## Resources
**Tickets** (`/tickets`): the main resource. Fields: `subject` (required), `description`, `status` (open/in_progress/on_hold/resolved/closed), `priority` (low/medium/high/urgent), `channel`, `requester_email`, `company_id`, `category_id`, `assigned_to`, `tags`. Sub-routes: `/tickets/{id}/comments`, `/tickets/{id}/assign`, `/tickets/{id}/escalate`, `/tickets/batch`.

**Companies** (`/companies`): B2B clients. Fields: `name`, `industry`, `tier`, `website`.

**Categories** (`/categories`): ticket buckets. Fields: `name`, `color`, `icon`.

**Users** (`/users`): workspace agents. Fields: `name`, `email`, `password`, `is_technician`, `is_active`.

**KB articles** (`/kb/articles`): knowledge base. Fields: `title`, `body`, `status` (draft/published), `visibility` (internal/public), `category_id`.

**SLA** (`/sla`): policies. Fields: `name`, `priority`, `response_minutes`, `resolve_minutes`.

**Automations** (`/automations`): IFTTT-like rules. Fields: `name`, `trigger_event`, `conditions` (json), `actions` (json).

**Assets** (`/assets`): inventory. Fields: `name`, `type`, `serial`, `status`, `company_id`, `assigned_to`.

## Other endpoints
- `GET /me` — token identity (developer + app + tenant)
- `GET /health` — public health (no auth)
- `GET /stats` — aggregated workspace stats
- `GET /search?q=...` — global search across resources
- `GET /openapi.json` — full OpenAPI 3.1 spec

## Webhooks
Events: `ticket.created`, `ticket.updated`, `ticket.assigned`, `ticket.resolved`, `ticket.escalated`, `sla.breach`, `comment.created`.
Each delivery is signed with HMAC-SHA256 in `X-Kydesk-Signature` header. Verify before processing.

## Conventions when generating code for the developer
- Always use the `Bearer` scheme. Read the token from an env variable (e.g. `KYDESK_TOKEN`).
- Use `Idempotency-Key` for any POST that creates a resource.
- Honor `Retry-After` on 429.
- Validate user input before sending (especially email, enums).
- Prefer `PATCH` for updates (partial). Don't send unchanged fields.
- For lists, always paginate or filter to avoid loading too much.
- Match the developer's stack and tone ({$tone}). Use idiomatic code in their language.

## Source of truth
The full spec lives at `{$apiBase}/openapi.json`. Always reach there if uncertain about a field.
PROMPT;
        exit;
    }

    /**
     * MCP server configuration for Claude Desktop, Continue, Cline, etc.
     */
    public function mcpConfig(): void
    {
        $this->requireDeveloper();
        $base = rtrim($this->app->config['app']['url'], '/');
        $apiBase = $base . '/api/v1';

        // The developer needs an active token to use this — we don't embed real tokens
        $config = [
            'mcpServers' => [
                'kydesk' => [
                    'command' => 'npx',
                    'args' => ['-y', '@modelcontextprotocol/server-fetch', $apiBase],
                    'env' => [
                        'KYDESK_TOKEN' => '${KYDESK_TOKEN}',
                        'KYDESK_BASE_URL' => $apiBase,
                    ],
                    'description' => 'Kydesk Helpdesk API. Read tickets, create comments, update statuses. Set KYDESK_TOKEN env var with your Bearer token.',
                ],
            ],
        ];
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="claude_desktop_config.json"');
        echo json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Returns a `.cursorrules` / `claude.md` style file the developer drops into their repo
     * so AI editors (Cursor, Continue, Claude Code, Copilot) know the API conventions.
     */
    public function cursorRules(): void
    {
        $this->requireDeveloper();
        $base = rtrim($this->app->config['app']['url'], '/');
        $apiBase = $base . '/api/v1';
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename=".cursorrules"');
        echo <<<RULES
# Kydesk Helpdesk API — AI editor rules

This project integrates with the Kydesk Helpdesk REST API. When generating code that interacts with Kydesk:

## Auth
- Read the token from environment variable `KYDESK_TOKEN`.
- Include header `Authorization: Bearer \${KYDESK_TOKEN}` on every request.
- Never log or commit tokens. Treat them like secrets.

## Base URL
- Use `{$apiBase}` as the base for all calls.
- Do NOT prepend or append slashes inconsistently.

## Request shape
- All POST/PATCH bodies are JSON. Set `Content-Type: application/json`.
- Use `PATCH` for partial updates, not `PUT`.
- Use `Idempotency-Key: <uuid>` on every POST that creates a resource.

## Pagination
- Always paginate list endpoints. Default `?page=1&per_page=25`.
- Stop iterating when `meta.has_more` is false OR when `links.next` is null.

## Errors
- Errors come in `{ error: { type, message, status, request_id } }` format.
- On 429 with `Retry-After`, sleep and retry. Use exponential backoff for other 5xx.
- On 401, refresh credentials, never silently swallow.

## Rate limiting
- Read `X-RateLimit-Limit`, `X-Quota-Used`, `X-Quota-Limit`, `X-Quota-Pct` headers.
- If `X-Quota-Pct >= 80`, surface a warning to the developer/user.
- Pre-emptively throttle requests to stay under `X-RateLimit-Limit / 60` per second.

## Expansion / fields
- Use `?expand=` to fetch related resources in one round-trip when you'll need them.
- Use `?fields=id,...` to reduce response size when listing.

## Resource-specific tips
- Tickets `status` is one of: open, in_progress, on_hold, resolved, closed.
- Tickets `priority` is one of: low, medium, high, urgent.
- KB articles need `slug` (auto-generated if omitted) and `status` (draft/published).
- Automations `trigger_event` must be one of: ticket.created, ticket.updated, ticket.sla_breach, ticket.escalated, ticket.resolved.

## Webhooks
- Verify the `X-Kydesk-Signature` HMAC-SHA256 header on every incoming webhook.
- Always respond 2xx within 5s; queue the actual processing.
- Don't trust webhook payload data without validating it against your DB.

## When in doubt
- The OpenAPI spec at `{$apiBase}/openapi.json` is the source of truth.
- The official docs are at `{$base}/developers/docs`.
RULES;
        exit;
    }
}
