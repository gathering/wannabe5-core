<?php

namespace App\Http\Controllers;

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
        $pages = Page::all();

        // Return the retrieved pages as a JSON response
        return response()->json($pages);
    }

    /**
     * Returns the page with the given ID.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $page = Page::find($id);

        if (!$page) {
            return response()->json(['error' => 'Page not found'], 404);
        }

        // Return the retrieved page as a JSON response
        return response()->json($page);
    }


    /**
     * Validate and store a new page in the database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {

        // Validate incoming request data
        $validatedData = $request->validate([
            'title' => 'required', // Page title is required
            'content' => 'required', // Page content is required
            #'event_id' => 'required|integer', // Event ID is required and must be an integer
            'author_id' => 'uuid|required', // User ID is required and must be a UUID
            'slug' => 'unique:posts|required|alpha_dash:ascii', // Slug is required and must contain only ASCII characters and underscores/dashes
        ]);

        // Create new page instance and fill it with validated data
        $page = new Page();
        $page->fill($validatedData);
        $page->save();

        // Return JSON response indicating successful creation of page
        return response()->json(['message' => 'Page created successfully'], 201);
    }

    /**
     * Update a page with new data and saves it as a new version.
     * @param  \Illuminate\Http\Request  $request
     * @param  App\Models\Page  $page
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Page $page)
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
        $page->update($request->only(['title', 'slug', 'content']));

        // Return updated page as JSON response
        return response()->json($page);
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
