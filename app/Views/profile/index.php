<?php
use App\Core\Helpers;
$slug = $tenant->slug;
$u = $auth->user();

// Stats del usuario
$assignedOpen = (int)$app->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND assigned_to=? AND status IN ('open','in_progress')", [$tenant->id, $u['id']]);
$assignedTotal = (int)$app->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND assigned_to=?", [$tenant->id, $u['id']]);
$resolvedByMe = (int)$app->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND assigned_to=? AND status IN ('resolved','closed')", [$tenant->id, $u['id']]);
$createdByMe = (int)$app->db->val("SELECT COUNT(*) FROM tickets WHERE tenant_id=? AND created_by=?", [$tenant->id, $u['id']]);
$commentsByMe = (int)$app->db->val("SELECT COUNT(*) FROM ticket_comments WHERE tenant_id=? AND user_id=?", [$tenant->id, $u['id']]);
$notesCount = (int)$app->db->val("SELECT COUNT(*) FROM notes WHERE tenant_id=? AND user_id=?", [$tenant->id, $u['id']]);
$todosOpen = (int)$app->db->val("SELECT COUNT(*) FROM todos WHERE tenant_id=? AND user_id=? AND completed=0", [$tenant->id, $u['id']]);
$resolutionRate = $assignedTotal > 0 ? round($resolvedByMe * 100 / $assignedTotal) : 0;

// Tickets recientes asignados
$recentTickets = $app->db->all(
    "SELECT id, code, subject, priority, status, created_at FROM tickets WHERE tenant_id=? AND assigned_to=? ORDER BY updated_at DESC LIMIT 5",
    [$tenant->id, $u['id']]
);

// Joined date
$joined = $u['created_at'] ?? null;
$joinedAgo = $joined ? Helpers::ago($joined) : 'reciente';
?>

<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
    <div>
        <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">Mi perfil</h1>
        <p class="text-[13px] text-ink-400">Tu información personal, actividad y configuración de seguridad</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="<?= $url('/t/' . $slug . '/preferences') ?>" class="btn btn-outline btn-sm"><i class="lucide lucide-palette"></i> Personalizar panel</a>
        <span class="badge badge-emerald inline-flex"><span class="pulse"></span> Cuenta activa</span>
    </div>
</div>

