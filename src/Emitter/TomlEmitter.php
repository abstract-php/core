<?php

declare(strict_types=1);

namespace Abstract\Emitter;

use Abstract\Exception\MappingException;
use Abstract\Tree\Node;
use Devium\Toml\Toml;
use Devium\Toml\TomlError;

final class TomlEmitter
{
    public function emitTree(Node $node, string $mode = JsonEmitter::MODE_COMPACT): string
    {
        return $this->emitData((new JsonEmitter())->toData($node, $mode));
    }

    public function emitData(mixed $data): string
    {
        if (!is_array($data) || array_is_list($data)) {
            throw new MappingException('TOML output requires an object/map root.');
        }

        try {
            return Toml::encode($data);
        } catch (TomlError $exception) {
            throw new MappingException(sprintf('Unable to emit TOML: %s', $exception->getMessage()), 0, $exception);
        }
    }
}
