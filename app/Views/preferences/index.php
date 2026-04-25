<?php $slug = $tenant->slug; $p = $prefs;
$initialState = json_encode($p, JSON_UNESCAPED_UNICODE);
?>

<div x-data='preferences(<?= $initialState ?>)' x-init="applyAll()" class="space-y-5">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">Personalizar panel</h1>
            <p class="text-[13px] text-ink-400">Hace tuyo el workspace · Los cambios se previsualizan al instante y se guardan al pulsar el botón</p>
        </div>
        <div class="flex items-center gap-2">
            <form method="POST" action="<?= $url('/t/' . $slug . '/preferences/reset') ?>" onsubmit="return confirm('¿Restaurar todo a los valores por defecto?')">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <button class="btn btn-outline btn-sm"><i class="lucide lucide-rotate-ccw"></i> Restaurar</button>
            </form>
            <button form="prefs-form" class="btn btn-primary btn-sm"><i class="lucide lucide-save"></i> Guardar</button>
        </div>
    </div>

    <form id="prefs-form" method="POST" action="<?= $url('/t/' . $slug . '/preferences') ?>" class="grid grid-cols-1 lg:grid-cols-12 gap-5">
        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

        <!-- TABS sidebar -->
        <div class="lg:col-span-3">
            <div class="card overflow-hidden sticky top-4">
                <?php
                $tabs = [
                    ['apariencia','Apariencia','palette'],
                    ['layout','Layout','layout'],
                    ['dashboard','Dashboard','grid-2x2'],
                    ['notificaciones','Notificaciones','bell'],
                    ['region','Idioma & región','globe'],
                ];
                ?>
                <nav class="p-2.5">
                    <?php foreach ($tabs as [$k, $lbl, $ic]): ?>
                        <button type="button" @click="tab='<?= $k ?>'" :class="tab==='<?= $k ?>' ? 'bg-brand-50 text-brand-700 font-semibold' : 'text-ink-500 hover:bg-bg hover:text-ink-900'" class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-[13px] font-medium transition">
                            <i class="lucide lucide-<?= $ic ?> text-[15px]"></i> <?= $lbl ?>
                        </button>
                    <?php endforeach; ?>
                </nav>
                <div class="px-4 py-3 border-t border-[#ececef] text-[11px] text-ink-400 inline-flex items-center gap-1.5"><i class="lucide lucide-info text-[12px]"></i> Cambios solo afectan tu cuenta</div>
            </div>
        </div>

        <!-- CONTENT -->
        <div class="lg:col-span-9 space-y-5">

            <!-- APARIENCIA -->
            <div x-show="tab==='apariencia'" class="space-y-5" x-transition>
                <div class="card card-pad">
                    <div class="section-head">
                        <div class="section-head-icon"><i class="lucide lucide-sun-moon text-[16px]"></i></div>
                        <div><h3 class="section-title">Tema</h3><div class="section-head-meta">Claro, oscuro o automático según tu sistema</div></div>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <?php foreach ([['light','Claro','sun'],['dark','Oscuro','moon'],['auto','Automático','laptop']] as [$v,$lbl,$ic]): ?>
                            <button type="button" @click="set('theme','<?= $v ?>')" :class="state.theme==='<?= $v ?>' ? 'choice-tile selected' : 'choice-tile'">
                                <div class="w-10 h-10 rounded-xl grid place-items-center mb-1" style="background:<?= $v==='dark'?'#16151b':($v==='light'?'#f3f4f6':'linear-gradient(135deg,#16151b 50%,#f3f4f6 50%)') ?>;color:<?= $v==='dark'?'#f5f5f7':($v==='light'?'#16151b':'#7c5cff') ?>"><i class="lucide lucide-<?= $ic ?> text-[16px]"></i></div>
                                <div class="font-display font-bold text-[13px]"><?= $lbl ?></div>
                                <div class="text-[11px] text-ink-400"><?= $v==='dark'?'Reduce fatiga visual':($v==='light'?'Por defecto':'Sigue al sistema') ?></div>
                                <input type="radio" name="theme" value="<?= $v ?>" x-model="state.theme" class="hidden">
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="card card-pad">
                    <div class="section-head">
                        <div class="section-head-icon"><i class="lucide lucide-droplet text-[16px]"></i></div>
                        <div><h3 class="section-title">Color de acento</h3><div class="section-head-meta">Aplica a botones, links activos y badges</div></div>
                    </div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <?php foreach ($accents as $hex => $name): ?>
                            <div class="flex flex-col items-center gap-1.5">
                                <button type="button" @click="set('accent','<?= $hex ?>')" :class="state.accent==='<?= $hex ?>' && 'selected'" class="accent-tile" style="background:<?= $hex ?>;box-shadow:0 6px 16px -4px <?= $hex ?>66" data-tooltip="<?= $name ?>"></button>
                                <span class="text-[10.5px] text-ink-400"><?= $name ?></span>
                            </div>
                        <?php endforeach; ?>
                        <div class="flex flex-col items-center gap-1.5 ml-2 pl-3" style="border-left:1px solid var(--border)">
                            <input type="color" :value="state.accent" @input="set('accent', $event.target.value)" class="accent-tile" style="border:none;padding:0">
                            <span class="text-[10.5px] text-ink-400">Custom</span>
                        </div>
                        <input type="hidden" name="accent" :value="state.accent">
                    </div>
                </div>

                <div class="card card-pad">
                    <div class="section-head">
                        <div class="section-head-icon"><i class="lucide lucide-rows-3 text-[16px]"></i></div>
                        <div><h3 class="section-title">Densidad</h3><div class="section-head-meta">Controla el espaciado vertical de la UI</div></div>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <?php foreach ([['compact','Compacto','Para más info en menos espacio'],['comfortable','Cómodo','Recomendado'],['spacious','Espacioso','Más respiro y aire']] as [$v,$lbl,$desc]): ?>
                            <button type="button" @click="set('density','<?= $v ?>')" :class="state.density==='<?= $v ?>' ? 'choice-tile selected' : 'choice-tile'">
                                <div class="space-y-1 mb-2">
                                    <div class="rounded" style="height:<?= $v==='compact'?'4':($v==='comfortable'?'6':'9') ?>px;background:#cdbfff"></div>
                                    <div class="rounded" style="height:<?= $v==='compact'?'4':($v==='comfortable'?'6':'9') ?>px;background:#e7e0ff"></div>
                                    <div class="rounded" style="height:<?= $v==='compact'?'4':($v==='comfortable'?'6':'9') ?>px;background:#e7e0ff"></div>
                                </div>
                                <div class="font-display font-bold text-[13px]"><?= $lbl ?></div>
                                <div class="text-[11px] text-ink-400"><?= $desc ?></div>
                                <input type="radio" name="density" value="<?= $v ?>" x-model="state.density" class="hidden">
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="card card-pad">
                    <div class="section-head">
                        <div class="section-head-icon"><i class="lucide lucide-image text-[16px]"></i></div>
                        <div><h3 class="section-title">Fondo del workspace</h3><div class="section-head-meta">Patrón sutil detrás del contenedor principal</div></div>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <?php
                        $previews = [
                            'none' => 'background:#f3f4f6',
                            'grid' => 'background:#f3f4f6;background-image:linear-gradient(rgba(124,92,255,.18) 1px,transparent 1px),linear-gradient(90deg,rgba(124,92,255,.18) 1px,transparent 1px);background-size:14px 14px',
                            'dots' => 'background:#f3f4f6;background-image:radial-gradient(rgba(124,92,255,.4) 1px,transparent 1px);background-size:10px 10px',
                            'mesh' => 'background:#f3f4f6;background-image:radial-gradient(circle at 0% 0%,rgba(124,92,255,.5),transparent 60%),radial-gradient(circle at 100% 100%,rgba(217,70,239,.4),transparent 60%)',
                        ];
                        foreach ($wallpapers as $key => $lbl): ?>
                            <button type="button" @click="set('wallpaper','<?= $key ?>')" :class="state.wallpaper==='<?= $key ?>' ? 'choice-tile selected' : 'choice-tile'">
                                <div class="rounded-lg h-12 mb-1" style="<?= $previews[$key] ?>"></div>
                                <div class="font-display font-bold text-[13px]"><?= $lbl ?></div>
                                <input type="radio" name="wallpaper" value="<?= $key ?>" x-model="state.wallpaper" class="hidden">
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- LAYOUT -->
            <div x-show="tab==='layout'" class="space-y-5" x-transition x-cloak>
                <div class="card card-pad">
                    <div class="section-head">
                        <div class="section-head-icon"><i class="lucide lucide-panel-left text-[16px]"></i></div>
                        <div><h3 class="section-title">Sidebar</h3><div class="section-head-meta">Modo de visualización del menú lateral</div></div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 max-w-md">
                        <?php foreach ([['expanded','Expandido','Texto + iconos','panel-left-open'],['compact','Compacto','Solo iconos · Más espacio para tu contenido','panel-left-close']] as [$v,$lbl,$desc,$ic]): ?>
                            <button type="button" @click="set('sidebar_mode','<?= $v ?>')" :class="state.sidebar_mode==='<?= $v ?>' ? 'choice-tile selected' : 'choice-tile'">
                                <i class="lucide lucide-<?= $ic ?> text-[20px] text-brand-600 mb-1"></i>
                                <div class="font-display font-bold text-[13px]"><?= $lbl ?></div>
                                <div class="text-[11px] text-ink-400"><?= $desc ?></div>
                                <input type="radio" name="sidebar_mode" value="<?= $v ?>" x-model="state.sidebar_mode" class="hidden">
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="card card-pad">
                    <div class="section-head">
                        <div class="section-head-icon"><i class="lucide lucide-home text-[16px]"></i></div>
                        <div><h3 class="section-title">Página de inicio</h3><div class="section-head-meta">A dónde te lleva el logo y el inicio</div></div>
                    </div>
                    <select name="default_landing" x-model="state.default_landing" class="input max-w-md">
                        <option value="dashboard">Dashboard</option>
                        <option value="tickets">Tickets</option>
                        <option value="board">Tablero kanban</option>
                        <option value="todos">Mis tareas</option>
                    </select>
                </div>

                <div class="card card-pad">
                    <div class="section-head">
                        <div class="section-head-icon"><i class="lucide lucide-list-tree text-[16px]"></i></div>
                        <div><h3 class="section-title">Vista preferida de tickets</h3><div class="section-head-meta">Lista o tablero por defecto</div></div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 max-w-md">
                        <?php foreach ([['list','Lista','list'],['board','Tablero','kanban-square']] as [$v,$lbl,$ic]): ?>
                            <button type="button" @click="set('default_ticket_view','<?= $v ?>')" :class="state.default_ticket_view==='<?= $v ?>' ? 'choice-tile selected' : 'choice-tile'">
                                <i class="lucide lucide-<?= $ic ?> text-[20px] text-brand-600 mb-1"></i>
                                <div class="font-display font-bold text-[13px]"><?= $lbl ?></div>
                                <input type="radio" name="default_ticket_view" value="<?= $v ?>" x-model="state.default_ticket_view" class="hidden">
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- DASHBOARD -->
            <div x-show="tab==='dashboard'" class="space-y-5" x-transition x-cloak>
                <div class="card card-pad">
                    <div class="section-head">
                        <div class="section-head-icon"><i class="lucide lucide-grid-2x2 text-[16px]"></i></div>
                        <div><h3 class="section-title">Widgets visibles</h3><div class="section-head-meta">Activa o desactiva las secciones de tu dashboard</div></div>
                    </div>
                    <div class="space-y-1">
                        <?php
                        $widgets = [
                            ['show_hero','Hero card de bienvenida','sparkles','Banner morado con KPIs principales'],
                            ['show_stats','Stat cards (Abiertos, En progreso, Resueltos)','activity','Tarjetas con sparklines'],
                            ['show_tickets_grid','Tickets activos en grid','layout-grid','3 cards destacadas más recientes'],
                            ['show_inbox','Tu bandeja','inbox','Tabla con tickets recientes'],
                            ['show_team','Tu equipo','users','Lista de técnicos con online status'],
                            ['show_sla','SLA en riesgo','alarm-clock','Tickets que vencen pronto'],
                            ['show_todos','Mis pendientes','list-checks','Tareas activas'],
                        ];
                        foreach ($widgets as [$k, $lbl, $ic, $desc]):
                            $checked = !empty($p[$k]); ?>
                            <label class="flex items-center gap-3.5 p-3 rounded-xl hover:bg-bg cursor-pointer transition">
                                <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-700 grid place-items-center flex-shrink-0"><i class="lucide lucide-<?= $ic ?> text-[16px]"></i></div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-display font-bold text-[13px]"><?= $lbl ?></div>
                                    <div class="text-[11.5px] text-ink-400"><?= $desc ?></div>
                                </div>
                                <span class="kswitch">
                                    <input type="checkbox" name="<?= $k ?>" value="1" <?= $checked?'checked':'' ?> x-model="state.<?= $k ?>" :true-value="1" :false-value="0">
                                    <span class="kswitch-track"></span>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- NOTIFICACIONES -->
            <div x-show="tab==='notificaciones'" class="space-y-5" x-transition x-cloak>
                <div class="card card-pad">
                    <div class="section-head">
                        <div class="section-head-icon"><i class="lucide lucide-bell text-[16px]"></i></div>
                        <div><h3 class="section-title">En el navegador</h3><div class="section-head-meta">Avísame mientras tengo Kydesk abierto</div></div>
                    </div>
                    <label class="flex items-center gap-3.5 p-3 rounded-xl hover:bg-bg cursor-pointer transition">
                        <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-700 grid place-items-center"><i class="lucide lucide-monitor text-[16px]"></i></div>
                        <div class="flex-1"><div class="font-display font-bold text-[13px]">Notificaciones de escritorio</div><div class="text-[11.5px] text-ink-400">Pop-ups del navegador para nuevos tickets</div></div>
                        <span class="kswitch">
                            <input type="checkbox" name="notify_desktop" value="1" <?= !empty($p['notify_desktop'])?'checked':'' ?> x-model="state.notify_desktop" :true-value="1" :false-value="0">
                            <span class="kswitch-track"></span>
                        </span>
                    </label>
                    <label class="flex items-center gap-3.5 p-3 rounded-xl hover:bg-bg cursor-pointer transition">
                        <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-700 grid place-items-center"><i class="lucide lucide-volume-2 text-[16px]"></i></div>
                        <div class="flex-1"><div class="font-display font-bold text-[13px]">Sonido al recibir ticket</div><div class="text-[11.5px] text-ink-400">Un beep discreto cuando entra un nuevo caso</div></div>
                        <span class="kswitch">
                            <input type="checkbox" name="notify_sound" value="1" <?= !empty($p['notify_sound'])?'checked':'' ?> x-model="state.notify_sound" :true-value="1" :false-value="0">
                            <span class="kswitch-track"></span>
                        </span>
                    </label>
                </div>
                <div class="card card-pad">
                    <div class="section-head">
                        <div class="section-head-icon"><i class="lucide lucide-mail text-[16px]"></i></div>
                        <div><h3 class="section-title">Resumen por email</h3><div class="section-head-meta">Frecuencia de digest enviado a tu cuenta</div></div>
                    </div>
                    <div class="grid grid-cols-3 gap-3 max-w-md">
                        <?php foreach ([['off','Apagado','x'],['daily','Diario','sun'],['weekly','Semanal','calendar']] as [$v,$lbl,$ic]): ?>
                            <button type="button" @click="set('notify_email_digest','<?= $v ?>')" :class="state.notify_email_digest==='<?= $v ?>' ? 'choice-tile selected' : 'choice-tile'">
                                <i class="lucide lucide-<?= $ic ?> text-[18px] text-brand-600 mb-1"></i>
                                <div class="font-display font-bold text-[13px]"><?= $lbl ?></div>
                                <input type="radio" name="notify_email_digest" value="<?= $v ?>" x-model="state.notify_email_digest" class="hidden">
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- REGION -->
            <div x-show="tab==='region'" class="space-y-5" x-transition x-cloak>
                <div class="card card-pad">
                    <div class="section-head">
                        <div class="section-head-icon"><i class="lucide lucide-languages text-[16px]"></i></div>
                        <div><h3 class="section-title">Idioma</h3><div class="section-head-meta">El interfaz se traduce a tu elección</div></div>
                    </div>
                    <div class="grid grid-cols-3 gap-3 max-w-md">
                        <?php foreach ([['es','Español','🇪🇸'],['en','English','🇬🇧'],['pt','Português','🇧🇷']] as [$v,$lbl,$flag]): ?>
                            <button type="button" @click="set('locale','<?= $v ?>')" :class="state.locale==='<?= $v ?>' ? 'choice-tile selected' : 'choice-tile'">
                                <div class="text-[28px] leading-none mb-1"><?= $flag ?></div>
                                <div class="font-display font-bold text-[13px]"><?= $lbl ?></div>
                                <input type="radio" name="locale" value="<?= $v ?>" x-model="state.locale" class="hidden">
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="card card-pad">
                    <div class="section-head">
                        <div class="section-head-icon"><i class="lucide lucide-calendar-days text-[16px]"></i></div>
                        <div><h3 class="section-title">Formato de fecha</h3></div>
                    </div>
                    <div class="grid grid-cols-3 gap-3 max-w-md">
                        <?php foreach ([['dmy','DD/MM/AAAA','15/04/2026'],['mdy','MM/DD/AAAA','04/15/2026'],['ymd','AAAA-MM-DD','2026-04-15']] as [$v,$lbl,$ex]): ?>
                            <button type="button" @click="set('date_format','<?= $v ?>')" :class="state.date_format==='<?= $v ?>' ? 'choice-tile selected' : 'choice-tile'">
                                <div class="font-display font-bold text-[13px]"><?= $lbl ?></div>
                                <div class="text-[11px] font-mono text-ink-400 mt-0.5"><?= $ex ?></div>
                                <input type="radio" name="date_format" value="<?= $v ?>" x-model="state.date_format" class="hidden">
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

<script>
function preferences(initial) {
    return {
        tab: 'apariencia',
        state: initial,
        set(key, value) {
            this.state[key] = value;
            this.applyAll();
        },
        applyAll() {
            const b = document.body;
            b.dataset.theme = this.state.theme;
            b.dataset.density = this.state.density;
            b.dataset.sidebar = this.state.sidebar_mode;
            b.dataset.wallpaper = this.state.wallpaper;
            b.style.setProperty('--accent', this.state.accent);
            b.style.setProperty('--brand-500', this.state.accent);
        },
    };
}
</script>
