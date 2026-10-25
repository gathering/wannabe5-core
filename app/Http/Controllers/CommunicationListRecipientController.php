<?php

namespace App\Http\Controllers;

use App\Models\CommunicationListRecipient;
use Illuminate\Http\Request;

class CommunicationListRecipientController extends Controller
{
    public function index()
    {
        return response()->json(CommunicationListRecipient::query()->latest()->get());
    }

    public function show(int $list_recipient_id)
    {
        return response()->json(CommunicationListRecipient::query()->findOrFail($list_recipient_id));
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'list_id' => ['required', 'integer', 'exists:communication_lists,id'],
            'recipient_id' => ['required', 'string'],
            'recipient_type' => ['required', 'in:user,crew,team,event'],
        ]);

        return response()->json(CommunicationListRecipient::create($validated), 201);
    }

    public function update(Request $request, int $list_recipient_id)
    {
        $recipient = CommunicationListRecipient::query()->findOrFail($list_recipient_id);
        $recipient->update($request->only(['list_id', 'recipient_id', 'recipient_type']));

        return response()->json($recipient->refresh());
    }

    public function destroy(int $list_recipient_id)
    {
        CommunicationListRecipient::query()->findOrFail($list_recipient_id)->delete();

        return response()->json(true);
    }
}
