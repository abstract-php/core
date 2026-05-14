<?php

declare(strict_types=1);

namespace AbstractLang\Mapper;

use AbstractLang\Tree\Node;

interface MapperInterface
{
    public function map(Node $node): TargetNode;
}
