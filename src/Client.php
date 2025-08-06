<?php

declare(strict_types=1);

namespace Tipee\Hedwig;

use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Client
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $url,
        private string $secret,
        private string $tenant = 'default',
        private int $tokenExpiration = 15,
        private int $tokenLifetime = 60,
        private ?UserProviderInterface $userProvider = null,
    ) {
    }

    /**
     * Generates a token that allows the bearer to subscribe to a channel.
     *
     * @param string $channel
     * @param array $opts
     * @return string
     */
    public function makeSubscribeToken(string $channel, array $override = []): string
    {
        $token = json_encode([
            't' => $override['tenant'] ?? $this->tenant,
            'u' => $override['user'] ?? $this->userProvider?->getUser() ?? throw new RuntimeException(
                'A user identifier is required to generate a subscription token.'
            ),
            'c' => $channel,
            'i' => time(),
            'e' => $override['expiration'] ?? $this->tokenExpiration,
            'l' => $override['lifetime'] ?? $this->tokenLifetime,
        ]);

        $signature = hash_hmac('sha256', $token, $override['secret'] ?? $this->secret, binary: true);

        return $this->base64_encode($token) . '.' . $this->base64_encode($signature);
    }

    public function publish(string $channel, mixed $payload, array $override = [])
    {
        $message = json_encode([
            't' => $override['tenant'] ?? $this->tenant,
            'c' => $channel,
            'p' => $payload,
        ], flags: JSON_THROW_ON_ERROR);

        $signature = hash_hmac('sha256', $message, $override['secret'] ?? $this->secret, binary: true);

        $this->httpClient->request('POST', $override['url'] ?? $this->url . '/publish', [
            'headers' => [
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
