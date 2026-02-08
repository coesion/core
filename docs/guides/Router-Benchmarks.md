# Router Benchmarks

This report compares router dispatch performance. Laravel is used as the baseline.

**Last updated:** 2026-02-08 20:05:33

## Summary

- Baseline: Laravel
- Source: `bench_20260208_194928.json`
- PHP: 8.5.2
- Iterations: 500
- Repeats: 1

## Highlights

Fastest router per scenario (by ops/sec):

| Scenario | Router | Ops/sec |
| --- | --- | ---: |
| Warm / Small | fastroute | 5834305.67 |
| Warm / Medium | fastroute | 5747126.42 |
| Warm / Large | fastroute | 3276539.98 |
| Cold / Small | fastroute | 1755001.76 |
| Cold / Medium | fastroute | 577567.29 |
| Cold / Large | fastroute | 64200.51 |

## Warm / Small

| Router | Ops/sec | Delta vs Laravel | Relative |
| --- | ---: | ---: | ---: |
| laravel | 19528.66 | 0.00% | 1.00x |
| core_loop | 233633.94 | +1096.36% | 11.96x |
| core_loop_tree | 147158.37 | +653.55% | 7.54x |
| core_request | 206645.73 | +958.17% | 10.58x |
| **fastroute** | 5834305.67 | +29775.61% | 298.76x |
| slim | 73880.34 | +278.32% | 3.78x |
| symfony | 89860.18 | +360.15% | 4.60x |

## Warm / Medium

| Router | Ops/sec | Delta vs Laravel | Relative |
| --- | ---: | ---: | ---: |
| laravel | 1306.85 | 0.00% | 1.00x |
| core_loop | 68335.88 | +5129.03% | 52.29x |
| core_loop_tree | 65346.66 | +4900.30% | 50.00x |
| core_request | 242400.74 | +18448.41% | 185.48x |
| **fastroute** | 5747126.42 | +439667.82% | 4397.68x |
| slim | 54823.96 | +4095.11% | 41.95x |
| symfony | 9118.60 | +597.75% | 6.98x |

## Warm / Large

| Router | Ops/sec | Delta vs Laravel | Relative |
| --- | ---: | ---: | ---: |
| laravel | 24.45 | 0.00% | 1.00x |
| core_loop | 56399.03 | +230607.21% | 2307.07x |
| core_loop_tree | 8413.97 | +34318.37% | 344.18x |
| core_request | 255846.08 | +1046469.97% | 10465.70x |
| **fastroute** | 3276539.98 | +13402991.03% | 134030.91x |
| slim | 29460.81 | +120413.07% | 1205.13x |
| symfony | 583.42 | +2286.54% | 23.87x |

## Cold / Small

| Router | Ops/sec | Delta vs Laravel | Relative |
| --- | ---: | ---: | ---: |
| laravel | 14079.67 | 0.00% | 1.00x |
| core_loop | 146395.74 | +939.77% | 10.40x |
| core_loop_tree | 143057.42 | +916.06% | 10.16x |
| core_request | 83923.60 | +496.06% | 5.96x |
| **fastroute** | 1755001.76 | +12364.79% | 124.65x |
| slim | 56600.14 | +302.00% | 4.02x |
| symfony | 79833.95 | +467.02% | 5.67x |

## Cold / Medium

| Router | Ops/sec | Delta vs Laravel | Relative |
| --- | ---: | ---: | ---: |
| laravel | 2365.23 | 0.00% | 1.00x |
| core_loop | 24594.80 | +939.85% | 10.40x |
| core_loop_tree | 35658.00 | +1407.59% | 15.08x |
| core_request | 101781.17 | +4203.23% | 43.03x |
| **fastroute** | 577567.29 | +24319.11% | 244.19x |
| slim | 49921.12 | +2010.63% | 21.11x |
| symfony | 6725.08 | +184.33% | 2.84x |

## Cold / Large

| Router | Ops/sec | Delta vs Laravel | Relative |
| --- | ---: | ---: | ---: |
| laravel | 26.35 | 0.00% | 1.00x |
| core_loop | 2100.74 | +7872.91% | 79.73x |
| core_loop_tree | 4475.30 | +16885.05% | 169.85x |
| core_request | 13714.79 | +51951.58% | 520.52x |
| **fastroute** | 64200.51 | +243559.49% | 2436.59x |
| slim | 8974.12 | +33959.36% | 340.59x |
| symfony | 480.71 | +1724.43% | 18.24x |

Regenerate:

```bash
php tools/benchmark_report.php
```
