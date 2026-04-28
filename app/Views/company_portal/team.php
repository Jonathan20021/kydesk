<?php
$slug = $tenantPublic->slug;
ob_start(); ?>
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
    <div>
        <h1 class="font-display font-extrabold text-[24px] tracking-[-0.02em]">Equipo</h1>
        <p class="text-[12.5px] text-ink-500">Miembros del portal y contactos vinculados a <strong><?= $e($company['name']) ?></strong>.</p>
    </div>
</div>

<h2 class="font-display font-bold text-[14px] mb-2 mt-2 text-ink-700">Usuarios del portal · <?= count($portalUsers) ?></h2>
<div class="card overflow-hidden mb-6">
    <?php if (empty($portalUsers)): ?>
        <div class="text-center py-10 text-[13px] text-ink-400">Aún no hay usuarios registrados en el portal.</div>
    <?php else: ?>
        <table class="w-full text-[13px]">
            <thead class="bg-[#fafafb] border-b border-[#ececef]">
                <tr>
                    <th class="text-left py-3 px-4 text-[10.5px] uppercase tracking-[0.12em] font-bold text-ink-400">Persona</th>
                    <th class="text-left py-3 px-2 text-[10.5px] uppercase tracking-[0.12em] font-bold text-ink-400 hidden md:table-cell">Email</th>
                    <th class="text-left py-3 px-2 text-[10.5px] uppercase tracking-[0.12em] font-bold text-ink-400">Rol</th>
                    <th class="text-right py-3 px-2 text-[10.5px] uppercase tracking-[0.12em] font-bold text-ink-400">Tickets</th>
                    <th class="text-right py-3 px-2 text-[10.5px] uppercase tracking-[0.12em] font-bold text-ink-400">Abiertos</th>
                    <th class="text-right py-3 px-4 text-[10.5px] uppercase tracking-[0.12em] font-bold text-ink-400 hidden lg:table-cell">Último login</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($portalUsers as $u): ?>
                    <tr class="border-b border-[#ececef]">
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-xl text-white grid place-items-center font-display font-bold text-[12px]" style="background:linear-gradient(135deg,#7c5cff,#a78bfa)"><?= strtoupper(substr($u['name'],0,1)) ?></div>
                                <div class="min-w-0">
                                    <div class="font-display font-bold text-[12.5px] truncate"><?= $e($u['name']) ?></div>
                                    <?php if (!empty($u['phone'])): ?><div class="text-[11px] text-ink-400 truncate"><?= $e($u['phone']) ?></div><?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="py-3 px-2 text-[12px] truncate max-w-[220px] hidden md:table-cell"><?= $e($u['email']) ?></td>
                        <td class="py-3 px-2">
                            <?php if ($u['is_company_manager']): ?><span class="badge bg-brand-50 text-brand-700">Manager</span>
                            <?php else: ?><span class="badge bg-[#f3f4f6] text-ink-500">Miembro</span><?php endif; ?>
                            <?php if (!$u['is_active']): ?><span class="badge bg-rose-50 text-rose-700">Inactivo</span><?php endif; ?>
                        </td>
                        <td class="py-3 px-2 text-right font-display font-bold"><?= (int)$u['tickets_count'] ?></td>
                        <td class="py-3 px-2 text-right">
                            <?php $oc = (int)$u['open_count']; ?>
                            <span class="badge" style="background:<?= $oc>0?'#f59e0b15':'#16a34a15' ?>;color:<?= $oc>0?'#b45309':'#15803d' ?>"><?= $oc ?></span>
                        </td>
                        <td class="py-3 px-4 text-right text-[11px] text-ink-500 hidden lg:table-cell"><?= $e($u['last_login_at'] ?: '—') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<h2 class="font-display font-bold text-[14px] mb-2 mt-2 text-ink-700">Contactos · <?= count($contacts) ?></h2>
<p class="text-[11.5px] text-ink-400 mb-3">Cualquier solicitante histórico vinculado a tu empresa. Como manager podés crear tickets en nombre de cualquiera de ellos.</p>
<div class="card overflow-hidden">
    <?php if (empty($contacts)): ?>
        <div class="text-center py-8 text-[13px] text-ink-400">Sin contactos registrados.</div>
    <?php else: ?>
        <table class="w-full text-[13px]">
            <thead class="bg-[#fafafb] border-b border-[#ececef]">
                <tr>
                    <th class="text-left py-2.5 px-4 text-[10.5px] uppercase tracking-[0.12em] font-bold text-ink-400">Nombre</th>
                    <th class="text-left py-2.5 px-2 text-[10.5px] uppercase tracking-[0.12em] font-bold text-ink-400">Email</th>
                    <th class="text-left py-2.5 px-2 text-[10.5px] uppercase tracking-[0.12em] font-bold text-ink-400 hidden md:table-cell">Cargo</th>
                    <th class="text-left py-2.5 px-4 text-[10.5px] uppercase tracking-[0.12em] font-bold text-ink-400 hidden md:table-cell">Teléfono</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($contacts as $c): ?>
                    <tr class="border-b border-[#ececef]">
                        <td class="py-2.5 px-4 font-semibold"><?= $e($c['name']) ?></td>
                        <td class="py-2.5 px-2 text-[12px]"><?= $e($c['email'] ?: '—') ?></td>
                        <td class="py-2.5 px-2 text-[12px] hidden md:table-cell"><?= $e($c['title'] ?: '—') ?></td>
                        <td class="py-2.5 px-4 text-[12px] hidden md:table-cell"><?= $e($c['phone'] ?: '—') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php $bodyContent = ob_get_clean();
include __DIR__ . '/_shell.php'; ?>
