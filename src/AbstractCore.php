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
use Abstract\Mapper\HtmlElementMapping;
use Abstract\Mapper\MappingContext;
use Abstract\Mapper\ReactComponent;
use Abstract\Render\RenderTarget;
use Abstract\Render\RenderTargetRegistry;
use Abstract\Runtime\RuntimeResolver;
use Abstract\Tree\Node;

final class AbstractCore
{
    private readonly JsonTagParser $parser;
    private readonly RenderTargetRegistry $renderTargets;

    public function __construct(
        ?JsonTagParser $parser = null,
        ?RenderTargetRegistry $renderTargets = null,
    ) {
        $this->parser = $parser ?? new JsonTagParser();
        $this->renderTargets = $renderTargets ?? self::defaultRenderTargets();
    }

    public static function default(): self
    {
        return new self();
    }

    public static function fromConfig(array $config): self
    {
        return self::default()->withConfig($config);
    }

    public function withRenderTarget(string $name, RenderTarget $target): self
    {
        return new self($this->parser, $this->renderTargets->with($name, $target));
    }

    /**
     * @param array<string, mixed> $config
     */
    public function withConfig(array $config): self
    {
        $core = $this;
        $targets = $config['targets'] ?? [];
        if (!is_array($targets)) {
            return $core;
        }

        $jsx = $targets['jsx']['components'] ?? null;
        if (is_array($jsx)) {
            $mapper = ReactMapper::make();
            foreach ($jsx as $name => $component) {
                if (!is_string($name) || !is_array($component)) {
                    continue;
                }
                $source = $component['source'] ?? null;
                $export = $component['export'] ?? null;
                if (!is_string($source) || !is_string($export)) {
                    continue;
                }
                $as = $component['as'] ?? null;
                $importKind = $component['importKind'] ?? 'named';
                $mapper = $mapper->component($name, ReactComponent::imported(
                    source: $source,
                    export: $export,
                    as: is_string($as) ? $as : null,
                    importKind: is_string($importKind) ? $importKind : 'named',
                ));
            }
            $core = $core->withRenderTarget('jsx', RenderTarget::make($mapper, new JsxEmitter()));
        }

        $html = $targets['html']['elements'] ?? null;
        if (is_array($html)) {
            $mapper = HtmlMapper::make();
            foreach ($html as $name => $element) {
                if (!is_string($name) || !is_array($element) || !is_string($element['tag'] ?? null)) {
                    continue;
                }
                $mapper = $mapper->element($name, HtmlElementMapping::tag($element['tag']));
            }
            $core = $core->withRenderTarget('html', RenderTarget::make($mapper, new HtmlEmitter()));
        }

        return $core;
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
        return $this->render('html', $tree, $context, $strict);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function renderJsx(Node $tree, array $context = [], bool $strict = true): string
    {
        return $this->render('jsx', $tree, $context, $strict);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function renderXml(Node $tree, array $context = [], bool $strict = true): string
    {
        return $this->render('xml', $tree, $context, $strict);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function render(string $target, Node $tree, array $context = [], bool $strict = true): string
    {
        $resolved = $this->resolve($tree, $context, $strict);
        return $this->renderTargets->get($target)->render($resolved, new MappingContext(
            target: $target,
            strict: $strict,
            runtimeContext: $context,
        ));
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

    private static function defaultRenderTargets(): RenderTargetRegistry
    {
        return new RenderTargetRegistry([
            'html' => RenderTarget::make(HtmlMapper::make(), new HtmlEmitter()),
            'jsx' => RenderTarget::make(ReactMapper::make(), new JsxEmitter()),
            'xml' => RenderTarget::make(HtmlMapper::make(), new XmlEmitter()),
        ]);
    }
}
