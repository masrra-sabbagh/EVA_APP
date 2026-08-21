<?php

namespace App\Services;

use App\Exceptions\WhatsappServiceException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappService {
    protected string $instanceId;
    protected string $token;
    protected string $baseUrl;

    public function __construct() {
        $this->instanceId = (string) config('services.ultramsg.instance_id');
        $this->token      = (string) config('services.ultramsg.token');
        $this->baseUrl    = (string) config('services.ultramsg.url');
    }
    public function sendOtp(string $to, string $message): array {
        $endpoint = "{$this->baseUrl}/{$this->instanceId}/messages/chat";

        try {
            $response = Http::timeout(10)
                ->retry(2, 200)
                ->post($endpoint, [
                    'token' => $this->token,
                    'to'    => $to,
                    'body'  => $message,
                ]);
        } catch (ConnectionException $e) {
            Log::error('WhatsApp OTP send failed: connection error', [
                'to'    => $to,
                'error' => $e->getMessage(),
            ]);

            throw new WhatsappServiceException('Unable to reach WhatsApp service.', previous: $e);
        }

        if ($response->failed()) {
            Log::error('WhatsApp OTP send failed: bad response', [
                'to'     => $to,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            throw new WhatsappServiceException(
                "WhatsApp service returned an error (status {$response->status()})."
            );
        }

        Log::info('WhatsApp OTP sent successfully', [
            'to' => $to,
        ]);

        return $response->json() ?? [];
    }
}
