<?php

/**
 * Benchmark Report Generator
 *
 * Generates router benchmark comparison tables (Laravel baseline).
 *
 * @package core
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

/**
 * Parse CLI arguments for --file and --out.
 * @return array
 */
function parseArgs(): array {
  $args = $GLOBALS['argv'] ?? [];
  array_shift($args);
  $out = [
    'file' => null,
    'out' => null,
  ];
  foreach ($args as $arg) {
    if (strpos($arg, '--file=') === 0) {
      $out['file'] = substr($arg, 7);
    } elseif (strpos($arg, '--out=') === 0) {
      $out['out'] = substr($arg, 6);
    }
  }
  return $out;
}

/**
 * Resolve the latest benchmark json file by mtime.
 * @param string $dir
 * @return string
 */
function latestBenchmarkFile(string $dir): string {
  $files = glob($dir . DIRECTORY_SEPARATOR . 'bench_*.json');
  if (!$files) return '';
  usort($files, function ($a, $b) {
    return filemtime($b) <=> filemtime($a);
  });
  return $files[0] ?? '';
}

/**
 * Parse name into router, mode, size.
 * @param string $name
 * @return array
 */
function parseName(string $name): array {
  $parts = explode(':', $name);
  return [
    'router' => $parts[0] ?? '',
    'mode' => $parts[1] ?? '',
    'size' => $parts[2] ?? '',
  ];
}

/**
 * Format ops per second for display.
 * @param float $value
 * @return string
 */
function formatOps(float $value): string {
  return number_format($value, 2, '.', '');
}

/**
 * Format percent delta vs baseline.
 * @param float $value
 * @return string
 */
function formatDelta(float $value): string {
  $sign = $value > 0 ? '+' : '';
  return $sign . number_format($value, 2, '.', '') . '%';
}

/**
 * Format relative multiplier vs baseline.
 * @param float $value
 * @return string
 */
function formatRatio(float $value): string {
  return number_format($value, 2, '.', '') . 'x';
}

/**
 * Order routers for output.
 * @param array $routers
 * @return array
 */
function orderRouters(array $routers): array {
  $laravel = [];
  $core = [];
  $other = [];
  foreach ($routers as $router) {
    if ($router === 'laravel') {
      $laravel[] = $router;
    } elseif (strpos($router, 'core_') === 0) {
      $core[] = $router;
    } else {
      $other[] = $router;
    }
  }
  sort($core, SORT_STRING);
  sort($other, SORT_STRING);
  return array_merge($laravel, $core, $other);
}

/**
 * Find the fastest router in a scenario.
 * @param array $rows
 * @return array
 */
function fastestRouter(array $rows): array {
  $best = ['router' => '', 'ops' => 0.0];
  foreach ($rows as $row) {
    if ($row['ops'] > $best['ops']) {
      $best = ['router' => $row['router'], 'ops' => $row['ops']];
    }
  }
  return $best;
}

$root = dirname(__DIR__);
$benchDir = $root . DIRECTORY_SEPARATOR . 'benchmarks' . DIRECTORY_SEPARATOR . 'results';
$defaultOut = $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'guides' . DIRECTORY_SEPARATOR . 'Router-Benchmarks.md';

$args = parseArgs();
$input = $args['file'] ?: latestBenchmarkFile($benchDir);
$output = $args['out'] ?: $defaultOut;

if (!$input || !is_file($input)) {
  fwrite(STDERR, "Benchmark json not found. Use --file=PATH\n");
  exit(1);
}

$raw = file_get_contents($input);
$data = json_decode($raw, true);
if (!is_array($data)) {
  fwrite(STDERR, "Invalid json file: $input\n");
  exit(1);
}

$records = [];
$iterations = [];
$repeats = [];
$memory = [];
foreach ($data as $row) {
  if (empty($row['name'])) continue;
  $info = parseName($row['name']);
  if (!$info['router'] || !$info['mode'] || !$info['size']) continue;
  $records[] = [
    'router' => $info['router'],
    'mode' => $info['mode'],
    'size' => $info['size'],
    'ops' => (float)($row['ops_per_sec'] ?? 0),
    'iterations' => (int)($row['iterations'] ?? 0),
    'repeats' => (int)($row['repeats'] ?? 0),
    'memory' => (int)($row['memory'] ?? 0),
  ];
  if (!empty($row['iterations'])) $iterations[$row['iterations']] = true;
  if (!empty($row['repeats'])) $repeats[$row['repeats']] = true;
  if (!empty($row['memory'])) $memory[$row['memory']] = true;
}

