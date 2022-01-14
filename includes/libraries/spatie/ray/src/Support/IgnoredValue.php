<?php

namespace DLSpatie\Ray\Support;

class IgnoredValue
{
    public static function make(): self
    {
        return new static();
    }
}
