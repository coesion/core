<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../../classes/Loader.php';

function now_ms() {
  return hrtime(true) / 1e6;
}

function format_time_ms(float $ms) {
  $seconds = max(0, (int) round($ms / 1000));
  $hours = intdiv($seconds, 3600);
  $minutes = intdiv($seconds % 3600, 60);
  $secs = $seconds % 60;
  if ($hours > 0) {
    return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
  }
  return sprintf('%02d:%02d', $minutes, $secs);
}

function pick_path(array $paths, int $i, bool $random) {
  if ($random) {
    return $paths[array_rand($paths)];
  }
  return $paths[$i % count($paths)];
}

function progress_bar(float $current, int $total, string $label = '', ?float $started_at = null) {
  static $last_len = 0;
  $width = 30;
  $ratio = $total > 0 ? ($current / $total) : 1;
  $filled = (int) floor($ratio * $width);
  if ($filled > $width) { $filled = $width; }
  $bar = str_repeat('#', $filled) . str_repeat('-', $width - $filled);
  $percent = (int) floor($ratio * 100);
  if ($percent > 100) { $percent = 100; }
  $timing = '';
  if ($started_at !== null && $current > 0) {
    $elapsed_ms = max(0.001, now_ms() - $started_at);
    $remaining_steps = max(0.0, $total - $current);
    $eta_ms = ($elapsed_ms / $current) * $remaining_steps;
    $timing = ' | elapsed ' . format_time_ms($elapsed_ms) . ' | eta ' . format_time_ms($eta_ms);
  }
  $line = "[$bar] $percent% $label$timing";
  $pad_len = max(0, $last_len - strlen($line));
  echo "\r" . $line . str_repeat(' ', $pad_len);
  $last_len = strlen($line);
  if ($current >= $total) {
    $last_len = 0;
    echo "\n";
  }
}

function bench_run($name, callable $setup, callable $dispatch, array $paths, int $iterations, int $warmup, bool $random, bool $include_setup_time, ?callable $heartbeat = null) {
  if ($include_setup_time) {
    $start = now_ms();
    $setup();
  } else {
    $setup();
  }

  $total_ops = max(1, $warmup + $iterations);
  $last_beat = now_ms();

  for ($i = 0; $i < $warmup; $i++) {
    $dispatch(pick_path($paths, $i, $random));
    if ($heartbeat && (($i + 1) === $warmup || (now_ms() - $last_beat) >= 1000)) {
      $heartbeat(($i + 1) / $total_ops);
      $last_beat = now_ms();
    }
  }

  if (!$include_setup_time) {
    $start = now_ms();
  }

  for ($i = 0; $i < $iterations; $i++) {
    $dispatch(pick_path($paths, $i, $random));
    if ($heartbeat && (($i + 1) === $iterations || (now_ms() - $last_beat) >= 1000)) {
      $heartbeat(($warmup + $i + 1) / $total_ops);
      $last_beat = now_ms();
    }
  }

  $end = now_ms();
  $elapsed_ms = max(0.001, $end - $start);

  return [
    'name' => $name,
    'iterations' => $iterations,
    'elapsed_ms' => $elapsed_ms,
    'ops_per_sec' => ($iterations / ($elapsed_ms / 1000)),
    'memory' => memory_get_usage(true),
  ];
}

