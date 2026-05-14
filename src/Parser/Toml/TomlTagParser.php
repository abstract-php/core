<?php

declare(strict_types=1);

namespace Abstract\Parser\Toml;

use Abstract\Exception\ParseException;
use Abstract\Parser\Native\NativeTagParser;
use Abstract\Tree\Node;
use Devium\Toml\Toml;
use Devium\Toml\TomlError;

final class TomlTagParser
{
    public function __construct(
        private readonly NativeTagParser $nativeParser = new NativeTagParser(),
    ) {
    }

    public function parseFile(string $path): Node
    {
        if (!is_file($path)) {
            throw new ParseException(sprintf('TOML source "%s" does not exist.', $path));
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new ParseException(sprintf('Unable to read TOML source "%s".', $path));
        }

        return $this->parseString($content, $path);
    }

    public function parseString(string $toml, ?string $source = null): Node
    {
        try {
            $decoded = Toml::decode($toml, true);
        } catch (TomlError $exception) {
            throw new ParseException(sprintf('Invalid Abstract TOML: %s', $exception->getMessage()), 0, $exception);
        }

        return $this->nativeParser->parse($decoded, $source);
    }
}
