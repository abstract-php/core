<?php

declare(strict_types=1);

namespace AbstractLang;

use AbstractLang\Emitter\HtmlEmitter;
use AbstractLang\Emitter\JsonEmitter;
use AbstractLang\Emitter\JsxEmitter;
use AbstractLang\Mapper\HtmlMapper;
use AbstractLang\Mapper\ReactMapper;
use AbstractLang\Parser\Json\JsonTagParser;
use AbstractLang\Parser\Markup\DomMarkupParser;
use AbstractLang\Parser\Markup\MarkupParseOptions;
use AbstractLang\Runtime\RuntimeResolver;
use AbstractLang\Tree\Node;

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

    public function treeJson(Node $tree, bool $pretty = true, string $mode = JsonEmitter::MODE_CANONICAL): string
    {
        return (new JsonEmitter())->emitTree($tree, $pretty, $mode);
    }
}
