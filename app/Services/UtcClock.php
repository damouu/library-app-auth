<?php

namespace App\Services;

use App\Contracts\Clock;
use DateTimeImmutable;
use DateTimeZone;
use Exception;

class UtcClock implements Clock
{
    /**
     * @throws Exception
     */
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
