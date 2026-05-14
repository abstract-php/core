<?php

declare(strict_types=1);

namespace Abstract;

use Abstract\Emitter\HtmlEmitter;
use Abstract\Emitter\JsonEmitter;
use Abstract\Emitter\JsxEmitter;
use Abstract\Emitter\PklEmitter;
use Abstract\Emitter\TomlEmitter;
use Abstract\Emitter\XmlEmitter;
use Abstract\Emitter\YamlEmitter;
use Abstract\Mapper\HtmlMapper;
use Abstract\Mapper\ReactMapper;
use Abstract\Parser\Json\JsonTagParser;
use Abstract\Parser\Markup\DomMarkupParser;
use Abstract\Parser\Markup\MarkupParseOptions;
use Abstract\Parser\Pkl\PklTagParser;
use Abstract\Parser\Toml\TomlTagParser;
use Abstract\Parser\Yaml\YamlTagParser;
use Abstract\Runtime\RuntimeResolver;
use Abstract\Tree\Node;

final class AbstractCore
{
    public function __construct(
        private readonly JsonTagParser $parser = new JsonTagParser(),
    ) {
    }

    public function parseJson(string $json, ?string $source = null): Node
    {
        return $this->parser->parseString($json, $source);
    }

    public function parseJsonFile(string $path): Node
    {
        return $this->parser->parseFile($path);
    }

    public function parseHtml(string $html, ?string $source = null, ?MarkupParseOptions $options = null): Node
    {
        return (new DomMarkupParser())->parseHtmlString($html, $source, $options);
    }

    public function parseHtmlFile(string $path, ?MarkupParseOptions $options = null): Node
    {
        return (new DomMarkupParser())->parseHtmlFile($path, $options);
    }

    public function parseXml(string $xml, ?string $source = null, ?MarkupParseOptions $options = null): Node
    {
        return (new DomMarkupParser())->parseXmlString($xml, $source, $options);
    }

    public function parseXmlFile(string $path, ?MarkupParseOptions $options = null): Node
    {
        return (new DomMarkupParser())->parseXmlFile($path, $options);
    }

    public function parseYaml(string $yaml, ?string $source = null): Node
    {
        return (new YamlTagParser())->parseString($yaml, $source);
    }

    public function parseYamlFile(string $path): Node
    {
        return (new YamlTagParser())->parseFile($path);
    }

    public function parseToml(string $toml, ?string $source = null): Node
    {
        return (new TomlTagParser())->parseString($toml, $source);
    }

    public function parseTomlFile(string $path): Node
    {
        return (new TomlTagParser())->parseFile($path);
    }

    public function parsePkl(string $pkl, ?string $source = null): Node
    {
        return (new PklTagParser())->parseString($pkl, $source);
    }

    public function parsePklFile(string $path): Node
    {
        return (new PklTagParser())->parseFile($path);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function resolve(Node $tree, array $context = [], bool $strict = true): Node
    {
        return (new RuntimeResolver($strict, $this->parser))->resolve($tree, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function renderHtml(Node $tree, array $context = [], bool $strict = true): string
    {
        $resolved = $this->resolve($tree, $context, $strict);
        return (new HtmlEmitter())->emit((new HtmlMapper($strict))->map($resolved));
    }

    /**
     * @param array<string, mixed> $context
     */
    public function renderJsx(Node $tree, array $context = [], bool $strict = true): string
    {
        $resolved = $this->resolve($tree, $context, $strict);
        return (new JsxEmitter())->emit((new ReactMapper($strict))->map($resolved));
    }

    /**
     * @param array<string, mixed> $context
     */
    public function renderXml(Node $tree, array $context = [], bool $strict = true): string
    {
        $resolved = $this->resolve($tree, $context, $strict);
        return (new XmlEmitter())->emit((new HtmlMapper($strict))->map($resolved));
    }

    /**
     * @param array<string, mixed> $context
     */
    public function renderYaml(Node $tree, array $context = [], bool $strict = true, string $mode = JsonEmitter::MODE_COMPACT): string
    {
        $resolved = $this->resolve($tree, $context, $strict);
        return (new YamlEmitter())->emitTree($resolved, $mode);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function renderToml(Node $tree, array $context = [], bool $strict = true, string $mode = JsonEmitter::MODE_COMPACT): string
    {
        $resolved = $this->resolve($tree, $context, $strict);
        return (new TomlEmitter())->emitTree($resolved, $mode);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function renderPkl(Node $tree, array $context = [], bool $strict = true, string $mode = JsonEmitter::MODE_COMPACT): string
    {
        $resolved = $this->resolve($tree, $context, $strict);
        return (new PklEmitter())->emitTree($resolved, $mode);
    }

    public function treeJson(Node $tree, bool $pretty = true, string $mode = JsonEmitter::MODE_CANONICAL): string
    {
        return (new JsonEmitter())->emitTree($tree, $pretty, $mode);
    }
}
