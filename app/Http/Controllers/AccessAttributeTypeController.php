<?php

namespace App\Http\Controllers;

use App\Models\AccessAttributeType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccessAttributeTypeController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(AccessAttributeType::query()->paginate((int) $request->integer('per_page', 10)));
    }

    public function show(AccessAttributeType $attribute_type_id)
    {
        return response()->json($attribute_type_id);
    }

    public function attributes(AccessAttributeType $attribute_type_id, Request $request)
    {
        return response()->json($attribute_type_id->attributes()->paginate((int) $request->integer('per_page', 10)));
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'key' => ['required', 'regex:/^[a-z:]+$/', 'min:4', Rule::unique('access_attribute_types', 'key')],
            'source' => ['required', 'regex:/^[a-z0-9-]+$/'],
            'summary' => ['required', 'min:4'],
            'description' => ['nullable', 'string'],
        ]);

        $type = AccessAttributeType::create($validated);

        return response()->json($type, 201);
    }

    public function update(Request $request, AccessAttributeType $attribute_type_id)
    {
        $validated = $request->validate([
            'key' => ['required', 'regex:/^[a-z:]+$/', 'min:4', Rule::unique('access_attribute_types', 'key')->ignore($attribute_type_id->id)],
            'source' => ['required', 'regex:/^[a-z0-9-]+$/'],
            'summary' => ['required', 'min:4'],
            'description' => ['nullable', 'string'],
        ]);

        $attribute_type_id->update($validated);

        return response()->json($attribute_type_id->refresh());
    }

    public function destroy(AccessAttributeType $attribute_type_id)
    {
        $attribute_type_id->delete();

        return response()->noContent();
    }
}
