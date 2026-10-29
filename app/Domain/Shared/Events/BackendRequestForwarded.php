<?php

namespace App\Domain\Shared\Events;

class BackendRequestForwarded
{
    public function __construct(
        public string $service,
        public string $method,
        public string $path,
        public int $statusCode,
        public array $query = [],
    ) {}
}
