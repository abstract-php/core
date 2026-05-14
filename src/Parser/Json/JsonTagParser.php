<?php

declare(strict_types=1);

namespace Abstract\Parser\Json;

use Abstract\Exception\ParseException;
use Abstract\Parser\Native\NativeTagParser;
use Abstract\Tree\Node;
use JsonException;

final class JsonTagParser
{
    public function __construct(
        private readonly NativeTagParser $nativeParser = new NativeTagParser(),
    ) {
    }

    public function parseFile(string $path): Node
    {
        if (!is_file($path)) {
            throw new ParseException(sprintf('Abstract JSON source "%s" does not exist.', $path));
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new ParseException(sprintf('Unable to read Abstract JSON source "%s".', $path));
        }

        return $this->parseString($content, $path);
    }

    public function parseString(string $json, ?string $source = null): Node
    {
        try {
            $decoded = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new ParseException(sprintf('Invalid Abstract JSON: %s', $exception->getMessage()), 0, $exception);
        }

        return $this->nativeParser->parse($decoded, $source);
    }
}
