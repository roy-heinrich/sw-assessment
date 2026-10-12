<?php
/**
 * @var array<string, mixed>|null $booking
 * @var string $mode 'create' or 'edit'
 */
$isEdit = ($mode === 'edit' && !empty($booking));
$id = $isEdit ? (int)$booking['id'] : 0;
$title = $isEdit ? "Edit Booking (#SW-" . str_pad((string)$id, 5, '0', STR_PAD_LEFT) . ")" : "New Booking";
$actionUrl = $isEdit ? "?action=update&id={$id}" : "?action=store";

$services = [
    'Airport Shuttle (Kalibo to Boracay)',
    'Airport Shuttle (Caticlan to Boracay)',
    'Private Van Transfer (Kalibo/Caticlan)',
    'Express Speedboat Transfer',
    'Island Hopping & Water Activities',
    'Door-to-Door Resort Transfer'
];
?>
<div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <div 
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
        onclick="document.getElementById('modal-container').innerHTML = ''"></div>

    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-night-800 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-xl border border-slate-200 dark:border-night-600">
            
            <div class="bg-gradient-to-r from-teal-700 to-teal-800 px-6 py-4 flex items-center justify-between text-white">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 bg-white/10 rounded-lg backdrop-blur-md">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold leading-6" id="modal-title"><?= htmlspecialchars($title) ?></h3>
                        <p class="text-xs text-teal-100">Southwest Tours (Boracay)</p>
                    </div>
                </div>
                <button 
                    type="button" 
                    onclick="document.getElementById('modal-container').innerHTML = ''"
                    class="rounded-lg p-1 text-white/80 hover:text-white hover:bg-white/10 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form 
                hx-post="<?= $actionUrl ?>" 
                hx-target="#main-content" 
                hx-swap="innerHTML"
                hx-on::after-request="if(event.detail.successful) document.getElementById('modal-container').innerHTML = '';"
                class="px-6 py-5 space-y-4">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Guest Name *</label>
                        <input 
                            type="text" 
                            name="guest_name" 
                            required 
                            placeholder="Juan Dela Cruz"
                            value="<?= htmlspecialchars((string)($booking['guest_name'] ?? '')) ?>"
                            class="w-full px-3.5 py-2 text-sm rounded-lg border border-slate-300 dark:border-night-600 dark:bg-night-700 dark:text-slate-100 dark:placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Contact Number *</label>
                        <input 
                            type="text" 
                            name="contact_number" 
                            required 
                            placeholder="+63 917 123 4567"
                            value="<?= htmlspecialchars((string)($booking['contact_number'] ?? '')) ?>"
                            class="w-full px-3.5 py-2 text-sm rounded-lg border border-slate-300 dark:border-night-600 dark:bg-night-700 dark:text-slate-100 dark:placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Service Type *</label>
                        <select 
                            name="service_type" 
                            required
                            class="w-full px-3.5 py-2 text-sm rounded-lg border border-slate-300 dark:border-night-600 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent bg-white dark:bg-night-700 dark:text-slate-100 transition">
                            <?php foreach ($services as $srv): ?>
                                <option value="<?= htmlspecialchars($srv) ?>" <?= (($booking['service_type'] ?? '') === $srv) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($srv) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Status *</label>
                        <select 
                            name="status" 
                            required
                            class="w-full px-3.5 py-2 text-sm rounded-lg border border-slate-300 dark:border-night-600 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent bg-white dark:bg-night-700 dark:text-slate-100 transition">
                            <option value="Confirmed" <?= (($booking['status'] ?? 'Confirmed') === 'Confirmed') ? 'selected' : '' ?>>Confirmed</option>
                            <option value="Pending" <?= (($booking['status'] ?? '') === 'Pending') ? 'selected' : '' ?>>Pending</option>
                            <option value="Completed" <?= (($booking['status'] ?? '') === 'Completed') ? 'selected' : '' ?>>Completed</option>
                            <option value="Cancelled" <?= (($booking['status'] ?? '') === 'Cancelled') ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Pickup Location *</label>
                        <input 
                            type="text" 
                            name="pickup_point" 
                            required 
                            placeholder="Kalibo Airport (KLO)"
                            value="<?= htmlspecialchars((string)($booking['pickup_point'] ?? '')) ?>"
                            class="w-full px-3.5 py-2 text-sm rounded-lg border border-slate-300 dark:border-night-600 dark:bg-night-700 dark:text-slate-100 dark:placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Destination *</label>
                        <input 
                            type="text" 
                            name="dropoff_point" 
                            required 
                            placeholder="Station 2 Resort (Boracay)"
                            value="<?= htmlspecialchars((string)($booking['dropoff_point'] ?? '')) ?>"
                            class="w-full px-3.5 py-2 text-sm rounded-lg border border-slate-300 dark:border-night-600 dark:bg-night-700 dark:text-slate-100 dark:placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Travel Date *</label>
                        <input 
                            type="date" 
                            name="travel_date" 
                            required 
                            value="<?= htmlspecialchars((string)($booking['travel_date'] ?? date('Y-m-d'))) ?>"
                            class="w-full px-3.5 py-2 text-sm rounded-lg border border-slate-300 dark:border-night-600 dark:bg-night-700 dark:text-slate-100 dark:placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Passengers (Pax) *</label>
                        <input 
                            type="number" 
                            name="passengers" 
                            min="1" 
                            max="50" 
                            required 
                            value="<?= (int)($booking['passengers'] ?? 1) ?>"
                            class="w-full px-3.5 py-2 text-sm rounded-lg border border-slate-300 dark:border-night-600 dark:bg-night-700 dark:text-slate-100 dark:placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Notes / Flight Info</label>
                    <textarea 
                        name="notes" 
                        rows="2" 
                        placeholder="Flight number, arrival time, special assistance"
                        class="w-full px-3.5 py-2 text-sm rounded-lg border border-slate-300 dark:border-night-600 dark:bg-night-700 dark:text-slate-100 dark:placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition"><?= htmlspecialchars((string)($booking['notes'] ?? '')) ?></textarea>
                </div>

                <div class="pt-3 border-t border-slate-200 dark:border-night-600 flex items-center justify-end gap-3">
                    <button 
                        type="button" 
                        onclick="document.getElementById('modal-container').innerHTML = ''"
                        class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-night-700 rounded-lg transition">
                        Cancel
                    </button>
                    <button 
                        type="submit"
                        class="inline-flex items-center px-5 py-2 text-sm font-medium text-white bg-teal-600 hover:bg-teal-700 active:bg-teal-800 rounded-lg shadow-sm transition">
                        <?= $isEdit ? 'Save Changes' : 'Create Booking' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
