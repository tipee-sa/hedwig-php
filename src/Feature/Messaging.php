<?php

declare(strict_types=1);

namespace Tipee\Hedwig\Feature;

class Messaging implements Feature
{
    public function jsonSerialize(): string
    {
        return 'messaging';
    }
}
