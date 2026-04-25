<?php use App\Core\Helpers; $slug = $tenant->slug; $a = $article; ?>
<a href="<?= $url('/t/' . $slug . '/kb') ?>" class="inline-flex items-center gap-1.5 text-[13px] text-ink-500"><i class="lucide lucide-arrow-left"></i> Base de conocimiento</a>

<article class="card card-pad max-w-3xl space-y-4">
    <div class="flex items-center gap-2 flex-wrap">
        <?php if ($a['cat_name']): ?><span class="badge badge-purple"><span class="dot" style="background:<?= $e($a['cat_color']) ?>"></span> <?= $e($a['cat_name']) ?></span><?php endif; ?>
        <span class="badge <?= $a['status']==='published'?'badge-emerald':'badge-gray' ?>"><?= $a['status']==='published'?'Publicado':'Borrador' ?></span>
        <span class="badge badge-gray"><?= $a['visibility']==='public'?'Público':'Interno' ?></span>
    </div>
    <h1 class="font-display font-extrabold text-[34px] tracking-[-0.03em] leading-tight"><?= $e($a['title']) ?></h1>
    <?php if ($a['excerpt']): ?><p class="text-[16px] leading-relaxed text-ink-500"><?= $e($a['excerpt']) ?></p><?php endif; ?>
    <div class="flex items-center justify-between pb-4 border-b border-[#ececef] text-[12px] text-ink-400">
        <div class="flex items-center gap-2">
            <?php if ($a['author']): ?>
                <div class="avatar avatar-sm" style="background:<?= Helpers::colorFor($a['author']) ?>;color:white"><?= Helpers::initials($a['author']) ?></div>
                <span><?= $e($a['author']) ?></span><span>·</span>
            <?php endif; ?>
            <span><?= Helpers::ago($a['updated_at']) ?></span>
        </div>
        <div class="flex items-center gap-3">
            <span class="flex items-center gap-1"><i class="lucide lucide-eye text-[13px]"></i> <?= number_format($a['views']) ?></span>
            <span class="flex items-center gap-1 text-emerald-600"><i class="lucide lucide-thumbs-up text-[13px]"></i> <?= $a['helpful_yes'] ?></span>
            <span class="flex items-center gap-1"><i class="lucide lucide-thumbs-down text-[13px]"></i> <?= $a['helpful_no'] ?></span>
        </div>
    </div>
    <div class="text-[14.5px] leading-[1.7] whitespace-pre-wrap"><?= $e($a['body']) ?></div>
    <?php if ($auth->can('kb.delete')): ?>
        <div class="pt-4 flex justify-end border-t border-[#ececef]">
            <form method="POST" action="<?= $url('/t/' . $slug . '/kb/' . $a['id'] . '/delete') ?>" onsubmit="return confirm('¿Eliminar?')">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <button class="btn btn-outline btn-sm" style="color:#ef4444;border-color:#fecaca;background:#fef2f2"><i class="lucide lucide-trash-2"></i> Eliminar</button>
            </form>
        </div>
    <?php endif; ?>
</article>
