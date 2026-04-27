<?php $slug = $tenant->slug; ?>

<div class="flex items-end justify-between mb-5 flex-wrap gap-3">
    <div>
        <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">Email-to-Ticket</h1>
        <p class="text-[13px] text-ink-400">Recibí emails y convertilos en tickets automáticamente · IMAP o forward webhook</p>
    </div>
</div>

<div class="grid grid-cols-2 lg:grid-cols-3 gap-3 mb-5">
    <div class="card card-pad"><div class="text-[10.5px] uppercase font-bold text-ink-400 tracking-[0.14em]">Buzones</div><div class="font-display font-extrabold text-[26px]"><?= $stats['accounts'] ?></div></div>
    <div class="card card-pad"><div class="text-[10.5px] uppercase font-bold text-ink-400 tracking-[0.14em]">Inbound hoy</div><div class="font-display font-extrabold text-[26px] text-emerald-600"><?= $stats['inbound_today'] ?></div></div>
    <div class="card card-pad"><div class="text-[10.5px] uppercase font-bold text-ink-400 tracking-[0.14em]">Tickets vía email</div><div class="font-display font-extrabold text-[26px] text-brand-700"><?= $stats['tickets_via_email'] ?></div></div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <!-- New account -->
    <div class="card card-pad lg:col-span-1" x-data="{method:'imap'}">
        <h3 class="font-display font-bold text-[15px] mb-3 flex items-center gap-2"><i class="lucide lucide-plus text-brand-600"></i> Nuevo buzón</h3>
        <form method="POST" action="<?= $url('/t/' . $slug . '/email-inbound') ?>" class="space-y-3">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
            <div><label class="label">Nombre</label><input name="name" required class="input" placeholder="Ej: Soporte"></div>
            <div><label class="label">Email</label><input name="email" type="email" required class="input" placeholder="soporte@miempresa.com"></div>
            <div>
                <label class="label">Método</label>
                <select name="fetch_method" x-model="method" class="input">
                    <option value="imap">IMAP (pull)</option>
                    <option value="forward">Forward webhook (push)</option>
                </select>
            </div>
            <div x-show="method==='imap'" x-cloak class="space-y-2">
                <div class="grid grid-cols-2 gap-2">
                    <div><label class="label">Host</label><input name="imap_host" class="input" placeholder="imap.gmail.com"></div>
                    <div><label class="label">Puerto</label><input name="imap_port" type="number" value="993" class="input"></div>
                </div>
                <div><label class="label">Usuario</label><input name="imap_user" class="input"></div>
                <div><label class="label">Contraseña</label><input name="imap_pass" type="password" class="input"></div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="label">Encriptación</label>
                        <select name="imap_encryption" class="input">
                            <option value="ssl">SSL</option>
                            <option value="tls">TLS</option>
                            <option value="none">Ninguna</option>
                        </select>
                    </div>
                    <div><label class="label">Folder</label><input name="imap_folder" value="INBOX" class="input"></div>
                </div>
            </div>
            <div>
                <label class="label">Categoría por defecto</label>
                <select name="default_category_id" class="input">
                    <option value="">— Ninguna —</option>
                    <?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>"><?= $e($c['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="label">Prioridad</label>
                    <select name="default_priority" class="input">
                        <option value="low">Baja</option>
                        <option value="medium" selected>Media</option>
                        <option value="high">Alta</option>
                        <option value="urgent">Urgente</option>
                    </select>
                </div>
                <div>
                    <label class="label">Auto-asignar</label>
                    <select name="auto_assign_to" class="input">
                        <option value="">—</option>
                        <?php foreach ($users as $u): ?><option value="<?= (int)$u['id'] ?>"><?= $e($u['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>
            <label class="flex items-center gap-2 text-[13px]"><input type="checkbox" name="is_active" value="1" checked> Activo</label>
            <button class="btn btn-primary w-full"><i class="lucide lucide-check"></i> Crear buzón</button>
        </form>
    </div>

    <div class="lg:col-span-2 space-y-2">
        <?php foreach ($accounts as $a):
            $forwardUrl = $appUrl . '/api/v1/email-inbound/forward';
        ?>
            <div class="card card-pad" x-data="{open:false}">
                <div class="flex items-center gap-3 flex-wrap">
                    <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-600 grid place-items-center"><i class="lucide lucide-<?= $a['fetch_method']==='imap'?'mail-open':'webhook' ?> text-[16px]"></i></div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <div class="font-display font-bold text-[14px]"><?= $e($a['name']) ?></div>
                            <span class="badge badge-purple"><?= strtoupper($a['fetch_method']) ?></span>
                            <?php if (!$a['is_active']): ?><span class="badge badge-gray">Pausado</span><?php endif; ?>
                            <?php if (!empty($a['last_error'])): ?><span class="badge badge-red">Error</span><?php endif; ?>
                        </div>
                        <div class="text-[12px] text-ink-500 mt-0.5 font-mono"><?= $e($a['email']) ?></div>
                        <?php if ($a['fetch_method']==='imap' && $a['last_fetched_at']): ?>
                            <div class="text-[11px] text-ink-400 mt-0.5">Último fetch: <?= $e($a['last_fetched_at']) ?> · <?= (int)$a['fetch_count'] ?> emails</div>
                        <?php endif; ?>
                    </div>
                    <?php if ($a['fetch_method']==='imap'): ?>
                        <form method="POST" action="<?= $url('/t/' . $slug . '/email-inbound/' . $a['id'] . '/fetch') ?>"><input type="hidden" name="_csrf" value="<?= $e($csrf) ?>"><button class="btn btn-soft btn-xs"><i class="lucide lucide-refresh-cw text-[12px]"></i> Fetch ahora</button></form>
                    <?php endif; ?>
                    <button @click="open=!open" class="btn btn-soft btn-xs"><i class="lucide lucide-pencil text-[12px]"></i></button>
                </div>

                <?php if ($a['fetch_method']==='forward'): ?>
                    <div class="mt-3 p-3 rounded-lg" style="background:#f3f0ff;border:1px solid #cdbfff">
                        <div class="text-[11px] uppercase font-bold tracking-[0.12em] text-brand-700 mb-1">URL de webhook</div>
                        <div class="font-mono text-[11.5px] break-all text-ink-700"><?= $e($forwardUrl) ?></div>
                        <div class="text-[11px] uppercase font-bold tracking-[0.12em] text-brand-700 mt-2 mb-1">Header requerido</div>
                        <div class="font-mono text-[11.5px] break-all text-ink-700">x-forward-token: <?= $e($a['forward_token']) ?></div>
                        <p class="text-[11px] text-ink-500 mt-2">Configurá este endpoint en tu proveedor (Mailgun, SendGrid, Postmark, IFTTT, n8n, Zapier, etc.) para recibir emails y crearlos como tickets.</p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($a['last_error'])): ?>
                    <div class="mt-3 p-3 rounded-lg bg-rose-50 border border-rose-200 text-[12px] text-rose-700"><i class="lucide lucide-alert-circle text-[12px]"></i> <?= $e($a['last_error']) ?></div>
                <?php endif; ?>

                <div x-show="open" x-cloak class="mt-3 pt-3" style="border-top:1px solid var(--border)">
                    <form method="POST" action="<?= $url('/t/' . $slug . '/email-inbound/' . $a['id']) ?>" class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                        <input name="name" value="<?= $e($a['name']) ?>" class="input" placeholder="Nombre">
                        <select name="default_priority" class="input">
                            <?php foreach (['low'=>'Baja','medium'=>'Media','high'=>'Alta','urgent'=>'Urgente'] as $k=>$lbl): ?>
                                <option value="<?= $k ?>" <?= $a['default_priority']===$k?'selected':'' ?>><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($a['fetch_method']==='imap'): ?>
                            <input name="imap_host" value="<?= $e($a['imap_host']) ?>" class="input" placeholder="Host IMAP">
                            <input name="imap_port" type="number" value="<?= (int)$a['imap_port'] ?>" class="input">
                            <input name="imap_user" value="<?= $e($a['imap_user']) ?>" class="input" placeholder="Usuario">
                            <input name="imap_pass" type="password" class="input" placeholder="Dejar vacío para conservar">
                            <select name="imap_encryption" class="input">
                                <?php foreach (['ssl','tls','none'] as $enc): ?>
                                    <option value="<?= $enc ?>" <?= $a['imap_encryption']===$enc?'selected':'' ?>><?= strtoupper($enc) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input name="imap_folder" value="<?= $e($a['imap_folder']) ?>" class="input">
                        <?php endif; ?>
                        <select name="default_category_id" class="input">
                            <option value="">— Sin categoría por defecto —</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= (int)$c['id'] ?>" <?= (int)$a['default_category_id']===(int)$c['id']?'selected':'' ?>><?= $e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="auto_assign_to" class="input">
                            <option value="">— Sin auto-asignar —</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= (int)$u['id'] ?>" <?= (int)$a['auto_assign_to']===(int)$u['id']?'selected':'' ?>><?= $e($u['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label class="md:col-span-2 flex items-center gap-2 text-[13px]"><input type="checkbox" name="is_active" value="1" <?= $a['is_active']?'checked':'' ?>> Activo</label>
                        <div class="md:col-span-2 flex justify-between gap-2 pt-2" style="border-top:1px solid var(--border)">
                            <button type="button" onclick="if(confirm('Eliminar buzón?')) document.getElementById('del-em-<?= (int)$a['id'] ?>').submit()" class="btn btn-outline btn-sm" style="color:#b91c1c"><i class="lucide lucide-trash-2"></i> Eliminar</button>
                            <button class="btn btn-primary btn-sm">Guardar</button>
                        </div>
                    </form>
                    <form id="del-em-<?= (int)$a['id'] ?>" method="POST" action="<?= $url('/t/' . $slug . '/email-inbound/' . $a['id'] . '/delete') ?>" style="display:none">
                        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($accounts)): ?>
            <div class="card card-pad text-center py-12">
                <i class="lucide lucide-mail-open text-[24px] text-ink-300"></i>
                <h3 class="font-display font-bold mt-3">Sin buzones configurados</h3>
                <p class="text-[12.5px] text-ink-400 mt-1">Configurá un buzón para empezar a recibir emails como tickets.</p>
            </div>
        <?php endif; ?>

        <!-- Recent emails -->
        <?php if (!empty($messages)): ?>
            <div class="card overflow-hidden mt-4">
                <div class="p-4" style="border-bottom:1px solid var(--border)">
                    <h3 class="font-display font-bold text-[15px]">Últimos emails recibidos</h3>
                </div>
                <table class="admin-table">
                    <thead><tr><th>Fecha</th><th>De</th><th>Asunto</th><th>Buzón</th><th>Ticket</th></tr></thead>
                    <tbody>
                        <?php foreach (array_slice($messages, 0, 25) as $m): ?>
                            <tr>
                                <td class="text-[11.5px] font-mono text-ink-500"><?= $e($m['received_at']) ?></td>
                                <td>
                                    <div class="text-[12px] font-mono"><?= $e($m['from_email'] ?? '—') ?></div>
                                    <?php if (!empty($m['from_name'])): ?><div class="text-[11px] text-ink-400"><?= $e($m['from_name']) ?></div><?php endif; ?>
                                </td>
                                <td class="text-[12.5px]"><?= $e(mb_strimwidth($m['subject'] ?? '', 0, 80, '…')) ?></td>
                                <td class="text-[12px] text-ink-500"><?= $e($m['account_name'] ?? '—') ?></td>
                                <td>
                                    <?php if (!empty($m['ticket_code'])): ?>
                                        <a href="<?= $url('/t/' . $slug . '/tickets/' . (int)$m['ticket_id']) ?>" class="text-brand-700 font-mono text-[11.5px]"><?= $e($m['ticket_code']) ?></a>
                                    <?php else: ?>—<?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
