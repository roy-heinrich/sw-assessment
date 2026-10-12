<?php
declare(strict_types=1);

$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines !== false) {
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }
            if (str_contains($line, '=')) {
                [$key, $val] = explode('=', $line, 2);
                $key = trim($key);
                $val = trim($val, " \t\n\r\0\x0B\"'");
                putenv("{$key}={$val}");
                $_ENV[$key] = $val;
                $_SERVER[$key] = $val;
            }
        }
    }
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\BookingRepository;

$repo = new BookingRepository();
$action = $_GET['action'] ?? 'index';
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$flashMessage = '';

$isHtmx = isset($_SERVER['HTTP_HX_REQUEST']) && $_SERVER['HTTP_HX_REQUEST'] === 'true';

switch ($action) {
    case 'create_form':
        $mode = 'create';
        $booking = null;
        require __DIR__ . '/views/form_modal.php';
        exit;

    case 'edit_form':
        $id = (int)($_GET['id'] ?? 0);
        $booking = $repo->find($id);
        if (!$booking) {
            http_response_code(404);
            echo "<div class='p-4 text-rose-600 bg-rose-50 rounded-lg'>Booking not found.</div>";
            exit;
        }
        $mode = 'edit';
        require __DIR__ . '/views/form_modal.php';
        exit;

    case 'store':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $guestName = trim($_POST['guest_name'] ?? '');
            $contact = trim($_POST['contact_number'] ?? '');
            $pickup = trim($_POST['pickup_point'] ?? '');
            $dropoff = trim($_POST['dropoff_point'] ?? '');

            if (!empty($guestName) && !empty($contact) && !empty($pickup) && !empty($dropoff)) {
                $repo->create($_POST);
                $flashMessage = "Booking for {$guestName} created successfully.";
            } else {
                $flashMessage = "Please fill in all required fields.";
            }
        }
        break;

    case 'update':
        $id = (int)($_GET['id'] ?? 0);
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0) {
            $guestName = trim($_POST['guest_name'] ?? '');
            $contact = trim($_POST['contact_number'] ?? '');
            $pickup = trim($_POST['pickup_point'] ?? '');
            $dropoff = trim($_POST['dropoff_point'] ?? '');

            if (!empty($guestName) && !empty($contact) && !empty($pickup) && !empty($dropoff)) {
                $repo->update($id, $_POST);
                $flashMessage = "Booking #SW-" . str_pad((string)$id, 5, '0', STR_PAD_LEFT) . " updated.";
            } else {
                $flashMessage = "Update failed: missing required fields.";
            }
        }
        break;

    case 'delete':
        $id = (int)($_GET['id'] ?? 0);
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0) {
            $repo->delete($id);
            $flashMessage = "Booking #SW-" . str_pad((string)$id, 5, '0', STR_PAD_LEFT) . " removed.";
        }
        break;

    case 'quick_status':
        $id = (int)($_GET['id'] ?? 0);
        $newStatus = (string)($_GET['status'] ?? '');
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0 && !empty($newStatus)) {
            $repo->updateStatus($id, $newStatus);
            $flashMessage = "Status updated to {$newStatus}.";
        }
        break;

    case 'search':
    case 'filter':
    case 'index':
    default:
        break;
}

$bookings = $repo->getAll($search, $statusFilter);
$stats = $repo->getStats();

if ($isHtmx) {
    if (!empty($flashMessage)) {
        echo "<div id='flash-banner' class='mb-6 p-4 rounded-xl bg-teal-50 border border-teal-200 text-teal-800 text-sm flex items-center justify-between shadow-sm animate-fade-in'>
                <div class='flex items-center gap-2.5'>
                    <svg class='w-5 h-5 text-teal-600' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'/></svg>
                    <span>" . htmlspecialchars($flashMessage) . "</span>
                </div>
                <button onclick=\"document.getElementById('flash-banner').remove()\" class='text-teal-600 hover:text-teal-800'>
                    <svg class='w-4 h-4' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M6 18L18 6M6 6l12 12'/></svg>
                </button>
              </div>";
    }
    echo "<div id='skeleton-loading' class='hidden space-y-6 animate-pulse'>
            <div class='grid grid-cols-2 md:grid-cols-4 gap-4 mb-6'>";
    for ($i = 0; $i < 4; $i++) {
        echo "<div class='bg-white p-4 rounded-xl border border-slate-200/80 shadow-sm flex items-center justify-between'>
                <div class='space-y-2 flex-1'>
                    <div class='h-3 bg-slate-200 rounded w-20'></div>
                    <div class='h-6 bg-slate-200 rounded w-12'></div>
                </div>
                <div class='w-10 h-10 bg-slate-100 rounded-lg'></div>
              </div>";
    }
    echo "</div>
          <div class='bg-white rounded-xl shadow-sm border border-slate-200 p-4 space-y-4'>
            <div class='h-5 bg-slate-200 rounded w-1/4 mb-4'></div>";
    for ($i = 0; $i < 4; $i++) {
        echo "<div class='flex items-center justify-between py-3 border-b border-slate-100 last:border-0 gap-4'>
                <div class='h-4 bg-slate-200 rounded w-20'></div>
                <div class='h-4 bg-slate-200 rounded w-32'></div>
                <div class='h-4 bg-slate-200 rounded w-48'></div>
                <div class='h-4 bg-slate-200 rounded w-16'></div>
                <div class='h-6 bg-slate-200 rounded-full w-20'></div>
                <div class='h-4 bg-slate-200 rounded w-12'></div>
              </div>";
    }
    echo "</div></div>";

    echo "<div id='manifest-data'>";
    require __DIR__ . '/views/stats.php';
    require __DIR__ . '/views/table.php';
    echo "</div>";
    exit;
}

require __DIR__ . '/views/layout.php';
