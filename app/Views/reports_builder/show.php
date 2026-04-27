<?php $slug = $tenant->slug; ?>

<div class="mb-4">
    <a href="<?= $url('/t/' . $slug . '/reports-builder') ?>" class="inline-flex items-center gap-1.5 text-[12.5px] text-ink-500 hover:text-ink-900"><i class="lucide lucide-arrow-left text-[13px]"></i> Volver</a>
</div>

<form method="POST" action="<?= $url('/t/' . $slug . '/reports-builder/' . (int)$report['id']) ?>"
      x-data='reportBuilder(<?= htmlspecialchars(json_encode($layout), ENT_QUOTES) ?>)'>
    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
    <input type="hidden" name="layout" :value="JSON.stringify(layout)">

    <div class="card card-pad mb-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
            <div class="md:col-span-2">
                <label class="label">Nombre</label>
                <input name="name" value="<?= $e($report['name']) ?>" class="input" style="font-weight:700;font-size:18px">
            </div>
            <div class="flex items-end gap-2">
                <label class="flex items-center gap-1.5 text-[13px]"><input type="checkbox" name="is_favorite" value="1" <?= $report['is_favorite']?'checked':'' ?>> Favorito</label>
                <label class="flex items-center gap-1.5 text-[13px]"><input type="checkbox" name="is_shared" value="1" <?= $report['is_shared']?'checked':'' ?>> Compartido</label>
            </div>
            <div class="md:col-span-3"><label class="label">Descripción</label><input name="description" value="<?= $e($report['description']) ?>" class="input"></div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div><label class="label">Desde</label><input name="filter_from" type="date" value="<?= $e($filters['from']) ?>" class="input"></div>
            <div><label class="label">Hasta</label><input name="filter_to" type="date" value="<?= $e($filters['to']) ?>" class="input"></div>
            <div class="md:col-span-2"><label class="label">Emails para envío programado (separados por coma)</label><input name="schedule_emails" value="<?= $e($report['schedule_emails']) ?>" class="input" placeholder="ana@x.com, juan@y.com"></div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
        <!-- Available widgets -->
        <div class="card card-pad">
            <div class="text-[11px] uppercase font-bold tracking-[0.14em] text-ink-400 mb-3">Widgets disponibles</div>
            <div class="space-y-1.5">
                <?php foreach ($widgets as $key => $w): ?>
                    <button type="button" @click="addWidget('<?= $key ?>')" class="w-full flex items-center gap-2.5 p-2.5 rounded-lg hover:bg-brand-50 transition text-left">
                        <div class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 grid place-items-center"><i class="lucide lucide-<?= $w['icon'] ?> text-[14px]"></i></div>
                        <div class="flex-1 min-w-0">
                            <div class="font-display font-bold text-[12.5px]"><?= $e($w['title']) ?></div>
                            <div class="text-[10.5px] text-ink-400 uppercase tracking-wider"><?= $w['type'] ?></div>
                        </div>
                        <i class="lucide lucide-plus text-brand-600 text-[14px]"></i>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Canvas -->
        <div class="lg:col-span-3 space-y-3 min-h-[400px]" id="canvas">
            <template x-if="layout.length === 0">
                <div class="card card-pad text-center py-16 border-2 border-dashed" style="border-color:#e5e7eb">
                    <i class="lucide lucide-layout-dashboard text-[32px] text-ink-300"></i>
                    <h3 class="font-display font-bold mt-3">Canvas vacío</h3>
                    <p class="text-[12.5px] text-ink-400 mt-1">Agregá widgets desde la izquierda para empezar a construir tu reporte.</p>
                </div>
            </template>
            <template x-for="(w, i) in layout" :key="w.id">
                <div class="card card-pad">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <i class="lucide lucide-grip-vertical text-ink-300"></i>
                            <span class="font-display font-bold text-[14px]" x-text="widgetTitle(w.key)"></span>
                        </div>
                        <div class="flex gap-1">
                            <button type="button" @click="moveUp(i)" :disabled="i===0" class="btn btn-soft btn-xs"><i class="lucide lucide-arrow-up text-[11px]"></i></button>
                            <button type="button" @click="moveDown(i)" :disabled="i===layout.length-1" class="btn btn-soft btn-xs"><i class="lucide lucide-arrow-down text-[11px]"></i></button>
                            <button type="button" @click="removeWidget(i)" class="btn btn-soft btn-xs" style="color:#b91c1c"><i class="lucide lucide-trash-2 text-[11px]"></i></button>
                        </div>
                    </div>
                    <div x-html="widgetData(w.key)"></div>
                </div>
            </template>
        </div>
    </div>

    <div class="flex items-center justify-between sticky bottom-3 mt-4 card card-pad" style="z-index:10;box-shadow:0 -8px 20px -8px rgba(22,21,27,.08)">
        <form method="POST" action="<?= $url('/t/' . $slug . '/reports-builder/' . (int)$report['id'] . '/delete') ?>" onsubmit="return confirm('Eliminar reporte?')">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
            <button type="submit" class="btn btn-outline btn-sm" style="color:#b91c1c"><i class="lucide lucide-trash-2"></i> Eliminar</button>
        </form>
        <button type="submit" class="btn btn-primary"><i class="lucide lucide-save"></i> Guardar reporte</button>
    </div>
