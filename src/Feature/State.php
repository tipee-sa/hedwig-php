<?php

declare(strict_types=1);

namespace Tipee\Hedwig\Feature;

class State implements Feature
{
    public function jsonSerialize(): string
    {
        return 'state';
    }
}
