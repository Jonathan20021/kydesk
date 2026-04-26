<div class="admin-card">
    <div class="admin-card-head">
        <div class="flex-1">
            <form method="GET" action="<?= $url('/admin/dev-tokens') ?>" class="max-w-[400px]">
                <input type="search" name="q" value="<?= $e($q) ?>" placeholder="Buscar token, app o developer…" class="admin-input">
            </form>
        </div>
    </div>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th>Token</th><th>Developer</th><th>App</th><th>Scopes</th><th>Último uso</th><th>Estado</th><th></th></tr></thead>
            <tbody>
                <?php if (empty($tokens)): ?>
                    <tr><td colspan="7" class="text-center py-10 text-ink-400">Sin tokens.</td></tr>
                <?php else: foreach ($tokens as $t): ?>
                    <tr>
                        <td>
                            <div class="font-display font-bold"><?= $e($t['name']) ?></div>
                            <div class="text-[11.5px] font-mono text-ink-400"><?= $e($t['token_preview']) ?></div>
                        </td>
                        <td>
                            <a href="<?= $url('/admin/developers/' . $t['developer_id']) ?>" class="font-medium hover:text-brand-700"><?= $e($t['dev_name']) ?></a>
                            <div class="text-[11px] text-ink-400"><?= $e($t['dev_email']) ?></div>
                        </td>
                        <td>
                            <?php if ($t['app_name']): ?>
                                <div class="font-medium"><?= $e($t['app_name']) ?></div>
                                <div class="text-[11px] font-mono text-ink-400"><?= $e($t['app_slug']) ?></div>
                            <?php else: ?>
                                <span class="text-ink-400">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-[12px]"><?= $e($t['scopes']) ?></td>
                        <td class="text-[11.5px] text-ink-400"><?= $t['last_used_at'] ? $e($t['last_used_at']) : '<em>Nunca</em>' ?></td>
                        <td>
                            <?php if ($t['revoked_at']): ?>
                                <span class="admin-pill admin-pill-red">Revocado</span>
                            <?php elseif ($t['expires_at'] && strtotime($t['expires_at']) < time()): ?>
                                <span class="admin-pill admin-pill-amber">Expirado</span>
                            <?php else: ?>
                                <span class="admin-pill admin-pill-green">Activo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (empty($t['revoked_at'])): ?>
                                <form method="POST" action="<?= $url('/admin/dev-tokens/' . $t['id'] . '/revoke') ?>" onsubmit="return confirm('¿Revocar este token?')">
                                    <input type="hidden" name="_csrf" value="<?= $e($csrf) ?>">
                                    <button type="submit" class="admin-btn admin-btn-danger admin-btn-icon" title="Revocar"><i class="lucide lucide-x text-[13px]"></i></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
