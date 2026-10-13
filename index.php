<?php
declare(strict_types=1);

// The company and its bookings are in the Philippines; default dates must match that day.
date_default_timezone_set('Asia/Manila');
// Never print stack traces to visitors; failures are logged in the catch below.
ini_set('display_errors', '0');

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
                $val = trim($val);
                if ((str_starts_with($val, '"') && str_ends_with($val, '"')) || (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
                    $val = substr($val, 1, -1);
                }
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

use App\BookingInput;
use App\BookingRepository;
use App\GuestName;

$action = $_GET['action'] ?? 'index';
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$showArchived = ($_GET['show'] ?? '') === 'archived';
// Current list view; the confirm modal re-posts these so the swap keeps the same view
// (otherwise restoring/purging from the archive would jump back to the active list).
$viewParams = array_filter([
    'search' => $search,
    'status' => $statusFilter,
    'show' => $showArchived ? 'archived' : '',
]);
$flashMessage = '';

session_start();

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    $token = csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '">';
}

function verify_csrf(): bool
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return true;
    }
    $token = $_POST['csrf_token'] ?? ($_GET['csrf_token'] ?? '');
    if (empty($_SESSION['csrf_token']) || !is_string($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

$isHtmx = isset($_SERVER['HTTP_HX_REQUEST']) && $_SERVER['HTTP_HX_REQUEST'] === 'true';

// The create modal reads no data; render it before opening a DB connection.
if ($action === 'create_form') {
    $mode = 'create';
    $booking = null;
    require __DIR__ . '/views/form_modal.php';
    exit;
}

$repo = new BookingRepository();

try {
    switch ($action) {
        case 'edit_form':
            $id = (int)($_GET['id'] ?? 0);
            $booking = $repo->find($id);
            if (!$booking) {
                http_response_code(404);
                echo "<div class='p-4 text-rose-600 dark:text-rose-300 bg-rose-50 dark:bg-rose-500/10 rounded-lg'>Booking not found.</div>";
                exit;
            }
            $mode = 'edit';
            require __DIR__ . '/views/form_modal.php';
            exit;

        case 'delete_form':
            $id = (int)($_GET['id'] ?? 0);
            $booking = $repo->find($id);
            if (!$booking) {
                http_response_code(404);
                echo "<div class='p-4 text-rose-600 dark:text-rose-300 bg-rose-50 dark:bg-rose-500/10 rounded-lg'>Booking not found.</div>";
                exit;
            }
            $page = max(1, (int)($_GET['page'] ?? 1));
            $mode = 'archive';
            require __DIR__ . '/views/confirm_modal.php';
            exit;

        case 'purge_form':
            $id = (int)($_GET['id'] ?? 0);
            $booking = $repo->find($id, true);
            if (!$booking) {
                http_response_code(404);
                echo "<div class='p-4 text-rose-600 dark:text-rose-300 bg-rose-50 dark:bg-rose-500/10 rounded-lg'>Archived booking not found.</div>";
                exit;
            }
            $page = max(1, (int)($_GET['page'] ?? 1));
            $mode = 'purge';
            require __DIR__ . '/views/confirm_modal.php';
            exit;

        case 'restore_form':
            $id = (int)($_GET['id'] ?? 0);
            $booking = $repo->find($id, true);
            if (!$booking) {
                http_response_code(404);
                echo "<div class='p-4 text-rose-600 dark:text-rose-300 bg-rose-50 dark:bg-rose-500/10 rounded-lg'>Archived booking not found.</div>";
                exit;
            }
            $page = max(1, (int)($_GET['page'] ?? 1));
            $mode = 'restore';
            require __DIR__ . '/views/confirm_modal.php';
            exit;

        case 'store':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (!verify_csrf()) {
                    $flashMessage = 'Invalid request. Please try again.';
                    break;
                }
                $error = BookingInput::validate($_POST);
                if ($error === null) {
                    $guestName = GuestName::display($_POST);
                    $repo->create($_POST);
                    $flashMessage = "Booking for {$guestName} created successfully.";
                } else {
                    $flashMessage = $error;
                }
            }
            break;

        case 'update':
            $id = (int)($_GET['id'] ?? 0);
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0) {
                if (!verify_csrf()) {
                    $flashMessage = 'Invalid request. Please try again.';
                    break;
                }
                $ref = "Booking #SW-" . str_pad((string)$id, 5, '0', STR_PAD_LEFT);
                $error = BookingInput::validate($_POST);
                if ($error !== null) {
                    $flashMessage = $error;
                } elseif ($repo->update($id, $_POST)) {
                    $flashMessage = "{$ref} updated.";
                } else {
                    $flashMessage = "{$ref} could not be updated; it may have been removed.";
                }
            }
            break;

        case 'delete':
            $id = (int)($_GET['id'] ?? 0);
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0) {
                if (!verify_csrf()) {
                    $flashMessage = 'Invalid request. Please try again.';
                    break;
                }
                $ref = "Booking #SW-" . str_pad((string)$id, 5, '0', STR_PAD_LEFT);
                $flashMessage = $repo->delete($id) ? "{$ref} removed." : "{$ref} not found.";
            }
            break;

        case 'restore':
            $id = (int)($_GET['id'] ?? 0);
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0) {
                if (!verify_csrf()) {
                    $flashMessage = 'Invalid request. Please try again.';
                    break;
                }
                $ref = "Booking #SW-" . str_pad((string)$id, 5, '0', STR_PAD_LEFT);
                $flashMessage = $repo->restore($id) ? "{$ref} restored." : "{$ref} not found.";
            }
            break;

        case 'purge':
            $id = (int)($_GET['id'] ?? 0);
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0) {
                if (!verify_csrf()) {
                    $flashMessage = 'Invalid request. Please try again.';
                    break;
                }
                $ref = "Booking #SW-" . str_pad((string)$id, 5, '0', STR_PAD_LEFT);
                $flashMessage = $repo->purge($id) ? "{$ref} permanently deleted." : "{$ref} not found.";
            }
            break;

        case 'quick_status':
            $id = (int)($_GET['id'] ?? 0);
            // Desktop links put new_status in the query string; the mobile select posts it.
            $newStatus = (string)($_POST['new_status'] ?? $_GET['new_status'] ?? '');
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0 && $newStatus !== '') {
                if (!verify_csrf()) {
                    $flashMessage = 'Invalid request. Please try again.';
                    break;
                }
                $flashMessage = $repo->updateStatus($id, $newStatus)
                    ? "Status updated to {$newStatus}."
                    : "Could not update status.";
            }
            break;

        case 'search':
        case 'filter':
        case 'index':
        default:
            break;
    }
} catch (\Throwable $e) {
    error_log('Booking action failed: ' . $e->getMessage());
    // htmx only swaps 2xx responses, so an htmx error must render as 200 to show the toast.
    if (!$isHtmx) {
        http_response_code(500);
    }
    $flashMessage = 'Something went wrong. Please try again.';
}

$perPage = 5;
$total = $repo->countAll($search, $statusFilter, $showArchived);
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min(max(1, (int)($_GET['page'] ?? 1)), $totalPages);
$bookings = $repo->getAll($search, $statusFilter, $showArchived, $perPage, ($page - 1) * $perPage);
$stats = $repo->getStats();

if ($isHtmx) {
    $oob = true;
    require __DIR__ . '/views/flash.php';

    echo "<div id='manifest-data'>";
    require __DIR__ . '/views/stats.php';
    require __DIR__ . '/views/table.php';
    echo "</div>";
    exit;
}

require __DIR__ . '/views/layout.php';
