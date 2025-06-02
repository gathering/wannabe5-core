<?php

use App\Rules\Countrycode;
use Illuminate\Support\Facades\Validator;

test('Countrycode Validation Rule', function () {
    // Valid
    expect(Validator::make(['countrycode' => 'NO'], ['countrycode' => new Countrycode])->passes())->toBeTrue();
    expect(Validator::make(['countrycode' => 'no'], ['countrycode' => new Countrycode])->passes())->toBeTrue();
    expect(Validator::make(['countrycode' => 'SE'], ['countrycode' => new Countrycode])->passes())->toBeTrue();
    expect(Validator::make(['countrycode' => 'Gb'], ['countrycode' => new Countrycode])->passes())->toBeTrue();

    // Fail
    expect(Validator::make(['countrycode' => 'LoL'], ['countrycode' => new Countrycode])->passes())->toBeFalse();
    expect(Validator::make(['countrycode' => 'AA'], ['countrycode' => new Countrycode])->passes())->toBeFalse();
    expect(Validator::make(['countrycode' => 'Norway'], ['countrycode' => new Countrycode])->passes())->toBeFalse();
});
