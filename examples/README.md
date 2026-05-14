# Abstract Examples

Each scenario has a source file and a mapper script. Run scripts from the repo root:

```bash
php examples/00-chaotic-markup-roundtrip.mapper.php
php examples/01-basic-html.mapper.php
php examples/02-react-jsx.mapper.php
php examples/03-runtime-dashboard.mapper.php
php examples/04-imports.mapper.php
php examples/05-markup-roundtrip.mapper.php
php examples/06-big-html.mapper.php
```

## Scenarios

- `00-chaotic-markup-roundtrip.*`: intentionally chaotic HTML. Unsupported runtime-style tag names now report the original source file and line.
- `01-basic-html.*`: JSON tag-key source mapped to HTML plus compact/canonical JSON.
- `02-react-jsx.*`: JSON source mapped through the React/JSX pipeline.
- `03-runtime-dashboard.*`: `:expr`, `:if`, and `:each` with context data.
- `04-imports.*`: inline `:import` with props and slot children.
- `05-markup-roundtrip.*`: HTML source parsed to Abstract compact JSON and emitted back to HTML.
- `06-big-html.mapper.php`: parses `examples/big-html.html`, saves compact JSON and roundtrip HTML, and checks structural equality.

Generated files are written to `examples/output/` and ignored by git.
