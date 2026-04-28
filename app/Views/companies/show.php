<?php use App\Core\Helpers; $slug = $tenant->slug; $c = $company;
$tierConfig = ['enterprise'=>['Enterprise','badge-purple'],'premium'=>['Premium','badge-blue'],'standard'=>['Standard','badge-gray']];
[$tl,$tcl] = $tierConfig[$c['tier']] ?? ['—','badge-gray']; ?>

<?php
$publicPortalCreate = $url('/portal/' . $slug . '/new?company=' . (int)$c['id']);
$internalCreate = $url('/t/' . $slug . '/tickets/create?company_id=' . (int)$c['id']);
?>

<a href="<?= $url('/t/' . $slug . '/companies') ?>" class="inline-flex items-center gap-1.5 text-[12.5px] text-ink-500 hover:text-ink-900 transition"><i class="lucide lucide-arrow-left text-[13px]"></i> Volver a empresas</a>

<div class="card card-pad flex flex-col lg:flex-row lg:items-center gap-5">
    <div class="avatar avatar-xl flex-shrink-0" style="background:<?= Helpers::colorFor($c['name']) ?>;color:white;border-radius:18px;"><?= strtoupper(substr($c['name'],0,2)) ?></div>
    <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2 flex-wrap">
            <h1 class="font-display font-extrabold text-[24px] tracking-[-0.022em]"><?= $e($c['name']) ?></h1>
            <span class="badge <?= $tcl ?>"><?= $tl ?></span>
        </div>
        <div class="text-[13px] mt-1 text-ink-400 flex items-center gap-2 flex-wrap">
            <?php if (!empty($c['industry'])): ?><span class="inline-flex items-center gap-1"><i class="lucide lucide-briefcase text-[12px]"></i><?= $e($c['industry']) ?></span><?php endif; ?>
            <?php if (!empty($c['size'])): ?><span class="inline-flex items-center gap-1"><i class="lucide lucide-users text-[12px]"></i><?= $e($c['size']) ?></span><?php endif; ?>
            <?php if (!empty($c['website'])): ?><a href="<?= $e($c['website']) ?>" target="_blank" class="inline-flex items-center gap-1 hover:text-brand-700"><i class="lucide lucide-external-link text-[12px]"></i><?= $e($c['website']) ?></a><?php endif; ?>
        </div>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <a href="<?= $internalCreate ?>" class="btn btn-primary btn-sm"><i class="lucide lucide-plus"></i> Nuevo ticket</a>
        <a href="<?= $publicPortalCreate ?>" target="_blank" class="btn btn-outline btn-sm"><i class="lucide lucide-external-link"></i> Portal público</a>
    </div>
</div>

<div class="rounded-[24px] p-6 relative overflow-hidden" style="background:linear-gradient(135deg,#fafafb 0%,#f3f0ff 100%);border:1px solid #cdbfff">
    <div class="absolute top-0 inset-x-0 h-[3px]" style="background:linear-gradient(90deg,#7c5cff,#d946ef)"></div>
    <div class="flex flex-col lg:flex-row lg:items-center gap-5">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl grid place-items-center flex-shrink-0" style="background:linear-gradient(135deg,#7c5cff,#a78bfa);color:white;box-shadow:0 8px 18px -6px rgba(124,92,255,.5)"><i class="lucide lucide-link-2 text-[20px]"></i></div>
            <div>
                <div class="text-[10.5px] font-bold uppercase tracking-[0.14em] text-brand-700">Link público de soporte</div>
                <div class="font-display font-bold text-[15px] tracking-[-0.015em]">Para que <?= $e($c['name']) ?> reporte tickets</div>
                <div class="text-[12px] mt-0.5 text-ink-500">Se autocompleta con la empresa al abrir</div>
            </div>
        </div>
        <div class="flex-1 lg:max-w-2xl flex items-center gap-2 px-3 py-2.5 rounded-xl bg-white border border-[#ececef] min-w-0">
            <i class="lucide lucide-globe text-[15px] text-ink-400 flex-shrink-0"></i>
            <input id="publicLinkInput" readonly value="<?= $e($publicPortalCreate) ?>" class="flex-1 bg-transparent text-[12px] font-mono text-ink-700 outline-none min-w-0">
            <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('publicLinkInput').value); this.querySelector('span').textContent='Copiado'; this.querySelector('i').setAttribute('data-lucide','check'); window.renderIcons && window.renderIcons()" class="btn btn-soft btn-xs flex-shrink-0"><i class="lucide lucide-copy text-[12px]"></i> <span>Copiar</span></button>
            <a href="<?= $publicPortalCreate ?>" target="_blank" class="btn btn-primary btn-xs flex-shrink-0"><i class="lucide lucide-arrow-up-right text-[12px]"></i> Abrir</a>
        </div>
    </div>
