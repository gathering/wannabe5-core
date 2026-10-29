<?php

namespace App\Http\Controllers;

use App\Domain\Shared\Services\ServiceAccessAttributeRegistry;

class InternalAccessController extends Controller
{
    public function __construct(
        protected ServiceAccessAttributeRegistry $registry,
    ) {}

    public function access(string $service)
    {
        return response()->json($this->registry->forService($service));
    }

    public function metrics()
    {
        return app(MetricsController::class)->index();
    }

    public function liveness()
    {
        return response('ok', 200);
    }
}
