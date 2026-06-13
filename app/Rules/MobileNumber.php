<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates a mobile / contact number.
 *
 * Rule: numbers only, no letters, spaces, or special characters,
 * and exactly 11 digits (e.g. 09171234567).
 *
 * Used everywhere a mobile or contact number is accepted so the
 * same message and format applies across the whole system.
 */
class MobileNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Only digits are allowed — reject letters, spaces, and symbols.
        if (! preg_match('/^[0-9]+$/', (string) $value)) {
            $fail('The :attribute must contain numbers only (no letters, spaces, or special characters).');
            return;
        }

        // Must be exactly 11 digits.
        if (strlen((string) $value) !== 11) {
            $fail('The :attribute must be exactly 11 digits.');
        }
    }
}
