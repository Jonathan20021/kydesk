<?php use App\Core\Helpers; $slug = $tenant->slug; ?>

<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
    <div>
        <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">Base de conocimiento</h1>
        <p class="text-[13px] text-ink-400"><?= number_format($counts['published']) ?> publicados · <?= number_format($counts['draft']) ?> borradores · <?= number_format($counts['views']) ?> vistas</p>
    </div>
    <?php if ($auth->can('kb.create')): ?>
        <a href="<?= $url('/t/' . $slug . '/kb/create') ?>" class="btn btn-primary btn-sm"><i class="lucide lucide-plus"></i> Nuevo artículo</a>
    <?php endif; ?>
</div>

<?php if (!empty($categories)): ?>
<div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
    <?php foreach ($categories as $c): ?>
        <a href="<?= $url('/t/' . $slug . '/kb?category=' . $c['id']) ?>" class="card card-pad spotlight-card hover:-translate-y-1 transition group" style="padding:20px;">
            <div class="bento-glow"></div>
            <div class="w-12 h-12 rounded-2xl text-white grid place-items-center shadow-lg" style="background:<?= $e($c['color']) ?>;box-shadow:0 6px 16px -4px <?= $e($c['color']) ?>66"><i class="lucide lucide-<?= $e($c['icon']) ?> text-base"></i></div>
            <div class="mt-4 font-display font-bold text-[14px] tracking-[-0.015em] truncate"><?= $e($c['name']) ?></div>
            <div class="text-[11.5px] mt-1 text-ink-400 line-clamp-2 min-h-[28px]"><?= $e($c['description'] ?? 'Explora artículos de esta categoría') ?></div>
            <div class="mt-3 inline-flex items-center gap-1 text-[11.5px] font-semibold text-brand-700 opacity-0 group-hover:opacity-100 transition">Ver <i class="lucide lucide-arrow-right text-[12px]"></i></div>
        </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<form method="GET" class="flex gap-3">
    <div class="search-pill flex-1" style="max-width:none"><i class="lucide lucide-search"></i><input name="q" value="<?= $e($q) ?>" placeholder="Buscar artículos…"></div>
    <select name="status" class="input" style="max-width:200px;height:44px;border-radius:999px;">
        <option value="">Cualquier estado</option>
        <option value="published" <?= $statusFilter==='published'?'selected':'' ?>>Publicados</option>
        <option value="draft" <?= $statusFilter==='draft'?'selected':'' ?>>Borradores</option>
    </select>
</form>

<div class="card overflow-hidden">
    <table class="table">
        <thead><tr><th>Artículo</th><th>Categoría</th><th>Estado</th><th>Vistas</th><th>Autor</th><th>Actualizado</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($articles as $a): ?>
                <tr onclick="location.href='<?= $url('/t/' . $slug . '/kb/' . $a['id']) ?>'" style="cursor:pointer">
                    <td>
                        <div class="font-display font-bold text-[13.5px] line-clamp-1 max-w-[420px]"><?= $e($a['title']) ?></div>
                        <div class="text-[11.5px] mt-0.5 line-clamp-1 max-w-[420px] text-ink-400"><?= $e($a['excerpt'] ?? '') ?></div>
                    </td>
                    <td>
                        <?php if ($a['cat_name']): ?>
                            <span class="badge badge-purple"><span class="dot" style="background:<?= $e($a['cat_color']) ?>"></span> <?= $e($a['cat_name']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge <?= $a['status']==='published'?'badge-emerald':'badge-gray' ?>"><?= $a['status']==='published'?'Publicado':'Borrador' ?></span></td>
                    <td class="font-mono text-[12.5px]"><?= number_format($a['views']) ?></td>
                    <td class="text-[12px] text-ink-500"><?= $e($a['author'] ?? '—') ?></td>
                    <td class="text-[11.5px] text-ink-400"><?= Helpers::ago($a['updated_at']) ?></td>
                    <td><span class="table-action"><i class="lucide lucide-arrow-up-right text-[13px]"></i></span></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($articles)): ?>
                <tr><td colspan="7">
                    <div class="empty-state py-16">
                        <div class="empty-illust"><i class="lucide lucide-book-open text-[26px]"></i></div>
                        <div class="empty-state-title">Sin artículos publicados</div>
                        <p class="empty-state-text mb-5">Crea tu primer artículo para empezar a compartir conocimiento</p>
                        <?php if ($auth->can('kb.create')): ?>
                            <a href="<?= $url('/t/' . $slug . '/kb/create') ?>" class="btn btn-primary btn-sm"><i class="lucide lucide-plus"></i> Nuevo artículo</a>
                        <?php endif; ?>
                    </div>
                </td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
