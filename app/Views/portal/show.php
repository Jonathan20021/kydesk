<?php use App\Core\Helpers; $t = $tenant; $tk = $ticket; ?>
<nav class="bg-white border-b border-[#ececef]">
    <div class="max-w-[1100px] mx-auto px-6 h-[68px] flex items-center justify-between">
        <a href="<?= $url('/portal/' . $t->slug) ?>" class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl text-white grid place-items-center font-display font-bold" style="background:<?= $e($t->data['primary_color'] ?? '#7c5cff') ?>"><?= strtoupper(substr($t->name,0,1)) ?></div>
            <div class="font-display font-bold text-[14px]"><?= $e($t->name) ?></div>
        </a>
    </div>
</nav>
<section class="py-10">
    <div class="max-w-[820px] mx-auto px-6 space-y-5">
        <div>
            <div class="flex flex-wrap items-center gap-2 mb-2.5">
                <span class="kbd"><?= $e($tk['code']) ?></span>
                <?= Helpers::priorityBadge($tk['priority']) ?>
                <?= Helpers::statusBadge($tk['status']) ?>
            </div>
            <h1 class="heading-md"><?= $e($tk['subject']) ?></h1>
            <div class="mt-2 text-[12px] text-ink-400">Creado <?= Helpers::ago($tk['created_at']) ?></div>
        </div>
        <div class="card card-pad whitespace-pre-wrap text-[14px] leading-relaxed"><?= $e($tk['description']) ?></div>
        <div class="card overflow-hidden">
            <div class="px-6 pt-5"><h3 class="section-title">Conversación</h3></div>
            <div class="px-6 pb-4 mt-4 space-y-4">
                <?php foreach ($comments as $c): $mine = !$c['user_id']; ?>
                    <div class="flex items-start gap-3 <?= $mine?'flex-row-reverse':'' ?>">
                        <div class="avatar avatar-md text-white" style="background: <?= Helpers::colorFor($c['author_email'] ?? '') ?>"><?= Helpers::initials($c['user_name'] ?? $c['author_name'] ?? 'U') ?></div>
                        <div class="flex-1 min-w-0 <?= $mine?'text-right':'' ?>">
                            <div class="inline-block max-w-full px-4 py-3 rounded-2xl text-left <?= $mine?'bg-[#f3f4f6]':'bg-brand-500 text-white' ?>"><div class="text-[13.5px] whitespace-pre-wrap leading-relaxed"><?= $e($c['body']) ?></div></div>
                            <div class="mt-1 text-[11px] text-ink-400"><?= $e($c['user_name'] ?? $c['author_name'] ?? '—') ?> · <?= Helpers::ago($c['created_at']) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($comments)): ?><div class="text-center py-6 text-[13px] text-ink-400">Sin respuestas aún</div><?php endif; ?>
            </div>
        </div>
        <form method="POST" action="<?= $url('/portal/' . $t->slug . '/ticket/' . $tk['public_token'] . '/reply') ?>" class="card card-pad">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
            <label class="label">Agregar mensaje</label>
            <textarea name="body" required rows="4" class="input"></textarea>
            <div class="mt-3 flex justify-end"><button class="btn btn-primary btn-sm"><i class="lucide lucide-send"></i> Enviar</button></div>
        </form>
    </div>
</section>
