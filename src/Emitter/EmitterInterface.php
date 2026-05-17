<?php

declare(strict_types=1);

namespace Abstract\Emitter;

interface EmitterInterface
{
    public function emit(mixed $node): string;
}
