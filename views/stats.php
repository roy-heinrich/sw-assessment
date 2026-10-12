<?php
/**
 * @var array<string, int> $stats
 */
?>
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-sm flex items-center justify-between">
        <div>
            <div class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Bookings</div>
            <div class="text-2xl font-bold text-slate-800 mt-1"><?= (int)($stats['total'] ?? 0) ?></div>
        </div>
        <div class="p-2.5 bg-slate-100 rounded-lg text-slate-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        </div>
    </div>

    <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-sm flex items-center justify-between">
        <div>
            <div class="text-xs font-medium text-emerald-600 uppercase tracking-wider">Confirmed</div>
            <div class="text-2xl font-bold text-emerald-700 mt-1"><?= (int)($stats['confirmed'] ?? 0) ?></div>
        </div>
        <div class="p-2.5 bg-emerald-50 rounded-lg text-emerald-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>
    </div>

    <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-sm flex items-center justify-between">
        <div>
            <div class="text-xs font-medium text-amber-600 uppercase tracking-wider">Pending Review</div>
            <div class="text-2xl font-bold text-amber-700 mt-1"><?= (int)($stats['pending'] ?? 0) ?></div>
        </div>
        <div class="p-2.5 bg-amber-50 rounded-lg text-amber-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
    </div>

    <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-sm flex items-center justify-between">
        <div>
            <div class="text-xs font-medium text-blue-600 uppercase tracking-wider">Completed Trips</div>
            <div class="text-2xl font-bold text-blue-700 mt-1"><?= (int)($stats['completed'] ?? 0) ?></div>
        </div>
        <div class="p-2.5 bg-blue-50 rounded-lg text-blue-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
    </div>
</div>
