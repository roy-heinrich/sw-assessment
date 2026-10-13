<?php
declare(strict_types=1);

/**
 * Repository checks. Runs against a throwaway SQLite file so it never touches
 * real data. Run: php tests/repository_test.php
 */

$tmp = tempnam(sys_get_temp_dir(), 'sw_repo_');
putenv('SQLITE_PATH=' . $tmp);
putenv('DATABASE_URL');
unset($_ENV['DATABASE_URL'], $_SERVER['DATABASE_URL']);

require __DIR__ . '/../src/Database.php';
require __DIR__ . '/../src/BookingRepository.php';
require __DIR__ . '/../src/GuestName.php';

use App\BookingRepository;
use App\Database;
use App\GuestName;

$failures = 0;
function check(bool $ok, string $label): void
{
    global $failures;
    if ($ok) {
        echo "ok   - {$label}\n";
    } else {
        $failures++;
        echo "FAIL - {$label}\n";
    }
}

$repo = new BookingRepository(Database::getConnection());

$id = $repo->create([
    'first_name' => '100%',
    'middle_name' => 'Sure',
    'last_name' => 'Guest',
    'contact_number' => '+63 917 000 0000',
    'pickup_point' => 'Kalibo Airport',
    'dropoff_point' => 'Station 2',
    'travel_date' => '2026-01-01',
    'status' => 'Confirmed',
]);
check($id > 0, 'create returns a new id');
check(count($repo->getAll()) === 1, 'new booking shows in the active list');
check(GuestName::display($repo->find($id) ?? []) === '100% S. Guest', 'name shows with middle initial');

$repo->delete($id);
check(count($repo->getAll()) === 0, 'deleted booking leaves the active list');
check(count($repo->getAll('', '', true)) === 1, 'deleted booking shows in the archived list');
check($repo->delete($id) === false, 'deleting twice reports no-op');

check($repo->update($id, ['first_name' => 'x']) === false, 'update on an archived booking is refused');

check($repo->restore($id) === true, 'restore succeeds');
check(count($repo->getAll()) === 1, 'restored booking is active again');
check($repo->restore($id) === false, 'restoring an active booking reports no-op');

check($repo->updateStatus($id, 'Bogus') === false, 'unknown status is refused');
check($repo->updateStatus($id, 'Pending') === true, 'valid status is saved');
check($repo->updateStatus(999999, 'Pending') === false, 'status on a missing id reports no-op');

check($repo->update(999999, [
    'first_name' => 'x', 'contact_number' => '1234567',
    'pickup_point' => 'a', 'dropoff_point' => 'b', 'travel_date' => '2026-01-01',
]) === false, 'update on a missing id reports no-op');

check(GuestName::display(['first_name' => 'Juan', 'middle_name' => '', 'last_name' => 'Dela Cruz']) === 'Juan Dela Cruz', 'name omits the initial when no middle name');

check(count($repo->getAll('%')) === 1, 'search escapes the % wildcard');

$stats = $repo->getStats();
check($stats['total'] === 1 && $stats['pending'] === 1, 'stats count active bookings by status');

@unlink($tmp);
echo $failures === 0 ? "\nAll repository checks passed.\n" : "\n{$failures} check(s) failed.\n";
exit($failures === 0 ? 0 : 1);
