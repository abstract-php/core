<?php

declare(strict_types=1);

namespace AbstractLang\Emitter;

use AbstractLang\Mapper\TargetNode;

interface EmitterInterface
{
    public function emit(TargetNode $node): string;
}
