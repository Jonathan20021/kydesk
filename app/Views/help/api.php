<?php
$slug = $tenant->slug;
$baseUrl = rtrim($app->config['app']['url'] ?? '', '/');
$apiBase = $baseUrl . '/api/v1';

$endpoints = [
    'Meta' => [
        ['GET', '/api/v1', 'Información del API y lista de endpoints', false],
        ['GET', '/api/v1/me', 'Datos del token y tenant actuales', true],
    ],
    'Tickets' => [
        ['GET',    '/api/v1/tickets', 'Listar tickets (filtros: status, priority, assigned_to, q, limit, offset)', true],
        ['POST',   '/api/v1/tickets', 'Crear ticket', true],
        ['GET',    '/api/v1/tickets/{id}', 'Obtener un ticket', true],
        ['PATCH',  '/api/v1/tickets/{id}', 'Actualizar ticket', true],
        ['DELETE', '/api/v1/tickets/{id}', 'Eliminar ticket', true],
        ['GET',    '/api/v1/tickets/{id}/comments', 'Listar comentarios de un ticket', true],
        ['POST',   '/api/v1/tickets/{id}/comments', 'Agregar comentario', true],
    ],
    'Categorías' => [
        ['GET',  '/api/v1/categories', 'Listar categorías', true],
        ['POST', '/api/v1/categories', 'Crear categoría', true],
    ],
    'Empresas (clientes)' => [
        ['GET',  '/api/v1/companies', 'Listar empresas', true],
        ['POST', '/api/v1/companies', 'Crear empresa', true],
    ],
    'Usuarios y equipo' => [
        ['GET', '/api/v1/users', 'Listar usuarios del tenant', true],
    ],
    'Knowledge Base' => [
        ['GET', '/api/v1/kb/articles', 'Listar artículos publicados', true],
    ],
    'SLA y Automatizaciones' => [
        ['GET', '/api/v1/sla', 'Listar políticas SLA', true],
        ['GET', '/api/v1/automations', 'Listar automatizaciones', true],
    ],
    'Estadísticas' => [
        ['GET', '/api/v1/stats', 'Métricas agregadas del tenant', true],
    ],
];
?>

<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
    <div>
        <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">Documentación API</h1>
        <p class="text-[13px] text-ink-400">REST · JSON · Bearer token authentication</p>
    </div>
    <div class="flex items-center gap-2">
        <span class="badge badge-purple font-mono">v1</span>
        <a href="<?= $e($apiBase) ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm"><i class="lucide lucide-external-link text-[13px]"></i> Endpoint base</a>
    </div>
</div>

<?php if (!empty($newToken)): ?>
<div class="card card-pad" style="background:#fef3c7;border-color:#fbbf24">
    <div class="flex items-start gap-3">
        <i class="lucide lucide-key text-amber-700 text-[18px] mt-0.5"></i>
        <div class="flex-1 min-w-0">
            <div class="font-display font-bold text-[14px] text-amber-900">Tu nuevo token</div>
            <p class="text-[12.5px] text-amber-800 mt-1">Cópialo ahora — por seguridad no se mostrará completo nunca más.</p>
            <div class="mt-2 flex items-center gap-2">
                <input id="new-token" readonly value="<?= $e($newToken) ?>" class="input font-mono flex-1" style="background:white">
                <button onclick="(function(){const i=document.getElementById('new-token');navigator.clipboard.writeText(i.value);i.select();})()" class="btn btn-dark btn-sm"><i class="lucide lucide-copy text-[13px]"></i> Copiar</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Quickstart -->
<div class="card card-pad">
    <h2 class="font-display font-extrabold text-[18px] tracking-[-0.02em]"><i class="lucide lucide-rocket text-brand-600"></i> Quickstart</h2>
    <ol class="mt-4 space-y-4 text-[13px]">
        <li class="flex gap-3">
            <span class="w-6 h-6 rounded-full bg-brand-500 text-white text-[11px] font-bold grid place-items-center shrink-0">1</span>
            <div>
                <strong>Genera un token</strong> abajo en la sección "Tokens API". Asigna un nombre y los scopes (read / write).
            </div>
        </li>
        <li class="flex gap-3">
            <span class="w-6 h-6 rounded-full bg-brand-500 text-white text-[11px] font-bold grid place-items-center shrink-0">2</span>
            <div>
                <strong>Incluye el token</strong> en cada request en el header <code class="font-mono bg-[#f3f4f6] px-1.5 py-0.5 rounded">Authorization: Bearer kyd_xxx...</code>
            </div>
        </li>
        <li class="flex gap-3">
            <span class="w-6 h-6 rounded-full bg-brand-500 text-white text-[11px] font-bold grid place-items-center shrink-0">3</span>
            <div>
                <strong>Llama a los endpoints</strong> bajo <code class="font-mono bg-[#f3f4f6] px-1.5 py-0.5 rounded"><?= $e($apiBase) ?></code>. Todas las respuestas son JSON.
            </div>
        </li>
    </ol>

    <div class="mt-5 rounded-xl overflow-hidden border border-[#ececef]">
        <div class="px-3 py-2 bg-[#0f0f12] text-[11px] font-mono text-ink-300 flex items-center justify-between">
            <span>curl · ejemplo</span>
            <button onclick="navigator.clipboard.writeText(document.getElementById('curl-ex').innerText)" class="text-ink-300 hover:text-white text-[11px]"><i class="lucide lucide-copy text-[11px]"></i> Copiar</button>
        </div>
        <pre id="curl-ex" class="p-4 bg-[#16151b] text-emerald-300 text-[12px] font-mono overflow-x-auto"><code>curl -X GET "<?= $e($apiBase) ?>/tickets?status=open&limit=10" \
  -H "Authorization: Bearer kyd_TU_TOKEN_AQUI" \
  -H "Content-Type: application/json"</code></pre>
    </div>
