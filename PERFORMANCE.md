# Performance

## Method

Benchmarks are intentionally simple and non-fragile. They measure relative baseline cost for the v0 JSON pipeline and the large HTML markup roundtrip path.

Command:

```bash
php benchmarks/core-benchmark.php
php benchmarks/markup-benchmark.php
```

Environment from the latest run:

```json
{
  "php": "8.4.13",
  "core_iterations": 1000,
  "date": "2026-05-14"
}
```

## Core JSON Results

| Benchmark | Iterations | Total ms | Mean us | Peak MB |
| --- | ---: | ---: | ---: | ---: |
| json_decode only | 1000 | 1.877 | 1.877 | 4 |
| parse + normalize | 1000 | 12.775 | 12.775 | 4 |
| parse + normalize + resolve runtime | 1000 | 32.433 | 32.433 | 4 |
| parse + normalize + resolve + map to HTML | 1000 | 52.289 | 52.289 | 4 |
| import resolve cold cache | 1 | 0.102 | 102.250 | 4 |
| import resolve warm cache | 100 | 3.081 | 30.815 | 4 |

## Big HTML Markup Results

Input: `benchmarks/big-html.html`

Generated files are written to `benchmarks/output/`, which is ignored by git:

- `big-html.roundtrip.html`
- `big-html.compact.json`
- `big-html.report.json`

| Benchmark | Time ms |
| --- | ---: |
| DOM parse only | 3.235 |
| parse + normalize | 6.195 |
| compact JSON export | 1.633 |
| compact JSON reparse | 3.520 |
| HTML emit | 1.525 |
| output reparse for comparison | 6.829 |

| Artifact | Size |
| --- | ---: |
| source HTML | 825,408 B |
| compact JSON | 882,127 B |
| roundtrip HTML | 813,691 B |

Structural fingerprint comparison: `yes`.

## Observations

- Native `json_decode` is extremely fast and should remain the first stage for JSON.
- Parse plus normalization is roughly 7x raw decode on the sample, which is reasonable after moving the rules into a reusable native-data parser.
- Runtime resolution dominates the current benchmark because expressions and loops walk the tree.
- HTML mapping/emission adds predictable cost after runtime resolution.
- Warm import resolution avoids reparsing imported files and sits close to normal resolve cost.
- Native DOMDocument parsing is fast on the 806 KB HTML sample; normalization adds roughly 1 ms over DOM parse alone in this run.
- Compact JSON is larger than the source HTML for this sample because it preserves tree structure, explicit arrays, attributes, comments, and escaped JSON strings.
- Void-element normalization is required for DOMDocument HTML parity on real-world `<picture><source>...` markup.
- YAML/TOML/Pkl parsers intentionally reuse the same native-data normalizer; format-library decode cost should be benchmarked separately once real workloads exist.

## Optimization Opportunities

- Add optional metadata-off mode for production hot paths.
- Reuse runtime resolver instances when resolving many files with shared imports.
- Compile repeated expression paths into small lookup callables.
- Add mapper-plan caching for stable component trees.
- Track line/column spans only in debug mode.
- Consider optional whitespace compaction for storage paths that do not require exact text-node preservation.
- Add a streaming or chunked export path only if future benchmarks show memory pressure on multi-megabyte documents.
