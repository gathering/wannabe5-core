<?php

namespace App\Http\Requests;

use App\Enums\UserProfileGender;
use App\Rules\Countrycode;
use App\Rules\Postcode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nickname' => 'required|string|max:50|unique:user_profiles,nickname,'.$this->profile->id.',id',
            'birthdate' => [
                'required',
                Rule::date()->format('Y-m-d'),
                Rule::date()->before(today()->subYear()),
                Rule::date()->after(today()->subYears(120)),
            ],
            'gender' => [
                'required',
                Rule::enum(UserProfileGender::class),
            ],
            'phone' => 'required|phone',
            'address.streetaddress' => 'required|string',
            'address.town' => 'required|string',
            'address.postcode' => [
                'required',
                new Postcode('address.countrycode'),
            ],
            'address.countrycode' => [
                'required',
                new Countrycode,
            ],
        ];
    }
}
