<?php

namespace App\Http\Controllers;

use App\Models\CommunicationList;
use Illuminate\Http\Request;

class CommunicationListController extends Controller
{
    use \App\Http\Concerns\CommunicationAccess;

    public function index()
    {
        $this->authorizeList('*', false);

        return response()->json(CommunicationList::query()->latest()->get());
    }

    public function show(int $list_id)
    {
        $this->authorizeList((string) $list_id, false);

        return response()->json(CommunicationList::query()->findOrFail($list_id));
    }

    public function create(Request $request)
    {
        $this->authorizeList('*', true);

        $validated = $request->validate([
            'identifier' => ['required', 'string'],
            'type' => ['required', 'in:sms,slack,mail'],
            'summary' => ['required', 'string'],
            'event_id' => ['nullable', 'integer'],
        ]);

        return response()->json(CommunicationList::create([
            ...$validated,
            'event_id' => $validated['event_id'] ?? 1,
        ]), 201);
    }

    public function update(Request $request, int $list_id)
    {
        $this->authorizeList((string) $list_id, true);

        $list = CommunicationList::query()->findOrFail($list_id);
        $list->update($request->only(['identifier', 'type', 'summary', 'event_id']));

        return response()->json($list->refresh());
    }

    public function destroy(int $list_id)
    {
        $this->authorizeList((string) $list_id, true);

        CommunicationList::query()->findOrFail($list_id)->delete();

        return response()->json(true);
    }

    public function showRecipients(int $list_id)
    {
        $this->authorizeList((string) $list_id, false);

        $list = CommunicationList::query()->findOrFail($list_id);

        return response()->json($list->recipients()->get());
    }

    public function showOwners(int $list_id)
    {
        $this->authorizeList((string) $list_id, false);

        $list = CommunicationList::query()->findOrFail($list_id);

        return response()->json($list->owners()->get());
    }
}