</form>

<script>
const WIDGET_TITLES = <?= json_encode(array_map(fn($w) => $w['title'], $widgets)) ?>;
const WIDGET_DATA = <?= json_encode($widgetData) ?>;

function reportBuilder(initialLayout) {
    return {
        layout: initialLayout || [],
        addWidget(key) {
            this.layout.push({ id: Date.now() + Math.random(), key });
        },
        removeWidget(i) { this.layout.splice(i, 1); },
        moveUp(i) { if (i > 0) [this.layout[i-1], this.layout[i]] = [this.layout[i], this.layout[i-1]]; },
        moveDown(i) { if (i < this.layout.length - 1) [this.layout[i+1], this.layout[i]] = [this.layout[i], this.layout[i+1]]; },
        widgetTitle(key) { return WIDGET_TITLES[key] || key; },
        widgetData(key) {
            const d = WIDGET_DATA[key];
            if (!d) return '<div class="text-[12px] text-ink-400 text-center py-8">Guardá el reporte para ver los datos.</div>';
            if (d.type === 'kpi') {
                return `<div class="text-center py-4"><div class="font-display font-extrabold text-[42px] text-brand-700">${d.value}</div><div class="text-[12px] text-ink-500 mt-1">${d.sub || ''}</div></div>`;
            }
            if (d.type === 'table' || d.type === 'bar' || d.type === 'donut' || d.type === 'line') {
                if (!d.rows || !d.rows.length) return '<div class="text-[12px] text-ink-400 text-center py-6">Sin datos en el rango seleccionado.</div>';
                const max = Math.max(...d.rows.map(r => parseFloat(r.value)));
                let html = '<div class="space-y-1.5">';
                d.rows.forEach(r => {
                    const pct = max > 0 ? (parseFloat(r.value) / max) * 100 : 0;
                    html += `<div class="flex items-center gap-3 text-[12.5px]"><div class="w-32 truncate font-medium">${escapeHtml(r.label)}</div><div class="flex-1 rounded-full h-2 overflow-hidden" style="background:#f3f4f6"><div class="h-full" style="width:${pct}%;background:#7c5cff"></div></div><div class="w-12 text-right font-mono font-bold">${r.value}</div></div>`;
                });
                html += '</div>';
                return html;
            }
            return '<pre class="text-[10px]">' + escapeHtml(JSON.stringify(d, null, 2)) + '</pre>';
        },
    };
}
function escapeHtml(s) { return String(s||'').replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])); }
</script>
