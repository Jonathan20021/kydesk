<?php
use App\Core\Helpers;
$slug = $tenant->slug;
$statusLabels = [
    'new'         => ['Nuevo',       '#94a3b8'],
    'contacted'   => ['Contactado',  '#3b82f6'],
    'qualified'   => ['Calificado',  '#0ea5e9'],
    'proposal'    => ['Propuesta',   '#a78bfa'],
    'negotiation' => ['Negociación', '#f59e0b'],
    'customer'    => ['Cliente',     '#16a34a'],
    'lost'        => ['Perdido',     '#ef4444'],
    'archived'    => ['Archivado',   '#6b7280'],
];
$ratingLabels = [
    'cold' => ['Frío', '#3b82f6', 'snowflake'],
    'warm' => ['Tibio', '#f59e0b', 'flame'],
    'hot'  => ['Caliente', '#ef4444', 'flame-kindling'],
];
?>

<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-5">
    <div>
        <a href="<?= $url('/t/' . $slug . '/crm') ?>" class="text-[11.5px] font-semibold text-brand-700 hover:underline inline-flex items-center gap-1 mb-1.5"><i class="lucide lucide-arrow-left text-[11px]"></i> Volver al CRM</a>
        <h1 class="font-display font-extrabold text-[26px] tracking-[-0.025em]">Leads y Clientes</h1>
        <p class="text-[12.5px] text-ink-400">Gestioná todo el funnel comercial · contactos, oportunidades y conversión.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="<?= $url('/t/' . $slug . '/crm/pipeline') ?>" class="btn btn-soft btn-sm"><i class="lucide lucide-kanban-square"></i> Pipeline</a>
        <?php if ($auth->can('crm.create')): ?>
            <a href="<?= $url('/t/' . $slug . '/crm/leads/create') ?>" class="btn btn-primary btn-sm"><i class="lucide lucide-plus"></i> Nuevo lead</a>
        <?php endif; ?>
    </div>
</div>

