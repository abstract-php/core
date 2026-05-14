<?php

declare(strict_types=1);

namespace AbstractLang\Parser\Json;

use AbstractLang\Exception\ParseException;
use AbstractLang\Tree\Node;
use JsonException;
use stdClass;

final class JsonTagParser
{
    private const RUNTIME_PREFIX = ':';

    /** @var array<string, true> */
    private const TYPED_RUNTIME = [
        'string' => true,
        'int' => true,
        'integer' => true,
        'float' => true,
        'bool' => true,
        'boolean' => true,
        'null' => true,
        'array' => true,
        'object' => true,
    ];

    /** @var array<string, true> */
    private const STRUCTURAL_VALUE_RUNTIME = [
        'comment' => true,
        'doctype' => true,
        'cdata' => true,
        'raw' => true,
        'text' => true,
    ];

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

        return $this->parseValue($decoded, $this->meta($source, ''));
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function parseValue(mixed $value, array $meta): Node
    {
        if ($value instanceof stdClass) {
            return $this->parseObject($value, $meta);
        }

        if (is_array($value)) {
            return Node::fragment(
                array_map(
                    fn (mixed $child, int $index): Node => $this->parseValue($child, $this->appendPointer($meta, (string) $index)),
                    $value,
                    array_keys($value),
                ),
                $meta,
            );
        }

        return $this->inferValueNode($value, $meta);
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function parseObject(stdClass $object, array $meta): Node
    {
        $entries = get_object_vars($object);
        if ($entries === []) {
            return Node::value('object', [], $meta);
        }

        $nodes = [];
        foreach ($entries as $key => $value) {
            if (!is_string($key) || $key === '') {
                $this->fail('Abstract JSON object keys must be non-empty strings.', $meta);
            }
            $nodes[] = $this->parseKeyedNode($key, $value, $this->appendPointer($meta, $key));
        }

        return count($nodes) === 1 ? $nodes[0] : Node::fragment($nodes, $meta);
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function parseKeyedNode(string $key, mixed $value, array $meta): Node
    {
        if (str_starts_with($key, self::RUNTIME_PREFIX)) {
            $name = substr($key, 1);
            if ($name === '') {
                $this->fail(sprintf('Runtime node names must not be empty for key "%s".', $key), $meta);
            }
            return $this->parseRuntime($name, $value, $meta);
        }

        return $this->parseElement($key, $value, $meta);
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function parseElement(string $name, mixed $body, array $meta): Node
    {
        $props = [];
        $children = [];

        if ($body instanceof stdClass) {
            $entries = get_object_vars($body);
            if (array_key_exists('@', $entries) || array_key_exists('#', $entries)) {
                $props = array_key_exists('@', $entries)
                    ? $this->parseProps($entries['@'], $this->appendPointer($meta, '@'))
                    : [];

                if (array_key_exists('#', $entries)) {
                    $children = $this->parseChildren($entries['#'], $this->appendPointer($meta, '#'));
                }

                foreach ($entries as $childKey => $childValue) {
                    if ($childKey === '@' || $childKey === '#') {
                        continue;
                    }
                    $children[] = $this->parseKeyedNode($childKey, $childValue, $this->appendPointer($meta, $childKey));
                }
            } else {
                $children = $this->parseChildren($body, $meta);
            }
        } elseif (is_array($body)) {
            $children = $this->parseChildren($body, $meta);
        } else {
            $children = [$this->inferValueNode($body, $meta)];
        }

        return Node::element($name, $props, $children, $meta);
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function parseRuntime(string $name, mixed $body, array $meta): Node
    {
        $canonicalName = match ($name) {
            'integer' => 'int',
            'boolean' => 'bool',
            default => $name,
        };

        if (isset(self::STRUCTURAL_VALUE_RUNTIME[$canonicalName])) {
            return $this->explicitStructuralValueNode($canonicalName, $body, $meta);
        }

        if (isset(self::TYPED_RUNTIME[$name])) {
            return $this->explicitValueNode($canonicalName, $body, $meta);
        }

        if ($canonicalName === 'if') {
            return $this->parseIfRuntime($body, $meta);
        }

        if (in_array($canonicalName, ['props', 'attributes'], true) && $body instanceof stdClass) {
            return Node::runtime($canonicalName, [], [], $this->parseProps($body, $meta), $meta);
        }

        if ($body instanceof stdClass) {
            $entries = get_object_vars($body);
            if (array_key_exists('@', $entries) || array_key_exists('#', $entries)) {
                $props = array_key_exists('@', $entries)
                    ? $this->parseProps($entries['@'], $this->appendPointer($meta, '@'))
                    : [];
                $children = array_key_exists('#', $entries)
                    ? $this->parseChildren($entries['#'], $this->appendPointer($meta, '#'))
                    : [];

                foreach ($entries as $childKey => $childValue) {
                    if ($childKey === '@' || $childKey === '#') {
                        continue;
                    }
                    $children[] = $this->parseKeyedNode($childKey, $childValue, $this->appendPointer($meta, $childKey));
                }

                return Node::runtime($canonicalName, $props, $children, null, $meta);
            }
        }

        return Node::runtime($canonicalName, [], [], $this->toPlainData($body), $meta);
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function parseIfRuntime(mixed $body, array $meta): Node
    {
        if (!$body instanceof stdClass) {
            return Node::runtime('if', [], $this->parseChildren($body, $meta), null, $meta);
        }

        $entries = get_object_vars($body);
        $props = array_key_exists('@', $entries)
            ? $this->parseProps($entries['@'], $this->appendPointer($meta, '@'))
            : [];
        $children = array_key_exists('#', $entries)
            ? $this->parseChildren($entries['#'], $this->appendPointer($meta, '#'))
            : [];

        foreach ($entries as $key => $value) {
            if ($key === '@' || $key === '#') {
                continue;
            }

            if ($key === ':else') {
                $props['else'] = $this->parseChildren($value, $this->appendPointer($meta, ':else'));
                continue;
            }

            $children[] = $this->parseKeyedNode($key, $value, $this->appendPointer($meta, $key));
        }

        return Node::runtime('if', $props, $children, null, $meta);
    }

    /**
     * @param array<string, mixed> $meta
     * @return list<Node>
     */
    private function parseChildren(mixed $value, array $meta): array
    {
        if (is_array($value)) {
            return array_map(
                fn (mixed $child, int $index): Node => $this->parseValue($child, $this->appendPointer($meta, (string) $index)),
                $value,
                array_keys($value),
            );
        }

        if ($value instanceof stdClass) {
            $entries = get_object_vars($value);
            if ($entries === []) {
                return [Node::value('object', [], $meta)];
            }

            $children = [];
            foreach ($entries as $key => $childValue) {
                $children[] = $this->parseKeyedNode($key, $childValue, $this->appendPointer($meta, $key));
            }
            return $children;
        }

        return [$this->inferValueNode($value, $meta)];
    }

    /**
     * @param array<string, mixed> $meta
     * @return array<string, mixed>
     */
    private function parseProps(mixed $value, array $meta): array
    {
        if (!$value instanceof stdClass) {
            $this->fail('Props must be a JSON object.', $meta);
        }

        $props = [];
        foreach (get_object_vars($value) as $key => $propValue) {
            $props[$key] = $this->parsePropValue($propValue, $this->appendPointer($meta, $key));
        }
        return $props;
    }

    /**
     * Runtime nodes are significant inside props, but plain objects remain data.
     *
     * @param array<string, mixed> $meta
     */
    private function parsePropValue(mixed $value, array $meta): mixed
    {
        if ($value instanceof stdClass) {
            $entries = get_object_vars($value);
            if (count($entries) === 1) {
                $key = array_key_first($entries);
                if (is_string($key) && str_starts_with($key, self::RUNTIME_PREFIX)) {
                    return $this->parseKeyedNode($key, $entries[$key], $this->appendPointer($meta, $key));
                }
            }

            $result = [];
            foreach ($entries as $key => $childValue) {
                $result[$key] = $this->parsePropValue($childValue, $this->appendPointer($meta, $key));
            }
            return $result;
        }

        if (is_array($value)) {
            return array_map(
                fn (mixed $child, int $index): mixed => $this->parsePropValue($child, $this->appendPointer($meta, (string) $index)),
                $value,
                array_keys($value),
            );
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function inferValueNode(mixed $value, array $meta): Node
    {
        return Node::value($this->inferType($value), $this->toPlainData($value), $meta);
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function explicitValueNode(string $type, mixed $value, array $meta): Node
    {
        return match ($type) {
            'string' => Node::value('string', is_scalar($value) || $value === null ? (string) $value : json_encode($this->toPlainData($value), JSON_UNESCAPED_UNICODE), $meta),
            'int' => Node::value('int', (int) $value, $meta),
            'float' => Node::value('float', (float) $value, $meta),
            'bool' => Node::value('bool', filter_var($value, FILTER_VALIDATE_BOOLEAN), $meta),
            'null' => Node::value('null', null, $meta),
            'array' => Node::value('array', array_values((array) $this->toPlainData($value)), $meta),
            'object' => Node::value('object', (array) $this->toPlainData($value), $meta),
            default => Node::value($type, $this->toPlainData($value), $meta),
        };
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function explicitStructuralValueNode(string $type, mixed $value, array $meta): Node
    {
        $nodeType = $type === 'text' ? 'string' : $type;
        if ($nodeType === 'string' && !is_scalar($value) && $value !== null) {
            return Node::value('string', json_encode($this->toPlainData($value), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '', $meta);
        }

        return Node::value($nodeType, is_scalar($value) || $value === null ? (string) $value : $this->toPlainData($value), $meta);
    }

    private function inferType(mixed $value): string
    {
        return match (true) {
            is_string($value) => 'string',
            is_int($value) => 'int',
            is_float($value) => 'float',
            is_bool($value) => 'bool',
            $value === null => 'null',
            is_array($value) => 'array',
            $value instanceof stdClass => 'object',
            default => get_debug_type($value),
        };
    }

    private function toPlainData(mixed $value): mixed
    {
        if ($value instanceof stdClass) {
            $result = [];
            foreach (get_object_vars($value) as $key => $childValue) {
                $result[$key] = $this->toPlainData($childValue);
            }
            return $result;
        }

        if (is_array($value)) {
            return array_map(fn (mixed $child): mixed => $this->toPlainData($child), $value);
        }

        return $value;
    }

    /**
     * @return array<string, mixed>
     */
    private function meta(?string $source, string $pointer): array
    {
        return array_filter([
            'source' => $source,
            'pointer' => $pointer === '' ? '/' : $pointer,
        ], static fn (?string $value): bool => $value !== null);
    }

    /**
     * @param array<string, mixed> $meta
     * @return array<string, mixed>
     */
    private function appendPointer(array $meta, string $segment): array
    {
        $meta['pointer'] = ($meta['pointer'] ?? '/') === '/'
            ? '/' . $this->escapePointer($segment)
            : $meta['pointer'] . '/' . $this->escapePointer($segment);
        return $meta;
    }

    private function escapePointer(string $segment): string
    {
        return str_replace(['~', '/'], ['~0', '~1'], $segment);
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function fail(string $message, array $meta): never
    {
        throw new ParseException($message . ' Source: ' . $this->formatLocation($meta) . '.');
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function formatLocation(array $meta): string
    {
        $source = isset($meta['source']) && is_string($meta['source']) && $meta['source'] !== ''
            ? $meta['source']
            : 'JSON source';
        $pointer = isset($meta['pointer']) && is_string($meta['pointer']) && $meta['pointer'] !== ''
            ? $meta['pointer']
            : '/';

        return $source . ' at ' . $pointer;
    }
}
