<?php

namespace App\Rules;

use Brick\Postcode\InvalidPostcodeException;
use Brick\Postcode\PostcodeFormatter;
use Brick\Postcode\UnknownCountryException;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;

class Postcode implements DataAwareRule, ValidationRule
{
    /**
     * All of the data under validation.
     *
     * @var array<string, mixed>
     */
    protected $data = [];

    /**
     * Where to find countrycode
     */
    protected $countrycode_field;

    public function __construct($countrycode_field = null)
    {
        $this->countrycode_field = $countrycode_field;
    }

    /**
     * Set the data under validation.
     *
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $formatter = new PostcodeFormatter;
        $countrycode = $this->countrycode_field ? Arr::get($this->data, $this->countrycode_field) : 'NO';
        try {
            $formatter->format($countrycode, $value);
        } catch (UnknownCountryException $th) {
            $fail('countrycode is invalid or has no postcode rules');
        } catch (InvalidPostcodeException $th) {
            $fail(':attribute is a invalid postcode');
        }
    }
}
