<?php $slug = $tenant->slug;
$isAssigned = (int)$cfg['is_assigned'] === 1;
$isEnabled = (int)$cfg['is_enabled'] === 1;
$used = (int)$cfg['used_this_month'];
$quota = (int)$cfg['monthly_quota'];
$pct = $quota > 0 ? min(100, ($used / $quota) * 100) : 0;
?>

<div class="flex items-end justify-between mb-5 flex-wrap gap-3">
    <div>
        <div class="flex items-center gap-2 mb-1.5">
            <span class="badge badge-purple"><i class="lucide lucide-sparkles"></i> Kyros IA · Enterprise</span>
            <span class="badge badge-gray"><i class="lucide lucide-shield"></i> Gestionada por Kydesk</span>
        </div>
        <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">Kyros IA Asistente</h1>
        <p class="text-[13px] text-ink-400">Sugerir respuesta · Resumir · Clasificar · Sentiment · Traducir</p>
    </div>
</div>

<?php if (!$isAssigned): ?>
    <!-- Pending activation -->
    <div class="card card-pad text-center py-12" style="background:linear-gradient(135deg,#fef3c7,#fef9c3);border-color:#fde68a">
        <div class="w-16 h-16 mx-auto rounded-2xl bg-amber-500 text-white grid place-items-center mb-4"><i class="lucide lucide-shield-alert text-[28px]"></i></div>
        <h2 class="font-display font-extrabold text-[22px] tracking-[-0.02em]">IA pendiente de activación</h2>
        <p class="text-[13.5px] text-amber-800 max-w-md mx-auto mt-2">El módulo está incluido en tu plan Enterprise pero requiere activación manual del equipo de Kydesk. Contactanos para habilitarlo.</p>
        <a href="mailto:soporte@kydesk.com?subject=Activar%20IA%20-%20<?= urlencode($tenant->name) ?>" class="btn btn-primary btn-sm mt-5 inline-flex"><i class="lucide lucide-mail"></i> Solicitar activación</a>
    </div>
