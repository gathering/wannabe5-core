<?php

namespace App\Http\Controllers;

use App\Models\CommunicationListRecipient;
use Illuminate\Http\Request;

class CommunicationListRecipientController extends Controller
{
    use \App\Http\Concerns\CommunicationAccess;

    public function index()
    {
        $this->authorizeList('*', false);

        return response()->json(CommunicationListRecipient::query()->latest()->get());
    }

    public function show(int $list_recipient_id)
    {
        $this->authorizeList((string) CommunicationListRecipient::query()->findOrFail($list_recipient_id)->list_id, false);

        return response()->json(CommunicationListRecipient::query()->findOrFail($list_recipient_id));
    }

    public function create(Request $request)
    {
        $this->authorizeList((string) $request->input('list_id'), true);

        $validated = $request->validate([
            'list_id' => ['required', 'integer', 'exists:communication_lists,id'],
            'recipient_id' => ['required', 'string'],
            'recipient_type' => ['required', 'in:user,crew,team,event'],
        ]);

        return response()->json(CommunicationListRecipient::create($validated), 201);
    }

    public function update(Request $request, int $list_recipient_id)
    {
        $this->authorizeList((string) CommunicationListRecipient::query()->findOrFail($list_recipient_id)->list_id, true);
        if ($request->has('list_id')) {
            $this->authorizeList((string) $request->input('list_id'), true);
        }

        $recipient = CommunicationListRecipient::query()->findOrFail($list_recipient_id);
        $recipient->update($request->only(['list_id', 'recipient_id', 'recipient_type']));

        return response()->json($recipient->refresh());
    }

    public function destroy(int $list_recipient_id)
    {
        $this->authorizeList((string) CommunicationListRecipient::query()->findOrFail($list_recipient_id)->list_id, true);

        CommunicationListRecipient::query()->findOrFail($list_recipient_id)->delete();

        return response()->json(true);
    }
}
