<?php

use App\Rules\Postcode;
use Illuminate\Support\Facades\Validator;

test('Postcode Validation Rule', function () {
    // Valid
    expect(Validator::make(
        ['address' => ['countrycode' => 'NO', 'postcode' => '6264']],
        ['address.postcode' => new Postcode('address.countrycode')]
    )->passes())->toBeTrue();
    expect(Validator::make(['countrycode' => 'NO', 'postcode' => '0484'], ['postcode' => new Postcode('countrycode')])->passes())->toBeTrue();
    expect(Validator::make(['countrycode' => 'SE', 'postcode' => '10316'], ['postcode' => new Postcode('countrycode')])->passes())->toBeTrue();
    expect(Validator::make(['countrycode' => 'NL', 'postcode' => '1011AE'], ['postcode' => new Postcode('countrycode')])->passes())->toBeTrue();
    expect(Validator::make(['countrycode' => 'GB', 'postcode' => 'E1 6AN'], ['postcode' => new Postcode('countrycode')])->passes())->toBeTrue();
    expect(Validator::make(['postcode' => '0484'], ['postcode' => new Postcode])->passes())->toBeTrue();

    // Fail
    expect(Validator::make(['countrycode' => 'NO', 'postcode' => '12345'], ['postcode' => new Postcode('countrycode')])->passes())->toBeFalse();
    expect(Validator::make(['countrycode' => 'LOL', 'postcode' => '4321'], ['postcode' => new Postcode('countrycode')])->passes())->toBeFalse();
    expect(Validator::make(['countrycode' => 'NL', 'postcode' => 'AE 1011'], ['postcode' => new Postcode('countrycode')])->passes())->toBeFalse();
    expect(Validator::make(['postcode' => '01234'], ['postcode' => new Postcode])->passes())->toBeFalse();
});
