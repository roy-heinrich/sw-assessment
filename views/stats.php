<?php
/**
 * @var array<string, int> $stats
 */
$cards = [
    'Total Bookings' => $stats['total'] ?? 0,
    'Confirmed' => $stats['confirmed'] ?? 0,
    'Pending Review' => $stats['pending'] ?? 0,
    'Completed Trips' => $stats['completed'] ?? 0,
];
?>
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <?php foreach ($cards as $label => $count): ?>
        <div class="group relative overflow-hidden rounded-xl border border-slate-200/70 dark:border-night-600/70 bg-white dark:bg-night-800 py-5 pr-5 pl-[30px] transition-shadow hover:shadow-sm">
            <span class="absolute left-[14px] top-[22px] h-[9px] w-[9px] rounded-full bg-slate-50 dark:bg-night-800 border-[1.5px] border-slate-200 dark:border-night-600 ring-2 ring-white dark:ring-night-800" aria-hidden="true"></span>
            <span class="absolute left-[22px] top-[14px] bottom-[14px] border-l-[1.5px] border-dashed border-slate-200 dark:border-night-600" aria-hidden="true"></span>
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400"><?= $label ?></p>
            <p class="mt-2 font-mono text-2xl font-semibold tabular-nums tracking-tight text-slate-800 dark:text-slate-100"><?= number_format((int)$count) ?></p>
            <span class="absolute inset-x-0 bottom-0 h-px bg-gradient-to-r from-transparent via-teal-500/40 to-transparent opacity-0 transition-opacity group-hover:opacity-100" aria-hidden="true"></span>
        </div>
    <?php endforeach; ?>
</div>
