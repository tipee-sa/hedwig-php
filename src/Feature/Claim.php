<?php

declare(strict_types=1);

namespace Tipee\Hedwig\Feature;

class Claim implements Feature
{
    public function __construct(
        public string $user,
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'claim' => [
                'user' => $this->user,
            ],
        ];
    }
}
