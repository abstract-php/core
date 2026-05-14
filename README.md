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
- DOMDocument-backed HTML markup parsing
- compact, tagged, and canonical JSON tree export
- strict and loose runtime modes
- HTML and JSX-like output pipelines
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

use AbstractLang\AbstractCore;

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
use AbstractLang\AbstractCore;
use AbstractLang\Emitter\JsonEmitter;
use AbstractLang\Parser\Markup\MarkupParseOptions;

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

## Tests

```bash
./vendor/bin/phpunit --configuration phpunit.xml
```

Current verified result:

```text
OK (33 tests, 47 assertions)
```

## Benchmarks

```bash
php benchmarks/core-benchmark.php
php benchmarks/markup-benchmark.php
```

Benchmark results are recorded in [PERFORMANCE.md](PERFORMANCE.md).

## Examples

Runnable examples live in [examples/](examples/). They include JSON-to-HTML, React/JSX mapping, runtime logic, imports, small HTML roundtrip, and a large `examples/big-html.html` roundtrip that writes compact JSON and HTML output under `examples/output/`.

## Documentation

- [SPEC.md](SPEC.md) defines the portable Abstract syntax and processing rules.
- [ARCHITECTURE.md](ARCHITECTURE.md) explains the PHP v0 implementation.
- [REPORT.md](REPORT.md) records the repo rescue and design decisions.
- [PERFORMANCE.md](PERFORMANCE.md) records benchmark method and results.
