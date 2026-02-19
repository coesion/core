# Router Benchmarks

Current benchmark conclusions should be read from practical API scenarios first (`small` and `medium`), with `large` treated as a stress/scaling check.

**Last updated:** 2026-02-19 03:17:48  
**Latest full run:** `benchmarks/results/bench_20260219_030929.csv`  
**Runtime defaults:** `BENCH_ITERATIONS=5000`, `BENCH_WARMUP=500`, `BENCH_REPEATS=1`

## Core Strengths

- Core has very strong warm routing throughput for practical route set sizes.
- Core has the lowest memory footprint across scenarios in this benchmark harness.
- Core remains stable in `large` warm mode while `large` one-shot cold mode is intentionally harsh.

## Why Small/Medium Matter Most

- Most production APIs do not rebuild and dispatch against a 10k+ route tree on every request.
- `large` in this suite is primarily a stress test to expose scaling regressions.
- For production readiness, prioritize `small`/`medium` trends and regressions.

## Core Snapshot (Latest Full Run)

| Benchmark | Elapsed (ms) | Ops/s | Memory |
| --- | ---: | ---: | ---: |
| `core_loop:warm:small` | 28,253 | 176 971,10 | 2,0 MB |
| `core_request:cold:small` | 1 860,864 | 2 686,92 | 2,0 MB |
| `core_loop:warm:medium` | 26,811 | 186 490,62 | 12,0 MB |
| `core_request:cold:medium` | 18 736,168 | 266,86 | 12,0 MB |
| `core_loop:warm:large` | 91,927 | 54 391,28 | 52,0 MB |
| `core_request:cold:large` | 198 236,307 | 25,22 | 52,0 MB |

## Memory Context (Large Scenario)

| Router | Warm Memory |
| --- | ---: |
| `core_loop:warm:large` | 52,0 MB |
| `laravel:warm:large` | 110,0 MB |
| `symfony:warm:large` | 142,0 MB |
| `fastroute:warm:large` | 144,0 MB |
| `slim:warm:large` | 146,0 MB |

## Methodology Notes

- Core has two relevant modes:
  - `core_loop:warm:*`: compiled loop-style dispatch
  - `core_request:cold:*`: one-shot full rebuild + dispatch (harsh by design)
- Laravel warm setup uses compiled routes (`route:cache` equivalent) in this harness.
- Results are single-process microbenchmarks; compare trends over multiple runs for confidence.

## Regenerate

Run full benchmark:

```bash
php benchmarks/bin/benchmark_router.php
```

Run focused benchmark (example):

```bash
BENCH_FRAMEWORKS=core BENCH_SCENARIOS=small,medium BENCH_MODES=warm php benchmarks/bin/benchmark_router.php
```
