<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Southwest Tours (Boracay) - Booking Manifest</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        teal: {
                            50: '#f0fdfa',
                            100: '#ccfbf1',
                            500: '#14b8a6',
                            600: '#0d9488',
                            700: '#0f766e',
                            800: '#115e59',
                            900: '#134e4a',
                        }
                    }
                }
            }
        }
    </script>

    <script src="https://unpkg.com/htmx.org@1.9.12"></script>

    <style>
        .htmx-swapping {
            opacity: 0;
            transition: opacity 150ms ease-out;
        }
        .htmx-settling {
            opacity: 1;
            transition: opacity 200ms ease-in;
        }
        .htmx-indicator {
            display: none;
        }
        .htmx-request .htmx-indicator,
        .htmx-request.htmx-indicator {
            display: inline-block;
        }
    </style>
</head>
<body onkeydown="if(event.key === 'Escape') { const m = document.getElementById('modal-container'); if(m) m.innerHTML = ''; }" class="h-full text-slate-800 antialiased flex flex-col font-sans selection:bg-teal-500 selection:text-white">

    <header class="bg-white border-b border-slate-200/80 sticky top-0 z-40 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-teal-600 to-emerald-400 flex items-center justify-center text-white shadow-md shadow-teal-500/20">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-slate-900 tracking-tight text-lg">Southwest Tours</span>
                            <span class="text-[10px] font-semibold bg-teal-50 text-teal-700 px-2 py-0.5 rounded-full border border-teal-200/60 uppercase tracking-wider">Boracay</span>
                        </div>
                        <p class="text-xs text-slate-500 hidden sm:block">Passenger Transfer & Dispatch Manifest</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <?php $isPgsql = (\App\Database::getDriverName() === 'pgsql'); ?>
                    <div class="hidden md:flex items-center gap-1.5 px-3 py-1 rounded-full text-xs border <?= $isPgsql ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200' ?>">
                        <span class="w-2 h-2 rounded-full <?= $isPgsql ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500' ?>"></span>
                        <span class="font-medium"><?= $isPgsql ? 'Neon PostgreSQL' : 'Local SQLite' ?></span>
                    </div>

                    <button 
                        hx-get="?action=create_form" 
                        hx-target="#modal-container" 
                        hx-swap="innerHTML"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-white bg-teal-600 hover:bg-teal-700 active:bg-teal-800 rounded-xl shadow-md shadow-teal-600/20 transition-all transform active:scale-95">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>New Booking</span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <?php if (!empty($flashMessage)): ?>
            <div id="flash-banner" class="mb-6 p-4 rounded-xl bg-teal-50 border border-teal-200 text-teal-800 text-sm flex items-center justify-between shadow-sm animate-fade-in">
                <div class="flex items-center gap-2.5">
                    <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span><?= htmlspecialchars($flashMessage) ?></span>
                </div>
                <button onclick="document.getElementById('flash-banner').remove()" class="text-teal-600 hover:text-teal-800">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        <?php endif; ?>

        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm mb-6 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
            
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input 
                    type="search" 
                    name="search" 
                    id="search-input"
                    placeholder="Search guest name, pickup point, destination, or phone..." 
                    value="<?= htmlspecialchars($search ?? '') ?>"
                    hx-get="?action=search"
                    hx-target="#main-content"
                    hx-swap="innerHTML"
                    hx-trigger="keyup changed delay:250ms, search"
                    hx-include="#status-filter"
                    class="w-full pl-10 pr-10 py-2.5 text-sm rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition">
                
                <div class="htmx-indicator absolute inset-y-0 right-0 pr-3.5 flex items-center text-teal-600">
                    <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2">
                    <label for="status-filter" class="text-xs font-semibold text-slate-500 uppercase tracking-wider hidden sm:block">Status:</label>
                    <select 
                        id="status-filter" 
                        name="status"
                        hx-get="?action=filter"
                        hx-target="#main-content"
                        hx-swap="innerHTML"
                        hx-include="#search-input"
                        class="px-3 py-2 text-sm rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-teal-500 transition">
                        <option value="" <?= empty($statusFilter) ? 'selected' : '' ?>>All Statuses</option>
                        <option value="Confirmed" <?= ($statusFilter === 'Confirmed') ? 'selected' : '' ?>>Confirmed</option>
                        <option value="Pending" <?= ($statusFilter === 'Pending') ? 'selected' : '' ?>>Pending</option>
                        <option value="Completed" <?= ($statusFilter === 'Completed') ? 'selected' : '' ?>>Completed</option>
                        <option value="Cancelled" <?= ($statusFilter === 'Cancelled') ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>

                <a 
                    href="?" 
                    title="Reset Filter"
                    class="p-2.5 rounded-xl border border-slate-200 text-slate-500 hover:text-slate-800 hover:bg-slate-50 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </a>
            </div>
        </div>

        <div id="main-content" class="transition-all duration-200">
            <?php require __DIR__ . '/stats.php'; ?>
            <?php require __DIR__ . '/table.php'; ?>
        </div>

    </main>

    <footer class="bg-white border-t border-slate-200 py-4 text-center text-xs text-slate-500">
        <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-2">
            <div>
                &copy; <?= date('Y') ?> Southwest Tours (Boracay) Inc. &bull; Passenger Dispatch System
            </div>
            <div class="text-slate-400 text-[11px]">
                Kalibo &bull; Caticlan &bull; Boracay Island
            </div>
        </div>
    </footer>

    <div id="modal-container"></div>

</body>
</html>
