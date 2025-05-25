<?php

namespace App\Http\Controllers;

class IndexController extends Controller
{
    public function index()
    {
        return response()->json([
            'name' => config('app.name'),
            'env' => config('app.env'),
            'debug' => config('app.debug'),
            'url' => config('app.url'),
            'timezone' => config('app.timezone'),
        ]);
    }
}
