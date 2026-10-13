<?php
declare(strict_types=1);

/**
 * Repository checks. Runs against Neon/Postgres inside a transaction so it never
 * commits changes to real data. Run: php tests/repository_test.php
 */

$testDbUrl = getenv('TEST_DATABASE_URL') ?: getenv('DATABASE_URL') ?: ($_ENV['TEST_DATABASE_URL'] ?? $_ENV['DATABASE_URL'] ?? '');
if (!$testDbUrl) {
    echo "FAIL - TEST_DATABASE_URL or DATABASE_URL must be set to run repository tests against Postgres/Neon.\n";
    exit(1);
}
putenv('DATABASE_URL=' . $testDbUrl);

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

$db = Database::getConnection();
$db->beginTransaction();
$repo = new BookingRepository($db);

$activeBefore = count($repo->getAll());

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
check(count($repo->getAll()) === ($activeBefore + 1), 'new booking shows in the active list');
check(GuestName::display($repo->find($id) ?? []) === '100% S. Guest', 'name shows with middle initial');

$repo->delete($id);
check(count($repo->getAll()) === $activeBefore, 'deleted booking leaves the active list');
check(count($repo->getAll('', '', true)) >= 1, 'deleted booking shows in the archived list');
check($repo->delete($id) === false, 'deleting twice reports no-op');

check($repo->update($id, ['first_name' => 'x']) === false, 'update on an archived booking is refused');

check($repo->restore($id) === true, 'restore succeeds');
check(count($repo->getAll()) === ($activeBefore + 1), 'restored booking is active again');
check($repo->restore($id) === false, 'restoring an active booking reports no-op');

check($repo->updateStatus($id, 'Bogus') === false, 'unknown status is refused');
check($repo->updateStatus($id, 'Pending') === true, 'valid status is saved');
check($repo->updateStatus(999999, 'Pending') === false, 'status on a missing id reports no-op');

check($repo->update(999999, [
    'first_name' => 'x', 'contact_number' => '1234567',
    'pickup_point' => 'a', 'dropoff_point' => 'b', 'travel_date' => '2026-01-01',
]) === false, 'update on a missing id reports no-op');

check(GuestName::display(['first_name' => 'Juan', 'middle_name' => '', 'last_name' => 'Dela Cruz']) === 'Juan Dela Cruz', 'name omits the initial when no middle name');

check(count($repo->getAll('%')) >= 1, 'search escapes the % wildcard');

$stats = $repo->getStats();
check($stats['total'] >= 1 && array_key_exists('pending', $stats), 'stats count active bookings by status');

$db->rollBack();
echo $failures === 0 ? "\nAll repository checks passed.\n" : "\n{$failures} check(s) failed.\n";
exit($failures === 0 ? 0 : 1);
