<?php

declare(strict_types=1);

namespace Tipee\Hedwig;

use Closure;

class PublishBatch
{
    /**
     * @var array<array{channels: array<string>, payload: mixed}>
     */
    private array $events = [];

    public function __construct(
        private Closure $send,
    ) {}

    /**
     * @param string|array<string> $channels
     */
    public function add(string|array $channels, mixed $payload = null): self
    {
        $this->events[] = [
            'channels' => is_array($channels) ? $channels : [$channels],
            'payload' => $payload,
        ];

        return $this;
    }

    public function send(): void
    {
        ($this->send)($this->events);
        $this->events = [];
    }
}