<?php else: ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 card card-pad">
        <h3 class="font-display font-bold text-[15px] mb-4 flex items-center gap-2"><i class="lucide lucide-toggle-right text-brand-600"></i> Acciones disponibles</h3>
        <p class="text-[12.5px] text-ink-500 mb-4">Configurá qué acciones de IA usar en este workspace. La API key, el modelo y la cuota están gestionados por Kydesk.</p>
        <form method="POST" action="<?= $url('/t/' . $slug . '/ai/settings') ?>" class="space-y-3">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                <label class="flex items-start gap-2 p-3 rounded-lg" style="background:#fafafb;border:1px solid var(--border)">
                    <input type="checkbox" name="suggest_replies" value="1" <?= $cfg['suggest_replies']?'checked':'' ?> class="mt-0.5">
                    <div><div class="font-display font-bold text-[13px]">Sugerir respuesta</div><div class="text-[11.5px] text-ink-500">Genera draft de reply para cada ticket</div></div>
                </label>
                <label class="flex items-start gap-2 p-3 rounded-lg" style="background:#fafafb;border:1px solid var(--border)">
                    <input type="checkbox" name="auto_summarize" value="1" <?= $cfg['auto_summarize']?'checked':'' ?> class="mt-0.5">
                    <div><div class="font-display font-bold text-[13px]">Resumen automático</div><div class="text-[11.5px] text-ink-500">Resume hilo del ticket en 2-4 líneas</div></div>
                </label>
                <label class="flex items-start gap-2 p-3 rounded-lg" style="background:#fafafb;border:1px solid var(--border)">
                    <input type="checkbox" name="auto_categorize" value="1" <?= $cfg['auto_categorize']?'checked':'' ?> class="mt-0.5">
                    <div><div class="font-display font-bold text-[13px]">Auto-categorizar</div><div class="text-[11.5px] text-ink-500">Sugerir categoría y prioridad</div></div>
                </label>
                <label class="flex items-start gap-2 p-3 rounded-lg" style="background:#fafafb;border:1px solid var(--border)">
                    <input type="checkbox" name="detect_sentiment" value="1" <?= $cfg['detect_sentiment']?'checked':'' ?> class="mt-0.5">
                    <div><div class="font-display font-bold text-[13px]">Detectar sentiment</div><div class="text-[11.5px] text-ink-500">Marca tickets con sentimiento negativo o urgente</div></div>
                </label>
                <label class="flex items-start gap-2 p-3 rounded-lg md:col-span-2" style="background:#fafafb;border:1px solid var(--border)">
                    <input type="checkbox" name="auto_translate" value="1" <?= $cfg['auto_translate']?'checked':'' ?> class="mt-0.5">
                    <div><div class="font-display font-bold text-[13px]">Traducción</div><div class="text-[11.5px] text-ink-500">Traducí mensajes entrantes/salientes al idioma destino</div></div>
                </label>
            </div>

            <div><label class="label">Idioma destino para traducción</label><input name="target_language" value="<?= $e($cfg['target_language']) ?>" class="input" placeholder="es / en / pt / fr"></div>

            <label class="flex items-center gap-2 text-[13px] p-3 rounded-lg" style="background:#f3f0ff;border:1px solid #cdbfff">
                <input type="checkbox" name="is_enabled" value="1" <?= $isEnabled?'checked':'' ?>>
                <strong>Activar IA en este workspace</strong>
                <span class="text-[11.5px] text-ink-500 ml-auto">Pausa todas las acciones sin perder configuración</span>
            </label>

            <div class="flex justify-end pt-2" style="border-top:1px solid var(--border)">
                <button class="btn btn-primary"><i class="lucide lucide-save"></i> Guardar preferencias</button>
            </div>
        </form>
    </div>

    <div class="space-y-3">
        <!-- Status card -->
        <div class="card card-pad">
            <div class="flex items-center justify-between mb-2">
                <div class="text-[11px] uppercase font-bold tracking-[0.14em] text-ink-400">Estado</div>
                <?php if ($isEnabled): ?>
                    <span class="badge badge-green"><i class="lucide lucide-check-circle text-[10px]"></i> Activa</span>
                <?php else: ?>
                    <span class="badge badge-amber">Pausada</span>
                <?php endif; ?>
            </div>
            <div class="text-[12.5px] text-ink-500">
                <div>Modelo: <span class="font-mono text-ink-700"><?= $e($cfg['model'] ?: 'configurado por Kydesk') ?></span></div>
                <?php if (!empty($cfg['assigned_at'])): ?>
                    <div class="mt-1.5">Activada: <span class="font-mono text-ink-700"><?= $e($cfg['assigned_at']) ?></span></div>
                    <?php if (!empty($cfg['admin_name'])): ?><div>Por: <strong><?= $e($cfg['admin_name']) ?></strong> (Kydesk)</div><?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="card card-pad">
            <div class="text-[11px] uppercase font-bold tracking-[0.14em] text-ink-400 mb-2">Uso este mes</div>
            <div class="font-display font-extrabold text-[26px]"><?= $used ?><span class="text-ink-400 text-[14px]"> / <?= $quota ?></span></div>
            <div style="height:6px;background:#f3f4f6;border-radius:999px;overflow:hidden" class="mt-2">
                <div style="height:100%;background:<?= $pct >= 90 ? '#dc2626' : ($pct >= 70 ? '#f59e0b' : '#10b981') ?>;width:<?= $pct ?>%"></div>
            </div>
            <p class="text-[11px] text-ink-500 mt-2">¿Necesitás más cuota? Contactá al equipo de Kydesk.</p>
        </div>

        <div class="card card-pad">
            <div class="text-[11px] uppercase font-bold tracking-[0.14em] text-ink-400 mb-2">Por acción</div>
            <?php foreach ($usage as $u): ?>
                <div class="flex items-center justify-between py-1 text-[12.5px]">
                    <span class="text-ink-700"><?= $e($u['action']) ?></span>
                    <span class="font-mono font-bold"><?= (int)$u['cnt'] ?></span>
                </div>
            <?php endforeach; ?>
            <?php if (empty($usage)): ?><p class="text-[12px] text-ink-400">Sin uso este mes.</p><?php endif; ?>
        </div>
    </div>

    <div class="lg:col-span-3">
        <h3 class="font-display font-bold text-[15px] mb-3">Últimas completions</h3>
        <div class="card overflow-hidden">
            <table class="admin-table">
                <thead><tr><th>Fecha</th><th>Acción</th><th>Usuario</th><th>Ticket</th><th>Tokens (in/out)</th><th>Estado</th></tr></thead>
                <tbody>
                <?php foreach ($logs as $l): ?>
                    <tr>
                        <td class="text-[11.5px] font-mono text-ink-500"><?= $e($l['created_at']) ?></td>
                        <td><span class="badge badge-purple"><?= $e($l['action']) ?></span></td>
                        <td class="text-[12px]"><?= $e($l['user_name'] ?? '—') ?></td>
                        <td>
                            <?php if (!empty($l['ticket_code'])): ?><a href="<?= $url('/t/' . $slug . '/tickets/' . (int)$l['ticket_id']) ?>" class="text-brand-700 font-mono text-[11.5px]"><?= $e($l['ticket_code']) ?></a><?php else: ?>—<?php endif; ?>
                        </td>
                        <td class="font-mono text-[12px]"><?= (int)$l['tokens_in'] ?> / <?= (int)$l['tokens_out'] ?></td>
                        <td>
                            <?php if ($l['status'] === 'ok'): ?><span class="badge badge-green">OK</span>
                            <?php else: ?><span class="badge badge-red" data-tooltip="<?= $e($l['error']) ?>">Error</span><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($logs)): ?><tr><td colspan="6" style="text-align:center;padding:20px;color:#8e8e9a">Sin actividad.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php endif; ?>
