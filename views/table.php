<?php
/**
 * @var array<array<string, mixed>> $bookings
 */
$showArchived = $showArchived ?? false;
?>
<div id="booking-table-container" class="overflow-x-auto">
    <?php if (empty($bookings)): ?>
        <div class="text-center py-12 bg-white dark:bg-night-800 rounded-xl shadow-sm border border-slate-200 dark:border-night-600">
            <svg class="mx-auto h-12 w-12 text-slate-300 dark:text-night-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
            <h3 class="mt-3 text-sm font-semibold text-slate-800 dark:text-slate-100"><?= $showArchived ? 'No archived bookings' : 'No booking records found' ?></h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400"><?= $showArchived ? 'Deleted bookings appear here so you can restore them.' : 'Get started by creating a new passenger booking record.' ?></p>
            <?php if (!$showArchived): ?>
            <div class="mt-4">
                <button 
                    hx-get="?action=create_form" 
                    hx-target="#modal-container" 
                    hx-swap="innerHTML"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-teal-600 hover:bg-teal-700 rounded-lg shadow-sm transition">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    New Booking
                </button>
            </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <table class="w-full text-left border-collapse text-sm bg-white dark:bg-night-800 rounded-xl shadow-sm border border-slate-200 dark:border-night-600">
            <thead>
                <tr class="bg-slate-50/80 dark:bg-night-700/80 text-slate-600 dark:text-slate-300 text-xs font-semibold uppercase tracking-wider border-b border-slate-200 dark:border-night-600">
                    <th class="py-3.5 px-4">Booking Ref</th>
                    <th class="py-3.5 px-4">Passenger / Guest</th>
                    <th class="py-3.5 px-4">Service & Transfer Route</th>
                    <th class="py-3.5 px-4">Travel Date</th>
                    <th class="py-3.5 px-4 text-center">Pax</th>
                    <th class="py-3.5 px-4 text-center">Status</th>
                    <th class="py-3.5 px-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-night-700">
                <?php foreach ($bookings as $b): ?>
                    <?php
                        $status = (string)$b['status'];
                        $statusClass = match ($status) {
                            'Confirmed' => 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-500/30 ring-emerald-500/10',
                            'Pending' => 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-500/30 ring-amber-500/10',
                            'Completed' => 'bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-500/30 ring-blue-500/10',
                            'Cancelled' => 'bg-rose-50 dark:bg-rose-500/10 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-500/30 ring-rose-500/10',
                            default => 'bg-slate-50 dark:bg-night-700 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-night-600',
                        };
                    ?>
                    <tr id="booking-row-<?= (int)$b['id'] ?>" class="hover:bg-slate-50/70 dark:hover:bg-night-700/50 transition duration-150">
                        <td class="py-3.5 px-4 font-mono font-medium text-slate-500 dark:text-slate-400 text-xs">
                            #SW-<?= str_pad((string)$b['id'], 5, '0', STR_PAD_LEFT) ?>
                        </td>
                        <td class="py-3.5 px-4">
                            <div class="font-semibold text-slate-800 dark:text-slate-100"><?= htmlspecialchars((string)$b['guest_name']) ?></div>
                            <div class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1 mt-0.5">
                                <svg class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                <?= htmlspecialchars((string)$b['contact_number']) ?>
                            </div>
                        </td>
                        <td class="py-3.5 px-4">
                            <div class="font-medium text-slate-700 dark:text-slate-200 text-xs inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-slate-100 dark:bg-night-700 text-slate-700 dark:text-slate-200 mb-1">
                                <?= htmlspecialchars((string)$b['service_type']) ?>
                            </div>
                            <div class="text-xs text-slate-600 dark:text-slate-300 flex items-center gap-1">
                                <span class="truncate max-w-[140px]" title="<?= htmlspecialchars((string)$b['pickup_point']) ?>"><?= htmlspecialchars((string)$b['pickup_point']) ?></span>
                                <svg class="w-3 h-3 text-slate-400 dark:text-slate-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                <span class="truncate max-w-[140px]" title="<?= htmlspecialchars((string)$b['dropoff_point']) ?>"><?= htmlspecialchars((string)$b['dropoff_point']) ?></span>
                            </div>
                            <?php if (!empty($b['notes'])): ?>
                                <div class="text-[11px] text-slate-400 dark:text-slate-500 italic mt-0.5 truncate max-w-xs" title="<?= htmlspecialchars((string)$b['notes']) ?>">
                                    <?= htmlspecialchars((string)$b['notes']) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="py-3.5 px-4 text-slate-600 dark:text-slate-300 text-xs">
                            <div class="font-medium"><?= date('M d, Y', strtotime((string)$b['travel_date'])) ?></div>
                            <div class="text-[11px] text-slate-400 dark:text-slate-500"><?= date('l', strtotime((string)$b['travel_date'])) ?></div>
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 dark:bg-night-700 text-slate-700 dark:text-slate-200">
                                <?= (int)$b['passengers'] ?>
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            <div class="relative inline-block text-left group">
                                <button type="button" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border <?= $statusClass ?>">
                                    <span class="w-1.5 h-1.5 rounded-full mr-1.5 bg-current"></span>
                                    <?= htmlspecialchars($status) ?>
                                </button>
                                <?php if (empty($b['deleted_at'])): ?>
                                <div class="hidden group-hover:block absolute z-10 -ml-4 mt-1 w-32 rounded-lg bg-white dark:bg-night-800 shadow-lg ring-1 ring-black ring-opacity-5 dark:ring-night-600 py-1 text-xs before:absolute before:content-[''] before:-top-1 before:left-0 before:h-1 before:w-full">
                                    <?php foreach (['Confirmed', 'Pending', 'Completed', 'Cancelled'] as $st): ?>
                                        <?php if ($st !== $status): ?>
                                            <a 
                                                href="javascript:void(0)"
                                                hx-post="?action=quick_status&id=<?= (int)$b['id'] ?>&new_status=<?= urlencode($st) ?>"
                                                hx-target="#main-content"
                                                hx-swap="innerHTML"
                                                class="block px-3 py-1.5 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-night-700 hover:text-teal-700 dark:hover:text-teal-300 text-left">
                                                Mark <?= $st ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="py-3.5 px-4 text-right">
                            <div class="inline-flex items-center gap-1.5 justify-end">
                                <?php if (!empty($b['deleted_at'])): ?>
                                <span class="text-[11px] text-slate-400 dark:text-slate-500 mr-1 hidden sm:inline">Deleted <?= date('M d, Y', strtotime((string)$b['deleted_at'])) ?></span>
                                <button 
                                    hx-post="?action=restore&id=<?= (int)$b['id'] ?>&show=archived"
                                    hx-target="#main-content"
                                    hx-swap="innerHTML"
                                    hx-confirm="Restore the booking for '<?= htmlspecialchars((string)$b['guest_name']) ?>'?"
                                    title="Restore Booking"
                                    class="p-1.5 text-slate-500 dark:text-slate-400 hover:text-emerald-600 dark:hover:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-500/10 rounded-lg transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                </button>
                                <?php else: ?>
                                <button 
                                    hx-get="?action=edit_form&id=<?= (int)$b['id'] ?>"
                                    hx-target="#modal-container"
                                    hx-swap="innerHTML"
                                    title="Edit Booking"
                                    class="p-1.5 text-slate-500 dark:text-slate-400 hover:text-teal-600 dark:hover:text-teal-400 hover:bg-teal-50 dark:hover:bg-teal-500/10 rounded-lg transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>

                                <button 
                                    hx-post="?action=delete&id=<?= (int)$b['id'] ?>"
                                    hx-target="#main-content"
                                    hx-swap="innerHTML"
                                    hx-confirm="Move the booking for '<?= htmlspecialchars((string)$b['guest_name']) ?>' to the archive? You can restore it later."
                                    title="Delete Booking"
                                    class="p-1.5 text-slate-500 dark:text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10 rounded-lg transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
