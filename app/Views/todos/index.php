<?php use App\Core\Helpers; $slug = $tenant->slug;
$priColor = ['urgent'=>'#ef4444','high'=>'#f59e0b','medium'=>'#7c5cff','low'=>'#9ca3af']; ?>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-4" x-data="{ showListForm:false }">
    <aside class="lg:col-span-1 space-y-3">
        <div class="card" style="padding:14px">
            <a href="<?= $url('/t/' . $slug . '/todos') ?>" class="nav-item <?= $currentListId===0 && !$showCompleted?'active':'' ?>"><i class="lucide lucide-inbox"></i> Todas <span class="kbd ml-auto"><?= $counts['pending'] ?></span></a>
            <a href="<?= $url('/t/' . $slug . '/todos?completed=1') ?>" class="nav-item <?= $showCompleted?'active':'' ?>"><i class="lucide lucide-check-circle-2"></i> Completadas <span class="kbd ml-auto"><?= $counts['done'] ?></span></a>
        </div>

        <div class="card" style="padding:14px">
            <div class="flex items-center justify-between mb-2 px-2">
                <span class="nav-heading" style="margin-bottom:0">Mis listas</span>
                <button @click="showListForm=!showListForm" class="w-6 h-6 rounded-md hover:bg-[#f3f4f6] grid place-items-center text-ink-400"><i class="lucide lucide-plus text-[13px]"></i></button>
            </div>
            <form x-show="showListForm" x-cloak method="POST" action="<?= $url('/t/' . $slug . '/todos/lists') ?>" class="mb-2 p-2.5 rounded-2xl space-y-2 bg-[#f3f4f6]">
                <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                <input name="name" required placeholder="Nombre" class="input" style="height:34px;border-radius:10px">
                <div class="flex gap-1.5">
                    <?php foreach (['#7c5cff','#3b82f6','#16a34a','#f59e0b','#ef4444'] as $c): ?>
                        <label><input type="radio" name="color" value="<?= $c ?>" class="sr-only peer" <?= $c==='#7c5cff'?'checked':'' ?>><span class="block w-5 h-5 rounded-full border-2 peer-checked:border-ink-900 border-transparent cursor-pointer" style="background:<?= $c ?>"></span></label>
                    <?php endforeach; ?>
                </div>
                <button class="btn btn-primary btn-xs w-full">Crear</button>
            </form>
            <div class="space-y-0.5">
                <?php foreach ($lists as $l): ?>
                    <div class="group flex items-center gap-1">
                        <a href="<?= $url('/t/' . $slug . '/todos?list=' . $l['id']) ?>" class="nav-item flex-1 <?= $currentListId===(int)$l['id']?'active':'' ?>">
                            <span class="dot" style="background:<?= $e($l['color']) ?>"></span><span class="truncate"><?= $e($l['name']) ?></span>
                        </a>
                        <form method="POST" action="<?= $url('/t/' . $slug . '/todos/lists/' . $l['id'] . '/delete') ?>" onsubmit="return confirm('¿Eliminar?')" class="opacity-0 group-hover:opacity-100">
                            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                            <button class="w-6 h-6 grid place-items-center rounded-md text-ink-400"><i class="lucide lucide-x text-[12px]"></i></button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card card-pad text-white" style="background:linear-gradient(135deg,#7c5cff,#a78bfa)">
            <div class="text-[10.5px] font-bold uppercase tracking-[0.12em] opacity-80">Hoy</div>
            <div class="font-display font-extrabold text-[36px] mt-1"><?= $counts['today'] ?></div>
            <div class="text-[12px] opacity-80">tareas vencen hoy</div>
        </div>
    </aside>

    <div class="lg:col-span-3 space-y-3">
        <div>
            <h1 class="font-display font-extrabold text-[28px] tracking-[-0.025em]"><?= $showCompleted?'Completadas':'Tareas' ?></h1>
            <p class="text-[13px] text-ink-400">Estilo Todoist, sin fricción</p>
        </div>

        <form method="POST" action="<?= $url('/t/' . $slug . '/todos') ?>" class="card" style="padding:10px 14px">
            <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
            <input type="hidden" name="list_id" value="<?= $currentListId ?>">
            <div class="flex items-center gap-2">
                <div class="w-5 h-5 rounded-full border-2 border-ink-300 shrink-0"></div>
                <input name="title" required placeholder="Añadir tarea y Enter…" class="flex-1 text-[14px] outline-none border-0 bg-transparent">
                <select name="priority" class="text-[12px] px-2 py-1 rounded-md bg-[#f3f4f6] border-0">
                    <option value="low">Baja</option><option value="medium" selected>Media</option><option value="high">Alta</option><option value="urgent">Urgente</option>
                </select>
                <input name="due_at" type="datetime-local" class="text-[12px] px-2 py-1 rounded-md bg-[#f3f4f6] border-0">
                <button class="btn btn-primary btn-icon-sm"><i class="lucide lucide-plus text-[14px]"></i></button>
            </div>
        </form>

        <div class="card overflow-hidden">
            <?php if (empty($todos)): ?>
                <div class="p-12 text-center">
                    <div class="w-12 h-12 rounded-2xl bg-[#f3f4f6] grid place-items-center mx-auto mb-3"><i class="lucide lucide-check-square text-[20px] text-ink-400"></i></div>
                    <div class="font-display font-bold"><?= $showCompleted?'Sin completadas':'Todo al día' ?></div>
                </div>
            <?php endif; ?>
            <?php foreach ($todos as $td): $color = $priColor[$td['priority']] ?? '#7c5cff'; ?>
                <div class="group flex items-start gap-3 px-5 py-3.5 hover:bg-[#f3f4f6] transition border-b border-[#ececef]">
                    <form method="POST" action="<?= $url('/t/' . $slug . '/todos/' . $td['id'] . '/toggle') ?>" class="pt-0.5">
                        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                        <button class="w-5 h-5 rounded-full border-2 grid place-items-center transition hover:scale-110" style="border-color:<?= $color ?>;<?= $td['completed']?"background:$color":'' ?>">
                            <?php if ($td['completed']): ?><i class="lucide lucide-check text-white text-[11px]"></i><?php endif; ?>
                        </button>
                    </form>
                    <div class="flex-1 min-w-0">
                        <div class="text-[13.5px] font-medium <?= $td['completed']?'line-through text-ink-400':'' ?>"><?= $e($td['title']) ?></div>
                        <?php if ($td['description']): ?><div class="text-[12px] mt-0.5 text-ink-400"><?= $e($td['description']) ?></div><?php endif; ?>
                        <div class="mt-1.5 flex items-center gap-2 text-[11px]">
                            <?= Helpers::priorityBadge($td['priority']) ?>
                            <?php if ($td['due_at']): ?><span class="inline-flex items-center gap-1 text-ink-400"><i class="lucide lucide-calendar text-[11px]"></i> <?= date('d/m H:i', strtotime($td['due_at'])) ?></span><?php endif; ?>
                            <span class="text-ink-400"><?= Helpers::ago($td['created_at']) ?></span>
                        </div>
                    </div>
                    <form method="POST" action="<?= $url('/t/' . $slug . '/todos/' . $td['id'] . '/delete') ?>" onsubmit="return confirm('¿Eliminar?')" class="opacity-0 group-hover:opacity-100">
                        <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                        <button class="w-7 h-7 grid place-items-center rounded-md text-ink-400"><i class="lucide lucide-trash-2 text-[13px]"></i></button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
