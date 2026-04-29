<?php
use App\Core\Helpers;
$slug = $tenant->slug;
$currentPipeline = null;
foreach ($pipelines as $p) if ((int)$p['id'] === (int)$pipelineId) { $currentPipeline = $p; break; }
?>

<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-5">
    <div>
        <a href="<?= $url('/t/' . $slug . '/crm') ?>" class="text-[11.5px] font-semibold text-brand-700 hover:underline inline-flex items-center gap-1 mb-1.5"><i class="lucide lucide-arrow-left text-[11px]"></i> Volver al CRM</a>
        <h1 class="font-display font-extrabold text-[26px] tracking-[-0.025em]">Pipeline de oportunidades</h1>
        <p class="text-[12.5px] text-ink-400">Arrastrá las tarjetas entre etapas para mover el deal · cada etapa actualiza su probabilidad y, si es ganada/perdida, cierra la oportunidad.</p>
    </div>
    <div class="flex items-center gap-2">
        <form method="GET" class="flex items-center gap-2">
            <select class="input" name="pipeline_id" onchange="this.form.submit()">
                <?php foreach ($pipelines as $p): ?>
                    <option value="<?= (int)$p['id'] ?>" <?= (int)$p['id']===(int)$pipelineId?'selected':'' ?>><?= $e($p['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <a href="<?= $url('/t/' . $slug . '/crm/leads') ?>" class="btn btn-soft btn-sm"><i class="lucide lucide-list"></i> Lista</a>
    </div>
</div>

<?php if ($currentPipeline): ?>
<div class="card card-pad mb-4" style="background:linear-gradient(135deg,<?= $e($currentPipeline['color']) ?>10,#ffffff);border-color:<?= $e($currentPipeline['color']) ?>33">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl grid place-items-center" style="background:<?= $e($currentPipeline['color']) ?>;color:white"><i class="lucide lucide-<?= $e($currentPipeline['icon']) ?>"></i></div>
        <div class="flex-1">
            <div class="font-display font-bold text-[15px]"><?= $e($currentPipeline['name']) ?></div>
            <div class="text-[11.5px] text-ink-500"><?= $e($currentPipeline['description'] ?? '—') ?></div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$totalDeals = 0; $totalValue = 0;
foreach ($byStage as $list) { foreach ($list as $d) { $totalDeals++; $totalValue += (float)$d['amount']; } }
?>
<div class="overflow-x-auto pb-3">
    <div class="flex gap-3" id="kanban" style="min-width:max-content">
        <?php foreach ($stages as $stage):
            $stageDeals = $byStage[(int)$stage['id']] ?? [];
            $stageValue = 0;
            foreach ($stageDeals as $d) $stageValue += (float)$d['amount'];
        ?>
            <div class="kanban-col" data-stage-id="<?= (int)$stage['id'] ?>" style="width:300px;flex-shrink:0">
                <div class="rounded-2xl border-t-4 bg-white border border-[#ececef] overflow-hidden" style="border-top-color:<?= $e($stage['color']) ?>">
                    <div class="px-3 py-2.5 flex items-center gap-2" style="background:<?= $e($stage['color']) ?>0d">
                        <div class="font-display font-bold text-[13px] flex-1 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full" style="background:<?= $e($stage['color']) ?>"></span>
                            <?= $e($stage['name']) ?>
                            <?php if ((int)$stage['is_won']===1): ?><i class="lucide lucide-trophy text-[11px] text-emerald-600"></i><?php endif; ?>
                            <?php if ((int)$stage['is_lost']===1): ?><i class="lucide lucide-x-circle text-[11px] text-rose-600"></i><?php endif; ?>
                        </div>
                        <span class="text-[10.5px] font-bold text-ink-500"><?= count($stageDeals) ?></span>
                    </div>
                    <div class="px-3 py-1.5 text-[11px] text-ink-400 border-b border-[#ececef]">
                        <?= (int)$stage['probability'] ?>% prob · <strong class="font-mono">$<?= number_format($stageValue, 0) ?></strong>
                    </div>
                    <div class="kanban-list p-2 space-y-2 min-h-[200px]" data-stage-id="<?= (int)$stage['id'] ?>">
                        <?php if (empty($stageDeals)): ?>
                            <div class="text-center py-8 text-[11px] text-ink-400">Sin oportunidades</div>
                        <?php else: foreach ($stageDeals as $d): ?>
                            <a href="<?= $url('/t/' . $slug . '/crm/leads/' . (int)$d['lead_id']) ?>" class="kanban-card block bg-white border border-[#ececef] rounded-xl p-3 hover:border-brand-300 hover:shadow-sm transition cursor-grab" data-deal-id="<?= (int)$d['id'] ?>" draggable="true">
                                <div class="font-display font-bold text-[12.5px] line-clamp-2"><?= $e($d['title']) ?></div>
                                <div class="flex items-center gap-1.5 mt-2 text-[10.5px] text-ink-500">
                                    <div class="avatar avatar-xs" style="background:<?= Helpers::colorFor($d['email'] ?? $d['lead_code']) ?>;color:white;width:18px;height:18px;font-size:8px"><?= Helpers::initials(trim(($d['first_name']??'').' '.($d['last_name']??''))) ?></div>
                                    <span class="truncate"><?= $e(trim(($d['first_name']??'').' '.($d['last_name']??''))) ?></span>
                                </div>
                                <div class="flex items-center justify-between mt-2">
                                    <span class="font-mono font-extrabold text-[13px]">$<?= number_format((float)$d['amount'], 0) ?></span>
                                    <?php if ($d['expected_close_on']): ?>
                                        <span class="text-[10px] text-ink-400"><?= date('d M', strtotime($d['expected_close_on'])) ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($d['rating']==='hot'): ?>
                                    <span class="inline-flex items-center gap-1 text-[9.5px] font-bold mt-1.5 px-1.5 py-0.5 rounded-full" style="background:#fee2e2;color:#dc2626"><i class="lucide lucide-flame-kindling text-[9px]"></i> Hot</span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="card card-pad mt-4 flex flex-wrap items-center gap-4 text-[12px]">
    <div><span class="text-ink-400">Total oportunidades:</span> <strong><?= number_format($totalDeals) ?></strong></div>
    <div><span class="text-ink-400">Valor pipeline:</span> <strong class="font-mono">$<?= number_format($totalValue, 0) ?></strong></div>
    <?php if ($auth->can('crm.create')): ?>
        <a href="<?= $url('/t/' . $slug . '/crm/leads') ?>" class="ml-auto text-brand-700 font-semibold text-[12px]">→ Crear oportunidades desde un lead</a>
    <?php endif; ?>
</div>

<script>
(function(){
    const csrf = '<?= $e($csrf) ?>';
    const moveUrl = (id) => '<?= $url('/t/' . $slug . '/crm/deals/') ?>' + id + '/move';
    let dragId = null;
    document.querySelectorAll('.kanban-card').forEach(card => {
        card.addEventListener('dragstart', (e) => {
            dragId = card.dataset.dealId;
            e.dataTransfer.effectAllowed = 'move';
            card.style.opacity = '0.4';
        });
        card.addEventListener('dragend', () => { card.style.opacity = '1'; dragId = null; });
    });
    document.querySelectorAll('.kanban-list').forEach(list => {
        list.addEventListener('dragover', (e) => { e.preventDefault(); list.style.background = '#f3f0ff'; });
        list.addEventListener('dragleave', () => { list.style.background = ''; });
        list.addEventListener('drop', async (e) => {
            e.preventDefault();
            list.style.background = '';
            if (!dragId) return;
            const stageId = list.dataset.stageId;
            const fd = new FormData();
            fd.append('_csrf', csrf);
            fd.append('stage_id', stageId);
            try {
                const r = await fetch(moveUrl(dragId), { method:'POST', body: fd });
                if (r.ok) location.reload();
                else alert('No se pudo mover la oportunidad.');
            } catch (err) { alert('Error de conexión.'); }
        });
    });
})();
</script>
