<?php $slug = $tenant->slug; ?>

<div class="mb-5">
    <a href="<?= $url('/t/' . $slug . '/chat') ?>" class="inline-flex items-center gap-1.5 text-[12.5px] text-ink-500 hover:text-ink-900"><i class="lucide lucide-arrow-left text-[13px]"></i> Volver</a>
    <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em] mt-2">Configurar Widget</h1>
    <p class="text-[13px] text-ink-400">Configurá el widget de chat embebible para tu sitio web</p>
</div>

<?php foreach ($widgets as $w):
    $script = $appUrl . '/chat-widget/' . $w['public_key'] . '.js';
    $snippet = '<script async src="' . $script . '"></script>';
?>
    <div class="card card-pad mb-4" x-data="{open:false}">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-xl grid place-items-center" style="background:<?= $e($w['primary_color']) ?>22;color:<?= $e($w['primary_color']) ?>"><i class="lucide lucide-message-square text-[16px]"></i></div>
            <div class="flex-1">
                <div class="font-display font-bold text-[15px]"><?= $e($w['name']) ?></div>
                <div class="text-[11.5px] text-ink-400 font-mono">key: <?= $e($w['public_key']) ?></div>
            </div>
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
            <div class="md:col-span-2 flex justify-end">
                <button class="btn btn-primary"><i class="lucide lucide-save"></i> Guardar</button>
            </div>
        </form>
    </div>
<?php endforeach; ?>
