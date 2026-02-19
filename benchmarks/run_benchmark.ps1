# Run full benchmark (may take longer)
$env:BENCH_ITERATIONS=5000
$env:BENCH_WARMUP=500
$env:BENCH_REPEATS=1
$env:BENCH_RANDOM=1
$env:BENCH_SCENARIOS='small,medium,large'
$env:BENCH_FRAMEWORKS=''
$env:BENCH_MODES=''
$env:BENCH_FILTER=''
$env:BENCH_STATIC_SCALE=1
$env:BENCH_DYNAMIC_SCALE=1
php bin/benchmark_router.php

# Run fast benchmark (smaller + quicker)
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
