<?php

declare(strict_types=1);

namespace Abstract\Mapper;

use Abstract\Tree\Node;

interface MapperInterface
{
    public function map(Node $node): TargetNode;
}
