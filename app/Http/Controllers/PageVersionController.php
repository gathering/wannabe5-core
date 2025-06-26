<?php

namespace App\Http\Controllers;

use App\Http\Resources\PageVersionCollection;
use App\Http\Resources\PageVersionResource;
use App\Models\Page;
use App\Models\PageVersion;

class PageVersionController extends Controller
{
    /**
     * Get all page versions
     */
    /**
     * Retrieves a collection of page versions.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \App\Http\Resources\PageVersionCollection
     */
    public function index(Page $page)
    {
        // If a page ID is provided, filter the results to only include versions for that page
        if ($page) {
            // Use Eloquent's where method to retrieve all page versions with the specified page ID
            $page_versions = PageVersion::where('page_id', $page->id)->get();
        } else {
            // If no page ID is provided, retrieve all page versions
            $page_versions = PageVersion::all();
        }

        // Return a collection of page versions as a PageVersionCollection resource
        return new PageVersionCollection($page_versions);
    }

    /**
     * Display one page version
     */
    public function show(Page $page, string $version_number)
    {
        return new PageVersionResource(PageVersion::where('page_id', $page->id)->where('version_number', $version_number)->get());
    }
}