function bench_series($name, callable $setup, callable $dispatch, array $paths, int $iterations, int $warmup, bool $random, bool $include_setup_time, int $repeats, int &$step, int $total_steps, float $started_at) {
  $runs = [];
  for ($i = 0; $i < $repeats; $i++) {
    $label = $name . ' run ' . ($i + 1) . '/' . $repeats;
    $heartbeat = function(float $fraction) use (&$step, $total_steps, $label, $started_at) {
      $current = $step + min(1, max(0, $fraction));
      progress_bar($current, $total_steps, $label, $started_at);
    };
    $runs[] = bench_run($name, $setup, $dispatch, $paths, $iterations, $warmup, $random, $include_setup_time, $heartbeat);
    $step++;
    progress_bar($step, $total_steps, $label, $started_at);
  }

  $avg_elapsed = array_sum(array_column($runs, 'elapsed_ms')) / $repeats;
  $avg_ops = array_sum(array_column($runs, 'ops_per_sec')) / $repeats;
  $avg_mem = array_sum(array_column($runs, 'memory')) / $repeats;

  return [
    'name' => $name,
    'iterations' => $iterations,
    'elapsed_ms' => $avg_elapsed,
    'ops_per_sec' => $avg_ops,
    'memory' => $avg_mem,
    'repeats' => $repeats,
  ];
}

function build_routes(int $static_count, int $dynamic_count) {
  $routes = [];
  for ($i = 0; $i < $static_count; $i++) {
    $routes[] = ['/static/' . $i, false];
  }
  for ($i = 0; $i < $dynamic_count; $i++) {
    $routes[] = ['/user/:id/item' . $i, true];
  }
  return $routes;
}

function build_paths(int $static_count, int $dynamic_count) {
  $paths = [];
  for ($i = 0; $i < $static_count; $i++) {
    $paths[] = '/static/' . $i;
  }
  for ($i = 0; $i < $dynamic_count; $i++) {
    $paths[] = '/user/123/item' . $i;
  }
  return $paths;
}

function framework_pattern(array $route) {
  return str_replace(':id', '{id}', $route[0]);
}

function parse_cli_options(array $argv) {
  $options = [];
  $count = count($argv);
  for ($i = 1; $i < $count; $i++) {
    $arg = $argv[$i];
    if (!str_starts_with($arg, '--')) continue;
    $chunk = substr($arg, 2);
    if ($chunk === '') continue;
    $eq = strpos($chunk, '=');
    if ($eq !== false) {
      $key = substr($chunk, 0, $eq);
      $value = substr($chunk, $eq + 1);
      $options[$key] = $value;
      continue;
    }
    $next = $argv[$i + 1] ?? null;
    if ($next !== null && !str_starts_with($next, '--')) {
      $options[$chunk] = $next;
      $i++;
    } else {
      $options[$chunk] = '1';
    }
  }
  return $options;
}

function parse_csv_option(?string $value) {
  if ($value === null || trim($value) === '') return [];
  return array_values(array_filter(array_map(static function($item) {
    return strtolower(trim($item));
  }, explode(',', $value)), static function($item) {
    return $item !== '';
  }));
}

function benchmark_names_for_scenario(string $scenario_name) {
  return [
    'core_loop:warm:' . $scenario_name,
    'core_request:cold:' . $scenario_name,
    'laravel:warm:' . $scenario_name,
    'laravel:cold:' . $scenario_name,
    'symfony:warm:' . $scenario_name,
    'symfony:cold:' . $scenario_name,
    'fastroute:warm:' . $scenario_name,
    'fastroute:cold:' . $scenario_name,
    'slim:warm:' . $scenario_name,
    'slim:cold:' . $scenario_name,
  ];
}

function benchmark_framework(string $name) {
  $parts = explode(':', $name);
  $framework = strtolower($parts[0] ?? '');
  if ($framework === 'core_loop' || $framework === 'core_request') {
    return 'core';
  }
  return $framework;
}

function benchmark_mode(string $name) {
  $parts = explode(':', $name);
  return strtolower($parts[1] ?? '');
}

function benchmark_matches_filter(string $name, ?string $filter) {
  if ($filter === null || trim($filter) === '') return true;
  $filter = trim($filter);
  $regex_styled = strlen($filter) >= 3
    && $filter[0] === '/'
    && strrpos($filter, '/') > 0;
  if ($regex_styled) {
    return @preg_match($filter, $name) === 1;
  }
  return stripos($name, $filter) !== false;
}

