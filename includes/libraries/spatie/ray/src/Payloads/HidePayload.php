<?php

namespace DLSpatie\Ray\Payloads;

class HidePayload extends Payload
{
    public function getType(): string
    {
        return 'hide';
    }
}
