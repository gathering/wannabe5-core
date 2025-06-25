<?php

namespace App\Http\Controllers;

use App\Http\Requests\PageRequest;
use App\Http\Resources\PageCollection;
use App\Http\Resources\PageResource;
use App\Models\Page;
use Illuminate\Http\Request;

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
        return new PageCollection(Page::all());
    }

    /**
     * Returns the page with the given ID.
     *
     * @param  App\Models\Page  $page
     * @return App\Http\Resources\PageResource
     */
    public function show(Page $page)
    {
        return new PageResource($page);
    }

    /**
     * Validate and store a new page in the database.
     *
     * @param  App\Http\Requests\PageRequest  $request;
     * @return App\Http\Resources\PageResource
     */
    public function store(PageRequest $request)
    {
        $validatedData = $request->validated(); // Uses PageRequest validation

        // Create new page instance and fill it with validated data
        $page = new Page;
        $page->fill($validatedData);
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
        // Save current version
        $page->versions()->create([
            'title' => $page->title,
            'slug' => $page->slug,
            'content' => $page->content,
            'version_number' => $page->versions()->count() + 1,
            'edited_by' => auth()->id(), // The user ID of the current authenticated user
        ]);

        // Update live page with new data from request

        $page->updateOrFail($request->safe()->only(['title', 'slug', 'content']));

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
        $page->delete();

        return response()->json(['message' => 'Page deleted successfully'], 200);
    }
}
