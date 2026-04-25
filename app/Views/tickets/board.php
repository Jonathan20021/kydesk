<?php use App\Core\Helpers; $slug = $tenant->slug;
$priColor = ['urgent'=>'#ef4444','high'=>'#f59e0b','medium'=>'#7c5cff','low'=>'#9ca3af'];
$stInfo = [
    'open'=>['Abierto','#3b82f6','#dbeafe'],
    'in_progress'=>['En progreso','#f59e0b','#fef3c7'],
    'on_hold'=>['En espera','#9ca3af','#f3f4f6'],
    'resolved'=>['Resuelto','#16a34a','#d1fae5'],
];
?>

<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
    <div>
        <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">Tablero</h1>
        <p class="text-[13px] text-ink-400 inline-flex items-center gap-1.5"><i class="lucide lucide-move text-[13px]"></i> Arrastra entre columnas para cambiar el estado · <?= array_sum(array_map('count', $groups)) ?> tickets</p>
    </div>
    <div class="flex items-center gap-2">
        <div class="segmented">
            <a href="<?= $url('/t/' . $slug . '/tickets') ?>"><i class="lucide lucide-list text-[13px]"></i> Lista</a>
            <a href="<?= $url('/t/' . $slug . '/tickets/board') ?>" class="active"><i class="lucide lucide-kanban-square text-[13px]"></i> Tablero</a>
        </div>
        <?php if ($auth->can('tickets.create')): ?>
            <a href="<?= $url('/t/' . $slug . '/tickets/create') ?>" class="btn btn-primary btn-sm"><i class="lucide lucide-plus"></i> Nuevo</a>
        <?php endif; ?>
    </div>
</div>

<div id="kanban-toast" class="fixed top-6 right-6 z-50 hidden items-center gap-2 px-4 py-2.5 rounded-xl text-[13px] font-medium" style="background:#16151b;color:white;box-shadow:0 12px 28px -8px rgba(0,0,0,.3)"></div>

