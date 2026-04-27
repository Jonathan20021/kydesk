<?php
$isNps = $survey['type'] === 'nps';
$max = $isNps ? 10 : 5;
$done = !empty($survey['responded_at']);
?>

<div class="min-h-screen flex items-center justify-center px-4 py-12" style="background:linear-gradient(135deg,#f3f0ff,#ecfdf5)">
    <div class="w-full max-w-lg bg-white rounded-3xl shadow-xl overflow-hidden border border-[#ececef]">
        <div class="p-6 sm:p-8 text-center" style="background:linear-gradient(135deg,#7c5cff,#a78bfa);color:white">
            <div class="text-[11px] font-bold uppercase tracking-[0.16em] opacity-80"><?= $survey['tenant_name'] ?></div>
            <h1 class="font-display font-extrabold text-[24px] mt-2"><?= $e($cfg['subject'] ?? '¿Cómo fue tu experiencia?') ?></h1>
            <p class="text-[13.5px] opacity-90 mt-2">Ticket <span class="font-mono"><?= $e($survey['ticket_code']) ?></span> · <?= $e($survey['ticket_subject']) ?></p>
        </div>

        <div class="p-6 sm:p-8">
            <?php if ($done): ?>
                <div class="text-center py-6">
                    <div class="w-16 h-16 mx-auto rounded-full bg-emerald-100 text-emerald-600 grid place-items-center mb-4"><i class="lucide lucide-check text-[28px]"></i></div>
                    <h2 class="font-display font-bold text-[20px]">¡Gracias por tu feedback!</h2>
                    <p class="text-[13.5px] text-ink-500 mt-2"><?= $e($cfg['thanks_message'] ?? 'Tu opinión nos ayuda a mejorar todos los días.') ?></p>
                    <?php if ($isNps): ?>
                        <div class="mt-5 inline-flex items-center gap-2 px-4 py-2 rounded-full bg-brand-50 text-brand-700 font-mono">
                            <span class="text-[11px] uppercase font-bold tracking-wider">Tu calificación</span>
                            <span class="font-display font-extrabold text-[18px]"><?= (int)$survey['score'] ?>/10</span>
                        </div>
                    <?php else: ?>
                        <div class="mt-5 text-[36px]"><?= ['😡','😞','😐','🙂','😍'][max(0,(int)$survey['score']-1)] ?? '—' ?></div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php if (!empty($cfg['intro'])): ?>
                    <p class="text-[14px] text-ink-700 mb-5"><?= $e($cfg['intro']) ?></p>
                <?php endif; ?>

                <form method="POST" action="<?= $url('/csat/' . $survey['token']) ?>" x-data="{score:null, comment:''}">
                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">

                    <?php if ($isNps): ?>
                        <p class="text-[13.5px] text-ink-500 mb-3 text-center">¿Qué tan probable es que recomiendes a un amigo o colega?</p>
                        <div class="grid grid-cols-11 gap-1">
                            <?php for ($i = 0; $i <= 10; $i++):
                                $bg = $i <= 6 ? '#fee2e2' : ($i <= 8 ? '#fef3c7' : '#d1fae5');
                                $cl = $i <= 6 ? '#dc2626' : ($i <= 8 ? '#d97706' : '#16a34a');
                            ?>
                                <button type="button" @click="score=<?= $i ?>" :class="score===<?= $i ?> ? 'ring-2 ring-offset-2' : ''" class="aspect-square rounded-lg font-display font-bold text-[16px] transition" style="background:<?= $bg ?>;color:<?= $cl ?>"><?= $i ?></button>
                            <?php endfor; ?>
                        </div>
                        <div class="flex justify-between text-[11px] text-ink-400 mt-1.5 px-1"><span>Nada probable</span><span>Muy probable</span></div>
                    <?php else: ?>
                        <p class="text-[13.5px] text-ink-500 mb-3 text-center">¿Qué tan satisfecho estás con la atención recibida?</p>
                        <div class="grid grid-cols-5 gap-2">
                            <?php
                            $emoji = ['😡','😞','😐','🙂','😍'];
                            $labels = ['Muy mal','Mal','Regular','Bien','Excelente'];
                            for ($i = 1; $i <= 5; $i++): ?>
                                <button type="button" @click="score=<?= $i ?>" :class="score===<?= $i ?> ? 'ring-2 ring-brand-500 bg-brand-50 -translate-y-1' : ''" class="aspect-square rounded-2xl border border-[#ececef] hover:border-brand-300 transition flex flex-col items-center justify-center gap-1 hover:-translate-y-0.5">
                                    <span class="text-[28px]"><?= $emoji[$i-1] ?></span>
                                    <span class="text-[10.5px] text-ink-500 font-medium"><?= $labels[$i-1] ?></span>
                                </button>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>

                    <input type="hidden" name="score" :value="score">

                    <div class="mt-5">
                        <label class="text-[12.5px] font-semibold text-ink-700">Comentario (opcional)</label>
                        <textarea name="comment" x-model="comment" rows="3" class="input mt-1.5" placeholder="¿Algo que querés contarnos?" style="height:auto;padding:10px 14px"></textarea>
                    </div>

                    <button :disabled="score === null" class="btn btn-primary w-full mt-5" :class="score === null ? 'opacity-50 cursor-not-allowed' : ''" style="height:48px"><i class="lucide lucide-send"></i> Enviar feedback</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="px-6 py-4 text-center text-[11.5px] text-ink-400" style="background:#fafafb;border-top:1px solid #ececef">
            Tu respuesta es confidencial · Powered by Kydesk Helpdesk
        </div>
    </div>
</div>
