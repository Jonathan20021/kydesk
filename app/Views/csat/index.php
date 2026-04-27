<?php $slug = $tenant->slug; $isNps = $type === 'nps';
$emojiCsat = ['😡','😞','😐','🙂','😍'];
?>

<div class="flex items-end justify-between mb-5 flex-wrap gap-3">
    <div>
        <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]"><?= $isNps ? 'NPS · Net Promoter Score' : 'CSAT · Satisfacción del cliente' ?></h1>
        <p class="text-[13px] text-ink-400">Encuestas automáticas post-resolución <?= $isNps ? '(escala 0-10)' : '(escala 1-5)' ?></p>
    </div>
    <div class="admin-tabs" style="background:white;border:1px solid var(--border)">
        <a href="?type=csat" class="admin-tab <?= !$isNps?'active':'' ?>"><i class="lucide lucide-smile text-[13px]"></i> CSAT</a>
        <a href="?type=nps" class="admin-tab <?= $isNps?'active':'' ?>"><i class="lucide lucide-trending-up text-[13px]"></i> NPS</a>
    </div>
</div>

<!-- Stats -->
<?php if ($isNps): ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
        <div class="card card-pad"><div class="text-[10.5px] uppercase font-bold text-ink-400 tracking-[0.14em]">NPS Score</div><div class="font-display font-extrabold text-[34px]" style="color:<?= $stats['score']>=50?'#16a34a':($stats['score']>=0?'#f59e0b':'#dc2626') ?>"><?= $stats['score'] ?></div></div>
        <div class="card card-pad"><div class="text-[10.5px] uppercase font-bold text-ink-400 tracking-[0.14em]">Promotores (9-10)</div><div class="font-display font-extrabold text-[28px] text-emerald-600"><?= $stats['promoters'] ?></div></div>
        <div class="card card-pad"><div class="text-[10.5px] uppercase font-bold text-ink-400 tracking-[0.14em]">Pasivos (7-8)</div><div class="font-display font-extrabold text-[28px] text-amber-600"><?= $stats['passives'] ?></div></div>
        <div class="card card-pad"><div class="text-[10.5px] uppercase font-bold text-ink-400 tracking-[0.14em]">Detractores (0-6)</div><div class="font-display font-extrabold text-[28px] text-rose-600"><?= $stats['detractors'] ?></div></div>
    </div>
<?php else: ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
        <div class="card card-pad"><div class="text-[10.5px] uppercase font-bold text-ink-400 tracking-[0.14em]">% Satisfechos</div><div class="font-display font-extrabold text-[34px]" style="color:<?= $stats['rate']>=80?'#16a34a':($stats['rate']>=60?'#f59e0b':'#dc2626') ?>"><?= $stats['rate'] ?>%</div></div>
        <div class="card card-pad"><div class="text-[10.5px] uppercase font-bold text-ink-400 tracking-[0.14em]">Promedio</div><div class="font-display font-extrabold text-[28px] text-brand-700"><?= number_format($stats['avg'], 1) ?></div></div>
        <div class="card card-pad"><div class="text-[10.5px] uppercase font-bold text-ink-400 tracking-[0.14em]">Respondidas</div><div class="font-display font-extrabold text-[28px]"><?= $stats['responded'] ?></div></div>
        <div class="card card-pad"><div class="text-[10.5px] uppercase font-bold text-ink-400 tracking-[0.14em]">Enviadas</div><div class="font-display font-extrabold text-[28px] text-ink-500"><?= $stats['total'] ?></div></div>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <!-- Settings -->
    <div class="card card-pad">
        <h3 class="font-display font-bold text-[15px] mb-3 flex items-center gap-2"><i class="lucide lucide-settings text-brand-600"></i> Configuración</h3>
        <form method="POST" action="<?= $url('/t/' . $slug . '/csat/settings') ?>" class="space-y-3">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
            <input type="hidden" name="type" value="<?= $type ?>">
            <label class="flex items-center gap-2 text-[13px]">
                <input type="checkbox" name="is_enabled" value="1" <?= (int)$settings['is_enabled']?'checked':'' ?>> Enviar encuesta automáticamente al resolver tickets
            </label>
            <div><label class="label">Demora (minutos después de resolver)</label><input name="delay_minutes" type="number" min="0" value="<?= (int)$settings['delay_minutes'] ?>" class="input"></div>
            <div><label class="label">Asunto del email</label><input name="subject" value="<?= $e($settings['subject']) ?>" class="input"></div>
            <div><label class="label">Texto introductorio</label><textarea name="intro" rows="2" class="input"><?= $e($settings['intro']) ?></textarea></div>
            <div><label class="label">Mensaje de agradecimiento</label><textarea name="thanks_message" rows="2" class="input"><?= $e($settings['thanks_message']) ?></textarea></div>
            <button class="btn btn-primary w-full"><i class="lucide lucide-save"></i> Guardar</button>
        </form>
    </div>

    <!-- Respuestas -->
    <div class="lg:col-span-2 card overflow-hidden">
        <div class="p-4" style="border-bottom:1px solid var(--border)">
            <h3 class="font-display font-bold text-[15px]">Últimas respuestas</h3>
        </div>
        <?php if (empty($surveys)): ?>
            <div class="text-center py-12 text-ink-400 text-[12.5px]">Sin respuestas todavía.</div>
        <?php else: ?>
            <table class="admin-table">
                <thead><tr><th>Fecha</th><th>Ticket</th><th>Score</th><th>Comentario</th></tr></thead>
                <tbody>
                <?php foreach ($surveys as $s): ?>
                    <tr>
                        <td class="text-[11.5px] text-ink-500 font-mono"><?= $e($s['responded_at']) ?></td>
                        <td>
                            <a href="<?= $url('/t/' . $slug . '/tickets/' . (int)$s['ticket_id']) ?>" class="text-brand-700 font-mono text-[11.5px]"><?= $e($s['ticket_code']) ?></a>
                            <div class="text-[11px] text-ink-500 line-clamp-1 max-w-xs"><?= $e($s['ticket_subject']) ?></div>
                        </td>
                        <td>
                            <?php if ($isNps): ?>
                                <?php $color = (int)$s['score']>=9?'#16a34a':((int)$s['score']>=7?'#f59e0b':'#dc2626'); ?>
                                <span class="font-display font-bold text-[18px]" style="color:<?= $color ?>"><?= (int)$s['score'] ?></span><span class="text-ink-400 text-[12px]">/10</span>
                            <?php else: ?>
                                <span class="text-[18px]"><?= $emojiCsat[max(0,(int)$s['score']-1)] ?? '—' ?></span>
                                <span class="text-ink-500 text-[11.5px] font-mono ml-1"><?= (int)$s['score'] ?>/5</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-[12.5px] text-ink-700 max-w-md"><?= $e($s['comment'] ?: '—') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
