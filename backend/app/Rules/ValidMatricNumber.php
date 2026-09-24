<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidMatricNumber implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $valueStr = (string) $value;

        if (strlen($valueStr) < 6) {
            $fail('The matriculation number is too short.');
            return;
        }

        // e.g. ACE2110003 -> ACE (0-2), 21 (3-4), 1 (5, batch indicator)
        $batchStr = substr($valueStr, 5, 1);
        
        if (!in_array($batchStr, ['1', '2'])) {
            $fail('The matriculation number is incorrect. After the year (e.g., ACE21...), the next character must be 1 or 2 to signify the first or second batch.');
        }
    }
}