<form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-2 mb-3">
    <div class="search-pill md:col-span-2"><i class="lucide lucide-search"></i><input name="q" value="<?= $e($q) ?>" placeholder="Buscar por nombre, email, código…"></div>
    <select name="status" class="input">
        <option value="">Todos los estados</option>
        <?php foreach ($statusLabels as $k => [$lbl,]): ?>
            <option value="<?= $k ?>" <?= $status===$k?'selected':'' ?>><?= $lbl ?></option>
        <?php endforeach; ?>
    </select>
    <select name="rating" class="input">
        <option value="">Todos los ratings</option>
        <?php foreach ($ratingLabels as $k => [$lbl,]): ?>
            <option value="<?= $k ?>" <?= $rating===$k?'selected':'' ?>><?= $lbl ?></option>
        <?php endforeach; ?>
    </select>
    <select name="source_id" class="input">
        <option value="0">Todos los orígenes</option>
        <?php foreach ($sources as $s): ?>
            <option value="<?= (int)$s['id'] ?>" <?= $sourceId===(int)$s['id']?'selected':'' ?>><?= $e($s['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="owner_id" class="input">
        <option value="0">Todos los owners</option>
        <?php foreach ($owners as $o): ?>
            <option value="<?= (int)$o['id'] ?>" <?= $ownerId===(int)$o['id']?'selected':'' ?>><?= $e($o['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <input type="hidden" name="tag_id" value="<?= (int)$tagId ?>" id="tagFilter">
    <button class="btn btn-soft btn-sm md:col-span-1"><i class="lucide lucide-filter text-[13px]"></i> Filtrar</button>
</form>

<?php if (!empty($tags)): ?>
    <div class="flex items-center gap-1.5 flex-wrap mb-4 overflow-x-auto pb-1">
        <a href="<?= $url('/t/' . $slug . '/crm/leads' . ($q!==''?'?q='.urlencode($q):'')) ?>"
           class="inline-flex items-center gap-1.5 text-[12px] font-semibold px-2.5 py-1 rounded-full border <?= $tagId===0 ? 'bg-ink-900 text-white border-ink-900' : 'bg-white border-[#ececef] text-ink-500' ?>">
            <i class="lucide lucide-tags text-[12px]"></i> Todos
        </a>
        <?php foreach ($tags as $t):
            $active = (int)$t['id'] === $tagId;
            $params = ['tag_id' => $t['id']];
            if ($q !== '') $params['q'] = $q;
            if ($status !== '') $params['status'] = $status;
        ?>
            <a href="<?= $url('/t/' . $slug . '/crm/leads?' . http_build_query($params)) ?>"
               class="inline-flex items-center gap-1.5 text-[12px] font-semibold px-2.5 py-1 rounded-full border transition"
               style="<?= $active ? 'background:'.$t['color'].';border-color:'.$t['color'].';color:white' : 'background:'.$t['color'].'12;border-color:'.$t['color'].'33;color:'.$t['color'] ?>">
                <i class="lucide lucide-tag text-[12px]"></i> <?= $e($t['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (empty($leads)): ?>
    <div class="card card-pad text-center py-20">
        <div class="w-16 h-16 rounded-2xl bg-brand-50 grid place-items-center mx-auto mb-4"><i class="lucide lucide-contact-round text-[26px] text-brand-600"></i></div>
        <h3 class="font-display font-bold text-[18px]">Sin leads</h3>
        <p class="text-[13px] text-ink-400 mt-1 max-w-md mx-auto">Empezá agregando tus primeros prospectos. Cargalos manualmente, importálos por CSV o conectalos al formulario público de tu sitio.</p>
        <?php if ($auth->can('crm.create')): ?>
            <a href="<?= $url('/t/' . $slug . '/crm/leads/create') ?>" class="btn btn-primary btn-sm mt-4 inline-flex"><i class="lucide lucide-plus"></i> Crear el primero</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="card overflow-hidden">
        <table class="admin-table" style="width:100%">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Lead</th>
                    <th>Empresa</th>
                    <th>Origen</th>
                    <th>Rating</th>
                    <th>Estado</th>
                    <th>Owner</th>
                    <th class="text-right">Valor</th>
                    <th class="text-right">Deals</th>
                    <th>Próximo</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($leads as $l):
                    [$sl, $sCol] = $statusLabels[$l['status']] ?? ['—', '#6b7280'];
                    [$rl, $rCol, $rIc] = $ratingLabels[$l['rating']] ?? ['—', '#6b7280', 'circle'];
                ?>
                    <tr style="cursor:pointer" onclick="location='<?= $url('/t/' . $slug . '/crm/leads/' . (int)$l['id']) ?>'">
                        <td class="font-mono text-[11.5px] text-ink-500"><?= $e($l['code']) ?></td>
                        <td>
                            <div class="flex items-center gap-2">
                                <div class="avatar avatar-sm" style="background:<?= Helpers::colorFor($l['email'] ?? $l['code']) ?>;color:white"><?= Helpers::initials(trim(($l['first_name']??'').' '.($l['last_name']??''))) ?></div>
                                <div>
                                    <div class="font-display font-bold text-[13px]"><?= $e(trim(($l['first_name']??'').' '.($l['last_name']??''))) ?></div>
                                    <div class="text-[11px] text-ink-400"><?= $e($l['email'] ?? $l['phone'] ?? '—') ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="text-[12.5px]"><?= $e($l['company_name'] ?? '—') ?></td>
                        <td>
                            <?php if (!empty($l['source_name'])): ?>
                                <span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded-full" style="background:<?= $e($l['source_color']) ?>1a;color:<?= $e($l['source_color']) ?>"><i class="lucide lucide-<?= $e($l['source_icon']) ?> text-[10px]"></i> <?= $e($l['source_name']) ?></span>
                            <?php else: ?>
                                <span class="text-[11px] text-ink-400">—</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-full" style="background:<?= $rCol ?>1a;color:<?= $rCol ?>"><i class="lucide lucide-<?= $rIc ?> text-[10px]"></i> <?= $e($rl) ?></span></td>
                        <td><span class="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-full" style="background:<?= $sCol ?>1a;color:<?= $sCol ?>"><?= $e($sl) ?></span></td>
                        <td class="text-[12px]"><?= $e($l['owner_name'] ?? '—') ?></td>
                        <td class="text-right font-mono font-bold text-[12.5px]"><?= $l['estimated_value'] > 0 ? '$' . number_format((float)$l['estimated_value'], 0) : '—' ?></td>
                        <td class="text-right text-[12px]"><?= (int)$l['deals_count'] ?></td>
                        <td class="text-[11px] text-ink-500"><?= $l['next_followup_at'] ? date('d M, H:i', strtotime($l['next_followup_at'])) : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="text-[11px] text-ink-400 mt-2">Mostrando <?= count($leads) ?> resultados.</div>
<?php endif; ?>
