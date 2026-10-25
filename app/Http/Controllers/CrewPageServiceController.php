<?php

namespace App\Http\Controllers;

use App\Models\CrewEvent;
use App\Models\CrewPage;
use Illuminate\Http\Request;

class CrewPageServiceController extends Controller
{
    /**
     * TipTap/ProseMirror empty document — required by wannabe5-frontend RenderPage.
     *
     * @return array{type: string, content: array<int, mixed>}
     */
    private function emptyTiptapDoc(): array
    {
        return [
            'type' => 'doc',
            'content' => [],
        ];
    }

    public function publicFrontpage()
    {
        // Prefer an active+visible event frontpage (legacy crew behavior), then event_id=0 fallback.
        $event = CrewEvent::query()
            ->where('active', true)
            ->where('visible', true)
            ->latest('id')
            ->first();

        $page = null;
        if ($event !== null) {
            $page = CrewPage::query()
                ->where('event_id', $event->id)
                ->where('type', 'public_frontpage')
                ->first();
        }
        $page ??= CrewPage::query()->where('type', 'public_frontpage')->where('event_id', 0)->first();

        if ($page === null) {
            return response()->json([
                'type' => 'public_frontpage',
                'event_id' => $event?->id ?? 0,
                'content' => $this->emptyTiptapDoc(),
            ]);
        }

        $payload = $page->toArray();
        if (! is_array($payload['content'] ?? null) || ($payload['content']['type'] ?? null) !== 'doc') {
            $payload['content'] = $this->emptyTiptapDoc();
        }

        return response()->json($payload);
    }

    public function index(int $event_id)
    {
        return response()->json(CrewPage::query()->where('event_id', $event_id)->get());
    }

    public function show(int $event_id, int $page_id)
    {
        return response()->json(
            CrewPage::query()->where('event_id', $event_id)->findOrFail($page_id)
        );
    }

    public function update(Request $request, int $event_id, int $page_id)
    {
        $page = CrewPage::query()->where('event_id', $event_id)->findOrFail($page_id);
        $page->update($request->only(['content', 'type']));

        return response()->json($page->refresh());
    }

    public function eventPage(int $event_id, string $page_type)
    {
        $page = CrewPage::query()->where('event_id', $event_id)->where('type', $page_type)->first();

        if ($page === null) {
            return response()->json([
                'type' => $page_type,
                'event_id' => $event_id,
                'content' => $this->emptyTiptapDoc(),
            ]);
        }

        $payload = $page->toArray();
        if (! is_array($payload['content'] ?? null) || ($payload['content']['type'] ?? null) !== 'doc') {
            $payload['content'] = $this->emptyTiptapDoc();
        }

        return response()->json($payload);
    }
}
