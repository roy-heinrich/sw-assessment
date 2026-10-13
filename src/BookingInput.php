<?php
declare(strict_types=1);

namespace App;

/**
 * Server-side check for the booking form. The HTML form validates too, but that
 * runs in the browser and can be bypassed, so the same rules must be enforced here.
 */
final class BookingInput
{
    private const CONTACT_PATTERN = '/^[0-9+()\s-]{7,20}$/';

    /** Returns an error message, or null when the input is valid. */
    public static function validate(array $post): ?string
    {
        $firstName = trim((string)($post['first_name'] ?? ''));
        $lastName = trim((string)($post['last_name'] ?? ''));
        $contact = trim((string)($post['contact_number'] ?? ''));
        $pickup = trim((string)($post['pickup_point'] ?? ''));
        $dropoff = trim((string)($post['dropoff_point'] ?? ''));
        $date = trim((string)($post['travel_date'] ?? ''));

        if ($firstName === '' || $lastName === '' || $pickup === '' || $dropoff === '') {
            return 'Please fill in all required fields.';
        }
        if (!preg_match(self::CONTACT_PATTERN, $contact)) {
            return 'Please enter a valid contact number.';
        }
        // createFromFormat rolls invalid dates over (2026-02-30 -> 2026-03-02),
        // so compare the round-trip to reject them.
        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
        if (!$parsed || $parsed->format('Y-m-d') !== $date) {
            return 'Please enter a valid travel date.';
        }
        return null;
    }
}
