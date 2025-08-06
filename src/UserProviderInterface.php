<?php

declare(strict_types=1);

namespace Tipee\Hedwig;

interface UserProviderInterface
{
    public function getUser(): string;
}
