<?php
/**
 * Confirmation popup shared by archive, permanent delete and restore.
 * Replaces the builtin hx-confirm dialog.
 *
 * @var array<string, mixed> $booking
 * @var int $page
 * @var string $mode 'archive' | 'purge' | 'restore'
 * @var array<string, string> $viewParams current search/status/show, if any
 */
$mode = $mode ?? 'archive';
$id = (int)$booking['id'];
$displayName = \App\GuestName::display($booking);
$ref = 'Booking #SW-' . str_pad((string)$id, 5, '0', STR_PAD_LEFT);

// Restore is harmless, so it keeps the teal "safe" accent; archive/purge are rose.
$isRestore = $mode === 'restore';
$headerClass = $isRestore ? 'from-teal-700 to-teal-800' : 'from-rose-600 to-rose-700';
$buttonClass = $isRestore
    ? 'bg-teal-600 hover:bg-teal-700 active:bg-teal-800'
    : 'bg-rose-600 hover:bg-rose-700 active:bg-rose-800';
$iconPath = $isRestore
    ? 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15'
    : 'M12 9v2m0 4h.01M5 19h14a2 2 0 001.84-2.75L13.74 4a2 2 0 00-3.5 0L3.16 16.25A2 2 0 005 19z';

$title = match ($mode) {
    'purge' => "Delete {$ref} Permanently",
    'restore' => "Restore {$ref}",
    default => "Archive {$ref}",
};
$confirmAction = match ($mode) {
    'purge' => 'purge',
    'restore' => 'restore',
    default => 'delete',
};
$buttonLabel = match ($mode) {
    'purge' => 'Delete Permanently',
    'restore' => 'Restore Booking',
    default => 'Archive Booking',
};
// `+` keeps the left-hand keys, so the action/id/page always win over stale view params.
$actionQuery = http_build_query(($viewParams ?? []) + ['action' => $confirmAction, 'id' => $id, 'page' => $page]);
?>
<div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <div
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
        onclick="document.getElementById('modal-container').innerHTML = ''"></div>

    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-night-800 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-slate-200 dark:border-night-600">

            <div class="bg-gradient-to-r <?= $headerClass ?> px-6 py-4 flex items-center justify-between text-white">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 bg-white/10 rounded-lg backdrop-blur-md">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $iconPath ?>"/></svg>
                    </div>
                    <h3 class="text-base font-semibold leading-6" id="modal-title"><?= htmlspecialchars($title) ?></h3>
                </div>
                <button
                    type="button"
                    onclick="document.getElementById('modal-container').innerHTML = ''"
                    class="rounded-lg p-1 text-white/80 hover:text-white hover:bg-white/10 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form
                hx-post="?<?= $actionQuery ?>"
                hx-target="#main-content"
                hx-swap="innerHTML"
                
                hx-on::after-request="if(event.detail.successful) document.getElementById('modal-container').innerHTML = '';"
                class="px-6 py-5">
                
                <p class="text-sm text-slate-600 dark:text-slate-300">
                    <?php if ($mode === 'purge'): ?>
                        Permanently delete the booking for
                        <span class="font-semibold text-slate-800 dark:text-slate-100"><?= htmlspecialchars($displayName) ?></span>?
                        This cannot be undone.
                    <?php elseif ($isRestore): ?>
                        Restore the booking for
                        <span class="font-semibold text-slate-800 dark:text-slate-100"><?= htmlspecialchars($displayName) ?></span>?
                        It will move back to your active bookings.
                    <?php else: ?>
                        Move the booking for
                        <span class="font-semibold text-slate-800 dark:text-slate-100"><?= htmlspecialchars($displayName) ?></span>
                        to the archive? You can restore it later from the archive.
                    <?php endif; ?>
                </p>

                <div class="mt-5 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                    <button
                        type="button"
                        onclick="document.getElementById('modal-container').innerHTML = ''"
                        class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-night-700 rounded-lg transition">
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="inline-flex items-center px-5 py-2 text-sm font-medium text-white <?= $buttonClass ?> rounded-lg shadow-sm transition">
                        <?= $buttonLabel ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
