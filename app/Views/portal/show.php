<?php
use App\Core\Helpers;
$t = $tenant; $tk = $ticket;
$brand = $t->data['primary_color'] ?? '#7c5cff';
$brandRgb = sscanf($brand, "#%02x%02x%02x");
$rgbStr = $brandRgb ? implode(',', $brandRgb) : '124,92,255';
?>
<nav class="fixed top-4 inset-x-0 z-50 px-4">
    <div class="nav-land">
        <div class="nav-land-inner">
            <a href="<?= $url('/portal/' . $t->slug) ?>" class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl text-white grid place-items-center font-display font-bold text-[14px]" style="background:<?= $e($brand) ?>;box-shadow:0 6px 14px -4px rgba(<?= $rgbStr ?>,.45)"><?= strtoupper(substr($t->name,0,1)) ?></div>
                <div class="leading-tight">
                    <div class="font-display font-extrabold text-[15px] tracking-[-0.015em]"><?= $e($t->name) ?></div>
                    <div class="text-[10px] text-ink-400 uppercase tracking-[0.12em]">Centro de soporte</div>
                </div>
            </a>
            <div class="flex items-center gap-1.5 ml-auto">
                <a href="https://kydesk.kyrosrd.com" target="_blank" rel="noopener" class="hidden sm:inline-flex items-center gap-1.5 text-[11px] text-ink-400 hover:text-ink-900 transition">
                    Powered by <span class="font-display font-bold text-ink-900">Kydesk</span>
                </a>
                <a href="<?= $url('/portal/' . $t->slug) ?>" class="btn btn-ghost btn-sm"><i class="lucide lucide-home text-[13px]"></i> Inicio</a>
            </div>
        </div>
    </div>
</nav>
<div class="h-[88px]"></div>
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
