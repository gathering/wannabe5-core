<?php

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Events\BackendRequestForwarded;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class BackendCompatClient
{
    public function forward(string $service, string $path, Request $request): Response
    {
        $baseUrl = config("backend_compat.services.{$service}");
        if (! is_string($baseUrl) || $baseUrl === '') {
            return response()->json([
                'message' => "Backend compatibility service '{$service}' is not configured.",
            ], 503);
        }

        $targetUrl = rtrim($baseUrl, '/').'/'.ltrim($path, '/');
        $headers = $this->forwardableHeaders($request);

        $pending = Http::withHeaders($headers)
            ->timeout((int) config('backend_compat.timeout_seconds', 20));

        [$pending, $options] = $this->buildForwardOptions($pending, $request);

        $upstream = $pending->send($request->method(), $targetUrl, $options);

        Event::dispatch(new BackendRequestForwarded(
            service: $service,
            method: $request->method(),
            path: ltrim($path, '/'),
            statusCode: $upstream->status(),
            query: $request->query(),
        ));

        return $this->toResponse($upstream);
    }

    protected function forwardableHeaders(Request $request): array
    {
        return Arr::where($request->headers->all(), function ($_, string $name) {
            return ! in_array(strtolower($name), [
                'host',
                'connection',
                'content-length',
            ], true);
        });
    }

    protected function toResponse(HttpResponse $upstream): Response
    {
        $response = response($upstream->body(), $upstream->status());

        foreach ($upstream->headers() as $header => $values) {
            if (in_array(strtolower($header), ['transfer-encoding', 'content-length', 'connection'], true)) {
                continue;
            }

            foreach ($values as $value) {
                $response->headers->set($header, $value, false);
            }
        }

        return $response;
    }

    protected function buildForwardOptions(\Illuminate\Http\Client\PendingRequest $pending, Request $request): array
    {
        $options = ['query' => $request->query()];

        if ($request->files->count() > 0 || $request->isMethod('post') && str_contains((string) $request->header('Content-Type'), 'multipart/form-data')) {
            $multipart = [];
            foreach ($request->request->all() as $key => $value) {
                $this->appendMultipartField($multipart, $key, $value);
            }
            foreach ($request->allFiles() as $key => $file) {
                $this->appendMultipartFile($multipart, $key, $file);
            }

            return [$pending, $options + ['multipart' => $multipart]];
        }

        if (str_contains((string) $request->header('Content-Type'), 'application/x-www-form-urlencoded')) {
            return [$pending, $options + ['form_params' => $request->request->all()]];
        }

        if ($request->getContent() !== '') {
            $options['body'] = $request->getContent();
        }

        return [$pending, $options];
    }

    protected function appendMultipartField(array &$multipart, string $name, mixed $value): void
    {
        if (is_array($value)) {
            foreach ($value as $nestedKey => $nestedValue) {
                $this->appendMultipartField($multipart, "{$name}[{$nestedKey}]", $nestedValue);
            }

            return;
        }

        $multipart[] = [
            'name' => $name,
            'contents' => is_bool($value) ? ($value ? '1' : '0') : (string) $value,
        ];
    }

    protected function appendMultipartFile(array &$multipart, string $name, UploadedFile|array $file): void
    {
        if (is_array($file)) {
            foreach ($file as $nestedKey => $nestedFile) {
                $this->appendMultipartFile($multipart, "{$name}[{$nestedKey}]", $nestedFile);
            }

            return;
        }

        $multipart[] = [
            'name' => $name,
            'contents' => fopen($file->getRealPath(), 'r'),
            'filename' => $file->getClientOriginalName(),
            'headers' => [
                'Content-Type' => $file->getMimeType() ?: 'application/octet-stream',
            ],
        ];
    }
}
