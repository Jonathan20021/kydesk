<?php $slug = $tenant->slug; ?>

<div class="mb-4">
    <a href="<?= $url('/t/' . $slug . '/chat') ?>" class="inline-flex items-center gap-1.5 text-[12.5px] text-ink-500 hover:text-ink-900"><i class="lucide lucide-arrow-left text-[13px]"></i> Volver</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4" x-data='chatShow(<?= (int)$convo["id"] ?>, <?= count($messages) > 0 ? (int)end($messages)["id"] : 0 ?>)' x-init="poll()">
    <!-- Conversation -->
    <div class="lg:col-span-2 card overflow-hidden flex flex-col" style="height:600px">
        <div class="p-4 flex items-center gap-3" style="border-bottom:1px solid var(--border)">
            <div class="w-10 h-10 rounded-xl text-white grid place-items-center font-display font-bold text-[14px]" style="background:linear-gradient(135deg,#7c5cff,#a78bfa)"><?= $e(strtoupper(substr($convo['visitor_name'] ?: 'V', 0, 1))) ?></div>
            <div class="flex-1 min-w-0">
                <div class="font-display font-bold text-[14px] flex items-center gap-2">
                    <?= $e($convo['visitor_name'] ?: 'Visitante') ?>
                    <?php if ((int)($convo['ai_takeover'] ?? 0) === 1): ?>
                        <span class="inline-flex items-center gap-1 text-[10.5px] font-semibold px-2 py-0.5 rounded-full" style="background:#ede9fe;color:#6d28d9">
                            <i class="lucide lucide-sparkles text-[10px]"></i> IA atendiendo
                        </span>
                    <?php endif; ?>
                </div>
                <div class="text-[11.5px] text-ink-400">
                    <?= $e($convo['visitor_email'] ?: '—') ?>
                    <?php if (!empty($convo['page_url'])): ?>· <a href="<?= $e($convo['page_url']) ?>" target="_blank" class="hover:text-ink-700">Página</a><?php endif; ?>
                </div>
            </div>
            <?php
                $aiOn = (int)($convo['ai_takeover'] ?? 0) === 1;
                $aiAvailable = $aiAvailable ?? false;
            ?>
            <?php if ($aiAvailable && $convo['status'] !== 'closed'): ?>
                <form method="POST" action="<?= $url('/t/' . $slug . '/chat/' . (int)$convo['id'] . '/ai-toggle') ?>">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    <input type="hidden" name="enable" value="<?= $aiOn ? '0' : '1' ?>">
                    <?php if ($aiOn): ?>
                        <button class="btn btn-soft btn-sm" style="color:#7c3aed" title="Volver a tomar la conversación">
                            <i class="lucide lucide-user-check text-[13px]"></i> Tomar de la IA
                        </button>
                    <?php else: ?>
                        <button class="btn btn-soft btn-sm" style="color:#7c3aed" title="Que la IA responda en automático">
                            <i class="lucide lucide-sparkles text-[13px]"></i> Pasar a IA
                        </button>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
            <?php if ($convo['ticket_id']): ?>
                <a href="<?= $url('/t/' . $slug . '/tickets/' . (int)$convo['ticket_id']) ?>" class="btn btn-soft btn-sm"><i class="lucide lucide-link text-[13px]"></i> Ticket</a>
            <?php else: ?>
                <form method="POST" action="<?= $url('/t/' . $slug . '/chat/' . (int)$convo['id'] . '/to-ticket') ?>">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    <button class="btn btn-soft btn-sm"><i class="lucide lucide-ticket text-[13px]"></i> Convertir a ticket</button>
                </form>
            <?php endif; ?>
            <?php if ($convo['status'] !== 'closed'): ?>
                <form method="POST" action="<?= $url('/t/' . $slug . '/chat/' . (int)$convo['id'] . '/close') ?>">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                    <button class="btn btn-soft btn-sm" style="color:#b91c1c"><i class="lucide lucide-x text-[13px]"></i> Cerrar</button>
                </form>
            <?php endif; ?>
        </div>

        <div id="msg-box" class="flex-1 overflow-y-auto p-4 space-y-2.5" style="background:#fafafb">
            <?php foreach ($messages as $m):
                $isAi = (int)($m['is_ai'] ?? 0) === 1;
            ?>
                <div class="flex <?= $m['sender_type'] === 'visitor' ? 'justify-start' : ($m['sender_type'] === 'system' ? 'justify-center' : 'justify-end') ?>">
                    <?php if ($m['sender_type'] === 'system'): ?>
                        <div class="text-[11px] text-ink-400 italic"><?= $e($m['body']) ?></div>
                    <?php else: ?>
                        <div class="max-w-[70%] rounded-2xl px-3.5 py-2.5 <?= $m['sender_type'] === 'visitor' ? 'bg-white border border-[#ececef]' : ($isAi ? 'text-white' : 'bg-brand-500 text-white') ?>" <?= $isAi ? 'style="background:linear-gradient(135deg,#7c3aed,#a78bfa)"' : '' ?>>
                            <?php if ($isAi): ?>
                                <div class="flex items-center gap-1 text-[10px] mb-0.5 text-white/85"><i class="lucide lucide-sparkles text-[10px]"></i> Respuesta IA</div>
                            <?php endif; ?>
                            <div class="text-[13.5px] whitespace-pre-wrap"><?= $e($m['body']) ?></div>
                            <div class="text-[10px] mt-1 <?= $m['sender_type'] === 'visitor' ? 'text-ink-400' : 'text-white/70' ?>"><?= $e($m['created_at']) ?><?= $m['user_name'] ? ' · ' . $e($m['user_name']) : '' ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($convo['status'] !== 'closed'): ?>
            <form @submit.prevent="send" class="p-3 flex gap-2" style="border-top:1px solid var(--border);background:white">
                <textarea x-model="body" @keydown.enter.prevent="if(!$event.shiftKey){send()}" rows="1" class="input flex-1" style="height:42px;resize:none" placeholder="Escribí una respuesta…"></textarea>
                <button class="btn btn-primary"><i class="lucide lucide-send"></i></button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Sidebar info -->
    <div class="space-y-3">
        <div class="card card-pad">
            <div class="text-[11px] uppercase font-bold tracking-[0.14em] text-ink-400 mb-2">Información</div>
            <div class="space-y-1.5 text-[12.5px]">
                <div class="flex justify-between"><span class="text-ink-500">Iniciado:</span><span class="font-mono text-ink-700"><?= $e($convo['started_at']) ?></span></div>
                <?php if (!empty($convo['user_agent'])): ?>
                    <div class="text-ink-500">User-Agent:</div>
                    <div class="text-[10.5px] text-ink-700 break-all"><?= $e(mb_strimwidth($convo['user_agent'],0,80,'…')) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function chatShow(convoId, lastId) {
    return {
        body: '',
        lastId: lastId,
        send() {
            if (!this.body.trim()) return;
            const fd = new FormData();
            fd.append('_csrf', '<?= $e($csrf) ?>');
            fd.append('body', this.body);
            fetch('<?= $url("/t/$slug/chat/") ?>' + convoId + '/reply', {
                method: 'POST', body: fd, headers: {'X-Requested-With':'XMLHttpRequest'}
            }).then(r => r.json()).then(r => {
                if (r.ok) { this.body = ''; this.poll(); }
            });
        },
        poll() {
            fetch('<?= $url("/t/$slug/chat/poll") ?>?conversation_id=' + convoId + '&since=' + this.lastId)
                .then(r => r.json()).then(r => {
                    if (!r.ok) return;
                    const box = document.getElementById('msg-box');
                    r.messages.forEach(m => {
                        this.lastId = Math.max(this.lastId, m.id);
                        const isAi = m.is_ai == 1;
                        const div = document.createElement('div');
                        div.className = 'flex ' + (m.sender_type === 'visitor' ? 'justify-start' : (m.sender_type === 'system' ? 'justify-center' : 'justify-end'));
                        if (m.sender_type === 'system') {
                            div.innerHTML = '<div class="text-[11px] text-ink-400 italic">' + escapeHtml(m.body) + '</div>';
                        } else {
                            const isVisitor = m.sender_type === 'visitor';
                            const bubbleStyle = isAi ? 'style="background:linear-gradient(135deg,#7c3aed,#a78bfa)"' : '';
                            const cls = isVisitor ? 'bg-white border border-[#ececef]' : (isAi ? 'text-white' : 'bg-brand-500 text-white');
                            const tcls = isVisitor ? 'text-ink-400' : 'text-white/70';
                            const aiBadge = isAi ? '<div class="flex items-center gap-1 text-[10px] mb-0.5 text-white/85"><i class="lucide lucide-sparkles text-[10px]"></i> Respuesta IA</div>' : '';
                            div.innerHTML = '<div class="max-w-[70%] rounded-2xl px-3.5 py-2.5 ' + cls + '" ' + bubbleStyle + '>' + aiBadge + '<div class="text-[13.5px] whitespace-pre-wrap">' + escapeHtml(m.body) + '</div><div class="text-[10px] mt-1 ' + tcls + '">' + escapeHtml(m.created_at) + (m.user_name ? ' · ' + escapeHtml(m.user_name) : '') + '</div></div>';
                        }
                        box.appendChild(div);
                    });
                    if (r.messages.length) box.scrollTop = box.scrollHeight;
                }).finally(() => {
                    setTimeout(() => this.poll(), 4000);
                });
        }
    };
}
function escapeHtml(s) { return String(s||'').replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])); }
document.addEventListener('DOMContentLoaded', () => { const b = document.getElementById('msg-box'); if (b) b.scrollTop = b.scrollHeight; });
</script>
