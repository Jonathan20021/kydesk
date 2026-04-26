<?php
$slug = $tenant->slug; $t = $tenant->data;
$portalPath = '/portal/' . $slug;
$base = $url($portalPath);
if (!preg_match('#^https?://#i', $base)) {
    $scheme = $_SERVER['REQUEST_SCHEME'] ?? (!empty($_SERVER['HTTPS']) ? 'https' : 'http');
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base = $scheme . '://' . $host . $base;
}
$portalBase = $base;
$portalNew  = $base . '/new';
$portalKb   = $base . '/kb';
?>

<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
    <div>
        <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">Ajustes</h1>
        <p class="text-[13px] text-ink-400">Configura tu organización, marca y categorías</p>
    </div>
    <a href="<?= $url('/t/' . $slug . '/users') ?>" class="btn btn-outline btn-sm"><i class="lucide lucide-users"></i> Gestionar equipo</a>
</div>

<!-- Portal público widget -->
<div class="rounded-[24px] p-6 relative overflow-hidden max-w-6xl" style="background:linear-gradient(135deg,#0f0d18 0%,#1a1530 60%,#2a1f3d 100%);color:white;box-shadow:0 16px 40px -12px rgba(124,92,255,.3)">
    <div class="absolute inset-0 pointer-events-none" style="background:radial-gradient(circle at 90% 50%,rgba(124,92,255,.3),transparent 60%),radial-gradient(circle at 10% 100%,rgba(217,70,239,.18),transparent 55%)"></div>

    <div class="relative">
        <div class="flex items-start gap-4 flex-wrap">
            <div class="w-12 h-12 rounded-2xl grid place-items-center flex-shrink-0" style="background:linear-gradient(135deg,#7c5cff,#a78bfa);box-shadow:0 8px 20px -6px rgba(124,92,255,.6)"><i class="lucide lucide-globe text-[20px]"></i></div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                    <span class="font-display font-extrabold text-[18px] tracking-[-0.02em]">Portal público de soporte</span>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-[0.12em]" style="background:rgba(34,197,94,.18);color:#86efac;border:1px solid rgba(34,197,94,.3)"><span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> EN VIVO</span>
                </div>
                <p class="text-[13px]" style="color:rgba(255,255,255,.65)">Tus clientes crean y siguen tickets desde estos enlaces. Sin login, sin registro.</p>
            </div>
        </div>

        <div class="mt-5 grid grid-cols-1 md:grid-cols-3 gap-3">
            <?php foreach ([
                ['Crear ticket', $portalNew, 'plus-circle', 'Para abrir nuevos casos'],
                ['Inicio del portal', $portalBase, 'home', 'Página de bienvenida'],
                ['Base de conocimiento', $portalKb, 'book-open', 'Artículos públicos'],
            ] as $i => [$lbl, $u, $ic, $desc]): ?>
                <div class="rounded-2xl p-4 relative" style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08)">
                    <div class="flex items-center gap-2 mb-2">
                        <i class="lucide lucide-<?= $ic ?> text-[14px]" style="color:#c4b5fd"></i>
                        <span class="font-display font-bold text-[12.5px]"><?= $lbl ?></span>
                    </div>
                    <div class="text-[10.5px] mb-2" style="color:rgba(255,255,255,.45)"><?= $desc ?></div>
                    <div class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg font-mono text-[10.5px]" style="background:rgba(0,0,0,.3);color:rgba(255,255,255,.85)">
                        <span class="flex-1 truncate" id="portal-link-<?= $i ?>"><?= $e($u) ?></span>
                        <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('portal-link-<?= $i ?>').textContent); this.style.color='#86efac'; setTimeout(()=>this.style.color='',1500)" class="flex-shrink-0 p-1 rounded transition" style="color:rgba(255,255,255,.5)" data-tooltip="Copiar"><i class="lucide lucide-copy text-[12px]"></i></button>
                        <a href="<?= $e($u) ?>" target="_blank" class="flex-shrink-0 p-1 rounded transition hover:text-white" style="color:rgba(255,255,255,.5)" data-tooltip="Abrir"><i class="lucide lucide-external-link text-[12px]"></i></a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-5 pt-5 flex flex-wrap items-center gap-3" style="border-top:1px solid rgba(255,255,255,.08)">
            <div class="text-[12px] flex items-center gap-2" style="color:rgba(255,255,255,.55)">
                <i class="lucide lucide-info text-[13px]"></i>
                Compártelos por email, chat o en tu sitio. Cada ticket creado desde el portal aparece automáticamente en tu bandeja.
            </div>
            <a href="<?= $portalNew ?>" target="_blank" class="ml-auto inline-flex items-center gap-1.5 h-9 px-4 rounded-lg font-semibold text-[12.5px] transition" style="background:white;color:#0f0d18">Probar portal <i class="lucide lucide-arrow-up-right text-[13px]"></i></a>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 max-w-6xl">
    <form method="POST" action="<?= $url('/t/' . $slug . '/settings') ?>" class="lg:col-span-2 card card-pad">
        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

        <div class="section-head">
            <div class="section-head-icon"><i class="lucide lucide-building-2 text-[16px]"></i></div>
            <div>
                <h3 class="section-title">Organización</h3>
                <div class="section-head-meta">Información general de tu workspace</div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="label">Nombre</label><input name="name" value="<?= $e($t['name']) ?>" class="input"></div>
            <div><label class="label">URL del panel</label><input value="/t/<?= $e($t['slug']) ?>" disabled class="input font-mono"></div>
            <div><label class="label">Email de soporte</label><input name="support_email" type="email" value="<?= $e($t['support_email']) ?>" placeholder="soporte@empresa.com" class="input"></div>
            <div><label class="label">Sitio web</label><input name="website" value="<?= $e($t['website']) ?>" placeholder="https://..." class="input"></div>
        </div>

        <div class="section-head mt-8">
            <div class="section-head-icon"><i class="lucide lucide-palette text-[16px]"></i></div>
            <div>
                <h3 class="section-title">Marca y región</h3>
                <div class="section-head-meta">Apariencia y zona horaria del workspace</div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="label">Color primario</label>
                <div class="swatch-wrap">
                    <input name="primary_color" type="color" value="<?= $e($t['primary_color']) ?>" oninput="document.getElementById('hexPrev').value=this.value">
                    <input id="hexPrev" value="<?= $e($t['primary_color']) ?>" class="font-mono text-[13px] border-0 bg-transparent flex-1 outline-none" readonly>
                </div>
            </div>
            <div>
                <label class="label">Zona horaria</label>
                <select name="timezone" class="input">
                    <?php foreach (['America/Santo_Domingo','America/Mexico_City','America/Guatemala','America/Bogota','America/Lima','America/Santiago','America/Buenos_Aires','Europe/Madrid','UTC'] as $tz): ?><option value="<?= $tz ?>" <?= $t['timezone']===$tz?'selected':'' ?>><?= $tz ?></option><?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="flex justify-end pt-6 mt-6 gap-2 border-t border-[#ececef]">
            <button class="btn btn-primary btn-sm"><i class="lucide lucide-save"></i> Guardar ajustes</button>
        </div>
    </form>

    <div class="space-y-4">
        <div class="plan-card">
            <div class="relative">
                <div class="flex items-center gap-2.5 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur grid place-items-center"><i class="lucide lucide-sparkles text-base"></i></div>
                    <span class="text-[10.5px] font-bold uppercase tracking-[0.18em] opacity-80">Plan actual</span>
                </div>
                <div class="font-display font-extrabold text-[28px] leading-none tracking-[-0.025em]"><?= $e(strtoupper($t['plan'])) ?></div>
                <p class="text-[12.5px] mt-2 opacity-85">Activo desde <?= date('d/m/Y', strtotime($t['created_at'])) ?></p>
                <a href="<?= $url('/pricing') ?>" class="inline-flex items-center gap-1.5 mt-5 px-4 py-2 rounded-full bg-white text-brand-700 font-semibold text-[12.5px]">Ver planes <i class="lucide lucide-arrow-right text-[14px]"></i></a>
            </div>
        </div>

        <div class="card card-pad">
            <div class="section-head">
                <div class="section-head-icon"><i class="lucide lucide-info text-[16px]"></i></div>
                <h3 class="section-title">Recursos</h3>
            </div>
            <ul class="space-y-2.5 text-[13px]">
                <li><a href="<?= $url('/t/' . $tenant->slug . '/help') ?>" class="flex items-center gap-2 text-ink-700 hover:text-brand-700"><i class="lucide lucide-book-open text-[14px] text-ink-400"></i> Centro de ayuda</a></li>
                <li><a href="<?= $url('/t/' . $tenant->slug . '/api-docs') ?>" class="flex items-center gap-2 text-ink-700 hover:text-brand-700"><i class="lucide lucide-file-text text-[14px] text-ink-400"></i> Documentación API</a></li>
                <li><a href="<?= $url('/t/' . $tenant->slug . '/support') ?>" class="flex items-center gap-2 text-ink-700 hover:text-brand-700"><i class="lucide lucide-message-circle text-[14px] text-ink-400"></i> Contactar soporte</a></li>
            </ul>
        </div>
    </div>
</div>

<div class="card card-pad max-w-6xl">
    <div class="section-head">
        <div class="section-head-icon"><i class="lucide lucide-folder-tree text-[16px]"></i></div>
        <div class="flex-1">
            <h3 class="section-title">Categorías de tickets</h3>
            <div class="section-head-meta"><?= count($categories) ?> categorías configuradas</div>
        </div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
        <?php foreach ($categories as $c): ?>
            <div class="flex items-center gap-3 p-3.5 rounded-2xl border border-[#ececef] hover:border-brand-200 transition">
                <div class="w-11 h-11 rounded-xl text-white grid place-items-center flex-shrink-0" style="background:<?= $e($c['color']) ?>"><i class="lucide lucide-<?= $e($c['icon']) ?> text-[15px]"></i></div>
                <div class="min-w-0 flex-1">
                    <div class="font-display font-bold text-[13.5px] truncate"><?= $e($c['name']) ?></div>
                    <div class="text-[10.5px] font-mono text-ink-400"><?= $e($c['color']) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
