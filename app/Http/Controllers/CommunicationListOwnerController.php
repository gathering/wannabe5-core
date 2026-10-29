<?php

namespace App\Http\Controllers;

use App\Models\CommunicationListOwner;
use Illuminate\Http\Request;

class CommunicationListOwnerController extends Controller
{
    use \App\Http\Concerns\CommunicationAccess;

    public function index()
    {
        $this->authorizeList('*', false);

        return response()->json(CommunicationListOwner::query()->latest()->get());
    }

    public function show(int $list_owner_id)
    {
        $this->authorizeList((string) CommunicationListOwner::query()->findOrFail($list_owner_id)->list_id, false);

        return response()->json(CommunicationListOwner::query()->findOrFail($list_owner_id));
    }

    public function create(Request $request)
    {
        $this->authorizeList((string) $request->input('list_id'), true);

        $validated = $request->validate([
            'list_id' => ['required', 'integer', 'exists:communication_lists,id'],
            'owner_id' => ['required', 'string'],
            'owner_type' => ['required', 'in:user,crew,team,event'],
        ]);

        return response()->json(CommunicationListOwner::create($validated), 201);
    }

    public function update(Request $request, int $list_owner_id)
    {
        $this->authorizeList((string) CommunicationListOwner::query()->findOrFail($list_owner_id)->list_id, true);
        if ($request->has('list_id')) {
            $this->authorizeList((string) $request->input('list_id'), true);
        }

        $owner = CommunicationListOwner::query()->findOrFail($list_owner_id);
        $owner->update($request->only(['list_id', 'owner_id', 'owner_type']));

        return response()->json($owner->refresh());
    }

    public function destroy(int $list_owner_id)
    {
        $this->authorizeList((string) CommunicationListOwner::query()->findOrFail($list_owner_id)->list_id, true);

        CommunicationListOwner::query()->findOrFail($list_owner_id)->delete();

        return response()->json(true);
    }
}
