<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MobileNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match('/^[0-9]+$/', (string) $value)) {
            $fail('The :attribute must contain numbers only (no letters, spaces, or special characters).');
            return;
        }

        if (strlen((string) $value) !== 11) {
            $fail('The :attribute must be exactly 11 digits.');
        }
    }
}
