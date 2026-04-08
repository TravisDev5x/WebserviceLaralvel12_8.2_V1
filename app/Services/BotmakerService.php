<?php

declare(strict_types=1);

namespace App\Services;

final class BotmakerService
{
    /**
     * Normaliza payload entrante de Botmaker a un formato estable para el job.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function parseIncomingPayload(array $payload): array
    {
        $firstName = (string) data_get($payload, 'firstName', data_get($payload, 'contact.firstName', ''));
        $lastName = (string) data_get($payload, 'lastName', data_get($payload, 'contact.lastName', ''));
        $phone = (string) data_get($payload, 'whatsappNumber', data_get($payload, 'contact.phone', data_get($payload, 'phone', '')));
        $email = (string) data_get($payload, 'email', data_get($payload, 'contact.email', ''));
        $status = (string) data_get($payload, 'status', data_get($payload, 'contact.status', ''));
        $message = (string) data_get($payload, 'message.text', data_get($payload, 'message', ''));
        $event = (string) data_get($payload, 'event', data_get($payload, 'type', 'unknown'));

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $phone,
            'email' => $email,
            'status' => $status,
            'message' => $message,
            'event' => $event,
            'raw' => $payload,
        ];
    }
}
