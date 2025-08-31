<?php

declare(strict_types=1);

namespace Tipee\Hedwig;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tipee\Hedwig\Feature\Events;

class Client
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $url,
        private string $secret,
        private string $app = 'default',
        private string $tenant = 'default',
        private int $tokenExpiration = 15,
        private int $tokenLifetime = 60,
    ) {}

    /**
     * Generates a token that allows the bearer to subscribe to a channel.
     *
     * @param string $channel
     * @param Feature[]|null $features
     * @param array $override
     * @return string
     */
    public function makeSubscribeToken(string $channel, ?array $features = null, array $override = []): string
    {
        $token = json_encode([
            't' => $override['tenant'] ?? $this->tenant,
            'c' => $channel,
            'i' => time(),
            'e' => $override['expiration'] ?? $this->tokenExpiration,
            'l' => $override['lifetime'] ?? $this->tokenLifetime,
            'f' => $features ?? [new Events()],
        ]);

        $signature = hash_hmac('sha256', $token, $override['secret'] ?? $this->secret, binary: true);

        return $this->base64_encode($override['app'] ?? $this->app) .
            '.' . $this->base64_encode($token) .
            '.' . $this->base64_encode($signature);
    }

    public function publish(string $channel, mixed $payload, array $override = [])
    {
        $message = json_encode([
            'tenant' =>  $override['tenant'] ?? $this->tenant,
            'messages' => [
                [
                    'channels' => [$channel],
                    'payload' => $payload,
                ],
            ],
        ], flags: JSON_THROW_ON_ERROR);

        $signature = hash_hmac('sha256', $message, $override['secret'] ?? $this->secret, binary: true);

        $this->httpClient->request('POST', $override['url'] ?? $this->url . '/publish', [
            'headers' => [
                'Hedwig-App' => $override['app'] ?? $this->app,
                'Hedwig-Signature' => $this->base64_encode($signature),
                'Content-Type' => 'application/json',
            ],
            'body' => $message,
        ]);
    }

    private function base64_encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
