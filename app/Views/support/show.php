<?php
$slug = $tenant->slug;
$t = $ticket;
$statusColors = ['open'=>'#f59e0b','in_progress'=>'#7c5cff','waiting'=>'#9ca3af','resolved'=>'#10b981','closed'=>'#6b6b78'];
$priColors = ['urgent'=>'#ef4444','high'=>'#f59e0b','medium'=>'#7c5cff','low'=>'#9ca3af'];
$sc = $statusColors[$t['status']] ?? '#6b6b78';
$pc = $priColors[$t['priority']] ?? '#7c5cff';
?>

<div class="flex items-center gap-3">
    <a href="<?= $url('/t/' . $slug . '/support') ?>" class="btn btn-ghost btn-sm"><i class="lucide lucide-arrow-left text-[13px]"></i></a>
    <div class="flex-1">
        <div class="flex items-center gap-2 mb-1">
            <span class="font-mono text-[11px] text-ink-400"><?= $e($t['code']) ?></span>
            <span class="status-pill" style="background:<?= $sc ?>15;color:<?= $sc ?>;border:1px solid <?= $sc ?>30"><?= ucfirst(str_replace('_',' ',$t['status'])) ?></span>
            <span class="status-pill" style="background:<?= $pc ?>15;color:<?= $pc ?>;border:1px solid <?= $pc ?>30"><?= ucfirst($t['priority']) ?></span>
        </div>
        <h1 class="font-display font-extrabold text-[22px] tracking-[-0.02em]"><?= $e($t['subject']) ?></h1>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 space-y-3">
        <!-- Mensaje original -->
        <div class="card card-pad">
            <div class="flex items-center gap-2 text-[12px] text-ink-400 mb-3">
                <i class="lucide lucide-user-circle text-[15px]"></i>
                <span class="font-semibold text-ink-700">Tú</span>
                · <?= date('d/m/Y H:i', strtotime($t['created_at'])) ?>
            </div>
            <div class="text-[13.5px] text-ink-700 leading-relaxed whitespace-pre-wrap"><?= $e($t['body']) ?></div>
        </div>

        <!-- Replies -->
        <?php foreach ($replies as $r):
            $isSuper = $r['author_type'] === 'super_admin'; ?>
            <div class="card card-pad <?= $isSuper?'border-brand-300':'' ?>" <?= $isSuper?'style="background:linear-gradient(135deg,#f3f0ff20,#fff)"':'' ?>>
                <div class="flex items-center gap-2 text-[12px] text-ink-400 mb-3">
                    <i class="lucide lucide-<?= $isSuper?'shield-check':'user-circle' ?> text-[15px] <?= $isSuper?'text-brand-600':'' ?>"></i>
                    <span class="font-semibold text-ink-700"><?= $e($r['author_name'] ?? ($isSuper?'Equipo Kydesk':'Tú')) ?></span>
                    <?php if ($isSuper): ?><span class="badge badge-purple">Soporte Kydesk</span><?php endif; ?>
                    · <?= date('d/m/Y H:i', strtotime($r['created_at'])) ?>
                </div>
                <div class="text-[13.5px] text-ink-700 leading-relaxed whitespace-pre-wrap"><?= $e($r['body']) ?></div>
            </div>
        <?php endforeach; ?>

        <!-- Reply form -->
        <?php if ($t['status'] !== 'closed'): ?>
            <form method="POST" action="<?= $url('/t/' . $slug . '/support/' . $t['id'] . '/reply') ?>" class="card card-pad">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <h3 class="font-display font-bold text-[14px] mb-3"><i class="lucide lucide-reply text-brand-600"></i> Responder</h3>
                <textarea name="body" required rows="5" class="input" placeholder="Tu respuesta..."></textarea>
                <div class="mt-3 flex justify-end">
                    <button class="btn btn-primary"><i class="lucide lucide-send"></i> Enviar respuesta</button>
                </div>
            </form>
        <?php else: ?>
            <div class="card card-pad text-center text-[13px] text-ink-400"><i class="lucide lucide-lock"></i> Este ticket está cerrado. Abre uno nuevo si necesitas más ayuda.</div>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div class="space-y-3">
        <div class="card card-pad">
            <h3 class="font-display font-bold text-[14px] mb-3">Detalles</h3>
            <dl class="space-y-2 text-[12.5px]">
                <div class="flex items-center justify-between"><dt class="text-ink-400">Código</dt><dd class="font-mono"><?= $e($t['code']) ?></dd></div>
                <div class="flex items-center justify-between"><dt class="text-ink-400">Estado</dt><dd class="font-semibold"><?= ucfirst(str_replace('_',' ',$t['status'])) ?></dd></div>
                <div class="flex items-center justify-between"><dt class="text-ink-400">Prioridad</dt><dd class="font-semibold"><?= ucfirst($t['priority']) ?></dd></div>
                <div class="flex items-center justify-between"><dt class="text-ink-400">Creado</dt><dd><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></dd></div>
                <div class="flex items-center justify-between"><dt class="text-ink-400">Actualizado</dt><dd><?= date('d/m/Y H:i', strtotime($t['updated_at'])) ?></dd></div>
            </dl>
        </div>
        <div class="card card-pad" style="background:#f3f0ff;border-color:#cdbfff">
            <i class="lucide lucide-info text-brand-700"></i>
            <p class="text-[12px] text-ink-700 mt-2 leading-relaxed">El equipo Kydesk te notificará por email cuando responda este ticket.</p>
        </div>
    </div>
</div>
