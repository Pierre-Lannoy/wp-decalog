<?php

namespace DLSpatie\Ray\Support;

use DateTimeImmutable;

interface Clock
{
    public function now(): DateTimeImmutable;
}
