# Engineering Report

## Repository Findings

The old repository was a sequence of drafts rather than a maintainable package:

- 2023 history started as `Compiler`/`Processor` AML experiments that mapped XML-like markup into PHP method/class access and HTML output.
- Later history pivoted into a large mutable `Abstraction`/`Reference`/`Value` tree model with DOM/markup experiments.
- The most recent commit bolted on JSON Logic classes, including duplicate `JsonLogic copy` folders.
- No PHPUnit/Pest config existed.
- `tests/` contained manual scripts, fixtures, timing probes, and old experiments rather than assertions.
- `README.md` described broad features that were only partly implemented.
- `composer.lock` was stale against `composer.json`.
- Existing code parsed under PHP 8.4 but emitted nullable-type deprecations.
- Markup tests failed at runtime because constructors and call sites no longer matched.
- The bundled XAMPP `phpunit` binary was too old for PHP 8.4 and failed on removed PHP functions.

## Salvaged Intent

The useful ideas were kept:

- source syntax should map to a tree
- attributes/props and children should be distinct
- runtime/control nodes should not render literally
- text/comment/typed data need explicit representation
- compiler-style phases are the right shape
- JSON Logic is the right inspiration for safe expressions
- import/file splitting belongs in the core architecture
- native DOM parsing is the right direction for markup performance

## Replaced Code

The old tracked `src/` implementation and manual `tests/` scripts were replaced with a clean v0 core. The old code remains available in git history.

Major replacements:

- mutable `Abstract\Abstraction` model -> `AbstractLang\Tree\Node`
- reflection-heavy parser/resolver/factory draft -> explicit parser/runtime/mapper contracts
- unsafe execution-oriented compiler intent -> safe data-based runtime
- manual probe scripts -> PHPUnit suite
- broad README claims -> spec/architecture/report/performance docs
- old dependency set -> PHP >=8.2 plus PHPUnit dev dependency

## Design Decisions

- The namespace is `AbstractLang` to avoid PHP keyword confusion and to keep concepts portable.
- JSON is the only v0 source parser.
- HTML markup parsing now uses a fresh DOMDocument adapter, not the old draft `MarkupParser`.
- Explicit typed nodes normalize directly to value nodes.
- `:props` and `:attributes` are resolved as parent modifiers, not rendered children.
- Strict mode is the default.
- Loose mode only drops runtime nodes when doing so is safe.
- `:php`, `:js`, `:ts`, and `:code` are recognized but rejected by strict default runtime.
- Import slot children are appended to the imported root element or fragment for v0.
- React/JSX mapping converts `class` to `className` only when `className` is absent.
- The core logic evaluator is implemented locally to avoid a dependency and to keep the initial operator set small and auditable.
- Compact JSON is the storage-oriented export format. Canonical JSON remains the full internal tree format.
- Markup roundtrip validation is structural rather than byte-for-byte because DOMDocument and emitters normalize formatting and void tags.

## Implementation Summary

Implemented:

- canonical node model
- JSON tag-key parser
- type inference and explicit typed nodes
- props and children syntax
- shorthand child maps and arrays
- runtime resolver
- expression evaluator
- `:if`, `:else`, `:each`
- `:props`, `:attributes`
- `:import`, `:include`
- import cache and circular import detection
- DOMDocument-backed HTML markup parser
- compact/tagged/canonical JSON export modes
- comment, doctype, cdata, and raw text value handling
- raw `script`/`style` HTML emission
- large HTML roundtrip benchmark with structural fingerprint comparison
- HTML mapper/emitter
- React/JSX mapper/emitter
- JSON tree emitter
- shared fixtures
- PHPUnit tests
- benchmark script
- README, SPEC, ARCHITECTURE, REPORT, PERFORMANCE docs

## Test Summary

Command:

```bash
./vendor/bin/phpunit --configuration phpunit.xml
```

Result:

```text
OK (33 tests, 47 assertions)
```

The old global XAMPP `phpunit` was not used because it is incompatible with PHP 8.4.

## Known Limitations

- HTML parsing is implemented; JSON remains the only fully specified authoring syntax.
- XML parser entrypoints exist through DOMDocument but do not yet have the same fixture depth as JSON/HTML.
- No AML/YAML/Pkl parser is implemented yet.
- Source spans are JSON-pointer based, not byte/line-column based.
- `:elseif` is reserved but not implemented.
- Import slot handling is simple append behavior.
- Payload code nodes are not emitted by a specialized code emitter yet.
- JSX output is JSX-like string output, not an AST.
- No static-analysis tool is configured yet.
- Compact JSON keeps whitespace text nodes by default, so it can be larger than minified HTML.

## Next Steps

- Add a formal extension/plugin registry for runtime nodes.
- Add `:elseif` and richer component slot semantics.
- Add deeper XML fixtures and decide XML-specific emitter behavior.
- Add optional whitespace compaction for storage use cases that do not need exact text nodes.
- Add schema emitter and PHP object mapper.
- Add persistent import cache options.
- Add static analysis, likely PHPStan or Psalm.
- Build a future TypeScript runner against the same fixtures.
