<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AccessUserController extends Controller
{
    public function currentUserAttributesCheck(Request $request)
    {
        $user = $request->user();
        abort_if($user === null, 401);

        return response()->json($this->serializeUserWithAttributes($user, $request->boolean('pretty')));
    }

    public function userAttributesCheck(string $user_id, Request $request)
    {
        $user = User::query()->findOrFail($user_id);

        return response()->json($this->serializeUserWithAttributes($user, $request->boolean('pretty')));
    }

    public function userAttributes(string $user_id)
    {
        $user = User::query()->findOrFail($user_id);

        return response()->json($user->accessAttributes()->with('description')->get());
    }

    public function currentUserHasAttribute(string $attribute, string $value, Request $request)
    {
        $user = $request->user();
        abort_if($user === null, 401);

        return response()->json($this->userHasAttribute($user, $attribute, $value));
    }

    public function hasAttribute(string $user_id, string $attribute, string $value)
    {
        $user = User::query()->findOrFail($user_id);

        return response()->json($this->userHasAttribute($user, $attribute, $value));
    }

    private function userHasAttribute(User $user, string $attribute, string $value): bool
    {
        $values = $user->accessAttributes()->where('key', $attribute)->pluck('value');

        foreach ($values as $valueSet) {
            if (! is_array($valueSet)) {
                continue;
            }
            if (in_array('*', $valueSet, true) || in_array($value, $valueSet, true)) {
                return true;
            }
        }

        return false;
    }

    private function serializeUserWithAttributes(User $user, bool $pretty): array
    {
        $rows = $user->accessAttributes()->with('description')->get();

        $attributes = [];
        $descriptions = [];
        foreach ($rows as $attribute) {
            $value = is_array($attribute->value) ? $attribute->value : [$attribute->value];
            $existing = $attributes[$attribute->key] ?? [];
            $merged = array_values(array_unique(array_merge($existing, $value)));
            $attributes[$attribute->key] = in_array('*', $merged, true) ? ['*'] : $merged;

            if ($attribute->description !== null) {
                $descriptions[$attribute->key] = $attribute->description;
            }
        }

        if (! $pretty) {
            return [
                'id' => $user->id,
                'username' => $user->username,
                'attributes' => $attributes,
            ];
        }

        // Legacy access User::pretty() shape consumed by frontend /debug/access.
        $attr = [];
        foreach ($attributes as $key => $value) {
            $attr[$key] = [
                'value' => $value,
                'description' => $descriptions[$key] ?? '',
            ];
        }

        return [
            'sub' => $user->id,
            'attr' => $attr,
            'partials' => [],
        ];
    }
}
