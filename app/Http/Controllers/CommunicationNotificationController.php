<?php

namespace App\Http\Controllers;

use App\Models\CommunicationNotification;
use App\Models\CommunicationTemplate;
use Illuminate\Http\Request;

class CommunicationNotificationController extends Controller
{
    use \App\Http\Concerns\CommunicationAccess;

    public function index()
    {
        $this->authorizeCommunication('*', false);

        return response()->json(CommunicationNotification::query()->latest()->get());
    }

    public function show(int $id)
    {
        $this->authorizeCommunication((string) $id, false);

        return response()->json(CommunicationNotification::query()->findOrFail($id));
    }

    public function create(Request $request)
    {
        $this->authorizeCommunication('*', true);

        $validated = $request->validate([
            'name' => ['required', 'string', 'unique:communication_notifications,name'],
            'mail_template_id' => ['nullable', 'integer', 'exists:communication_templates,id'],
            'sms_template_id' => ['nullable', 'integer', 'exists:communication_templates,id'],
            'slack_template_id' => ['nullable', 'integer', 'exists:communication_templates,id'],
        ]);

        return response()->json(CommunicationNotification::create($validated), 201);
    }

    public function update(Request $request, int $id)
    {
        $this->authorizeCommunication((string) $id, true);

        $notification = CommunicationNotification::query()->findOrFail($id);
        $notification->update($request->only([
            'name',
            'mail_template_id',
            'sms_template_id',
            'slack_template_id',
        ]));

        return response()->json($notification->refresh());
    }

    public function destroy(int $id)
    {
        $this->authorizeCommunication((string) $id, true);

        CommunicationNotification::query()->findOrFail($id)->delete();

        return response()->json(true);
    }

    public function schema(int $id)
    {
        $this->authorizeCommunication((string) $id, false);

        $notification = CommunicationNotification::query()->findOrFail($id);
        $templateId = $notification->mail_template_id ?? $notification->sms_template_id ?? $notification->slack_template_id;
        $schema = [];
        if ($templateId !== null) {
            $schema = CommunicationTemplate::query()->find($templateId)?->schema ?? [];
        }

        return response()->json([
            'id' => $notification->id,
            'name' => $notification->name,
            'schema' => $schema,
        ]);
    }
}
