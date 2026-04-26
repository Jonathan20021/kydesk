<?php $availableEvents = ['*','ticket.created','ticket.updated','ticket.assigned','ticket.resolved','ticket.escalated','sla.breach','comment.created']; ?>

<?php if (!empty($newSecret)): ?>
<div class="dev-card dev-card-pad" style="border-color:rgba(245,158,11,.40); background:rgba(245,158,11,.05)">
    <div class="flex items-center gap-2 mb-2"><i class="lucide lucide-alert-triangle text-amber-300"></i><span class="dev-pill dev-pill-amber">Secret recién creado · webhook #<?= (int)$newSecret['id'] ?></span></div>
    <p class="text-[13px] text-slate-300 mb-3">Cópialo ahora — no se mostrará otra vez. Úsalo para verificar el header <code class="text-amber-300 font-mono">X-Kydesk-Signature</code> (HMAC-SHA256).</p>
    <div class="dev-code flex items-center justify-between gap-3">
        <code id="newSecretValue" class="break-all"><?= $e($newSecret['secret']) ?></code>
        <button onclick="navigator.clipboard.writeText(document.getElementById('newSecretValue').textContent); this.textContent='✓ Copiado';" class="dev-btn dev-btn-soft text-[12px] flex-shrink-0">Copiar</button>
    </div>
</div>
<?php endif; ?>

<!-- Crear webhook -->
<div class="dev-card">
    <div class="dev-card-head">
        <div>
            <h2 class="font-display font-bold text-white text-[16px]">Crear webhook</h2>
            <p class="text-[12px] text-slate-400">Suscríbete a eventos. Cada delivery se firma con HMAC-SHA256.</p>
        </div>
    </div>
    <form method="POST" action="<?= $url('/developers/webhooks') ?>" class="p-5 grid sm:grid-cols-2 gap-4">
        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
        <div>
            <label class="dev-label">App</label>
            <select name="app_id" class="dev-input" required <?= empty($apps) ? 'disabled' : '' ?>>
                <?php if (empty($apps)): ?>
                    <option>Crea una app primero</option>
                <?php else: foreach ($apps as $a): ?>
                    <option value="<?= $a['id'] ?>"><?= $e($a['name']) ?></option>
                <?php endforeach; endif; ?>
            </select>
        </div>
        <div>
            <label class="dev-label">Nombre</label>
            <input type="text" name="name" required class="dev-input" placeholder="Ej: Slack notifier">
        </div>
        <div class="sm:col-span-2">
            <label class="dev-label">URL del endpoint</label>
            <input type="url" name="url" required class="dev-input" placeholder="https://miapp.com/webhooks/kydesk">
        </div>
        <div class="sm:col-span-2">
            <label class="dev-label">Eventos</label>
            <div class="grid sm:grid-cols-4 gap-2">
                <?php foreach ($availableEvents as $ev): ?>
                    <label class="ai-prompt-card !p-2 cursor-pointer flex items-center gap-2">
                        <input type="checkbox" name="events[]" value="<?= $ev ?>" <?= $ev === '*' ? 'checked' : '' ?>>
                        <span class="font-mono text-[11.5px]"><?= $ev ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="sm:col-span-2">
            <button type="submit" class="dev-btn dev-btn-primary" <?= empty($apps) ? 'disabled' : '' ?>><i class="lucide lucide-plus text-[13px]"></i> Crear webhook</button>
        </div>
    </form>
</div>