function benchmark_selected(string $name, array $frameworks, array $modes, ?string $filter) {
  if (!benchmark_matches_filter($name, $filter)) return false;
  if (!empty($frameworks) && !in_array(benchmark_framework($name), $frameworks, true)) return false;
  if (!empty($modes) && !in_array(benchmark_mode($name), $modes, true)) return false;
  return true;
}

function selected_benchmark_count(array $scenarios, array $frameworks, array $modes, ?string $filter) {
  $selected_benchmarks = 0;
  foreach ($scenarios as $scenario) {
    foreach (benchmark_names_for_scenario($scenario['name']) as $name) {
      if (benchmark_selected($name, $frameworks, $modes, $filter)) $selected_benchmarks++;
    }
  }
  return $selected_benchmarks;
}

function progress_total_steps(int $selected_benchmark_count, int $repeats) {
  return max(1, $selected_benchmark_count * $repeats);
}

$scenarios = [
  ['name' => 'small', 'static' => 100, 'dynamic' => 30],
  ['name' => 'medium', 'static' => 1000, 'dynamic' => 300],
  ['name' => 'large', 'static' => 10000, 'dynamic' => 3000],
];

$cli = parse_cli_options($argv ?? []);
$repeats = (int) (getenv('BENCH_REPEATS') ?: 1);
$iterations = (int) (getenv('BENCH_ITERATIONS') ?: 5000);
$warmup = (int) (getenv('BENCH_WARMUP') ?: 500);
$random = (bool) (getenv('BENCH_RANDOM') !== '0');
$static_scale = (float) (getenv('BENCH_STATIC_SCALE') ?: 1);
$dynamic_scale = (float) (getenv('BENCH_DYNAMIC_SCALE') ?: 1);
$scenario_filter = $cli['scenarios'] ?? getenv('BENCH_SCENARIOS') ?: '';
$framework_filter = $cli['frameworks'] ?? getenv('BENCH_FRAMEWORKS') ?: '';
$mode_filter = $cli['modes'] ?? getenv('BENCH_MODES') ?: '';
$name_filter = $cli['filter'] ?? getenv('BENCH_FILTER') ?: '';
$scenario_names = parse_csv_option($scenario_filter);
$framework_names = parse_csv_option($framework_filter);
$mode_names = parse_csv_option($mode_filter);

$selected = [];
foreach ($scenarios as $scenario) {
  if (!empty($scenario_names) && !in_array($scenario['name'], $scenario_names, true)) {
    continue;
  }
  $selected[] = $scenario;
}

$selected_benchmark_count = selected_benchmark_count($selected, $framework_names, $mode_names, $name_filter);
$total_steps = progress_total_steps($selected_benchmark_count, $repeats);
$step = 0;
$started_at = now_ms();

if ($selected_benchmark_count === 0) {
  echo "No benchmarks selected. Use --filter/--frameworks/--modes/--scenarios or BENCH_* variables.\n";
  exit(0);
}

echo "Running benchmarks...\n";
progress_bar(0, $total_steps, 'starting', $started_at);

$results = [];

