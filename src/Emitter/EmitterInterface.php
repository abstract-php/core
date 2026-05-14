<?php

declare(strict_types=1);

namespace Abstract\Emitter;

use Abstract\Mapper\TargetNode;

interface EmitterInterface
{
    public function emit(TargetNode $node): string;
}
