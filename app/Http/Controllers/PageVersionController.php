<?php

namespace App\Http\Controllers;

use App\Http\Resources\PageVersionCollection;
use App\Http\Resources\PageVersionResource;
use App\Models\Page;
use App\Models\PageVersion;
use Illuminate\Support\Facades\Gate;

class PageVersionController extends Controller
{
    public function index(Page $page)
    {
        Gate::authorize('update', $page);

        return new PageVersionCollection(
            PageVersion::where('page_id', $page->id)->get()
        );
    }

    public function show(Page $page, string $version_number)
    {
        Gate::authorize('update', $page);

        return new PageVersionResource(
            PageVersion::where('page_id', $page->id)
                ->where('version_number', $version_number)
                ->firstOrFail()
        );
    }
}
