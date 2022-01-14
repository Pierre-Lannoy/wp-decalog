<?php

namespace DLSpatie\Ray\Payloads;

class RemovePayload extends Payload
{
    public function getType(): string
    {
        return 'remove';
    }
}