<!-- Lista de webhooks -->
<div class="dev-card">
    <div class="dev-card-head">
        <h2 class="font-display font-bold text-white text-[16px]">Tus webhooks</h2>
    </div>
    <?php if (empty($hooks)): ?>
        <div class="p-10 text-center">
            <div class="w-14 h-14 rounded-2xl mx-auto grid place-items-center mb-3" style="background:rgba(14,165,233,.12); border:1px solid rgba(56,189,248,.20); color:#7dd3fc"><i class="lucide lucide-webhook text-[20px]"></i></div>
            <div class="font-display font-bold text-white text-[16px] mb-1">Sin webhooks</div>
            <p class="text-[13px] text-slate-400">Crea uno para recibir eventos en tu endpoint.</p>
        </div>
    <?php else: ?>
        <div style="overflow-x:auto">
            <table class="dev-table">
                <thead><tr><th>Nombre</th><th>App</th><th>URL</th><th>Eventos</th><th>Último</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($hooks as $h): ?>
                        <tr>
                            <td class="font-display font-bold"><?= $e($h['name']) ?></td>
                            <td class="text-[12px]"><?= $e($h['app_name'] ?? '—') ?></td>
                            <td class="font-mono text-[11.5px] truncate max-w-[300px]"><?= $e($h['url']) ?></td>
                            <td class="text-[11.5px] text-slate-400"><?= $e($h['events']) ?></td>
                            <td class="text-[11px]">
                                <?php if ($h['last_triggered_at']): ?>
                                    <div><?= $e(substr($h['last_triggered_at'], 0, 16)) ?></div>
                                    <div><span class="dev-pill <?= ($h['last_status_code'] ?? 0) >= 200 && ($h['last_status_code'] ?? 0) < 300 ? 'dev-pill-emerald' : 'dev-pill-red' ?> !text-[9px]">HTTP <?= (int)$h['last_status_code'] ?></span></div>
                                <?php else: ?>
                                    <span class="text-slate-500">Sin actividad</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ((int)$h['is_active'] === 1): ?>
                                    <span class="dev-pill dev-pill-emerald">Activo</span>
                                <?php else: ?>
                                    <span class="dev-pill dev-pill-gray">Pausado</span>
                                <?php endif; ?>
                                <?php if ((int)$h['failure_count'] >= 5): ?>
                                    <div class="mt-1"><span class="dev-pill dev-pill-red !text-[9px]">5+ fallos</span></div>
                                <?php endif; ?>
                            </td>
                            <td class="flex gap-1 flex-wrap">
                                <form method="POST" action="<?= $url('/developers/webhooks/' . $h['id'] . '/test') ?>"><input type="hidden" name="_csrf" value="<?= $e($csrf) ?>"><button type="submit" class="dev-btn dev-btn-soft dev-btn-icon" title="Test"><i class="lucide lucide-zap text-[12px]"></i></button></form>
                                <form method="POST" action="<?= $url('/developers/webhooks/' . $h['id'] . '/rotate-secret') ?>" onsubmit="return confirm('¿Rotar secret? Tu integración dejará de validar firmas hasta que actualices el secret.')"><input type="hidden" name="_csrf" value="<?= $e($csrf) ?>"><button class="dev-btn dev-btn-soft dev-btn-icon" title="Rotar secret"><i class="lucide lucide-refresh-cw text-[12px]"></i></button></form>
                                <form method="POST" action="<?= $url('/developers/webhooks/' . $h['id'] . '/toggle') ?>"><input type="hidden" name="_csrf" value="<?= $e($csrf) ?>"><button class="dev-btn dev-btn-soft dev-btn-icon" title="Pausar/activar"><i class="lucide lucide-power text-[12px]"></i></button></form>
                                <form method="POST" action="<?= $url('/developers/webhooks/' . $h['id'] . '/delete') ?>" onsubmit="return confirm('¿Eliminar webhook?')"><input type="hidden" name="_csrf" value="<?= $e($csrf) ?>"><button class="dev-btn dev-btn-danger dev-btn-icon" title="Eliminar"><i class="lucide lucide-trash-2 text-[12px]"></i></button></form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Recent deliveries -->
<?php if (!empty($deliveries)): ?>
<div class="dev-card">
    <div class="dev-card-head">
        <h2 class="font-display font-bold text-white text-[16px]">Últimas entregas</h2>
    </div>
    <div style="overflow-x:auto">
        <table class="dev-table">
            <thead><tr><th>Hora</th><th>Webhook</th><th>Evento</th><th>Status</th><th>Respuesta</th></tr></thead>
            <tbody>
                <?php foreach ($deliveries as $d):
                    $sc = (int)$d['status_code'];
                    $cls = $sc >= 500 ? 'dev-pill-red' : ($sc >= 400 ? 'dev-pill-amber' : ($sc >= 200 ? 'dev-pill-emerald' : 'dev-pill-gray'));
                ?>
                    <tr>
                        <td class="text-[11.5px] font-mono"><?= $e(substr($d['created_at'], 0, 19)) ?></td>
                        <td class="text-[12px]"><?= $e($d['webhook_name']) ?></td>
                        <td class="font-mono text-[11.5px]"><?= $e($d['event']) ?></td>
                        <td><span class="dev-pill <?= $cls ?>"><?= $sc ?: '—' ?></span></td>
                        <td class="text-[11px] font-mono text-slate-400" style="max-width:300px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap"><?= $e(mb_strimwidth((string)($d['response_excerpt'] ?? ''), 0, 80, '…')) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="dev-card dev-card-pad" style="border-color:rgba(56,189,248,.18); background:rgba(14,165,233,.04)">
    <h3 class="font-display font-bold text-white text-[14.5px] mb-2 flex items-center gap-2"><i class="lucide lucide-shield-check text-sky-300"></i> Verificación de firma</h3>
    <p class="text-[12.5px] text-slate-300 mb-3 leading-[1.65]">Cada request se firma con HMAC-SHA256 usando tu secret. Verifica el header <code class="text-amber-300 font-mono">X-Kydesk-Signature</code> antes de procesar:</p>
    <pre class="dev-code">// Node.js
const crypto = require('crypto');
const sig = req.headers['x-kydesk-signature'];
const expected = crypto.createHmac('sha256', process.env.WEBHOOK_SECRET)
  .update(rawBody)
  .digest('hex');
if (sig !== expected) return res.status(401).end();
// procesa req.body</pre>
</div>
