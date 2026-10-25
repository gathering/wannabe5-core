<?php

namespace App\Http\Controllers;

use App\Models\CommunicationTemplate;
use Illuminate\Http\Request;

class CommunicationTemplateController extends Controller
{
    public function index()
    {
        return response()->json(CommunicationTemplate::query()->latest()->get());
    }

    public function show(int $id)
    {
        return response()->json(CommunicationTemplate::query()->findOrFail($id));
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string'],
            'title' => ['required', 'string'],
            'content' => ['nullable', 'string'],
            'schema' => ['nullable', 'array'],
            'source_type' => ['nullable', 'in:view,database'],
            'view_id' => ['nullable', 'string'],
        ]);

        return response()->json(CommunicationTemplate::create([
            ...$validated,
            'source_type' => $validated['source_type'] ?? 'database',
        ]), 201);
    }

    public function update(Request $request, int $id)
    {
        $template = CommunicationTemplate::query()->findOrFail($id);
        $template->update($request->only(['name', 'title', 'content', 'schema', 'source_type', 'view_id']));

        return response()->json($template->refresh());
    }

    public function destroy(int $id)
    {
        CommunicationTemplate::query()->findOrFail($id)->delete();

        return response()->json(true);
    }

    public function schema(int $id)
    {
        $template = CommunicationTemplate::query()->findOrFail($id);

        return response()->json([
            'id' => $template->id,
            'schema' => $template->schema ?? ['type' => 'object', 'properties' => []],
        ]);
    }

    public function preview(int $id)
    {
        $template = CommunicationTemplate::query()->findOrFail($id);

        return response('<html><body>'.e((string) $template->content).'</body></html>', 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }
}
