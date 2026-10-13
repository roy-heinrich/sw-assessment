<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50 dark:bg-night-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Southwest Tours (Boracay) - Booking Manifest</title>

    <script>
        // Apply saved theme before paint to avoid a flash of the wrong theme.
        (function () {
            var saved = localStorage.getItem('theme');
            if (saved === 'dark' || (!saved && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
        function toggleTheme() {
            var dark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', dark ? 'dark' : 'light');
        }
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Prebuilt Tailwind (npm run build:css), inlined below so it always loads with the page. -->
    <script src="https://unpkg.com/htmx.org@1.9.12" integrity="sha384-ujb1lZYygJmzgSwoxRggbCHcjc0rB2XoQrxeTUQyRjrOnlCoYta87iKBWq3EsdM2" crossorigin="anonymous"></script>

    <style>
        <?php readfile(dirname(__DIR__) . '/assets/tailwind.css'); ?>
        html.dark {
            color-scheme: dark;
            background-color: #14181e;
        }
        html.dark body {
            background-color: #14181e;
        }
        .htmx-swapping {
            opacity: 0;
            transition: opacity 150ms ease-out;
        }
        .htmx-settling {
            opacity: 1;
            transition: opacity 200ms ease-in;
        }
        .htmx-indicator {
            display: none !important;
        }
        .htmx-request .htmx-indicator,
        .htmx-request.htmx-indicator {
            display: flex !important;
        }
        /* Top indeterminate loading bar */
        #global-progress {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(to right, #14b8a6, #10b981, #06b6d4);
            z-index: 9999;
            background-size: 200% 100%;
            animation: moveProgress 1s linear infinite;
        }
        @keyframes moveProgress {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        .htmx-request#global-progress,
        body.htmx-request #global-progress {
            display: block !important;
        }
        @keyframes toastIn {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .toast {
            animation: toastIn 200ms ease-out;
        }
        /* Click-to-wait feedback: dims the control and swaps its content for a spinner. */
        .is-loading {
            position: relative;
            pointer-events: none;
        }
        .is-loading > * {
            visibility: hidden;
        }
        .is-loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 1em;
            height: 1em;
            margin: -0.5em 0 0 -0.5em;
            border: 2px solid currentColor;
            border-right-color: transparent;
            border-radius: 9999px;
            animation: spin 0.6s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body hx-indicator="#global-progress" onkeydown="if(event.key === 'Escape') { const m = document.getElementById('modal-container'); if(m) m.innerHTML = ''; }" class="h-full text-slate-800 dark:text-slate-200 antialiased flex flex-col font-sans selection:bg-teal-500 selection:text-white">

    <div id="global-progress"></div>

    <div id="toast-container" class="fixed top-20 right-4 z-[60] flex flex-col items-end gap-2 pointer-events-none">
        <?php $oob = false; require __DIR__ . '/flash.php'; ?>
    </div>
    <script>
        (function () {
            var container = document.getElementById('toast-container');
            if (!container) return;
            new MutationObserver(function (mutations) {
                mutations.forEach(function (m) {
                    m.addedNodes.forEach(function (node) {
                        if (node.nodeType === 1) setTimeout(function () { node.remove(); }, 5000);
                    });
                });
            }).observe(container, { childList: true });
        })();
    </script>

    <script>
        // Click feedback: show a spinner on the control that started the action so the
        // user sees the click registered. htmx controls wait for beforeRequest so that
        // hx-confirm dialogs don't spin the button before the user has confirmed.
        (function () {
            function setLoading(el) {
                el.classList.add('is-loading');
                el.setAttribute('aria-busy', 'true');
            }
            function clearLoading() {
                document.querySelectorAll('.is-loading').forEach(function (el) {
                    el.classList.remove('is-loading');
                    el.removeAttribute('aria-busy');
                });
            }
            document.addEventListener('click', function (e) {
                if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
                if (!e.target.closest) return;
                var el = e.target.closest('a[href]:not([href^="#"]):not([href^="javascript:"])');
                if (!el || el.hasAttribute('hx-get') || el.hasAttribute('hx-post')) return;
                setLoading(el);
            });
            document.addEventListener('htmx:beforeRequest', function (e) {
                var el = e.detail.elt;
                if (el && el.matches && el.matches('button, a')) setLoading(el);
            });
            document.addEventListener('htmx:afterRequest', clearLoading);
        })();
    </script>

    <header class="bg-white dark:bg-night-800 border-b border-slate-200/80 dark:border-night-600 sticky top-0 z-40 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-teal-600 to-emerald-400 flex items-center justify-center text-white shadow-md shadow-teal-500/20">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    </div>
                    <div>
                        <span class="font-bold text-slate-900 dark:text-slate-100 tracking-tight text-lg">Southwest Tours</span>
                        <p class="text-xs text-slate-500 dark:text-slate-400 hidden sm:block">Passenger Transfer & Dispatch Manifest</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        onclick="toggleTheme()"
                        title="Toggle dark mode"
                        aria-label="Toggle dark mode"
                        class="p-2.5 rounded-xl border border-slate-200 dark:border-night-600 text-slate-500 dark:text-slate-300 hover:text-slate-800 dark:hover:text-slate-100 hover:bg-slate-50 dark:hover:bg-night-700 transition">
                        <svg class="w-4 h-4 dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                        <svg class="w-4 h-4 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </button>
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

        <div class="bg-white dark:bg-night-800 p-4 rounded-2xl border border-slate-200/80 dark:border-night-600 shadow-sm mb-6 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
            
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
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
                    hx-indicator="#search-spinner, #main-content"
                    hx-swap="innerHTML"
                    hx-trigger="keyup changed delay:400ms, search"
                    hx-include="#status-filter, #show-filter"
                    class="w-full pl-10 pr-10 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-night-600 bg-slate-50/50 dark:bg-night-700 focus:bg-white dark:focus:bg-night-800 text-slate-800 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition">
                
                <div id="search-spinner" class="htmx-indicator absolute inset-y-0 right-0 pr-3.5 items-center text-teal-600 dark:text-teal-400">
                    <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <input type="hidden" id="show-filter" name="show" value="<?= $showArchived ? 'archived' : '' ?>">
                <div class="flex items-center gap-2">
                    <label for="status-filter" class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider hidden sm:block">Status:</label>
                    <select 
                        id="status-filter" 
                        name="status"
                        hx-get="?action=filter"
                        hx-target="#main-content"
                        hx-indicator="#search-spinner, #main-content"
                        hx-swap="innerHTML"
                        hx-include="#search-input, #show-filter"
                        class="px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-night-600 bg-slate-50/50 dark:bg-night-700 focus:bg-white dark:focus:bg-night-800 text-slate-700 dark:text-slate-200 font-medium focus:outline-none focus:ring-2 focus:ring-teal-500 transition">
                        <option value="" <?= empty($statusFilter) ? 'selected' : '' ?>>All Statuses</option>
                        <option value="Confirmed" <?= ($statusFilter === 'Confirmed') ? 'selected' : '' ?>>Confirmed</option>
                        <option value="Pending" <?= ($statusFilter === 'Pending') ? 'selected' : '' ?>>Pending</option>
                        <option value="Completed" <?= ($statusFilter === 'Completed') ? 'selected' : '' ?>>Completed</option>
                        <option value="Cancelled" <?= ($statusFilter === 'Cancelled') ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>

                <a
                    href="?<?= $showArchived ? '' : 'show=archived' ?>"
                    title="<?= $showArchived ? 'Back to active bookings' : 'View archived bookings' ?>"
                    class="inline-flex items-center gap-1.5 px-3 py-2.5 text-sm font-medium rounded-xl border transition <?= $showArchived
                        ? 'border-teal-200 dark:border-teal-500/30 bg-teal-50 dark:bg-teal-500/10 text-teal-700 dark:text-teal-300 hover:bg-teal-100 dark:hover:bg-teal-500/20'
                        : 'border-slate-200 dark:border-night-600 text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-50 dark:hover:bg-night-700' ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $showArchived ? 'M10 19l-7-7m0 0l7-7m-7 7h18' : 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4' ?>"/></svg>
                    <span class="hidden sm:inline"><?= $showArchived ? 'Back to Active' : 'Archive' ?></span>
                </a>

                <a 
                    href="?" 
                    title="Reset Filter"
                    class="p-2.5 rounded-xl border border-slate-200 dark:border-night-600 text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-100 hover:bg-slate-50 dark:hover:bg-night-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </a>
            </div>
        </div>

        <div id="main-content" class="transition-all duration-200">
            <div id="manifest-data">
                <?php require __DIR__ . '/stats.php'; ?>
                <?php require __DIR__ . '/table.php'; ?>
            </div>
        </div>

    </main>

    <footer class="bg-white dark:bg-night-800 border-t border-slate-200 dark:border-night-600 py-4 text-center text-xs text-slate-500 dark:text-slate-400">
        <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-2">
            <div>
                &copy; <?= date('Y') ?> Southwest Tours (Boracay) Inc. &bull; Passenger Dispatch System
            </div>
            <div class="text-slate-400 dark:text-slate-500 text-[11px]">
                Kalibo &bull; Caticlan &bull; Boracay Island
            </div>
        </div>
    </footer>

    <div id="modal-container"></div>

</body>
</html>
