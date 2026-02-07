<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../../classes/Loader.php';

function now_ms() {
  return hrtime(true) / 1e6;
}

function pick_path(array $paths, int $i, bool $random) {
  if ($random) {
    return $paths[array_rand($paths)];
  }
  return $paths[$i % count($paths)];
}

function progress_bar(int $current, int $total, string $label = '') {
  $width = 30;
  $ratio = $total > 0 ? ($current / $total) : 1;
  $filled = (int) floor($ratio * $width);
  if ($filled > $width) { $filled = $width; }
  $bar = str_repeat('#', $filled) . str_repeat('-', $width - $filled);
  $percent = (int) floor($ratio * 100);
  if ($percent > 100) { $percent = 100; }
  echo "\r[$bar] $percent% $label";
  if ($current >= $total) {
    echo "\n";
  }
}

function bench_run($name, callable $setup, callable $dispatch, array $paths, int $iterations, int $warmup, bool $random, bool $include_setup_time) {
  if ($include_setup_time) {
    $start = now_ms();
    $setup();
  } else {
    $setup();
  }

  for ($i = 0; $i < $warmup; $i++) {
    $dispatch(pick_path($paths, $i, $random));
  }

  if (!$include_setup_time) {
    $start = now_ms();
  }

  for ($i = 0; $i < $iterations; $i++) {
    $dispatch(pick_path($paths, $i, $random));
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

function bench_series($name, callable $setup, callable $dispatch, array $paths, int $iterations, int $warmup, bool $random, bool $include_setup_time, int $repeats, int &$step, int $total_steps) {
  $runs = [];
  for ($i = 0; $i < $repeats; $i++) {
    $runs[] = bench_run($name, $setup, $dispatch, $paths, $iterations, $warmup, $random, $include_setup_time);
    $step++;
    progress_bar($step, $total_steps, $name . ' run ' . ($i + 1) . '/' . $repeats);
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

function progress_total_steps(array $scenarios, int $repeats) {
  return count($scenarios) * 12 * $repeats;
}

$scenarios = [
  ['name' => 'small', 'static' => 100, 'dynamic' => 30],
  ['name' => 'medium', 'static' => 1000, 'dynamic' => 300],
  ['name' => 'large', 'static' => 10000, 'dynamic' => 3000],
];

$repeats = (int) (getenv('BENCH_REPEATS') ?: 5);
$iterations = (int) (getenv('BENCH_ITERATIONS') ?: 5000);
$warmup = (int) (getenv('BENCH_WARMUP') ?: 500);
$random = (bool) (getenv('BENCH_RANDOM') !== '0');
$static_scale = (float) (getenv('BENCH_STATIC_SCALE') ?: 1);
$dynamic_scale = (float) (getenv('BENCH_DYNAMIC_SCALE') ?: 1);
$scenario_filter = getenv('BENCH_SCENARIOS');
$scenario_names = $scenario_filter ? array_map('trim', explode(',', $scenario_filter)) : null;

$selected = [];
foreach ($scenarios as $scenario) {
  if ($scenario_names && !in_array($scenario['name'], $scenario_names, true)) {
    continue;
  }
  $selected[] = $scenario;
}

$total_steps = progress_total_steps($selected, $repeats);
$step = 0;

echo "Running benchmarks...\n";
progress_bar(0, $total_steps, 'starting');

$results = [];

foreach ($selected as $scenario) {
  $static_count = max(1, (int) round($scenario['static'] * $static_scale));
  $dynamic_count = max(1, (int) round($scenario['dynamic'] * $dynamic_scale));

  $routes = build_routes($static_count, $dynamic_count);
  $paths = build_paths($static_count, $dynamic_count);

  $core_setup = function($loop_mode, $dispatcher = 'fast') use ($routes) {
    return function() use ($routes, $loop_mode, $dispatcher) {
      Route::reset();
      Options::set('core.response.autosend', false);
      Options::set('core.route.append_echoed_text', false);
      Options::set('core.route.loop_mode', $loop_mode);
      Options::set('core.route.loop_dispatcher', $dispatcher);
      foreach ($routes as $r) {
        if ($r[1]) {
          Route::get($r[0])->rules(['id' => '\\d+']);
        } else {
          Route::get($r[0]);
        }
      }
      if ($loop_mode) {
        Route::compile();
      }
    };
  };

  $core_dispatch = function($path) {
    Route::dispatch($path, 'get');
  };

  $results[] = bench_series('core_loop:warm:' . $scenario['name'], $core_setup(true, 'fast'), $core_dispatch, $paths, $iterations, $warmup, $random, false, $repeats, $step, $total_steps);
  $results[] = bench_series('core_loop:cold:' . $scenario['name'], $core_setup(true, 'fast'), $core_dispatch, $paths, $iterations, 0, $random, true, $repeats, $step, $total_steps);
  $results[] = bench_series('core_loop_tree:warm:' . $scenario['name'], $core_setup(true, 'tree'), $core_dispatch, $paths, $iterations, $warmup, $random, false, $repeats, $step, $total_steps);
  $results[] = bench_series('core_loop_tree:cold:' . $scenario['name'], $core_setup(true, 'tree'), $core_dispatch, $paths, $iterations, 0, $random, true, $repeats, $step, $total_steps);
  $results[] = bench_series('core_request:warm:' . $scenario['name'], $core_setup(false), $core_dispatch, $paths, $iterations, $warmup, $random, false, $repeats, $step, $total_steps);
  $results[] = bench_series('core_request:cold:' . $scenario['name'], $core_setup(false), $core_dispatch, $paths, $iterations, 0, $random, true, $repeats, $step, $total_steps);

  $laravel_setup = function() use ($routes) {
    $container = new Illuminate\Container\Container();
    $events = new Illuminate\Events\Dispatcher($container);
    $router = new Illuminate\Routing\Router($events, $container);
    foreach ($routes as $r) {
      $pattern = str_replace(':id', '{id}', $r[0]);
      $router->get($pattern, function(){});
    }
    $GLOBALS['__router'] = $router;
  };
  $laravel_dispatch = function($path) {
    $router = $GLOBALS['__router'];
    $request = Illuminate\Http\Request::create($path, 'GET');
    $router->getRoutes()->match($request);
  };

  $results[] = bench_series('laravel:warm:' . $scenario['name'], $laravel_setup, $laravel_dispatch, $paths, $iterations, $warmup, $random, false, $repeats, $step, $total_steps);
  $results[] = bench_series('laravel:cold:' . $scenario['name'], $laravel_setup, $laravel_dispatch, $paths, $iterations, 0, $random, true, $repeats, $step, $total_steps);

  $symfony_setup = function() use ($routes) {
    $collection = new Symfony\Component\Routing\RouteCollection();
    foreach ($routes as $idx => $r) {
      $pattern = str_replace(':id', '{id}', $r[0]);
      $route = new Symfony\Component\Routing\Route($pattern, [], $r[1] ? ['id' => '\\d+'] : []);
      $collection->add('r' . $idx, $route);
    }
    $context = new Symfony\Component\Routing\RequestContext('/');
    $matcher = new Symfony\Component\Routing\Matcher\UrlMatcher($collection, $context);
    $GLOBALS['__symfony_matcher'] = $matcher;
  };
  $symfony_dispatch = function($path) {
    $matcher = $GLOBALS['__symfony_matcher'];
    $matcher->match($path);
  };

  $results[] = bench_series('symfony:warm:' . $scenario['name'], $symfony_setup, $symfony_dispatch, $paths, $iterations, $warmup, $random, false, $repeats, $step, $total_steps);
  $results[] = bench_series('symfony:cold:' . $scenario['name'], $symfony_setup, $symfony_dispatch, $paths, $iterations, 0, $random, true, $repeats, $step, $total_steps);

  $fr_setup = function() use ($routes) {
    $dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) use ($routes) {
      foreach ($routes as $route) {
        $r->addRoute('GET', $route[0], 'h');
      }
    });
    $GLOBALS['__fr_dispatcher'] = $dispatcher;
  };
  $fr_dispatch = function($path) {
    $dispatcher = $GLOBALS['__fr_dispatcher'];
    $dispatcher->dispatch('GET', $path);
  };

  $results[] = bench_series('fastroute:warm:' . $scenario['name'], $fr_setup, $fr_dispatch, $paths, $iterations, $warmup, $random, false, $repeats, $step, $total_steps);
  $results[] = bench_series('fastroute:cold:' . $scenario['name'], $fr_setup, $fr_dispatch, $paths, $iterations, 0, $random, true, $repeats, $step, $total_steps);

  $slim_setup = function() use ($routes) {
    $app = Slim\Factory\AppFactory::create();
    foreach ($routes as $route) {
      $pattern = str_replace(':id', '{id}', $route[0]);
      $app->get($pattern, function($request, $response){
        return $response;
      });
    }
    $GLOBALS['__slim_app'] = $app;
  };
  $slim_dispatch = function($path) {
    $app = $GLOBALS['__slim_app'];
    $request = (new Slim\Psr7\Factory\ServerRequestFactory())->createServerRequest('GET', $path);
    $app->handle($request);
  };

  $results[] = bench_series('slim:warm:' . $scenario['name'], $slim_setup, $slim_dispatch, $paths, $iterations, $warmup, $random, false, $repeats, $step, $total_steps);
  $results[] = bench_series('slim:cold:' . $scenario['name'], $slim_setup, $slim_dispatch, $paths, $iterations, 0, $random, true, $repeats, $step, $total_steps);
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
