<?php
declare(strict_types=1);

/**
 * Booking input validation checks. Run: php tests/input_test.php
 */

require __DIR__ . '/../src/BookingInput.php';

use App\BookingInput;

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

$base = [
    'first_name' => 'Juan',
    'middle_name' => 'Santos',
    'last_name' => 'Dela Cruz',
    'contact_number' => '+63 9171234567',
    'pickup_point' => 'Kalibo',
    'dropoff_point' => 'Boracay',
    'travel_date' => '2026-05-04',
];

check(BookingInput::validate($base) === null, 'valid booking passes');
check(BookingInput::validate(['first_name' => ''] + $base) !== null, 'missing first name fails');
check(BookingInput::validate(['last_name' => ''] + $base) !== null, 'missing last name fails');
check(BookingInput::validate(['middle_name' => ''] + $base) === null, 'missing middle name passes');
check(BookingInput::validate(['pickup_point' => ''] + $base) !== null, 'missing pickup fails');
check(BookingInput::validate(['contact_number' => 'abc'] + $base) !== null, 'letters in contact fail');
check(BookingInput::validate(['contact_number' => '123'] + $base) !== null, 'too-short contact fails');
check(BookingInput::validate(['contact_number' => '+63 917123456'] + $base) !== null, '9 digits after +63 fails');
check(BookingInput::validate(['contact_number' => '+63 91712345678'] + $base) !== null, '11 digits after +63 fails');
check(BookingInput::validate(['contact_number' => '9171234567'] + $base) !== null, 'missing +63 prefix fails');
check(BookingInput::validate(['contact_number' => '+639171234567'] + $base) === null, '10 digits after +63 passes');
check(BookingInput::validate(['travel_date' => ''] + $base) !== null, 'empty date fails');
check(BookingInput::validate(['travel_date' => '2026-02-30'] + $base) !== null, 'impossible date fails');
check(BookingInput::validate(['travel_date' => 'tomorrow'] + $base) !== null, 'non-date text fails');

echo $failures === 0 ? "\nAll input checks passed.\n" : "\n{$failures} check(s) failed.\n";
exit($failures === 0 ? 0 : 1);
