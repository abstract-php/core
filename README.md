# Abstract

Abstract is a spec-first, language-agnostic tree processor. It turns structured source formats into a canonical Abstract Tree, resolves safe runtime nodes, maps that tree into a target model, and emits output such as HTML, JSX, JSON, schemas, workflows, or future custom targets.

This repository is the PHP v0 implementation. The concepts, fixtures, and docs are intentionally portable so a future JavaScript/TypeScript implementation can follow the same behavior.

## Status

Abstract Core v0 currently supports:

- JSON tag-key syntax
- canonical `element`, `runtime`, `value`, and `fragment` nodes
- primitive type inference
- explicit typed nodes such as `:string`, `:int`, `:float`, `:bool`, `:null`, `:array`, and `:object`
- `@` props and `#` children
- shorthand object and array children
- `:props` and `:attributes` parent prop modifiers
- data-based `:expr`, `:if`, `:else`, and `:each`
- inline `:import` and `:include`
- DOMDocument-backed HTML and XML markup parsing
- YAML, TOML, and Pkl parsing through the shared tag-key normalizer
- compact, tagged, and canonical JSON tree export
- strict and loose runtime modes
- HTML, XML, YAML, TOML, Pkl, and JSX-like output pipelines
- target-aware custom render targets and mapper overrides
- shared JSON fixtures and PHPUnit coverage
- benchmark scripts for JSON core flows and large HTML roundtrips

Unsafe code execution is not enabled. `:php`, `:js`, `:ts`, and `:code` are recognized as payload directives, but the default strict runtime rejects them instead of executing or rendering them.

## Installation

```bash
composer install
```

## Quick Example

```php
<?php

require 'vendor/autoload.php';

use Abstract\AbstractCore;

$core = new AbstractCore();

$tree = $core->parseJson('{
  "div": {
    "@": {
      "class": "card"
    },
    "#": [
      { "h1": "Title" },
      { "p": "Body" }
    ]
  }
}');

echo $core->renderHtml($tree);
// <div class="card"><h1>Title</h1><p>Body</p></div>
```

## JSON Tag-Key Syntax

The user syntax is tag-key based:

```json
{
  "div": "hello"
}
```

This normalizes to an element named `div` with one typed string child.

Canonical props and children:

```json
{
  "div": {
    "@": {
      "class": "card",
      "id": "main"
    },
    "#": [
      {
        "span": "Hello"
      }
    ]
  }
}
```

Shorthand child objects are supported:

```json
{
  "div": {
    "h1": "Title",
    "p": "Body"
  }
}
```

Repeated JSON object keys are impossible, so repeated elements must use arrays:

```json
{
  "ul": [
    { "li": "One" },
    { "li": "Two" }
  ]
}
```

## Runtime Nodes

Keys beginning with `:` are runtime nodes. They are used during processing and are never rendered as literal output tags.

```json
{
  "div": [
    { ":attributes": { "class": "card" } },
    { "span": "Hello" }
  ]
}
```

This renders as:

```html
<div class="card"><span>Hello</span></div>
```

Runtime logic is data-based:

```json
{
  ":if": {
    "@": {
      "test": {
        ":expr": { "var": "user.isLoggedIn" }
      }
    },
    "#": [
      { "Dashboard": [] }
    ],
    ":else": [
      { "Login": [] }
    ]
  }
}
```

## Imports

Imports resolve relative to the current source file and are cached by path, mtime, and content hash.

```json
{
  ":import": {
    "@": {
      "src": "./components/Card.abstract.json",
      "props": {
        "title": "Welcome"
      }
    },
    "#": [
      { "p": "This becomes slot content." }
    ]
  }
}
```

For v0, slot children are appended to the imported root element or fragment. A richer component/slot system can be added later.

## HTML Markup Parsing

HTML can be parsed through the same canonical tree model:

```php
use Abstract\AbstractCore;
use Abstract\Emitter\JsonEmitter;
use Abstract\Parser\Markup\MarkupParseOptions;

$core = new AbstractCore();
$tree = $core->parseHtmlFile('benchmarks/big-html.html', new MarkupParseOptions(includeMeta: false));

$compactJson = $core->treeJson($tree, pretty: false, mode: JsonEmitter::MODE_COMPACT);
$html = $core->renderHtml($core->parseJson($compactJson));
```

The parser uses native DOMDocument/libxml for v0 performance. Correctness is structural: output is not beautified and is not expected to be byte-identical to the source, but parsed nodes, attributes, text, comments, raw script/style content, and doctype data are preserved through the benchmark comparator.

JSON export modes:

- `canonical`: full internal `kind`/`name`/`props`/`children` tree
- `compact`: storage-oriented tag-key JSON with fewer model strings
- `tagged`: explicit Abstract tags for API/debug use

