<?php

namespace DLSpatie\Ray\Origin;

interface OriginFactory
{
    public function getOrigin(): Origin;
}