<!-- HERO CARD -->
<div class="profile-hero max-w-5xl">
    <div class="profile-hero-cover" style="background:linear-gradient(135deg,<?= Helpers::colorFor($u['email']) ?> 0%,<?= Helpers::colorFor($u['name']) ?> 100%)">
        <div class="profile-hero-shapes">
            <div class="hero-shape s1"></div>
            <div class="hero-shape s2"></div>
            <div class="hero-shape s3"></div>
        </div>
    </div>
    <div class="profile-hero-body">
        <div class="profile-hero-avatar" style="background:<?= Helpers::colorFor($u['email']) ?>;color:white"><?= Helpers::initials($u['name']) ?></div>
        <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between gap-3 flex-wrap">
                <div class="min-w-0">
                    <h2 class="font-display font-extrabold text-[26px] tracking-[-0.022em] leading-tight"><?= $e($u['name']) ?></h2>
                    <div class="flex items-center gap-1.5 text-[13px] text-ink-500 mt-1">
                        <i class="lucide lucide-mail text-[13px]"></i> <?= $e($u['email']) ?>
                    </div>
                </div>
                <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="badge badge-purple"><i class="lucide lucide-shield text-[11px]"></i> <?= $e($u['role_name'] ?? 'Miembro') ?></span>
                    <?php if ($u['is_technician']): ?><span class="badge badge-gray"><i class="lucide lucide-wrench text-[11px]"></i> Técnico</span><?php endif; ?>
                    <?php if (!empty($u['title'])): ?><span class="badge badge-blue"><i class="lucide lucide-briefcase text-[11px]"></i> <?= $e($u['title']) ?></span><?php endif; ?>
                </div>
            </div>

            <div class="flex items-center gap-4 mt-3 flex-wrap text-[12px] text-ink-500">
                <span class="inline-flex items-center gap-1.5"><i class="lucide lucide-building-2 text-[13px]"></i> <?= $e($tenant->name) ?></span>
                <span class="inline-flex items-center gap-1.5"><i class="lucide lucide-calendar text-[13px]"></i> Miembro desde <?= $joinedAgo ?></span>
                <?php if (!empty($u['last_login_at'])): ?>
                    <span class="inline-flex items-center gap-1.5"><i class="lucide lucide-log-in text-[13px]"></i> Último acceso <?= Helpers::ago($u['last_login_at']) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- STATS -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 max-w-5xl">
    <div class="card card-pad p-stat">
        <div class="p-stat-icon" style="background:#dbeafe;color:#1d4ed8"><i class="lucide lucide-inbox"></i></div>
        <div class="p-stat-label">Asignados abiertos</div>
        <div class="p-stat-value"><?= $assignedOpen ?></div>
    </div>
    <div class="card card-pad p-stat">
        <div class="p-stat-icon" style="background:#d1fae5;color:#047857"><i class="lucide lucide-check-circle"></i></div>
        <div class="p-stat-label">Resueltos</div>
        <div class="p-stat-value"><?= $resolvedByMe ?></div>
        <div class="text-[11px] text-ink-400 mt-1"><?= $resolutionRate ?>% tasa de resolución</div>
    </div>
    <div class="card card-pad p-stat">
        <div class="p-stat-icon" style="background:#fef3c7;color:#b45309"><i class="lucide lucide-message-square"></i></div>
        <div class="p-stat-label">Comentarios</div>
        <div class="p-stat-value"><?= $commentsByMe ?></div>
    </div>
    <div class="card card-pad p-stat">
        <div class="p-stat-icon" style="background:#f3f0ff;color:#5a3aff"><i class="lucide lucide-list-checks"></i></div>
        <div class="p-stat-label">Tareas pendientes</div>
        <div class="p-stat-value"><?= $todosOpen ?></div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 max-w-5xl">
    <!-- LEFT COLUMN: forms -->
    <div class="lg:col-span-2 space-y-4">
        <!-- INFO PERSONAL -->
        <form method="POST" action="<?= $url('/t/' . $slug . '/profile') ?>" class="card card-pad" x-data="{dirty:false}">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

            <div class="section-head">
                <div class="section-head-icon"><i class="lucide lucide-user-round-cog text-[16px]"></i></div>
                <div class="flex-1">
                    <h3 class="section-title">Información personal</h3>
                    <div class="section-head-meta">Datos visibles para tu equipo en el workspace</div>
                </div>
                <span x-show="dirty" x-cloak class="text-[11px] text-amber-700 font-semibold inline-flex items-center gap-1"><i class="lucide lucide-circle-dot text-[10px]"></i> Sin guardar</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4" @input="dirty=true">
                <div>
                    <label class="label">Nombre completo</label>
                    <div class="relative">
                        <i class="lucide lucide-user text-[14px] absolute left-3.5 top-1/2 -translate-y-1/2 text-ink-400 pointer-events-none"></i>
                        <input name="name" value="<?= $e($u['name']) ?>" required class="input pl-10">
                    </div>
                </div>
                <div>
                    <label class="label">Email <span class="text-[10.5px] text-ink-400 font-normal">(no editable)</span></label>
                    <div class="relative">
                        <i class="lucide lucide-mail text-[14px] absolute left-3.5 top-1/2 -translate-y-1/2 text-ink-400 pointer-events-none"></i>
                        <input value="<?= $e($u['email']) ?>" disabled class="input pl-10" style="background:#fafafb;color:#6b6b78">
                        <i class="lucide lucide-lock text-[12px] absolute right-3.5 top-1/2 -translate-y-1/2 text-ink-300 pointer-events-none"></i>
                    </div>
                </div>
                <div>
                    <label class="label">Cargo</label>
                    <div class="relative">
                        <i class="lucide lucide-briefcase text-[14px] absolute left-3.5 top-1/2 -translate-y-1/2 text-ink-400 pointer-events-none"></i>
                        <input name="title" value="<?= $e($u['title']) ?>" placeholder="Ej. Soporte N2" class="input pl-10">
                    </div>
                </div>
                <div>
                    <label class="label">Teléfono</label>
                    <div class="relative">
                        <i class="lucide lucide-phone text-[14px] absolute left-3.5 top-1/2 -translate-y-1/2 text-ink-400 pointer-events-none"></i>
                        <input name="phone" value="<?= $e($u['phone']) ?>" placeholder="+1 809 000 0000" class="input pl-10">
                    </div>
                </div>
            </div>

            <div class="section-head mt-8">
                <div class="section-head-icon" style="background:#fef2f2;color:#b91c1c"><i class="lucide lucide-shield-check text-[16px]"></i></div>
                <div>
                    <h3 class="section-title">Seguridad</h3>
                    <div class="section-head-meta">Cambia tu contraseña periódicamente para mantener tu cuenta segura</div>
                </div>
            </div>

            <div x-data="{pwd:'',show:false,score(){let s=0;if(this.pwd.length>=8)s++;if(/[A-Z]/.test(this.pwd))s++;if(/[0-9]/.test(this.pwd))s++;if(/[^A-Za-z0-9]/.test(this.pwd))s++;return s;}}">
                <label class="label">Nueva contraseña</label>
                <div class="relative">
                    <i class="lucide lucide-lock text-[14px] absolute left-3.5 top-1/2 -translate-y-1/2 text-ink-400 pointer-events-none"></i>
                    <input name="password" :type="show?'text':'password'" x-model="pwd" @input="dirty=true" minlength="6" placeholder="Vacío para no cambiar" class="input pl-10 pr-12">
                    <button type="button" @click="show=!show" class="absolute right-2.5 top-1/2 -translate-y-1/2 w-7 h-7 grid place-items-center text-ink-400 hover:text-ink-900 rounded-md transition" tabindex="-1"><i :class="show?'lucide-eye-off':'lucide-eye'" class="lucide text-[14px]"></i></button>
                </div>
                <div class="mt-2.5" x-show="pwd.length>0" x-cloak>
                    <div class="flex gap-1 mb-1">
                        <template x-for="i in 4">
                            <div class="h-1.5 flex-1 rounded-full transition" :class="score() >= i ? (score()<=1?'bg-rose-500':score()<=2?'bg-amber-500':score()<=3?'bg-yellow-500':'bg-emerald-500') : 'bg-[#ececef]'"></div>
                        </template>
                    </div>
                    <p class="text-[11px] text-ink-500" x-text="score()<=1?'Débil — agregá mayúsculas, números y símbolos':score()<=2?'Aceptable — podés mejorarla':score()<=3?'Buena':'Excelente'"></p>
                </div>
                <ul class="mt-3 grid grid-cols-2 gap-x-3 gap-y-1 text-[11.5px] text-ink-500">
                    <li class="inline-flex items-center gap-1.5"><i :class="pwd.length>=8?'lucide-check-circle text-emerald-600':'lucide-circle text-ink-300'" class="lucide text-[12px]"></i> 8+ caracteres</li>
                    <li class="inline-flex items-center gap-1.5"><i :class="/[A-Z]/.test(pwd)?'lucide-check-circle text-emerald-600':'lucide-circle text-ink-300'" class="lucide text-[12px]"></i> Mayúscula</li>
                    <li class="inline-flex items-center gap-1.5"><i :class="/[0-9]/.test(pwd)?'lucide-check-circle text-emerald-600':'lucide-circle text-ink-300'" class="lucide text-[12px]"></i> Número</li>
                    <li class="inline-flex items-center gap-1.5"><i :class="/[^A-Za-z0-9]/.test(pwd)?'lucide-check-circle text-emerald-600':'lucide-circle text-ink-300'" class="lucide text-[12px]"></i> Símbolo</li>
                </ul>
            </div>

            <div class="flex justify-between items-center pt-6 mt-6 gap-2 border-t border-[#ececef] flex-wrap">
                <p class="text-[11.5px] text-ink-400 inline-flex items-center gap-1.5"><i class="lucide lucide-shield-check text-[12px] text-emerald-600"></i> Tus datos viajan cifrados</p>
                <div class="flex gap-2">
                    <a href="<?= $url('/t/' . $slug . '/dashboard') ?>" class="btn btn-outline btn-sm">Cancelar</a>
                    <button class="btn btn-primary btn-sm"><i class="lucide lucide-save"></i> Guardar cambios</button>
                </div>
            </div>
        </form>

        <!-- ACTIVIDAD RECIENTE -->
        <div class="card card-pad">
            <div class="section-head" style="margin-bottom:14px">
                <div class="section-head-icon" style="background:#f3f0ff;color:#5a3aff"><i class="lucide lucide-activity text-[16px]"></i></div>
                <div class="flex-1">
                    <h3 class="section-title">Actividad reciente</h3>
                    <div class="section-head-meta">Tickets en los que participaste recientemente</div>
                </div>
                <a href="<?= $url('/t/' . $slug . '/tickets?assigned_to=' . $u['id']) ?>" class="text-[12px] font-semibold text-brand-700">Ver todos →</a>
            </div>

            <?php if (empty($recentTickets)): ?>
                <div class="text-center py-8 text-ink-400">
                    <i class="lucide lucide-inbox text-[28px] block mx-auto mb-2 opacity-40"></i>
                    <div class="text-[13px]">Sin tickets asignados todavía</div>
                </div>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($recentTickets as $tk):
                        $statusMap = ['open'=>['Abierto','#dbeafe','#1d4ed8'],'in_progress'=>['En progreso','#fef3c7','#b45309'],'on_hold'=>['En espera','#f3f4f6','#6b6b78'],'resolved'=>['Resuelto','#d1fae5','#047857'],'closed'=>['Cerrado','#f3f4f6','#6b6b78']];
                        [$sLbl,$sBg,$sCol] = $statusMap[$tk['status']] ?? ['?','#f3f4f6','#6b6b78'];
                        $prioMap = ['low'=>['#94a3b8','Baja'],'medium'=>['#3b82f6','Media'],'high'=>['#f59e0b','Alta'],'urgent'=>['#ef4444','Urgente']];
                        [$pCol,$pLbl] = $prioMap[$tk['priority']] ?? ['#6b6b78','—'];
                    ?>
                        <a href="<?= $url('/t/' . $slug . '/tickets/' . $tk['id']) ?>" class="flex items-center gap-3 p-3 rounded-xl border border-[#ececef] hover:border-brand-300 hover:bg-brand-50/30 transition group">
                            <span class="w-1 self-stretch rounded-full" style="background:<?= $pCol ?>"></span>
                            <div class="flex-1 min-w-0">
                                <div class="font-display font-bold text-[13px] truncate group-hover:text-brand-700 transition"><?= $e($tk['subject']) ?></div>
                                <div class="text-[11px] text-ink-400 mt-0.5 flex items-center gap-2">
                                    <span class="font-mono"><?= $e($tk['code']) ?></span>
                                    <span>·</span>
                                    <span><?= Helpers::ago($tk['created_at']) ?></span>
                                </div>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-[10.5px] font-bold uppercase tracking-[0.1em]" style="background:<?= $sBg ?>;color:<?= $sCol ?>"><?= $sLbl ?></span>
                            <i class="lucide lucide-chevron-right text-[14px] text-ink-300 group-hover:text-brand-600 transition"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- RIGHT COLUMN: side widgets -->
    <div class="space-y-4">
        <!-- COMPLETION CHECKLIST -->
        <?php
        $checklist = [
            ['Nombre completo', !empty($u['name'])],
            ['Email verificado', !empty($u['email'])],
            ['Cargo', !empty($u['title'])],
            ['Teléfono', !empty($u['phone'])],
            ['Avatar', !empty($u['avatar'])],
        ];
        $done = count(array_filter($checklist, fn($c) => $c[1]));
        $total = count($checklist);
        $pct = round($done * 100 / $total);
        ?>
        <div class="card card-pad" style="background:linear-gradient(135deg,#fafafb 0%,#f3f0ff 100%)">
            <div class="flex items-start gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl grid place-items-center flex-shrink-0" style="background:linear-gradient(135deg,#7c5cff,#a78bfa);color:white;box-shadow:0 6px 14px -4px rgba(124,92,255,.4)"><i class="lucide lucide-target text-[16px]"></i></div>
                <div class="flex-1">
                    <div class="font-display font-bold text-[14px]">Completá tu perfil</div>
                    <div class="text-[11.5px] text-ink-500"><?= $done ?> de <?= $total ?> elementos completados</div>
                </div>
            </div>
            <div class="h-2 rounded-full overflow-hidden mb-4" style="background:rgba(124,92,255,.12)">
                <div class="h-full rounded-full transition-all" style="width:<?= $pct ?>%;background:linear-gradient(90deg,#7c5cff,#a78bfa)"></div>
            </div>
            <ul class="space-y-2">
                <?php foreach ($checklist as [$lbl, $ok]): ?>
                    <li class="flex items-center gap-2 text-[12.5px] <?= $ok ? 'text-ink-700' : 'text-ink-400' ?>">
                        <span class="w-4 h-4 rounded-full grid place-items-center flex-shrink-0" style="background:<?= $ok ? '#d1fae5' : '#f3f4f6' ?>;color:<?= $ok ? '#047857' : '#b8b8c4' ?>"><i class="lucide lucide-<?= $ok ? 'check' : 'circle' ?> text-[10px]"></i></span>
                        <span class="<?= $ok ? 'line-through opacity-60' : '' ?>"><?= $e($lbl) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- QUICK ACTIONS -->
        <div class="card card-pad">
            <div class="section-head" style="margin-bottom:12px">
                <div class="section-head-icon" style="background:#fef3c7;color:#b45309"><i class="lucide lucide-zap text-[16px]"></i></div>
                <div>
                    <h3 class="section-title">Atajos</h3>
                    <div class="section-head-meta">Acciones frecuentes</div>
                </div>
            </div>
            <div class="space-y-1.5">
                <a href="<?= $url('/t/' . $slug . '/preferences') ?>" class="flex items-center gap-3 p-3 rounded-xl border border-[#ececef] hover:border-brand-300 hover:bg-brand-50/30 transition">
                    <i class="lucide lucide-palette text-[16px] text-brand-600"></i>
                    <div class="flex-1 min-w-0">
                        <div class="font-display font-bold text-[12.5px]">Personalizar panel</div>
                        <div class="text-[11px] text-ink-400">Tema, densidad, sidebar</div>
                    </div>
                    <i class="lucide lucide-chevron-right text-[14px] text-ink-300"></i>
                </a>
                <a href="<?= $url('/t/' . $slug . '/notes') ?>" class="flex items-center gap-3 p-3 rounded-xl border border-[#ececef] hover:border-brand-300 hover:bg-brand-50/30 transition">
                    <i class="lucide lucide-notebook-pen text-[16px] text-amber-600"></i>
                    <div class="flex-1 min-w-0">
                        <div class="font-display font-bold text-[12.5px]">Mis notas</div>
                        <div class="text-[11px] text-ink-400"><?= $notesCount ?> guardadas</div>
                    </div>
                    <i class="lucide lucide-chevron-right text-[14px] text-ink-300"></i>
                </a>
                <a href="<?= $url('/t/' . $slug . '/todos') ?>" class="flex items-center gap-3 p-3 rounded-xl border border-[#ececef] hover:border-brand-300 hover:bg-brand-50/30 transition">
                    <i class="lucide lucide-check-square text-[16px] text-emerald-600"></i>
                    <div class="flex-1 min-w-0">
                        <div class="font-display font-bold text-[12.5px]">Mis tareas</div>
                        <div class="text-[11px] text-ink-400"><?= $todosOpen ?> pendientes</div>
                    </div>
                    <i class="lucide lucide-chevron-right text-[14px] text-ink-300"></i>
                </a>
                <form method="POST" action="<?= $url('/auth/logout') ?>">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    <button class="w-full flex items-center gap-3 p-3 rounded-xl border border-rose-100 hover:bg-rose-50 text-rose-600 transition">
                        <i class="lucide lucide-log-out text-[16px]"></i>
                        <div class="flex-1 min-w-0 text-left">
                            <div class="font-display font-bold text-[12.5px]">Cerrar sesión</div>
                            <div class="text-[11px] text-rose-400">En todos los dispositivos no</div>
                        </div>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.profile-hero { background:white; border:1px solid #ececef; border-radius:24px; overflow:hidden; box-shadow:0 1px 2px rgba(22,21,27,.04); }
.profile-hero-cover { height:120px; position:relative; overflow:hidden; }
.profile-hero-shapes { position:absolute; inset:0; pointer-events:none; }
.hero-shape { position:absolute; border-radius:50%; }
.hero-shape.s1 { width:240px; height:240px; top:-100px; right:-50px; background:rgba(255,255,255,.18); filter:blur(20px); }
.hero-shape.s2 { width:160px; height:160px; bottom:-80px; left:20%; background:rgba(255,255,255,.12); filter:blur(20px); }
.hero-shape.s3 { width:120px; height:120px; top:30%; left:50%; background:rgba(255,255,255,.08); filter:blur(15px); }
.profile-hero-body { padding:0 28px 24px; display:flex; align-items:flex-end; gap:20px; flex-wrap:wrap; margin-top:-50px; position:relative; }
.profile-hero-avatar { width:100px; height:100px; border-radius:24px; display:grid; place-items:center; font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:36px; box-shadow:0 12px 30px -6px rgba(22,21,27,.18); border:5px solid white; flex-shrink:0; }

.p-stat { display:flex; align-items:flex-start; gap:14px; padding:18px 18px 16px; }
.p-stat-icon { width:42px; height:42px; border-radius:12px; display:grid; place-items:center; flex-shrink:0; }
.p-stat-icon i { font-size:18px; }
.p-stat-label { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.12em; color:#6b6b78; }
.p-stat-value { font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:24px; letter-spacing:-.02em; line-height:1.1; color:#16151b; margin-top:2px; }
@media (max-width:640px) {
    .profile-hero-body { flex-direction:column; align-items:flex-start; }
    .profile-hero-avatar { width:80px; height:80px; font-size:28px; }
}
</style>