</div>

<!-- Tokens management -->
<div class="card card-pad">
    <div class="flex items-center justify-between">
        <h2 class="font-display font-extrabold text-[18px] tracking-[-0.02em]"><i class="lucide lucide-key text-brand-600"></i> Tokens API</h2>
        <button onclick="document.getElementById('new-tok-form').classList.toggle('hidden')" class="btn btn-primary btn-sm"><i class="lucide lucide-plus"></i> Generar token</button>
    </div>

    <form id="new-tok-form" method="POST" action="<?= $url('/t/' . $slug . '/api-docs/tokens') ?>" class="hidden mt-4 grid grid-cols-1 md:grid-cols-3 gap-3 p-4 bg-[#f9fafb] rounded-2xl border border-[#ececef]">
        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
        <div>
            <label class="text-[11.5px] font-semibold text-ink-700">Nombre</label>
            <input name="name" required maxlength="120" class="input mt-1" placeholder="Ej: Integración Zapier">
        </div>
        <div>
            <label class="text-[11.5px] font-semibold text-ink-700">Scopes</label>
            <select name="scopes" class="input mt-1">
                <option value="read">read (solo lectura)</option>
                <option value="read,write" selected>read + write</option>
                <option value="*">* (acceso total)</option>
            </select>
        </div>
        <div class="flex items-end">
            <button class="btn btn-dark w-full"><i class="lucide lucide-key"></i> Crear token</button>
        </div>
    </form>

    <div class="mt-4 space-y-2">
        <?php if (empty($tokens)): ?>
            <div class="text-center py-8 text-[13px] text-ink-400">No tienes tokens. Genera uno arriba.</div>
        <?php else: foreach ($tokens as $tk): ?>
            <div class="flex flex-wrap items-center gap-3 p-3 rounded-xl border border-[#ececef] <?= $tk['revoked_at']?'opacity-60':'' ?>">
                <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-700 grid place-items-center"><i class="lucide lucide-key text-[14px]"></i></div>
                <div class="flex-1 min-w-[200px]">
                    <div class="font-display font-bold text-[14px]"><?= $e($tk['name']) ?> <?php if ($tk['revoked_at']): ?><span class="badge badge-rose ml-1">Revocado</span><?php endif; ?></div>
                    <div class="text-[11.5px] text-ink-400 font-mono"><?= $e($tk['token_preview']) ?> · scopes: <?= $e($tk['scopes']) ?></div>
                </div>
                <div class="text-[11.5px] text-ink-400">
                    <?php if ($tk['last_used_at']): ?>
                        Último uso: <?= date('d/m/Y H:i', strtotime($tk['last_used_at'])) ?>
                    <?php else: ?>
                        Nunca usado
                    <?php endif; ?>
                </div>
                <?php if (!$tk['revoked_at']): ?>
                    <form method="POST" action="<?= $url('/t/' . $slug . '/api-docs/tokens/' . $tk['id'] . '/revoke') ?>" onsubmit="return confirm('¿Revocar este token? Las apps que lo usen dejarán de funcionar.')">
                        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                        <button class="btn btn-outline btn-sm" style="color:#dc2626;border-color:#fecaca">Revocar</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<!-- Endpoints reference -->