if (!$records) {
  fwrite(STDERR, "No benchmark rows parsed from $input\n");
  exit(1);
}

$scenarios = [];
foreach ($records as $row) {
  $key = $row['mode'] . ':' . $row['size'];
  if (!isset($scenarios[$key])) $scenarios[$key] = [];
  $scenarios[$key][] = $row;
}

$sizes = ['small', 'medium', 'large'];
$modes = ['warm', 'cold'];

$lines = [];
$lines[] = '# Router Benchmarks';
$lines[] = '';
$lines[] = 'This report compares router dispatch performance. Laravel is used as the baseline.';
$lines[] = '';
$lines[] = '**Last updated:** ' . date('Y-m-d H:i:s');
$lines[] = '';
$lines[] = '## Summary';
$lines[] = '';
$lines[] = '- Baseline: Laravel';
$lines[] = '- Source: `' . basename($input) . '`';
$lines[] = '- PHP: ' . PHP_VERSION;
if ($iterations) $lines[] = '- Iterations: ' . implode(', ', array_keys($iterations));
if ($repeats) $lines[] = '- Repeats: ' . implode(', ', array_keys($repeats));
$lines[] = '';
$lines[] = '## Highlights';
$lines[] = '';
$lines[] = 'Fastest router per scenario (by ops/sec):';
$lines[] = '';
$lines[] = '| Scenario | Router | Ops/sec |';
$lines[] = '| --- | --- | ---: |';

$highlights = [];
$sections = [];

foreach ($modes as $mode) {
  foreach ($sizes as $size) {
    $key = $mode . ':' . $size;
    if (empty($scenarios[$key])) continue;
    $rows = $scenarios[$key];
    $baseline = null;
    foreach ($rows as $row) {
      if ($row['router'] === 'laravel') {
        $baseline = $row['ops'];
        break;
      }
    }
    $routers = array_values(array_unique(array_map(function ($row) {
      return $row['router'];
    }, $rows)));
    $routers = orderRouters($routers);
    $fastest = fastestRouter($rows);

    $highlights[] = '| ' . ucfirst($mode) . ' / ' . ucfirst($size) . ' | ' . $fastest['router'] . ' | ' . formatOps($fastest['ops']) . ' |';

    $sections[] = '## ' . ucfirst($mode) . ' / ' . ucfirst($size);
    $sections[] = '';
    $sections[] = '| Router | Ops/sec | Delta vs Laravel | Relative |';
    $sections[] = '| --- | ---: | ---: | ---: |';

    $byRouter = [];
    foreach ($rows as $row) {
      $byRouter[$row['router']] = $row['ops'];
    }

    foreach ($routers as $router) {
      $ops = $byRouter[$router] ?? 0.0;
      $routerLabel = $router;
      if ($fastest['router'] === $router) {
        $routerLabel = '**' . $router . '**';
      }
      if ($baseline && $baseline > 0) {
        $delta = ($ops - $baseline) / $baseline * 100;
        $ratio = $ops / $baseline;
        $deltaText = formatDelta($delta);
        $ratioText = formatRatio($ratio);
      } else {
        $deltaText = 'n/a';
        $ratioText = 'n/a';
      }
      $sections[] = '| ' . $routerLabel . ' | ' . formatOps($ops) . ' | ' . $deltaText . ' | ' . $ratioText . ' |';
    }
    $sections[] = '';
  }
}

$lines = array_merge($lines, $highlights);
$lines[] = '';
$lines = array_merge($lines, $sections);

$lines[] = 'Regenerate:';
$lines[] = '';
$lines[] = '```bash';
$lines[] = 'php tools/benchmark_report.php';
$lines[] = '```';
$lines[] = '';

file_put_contents($output, implode("\n", $lines));
fwrite(STDOUT, "Report written to $output\n");
