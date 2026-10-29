<?php

namespace App\Http\Controllers;

use App\Domain\Communication\Jobs\PrepareAndSendMessage;
use App\Models\CommunicationMessage;
use Illuminate\Http\Request;

class CommunicationMessageController extends Controller
{
    use \App\Http\Concerns\CommunicationAccess;

    public function index()
    {
        $this->authorizeCommunication('*', false);

        return response()->json(CommunicationMessage::query()->latest()->get());
    }

    public function show(int $id)
    {
        $this->authorizeCommunication((string) $id, false);

        return response()->json(CommunicationMessage::query()->findOrFail($id));
    }

    public function showMultiple(string $ids)
    {
        $this->authorizeCommunication('*', false);
        foreach (explode(',', $ids) as $messageId) {
            $this->authorizeCommunication((string) (int) $messageId);
        }

        $idList = array_filter(array_map('intval', explode(',', $ids)));

        return response()->json(CommunicationMessage::query()->whereIn('id', $idList)->get());
    }

    public function create(Request $request)
    {
        $this->authorizeCommunication('*', true);

        $validated = $request->validate([
            'channel' => ['required', 'in:sms,mail,slack'],
            'notification_name' => ['nullable', 'string'],
            'recipient' => ['nullable', 'array'],
            'data' => ['nullable', 'array'],
            'source' => ['nullable', 'array'],
            'state' => ['nullable', 'string'],
        ]);

        $message = CommunicationMessage::create([
            ...$validated,
            'state' => 'raw',
            'tries' => 0,
        ]);

        PrepareAndSendMessage::dispatch($message->id);

        return response()->json($message, 201);
    }

    public function batchCreateAndSend(Request $request)
    {
        $this->authorizeCommunication('*', true);

        $validated = $request->validate([
            'channel' => ['required', 'in:sms,mail,slack'],
            'notification_name' => ['nullable', 'string'],
            'data' => ['nullable', 'array'],
            'source' => ['nullable', 'array'],
            'recipients' => ['required', 'array', 'min:1'],
            'recipients.*.id' => ['required'],
            'recipients.*.type' => ['required', 'string'],
        ]);

        $messages = [];
        foreach ($validated['recipients'] as $recipient) {
            $message = CommunicationMessage::create([
                'channel' => $validated['channel'],
                'notification_name' => $validated['notification_name'] ?? null,
                'data' => $validated['data'] ?? [],
                'source' => $validated['source'] ?? [],
                'recipient' => $recipient,
                'state' => 'raw',
                'tries' => 0,
            ]);
            PrepareAndSendMessage::dispatch($message->id);
            $messages[] = $message;
        }

        return response()->json(['queued' => count($messages), 'messages' => $messages], 201);
    }

    public function update(Request $request, int $id)
    {
        $this->authorizeCommunication((string) $id, true);

        $message = CommunicationMessage::query()->findOrFail($id);
        $message->update($request->only([
            'channel',
            'notification_name',
            'recipient',
            'data',
            'source',
        ]));

        return response()->json($message->refresh());
    }

    public function destroy(int $id)
    {
        $this->authorizeCommunication((string) $id, true);

        CommunicationMessage::query()->findOrFail($id)->delete();

        return response()->json(true);
    }

    public function retry(int $id)
    {
        $this->authorizeCommunication((string) $id, true);

        $message = CommunicationMessage::query()->findOrFail($id);
        $message->update([
            'state' => 'raw',
            'last_error' => null,
        ]);
        PrepareAndSendMessage::dispatch($message->id);

        return response()->json($message->refresh());
    }

    public function schema(int $id)
    {
        $this->authorizeCommunication((string) $id, false);

        $message = CommunicationMessage::query()->findOrFail($id);

        return response()->json([
            'id' => $message->id,
            'schema' => $message->data['schema'] ?? ['type' => 'object', 'properties' => []],
        ]);
    }

    public function preview(int $id)
    {
        $this->authorizeCommunication((string) $id, false);

        $message = CommunicationMessage::query()->findOrFail($id);
        $body = is_array($message->data) ? ($message->data['content'] ?? json_encode($message->data)) : (string) $message->data;

        return response('<html><body>'.e((string) $body).'</body></html>', 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }
}
