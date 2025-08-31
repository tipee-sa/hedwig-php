<?php

declare(strict_types=1);

namespace Tipee\Hedwig\Feature;

class Events implements Feature
{
    public function jsonSerialize(): string
    {
        return 'events';
    }
}
