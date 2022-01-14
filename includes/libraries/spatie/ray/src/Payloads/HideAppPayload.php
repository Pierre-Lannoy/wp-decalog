<?php

namespace DLSpatie\Ray\Payloads;

class HideAppPayload extends Payload
{
    public function getType(): string
    {
        return 'hide_app';
    }
}
