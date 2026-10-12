<?php
/**
 * Toast notification. Include when $flashMessage is non-empty.
 *
 * @var string $flashMessage
 * @var bool   $oob Optional. When true, HTMX swaps this out-of-band into #toast-container.
 */
$oob = $oob ?? false;
?>
<?php if (!empty($flashMessage)): ?>
<?php if ($oob): ?><div hx-swap-oob="beforeend:#toast-container"><?php endif; ?>
<div class="toast pointer-events-auto flex items-start gap-3 w-80 max-w-[calc(100vw-2rem)] p-4 rounded-xl bg-white dark:bg-night-800 border border-teal-200 dark:border-teal-500/30 shadow-xl shadow-slate-900/10" role="status">
    <svg class="w-5 h-5 text-teal-600 dark:text-teal-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <p class="flex-1 text-sm text-slate-700 dark:text-slate-200"><?= htmlspecialchars($flashMessage) ?></p>
    <button type="button" onclick="this.closest('.toast').remove()" aria-label="Dismiss notification" class="shrink-0 text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
</div>
<?php if ($oob): ?></div><?php endif; ?>
<?php endif; ?>
