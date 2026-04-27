<?php
$f = $feature;
$siblings = array_filter($allFeatures, fn($k) => $k !== $featureKey, ARRAY_FILTER_USE_KEY);
include APP_PATH . '/Views/partials/landing_nav.php';
?>

<!-- HERO -->
<section class="relative pt-36 pb-16 overflow-hidden">
    <div class="aurora-bg">
        <div class="aurora-blob b1" style="background:radial-gradient(circle,<?= $f['color'] ?>aa,transparent 70%)"></div>
        <div class="aurora-blob b2"></div>
        <div class="aurora-blob b3"></div>
    </div>
    <div class="grid-bg"></div>

    <div class="max-w-[1240px] mx-auto px-6 relative">
        <a href="<?= $url('/') ?>#features" class="inline-flex items-center gap-1.5 text-[12.5px] text-ink-500 hover:text-ink-900 transition mb-6"><i class="lucide lucide-arrow-left text-[13px]"></i> Todas las funcionalidades</a>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
            <div class="lg:col-span-7">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-[11px] font-bold uppercase tracking-[0.16em]" style="background:<?= $f['bg'] ?>;color:<?= $f['color'] ?>;border:1px solid <?= $f['color'] ?>30">
                    <i class="lucide lucide-<?= $f['icon'] ?> text-[12px]"></i> <?= $e($f['category']) ?>
                </div>

                <h1 class="display-xl mt-7" style="text-wrap:balance;font-size:clamp(2.4rem,4.5vw + 1rem,4.5rem)">
                    <?= $e($f['title']) ?>
                </h1>

                <p class="mt-6 text-[18px] text-ink-500 max-w-xl leading-relaxed"><?= $e($f['tagline']) ?></p>

                <div class="mt-8 flex flex-col sm:flex-row gap-3">
                    <a href="<?= $url('/demo') ?>" class="btn btn-lg glow-purple" style="background:linear-gradient(135deg,<?= $f['color'] ?>,<?= $f['color'] ?>cc);color:white"><i class="lucide lucide-play"></i> Probar en demo</a>
                    <a href="<?= $url('/auth/register') ?>" class="btn btn-outline btn-lg">Crear cuenta gratis <i class="lucide lucide-arrow-right"></i></a>
                </div>

                <div class="mt-10 grid grid-cols-3 gap-4 max-w-lg">
                    <?php foreach ($f['hero_kpis'] as [$lbl, $val]): ?>
                        <div class="border-t-2 pt-3" style="border-color:<?= $f['color'] ?>">
                            <div class="font-display font-extrabold text-[24px] tracking-[-0.025em]" style="color:<?= $f['color'] ?>"><?= $e($val) ?></div>
                            <div class="text-[11px] text-ink-500 mt-1"><?= $e($lbl) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="lg:col-span-5">
                <div class="relative rounded-[28px] p-8 overflow-hidden" style="background:linear-gradient(135deg,<?= $f['bg'] ?>,white);border:1px solid <?= $f['color'] ?>30;box-shadow:0 30px 60px -20px <?= $f['color'] ?>40">
                    <div class="absolute -top-8 -right-8 w-48 h-48 rounded-full" style="background:radial-gradient(circle,<?= $f['color'] ?>30,transparent 70%);filter:blur(20px)"></div>
                    <div class="relative">
                        <div class="w-20 h-20 rounded-3xl grid place-items-center mb-6" style="background:linear-gradient(135deg,<?= $f['color'] ?>,<?= $f['color'] ?>cc);color:white;box-shadow:0 16px 40px -10px <?= $f['color'] ?>80">
                            <i class="lucide lucide-<?= $f['icon'] ?> text-[36px]"></i>
                        </div>
                        <p class="text-[14.5px] leading-relaxed text-ink-700"><?= $e($f['description']) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- HOW IT WORKS -->
<section class="py-24 border-t border-[#ececef]">
    <div class="max-w-[1240px] mx-auto px-6">
        <div class="text-center max-w-2xl mx-auto">
            <div class="text-[11.5px] font-bold uppercase tracking-[0.18em] mb-3" style="color:<?= $f['color'] ?>">CÓMO FUNCIONA</div>
            <h2 class="display-xl" style="font-size:clamp(2rem,3.5vw + 1rem,3rem);text-wrap:balance">3 pasos · Setup en minutos</h2>
        </div>

        <div class="mt-14 grid grid-cols-1 md:grid-cols-3 gap-5 relative">
            <div class="hidden md:block absolute top-12 left-[16.66%] right-[16.66%] h-px" style="background:linear-gradient(90deg,transparent,<?= $f['color'] ?>40,<?= $f['color'] ?>40,transparent);z-index:0"></div>
            <?php foreach ($f['steps'] as $i => [$title, $desc, $ic]): ?>
                <div class="relative bg-white">
                    <div class="w-12 h-12 rounded-2xl mx-auto grid place-items-center relative z-10" style="background:white;color:<?= $f['color'] ?>;border:2px solid <?= $f['color'] ?>;box-shadow:0 8px 16px -4px <?= $f['color'] ?>40">
                        <i class="lucide lucide-<?= $ic ?> text-[20px]"></i>
                    </div>
                    <div class="mt-3 text-center text-[10.5px] font-bold tracking-[0.16em] uppercase" style="color:<?= $f['color'] ?>">Paso <?= $i+1 ?></div>
                    <h3 class="mt-2 font-display font-extrabold text-[18px] text-center tracking-[-0.015em]"><?= $e($title) ?></h3>
                    <p class="mt-2 text-[13.5px] text-ink-500 text-center leading-relaxed max-w-xs mx-auto"><?= $e($desc) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- BENEFITS -->
<section class="py-24" style="background:linear-gradient(180deg,#fafafb,white)">
    <div class="max-w-[1240px] mx-auto px-6">
        <div class="text-center max-w-2xl mx-auto mb-14">
            <div class="text-[11.5px] font-bold uppercase tracking-[0.18em] mb-3" style="color:<?= $f['color'] ?>">BENEFICIOS</div>
            <h2 class="display-xl" style="font-size:clamp(2rem,3.5vw + 1rem,3rem);text-wrap:balance">Lo que tu equipo gana</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <?php foreach ($f['benefits'] as [$ic, $title, $desc]): ?>
                <div class="bento spotlight-card" style="border-color:<?= $f['color'] ?>20">
                    <div class="bento-glow"></div>
                    <div class="bento-icon" style="background:<?= $f['bg'] ?>;color:<?= $f['color'] ?>">
                        <i class="lucide lucide-<?= $ic ?> text-[22px] relative z-10"></i>
                    </div>
                    <h3 class="font-display font-extrabold text-[17px] mt-5 tracking-[-0.015em]"><?= $e($title) ?></h3>
                    <p class="text-[13px] text-ink-500 mt-2 leading-relaxed"><?= $e($desc) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php if ($featureKey === 'integrations'): ?>

