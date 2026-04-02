<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\IntegrationProbeService;
use Illuminate\Http\JsonResponse;

/**
 * Endpoints JSON para pruebas (misma sesión que el panel; enviar X-XSRF-TOKEN en POST).
 */
class IntegrationProbeController extends Controller
{
    public function bitrixSample(IntegrationProbeService $probe): JsonResponse
    {
        return response()->json($probe->runBitrixSampleLead('api_http'));
    }

    public function connectivity(IntegrationProbeService $probe): JsonResponse
    {
        return response()->json([
            'botmaker' => $probe->probeBotmakerApi(),
            'bitrix' => $probe->probeBitrixApi(),
            'queue' => $probe->probeQueueStuck(),
            'summary' => $probe->webhookSummaryToday(),
        ]);
    }
}
