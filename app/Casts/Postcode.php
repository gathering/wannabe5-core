<?php

namespace App\Casts;

use Brick\Postcode\PostcodeFormatter;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class Postcode implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (! isset($value) or ! isset($attributes['countrycode'])) {
            return $value;
        }
        $formatter = new PostcodeFormatter;

        return $formatter->format($attributes['countrycode'], $value);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return $value;
    }
}
