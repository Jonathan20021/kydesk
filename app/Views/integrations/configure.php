<?php
$slug = $tenant->slug;
$isEdit = !empty($integration);
$config = $isEdit ? (json_decode((string)$integration['config'], true) ?: []) : [];
$selectedEvents = $isEdit ? (json_decode((string)$integration['events'], true) ?: []) : [];
$action = $isEdit
    ? $url('/t/' . $slug . '/integrations/' . $provider['slug'] . '/' . (int)$integration['id'])
    : $url('/t/' . $slug . '/integrations/' . $provider['slug']);
?>

<a href="<?= $url('/t/' . $slug . '/integrations') ?>" class="inline-flex items-center gap-1.5 text-[12.5px] text-ink-500 hover:text-ink-900 mb-3"><i class="lucide lucide-arrow-left"></i> Volver al marketplace</a>

<div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-5">
    <div class="flex items-start gap-4">
        <div class="w-16 h-16 rounded-2xl grid place-items-center flex-shrink-0" style="background:<?= $e($provider['color']) ?>15;color:<?= $e($provider['color']) ?>;border:1px solid <?= $e($provider['color']) ?>40">
            <i class="lucide lucide-<?= $e($provider['icon']) ?> text-[26px]"></i>
        </div>
        <div>
            <div class="text-[10.5px] font-bold uppercase tracking-[0.14em] text-ink-400 mb-1"><?= $isEdit?'Editar integración':'Conectar' ?></div>
            <h1 class="font-display font-extrabold text-[26px] tracking-[-0.025em]"><?= $e($provider['name']) ?></h1>
            <p class="text-[13px] text-ink-500 mt-1 max-w-2xl"><?= $e($provider['description']) ?></p>
            <?php if (!empty($provider['docs_url'])): ?>
                <a href="<?= $e($provider['docs_url']) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-[12px] mt-2 font-semibold" style="color:<?= $e($provider['color']) ?>"><i class="lucide lucide-external-link text-[12px]"></i> Documentación oficial</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($isEdit): ?>
        <div class="flex items-center gap-2 flex-shrink-0">
            <form method="POST" action="<?= $url('/t/' . $slug . '/integrations/' . $provider['slug'] . '/' . (int)$integration['id'] . '/test') ?>">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <button class="btn btn-outline btn-sm"><i class="lucide lucide-zap text-[13px]"></i> Probar</button>
            </form>
            <form method="POST" action="<?= $url('/t/' . $slug . '/integrations/' . $provider['slug'] . '/' . (int)$integration['id'] . '/toggle') ?>">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <button class="btn btn-outline btn-sm"><i class="lucide lucide-<?= (int)$integration['is_active']?'pause':'play' ?> text-[13px]"></i> <?= (int)$integration['is_active']?'Pausar':'Activar' ?></button>
            </form>
            <form method="POST" action="<?= $url('/t/' . $slug . '/integrations/' . $provider['slug'] . '/' . (int)$integration['id'] . '/delete') ?>" onsubmit="return confirm('¿Eliminar esta integración? No se podrá recuperar.')">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <button class="btn btn-outline btn-sm" style="color:#dc2626;border-color:#fecaca"><i class="lucide lucide-trash-2 text-[13px]"></i> Eliminar</button>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php if ($isEdit): ?>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        <div class="card card-pad" style="border-radius:14px">
            <div class="text-[10.5px] font-bold uppercase tracking-[0.14em] text-ink-400">Estado</div>
            <div class="font-display font-extrabold text-[18px] mt-1" style="color:<?= (int)$integration['is_active']?'#16a34a':'#6b7280' ?>"><?= (int)$integration['is_active']?'Activa':'Pausada' ?></div>
        </div>
        <div class="card card-pad" style="border-radius:14px">
            <div class="text-[10.5px] font-bold uppercase tracking-[0.14em] text-ink-400">Envíos OK</div>
            <div class="font-display font-extrabold text-[18px] mt-1" style="color:#16a34a"><?= number_format((int)$integration['success_count']) ?></div>
        </div>
        <div class="card card-pad" style="border-radius:14px">
            <div class="text-[10.5px] font-bold uppercase tracking-[0.14em] text-ink-400">Errores</div>
            <div class="font-display font-extrabold text-[18px] mt-1" style="color:<?= (int)$integration['error_count']>0?'#dc2626':'#16151b' ?>"><?= number_format((int)$integration['error_count']) ?></div>
        </div>
        <div class="card card-pad" style="border-radius:14px">
            <div class="text-[10.5px] font-bold uppercase tracking-[0.14em] text-ink-400">Último evento</div>
            <div class="font-display font-extrabold text-[14px] mt-1.5"><?= $integration['last_event_at']?date('d M Y · H:i', strtotime((string)$integration['last_event_at'])):'—' ?></div>
        </div>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <form method="POST" action="<?= $action ?>" class="card lg:col-span-2 space-y-4" style="padding:22px;border-radius:18px">
        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

        <div>
            <label class="label">Nombre interno</label>
            <input name="name" required class="input" value="<?= $isEdit ? $e($integration['name']) : $e($provider['name']) ?>" placeholder="Ej: <?= $e($provider['name']) ?> · #soporte">
            <p class="text-[11px] text-ink-400 mt-1">Para identificar esta integración en logs y listados</p>
        </div>

        <?php if ($provider['slug'] === 'telegram'): ?>
            <div class="rounded-2xl p-4" style="background:linear-gradient(135deg,#eaf4fc,#fff);border:1px solid #b6dcf5">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl grid place-items-center flex-shrink-0" style="background:#0088CC;color:#fff"><i class="lucide lucide-send text-[18px]"></i></div>
                    <div class="flex-1 min-w-0">
                        <div class="text-[12.5px] font-display font-bold text-ink-900 mb-1">¿A quién se le envía?</div>
                        <p class="text-[11.5px] text-ink-500 mb-2 leading-relaxed">El campo <strong>Chat ID</strong> acepta los tres formatos:</p>
                        <ul class="text-[11.5px] text-ink-700 space-y-1 mb-3 leading-relaxed">
                            <li class="flex gap-2"><span class="badge" style="background:#dbeafe;color:#1d4ed8;flex-shrink:0">Privado</span> <span>Pegá el <strong>ID numérico del usuario</strong> (ej: <code class="font-mono">7931911586</code>). El usuario debe enviar <code class="font-mono">/start</code> al bot al menos una vez.</span></li>
                            <li class="flex gap-2"><span class="badge" style="background:#fef3c7;color:#b45309;flex-shrink:0">Grupo</span> <span>ID numérico negativo (ej: <code class="font-mono">-1001234567890</code>). Agregá el bot como miembro.</span></li>
                            <li class="flex gap-2"><span class="badge" style="background:#dcfce7;color:#15803d;flex-shrink:0">Canal</span> <span><code class="font-mono">@usuario_del_canal</code>. Agregá el bot como administrador.</span></li>
                        </ul>

                        <?php if (!empty($telegram) && !empty($telegram['bot'])): $bot = $telegram['bot']; ?>
                            <div class="mt-3 p-3 rounded-xl bg-white border border-[#b6dcf5]">
                                <div class="flex items-center justify-between gap-3 flex-wrap">
                                    <div class="text-[11.5px] text-ink-700">
                                        Bot conectado: <strong>@<?= $e($bot['username'] ?? '?') ?></strong>
                                        <?php if (!empty($bot['first_name'])): ?> · <?= $e($bot['first_name']) ?><?php endif; ?>
                                    </div>
                                    <a href="https://t.me/<?= $e($bot['username'] ?? '') ?>?start=hello" target="_blank" rel="noopener" class="btn btn-outline btn-xs" style="border-color:#0088CC;color:#0088CC">
                                        <i class="lucide lucide-external-link text-[12px]"></i> Abrir chat con el bot
                                    </a>
                                </div>
                                <p class="text-[10.5px] text-ink-400 mt-1.5">Cualquier persona que vaya a recibir notificaciones por privado debe abrir este link y enviar <code class="font-mono">/start</code> antes de poner su user ID acá.</p>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($telegram) && !empty($telegram['chat'])): $ch = $telegram['chat'];
                            $type = (string)($ch['type'] ?? '?');
                            $label = $type === 'private' ? 'Privado' : ($type === 'group' || $type === 'supergroup' ? 'Grupo' : ($type === 'channel' ? 'Canal' : ucfirst($type)));
                            $name = trim(($ch['title'] ?? '') . ' ' . ($ch['first_name'] ?? '') . ' ' . ($ch['last_name'] ?? '') . (isset($ch['username']) ? ' @' . $ch['username'] : ''));
                        ?>
                            <div class="mt-2 p-3 rounded-xl flex items-center gap-2" style="background:#dcfce7;border:1px solid #86efac">
                                <i class="lucide lucide-check-circle text-[15px]" style="color:#15803d"></i>
                                <div class="text-[11.5px] text-ink-900">
                                    <strong>Chat verificado</strong> · tipo: <?= $e($label) ?> · <?= $e($name ?: '#' . ($ch['id'] ?? '?')) ?>
                                </div>
                            </div>
                        <?php elseif (!empty($telegram) && !empty($telegram['chat_error'])): ?>
                            <div class="mt-2 p-3 rounded-xl flex items-start gap-2" style="background:#fef2f2;border:1px solid #fecaca">
                                <i class="lucide lucide-alert-circle text-[15px] mt-0.5" style="color:#dc2626"></i>
                                <div class="text-[11.5px] text-ink-900">
                                    <strong>Chat ID inválido o inaccesible:</strong> <?= $e($telegram['chat_error']) ?>
                                    <?php if (!empty($telegram['bot']['username'])): ?>
                                        <div class="mt-1 text-[11px] text-ink-700">
                                            Si es un usuario privado, abrí
                                            <a href="https://t.me/<?= $e($telegram['bot']['username']) ?>?start=hello" target="_blank" rel="noopener" class="underline font-semibold" style="color:#0088CC">@<?= $e($telegram['bot']['username']) ?></a>
                                            con esa cuenta y mandá <code class="font-mono">/start</code>, luego volvé a probar.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="space-y-3 pt-3" style="border-top:1px solid #ececef">
            <div class="text-[10.5px] font-bold uppercase tracking-[0.14em] text-ink-400">Configuración del proveedor</div>
            <?php foreach ($provider['config'] as $field):
                $key = $field['key'];
                $type = $field['type'] ?? 'text';
                $val = $config[$key] ?? ($field['default'] ?? '');
                $required = !empty($field['required']);
            ?>
                <div>
                    <label class="label flex items-center gap-1.5">
                        <?= $e($field['label']) ?>
                        <?php if ($required): ?><span class="text-rose-600">*</span><?php endif; ?>
                    </label>
                    <?php if ($type === 'select'): ?>
                        <select name="<?= $e($key) ?>" class="input" <?= $required?'required':'' ?>>
                            <?php foreach (($field['options'] ?? []) as $optV => $optL): ?>
                                <option value="<?= $e($optV) ?>" <?= ((string)$val===(string)$optV)?'selected':'' ?>><?= $e($optL) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php elseif ($type === 'password'): ?>
                        <input type="password" name="<?= $e($key) ?>" class="input font-mono" <?= ($required && !$isEdit)?'required':'' ?> placeholder="<?= $e($field['placeholder'] ?? '') ?>" value="" autocomplete="off">
                        <?php if ($isEdit && !empty($val)): ?>
                            <p class="text-[11px] text-ink-400 mt-1"><i class="lucide lucide-shield-check text-[11px] text-emerald-600"></i> Valor guardado · deja vacío para mantenerlo</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <input type="<?= $e($type) ?>" name="<?= $e($key) ?>" class="input <?= in_array($type,['url','email'])?'font-mono':'' ?>" <?= $required?'required':'' ?> placeholder="<?= $e($field['placeholder'] ?? '') ?>" value="<?= $e((string)$val) ?>">
                    <?php endif; ?>
                    <?php if (!empty($field['help'])): ?>
                        <p class="text-[11px] text-ink-400 mt-1"><?= $e($field['help']) ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="space-y-3 pt-3" style="border-top:1px solid #ececef">
            <div>
                <div class="text-[10.5px] font-bold uppercase tracking-[0.14em] text-ink-400">Eventos a notificar</div>
                <p class="text-[11.5px] text-ink-400 mt-0.5">Selecciona los eventos que disparan esta integración. Si no marcas ninguno, se enviarán <strong>todos</strong>.</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <?php foreach ($availableEvents as $evKey => $evLabel): ?>
                    <label class="flex items-center gap-2 p-2.5 rounded-xl border border-[#ececef] hover:border-brand-200 hover:bg-brand-50/30 transition cursor-pointer">
                        <input type="checkbox" name="events[]" value="<?= $e($evKey) ?>" <?= in_array($evKey, $selectedEvents, true)?'checked':'' ?> class="rounded border-[#d7d7df]">
                        <div class="flex-1 min-w-0">
                            <div class="text-[12.5px] font-semibold text-ink-700"><?= $e($evLabel) ?></div>
                            <div class="text-[10.5px] text-ink-400 font-mono"><?= $e($evKey) ?></div>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 pt-3" style="border-top:1px solid #ececef">
            <label class="flex items-center gap-2 text-[13px] cursor-pointer">
                <input type="checkbox" name="is_active" value="1" <?= !$isEdit || (int)$integration['is_active']?'checked':'' ?> class="rounded border-[#d7d7df]">
                Activar esta integración
            </label>
            <div class="flex gap-2">
                <a href="<?= $url('/t/' . $slug . '/integrations') ?>" class="btn btn-outline btn-sm">Cancelar</a>
                <button class="btn btn-primary btn-sm"><i class="lucide lucide-<?= $isEdit?'save':'plug' ?> text-[13px]"></i> <?= $isEdit?'Guardar cambios':'Conectar' ?></button>
            </div>
        </div>
    </form>

    <aside class="space-y-3">
        <?php if ($isEdit && !empty($logs)): ?>
            <div class="card" style="border-radius:18px;overflow:hidden">
                <div class="card-pad" style="border-bottom:1px solid #ececef">
                    <h3 class="font-display font-bold text-[14px] flex items-center gap-2"><i class="lucide lucide-history text-ink-400"></i> Logs recientes</h3>
                    <p class="text-[11.5px] text-ink-400 mt-0.5">Últimos <?= count($logs) ?> envíos</p>
                </div>
                <div style="max-height:520px;overflow:auto">
                    <?php foreach ($logs as $log):
                        $ok = $log['status'] === 'success';
                    ?>
                        <div class="px-4 py-3 border-b border-[#f3f4f6] last:border-b-0">
                            <div class="flex items-start gap-2">
                                <div class="w-2 h-2 rounded-full mt-1.5 flex-shrink-0" style="background:<?= $ok?'#16a34a':'#dc2626' ?>"></div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <code class="text-[11px] font-mono font-semibold"><?= $e($log['event_type']) ?></code>
                                        <span class="text-[10px] font-mono px-1.5 py-0.5 rounded" style="background:<?= $ok?'#f0fdf4':'#fef2f2' ?>;color:<?= $ok?'#16a34a':'#dc2626' ?>">HTTP <?= (int)$log['status_code'] ?: 'ERR' ?></span>
                                        <span class="text-[10px] text-ink-400"><?= (int)$log['latency_ms'] ?>ms</span>
                                    </div>
                                    <div class="text-[10.5px] text-ink-400 mt-0.5"><?= $e(date('d M H:i:s', strtotime((string)$log['created_at']))) ?></div>
                                    <?php if (!empty($log['error_message'])): ?>
                                        <div class="text-[11px] mt-1 font-mono px-2 py-1 rounded" style="background:#fef2f2;color:#dc2626;word-break:break-all"><?= $e($log['error_message']) ?></div>
                                    <?php elseif (!empty($log['response_excerpt']) && strlen($log['response_excerpt']) > 0): ?>
                                        <div class="text-[10.5px] mt-1 font-mono text-ink-500 truncate" title="<?= $e($log['response_excerpt']) ?>"><?= $e(substr($log['response_excerpt'], 0, 80)) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="card card-pad" style="border-radius:18px;background:#f9fafb">
                <div class="flex items-start gap-2">
                    <i class="lucide lucide-info text-brand-700 text-[15px] mt-0.5"></i>
                    <div class="text-[12.5px] text-ink-700 leading-relaxed">
                        <strong>¿Cómo funciona?</strong>
                        <ul class="mt-2 space-y-1.5 text-[12px] text-ink-500">
                            <li>1. Configura los datos del proveedor</li>
                            <li>2. Selecciona los eventos a notificar</li>
                            <li>3. Activa y prueba la conexión</li>
                            <li>4. Cada vez que ocurra un evento, se enviará automáticamente</li>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </aside>
</div>
