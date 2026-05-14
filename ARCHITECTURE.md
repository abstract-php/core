# Architecture

## Pipeline

Abstract PHP v0 uses a compiler-style pipeline:

```text
source -> parser/normalizer -> Abstract Tree -> runtime resolver -> mapper -> emitter
```

Each stage has one job:

- parser/normalizer reads syntax and builds boring canonical nodes
- runtime resolver consumes `:` nodes using deterministic handlers
- mapper converts resolved nodes to target meaning
- emitter serializes mapped target nodes

## Package Layout

```text
src/
  AbstractCore.php
  Tree/
  Parser/Json/
  Parser/Markup/
  Runtime/
  Mapper/
  Emitter/
  Exception/
```

The namespace is `AbstractLang\...` because `abstract` is a PHP keyword and the design should not lean on PHP-only naming.

## Tree Model

`AbstractLang\Tree\Node` is the canonical tree value object. It supports `element`, `runtime`, `value`, and `fragment` node creation plus array serialization for shared fixtures.

The tree is intentionally strict and plain. Render behavior does not live on the node model.

## JSON Parser

`JsonTagParser` uses `json_decode(..., JSON_THROW_ON_ERROR)` and converts JSON objects into tag-key nodes.

Important parser decisions:

- Normal keys become elements.
- Runtime keys start with `:`.
- Typed runtime nodes become `value` nodes during normalization.
- `@` props are data maps, but explicit runtime values inside props are preserved as nodes.
- `#` is ordered child content.
- Objects without `@` or `#` become shorthand child maps.
- Source metadata is optional and lightweight.

## Markup Parser

`Parser/Markup/DomMarkupParser` is the v0 HTML/XML-style parser. It is a new implementation that uses DOMDocument/libxml as the parsing engine and normalizes DOM nodes into the same Abstract Tree model as JSON.

HTML parser decisions:

- DOMDocument is the fast default path.
- parser options control HTML/XML mode, fragment parsing, whitespace/comments/doctype preservation, metadata, and libxml flags.
- a UTF-8 hint is injected before parsing and ignored during DOM conversion.
- DOM comments, doctypes, CDATA, and raw text are represented as value node types.
- `script` and `style` children map to raw text so output does not escape executable/source payload text.
- HTML void elements are normalized as childless even when DOMDocument nests following nodes under them.

The old draft markup parser is not a dependency. Its useful direction was native parser integration; its API and abstractions were replaced.

## Runtime

`RuntimeResolver` consumes runtime nodes. It is handler-map oriented through explicit `match` branches for v0 core nodes.

Runtime behavior:

- `:expr` delegates to `LogicEvaluator`.
- `:if` resolves one branch.
- `:each` expands children with a loop context.
- `:props` and `:attributes` patch only the direct parent element.
- `:import` and `:include` parse and resolve another Abstract JSON file.
- code payload nodes are rejected in strict mode and dropped with a warning in loose mode.

`LogicEvaluator` implements a small JSON-Logic-inspired expression system without raw `eval`.

## Imports And Cache

Imports resolve relative to the current source file. The resolver caches imported parse trees by absolute path, mtime, and SHA-256 content hash.

Circular imports are detected with an import stack and reported as strict errors.

## Mappers And Emitters

`HtmlMapper` and `ReactMapper` produce target nodes. `HtmlEmitter` and `JsxEmitter` serialize those target nodes.

HTML output:

- element nodes become tags
- value nodes become escaped text
- `comment`, `doctype`, and raw values emit through dedicated target nodes
- false/null attributes are omitted
- true attributes become boolean attributes
- void tags emit without closing tags
- unresolved runtime nodes fail in strict mode

JSX output:

- `class` maps to `className` when `className` is not already present
- scalar props use JSX-safe output
- text escapes JSX-sensitive characters
- unresolved runtime nodes fail in strict mode

## Performance Strategy

The v0 implementation keeps the hot path straightforward:

- native JSON parsing
- native DOMDocument/libxml parsing for markup
- single parser/normalizer pass
- explicit runtime dispatch
- import parse cache
- optional metadata
- no reflection or eval in the core

Large markup benchmarks disable metadata and compare structural fingerprints after reparsing the emitted HTML. This catches node/data loss without pretending serializer formatting must be byte-identical.

Future optimizations can add compiled mapper plans, persistent import caches, and source-span toggles.
