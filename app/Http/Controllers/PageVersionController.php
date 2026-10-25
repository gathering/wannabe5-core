<?php

namespace App\Http\Controllers;

use App\Http\Resources\PageVersionCollection;
use App\Http\Resources\PageVersionResource;
use App\Models\Page;
use App\Models\PageVersion;

class PageVersionController extends Controller
{
    public function index(Page $page)
    {
        return new PageVersionCollection(
            PageVersion::where('page_id', $page->id)->get()
        );
    }

    public function show(Page $page, string $version_number)
    {
        return new PageVersionResource(
            PageVersion::where('page_id', $page->id)
                ->where('version_number', $version_number)
                ->firstOrFail()
        );
    }
}
