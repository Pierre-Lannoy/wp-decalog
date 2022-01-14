<?php

namespace DLSpatie\Ray\Payloads;

class SeparatorPayload extends Payload
{
    public function getType(): string
    {
        return 'separator';
    }
}
