<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use libphonenumber\PhoneNumberUtil;

class Countrycode implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $codes = PhoneNumberUtil::getInstance()->getSupportedRegions();
        if (in_array(strtoupper($value), $codes) === false) {
            $fail('The :attribute must be a valid ISO_3166-2 code.');
        }
    }
}
