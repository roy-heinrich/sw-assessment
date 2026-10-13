<?php
/**
 * @var array<array<string, mixed>> $bookings
 */
$showArchived = $showArchived ?? false;
$page = $page ?? 1;
$totalPages = $totalPages ?? 1;
$total = $total ?? count($bookings);
$perPage = $perPage ?? 5;

// Keeps the current search/filter/page in every link so actions don't reset the view.
$query = function (array $params) use ($search, $statusFilter, $showArchived): string {
    $base = array_filter([
        'search' => $search ?? '',
        'status' => $statusFilter ?? '',
        'show' => $showArchived ? 'archived' : '',
    ]);
    return http_build_query($base + $params);
};
?>
<div id="booking-table-container">
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
        <!-- Mobile: table converted to cards via data-labels -->
        <div class="md:hidden">
            <table class="w-full text-sm bg-transparent border-none shadow-none" data-table-cards>
                <thead class="sr-only">
                    <tr>
                        <th>Booking Ref</th>
                        <th>Passenger / Guest</th>
                        <th>Service</th>
                        <th>Transfer Route</th>
                        <th>Travel Date</th>
                        <th>Pax</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $b): ?>
                        <?php
                            $status = (string)$b['status'];
                            $displayName = \App\GuestName::display($b);
                            $statusDot = match ($status) {
                                'Confirmed' => 'bg-emerald-500',
                                'Pending' => 'bg-amber-500',
                                'Completed' => 'bg-blue-500',
                                'Cancelled' => 'bg-rose-500',
                                default => 'bg-slate-400',
                            };
                        ?>
                        <tr id="booking-row-<?= (int)$b['id'] ?>">
                            <td data-label="Booking Ref" class="py-2.5 px-3 font-mono font-medium text-slate-500 dark:text-slate-400 text-xs before:font-sans before:text-[10px] before:uppercase before:tracking-wider before:text-slate-400 dark:before:text-slate-500">
                                #SW-<?= str_pad((string)$b['id'], 5, '0', STR_PAD_LEFT) ?>
                            </td>
                            <td data-label="Passenger / Guest" class="py-2.5 px-3 before:font-sans before:text-[10px] before:uppercase before:tracking-wider before:text-slate-400 dark:before:text-slate-500">
                                <?php if (empty($b['deleted_at'])): ?>
                                <button
                                    hx-get="?action=edit_form&id=<?= (int)$b['id'] ?>"
                                    hx-target="#modal-container"
                                    hx-swap="innerHTML"
                                    title="Open booking"
                                    class="text-left font-semibold text-slate-800 dark:text-slate-100 hover:text-teal-600 dark:hover:text-teal-400 hover:underline transition">
                                    <?= htmlspecialchars($displayName) ?>
                                </button>
                                <?php else: ?>
                                <div class="font-semibold text-slate-800 dark:text-slate-100"><?= htmlspecialchars($displayName) ?></div>
                                <?php endif; ?>
                                <div class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1 mt-0.5">
                                    <svg class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                    <?= htmlspecialchars((string)$b['contact_number']) ?>
                                </div>
                            </td>
                            <td data-label="Service" class="py-2.5 px-3 align-top before:font-sans before:text-[10px] before:uppercase before:tracking-wider before:text-slate-400 dark:before:text-slate-500">
                                <div class="font-medium text-slate-700 dark:text-slate-200 text-xs inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-slate-100 dark:bg-night-700 text-slate-700 dark:text-slate-200">
                                    <?= htmlspecialchars((string)$b['service_type']) ?>
                                </div>
                            </td>
                            <td data-label="Transfer Route" class="py-2.5 px-3 before:font-sans before:text-[10px] before:uppercase before:tracking-wider before:text-slate-400 dark:before:text-slate-500">
                                <div class="text-xs text-slate-600 dark:text-slate-300">
                                    <div class="flex items-start gap-2">
                                        <span class="mt-1 w-1.5 h-1.5 rounded-full bg-teal-500 shrink-0"></span>
                                        <span><span class="text-[10px] uppercase tracking-wider text-slate-400 dark:text-slate-500 mr-1">Pickup</span><?= htmlspecialchars((string)$b['pickup_point']) ?></span>
                                    </div>
                                    <div class="ml-0.5 h-3 border-l border-dashed border-slate-300 dark:border-night-600"></div>
                                    <div class="flex items-start gap-2">
                                        <span class="mt-1 w-1.5 h-1.5 rounded-full border border-slate-400 dark:border-slate-500 shrink-0"></span>
                                        <span><span class="text-[10px] uppercase tracking-wider text-slate-400 dark:text-slate-500 mr-1">Dropoff</span><?= htmlspecialchars((string)$b['dropoff_point']) ?></span>
                                    </div>
                                </div>
                                <?php if (!empty($b['notes'])): ?>
                                    <div class="text-[11px] text-slate-400 dark:text-slate-500 italic mt-0.5" title="<?= htmlspecialchars((string)$b['notes']) ?>">
                                        <?= htmlspecialchars((string)$b['notes']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td data-label="Travel Date" class="py-2.5 px-3 text-slate-600 dark:text-slate-300 text-xs before:font-sans before:text-[10px] before:uppercase before:tracking-wider before:text-slate-400 dark:before:text-slate-500">
                                <div class="font-medium"><?= date('M d, Y', strtotime((string)$b['travel_date'])) ?></div>
                                <div class="text-[11px] text-slate-400 dark:text-slate-500"><?= date('l', strtotime((string)$b['travel_date'])) ?></div>
                            </td>
                            <td data-label="Pax" class="py-2.5 px-3 text-center before:font-sans before:text-[10px] before:uppercase before:tracking-wider before:text-slate-400 dark:before:text-slate-500">
                                <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 dark:bg-night-700 text-slate-700 dark:text-slate-200">
                                    <?= (int)$b['passengers'] ?>
                                </span>
                            </td>
                            <td data-label="Status" class="py-2.5 px-3 text-center before:font-sans before:text-[10px] before:uppercase before:tracking-wider before:text-slate-400 dark:before:text-slate-500">
                                <?php if (empty($b['deleted_at'])): ?>
                                    <form hx-post="?action=quick_status&id=<?= (int)$b['id'] ?>&page=<?= $page ?>" hx-target="#main-content" hx-swap="innerHTML" class="inline">
                                                                                <select
                                            name="new_status"
                                            onchange="this.form.submit()"
                                            class="text-xs font-medium rounded-lg border border-slate-200 dark:border-night-600 bg-white dark:bg-night-700 text-slate-700 dark:text-slate-200 py-1 pl-2 pr-7 focus:outline-none focus:ring-2 focus:ring-teal-500">
                                        <?php foreach (['Confirmed', 'Pending', 'Completed', 'Cancelled'] as $st): ?>
                                            <option value="<?= $st ?>" <?= $st === $status ? 'selected' : '' ?>><?= $st ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                     </form>
                                <?php else: ?>
                                    <div class="inline-flex items-center gap-1.5 justify-center">
                                        <span class="w-1.5 h-1.5 rounded-full <?= $statusDot ?>"></span>
                                        <span class="text-xs font-medium text-slate-600 dark:text-slate-300"><?= htmlspecialchars($status) ?></span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td data-label="Actions" class="py-2.5 px-3 text-right before:font-sans before:text-[10px] before:uppercase before:tracking-wider before:text-slate-400 dark:before:text-slate-500">
                                <div class="inline-flex items-center gap-1.5 justify-end">
                                    <?php if (!empty($b['deleted_at'])): ?>
                                    <span class="text-[11px] text-slate-400 dark:text-slate-500 mr-1 hidden sm:inline">Deleted <?= date('M d, Y', strtotime((string)$b['deleted_at'])) ?></span>
                                    <button 
                                        hx-get="?<?= $query(['action' => 'restore_form', 'id' => (int)$b['id'], 'page' => $page]) ?>"
                                        hx-target="#modal-container"
                                        hx-swap="innerHTML"
                                        title="Restore Booking"
                                        class="p-1.5 text-slate-500 dark:text-slate-400 hover:text-emerald-600 dark:hover:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-500/10 rounded-lg transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    </button>
                                    <button 
                                        hx-get="?<?= $query(['action' => 'purge_form', 'id' => (int)$b['id'], 'page' => $page]) ?>"
                                        hx-target="#modal-container"
                                        hx-swap="innerHTML"
                                        title="Delete Permanently"
                                        class="p-1.5 text-slate-500 dark:text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10 rounded-lg transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
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
                                        hx-get="?<?= $query(['action' => 'delete_form', 'id' => (int)$b['id'], 'page' => $page]) ?>"
                                        hx-target="#modal-container"
                                        hx-swap="innerHTML"
                                        title="Archive Booking"
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
        </div>

        <!-- Desktop: table -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm bg-white dark:bg-night-800 rounded-xl shadow-sm border border-slate-200 dark:border-night-600">
                <thead>
                    <tr class="bg-slate-50/80 dark:bg-night-700/80 text-slate-600 dark:text-slate-300 text-xs font-semibold uppercase tracking-wider border-b border-slate-200 dark:border-night-600">
                        <th class="py-3.5 px-4 border-r border-slate-200 dark:border-night-600 last:border-r-0">Booking Ref</th>
                        <th class="py-3.5 px-4 border-r border-slate-200 dark:border-night-600 last:border-r-0">Passenger / Guest</th>
                        <th class="py-3.5 px-4 border-r border-slate-200 dark:border-night-600 last:border-r-0">Service</th>
                        <th class="py-3.5 px-4 border-r border-slate-200 dark:border-night-600 last:border-r-0">Transfer Route</th>
                        <th class="py-3.5 px-4 border-r border-slate-200 dark:border-night-600 last:border-r-0">Travel Date</th>
                        <th class="py-3.5 px-4 text-center border-r border-slate-200 dark:border-night-600 last:border-r-0">Pax</th>
                        <th class="py-3.5 px-4 text-center border-r border-slate-200 dark:border-night-600 last:border-r-0">Status</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-night-600">
                    <?php foreach ($bookings as $b): ?>
                        <?php
                            $status = (string)$b['status'];
                            $displayName = \App\GuestName::display($b);
                            $statusDot = match ($status) {
                                'Confirmed' => 'bg-emerald-500',
                                'Pending' => 'bg-amber-500',
                                'Completed' => 'bg-blue-500',
                                'Cancelled' => 'bg-rose-500',
                                default => 'bg-slate-400',
                            };
                        ?>
                        <tr id="booking-row-<?= (int)$b['id'] ?>" class="hover:bg-slate-50/70 dark:hover:bg-night-700/50 transition duration-150">
                            <td class="py-3.5 px-4 font-mono font-medium text-slate-500 dark:text-slate-400 text-xs border-r border-slate-200 dark:border-night-600 last:border-r-0">
                                #SW-<?= str_pad((string)$b['id'], 5, '0', STR_PAD_LEFT) ?>
                            </td>
                            <td class="py-3.5 px-4 border-r border-slate-200 dark:border-night-600 last:border-r-0">
                                <?php if (empty($b['deleted_at'])): ?>
                                <button
                                    hx-get="?action=edit_form&id=<?= (int)$b['id'] ?>"
                                    hx-target="#modal-container"
                                    hx-swap="innerHTML"
                                    title="Open booking"
                                    class="text-left font-semibold text-slate-800 dark:text-slate-100 hover:text-teal-600 dark:hover:text-teal-400 hover:underline transition">
                                    <?= htmlspecialchars($displayName) ?>
                                </button>
                                <?php else: ?>
                                <div class="font-semibold text-slate-800 dark:text-slate-100"><?= htmlspecialchars($displayName) ?></div>
                                <?php endif; ?>
                                <div class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1 mt-0.5">
                                    <svg class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                    <?= htmlspecialchars((string)$b['contact_number']) ?>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 align-top border-r border-slate-200 dark:border-night-600 last:border-r-0">
                                <div class="font-medium text-slate-700 dark:text-slate-200 text-xs inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-slate-100 dark:bg-night-700 text-slate-700 dark:text-slate-200">
                                    <?= htmlspecialchars((string)$b['service_type']) ?>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 border-r border-slate-200 dark:border-night-600 last:border-r-0">
                                <div class="text-xs text-slate-600 dark:text-slate-300">
                                    <div class="flex items-start gap-2">
                                        <span class="mt-1 w-1.5 h-1.5 rounded-full bg-teal-500 shrink-0"></span>
                                        <span><span class="text-[10px] uppercase tracking-wider text-slate-400 dark:text-slate-500 mr-1">Pickup</span><?= htmlspecialchars((string)$b['pickup_point']) ?></span>
                                    </div>
                                    <div class="ml-0.5 h-3 border-l border-dashed border-slate-300 dark:border-night-600"></div>
                                    <div class="flex items-start gap-2">
                                        <span class="mt-1 w-1.5 h-1.5 rounded-full border border-slate-400 dark:border-slate-500 shrink-0"></span>
                                        <span><span class="text-[10px] uppercase tracking-wider text-slate-400 dark:text-slate-500 mr-1">Dropoff</span><?= htmlspecialchars((string)$b['dropoff_point']) ?></span>
                                    </div>
                                </div>
                                <?php if (!empty($b['notes'])): ?>
                                    <div class="text-[11px] text-slate-400 dark:text-slate-500 italic mt-0.5 truncate max-w-xs" title="<?= htmlspecialchars((string)$b['notes']) ?>">
                                        <?= htmlspecialchars((string)$b['notes']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="py-3.5 px-4 text-slate-600 dark:text-slate-300 text-xs border-r border-slate-200 dark:border-night-600 last:border-r-0">
                                <div class="font-medium"><?= date('M d, Y', strtotime((string)$b['travel_date'])) ?></div>
                                <div class="text-[11px] text-slate-400 dark:text-slate-500"><?= date('l', strtotime((string)$b['travel_date'])) ?></div>
                            </td>
                            <td class="py-3.5 px-4 text-center border-r border-slate-200 dark:border-night-600 last:border-r-0">
                                <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 dark:bg-night-700 text-slate-700 dark:text-slate-200">
                                    <?= (int)$b['passengers'] ?>
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center border-r border-slate-200 dark:border-night-600 last:border-r-0">
                                <div class="relative inline-block text-left group"
                                     onmouseenter="var m=this.querySelector('.status-menu');if(!m)return;m.style.display='block';var b=this.getBoundingClientRect(),r=m.getBoundingClientRect();m.style.display='';m.style.position='fixed';m.style.margin='0';m.style.top=Math.max(8,(b.bottom+r.height>window.innerHeight-8?b.top-r.height:b.bottom))+'px';m.style.left=Math.max(8,b.right-r.width)+'px'">
                                    <button type="button" class="inline-flex items-center gap-1.5 -mx-1.5 px-1.5 py-0.5 rounded-md text-xs font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-night-700 hover:text-slate-900 dark:hover:text-slate-100 transition">
                                        <span class="w-1.5 h-1.5 rounded-full <?= $statusDot ?>"></span>
                                        <?= htmlspecialchars($status) ?>
                                        <svg class="w-3 h-3 text-slate-400 dark:text-slate-500 group-hover:text-slate-600 dark:group-hover:text-slate-300 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <?php if (empty($b['deleted_at'])): ?>
                                    <div class="status-menu hidden group-hover:block absolute z-50 -ml-4 mt-1 w-32 rounded-lg bg-white dark:bg-night-800 shadow-lg ring-1 ring-black ring-opacity-5 dark:ring-night-600 py-1 text-xs before:absolute before:content-[''] before:-top-1 before:left-0 before:h-1 before:w-full">
                                        <?php foreach (['Confirmed', 'Pending', 'Completed', 'Cancelled'] as $st): ?>
                                            <?php if ($st !== $status): ?>
                                                <a 
                                                    href="javascript:void(0)"
                                                    hx-post="?<?= $query(['action' => 'quick_status', 'id' => (int)$b['id'], 'new_status' => $st, 'page' => $page]) ?>"
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
                                        hx-get="?<?= $query(['action' => 'restore_form', 'id' => (int)$b['id'], 'page' => $page]) ?>"
                                        hx-target="#modal-container"
                                        hx-swap="innerHTML"
                                        title="Restore"
                                        class="p-1.5 text-slate-500 dark:text-slate-400 hover:text-emerald-600 dark:hover:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-500/10 rounded-lg transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    </button>
                                    <button 
                                        hx-get="?<?= $query(['action' => 'purge_form', 'id' => (int)$b['id'], 'page' => $page]) ?>"
                                        hx-target="#modal-container"
                                        hx-swap="innerHTML"
                                        title="Delete Permanently"
                                        class="p-1.5 text-slate-500 dark:text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10 rounded-lg transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                    <?php else: ?>
                                    <button 
                                        hx-get="?action=edit_form&id=<?= (int)$b['id'] ?>"
                                        hx-target="#modal-container"
                                        hx-swap="innerHTML"
                                        title="Edit"
                                        class="p-1.5 text-slate-500 dark:text-slate-400 hover:text-teal-600 dark:hover:text-teal-400 hover:bg-teal-50 dark:hover:bg-teal-500/10 rounded-lg transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <button 
                                        hx-get="?<?= $query(['action' => 'delete_form', 'id' => (int)$b['id'], 'page' => $page]) ?>"
                                        hx-target="#modal-container"
                                        hx-swap="innerHTML"
                                        title="Archive"
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
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($bookings)): ?>
    <?php
        $start = (($page - 1) * $perPage) + 1;
        $end = $start + count($bookings) - 1;
        $navBtn = 'inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 dark:border-night-600 text-slate-600 dark:text-slate-300 hover:text-teal-700 dark:hover:text-teal-300 hover:bg-slate-50 dark:hover:bg-night-700 transition';
        $navOff = 'inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 dark:border-night-600 text-slate-300 dark:text-night-600 cursor-not-allowed';
    ?>
    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 mt-4">
        <p class="text-xs text-slate-500 dark:text-slate-400">
            Showing
            <span class="font-semibold text-slate-700 dark:text-slate-200"><?= (int)$start ?>&ndash;<?= (int)$end ?></span>
            of
            <span class="font-semibold text-slate-700 dark:text-slate-200"><?= (int)$total ?></span>
        </p>
        <?php if ($totalPages > 1): ?>
        <div class="inline-flex items-center gap-2">
            <?php if ($page > 1): ?>
                <button hx-get="?<?= $query(['action' => 'index', 'page' => $page - 1]) ?>" hx-target="#main-content" hx-swap="innerHTML" class="<?= $navBtn ?>">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Prev
                </button>
            <?php else: ?>
                <span class="<?= $navOff ?>">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Prev
                </span>
            <?php endif; ?>

            <span class="text-xs font-medium text-slate-500 dark:text-slate-400 px-1">Page <?= (int)$page ?> of <?= (int)$totalPages ?></span>

            <?php if ($page < $totalPages): ?>
                <button hx-get="?<?= $query(['action' => 'index', 'page' => $page + 1]) ?>" hx-target="#main-content" hx-swap="innerHTML" class="<?= $navBtn ?>">
                    Next
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            <?php else: ?>
                <span class="<?= $navOff ?>">
                    Next
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
<?php if (!empty($bookings)): ?>
<style>
@media (max-width: 767px) {
  table[data-table-cards] thead { display: none; }
  table[data-table-cards] {
    border: none;
    background: transparent;
    box-shadow: none;
  }
  table[data-table-cards] tbody {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    background: transparent;
  }
  table[data-table-cards] tbody tr {
    display: block;
    margin: 0;
    border: 1px solid rgb(226 232 240 / var(--tw-border-opacity, 1));
    border-radius: 0.75rem;
    background: white;
    overflow: hidden;
    box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
  }
  html.dark table[data-table-cards] tbody tr {
    border-color: rgb(38 42 51 / var(--tw-border-opacity, 1));
    background: rgb(28 32 39 / var(--tw-bg-opacity, 1));
    box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.3), 0 1px 2px -1px rgb(0 0 0 / 0.3);
  }
  table[data-table-cards] tbody td {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    padding: 0.625rem 0.875rem;
    text-align: right;
    border-bottom: 1px solid rgb(241 245 249 / var(--tw-border-opacity, 1));
  }
  html.dark table[data-table-cards] tbody td {
    border-bottom-color: rgb(38 42 51 / var(--tw-border-opacity, 1));
  }
  table[data-table-cards] tbody td:last-child {
    border-bottom: none;
  }
  table[data-table-cards] tbody td::before {
    content: attr(data-label);
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: rgb(100 116 139 / var(--tw-text-opacity, 1));
    text-align: left;
    margin-right: auto;
  }
  html.dark table[data-table-cards] tbody td::before {
    color: rgb(148 163 184 / var(--tw-text-opacity, 1));
  }
  table[data-table-cards] tbody td > * {
    text-align: right;
    max-width: 70%;
  }
  table[data-table-cards] tbody td[data-label="Actions"] > * {
    max-width: 100%;
  }
}
</style>
<?php endif; ?>