foreach ($selected as $scenario) {
  $static_count = max(1, (int) round($scenario['static'] * $static_scale));
  $dynamic_count = max(1, (int) round($scenario['dynamic'] * $dynamic_scale));

  $routes = build_routes($static_count, $dynamic_count);
  $paths = build_paths($static_count, $dynamic_count);

  $core_setup = function($loop_mode) use ($routes) {
    return function() use ($routes, $loop_mode) {
      Route::reset();
      Options::set('core.response.autosend', false);
      Options::set('core.route.append_echoed_text', false);
      Options::set('core.route.loop_mode', $loop_mode);
      Options::set('core.route.loop_dispatcher', 'fast');
      foreach ($routes as $r) {
        Route::get($r[0]);
      }
      if ($loop_mode) {
        Route::compile();
      }
    };
  };

  $core_loop_dispatch = function($path) {
    Route::dispatch($path, 'get');
  };

  $core_request_setup = function() {
  };
  $core_request_dispatch = function($path) use ($routes) {
    Route::reset();
    Options::set('core.response.autosend', false);
    Options::set('core.route.append_echoed_text', false);
    Options::set('core.route.loop_mode', false);
    foreach ($routes as $r) {
      Route::get($r[0]);
    }
    Route::dispatch($path, 'get');
  };

  $name = 'core_loop:warm:' . $scenario['name'];
  if (benchmark_selected($name, $framework_names, $mode_names, $name_filter)) {
    $results[] = bench_series($name, $core_setup(true), $core_loop_dispatch, $paths, $iterations, $warmup, $random, false, $repeats, $step, $total_steps, $started_at);
  }

  $name = 'core_request:cold:' . $scenario['name'];
  if (benchmark_selected($name, $framework_names, $mode_names, $name_filter)) {
    $results[] = bench_series($name, $core_request_setup, $core_request_dispatch, $paths, $iterations, 0, $random, true, $repeats, $step, $total_steps, $started_at);
  }

  $laravel_setup = function() use ($routes) {
    $container = new Illuminate\Container\Container();
    $container->bind(Illuminate\Routing\Contracts\CallableDispatcher::class, function($app) {
      return new Illuminate\Routing\CallableDispatcher($app);
    });
    $container->bind(Illuminate\Routing\Contracts\ControllerDispatcher::class, function($app) {
      return new Illuminate\Routing\ControllerDispatcher($app);
    });
    $events = new Illuminate\Events\Dispatcher($container);
    $router = new Illuminate\Routing\Router($events, $container);
    foreach ($routes as $r) {
      $pattern = framework_pattern($r);
      $router->get($pattern, function(){});
    }
    // Use Laravel compiled routes (route:cache equivalent) for warm benchmarks.
    $router->setCompiledRoutes($router->getRoutes()->compile());
    $GLOBALS['__router'] = $router;
  };
  $laravel_dispatch = function($path) {
    try {
      $router = $GLOBALS['__router'];
      $request = Illuminate\Http\Request::create($path, 'GET');
      $response = $router->dispatch($request);
      return $response && $response->getStatusCode() < 400;
    } catch (Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
      return false;
    } catch (Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e) {
      return false;
    }
  };

  $name = 'laravel:warm:' . $scenario['name'];
  if (benchmark_selected($name, $framework_names, $mode_names, $name_filter)) {
    $results[] = bench_series($name, $laravel_setup, $laravel_dispatch, $paths, $iterations, $warmup, $random, false, $repeats, $step, $total_steps, $started_at);
  }
  $name = 'laravel:cold:' . $scenario['name'];
  if (benchmark_selected($name, $framework_names, $mode_names, $name_filter)) {
    $results[] = bench_series($name, $laravel_setup, $laravel_dispatch, $paths, $iterations, 0, $random, true, $repeats, $step, $total_steps, $started_at);
  }

  $symfony_setup = function() use ($routes) {
    $collection = new Symfony\Component\Routing\RouteCollection();
    foreach ($routes as $idx => $r) {
      $pattern = framework_pattern($r);
      $route = new Symfony\Component\Routing\Route($pattern);
      $collection->add('r' . $idx, $route);
    }
    $context = new Symfony\Component\Routing\RequestContext('/');
    $matcher = new Symfony\Component\Routing\Matcher\UrlMatcher($collection, $context);
    $GLOBALS['__symfony_matcher'] = $matcher;
  };
  $symfony_dispatch = function($path) {
    try {
      $matcher = $GLOBALS['__symfony_matcher'];
      return $matcher->match($path) !== [];
    } catch (Symfony\Component\Routing\Exception\ResourceNotFoundException $e) {
      return false;
    } catch (Symfony\Component\Routing\Exception\MethodNotAllowedException $e) {
      return false;
    }
  };

  $name = 'symfony:warm:' . $scenario['name'];
  if (benchmark_selected($name, $framework_names, $mode_names, $name_filter)) {
    $results[] = bench_series($name, $symfony_setup, $symfony_dispatch, $paths, $iterations, $warmup, $random, false, $repeats, $step, $total_steps, $started_at);
  }
  $name = 'symfony:cold:' . $scenario['name'];
  if (benchmark_selected($name, $framework_names, $mode_names, $name_filter)) {
    $results[] = bench_series($name, $symfony_setup, $symfony_dispatch, $paths, $iterations, 0, $random, true, $repeats, $step, $total_steps, $started_at);
  }

  $fr_setup = function() use ($routes) {
    $dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) use ($routes) {
      foreach ($routes as $route) {
        $r->addRoute('GET', framework_pattern($route), 'h');
      }
    });
    $GLOBALS['__fr_dispatcher'] = $dispatcher;
  };
  $fr_dispatch = function($path) {
    $dispatcher = $GLOBALS['__fr_dispatcher'];
    $result = $dispatcher->dispatch('GET', $path);
    return isset($result[0]) && $result[0] === FastRoute\Dispatcher::FOUND;
  };

  $name = 'fastroute:warm:' . $scenario['name'];
  if (benchmark_selected($name, $framework_names, $mode_names, $name_filter)) {
    $results[] = bench_series($name, $fr_setup, $fr_dispatch, $paths, $iterations, $warmup, $random, false, $repeats, $step, $total_steps, $started_at);
  }
  $name = 'fastroute:cold:' . $scenario['name'];
  if (benchmark_selected($name, $framework_names, $mode_names, $name_filter)) {
    $results[] = bench_series($name, $fr_setup, $fr_dispatch, $paths, $iterations, 0, $random, true, $repeats, $step, $total_steps, $started_at);
  }

  $slim_setup = function() use ($routes) {
    $app = Slim\Factory\AppFactory::create();
    foreach ($routes as $route) {
      $pattern = framework_pattern($route);
      $app->get($pattern, function($request, $response){
        return $response;
      });
    }
    $GLOBALS['__slim_app'] = $app;
  };
  $slim_dispatch = function($path) {
    $app = $GLOBALS['__slim_app'];
    $request = (new Slim\Psr7\Factory\ServerRequestFactory())->createServerRequest('GET', $path);
    $response = $app->handle($request);
    return $response->getStatusCode() < 400;
  };

  $name = 'slim:warm:' . $scenario['name'];
  if (benchmark_selected($name, $framework_names, $mode_names, $name_filter)) {
    $results[] = bench_series($name, $slim_setup, $slim_dispatch, $paths, $iterations, $warmup, $random, false, $repeats, $step, $total_steps, $started_at);
  }
  $name = 'slim:cold:' . $scenario['name'];
  if (benchmark_selected($name, $framework_names, $mode_names, $name_filter)) {
    $results[] = bench_series($name, $slim_setup, $slim_dispatch, $paths, $iterations, 0, $random, true, $repeats, $step, $total_steps, $started_at);
  }
}

$csv = ["name,iterations,elapsed_ms,ops_per_sec,memory,repeats"];
foreach ($results as $row) {
  $csv[] = implode(',', [
    $row['name'],
    $row['iterations'],
    number_format($row['elapsed_ms'], 3, '.', ''),
    number_format($row['ops_per_sec'], 2, '.', ''),
    $row['memory'],
    $row['repeats'],
  ]);
}

$output_dir = __DIR__ . '/../results';
if (!is_dir($output_dir)) {
  mkdir($output_dir, 0775, true);
}

$ts = date('Ymd_His');
file_put_contents($output_dir . "/bench_$ts.csv", implode("\n", $csv));
file_put_contents($output_dir . "/bench_$ts.json", json_encode($results, JSON_PRETTY_PRINT));

echo "Benchmark complete. Results saved in benchmarks/results\n";
