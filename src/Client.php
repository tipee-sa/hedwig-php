<?php

declare(strict_types=1);

namespace Tipee\Hedwig;

use InvalidArgumentException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

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
     * @param array<Feature> $features
     * @param array $override
     * @return string
     */
    public function makeSubscribeToken(string $channel, array $features, array $override = []): string
    {
        if (empty($features)) {
            throw new InvalidArgumentException('At least one feature is required');
        }

        $token = json_encode([
            't' => $override['tenant'] ?? $this->tenant,
            'c' => $channel,
            'i' => time(),
            'e' => $override['expiration'] ?? $this->tokenExpiration,
            'l' => $override['lifetime'] ?? $this->tokenLifetime,
            'f' => $features,
        ]);

        $signature = hash_hmac('sha256', $token, $override['secret'] ?? $this->secret, binary: true);

        return $this->base64_encode($override['app'] ?? $this->app) .
            '.' . $this->base64_encode($token) .
            '.' . $this->base64_encode($signature);
    }

    /**
     * @param string|array<string> $channels
     */
    public function publish(string|array $channels, mixed $payload = null, array $override = [])
    {
        $this->publishBatch($override)->add($channels, $payload)->send();
    }

    public function publishBatch(array $override = []): PublishBatch
    {
        return new PublishBatch(function ($events) use ($override) {
            $command = json_encode([
                'tenant' =>  $override['tenant'] ?? $this->tenant,
                'events' => $events,
            ], flags: JSON_THROW_ON_ERROR);

            $signature = hash_hmac('sha256', $command, $override['secret'] ?? $this->secret, binary: true);

            $this->httpClient->request('POST', $override['url'] ?? $this->url . '/publish', [
                'headers' => [
                    'Hedwig-App' => $override['app'] ?? $this->app,
                    'Hedwig-Signature' => $this->base64_encode($signature),
                    'Content-Type' => 'application/json',
                ],
                'body' => $command,
            ]);
        });
    }

    private function base64_encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