<!-- ════════════════════ INTEGRATIONS · DASHBOARD MOCKUP ════════════════════ -->
<section class="py-24" style="background:linear-gradient(180deg,#fafafb,white)">
    <style>
        .ifx-mock { position:relative; max-width:1180px; margin:0 auto; border-radius:24px; overflow:hidden; box-shadow:0 60px 120px -30px rgba(22,21,27,.18); border:1px solid #ececef; background:white; }
        .ifx-mock-bar { display:flex; align-items:center; gap:8px; padding:12px 16px; background:#f9fafb; border-bottom:1px solid #ececef; }
        .ifx-mock-dot { width:11px; height:11px; border-radius:50%; }
        .ifx-mock-url { flex:1; text-align:center; font-family:'Geist Mono',monospace; font-size:11.5px; color:#8e8e9a; display:inline-flex; justify-content:center; align-items:center; gap:6px; }
        .ifx-mock-body { display:grid; grid-template-columns:220px 1fr; min-height:580px; }
        @media (max-width:900px) { .ifx-mock-body { grid-template-columns:1fr; } .ifx-mock-side { display:none; } }
        .ifx-mock-side { background:#fafbfc; border-right:1px solid #ececef; padding:16px 12px; }
        .ifx-mock-side-title { font-size:10px; font-weight:700; letter-spacing:.16em; text-transform:uppercase; color:#8e8e9a; padding:6px 10px; }
        .ifx-mock-side-item { display:flex; align-items:center; gap:10px; padding:8px 10px; border-radius:9px; font-size:13px; color:#3d3d49; cursor:pointer; transition:background .15s; }
        .ifx-mock-side-item:hover { background:white; }
        .ifx-mock-side-item.active { background:#0ea5e91a; color:#0369a1; font-weight:600; position:relative; }
        .ifx-mock-side-item.active::before { content:''; position:absolute; left:0; top:50%; transform:translateY(-50%); width:3px; height:18px; border-radius:0 3px 3px 0; background:#0ea5e9; }
        .ifx-mock-side-item .badge { margin-left:auto; font-size:10.5px; font-weight:600; color:#8e8e9a; padding:1px 7px; border-radius:999px; background:#f3f4f6; }

        .ifx-mock-main { padding:24px 28px; background:white; }
        .ifx-mock-hero { padding:18px 22px; border-radius:18px; background:linear-gradient(135deg,#fff,#f3f0ff); border:1px solid #ececef; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; }
        .ifx-mock-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-top:16px; }
        .ifx-mock-stat { padding:14px; border-radius:14px; border:1px solid #ececef; background:white; }
        .ifx-mock-stat-label { font-size:9.5px; font-weight:700; letter-spacing:.14em; text-transform:uppercase; color:#8e8e9a; }
        .ifx-mock-stat-value { font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:22px; color:#16151b; margin-top:4px; letter-spacing:-.02em; }

        .ifx-mock-section-h { display:flex; align-items:center; justify-content:space-between; margin:22px 0 12px; }
        .ifx-mock-section-h h4 { font-family:'Plus Jakarta Sans',sans-serif; font-weight:700; font-size:13.5px; color:#16151b; }
        .ifx-mock-section-h .pill { font-size:10px; font-weight:600; padding:3px 9px; border-radius:999px; background:#f3f0ff; color:#5a3aff; }

        .ifx-mock-row { display:flex; align-items:center; gap:14px; padding:12px 14px; border-radius:14px; border:1px solid #ececef; transition:all .15s; cursor:pointer; }
        .ifx-mock-row + .ifx-mock-row { margin-top:8px; }
        .ifx-mock-row:hover { border-color:#0ea5e950; background:#f9fbff; }
        .ifx-mock-row-icon { width:36px; height:36px; border-radius:10px; display:grid; place-items:center; flex-shrink:0; }
        .ifx-mock-row-name { font-family:'Plus Jakarta Sans',sans-serif; font-weight:700; font-size:13px; color:#16151b; }
        .ifx-mock-row-meta { font-size:11px; color:#8e8e9a; margin-top:1px; }
        .ifx-mock-row-status { display:inline-flex; align-items:center; gap:5px; padding:2px 9px; border-radius:999px; font-size:10px; font-weight:700; letter-spacing:.04em; text-transform:uppercase; background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; }
        .ifx-mock-row-status::before { content:''; width:6px; height:6px; border-radius:50%; background:#16a34a; box-shadow:0 0 8px #16a34a; animation:ifx-pulse 1.6s ease-in-out infinite; }
        @keyframes ifx-pulse { 0%,100% { opacity:1; } 50% { opacity:.5; } }
    </style>

    <div class="max-w-[1240px] mx-auto px-6">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <div class="text-[11.5px] font-bold uppercase tracking-[0.18em] mb-3" style="color:<?= $f['color'] ?>">EL MARKETPLACE EN ACCIÓN</div>
            <h2 class="display-xl" style="font-size:clamp(2rem,3.5vw + 1rem,3rem);text-wrap:balance">Tu panel de integraciones</h2>
            <p class="mt-4 text-[15px] text-ink-500 max-w-xl mx-auto">Cada integración es una conexión viva — ves su salud, último evento entregado, errores y latencia en tiempo real.</p>
        </div>

        <div class="ifx-mock">
            <div class="ifx-mock-bar">
                <span class="ifx-mock-dot" style="background:#ff5f57"></span>
                <span class="ifx-mock-dot" style="background:#febc2e"></span>
                <span class="ifx-mock-dot" style="background:#28c840"></span>
                <div class="ifx-mock-url"><i class="lucide lucide-lock text-[10px]"></i> kydesk.kyrosrd.com / acme / integrations</div>
            </div>
            <div class="ifx-mock-body">
                <aside class="ifx-mock-side">
                    <div class="ifx-mock-side-title">General</div>
                    <div class="ifx-mock-side-item"><i class="lucide lucide-layout-dashboard text-[14px] text-ink-400"></i> Dashboard</div>
                    <div class="ifx-mock-side-item"><i class="lucide lucide-inbox text-[14px] text-ink-400"></i> Tickets <span class="badge">42</span></div>
                    <div class="ifx-mock-side-item"><i class="lucide lucide-list-checks text-[14px] text-ink-400"></i> Tareas</div>
                    <div class="ifx-mock-side-title mt-3">Administración</div>
                    <div class="ifx-mock-side-item"><i class="lucide lucide-workflow text-[14px] text-ink-400"></i> Automatizaciones</div>
                    <div class="ifx-mock-side-item active"><i class="lucide lucide-plug text-[14px]"></i> Integraciones <span class="badge" style="background:#0ea5e91a;color:#0369a1">5</span></div>
                    <div class="ifx-mock-side-item"><i class="lucide lucide-gauge text-[14px] text-ink-400"></i> SLA</div>
                    <div class="ifx-mock-side-item"><i class="lucide lucide-users text-[14px] text-ink-400"></i> Usuarios</div>
                </aside>
                <div class="ifx-mock-main">
                    <div class="ifx-mock-hero">
                        <div>
                            <div class="text-[10.5px] font-bold uppercase tracking-[0.16em] text-ink-400 mb-1.5">PRO · 12 integraciones</div>
                            <div class="font-display font-extrabold text-[22px] tracking-[-0.025em]">Conecta con tu stack</div>
                        </div>
                        <div class="text-right">
                            <div class="text-[10.5px] font-bold uppercase tracking-[0.16em] text-ink-400">Uso del plan</div>
                            <div class="font-display font-extrabold text-[20px]">5 <span class="text-ink-400 font-normal text-[13px]">/ 15</span></div>
                        </div>
                    </div>

                    <div class="ifx-mock-stats">
                        <div class="ifx-mock-stat" style="border-top:3px solid #7c5cff">
                            <div class="ifx-mock-stat-label">Instaladas</div>
                            <div class="ifx-mock-stat-value">5</div>
                        </div>
                        <div class="ifx-mock-stat" style="border-top:3px solid #16a34a">
                            <div class="ifx-mock-stat-label">Activas</div>
                            <div class="ifx-mock-stat-value">5</div>
                        </div>
                        <div class="ifx-mock-stat" style="border-top:3px solid #0ea5e9">
                            <div class="ifx-mock-stat-label">Eventos</div>
                            <div class="ifx-mock-stat-value">1,247</div>
                        </div>
                        <div class="ifx-mock-stat" style="border-top:3px solid #f59e0b">
                            <div class="ifx-mock-stat-label">Errores</div>
                            <div class="ifx-mock-stat-value">0</div>
                        </div>
                    </div>

                    <div class="ifx-mock-section-h">
                        <h4>Integraciones activas</h4>
                        <span class="pill">5 conectadas</span>
                    </div>

                    <?php foreach ([
                        ['Slack #soporte','slack','#4A154B','842 envíos · hace 12s','active'],
                        ['Discord #alertas','message-square','#5865F2','331 envíos · hace 2m','active'],
                        ['Telegram @kydesk','send','#0088CC','58 envíos · hace 1h','active'],
                        ['Zapier · Notion CRM','zap','#FF4A00','12 envíos · hace 4h','active'],
                        ['Email alertas@acme.com','mail','#0EA5E9','4 envíos · hace 6h','active'],
                    ] as [$nm,$ic,$cl,$mt,$st]): ?>
                        <div class="ifx-mock-row">
                            <div class="ifx-mock-row-icon" style="background:<?= $cl ?>15;color:<?= $cl ?>;border:1px solid <?= $cl ?>30"><i class="lucide lucide-<?= $ic ?> text-[14px]"></i></div>
                            <div class="flex-1 min-w-0">
                                <div class="ifx-mock-row-name"><?= $nm ?></div>
                                <div class="ifx-mock-row-meta"><?= $mt ?></div>
                            </div>
                            <div class="ifx-mock-row-status">activa</div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ════════════════════ TERMINAL · LIVE EVENT LOG ════════════════════ -->
<section class="py-24" style="background:#0a0913;color:white;position:relative;overflow:hidden">
    <style>
        .ifx-term-bg::before { content:''; position:absolute; inset:0; background:radial-gradient(ellipse at 30% 20%,rgba(124,92,255,.18),transparent 50%),radial-gradient(ellipse at 70% 80%,rgba(14,165,233,.12),transparent 60%); }
        .ifx-term { position:relative; max-width:980px; margin:0 auto; border-radius:18px; overflow:hidden; background:#0d0c14; border:1px solid rgba(255,255,255,.1); box-shadow:0 40px 90px -20px rgba(124,92,255,.3); }
        .ifx-term-bar { display:flex; align-items:center; gap:8px; padding:11px 14px; background:rgba(255,255,255,.03); border-bottom:1px solid rgba(255,255,255,.08); }
        .ifx-term-title { font-family:'Geist Mono',monospace; font-size:11.5px; color:rgba(255,255,255,.55); margin-left:auto; margin-right:auto; }
        .ifx-term-title .ifx-status { display:inline-flex; align-items:center; gap:6px; }
        .ifx-term-title .ifx-status::before { content:''; width:7px; height:7px; border-radius:50%; background:#10b981; box-shadow:0 0 8px #10b981; animation:ifx-pulse-2 1.4s ease-in-out infinite; }
        @keyframes ifx-pulse-2 { 0%,100% { opacity:1; transform:scale(1); } 50% { opacity:.5; transform:scale(.85); } }

        .ifx-term-body { padding:22px 24px; font-family:'Geist Mono',monospace; font-size:12.5px; line-height:1.85; min-height:380px; max-height:480px; overflow:hidden; position:relative; mask-image:linear-gradient(180deg,transparent 0%,black 12%,black 88%,transparent 100%); -webkit-mask-image:linear-gradient(180deg,transparent 0%,black 12%,black 88%,transparent 100%); }
        .ifx-term-body .scroll { animation:ifx-term-scroll 40s linear infinite; }
        @keyframes ifx-term-scroll { 0% { transform:translateY(0); } 100% { transform:translateY(-50%); } }
        .ifx-line { display:flex; gap:10px; align-items:baseline; }
        .ifx-time { color:rgba(255,255,255,.35); flex-shrink:0; }
        .ifx-tag { padding:1px 8px; border-radius:5px; font-size:10.5px; font-weight:700; flex-shrink:0; }
        .ifx-tag-info  { background:rgba(14,165,233,.18); color:#60a5fa; border:1px solid rgba(14,165,233,.4); }
        .ifx-tag-ok    { background:rgba(16,185,129,.18); color:#34d399; border:1px solid rgba(16,185,129,.4); }
        .ifx-tag-warn  { background:rgba(245,158,11,.18); color:#fbbf24; border:1px solid rgba(245,158,11,.4); }
        .ifx-tag-evt   { background:rgba(124,92,255,.18); color:#a78bfa; border:1px solid rgba(124,92,255,.4); }
        .ifx-text { color:rgba(255,255,255,.78); }
        .ifx-arr { color:#a78bfa; }
        .ifx-prov { color:#fbbf24; font-weight:600; }
        .ifx-ok { color:#34d399; font-weight:600; }
        .ifx-ms { color:rgba(255,255,255,.4); }
    </style>
    <div class="ifx-term-bg absolute inset-0"></div>
    <div class="max-w-[1240px] mx-auto px-6 relative">
        <div class="text-center max-w-2xl mx-auto mb-10">
            <div class="text-[11.5px] font-bold uppercase tracking-[0.18em] mb-3" style="color:#0ea5e9">LIVE EVENT LOG</div>
            <h2 class="display-xl text-white" style="font-size:clamp(1.8rem,3vw + 1rem,2.6rem);text-wrap:balance">Cada disparo registrado.<br><span class="ix2-grad" style="background:linear-gradient(120deg,#a78bfa,#d946ef);background-clip:text;-webkit-background-clip:text;-webkit-text-fill-color:transparent">Auditable y observable.</span></h2>
            <p class="mt-4 text-[14.5px]" style="color:rgba(255,255,255,.6)">Historial por integración con HTTP status, latencia y excerpt de respuesta. Errores nunca silenciosos.</p>
        </div>

        <div class="ifx-term">
            <div class="ifx-term-bar">
                <span class="w-3 h-3 rounded-full" style="background:#ff5f57"></span>
                <span class="w-3 h-3 rounded-full" style="background:#febc2e"></span>
                <span class="w-3 h-3 rounded-full" style="background:#28c840"></span>
                <div class="ifx-term-title"><span class="ifx-status">events.dispatcher · live</span></div>
            </div>
            <div class="ifx-term-body">
                <div class="scroll">
                    <?php
                    $logEntries = [
                        ['14:32:08', 'EVENT', 'evt', 'ticket.created',  'Slack #soporte',         'OK', '142ms'],
                        ['14:32:08', 'INFO',  'info','POST',             'hooks.slack.com/...',    'HTTP 200', '142ms'],
                        ['14:32:09', 'EVENT', 'evt', 'ticket.created',  'Discord #alertas',       'OK', '88ms'],
                        ['14:32:09', 'EVENT', 'evt', 'ticket.created',  'Telegram @kydesk_bot',   'OK', '210ms'],
                        ['14:32:11', 'EVENT', 'evt', 'comment.created', 'Slack #soporte',         'OK', '125ms'],
                        ['14:33:42', 'EVENT', 'evt', 'sla.breach',      'Slack #soporte-urgente', 'OK', '98ms'],
                        ['14:33:42', 'EVENT', 'evt', 'sla.breach',      'Pushover · Marco',       'OK', '342ms'],
                        ['14:33:42', 'EVENT', 'evt', 'sla.breach',      'Zapier · Notion',        'OK', '218ms'],
                        ['14:35:14', 'EVENT', 'evt', 'ticket.assigned', 'Discord #alertas',       'OK', '92ms'],
                        ['14:36:01', 'EVENT', 'evt', 'ticket.resolved', 'Slack #soporte',         'OK', '110ms'],
                        ['14:36:02', 'EVENT', 'evt', 'ticket.resolved', 'Email alertas@acme.com', 'OK', '88ms'],
                        ['14:38:25', 'INFO',  'info','HMAC',             'Webhook firmado SHA256', 'OK', '—'],
                        ['14:38:26', 'EVENT', 'evt', 'ticket.escalated','Microsoft Teams',        'OK', '156ms'],
                        ['14:39:11', 'EVENT', 'evt', 'comment.created', 'Slack #soporte',         'OK', '132ms'],
                        ['14:40:03', 'EVENT', 'evt', 'todo.completed',  'Telegram @kydesk_bot',   'OK', '198ms'],
                    ];
                    // Render twice for seamless infinite loop
                    for ($pass = 0; $pass < 2; $pass++):
                        foreach ($logEntries as [$t, $tag, $tagCls, $event, $prov, $resp, $ms]): ?>
                            <div class="ifx-line">
                                <span class="ifx-time"><?= $t ?></span>
                                <span class="ifx-tag <?= $tag === 'EVENT' ? 'ifx-tag-evt' : ($tag === 'INFO' ? 'ifx-tag-info' : 'ifx-tag-warn') ?>"><?= $tag ?></span>
                                <span class="ifx-text"><?= $event ?></span>
                                <span class="ifx-arr">→</span>
                                <span class="ifx-prov"><?= $prov ?></span>
                                <span class="ifx-tag ifx-tag-ok"><?= $resp ?></span>
                                <span class="ifx-ms"><?= $ms ?></span>
                            </div>
                        <?php endforeach;
                    endfor; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ════════════════════ FULL PROVIDER GALLERY ════════════════════ -->
<section class="py-24">
    <style>
        .ifx-cat-tab { padding:7px 16px; border-radius:999px; font-size:12.5px; font-weight:600; background:white; border:1px solid #ececef; color:#6b6b78; cursor:pointer; transition:all .15s; }
        .ifx-cat-tab:hover { border-color:#0ea5e9; color:#0ea5e9; }
        .ifx-cat-tab.active { background:#16151b; color:white; border-color:#16151b; }
        .ifx-prov-card { padding:22px; border-radius:18px; background:white; border:1px solid #ececef; transition:all .25s cubic-bezier(.2,.9,.3,1); cursor:default; position:relative; overflow:hidden; }
        .ifx-prov-card::before { content:''; position:absolute; inset:0; background:linear-gradient(135deg,var(--clr,#7c5cff)0d,transparent 60%); opacity:0; transition:opacity .25s; }
        .ifx-prov-card:hover { transform:translateY(-4px); border-color:var(--clr,#7c5cff); box-shadow:0 20px 40px -16px var(--clr,#7c5cff)40; }
        .ifx-prov-card:hover::before { opacity:1; }
        .ifx-prov-icon { position:relative; width:48px; height:48px; border-radius:14px; display:grid; place-items:center; }
        .ifx-prov-cat { position:relative; font-size:10px; font-weight:700; letter-spacing:.16em; text-transform:uppercase; color:#8e8e9a; margin-top:14px; }
        .ifx-prov-name { position:relative; font-family:'Plus Jakarta Sans',sans-serif; font-weight:700; font-size:15px; color:#16151b; margin-top:3px; letter-spacing:-.01em; }
        .ifx-prov-desc { position:relative; font-size:12.5px; color:#6b6b78; margin-top:6px; line-height:1.5; }
        .ifx-prov-link { position:relative; display:inline-flex; align-items:center; gap:5px; margin-top:12px; font-size:11.5px; font-weight:600; opacity:0; transform:translateX(-4px); transition:all .25s; color:var(--clr,#7c5cff); }
        .ifx-prov-card:hover .ifx-prov-link { opacity:1; transform:translateX(0); }
    </style>
    <div class="max-w-[1240px] mx-auto px-6" x-data="{cat:'all'}">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-6 mb-10">
            <div>
                <div class="text-[11.5px] font-bold uppercase tracking-[0.18em] mb-2" style="color:<?= $f['color'] ?>">12 PROVEEDORES LISTOS</div>
                <h2 class="display-xl" style="font-size:clamp(2rem,3.5vw + 1rem,3rem);text-wrap:balance">Catálogo completo de integraciones</h2>
            </div>
            <div class="flex flex-wrap gap-2">
                <button @click="cat='all'" :class="cat==='all'?'active':''" class="ifx-cat-tab">Todas</button>
                <button @click="cat='chat'" :class="cat==='chat'?'active':''" class="ifx-cat-tab"><i class="lucide lucide-message-square text-[12px]"></i> Chat</button>
                <button @click="cat='automation'" :class="cat==='automation'?'active':''" class="ifx-cat-tab"><i class="lucide lucide-workflow text-[12px]"></i> Automatización</button>
                <button @click="cat='devops'" :class="cat==='devops'?'active':''" class="ifx-cat-tab"><i class="lucide lucide-code-2 text-[12px]"></i> DevOps</button>
                <button @click="cat='notify'" :class="cat==='notify'?'active':''" class="ifx-cat-tab"><i class="lucide lucide-bell text-[12px]"></i> Notify</button>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <?php
            $providers = [
                ['Slack',          'slack',          '#4A154B', 'chat',       'Chat',          'Notifica a un canal con attachments coloreados según evento'],
                ['Discord',        'message-square', '#5865F2', 'chat',       'Chat',          'Embeds ricos con fields, color, timestamp y avatar'],
                ['Telegram',       'send',           '#0088CC', 'chat',       'Chat',          'Bot con sendMessage HTML a canales, grupos o chats privados'],
                ['Microsoft Teams','users-2',        '#5059C9', 'chat',       'Chat',          'MessageCard adaptativa con facts y theme color por evento'],
                ['Mattermost',     'message-circle', '#0058CC', 'chat',       'Chat',          'Alternativa open-source · payload compatible Slack'],
                ['Rocket.Chat',    'rocket',         '#F5455C', 'chat',       'Chat',          'Plataforma enterprise · Incoming Webhook nativo'],
                ['Zapier',         'zap',            '#FF4A00', 'automation', 'Automatización','Conecta con miles de apps via Zaps · Catch Hook'],
                ['n8n',            'workflow',       '#EA4B71', 'automation', 'Automatización','Workflows open-source self-hosted con webhook node'],
                ['Make',           'cpu',            '#6D00CC', 'automation', 'Automatización','Escenarios visuales drag & drop (ex-Integromat)'],
                ['Webhook',        'webhook',        '#0ea5e9', 'devops',     'DevOps',        'POST/PUT/PATCH JSON con HMAC-SHA256 opcional'],
                ['Email',          'mail',           '#0EA5E9', 'notify',     'Notify',        'Reenvía eventos a un email vía Resend con HTML branded'],
                ['Pushover',       'bell',           '#249DF1', 'notify',     'Notify',        'Push a tu móvil con prioridad configurable (silenciosa→emergencia)'],
            ];
            foreach ($providers as [$name, $icon, $color, $cat, $catLbl, $desc]): ?>
                <div class="ifx-prov-card" style="--clr:<?= $color ?>" x-show="cat==='all' || cat==='<?= $cat ?>'" x-transition>
                    <div class="ifx-prov-icon" style="background:<?= $color ?>15;color:<?= $color ?>;border:1px solid <?= $color ?>30">
                        <i class="lucide lucide-<?= $icon ?> text-[20px]"></i>
                    </div>
                    <div class="ifx-prov-cat"><?= $catLbl ?></div>
                    <div class="ifx-prov-name"><?= $name ?></div>
                    <div class="ifx-prov-desc"><?= $desc ?></div>
                    <span class="ifx-prov-link">Configurar <i class="lucide lucide-arrow-right text-[11px]"></i></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ════════════════════ EVENT TYPES ════════════════════ -->
<section class="py-24" style="background:linear-gradient(180deg,#fafafb,white)">
    <div class="max-w-[1240px] mx-auto px-6">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <div class="text-[11.5px] font-bold uppercase tracking-[0.18em] mb-3" style="color:<?= $f['color'] ?>">12 EVENTOS DISPARADORES</div>
            <h2 class="display-xl" style="font-size:clamp(2rem,3.5vw + 1rem,3rem);text-wrap:balance">Todo lo que pasa, lo notificas</h2>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 max-w-[1100px] mx-auto">
            <?php foreach ([
                ['ticket.created',   'Ticket creado',         'inbox',          '#3b82f6'],
                ['ticket.updated',   'Ticket actualizado',    'pencil',         '#7c5cff'],
                ['ticket.assigned',  'Ticket asignado',       'user-check',     '#7c5cff'],
                ['ticket.resolved',  'Ticket resuelto',       'check-circle-2', '#16a34a'],
                ['ticket.escalated', 'Ticket escalado',       'trending-up',    '#ef4444'],
                ['ticket.deleted',   'Ticket eliminado',      'trash-2',        '#6b7280'],
                ['comment.created',  'Comentario nuevo',      'message-circle', '#0ea5e9'],
                ['sla.breach',       'SLA vencido',           'alert-triangle', '#dc2626'],
                ['company.created',  'Empresa creada',        'building-2',     '#0ea5e9'],
                ['kb.published',     'Artículo publicado',    'book-open',      '#16a34a'],
                ['todo.created',     'Tarea creada',          'list-checks',    '#3b82f6'],
                ['todo.completed',   'Tarea completada',      'check',          '#16a34a'],
            ] as [$key, $label, $icon, $color]): ?>
                <div class="rounded-2xl p-4 bg-white border border-[#ececef] hover:border-[<?= $color ?>] hover:shadow-md transition cursor-default" style="--c:<?= $color ?>">
                    <div class="w-9 h-9 rounded-xl grid place-items-center mb-3" style="background:<?= $color ?>15;color:<?= $color ?>"><i class="lucide lucide-<?= $icon ?> text-[15px]"></i></div>
                    <div class="font-display font-bold text-[12.5px] text-ink-900"><?= $label ?></div>
                    <code class="text-[10.5px] font-mono text-ink-400 mt-0.5 block truncate" title="<?= $key ?>"><?= $key ?></code>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ════════════════════ PLAYBOOKS ════════════════════ -->
<section class="py-24">
    <style>
        .ifx-pb-card { position:relative; padding:24px; border-radius:20px; background:white; border:1px solid #ececef; transition:all .3s; overflow:hidden; }
        .ifx-pb-card:hover { border-color:#7c5cff; box-shadow:0 24px 50px -16px rgba(124,92,255,.25); }
        .ifx-pb-flow { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-top:12px; padding:12px 14px; border-radius:12px; background:#fafbfc; border:1px solid #ececef; }
        .ifx-pb-trigger { display:inline-flex; align-items:center; gap:6px; padding:5px 11px; border-radius:8px; background:#fef3c7; color:#92400e; font-size:11.5px; font-weight:700; }
        .ifx-pb-arrow { color:#b8b8c4; font-size:13px; }
        .ifx-pb-action { display:inline-flex; align-items:center; gap:6px; padding:5px 11px; border-radius:8px; background:white; border:1px solid; font-size:11.5px; font-weight:600; }
    </style>
    <div class="max-w-[1240px] mx-auto px-6">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <div class="text-[11.5px] font-bold uppercase tracking-[0.18em] mb-3" style="color:<?= $f['color'] ?>">PLAYBOOKS REALES</div>
            <h2 class="display-xl" style="font-size:clamp(2rem,3.5vw + 1rem,3rem);text-wrap:balance">Casos de uso que funcionan hoy</h2>
            <p class="mt-4 text-[14.5px] text-ink-500">Combinaciones probadas que tu equipo puede armar en 3 minutos.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php
            $playbooks = [
                [
                    'title' => 'Tickets urgentes al canal de guardia',
                    'desc'  => 'Cualquier ticket con prioridad urgente notifica al canal #guardia y dispara push al líder de soporte.',
                    'icon'  => 'siren',
                    'color' => '#ef4444',
                    'trigger' => ['ticket.created · priority:urgent', 'zap'],
                    'actions' => [
                        ['Slack #guardia', '#4A154B', 'slack'],
                        ['Pushover · Marco', '#249DF1', 'bell'],
                    ],
                ],
                [
                    'title' => 'SLA en riesgo → escalamiento',
                    'desc'  => 'Cuando un ticket está a 80% del SLA, alerta al supervisor y crea card en Notion via Zapier.',
                    'icon'  => 'gauge',
                    'color' => '#f59e0b',
                    'trigger' => ['sla.breach', 'alert-triangle'],
                    'actions' => [
                        ['Discord #alerta', '#5865F2', 'message-square'],
                        ['Zapier → Notion', '#FF4A00', 'zap'],
                    ],
                ],
                [
                    'title' => 'Resolución → CSAT survey',
                    'desc'  => 'Al resolver un ticket, n8n dispara una encuesta de satisfacción y guarda en hoja de Google Sheets.',
                    'icon'  => 'star',
                    'color' => '#16a34a',
                    'trigger' => ['ticket.resolved', 'check-circle-2'],
                    'actions' => [
                        ['n8n workflow', '#EA4B71', 'workflow'],
                        ['Email cliente', '#0EA5E9', 'mail'],
                    ],
                ],
                [
                    'title' => 'Comentarios públicos del cliente',
                    'desc'  => 'Cada vez que el cliente responde por el portal público, el equipo se entera en Slack al instante.',
                    'icon'  => 'message-square-quote',
                    'color' => '#7c5cff',
                    'trigger' => ['comment.created · public', 'message-circle'],
                    'actions' => [
                        ['Slack #equipo', '#4A154B', 'slack'],
                    ],
                ],
                [
                    'title' => 'Daily digest a Teams',
                    'desc'  => 'Make (Integromat) consume el log diario y postea un resumen ejecutivo al canal General de Microsoft Teams.',
                    'icon'  => 'newspaper',
                    'color' => '#5059C9',
                    'trigger' => ['todo.completed', 'check'],
                    'actions' => [
                        ['Make scenario', '#6D00CC', 'cpu'],
                        ['Teams · General', '#5059C9', 'users-2'],
                    ],
                ],
                [
                    'title' => 'Empresa nueva → CRM',
                    'desc'  => 'Crear empresa en Kydesk dispara webhook a tu CRM (Salesforce, HubSpot, custom) para sincronizar.',
                    'icon'  => 'building-2',
                    'color' => '#0ea5e9',
                    'trigger' => ['company.created', 'building-2'],
                    'actions' => [
                        ['Webhook firmado', '#0ea5e9', 'webhook'],
                    ],
                ],
            ];
            foreach ($playbooks as $pb): ?>
                <div class="ifx-pb-card">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 rounded-xl grid place-items-center" style="background:<?= $pb['color'] ?>15;color:<?= $pb['color'] ?>;border:1px solid <?= $pb['color'] ?>30"><i class="lucide lucide-<?= $pb['icon'] ?> text-[16px]"></i></div>
                        <h3 class="font-display font-extrabold text-[14.5px] tracking-[-0.015em]"><?= $e($pb['title']) ?></h3>
                    </div>
                    <p class="text-[12.5px] text-ink-500 leading-relaxed"><?= $e($pb['desc']) ?></p>
                    <div class="ifx-pb-flow">
                        <span class="ifx-pb-trigger"><i class="lucide lucide-<?= $pb['trigger'][1] ?> text-[11px]"></i> <?= $e($pb['trigger'][0]) ?></span>
                        <?php foreach ($pb['actions'] as [$lbl, $col, $ic]): ?>
                            <span class="ifx-pb-arrow"><i class="lucide lucide-arrow-right"></i></span>
                            <span class="ifx-pb-action" style="border-color:<?= $col ?>40;color:<?= $col ?>"><i class="lucide lucide-<?= $ic ?> text-[11px]"></i> <?= $e($lbl) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php else: /* GENERIC MOCKUP for other features */ ?>

<!-- MOCKUP showcase -->
<section class="py-24">
    <div class="max-w-[1240px] mx-auto px-6">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <div class="text-[11.5px] font-bold uppercase tracking-[0.18em] mb-3" style="color:<?= $f['color'] ?>">EN ACCIÓN</div>
            <h2 class="display-xl" style="font-size:clamp(2rem,3.5vw + 1rem,3rem);text-wrap:balance">Así se ve dentro de Kydesk</h2>
        </div>

        <?php
        $mockType = $f['mockup'] ?? 'inbox';
        $accent = $f['color'];
        $accentBg = $f['bg'];
        ?>

        <div class="mock-frame max-w-[1100px] mx-auto">
            <div class="flex items-center gap-1.5 px-3 py-2.5">
                <span class="w-3 h-3 rounded-full bg-[#ff5f57]"></span>
                <span class="w-3 h-3 rounded-full bg-[#febc2e]"></span>
                <span class="w-3 h-3 rounded-full bg-[#28c840]"></span>
                <div class="flex-1 text-center text-[11px] font-mono text-ink-400 inline-flex items-center justify-center gap-1.5"><i class="lucide lucide-lock text-[10px]"></i> kydesk.kyrosrd.com / acme<?= $mockType !== 'inbox' ? '/' . $mockType : '' ?></div>
            </div>
            <div class="rounded-[20px] overflow-hidden border border-[#ececef] p-8" style="background:linear-gradient(135deg,<?= $accentBg ?>,#fafafb)">

                <?php if ($mockType === 'kanban'): ?>
                    <div class="grid grid-cols-4 gap-3">
                        <?php foreach ([['Abierto','#3b82f6',2],['En progreso',$accent,3],['En espera','#9ca3af',1],['Resuelto','#16a34a',2]] as [$col,$c,$cnt]): ?>
                            <div class="rounded-2xl p-3 bg-white border border-[#ececef]">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full" style="background:<?= $c ?>"></span><span class="font-display font-bold text-[12px]"><?= $col ?></span></div>
                                    <span class="text-[10px] font-mono text-ink-400"><?= $cnt ?></span>
                                </div>
                                <?php for ($i=0; $i<$cnt; $i++): ?>
                                    <div class="rounded-xl p-3 mb-2 bg-[#fafafb] border border-[#ececef]">
                                        <div class="text-[9px] font-mono text-ink-400 mb-1">TK-04-0000<?= $i+1 ?></div>
                                        <div class="font-display font-bold text-[11.5px] line-clamp-2"><?= ['VPN se desconecta','Impresora offline','Reset de contraseña','Nuevo equipo','Error 500','Acceso RRHH'][($i+$cnt) % 6] ?></div>
                                        <div class="mt-2 flex items-center gap-1.5">
                                            <div class="w-5 h-5 rounded-full text-white text-[8px] grid place-items-center font-bold" style="background:<?= $accent ?>">M</div>
                                            <span class="text-[9px] text-ink-400">hace 2h</span>
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                <?php elseif ($mockType === 'sla'): ?>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <?php foreach ([['Urgente','15 min','12 min','#ef4444',80],['Alta','1h','38 min','#f59e0b',63],['Media','4h','2h 12min','#7c5cff',45]] as [$lbl,$total,$rest,$c,$pct]): ?>
                            <div class="rounded-2xl p-5 bg-white border border-[#ececef]">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="font-display font-bold text-[13px] inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full" style="background:<?= $c ?>"></span><?= $lbl ?></span>
                                    <span class="font-mono text-[11px] text-ink-400"><?= $total ?></span>
                                </div>
                                <div class="font-display font-extrabold text-[24px] tracking-[-0.02em]" style="color:<?= $c ?>"><?= $rest ?></div>
                                <div class="text-[10.5px] text-ink-400 mb-2">tiempo restante</div>
                                <div class="h-2 bg-[#f3f4f6] rounded-full overflow-hidden"><div class="h-full" style="width:<?= $pct ?>%;background:<?= $c ?>"></div></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                <?php elseif ($mockType === 'automations'): ?>
                    <div class="space-y-3 max-w-3xl mx-auto">
                        <?php foreach ([
                            ['Auto-asignar urgentes a N2','urgent → Marco Técnico','zap',true],
                            ['Notificar a Slack si SLA en riesgo','sla.threshold:80% → webhook','bell-ring',true],
                            ['Cerrar resueltos +7 días','resolved.age:7d → status:closed','clock',true],
                            ['Etiquetar tickets de Acme como VIP','company:acme → tag:vip','tag',false],
                        ] as [$name,$rule,$ic,$active]): ?>
                            <div class="rounded-2xl p-4 bg-white border border-[#ececef] flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl grid place-items-center" style="background:<?= $accentBg ?>;color:<?= $accent ?>"><i class="lucide lucide-<?= $ic ?> text-[16px]"></i></div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-display font-bold text-[13.5px] truncate"><?= $name ?></div>
                                    <div class="text-[11px] font-mono text-ink-400 truncate"><?= $rule ?></div>
                                </div>
                                <span class="kswitch"><input type="checkbox" <?= $active?'checked':'' ?> disabled><span class="kswitch-track"></span></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                <?php elseif ($mockType === 'analytics'): ?>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                        <?php foreach ([['Tasa resolución','94%','+4%','#16a34a'],['1ª respuesta','38min','-12min','#3b82f6'],['Cumplimiento SLA','98.7%','+2.1%',$accent]] as [$lbl,$val,$delta,$c]): ?>
                            <div class="rounded-2xl p-4 bg-white border border-[#ececef]">
                                <div class="text-[10.5px] uppercase tracking-[0.12em] font-bold text-ink-400"><?= $lbl ?></div>
                                <div class="flex items-baseline gap-2 mt-1">
                                    <span class="font-display font-extrabold text-[26px] tracking-[-0.02em]" style="color:<?= $c ?>"><?= $val ?></span>
                                    <span class="text-[11px] font-bold text-emerald-600"><?= $delta ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="rounded-2xl p-5 bg-white border border-[#ececef]">
                        <div class="font-display font-bold text-[13px] mb-3">Tickets por día (últimos 14)</div>
                        <div class="flex items-end gap-2 h-32">
                            <?php foreach ([35,42,38,55,48,62,58,71,65,52,68,75,82,78] as $i => $h): ?>
                                <div class="flex-1 rounded-t-md" style="height:<?= ($h/82)*100 ?>%;background:<?= $i % 3 === 0 ? $accent : $accentBg ?>"></div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                <?php elseif ($mockType === 'kb'): ?>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <?php foreach ([['Primeros pasos','rocket','#0ea5e9','12 artículos'],['Red & VPN','wifi','#10b981','8 artículos'],['Cuentas','lock','#ec4899','15 artículos']] as [$n,$ic,$c,$count]): ?>
                            <div class="rounded-2xl p-5 bg-white border border-[#ececef]">
                                <div class="w-12 h-12 rounded-2xl text-white grid place-items-center" style="background:<?= $c ?>"><i class="lucide lucide-<?= $ic ?> text-[18px]"></i></div>
                                <div class="font-display font-bold text-[15px] mt-4"><?= $n ?></div>
                                <div class="text-[11.5px] text-ink-400 mt-1"><?= $count ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                <?php elseif ($mockType === 'multitenant'): ?>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <?php foreach ([['Acme Corp','A','#7c5cff','enterprise'],['Globex Inc','G','#ec4899','premium'],['Initech','I','#f59e0b','premium']] as [$n,$letter,$c,$tier]): ?>
                            <div class="rounded-2xl p-5 bg-white border border-[#ececef]">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-10 h-10 rounded-xl text-white grid place-items-center font-display font-bold" style="background:<?= $c ?>"><?= $letter ?></div>
                                    <div>
                                        <div class="font-display font-bold text-[13.5px]"><?= $n ?></div>
                                        <div class="text-[10px] text-ink-400 uppercase tracking-wider">/t/<?= strtolower(str_replace(' ','',$n)) ?></div>
                                    </div>
                                </div>
                                <div class="text-[11px] text-ink-500 inline-flex items-center gap-1"><i class="lucide lucide-shield text-[11px]"></i> Aislado · <?= $tier ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                <?php elseif ($mockType === 'roles'): ?>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <?php foreach ([
                            ['Owner','crown','#b91c1c','Acceso total','30+'],
                            ['Supervisor','shield-check','#f59e0b','Tickets, equipo, SLA','22'],
                            ['Técnico','wrench',$accent,'Resuelve y comenta','14'],
                        ] as [$role,$ic,$c,$desc,$perms]): ?>
                            <div class="rounded-2xl p-5 bg-white border border-[#ececef]">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="w-10 h-10 rounded-xl grid place-items-center text-white" style="background:<?= $c ?>"><i class="lucide lucide-<?= $ic ?> text-[16px]"></i></div>
                                    <span class="text-[11px] font-mono px-2 py-0.5 rounded-full" style="background:<?= $accentBg ?>;color:<?= $c ?>"><?= $perms ?> permisos</span>
                                </div>
                                <div class="font-display font-bold text-[14px]"><?= $role ?></div>
                                <div class="text-[11px] text-ink-500 mt-1"><?= $desc ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                <?php else: /* inbox default */ ?>
                    <div class="space-y-2 max-w-3xl mx-auto">
                        <?php foreach ([
                            ['globe','Portal · Acme','VPN se desconecta cada 10 min','#ef4444','Urgente'],
                            ['mail','Email · Globex','No recibe correo corporativo','#f59e0b','Alta'],
                            ['phone','Teléfono · Initech','Impresora 3er piso offline',$accent,'Media'],
                            ['message-circle','Chat · Stark','Dudas con Office 365','#9ca3af','Baja'],
                        ] as [$ic,$ch,$s,$c,$pl]): ?>
                            <div class="rounded-2xl p-3.5 bg-white border border-[#ececef] flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl bg-[#fafafb] border border-[#ececef] grid place-items-center text-ink-500"><i class="lucide lucide-<?= $ic ?> text-[15px]"></i></div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-[10.5px] text-ink-400 mb-0.5"><?= $ch ?></div>
                                    <div class="text-[13px] font-semibold truncate"><?= $s ?></div>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold" style="background:<?= $c ?>1f;color:<?= $c ?>"><?= $pl ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</section>

<?php endif; /* end /features/integrations conditional vs generic mockup */ ?>

<!-- FAQ -->
<section class="py-20">
    <div class="max-w-3xl mx-auto px-6">
        <div class="text-center mb-12">
            <div class="text-[11.5px] font-bold uppercase tracking-[0.18em] mb-3" style="color:<?= $f['color'] ?>">DUDAS FRECUENTES</div>
            <h2 class="display-xl" style="font-size:clamp(1.8rem,2.8vw + 1rem,2.4rem)">Preguntas sobre <?= $e($f['title']) ?></h2>
        </div>
        <div x-data="{open:0}">
            <?php foreach ($f['faqs'] as $i => [$q, $a]): ?>
                <div class="faq-item" :class="open===<?= $i ?> ? 'open' : ''" @click="open = open===<?= $i ?> ? -1 : <?= $i ?>">
                    <div class="faq-q"><?= $e($q) ?><div class="faq-icon" style="background:<?= $f['bg'] ?>;color:<?= $f['color'] ?>"><i class="lucide lucide-plus text-[16px]"></i></div></div>
                    <div class="faq-a"><?= $e($a) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- RELATED FEATURES -->
<?php if (!empty($siblings)): ?>
<section class="py-20 border-t border-[#ececef]">
    <div class="max-w-[1240px] mx-auto px-6">
        <div class="flex items-end justify-between mb-10">
            <div>
                <div class="text-[11.5px] font-bold uppercase tracking-[0.18em] text-brand-600 mb-2">SEGUÍ EXPLORANDO</div>
                <h2 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">Otras funcionalidades</h2>
            </div>
            <a href="<?= $url('/') ?>#features" class="text-[13px] font-semibold text-brand-700 inline-flex items-center gap-1">Ver todas <i class="lucide lucide-arrow-right text-[12px]"></i></a>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <?php $count = 0; foreach ($siblings as $key => $sib): if ($count++ >= 4) break; ?>
                <a href="<?= $url('/features/' . $key) ?>" class="bento spotlight-card block group" style="padding:22px">
                    <div class="bento-glow"></div>
                    <div class="w-12 h-12 rounded-2xl grid place-items-center" style="background:<?= $sib['bg'] ?>;color:<?= $sib['color'] ?>;box-shadow:0 6px 14px -4px <?= $sib['color'] ?>40"><i class="lucide lucide-<?= $sib['icon'] ?> text-[20px]"></i></div>
                    <div class="font-display font-bold text-[15px] mt-4 tracking-[-0.015em]"><?= $e($sib['title']) ?></div>
                    <div class="text-[12px] text-ink-400 mt-1.5 line-clamp-2"><?= $e($sib['tagline']) ?></div>
                    <div class="mt-3 inline-flex items-center gap-1 text-[11.5px] font-semibold opacity-0 group-hover:opacity-100 transition" style="color:<?= $sib['color'] ?>">Saber más <i class="lucide lucide-arrow-right text-[11px]"></i></div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- CTA -->
<section class="py-24">
    <div class="max-w-[1240px] mx-auto px-6">
        <div class="relative rounded-[28px] overflow-hidden p-12 md:p-16 text-center" style="background:linear-gradient(135deg,<?= $f['color'] ?> 0%,#16151b 80%);color:white;box-shadow:0 30px 60px -20px <?= $f['color'] ?>80">
            <div class="absolute inset-0 pointer-events-none" style="background:radial-gradient(circle at 0% 0%,rgba(255,255,255,.18),transparent 50%),radial-gradient(circle at 100% 100%,rgba(255,255,255,.10),transparent 50%)"></div>
            <div class="relative max-w-2xl mx-auto">
                <h2 class="display-xl text-white" style="font-size:clamp(2rem,3.5vw + 1rem,3.4rem);text-wrap:balance">Probá <?= $e($f['title']) ?> ahora.</h2>
                <p class="mt-5 text-[16px]" style="color:rgba(255,255,255,.85)">Workspace pre-cargado · Sin tarjeta · Se borra en 24h</p>
                <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
                    <a href="<?= $url('/demo') ?>" class="btn btn-lg" style="background:white;color:#16151b"><i class="lucide lucide-play"></i> Probar demo</a>
                    <a href="<?= $url('/auth/register') ?>" class="btn btn-lg" style="background:rgba(255,255,255,.15);color:white;border:1px solid rgba(255,255,255,.25);backdrop-filter:blur(10px)">Crear cuenta</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include APP_PATH . '/Views/partials/landing_footer.php'; ?>
