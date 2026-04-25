<?php use App\Core\Helpers; $slug = $tenant->slug; ?>

<div x-data="{
    showForm:false,
    form: { id:0, title:'', body:'', color:'yellow', tags:'' },
    open(n=null) {
        this.form = n ? {id:n.id, title:n.title||'', body:n.body||'', color:n.color||'yellow', tags:n.tags||''} : {id:0, title:'', body:'', color:'yellow', tags:''};
        this.showForm = true;
    }
}" class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]">Notas</h1>
            <p class="text-[13px] text-ink-400">Tu base de conocimiento personal</p>
        </div>
        <button @click="open()" class="btn btn-primary btn-sm"><i class="lucide lucide-plus"></i> Nueva nota</button>
    </div>

    <form method="GET" class="flex gap-3 flex-wrap">
        <div class="search-pill flex-1" style="max-width:none"><i class="lucide lucide-search"></i><input name="q" value="<?= $e($q) ?>" placeholder="Buscar notas…"></div>
        <?php if (!empty($allTags)): ?>
            <div class="flex gap-1.5 overflow-x-auto">
                <a href="<?= $url('/t/' . $slug . '/notes') ?>" class="<?= $tag===''?'btn btn-primary btn-xs':'btn btn-soft btn-xs' ?>">Todas</a>
                <?php foreach ($allTags as $tg => $c): ?>
                    <a href="<?= $url('/t/' . $slug . '/notes?tag=' . urlencode($tg)) ?>" class="<?= $tag===$tg?'btn btn-primary btn-xs':'btn btn-soft btn-xs' ?>">#<?= $e($tg) ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </form>

    <?php if (empty($notes)): ?>
        <div class="card card-pad text-center py-16">
            <div class="w-14 h-14 rounded-2xl bg-[#f3f4f6] grid place-items-center mx-auto mb-3"><i class="lucide lucide-notebook-pen text-[22px] text-ink-400"></i></div>
            <div class="font-display font-bold">Sin notas</div>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <?php foreach ($notes as $n): ?>
                <div class="note-sticky note-<?= $e($n['color']) ?> group">
                    <?php if ($n['pinned']): ?><i class="lucide lucide-pin absolute top-3 right-3 text-[14px] rotate-45" style="color:rgba(0,0,0,.6)"></i><?php endif; ?>
                    <button @click='open(<?= json_encode($n, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)' class="block w-full text-left">
                        <h3 class="font-display font-bold text-[14.5px] line-clamp-2 pr-6"><?= $e($n['title']) ?></h3>
                        <div class="text-[12.5px] mt-2 whitespace-pre-wrap line-clamp-6 leading-relaxed text-ink-700"><?= $e($n['body']) ?></div>
                    </button>
                    <?php if ($n['tags']): ?>
                        <div class="mt-3 flex flex-wrap gap-1">
                            <?php foreach (array_filter(array_map('trim', explode(',', $n['tags']))) as $tg): ?>
                                <span class="text-[10px] font-medium px-2 py-0.5 rounded-full" style="background:rgba(0,0,0,.08);color:rgba(0,0,0,.7)">#<?= $e($tg) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="mt-3 pt-2.5 flex items-center justify-between" style="border-top:1px solid rgba(0,0,0,.08)">
                        <span class="text-[10.5px]" style="color:rgba(0,0,0,.5)"><?= Helpers::ago($n['updated_at']) ?></span>
                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition">
                            <form method="POST" action="<?= $url('/t/' . $slug . '/notes/' . $n['id']) ?>"><input type="hidden" name="_csrf" value="<?= $e($csrf) ?>"><input type="hidden" name="toggle_pin" value="1"><button class="w-7 h-7 grid place-items-center rounded-md hover:bg-black/10"><i class="lucide lucide-pin text-[12px]"></i></button></form>
                            <form method="POST" action="<?= $url('/t/' . $slug . '/notes/' . $n['id'] . '/delete') ?>" onsubmit="return confirm('¿Eliminar?')"><input type="hidden" name="_csrf" value="<?= $e($csrf) ?>"><button class="w-7 h-7 grid place-items-center rounded-md hover:bg-black/10"><i class="lucide lucide-trash-2 text-[12px]"></i></button></form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div x-show="showForm" x-cloak class="fixed inset-0 z-50 grid place-items-center p-4 bg-ink-900/40" @click.self="showForm=false" @keydown.escape.window="showForm=false">
        <form method="POST" :action="'<?= $url('/t/' . $slug . '/notes') ?>' + (form.id ? ('/' + form.id) : '')" class="card w-full max-w-lg p-6">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
            <h3 class="font-display font-bold text-[18px]" x-text="form.id ? 'Editar nota' : 'Nueva nota'"></h3>
            <input name="title" x-model="form.title" required placeholder="Título" class="input mt-4 font-medium">
            <textarea name="body" x-model="form.body" rows="6" placeholder="Escribe aquí…" class="input mt-2"></textarea>
            <input name="tags" x-model="form.tags" placeholder="Etiquetas (coma)" class="input mt-2">
            <div class="mt-4 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <?php foreach (['yellow','blue','pink','green','purple','orange','gray','red'] as $cl): ?>
                        <label class="cursor-pointer"><input type="radio" name="color" value="<?= $cl ?>" class="sr-only peer" :checked="form.color==='<?= $cl ?>'" @change="form.color='<?= $cl ?>'">
                        <span class="block w-7 h-7 rounded-full border-2 peer-checked:border-ink-900 border-transparent note-<?= $cl ?>"></span></label>
                    <?php endforeach; ?>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="showForm=false" class="btn btn-outline btn-sm">Cancelar</button>
                    <button class="btn btn-primary btn-sm">Guardar</button>
                </div>
            </div>
        </form>
    </div>
</div>
