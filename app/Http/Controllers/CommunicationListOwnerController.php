<?php

namespace App\Http\Controllers;

use App\Models\CommunicationListOwner;
use Illuminate\Http\Request;

class CommunicationListOwnerController extends Controller
{
    public function index()
    {
        return response()->json(CommunicationListOwner::query()->latest()->get());
    }

    public function show(int $list_owner_id)
    {
        return response()->json(CommunicationListOwner::query()->findOrFail($list_owner_id));
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'list_id' => ['required', 'integer', 'exists:communication_lists,id'],
            'owner_id' => ['required', 'string'],
            'owner_type' => ['required', 'in:user,crew,team,event'],
        ]);

        return response()->json(CommunicationListOwner::create($validated), 201);
    }

    public function update(Request $request, int $list_owner_id)
    {
        $owner = CommunicationListOwner::query()->findOrFail($list_owner_id);
        $owner->update($request->only(['list_id', 'owner_id', 'owner_type']));

        return response()->json($owner->refresh());
    }

    public function destroy(int $list_owner_id)
    {
        CommunicationListOwner::query()->findOrFail($list_owner_id)->delete();

        return response()->json(true);
    }
}