<div id="kanban-board" class="flex gap-4 overflow-x-auto pb-4" data-csrf="<?= $e($csrf) ?>" data-update-url="<?= $url('/t/' . $slug . '/tickets/__ID__/move') ?>">
    <?php foreach ($groups as $st => $list): [$lbl, $color, $bg] = $stInfo[$st]; ?>
        <div class="kanban-col" data-status="<?= $e($st) ?>">
            <div class="kanban-col-head" style="background:linear-gradient(180deg,<?= $bg ?>,white)">
                <span class="dot" style="background: <?= $color ?>"></span>
                <span class="font-display font-bold text-[14px]"><?= $lbl ?></span>
                <span class="kbd ml-auto" data-count><?= count($list) ?></span>
                <a href="<?= $url('/t/' . $slug . '/tickets/create?status=' . $st) ?>" class="ml-1 w-7 h-7 rounded-lg grid place-items-center text-ink-400 hover:bg-white hover:text-brand-700 transition" data-tooltip="Nuevo en <?= $lbl ?>"><i class="lucide lucide-plus text-[14px]"></i></a>
            </div>
            <div class="kanban-list p-3 space-y-2.5 overflow-y-auto" style="min-height:120px;max-height:calc(100vh - 280px)" data-status="<?= $e($st) ?>">
                <?php foreach ($list as $t): ?>
                    <div class="kanban-card group cursor-grab" data-id="<?= (int)$t['id'] ?>" data-href="<?= $url('/t/' . $slug . '/tickets/' . $t['id']) ?>">
                        <div class="flex items-center gap-1.5 mb-2">
                            <span class="text-[10px] font-mono text-ink-400"><?= $e($t['code']) ?></span>
                            <?= Helpers::priorityBadge($t['priority']) ?>
                            <?php if ((int)$t['escalation_level'] > 0): ?><span class="badge badge-rose">N<?= (int)$t['escalation_level']+1 ?></span><?php endif; ?>
                            <i class="lucide lucide-grip-vertical text-[12px] text-ink-300 ml-auto opacity-0 group-hover:opacity-100 transition" style="cursor:grab"></i>
                        </div>
                        <div class="font-display font-bold text-[13.5px] line-clamp-2 mb-2"><?= $e($t['subject']) ?></div>
                        <?php if ($t['category_name']): ?>
                            <div class="text-[11px] mb-2 inline-flex items-center gap-1.5 text-ink-400"><span class="dot" style="background: <?= $e($t['category_color']) ?>"></span><?= $e($t['category_name']) ?></div>
                        <?php endif; ?>
                        <div class="flex items-center justify-between mt-2 pt-2 border-t border-[#ececef]">
                            <?php if ($t['assigned_name']): ?>
                                <div class="avatar avatar-sm" style="background: <?= Helpers::colorFor($t['assigned_email'] ?? '') ?>;color:white;" data-tooltip="<?= $e($t['assigned_name']) ?>"><?= Helpers::initials($t['assigned_name']) ?></div>
                            <?php else: ?>
                                <div class="avatar avatar-sm" style="border:1px dashed #cdcdd6;background:transparent"><i class="lucide lucide-user-x text-[12px] text-ink-400"></i></div>
                            <?php endif; ?>
                            <span class="text-[10.5px] text-ink-400"><?= Helpers::ago($t['updated_at']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="kanban-empty text-[12px] text-center py-8 text-ink-300 italic" <?= empty($list)?'':'style="display:none"' ?>>
                    Soltá tickets aquí
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
<script>
(function(){
    const board = document.getElementById('kanban-board');
    if (!board || !window.Sortable) return;
    const csrf = board.dataset.csrf;
    const tpl = board.dataset.updateUrl;

    function toast(msg, ok) {
        const t = document.getElementById('kanban-toast');
        t.style.background = ok ? '#16151b' : '#ef4444';
        t.innerHTML = '<i data-lucide="' + (ok ? 'check-circle-2' : 'alert-circle') + '"></i><span>' + msg + '</span>';
        t.classList.remove('hidden');
        t.style.display = 'flex';
        if (window.lucide) window.lucide.createIcons({ attrs: { width: '1em', height: '1em' } });
        setTimeout(() => { t.style.display = 'none'; }, 1800);
    }

    function refreshCounts() {
        document.querySelectorAll('.kanban-col').forEach(col => {
            const list = col.querySelector('.kanban-list');
            const count = list.querySelectorAll('.kanban-card').length;
            const badge = col.querySelector('[data-count]');
            const empty = list.querySelector('.kanban-empty');
            if (badge) badge.textContent = count;
            if (empty) empty.style.display = count === 0 ? '' : 'none';
        });
    }

    document.querySelectorAll('.kanban-list').forEach(list => {
        new Sortable(list, {
            group: 'kanban',
            animation: 180,
            ghostClass: 'kanban-ghost',
            chosenClass: 'kanban-chosen',
            dragClass: 'kanban-drag',
            forceFallback: true,
            fallbackClass: 'kanban-fallback',
            filter: '.kanban-empty',
            preventOnFilter: false,
            onStart: () => board.classList.add('kanban-dragging'),
            onEnd: async (evt) => {
                board.classList.remove('kanban-dragging');
                const card = evt.item;
                const newStatus = evt.to.dataset.status;
                const oldStatus = evt.from.dataset.status;
                refreshCounts();
                if (newStatus === oldStatus) return;

                const id = card.dataset.id;
                const url = tpl.replace('__ID__', id);
                const fd = new FormData();
                fd.append('_csrf', csrf);
                fd.append('status', newStatus);
                try {
                    const res = await fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' });
                    const data = await res.json();
                    if (data.ok) {
                        toast('Movido a ' + (evt.to.closest('.kanban-col').querySelector('.font-display').textContent), true);
                    } else {
                        evt.from.appendChild(card);
                        refreshCounts();
                        toast(data.error || 'No se pudo mover', false);
                    }
                } catch (err) {
                    evt.from.appendChild(card);
                    refreshCounts();
                    toast('Error de red', false);
                }
            },
        });
    });

    document.querySelectorAll('.kanban-card').forEach(card => {
        card.addEventListener('click', e => {
            if (board.classList.contains('kanban-dragging')) return;
            if (e.target.closest('a, button')) return;
            window.location.href = card.dataset.href;
        });
    });
})();
</script>

<style>
.kanban-card { user-select: none; }
.kanban-card:active { cursor: grabbing; }
.kanban-ghost { opacity: 0.3; background: #f3f0ff !important; border: 2px dashed #7c5cff !important; }
.kanban-chosen { box-shadow: 0 16px 32px -8px rgba(124,92,255,.35) !important; transform: scale(1.02); border-color: #7c5cff !important; }
.kanban-drag { transform: rotate(2deg); }
.kanban-dragging .kanban-list { background: rgba(124,92,255,0.025); transition: background .2s; }
.kanban-dragging .kanban-list:hover { background: rgba(124,92,255,0.08); }
.kanban-fallback { opacity: 0.85 !important; }
</style>
