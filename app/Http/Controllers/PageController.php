<?php

namespace App\Http\Controllers;

use App\Http\Requests\PageRequest;
use App\Http\Resources\PageCollection;
use App\Http\Resources\PageResource;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PageController extends Controller
{
    /**
     * Retrieves all pages from the database and returns them as JSON.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        // Return the retrieved pages as a JSON response
        return new PageCollection(Page::all()->filter(fn ($page) => Gate::allows('view', $page))->values());
    }

    /**
     * Returns the page with the given ID.
     *
     * @param  App\Models\Page  $page
     * @return App\Http\Resources\PageResource
     */
    public function show(Page $page)
    {
        Gate::authorize('view', $page);

        return new PageResource($page);
    }

    /**
     * Create a new page in the database.
     *
     * @param  App\Http\Requests\PageRequest  $request;
     * @return App\Http\Resources\PageResource
     */
    public function store(PageRequest $request)
    {
        Gate::authorize('create', Page::class);
        $validatedData = $request->validated(); // Uses PageRequest validation

        // Create new page instance and fill it with validated data
        $page = new Page;
        $page->fill([...$validatedData, 'author_id' => auth()->id()]);
        $page->save();

        return new PageResource($page);
    }

    /**
     * Update a page with new data and saves it as a new version.
     *
     * @param  App\Models\Page  $page
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(PageRequest $request, Page $page)
    {
        Gate::authorize('update', $page);
        // Save current version
        $page->versions()->create([
            'title' => $page->title,
            'slug' => $page->slug,
            'content' => $page->content,
            'version_number' => $page->versions()->count() + 1,
            'edited_by' => auth()->id(), // The user ID of the current authenticated user
            'public' => $page->public,
        ]);

        // Update live page with new data from request

        $page->updateOrFail($request->safe()->only(['title', 'slug', 'content', 'public']));

        // Return updated page as JSON response
        return new PageResource($page);
    }

    /**
     * Deletes a page and its associated data from the database.
     *
     * @param  App\Models\Page  $page
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Page $page)
    {
        Gate::authorize('delete', $page);
        $page->delete();

        return response()->json(['message' => 'Page deleted successfully'], 200);
    }
}
