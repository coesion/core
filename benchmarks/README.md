# Benchmarks

This sub-app is isolated from the main repository. Install dependencies here and run the benchmark script.

## Setup

```powershell
cd benchmarks
composer install
```

## Run

```powershell
php bin/benchmark_router.php
```

## Options

Override defaults with environment variables:

- `BENCH_ITERATIONS` (default 5000)
- `BENCH_WARMUP` (default 500)
- `BENCH_REPEATS` (default 1)
- `BENCH_RANDOM` (default 1; set to 0 for sequential)
- `BENCH_SCENARIOS` (comma-separated: small,medium,large)
- `BENCH_FRAMEWORKS` (comma-separated: core,laravel,symfony,fastroute,slim)
- `BENCH_MODES` (comma-separated: warm,cold)
- `BENCH_FILTER` (substring or regex like `/core_request:cold:.*/`)
- `BENCH_STATIC_SCALE` (default 1.0)
- `BENCH_DYNAMIC_SCALE` (default 1.0)

Example (fast run):

```powershell
$env:BENCH_ITERATIONS=300
$env:BENCH_WARMUP=50
$env:BENCH_REPEATS=2
$env:BENCH_SCENARIOS='small,medium'
$env:BENCH_FRAMEWORKS='core,symfony'
$env:BENCH_MODES='cold'
$env:BENCH_FILTER='core_request:cold'
$env:BENCH_STATIC_SCALE=0.5
$env:BENCH_DYNAMIC_SCALE=0.5
php bin/benchmark_router.php
```

CLI flags are also supported:

```powershell
php bin/benchmark_router.php --scenarios small --frameworks core --modes cold --filter "core_request:cold"
```

## Notes

- Runs repeats per scenario and reports averages.
- Uses randomized path selection by default.
- Produces warm vs cold results (cold includes setup time).
- Compares Core loop mode vs request mode.

Results are written to `benchmarks/results` as CSV and JSON.
