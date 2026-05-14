# Abstract Agent Instructions

Abstract is a spec-first, language-agnostic tree processor.

Do not treat this as only a PHP template engine. PHP is the first implementation, but the core concepts must stay portable to future TypeScript/JavaScript implementations.

Core rules:
- Parse source formats into a canonical Abstract Tree.
- Normal object keys are element/component names.
- Keys/tags beginning with `:` are runtime nodes.
- Runtime nodes are never rendered literally.
- `@` means props.
- `#` means children.
- Primitive values are auto-typed.
- Explicit typed runtime nodes override inference.
- `:props` and `:attributes` patch the direct parent element's props.
- Logic should be data-based through `:expr`, not raw eval.
- `:php`, `:js`, and `:ts` are payload nodes only unless an explicit future plugin enables execution.
- Keep fixtures portable across PHP and future TypeScript implementations.
- Use strict mode for production correctness and loose mode for development exploration.

Before coding:
1. Read README.md, SPEC.md, ARCHITECTURE.md, composer.json, tests, fixtures, and existing source.
2. Run the current test suite if possible.
3. Make a small plan.
4. Implement with tests.
5. Update docs when behavior changes.
6. Run markup benchmarks when changing markup parsing or HTML emission.
7. Run tests again.
8. Report what changed and what remains.

Never silently ignore unresolved runtime nodes in strict mode.
Never add unsafe code execution by default.