Text-only markup is treated as content, not as an implicit DOM paragraph:

```php
$tree = $core->parseHtml('Hello Test');

echo $core->treeJson($tree, pretty: false, mode: JsonEmitter::MODE_COMPACT);
// "Hello Test"

echo $core->renderHtml($tree);
// Hello Test
```

## XML, YAML, TOML, And Pkl

All data/config formats decode into native values and then use the same tag-key normalizer as JSON.

```php
$tree = $core->parseYamlFile('page.abstract.yaml');

echo $core->renderHtml($tree);
echo $core->renderYaml($tree);
```

Available parser methods:

- `parseXml`, `parseXmlFile`
- `parseYaml`, `parseYamlFile`
- `parseToml`, `parseTomlFile`
- `parsePkl`, `parsePklFile`

Available render methods:

- `renderXml`
- `renderYaml`
- `renderToml`
- `renderPkl`

YAML supports scalar, list, and map roots. TOML and Pkl rendering require object/map roots because their module/document syntax is property-oriented. Pkl parsing uses the local `pkl` CLI with `eval --format=json --no-project --root-dir`; it is an explicit parser path for trusted local config modules, not implicit code execution.

## Custom Render Targets

`AbstractCore` is a facade over parsers, runtime resolution, render targets, and tree serializers. `renderHtml()`, `renderJsx()`, and `renderXml()` use registered render targets internally, and `render($target, ...)` can call a target by name.

Custom mapping is target-aware. A JSX override does not affect HTML, and an HTML override does not affect JSX.

Custom JSX mapping:

```php
use Abstract\AbstractCore;
use Abstract\Emitter\JsxEmitter;
use Abstract\Mapper\ReactComponent;
use Abstract\Mapper\ReactMapper;
use Abstract\Render\RenderTarget;

$core = AbstractCore::default()
    ->withRenderTarget('jsx', RenderTarget::make(
        ReactMapper::make()
            ->component('input', ReactComponent::imported(
                source: '@headlessui/react',
                export: 'Input',
                as: 'HeadlessInput',
            )),
        new JsxEmitter(),
    ));

echo $core->renderJsx($tree);
```

Output:

```jsx
import { Input as HeadlessInput } from "@headlessui/react";

<HeadlessInput name="email" />
```

Custom HTML mapping:

```php
use Abstract\AbstractCore;
use Abstract\Emitter\HtmlEmitter;
use Abstract\Mapper\HtmlElementMapping;
use Abstract\Mapper\HtmlMapper;
use Abstract\Render\RenderTarget;

$core = AbstractCore::default()
    ->withRenderTarget('html', RenderTarget::make(
        HtmlMapper::make()
            ->element('input', HtmlElementMapping::tag('x-input')),
        new HtmlEmitter(),
    ));

echo $core->renderHtml($tree);
```

Output:

```html
<x-input name="email"></x-input>
```

Config-driven customization is also available for simple JSX components and HTML tag replacement:

```php
$core = AbstractCore::fromConfig([
    'targets' => [
        'jsx' => [
            'components' => [
                'input' => [
                    'source' => '@headlessui/react',
                    'export' => 'Input',
                    'as' => 'HeadlessInput',
                ],
            ],
        ],
        'html' => [
            'elements' => [
                'input' => ['tag' => 'x-input'],
            ],
        ],
    ],
]);
```

YAML, TOML, Pkl, and `treeJson()` currently serialize resolved Abstract Tree data directly. They remain configurable through their render methods and may gain dedicated mappers later.

## Tests

```bash
./vendor/bin/phpunit --configuration phpunit.xml
```

Current verified result:

```text
OK (53 tests, 87 assertions)
```

## Benchmarks

```bash
php benchmarks/core-benchmark.php
php benchmarks/markup-benchmark.php
```

Benchmark results are recorded in [PERFORMANCE.md](PERFORMANCE.md).

## Examples

Runnable examples live in [examples/](examples/). They include JSON-to-HTML, React/JSX mapping, runtime logic, imports, text-only markup, XML/YAML/TOML/Pkl scenarios, custom render targets, a small HTML roundtrip, and a large `examples/big-html.html` roundtrip that writes compact JSON and HTML output under `examples/output/`.

## Documentation

- [SPEC.md](SPEC.md) defines the portable Abstract syntax and processing rules.
- [ARCHITECTURE.md](ARCHITECTURE.md) explains the PHP v0 implementation.
- [DEVELOPMENT.md](DEVELOPMENT.md) explains how to change the codebase safely.
- [REPORT.md](REPORT.md) records the repo rescue and design decisions.
- [PERFORMANCE.md](PERFORMANCE.md) records benchmark method and results.
