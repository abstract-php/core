# Abstract Specification

## Core Model

All source formats normalize into an Abstract Tree. The PHP v0 model uses these node kinds:

- `element`: renderable or mappable named node with `name`, `props`, and `children`
- `runtime`: processing node with `name`, `props`, optional `value`, and `children`
- `value`: typed data node with `type` and `value`
- `fragment`: ordered list of child nodes

Source metadata is optional. PHP v0 records JSON pointer metadata and source file paths when available.

## Tag-Key Syntax

JSON is the reference syntax, and YAML/TOML/Pkl decode into the same native tag-key data model before normalization. Normal object/map keys are element/component names:

```json
{ "div": "hello" }
```

`@` is props. `#` is children:

```json
{
  "div": {
    "@": { "class": "card" },
    "#": [
      { "span": "Hello" }
    ]
  }
}
```

Objects without `@` or `#` are shorthand child maps:

```json
{ "div": { "h1": "Title", "p": "Body" } }
```

Arrays are ordered children/fragments. Repeated elements must use arrays because JSON object keys cannot repeat.

## Types

Primitive source values infer these types:

- string
- int
- float
- bool
- null
- array
- object

Explicit typed runtime keys override inference:

```json
{ ":int": "42" }
```

Supported typed nodes in v0:

- `:string`
- `:int` / `:integer`
- `:float`
- `:bool` / `:boolean`
- `:null`
- `:array`
- `:object`

When ambiguity matters, plain data objects should be represented with `:object`.

Structural markup values may also appear when parsing HTML/XML-style source:

- `comment`
- `doctype`
- `cdata`
- `raw`

In JSON tag-key form these can be represented with reserved explicit value keys such as `:comment`, `:doctype`, `:cdata`, and `:raw`. They are value nodes, not executable runtime behavior.

## Runtime Nodes

Runtime nodes begin with `:` and are never rendered literally. Built-in short names are reserved for core behavior. Third-party packages should prefer namespaced runtime nodes such as `:vendor.name`, `:acme.chart`, or `:local.hero`.

Supported runtime nodes in PHP v0:

- `:expr`
- `:if`
- `:else`
- `:each`
- `:import`
- `:include`
- `:props`
- `:attributes`

Recognized but rejected by the strict default runtime:

- `:php`
- `:js`
- `:ts`
- `:code`

These are payload/directive concepts only. Core v0 does not execute them.

## Props Modifiers

`:props` and `:attributes` patch the direct parent element props.

Merge order:

1. Start with static `@` props.
2. Apply `:props` and `:attributes` in child order.
3. Later values override earlier values.

`:` props modifiers without a parent element are invalid in strict mode.

## Logic

`:expr` evaluates a deterministic data expression against a context object.

Supported v0 operators:

- `var`
- `==`
- `!=`
- `>`
- `>=`
- `<`
- `<=`
- `and`
- `or`
- `!`
- `+`
- `-`
- `*`
- `/`

No raw PHP or JavaScript `eval` is used.

`:if` chooses between children and `:else`:

```json
{
  ":if": {
    "@": {
      "test": { ":expr": { "var": "user.isLoggedIn" } }
    },
    "#": [{ "Dashboard": [] }],
    ":else": [{ "Login": [] }]
  }
}
```

`:each` expands children for each item:

```json
{
  ":each": {
    "@": {
      "items": { ":expr": { "var": "users" } },
      "as": "user"
    },
    "#": [
      { "UserCard": { "@": { "name": { ":expr": { "var": "user.name" } } } } }
    ]
  }
}
```

## Imports

`:import` and `:include` resolve JSON Abstract files relative to the importing source file.

Supported forms:

```json
{ ":import": "./components/Header.abstract.json" }
```

```json
{
  ":import": {
    "@": {
      "src": "./components/Card.abstract.json",
      "props": { "title": "Welcome" }
    }
  }
}
```

