<?php

namespace App\Domain\Shared\Http\Controllers;

use App\Domain\Shared\Services\BackendCompatClient;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BackendCompatController extends Controller
{
    public function __construct(
        protected BackendCompatClient $client,
    ) {}

    public function forward(Request $request, string $service, string $path = ''): Response
    {
        return $this->client->forward($service, $path, $request);
    }

    public function forwardTemplate(Request $request, string $service, string $upstreamPathTemplate = ''): Response
    {
        $path = $upstreamPathTemplate;
        foreach ($request->route()?->parameters() ?? [] as $name => $value) {
            if (! is_scalar($value)) {
                continue;
            }
            $path = str_replace('{'.$name.'}', (string) $value, $path);
        }

        return $this->client->forward($service, $path, $request);
    }
}
