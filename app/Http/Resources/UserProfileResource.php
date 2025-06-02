<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            /**
             * @example 1
             */
            'id' => $this->whenHas('id'),
            /**
             * @example 12345678-1234-5678-1234-567812345678
             */
            'user_id' => $this->whenHas('user_id'),
            'firstname' => $this->whenHas('firstname'),
            'lastname' => $this->lastname,
            'email' => $this->email,
            'nickname' => $this->nickname,
            /**
             * @example 2019-08-24
             */
            'birthdate' => $this->birthdate,
            'gender' => $this->gender,
            'phone' => $this->phone,
            'address' => [
                'streetaddress' => $this->streetaddress,
                'town' => $this->town,
                'postcode' => $this->postcode,
                'countrycode' => $this->countrycode,
            ],
            'crew_history' => UserCrewHistoryResource::collection($this->whenLoaded('userCrewHistory')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
