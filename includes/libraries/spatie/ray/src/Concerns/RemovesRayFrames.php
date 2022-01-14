<?php

namespace DLSpatie\Ray\Concerns;

use DLSpatie\Backtrace\Frame;

trait RemovesRayFrames
{
    protected function removeRayFrames(array $frames): array
    {
        $frames = array_filter($frames, function (Frame $frame) {
            return ! $this->isRayFrame($frame);
        });

        return array_values($frames);
    }

    protected function isRayFrame(Frame $frame): bool
    {
        foreach ($this->rayNamespaces() as $rayNamespace) {
            if (substr((string)$frame->class, 0, strlen($rayNamespace)) === $rayNamespace) {
                return true;
            }
        }

        return false;
    }

    protected function rayNamespaces(): array
    {
        return [
            'DLSpatie\Ray',
            'DLSpatie\LaravelRay',
            'DLSpatie\WordPressRay',
        ];
    }
}