</div>

<div class="grid grid-cols-3 gap-4">
    <?php foreach ([['Tickets', count($tickets), 'inbox', '#dbeafe', '#1d4ed8'],['Contactos', count($contacts), 'users', '#fce7f3', '#be185d'],['Activos', count($assets), 'server', '#d1fae5', '#047857']] as [$l, $v, $ic, $bg, $col]): ?>
        <div class="stat-mini">
            <div class="stat-mini-icon" style="background:<?= $bg ?>;color:<?= $col ?>"><i class="lucide lucide-<?= $ic ?> text-base"></i></div>
            <div>
                <div class="stat-mini-meta"><?= $l ?></div>
                <div class="stat-mini-title"><?= $v ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="card overflow-hidden">
        <div class="px-6 pt-5"><h3 class="section-title">Tickets recientes</h3></div>
        <div class="px-3 py-3 mt-2">
            <?php foreach (array_slice($tickets, 0, 6) as $t): ?>
                <a href="<?= $url('/t/' . $slug . '/tickets/' . $t['id']) ?>" class="flex items-center gap-3 p-3 rounded-2xl hover:bg-[#f3f4f6] transition">
                    <span class="kbd font-mono"><?= $e($t['code']) ?></span>
                    <div class="flex-1 min-w-0 text-[13px] font-medium truncate"><?= $e($t['subject']) ?></div>
                    <?= Helpers::statusBadge($t['status']) ?>
                </a>
            <?php endforeach; ?>
            <?php if (empty($tickets)): ?><div class="py-8 text-center text-[13px] text-ink-400">Sin tickets</div><?php endif; ?>
        </div>
    </div>
    <div class="card overflow-hidden">
        <div class="px-6 pt-5"><h3 class="section-title">Contactos</h3></div>
        <div class="px-3 py-3 mt-2">
            <?php foreach ($contacts as $ct): ?>
                <div class="flex items-center gap-3 p-3 rounded-2xl">
                    <div class="avatar avatar-sm" style="background:<?= Helpers::colorFor($ct['email'] ?? $ct['name']) ?>;color:white"><?= Helpers::initials($ct['name']) ?></div>
                    <div class="min-w-0 flex-1">
                        <div class="text-[13px] font-display font-bold truncate"><?= $e($ct['name']) ?></div>
                        <div class="text-[11px] text-ink-400"><?= $e($ct['email'] ?? '—') ?></div>
                    </div>
                    <?php if ($ct['title']): ?><span class="badge badge-gray"><?= $e($ct['title']) ?></span><?php endif; ?>
                </div>
            <?php endforeach; ?>
            <?php if (empty($contacts)): ?><div class="py-8 text-center text-[13px] text-ink-400">Sin contactos</div><?php endif; ?>
        </div>
    </div>
    <div class="card overflow-hidden lg:col-span-2" x-data="{open:false, editing:null}">
        <div class="px-6 pt-5 flex items-center justify-between">
            <div>
                <h3 class="section-title">Acceso al portal de empresa</h3>
                <p class="text-[12px] text-ink-400 mt-0.5">Usuarios autenticados del portal vinculados a <strong><?= $e($c['name']) ?></strong>. Los <em>managers</em> ven dashboard, reportes y exportes.</p>
            </div>
            <button @click="open=!open;editing=null" type="button" class="btn btn-primary btn-sm"><i class="lucide lucide-user-plus text-[13px]"></i> <span x-text="open ? 'Cerrar' : 'Invitar usuario'"></span></button>
        </div>

        <div x-show="open" x-cloak class="px-6 pt-4 pb-2">
            <form method="POST" action="<?= $url('/t/' . $slug . '/companies/' . (int)$c['id'] . '/portal-users') ?>" class="grid grid-cols-1 md:grid-cols-2 gap-3 p-4 rounded-2xl bg-[#fafafb] border border-[#ececef]">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <div><label class="label">Nombre <span class="text-rose-600">*</span></label><input name="name" required class="input" placeholder="Juan Pérez"></div>
                <div><label class="label">Email <span class="text-rose-600">*</span></label><input name="email" type="email" required class="input" placeholder="juan@empresa.com"></div>
                <div><label class="label">Teléfono</label><input name="phone" class="input"></div>
                <div><label class="label">Contraseña <span class="text-ink-400 font-normal text-[11px]">(opcional, se genera una si la dejás vacía)</span></label><input name="password" type="text" class="input" placeholder="Mínimo 6 caracteres" minlength="6"></div>
                <div class="md:col-span-2 flex flex-wrap items-center gap-4">
                    <label class="inline-flex items-center gap-2 text-[12.5px] cursor-pointer">
                        <input type="checkbox" name="is_company_manager" value="1" class="rounded">
                        <span><strong>Manager</strong> · puede ver reportes, equipo y crear tickets en nombre de otros</span>
                    </label>
                    <label class="inline-flex items-center gap-2 text-[12.5px] cursor-pointer">
                        <input type="checkbox" name="send_invite" value="1" checked class="rounded">
                        <span>Enviar email de bienvenida con instrucciones</span>
                    </label>
                </div>
                <div class="md:col-span-2 flex justify-end gap-2 border-t border-[#ececef] pt-3">
                    <button type="button" @click="open=false" class="btn btn-soft btn-sm">Cancelar</button>
                    <button class="btn btn-primary btn-sm"><i class="lucide lucide-send text-[13px]"></i> Crear acceso</button>
                </div>
            </form>
        </div>

        <div class="px-3 py-3 mt-2">
            <?php if (empty($portalUsers)): ?>
                <div class="text-center py-10">
                    <i class="lucide lucide-user-x text-[28px] text-ink-300"></i>
                    <h4 class="font-display font-bold text-[13.5px] mt-2">Sin acceso al portal todavía</h4>
                    <p class="text-[12px] text-ink-400">Invitá al primer usuario para que pueda ver sus tickets, reportes y crear nuevos.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-[13px]">
                        <thead>
                            <tr class="bg-[#fafafb] border-b border-[#ececef]">
                                <th class="text-left py-2.5 px-4 text-[10.5px] uppercase tracking-[0.12em] font-bold text-ink-400">Persona</th>
                                <th class="text-left py-2.5 px-2 text-[10.5px] uppercase tracking-[0.12em] font-bold text-ink-400">Rol</th>
                                <th class="text-left py-2.5 px-2 text-[10.5px] uppercase tracking-[0.12em] font-bold text-ink-400">Estado</th>
                                <th class="text-left py-2.5 px-2 text-[10.5px] uppercase tracking-[0.12em] font-bold text-ink-400">Último login</th>
                                <th class="text-right py-2.5 px-4 text-[10.5px] uppercase tracking-[0.12em] font-bold text-ink-400">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($portalUsers as $pu): ?>
                            <tr class="border-b border-[#ececef]" x-show="editing !== <?= (int)$pu['id'] ?>">
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-xl text-white grid place-items-center font-display font-bold text-[12px]" style="background:linear-gradient(135deg,#7c5cff,#a78bfa)"><?= strtoupper(substr($pu['name'],0,1)) ?></div>
                                        <div class="min-w-0">
                                            <div class="font-display font-bold text-[12.5px] truncate"><?= $e($pu['name']) ?></div>
                                            <div class="text-[11px] text-ink-400 truncate"><?= $e($pu['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-2">
                                    <?php if (!empty($pu['is_company_manager'])): ?>
                                        <span class="badge" style="background:#f3f0ff;color:#5a3aff"><i class="lucide lucide-shield text-[11px]"></i> Manager</span>
                                    <?php else: ?>
                                        <span class="badge badge-gray">Miembro</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-2">
                                    <?php if ($pu['is_active']): ?>
                                        <span class="badge" style="background:#dcfce7;color:#15803d">Activo</span>
                                    <?php else: ?>
                                        <span class="badge badge-gray">Inactivo</span>
                                    <?php endif; ?>
                                    <?php if ($pu['email_verified_at']): ?><span class="badge" style="background:#dbeafe;color:#1d4ed8">Verificado</span><?php endif; ?>
                                </td>
                                <td class="py-3 px-2 text-[11.5px] text-ink-500"><?= $e($pu['last_login_at'] ?: '—') ?></td>
                                <td class="py-3 px-4 text-right">
                                    <div class="inline-flex gap-1">
                                        <button type="button" @click="editing=<?= (int)$pu['id'] ?>" class="btn btn-soft btn-xs" data-tooltip="Editar"><i class="lucide lucide-pencil text-[12px]"></i></button>
                                        <form method="POST" action="<?= $url('/t/' . $slug . '/companies/' . (int)$c['id'] . '/portal-users/' . (int)$pu['id'] . '/manager') ?>" data-tooltip="<?= !empty($pu['is_company_manager']) ? 'Quitar manager' : 'Hacer manager' ?>">
                                            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                                            <button class="btn <?= !empty($pu['is_company_manager']) ? 'btn-primary' : 'btn-soft' ?> btn-xs"><i class="lucide lucide-shield text-[12px]"></i></button>
                                        </form>
                                        <form method="POST" action="<?= $url('/t/' . $slug . '/companies/' . (int)$c['id'] . '/portal-users/' . (int)$pu['id'] . '/toggle') ?>" data-tooltip="Activar/Desactivar">
                                            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                                            <button class="btn btn-soft btn-xs"><i class="lucide lucide-power text-[12px]"></i></button>
                                        </form>
                                        <form method="POST" action="<?= $url('/t/' . $slug . '/companies/' . (int)$c['id'] . '/portal-users/' . (int)$pu['id'] . '/resend') ?>" data-tooltip="Reenviar invitación con nueva contraseña" onsubmit="return confirm('Reenviar invitación y resetear contraseña?')">
                                            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                                            <button class="btn btn-soft btn-xs"><i class="lucide lucide-mail text-[12px]"></i></button>
                                        </form>
                                        <form method="POST" action="<?= $url('/t/' . $slug . '/companies/' . (int)$c['id'] . '/portal-users/' . (int)$pu['id'] . '/delete') ?>" data-tooltip="Eliminar" onsubmit="return confirm('Eliminar acceso al portal?')">
                                            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                                            <button class="btn btn-soft btn-xs text-rose-600"><i class="lucide lucide-trash-2 text-[12px]"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <tr x-show="editing === <?= (int)$pu['id'] ?>" x-cloak>
                                <td colspan="5" class="p-4 bg-[#fafafb]">
                                    <form method="POST" action="<?= $url('/t/' . $slug . '/companies/' . (int)$c['id'] . '/portal-users/' . (int)$pu['id']) ?>" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                                        <div><label class="label">Nombre</label><input name="name" value="<?= $e($pu['name']) ?>" class="input"></div>
                                        <div><label class="label">Teléfono</label><input name="phone" value="<?= $e($pu['phone'] ?? '') ?>" class="input"></div>
                                        <div><label class="label">Nueva contraseña <span class="text-ink-400 font-normal text-[11px]">(opcional, mín 6)</span></label><input name="new_password" type="password" minlength="6" class="input"></div>
                                        <div class="flex items-end gap-4">
                                            <label class="inline-flex items-center gap-2 text-[12.5px] cursor-pointer">
                                                <input type="checkbox" name="is_company_manager" value="1" <?= !empty($pu['is_company_manager'])?'checked':'' ?> class="rounded">
                                                <span>Manager</span>
                                            </label>
                                            <label class="inline-flex items-center gap-2 text-[12.5px] cursor-pointer">
                                                <input type="checkbox" name="is_active" value="1" <?= $pu['is_active']?'checked':'' ?> class="rounded">
                                                <span>Activo</span>
                                            </label>
                                        </div>
                                        <div class="md:col-span-2 flex justify-end gap-2 border-t border-[#ececef] pt-3">
                                            <button type="button" @click="editing=null" class="btn btn-soft btn-sm">Cancelar</button>
                                            <button class="btn btn-primary btn-sm">Guardar cambios</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card overflow-hidden lg:col-span-2">
        <div class="px-6 pt-5"><h3 class="section-title">Activos asignados</h3></div>
        <div class="px-3 py-3 mt-2">
            <?php
            $typeIcons = ['laptop'=>'laptop','phone'=>'smartphone','monitor'=>'monitor','printer'=>'printer','network'=>'wifi','server'=>'server'];
            foreach ($assets as $a): ?>
                <div class="flex items-center gap-3 p-3 rounded-2xl">
                    <div class="w-10 h-10 rounded-xl bg-[#f3f4f6] grid place-items-center text-ink-500"><i class="lucide lucide-<?= $typeIcons[$a['type']] ?? 'server' ?> text-[15px]"></i></div>
                    <div class="flex-1 min-w-0">
                        <div class="text-[13px] font-display font-bold truncate"><?= $e($a['name']) ?></div>
                        <div class="text-[11px] text-ink-400"><?= $e($a['serial'] ?? '') ?> · <?= $e($a['model'] ?? '') ?></div>
                    </div>
                    <span class="badge badge-gray"><?= ucfirst($a['status']) ?></span>
                </div>
            <?php endforeach; ?>
            <?php if (empty($assets)): ?><div class="py-8 text-center text-[13px] text-ink-400">Sin activos</div><?php endif; ?>
        </div>
    </div>
</div>