<div class="card overflow-hidden">
    <div class="px-6 pt-5 pb-3 border-b border-[#ececef]">
        <h2 class="font-display font-extrabold text-[18px] tracking-[-0.02em]"><i class="lucide lucide-code-2 text-brand-600"></i> Endpoints</h2>
        <p class="text-[12.5px] text-ink-400 mt-1">Todas las rutas devuelven JSON. Errores siguen el formato <code class="font-mono">{"error":{"message","type","status"}}</code>.</p>
    </div>
    <div class="divide-y divide-[#ececef]">
        <?php foreach ($endpoints as $section => $items): ?>
            <div class="px-6 py-4">
                <div class="text-[10.5px] font-bold uppercase tracking-[0.16em] text-ink-400 mb-3"><?= $e($section) ?></div>
                <div class="space-y-2">
                    <?php foreach ($items as [$method, $path, $desc, $auth]):
                        $methodColors = ['GET'=>'#10b981','POST'=>'#7c5cff','PATCH'=>'#f59e0b','DELETE'=>'#ef4444'];
                        $mc = $methodColors[$method] ?? '#6b6b78';
                    ?>
                        <div class="flex flex-wrap items-center gap-3 p-2.5 rounded-lg hover:bg-[#f9fafb] transition">
                            <span class="font-mono text-[10.5px] font-bold px-2 py-0.5 rounded text-white shrink-0" style="background:<?= $mc ?>"><?= $method ?></span>
                            <code class="font-mono text-[12.5px] text-ink-900"><?= $e($path) ?></code>
                            <span class="text-[12px] text-ink-500 flex-1 min-w-[200px]"><?= $e($desc) ?></span>
                            <?php if ($auth): ?>
                                <span class="badge badge-amber text-[10px]"><i class="lucide lucide-lock text-[10px]"></i> Auth</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Examples -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="card card-pad">
        <h3 class="font-display font-bold text-[15px]"><i class="lucide lucide-plus-square text-brand-600"></i> Crear ticket</h3>
        <pre class="mt-3 p-3 bg-[#16151b] text-emerald-300 text-[11.5px] font-mono rounded-xl overflow-x-auto"><code>POST <?= $e($apiBase) ?>/tickets
Authorization: Bearer kyd_xxx
Content-Type: application/json

{
  "subject": "Impresora 3er piso offline",
  "description": "No imprime desde las 9am",
  "priority": "high",
  "category_id": 1,
  "requester_name": "María T.",
  "requester_email": "maria@empresa.com"
}</code></pre>
    </div>

    <div class="card card-pad">
        <h3 class="font-display font-bold text-[15px]"><i class="lucide lucide-list text-brand-600"></i> Listar con filtros</h3>
        <pre class="mt-3 p-3 bg-[#16151b] text-emerald-300 text-[11.5px] font-mono rounded-xl overflow-x-auto"><code>GET <?= $e($apiBase) ?>/tickets?status=open&priority=urgent&limit=20
Authorization: Bearer kyd_xxx

# Respuesta:
{
  "data": [...],
  "meta": { "total": 42, "limit": 20, "offset": 0 }
}</code></pre>
    </div>

    <div class="card card-pad">
        <h3 class="font-display font-bold text-[15px]"><i class="lucide lucide-message-square text-brand-600"></i> Agregar comentario</h3>
        <pre class="mt-3 p-3 bg-[#16151b] text-emerald-300 text-[11.5px] font-mono rounded-xl overflow-x-auto"><code>POST <?= $e($apiBase) ?>/tickets/123/comments
Authorization: Bearer kyd_xxx
Content-Type: application/json

{
  "body": "Investigando el problema",
  "is_internal": false
}</code></pre>
    </div>

    <div class="card card-pad">
        <h3 class="font-display font-bold text-[15px]"><i class="lucide lucide-bar-chart-3 text-brand-600"></i> Métricas</h3>
        <pre class="mt-3 p-3 bg-[#16151b] text-emerald-300 text-[11.5px] font-mono rounded-xl overflow-x-auto"><code>GET <?= $e($apiBase) ?>/stats
Authorization: Bearer kyd_xxx

{
  "data": {
    "tickets": { "total": 1240, "open": 87, ... },
    "sla": { "breached": 3 },
    "users": 12,
    "companies": 45
  }
}</code></pre>
    </div>
</div>

<!-- Errors -->
<div class="card card-pad">
    <h2 class="font-display font-extrabold text-[18px] tracking-[-0.02em]"><i class="lucide lucide-alert-triangle text-amber-600"></i> Códigos de error</h2>
    <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-2 text-[12.5px]">
        <?php foreach ([
            ['400','Request inválido','request_error'],
            ['401','Token ausente o inválido','unauthorized'],
            ['403','Token sin scope requerido','forbidden'],
            ['404','Recurso no encontrado','not_found'],
            ['422','Validación fallida','validation_error'],
            ['429','Rate limit excedido','rate_limit'],
            ['500','Error interno','server_error'],
        ] as [$c, $msg, $type]): ?>
            <div class="flex items-center gap-2 p-2 rounded-lg border border-[#ececef]">
                <span class="font-mono font-bold text-[12px] px-2 py-0.5 rounded bg-[#f3f4f6] text-ink-700"><?= $c ?></span>
                <span class="text-ink-700"><?= $msg ?></span>
                <code class="font-mono text-[11px] text-ink-400 ml-auto"><?= $type ?></code>
            </div>
        <?php endforeach; ?>
    </div>
</div>
