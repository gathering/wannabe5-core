<?php

namespace App\Http\Controllers;

use App\Models\AccessAttribute;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccessAttributeController extends Controller
{
    public function index(Request $request)
    {
        $query = AccessAttribute::query();

        if ($request->boolean('pretty')) {
            $query->with('description');
        }

        if ($request->filled('key')) {
            $query->where('key', (string) $request->string('key'));
        }

        if ($request->filled('value')) {
            $query->whereJsonContains('value', (string) $request->string('value'));
        }

        return response()->json($query->paginate((int) $request->integer('per_page', 10)));
    }

    public function show(AccessAttribute $attribute_id)
    {
        return response()->json($attribute_id);
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'source' => ['required', 'regex:/^[a-z0-9-]+$/'],
            'user_id' => ['required', 'uuid'],
            'key' => [
                'required',
                'regex:/^[a-z:]+$/',
                'min:4',
                Rule::unique('access_attributes')->where(fn ($query) => $query
                    ->where('user_id', $request->input('user_id'))
                    ->where('source', $request->input('source'))),
            ],
            'value' => ['required', 'array'],
        ]);

        $attribute = AccessAttribute::create($validated);

        return response()->json($attribute, 201);
    }

    public function update(Request $request, AccessAttribute $attribute_id)
    {
        $validated = $request->validate([
            'source' => ['required', 'regex:/^[a-z0-9-]+$/'],
            'user_id' => ['required', 'uuid'],
            'key' => [
                'required',
                'regex:/^[a-z:]+$/',
                'min:4',
                Rule::unique('access_attributes')->ignore($attribute_id->id)->where(fn ($query) => $query
                    ->where('user_id', $request->input('user_id'))
                    ->where('source', $request->input('source'))),
            ],
            'value' => ['required', 'array'],
        ]);

        $attribute_id->update($validated);

        return response()->json($attribute_id->refresh());
    }

    public function destroy(AccessAttribute $attribute_id)
    {
        $attribute_id->delete();

        return response()->noContent();
    }
}
