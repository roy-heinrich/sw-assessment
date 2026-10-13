<?php
declare(strict_types=1);

namespace App;

/** Formats the stored name parts for display. */
final class GuestName
{
    /**
     * "First M. Last" — the middle initial is dropped when there is no middle name.
     *
     * @param array<string, mixed> $row a booking row (or submitted form values)
     */
    public static function display(array $row): string
    {
        $first = trim((string)($row['first_name'] ?? ''));
        $middle = trim((string)($row['middle_name'] ?? ''));
        $last = trim((string)($row['last_name'] ?? ''));

        $initial = $middle === '' ? '' : ' ' . mb_strtoupper(mb_substr($middle, 0, 1)) . '.';

        return trim($first . $initial . ' ' . $last);
    }
}