```json
{
  ":import": {
    "@": {
      "src": "./components/Card.abstract.json",
      "props": { "title": "Welcome" }
    },
    "#": [
      { "p": "This becomes slot content." }
    ]
  }
}
```

Circular imports and missing imports are strict errors.

## Mapper And Emitter Model

Mappers decide target meaning. Emitters serialize mapped target nodes.

PHP v0 includes:

- `HtmlMapper` + `HtmlEmitter`
- `ReactMapper` + `JsxEmitter`
- `JsonEmitter` for tree inspection

Runtime nodes should be resolved before final mapping. Mappers reject unresolved runtime nodes in strict mode.

## Markup Source Syntax

PHP v0 includes HTML and XML markup parsing backed by DOMDocument/libxml.

Markup normalization rules:

- normal tags become `element` nodes
- tags beginning with `:` become `runtime` nodes
- attributes become props
- non-English, mixed-case, namespaced, dotted, underscored, or very long tag/attribute names are preserved through a DOM-safe placeholder pass
- text becomes typed string values
- comments become `comment` values
- doctype becomes a `doctype` value when present in source
- `script` and `style` content becomes raw text for safe roundtrip emission
- parser metadata can be disabled for production/benchmark paths

Text-only HTML input contains no real markup token and therefore normalizes to a single string value. It must not become an implicit `<p>`:

```text
Hello Test
```

Compact JSON:

```json
"Hello Test"
```

HTML:

```html
Hello Test
```

DOMDocument may nest children under HTML void elements such as `source`, `img`, `meta`, or `br` when parsing non-beautified HTML. Abstract normalizes those cases by treating void elements as childless and lifting any parsed descendants back to sibling position before mapping/emission.

Markup roundtrip correctness is structural, not byte-for-byte. Formatting, attribute quote style, and other harmless serializer differences are not part of the v0 guarantee.

Runtime markup names after `:` must use portable ASCII names such as `<:if>` or `<:vendor.name>`. Non-English element names should be written without `:`, for example `<กรรม>ทดสอบ</กรรม>`.

## JSON Export Modes

`JsonEmitter` supports three output modes:

- `canonical`: full internal model, suitable for fixtures and debugging
- `compact`: storage-oriented tag-key JSON that omits internal model keys where unambiguous
- `tagged`: explicit Abstract tags for values/runtime nodes, suitable for API/debug output

Compact JSON is intended for backend storage. It preserves enough syntax to render later while avoiding repeated internal strings such as `kind`, `name`, `props`, and `children`.

## Data/Config Source Formats

YAML, TOML, and Pkl are data/config formats rather than tag-based markup. They are decoded to native maps/lists/scalars and then normalized with the same rules as JSON.

YAML:

- supports scalar, list, and map roots
- is the most flexible human-friendly storage/render format after JSON

TOML:

- is best for object/table roots
- parsing scalar documents fails because TOML has no top-level scalar document form
- rendering scalar/list roots fails with a clear mapping error

Pkl:

- parsing uses `pkl eval --format=json --no-project --root-dir`
- renderer emits simple module properties and `new Mapping` values
- rendering scalar/list roots fails because Pkl module output is property-oriented
- Pkl is only evaluated through explicit parser calls for trusted local config modules

These formats must not introduce different meaning for `@`, `#`, `:runtime`, typed nodes, props modifiers, or logic.

## XML Output

XML output uses XML escaping and always emits explicit closing tags. It does not use HTML void-tag behavior.

## Modes

Strict mode is the default. It errors on unknown runtime nodes, invalid imports, invalid props modifiers, payload code nodes, and mapper/runtime mismatches.

Loose mode warns and drops unknown runtime nodes when dropping is safe.

## Future Source Formats

JSON, HTML, XML, YAML, TOML, and Pkl are the PHP v0 parser family. AML and richer markup-specific parsers should be added through the same parser interface and must normalize into the same tree model.
