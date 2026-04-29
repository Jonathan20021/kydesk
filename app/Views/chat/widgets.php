<?php
$slug = $tenant->slug;
$hasAi = \App\Core\Plan::has($tenant, 'ai_assist');
?>

<div class="mb-5">
    <a href="<?= $url('/t/' . $slug . '/chat') ?>" class="inline-flex items-center gap-1.5 text-[12.5px] text-ink-500 hover:text-ink-900"><i class="lucide lucide-arrow-left text-[13px]"></i> Volver</a>
    <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em] mt-2">Configurar Widget</h1>
    <p class="text-[13px] text-ink-400">Configurá el widget de chat embebible para tu sitio web</p>
</div>

<?php foreach ($widgets as $w):
    $script = $appUrl . '/chat-widget/' . $w['public_key'] . '.js';
    $snippet = '<script async src="' . $script . '"></script>';
    $aiMode = (string)($w['ai_fallback_mode'] ?? 'off');
?>
    <div class="card card-pad mb-4" x-data="{open:false}">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-xl grid place-items-center" style="background:<?= $e($w['primary_color']) ?>22;color:<?= $e($w['primary_color']) ?>"><i class="lucide lucide-message-square text-[16px]"></i></div>
            <div class="flex-1">
                <div class="font-display font-bold text-[15px]"><?= $e($w['name']) ?></div>
                <div class="text-[11.5px] text-ink-400 font-mono">key: <?= $e($w['public_key']) ?></div>
            </div>
            <?php if ($hasAi && $aiMode !== 'off'): ?>
                <span class="badge" style="background:#ede9fe;color:#6d28d9"><i class="lucide lucide-sparkles text-[12px]"></i> IA <?= $aiMode === 'always' ? 'siempre' : 'sin agente' ?></span>
            <?php endif; ?>
            <span class="badge <?= $w['is_active']?'badge-green':'badge-gray' ?>"><?= $w['is_active']?'Activo':'Pausado' ?></span>
        </div>

        <div class="rounded-xl p-4 bg-ink-900 text-white font-mono text-[12px] mb-4 break-all">
            <div class="text-[10px] uppercase tracking-[0.14em] text-white/50 mb-2">Snippet · Pegá esto antes de &lt;/body&gt;</div>
            <code><?= htmlspecialchars($snippet) ?></code>
            <button onclick="navigator.clipboard.writeText('<?= htmlspecialchars($snippet) ?>'); this.textContent='Copiado!'" class="ml-2 text-[11px] text-emerald-300 hover:text-emerald-200">Copiar</button>
        </div>

        <form method="POST" action="<?= $url('/t/' . $slug . '/chat/widgets/' . (int)$w['id']) ?>" class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
            <div><label class="label">Nombre</label><input name="name" value="<?= $e($w['name']) ?>" class="input"></div>
            <div><label class="label">Color primario</label><input name="primary_color" type="color" value="<?= $e($w['primary_color']) ?>" class="input" style="height:42px"></div>
            <div class="md:col-span-2"><label class="label">Mensaje de bienvenida</label><input name="welcome_message" value="<?= $e($w['welcome_message']) ?>" class="input"></div>
            <div class="md:col-span-2"><label class="label">Mensaje fuera de horario</label><input name="away_message" value="<?= $e($w['away_message']) ?>" class="input"></div>
            <div class="md:col-span-2"><label class="label">Orígenes permitidos (CORS)</label><input name="allowed_origins" value="<?= $e($w['allowed_origins']) ?>" class="input" placeholder="https://misitio.com, https://otro.com (vacío = todos)"></div>
            <div class="md:col-span-2 flex items-center gap-4 text-[13px]">
                <label class="flex items-center gap-1.5"><input type="checkbox" name="require_email" value="1" <?= $w['require_email']?'checked':'' ?>> Requerir email del visitante</label>
                <label class="flex items-center gap-1.5"><input type="checkbox" name="is_active" value="1" <?= $w['is_active']?'checked':'' ?>> Activo</label>
            </div>

            <!-- ──────── Asistente IA (Enterprise) ──────── -->
            <div class="md:col-span-2 mt-3 pt-4" style="border-top:1px solid var(--border)">
                <div class="flex items-center gap-2 mb-1">
                    <i class="lucide lucide-sparkles text-[15px]" style="color:#7c3aed"></i>
                    <h3 class="font-display font-bold text-[14px]">Asistente IA (Claude)</h3>
                    <?php if (!$hasAi): ?>
                        <span class="badge badge-gray">Solo Enterprise</span>
                    <?php endif; ?>
                </div>
                <p class="text-[12px] text-ink-400 mb-3">Cuando no hay agentes humanos atendiendo, Claude responde usando tu base de conocimiento. Crea tickets automáticamente cuando hace falta seguimiento humano. Consume la cuota IA de tu workspace.</p>

                <?php if ($hasAi): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="label">Modo de respuesta</label>
                        <select name="ai_fallback_mode" class="input">
                            <option value="off"      <?= $aiMode==='off'?'selected':'' ?>>Desactivado · solo agentes humanos</option>
                            <option value="no_agent" <?= $aiMode==='no_agent'?'selected':'' ?>>Solo cuando no hay agente asignado (recomendado)</option>
                            <option value="always"   <?= $aiMode==='always'?'selected':'' ?>>Siempre responder primero (después escalable)</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Tope de turnos antes de escalar</label>
                        <input type="number" name="ai_max_turns" min="1" max="20" value="<?= (int)($w['ai_max_turns'] ?? 6) ?>" class="input">
                    </div>
                    <div>
                        <label class="label">Nombre del asistente</label>
                        <input name="ai_persona_name" value="<?= $e($w['ai_persona_name'] ?? 'Asistente') ?>" class="input" placeholder="Aurora, Iris, Asistente…">
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-1.5 text-[13px]">
                            <input type="checkbox" name="ai_use_kb" value="1" <?= (int)($w['ai_use_kb'] ?? 1) === 1 ? 'checked' : '' ?>>
                            Usar la base de conocimiento (KB) como contexto
                        </label>
                    </div>
                    <div class="md:col-span-2">
                        <label class="label">Instrucciones extra (opcional)</label>
                        <textarea name="ai_system_prompt" rows="3" class="input" style="resize:vertical" placeholder="Ej: 'Recordá que solo trabajamos con clientes en LATAM. Para temas de billing, siempre escalá. Nunca prometas tiempos de respuesta menores a 24hs.'"><?= $e($w['ai_system_prompt'] ?? '') ?></textarea>
                        <p class="text-[11px] text-ink-400 mt-1">Estas instrucciones se agregan al prompt base. No incluyas datos sensibles.</p>
                    </div>
                </div>
                <?php else: ?>
                    <div class="rounded-lg p-3 text-[12.5px]" style="background:#fafafb;color:#6b7280">
                        Disponible en plan Enterprise. Contactá al equipo Kydesk para activarlo.
                    </div>
                <?php endif; ?>
            </div>

            <div class="md:col-span-2 flex justify-end">
                <button class="btn btn-primary"><i class="lucide lucide-save"></i> Guardar</button>
            </div>
        </form>
    </div>
<?php endforeach; ?>
