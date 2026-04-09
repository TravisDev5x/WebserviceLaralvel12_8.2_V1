<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\AuthorizedToken;
use App\Models\Bitrix24Token;
use App\Services\Bitrix24AuthService;
use App\Services\Bitrix24ConnectorService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Throwable;

class ConnectorHealthStatus extends Component
{
    public function render(): View
    {
        $oauthOk = false;
        $oauthMessage = 'Sin token OAuth';
        $connectorOk = false;
        $connectorMessage = 'No verificado';
        $botmakerSendOk = false;
        $botmakerSendMessage = 'No verificado';

        try {
            $token = Bitrix24Token::getActive();

            if ($token instanceof Bitrix24Token) {
                $authService = app(Bitrix24AuthService::class);

                if ($authService->isTokenExpired($token)) {
                    $oauthMessage = 'Token expirado — se renovará automáticamente en la próxima llamada';
                } else {
                    $oauthOk = true;
                    $oauthMessage = "Válido hasta {$token->expires_at->format('Y-m-d H:i')} ({$token->domain})";
                }
            }
        } catch (Throwable $e) {
            $oauthMessage = "Error: {$e->getMessage()}";
        }

        try {
            if ($oauthOk) {
                $connector = app(Bitrix24ConnectorService::class);
                $lineId = (int) config_dynamic('bitrix24.line_id', config('services.bitrix24.line_id', 1));
                $status = $connector->checkStatus($lineId);

                if ($status['success']) {
                    $connectorOk = true;
                    $connectorMessage = 'Conector activo en línea ' . $lineId;
                } else {
                    $connectorMessage = 'Respuesta inesperada de imconnector.status';
                }
            }
        } catch (Throwable $e) {
            $connectorMessage = "Error: " . mb_substr($e->getMessage(), 0, 100);
        }

        try {
            $bmToken = AuthorizedToken::resolvedBotmakerApiToken();
            $bmUrl = AuthorizedToken::resolvedBotmakerApiUrl();

            if ($bmToken !== '' && $bmToken !== '__PENDIENTE__' && $bmUrl !== '') {
                $botmakerSendOk = true;
                $botmakerSendMessage = 'API token configurado';
            } else {
                $botmakerSendMessage = 'BOTMAKER_API_TOKEN pendiente de configurar';
            }
        } catch (Throwable $e) {
            $botmakerSendMessage = "Error: {$e->getMessage()}";
        }

        return view('livewire.connector-health-status', [
            'oauthOk' => $oauthOk,
            'oauthMessage' => $oauthMessage,
            'connectorOk' => $connectorOk,
            'connectorMessage' => $connectorMessage,
            'botmakerSendOk' => $botmakerSendOk,
            'botmakerSendMessage' => $botmakerSendMessage,
        ]);
    }
}
