<?php

/**
 * Route
 *
 * URL Router and action dispatcher.
 *
 * @package core
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

class Route {
    use Module,
        Events {
          on as onEvent;
        }

    public static $routes,
                  $base           = '',
                  $prefix         = [],
                  $group          = [],
                  $tags           = [],
                  $optimized_tree = [],
                  $compiled_tree  = null,
                  $compiled_dispatcher = null,
                  $compiled       = false,
                  $stats          = [
                    'dispatch'        => 0,
                    'matched'         => 0,
                    'unmatched'       => 0,
                    'static_checks'   => 0,
                    'dynamic_checks'  => 0,
                    'static_hit'      => 0,
                    'dynamic_hit'     => 0,
                    'regex_checks'    => 0,
                    'depth_total'     => 0,
                    'depth_max'       => 0,
                  ];

    protected     $URLPattern         = '',
                  $pattern            = '',
                  $matcher_pattern    = '',
                  $dynamic            = false,
                  $segments           = [],
                  $static_prefix      = [],
                  $param_names        = [],
                  $optimized_segments = [],
                  $dynamic_bucket     = null,
                  $dynamic_count      = 0,
                  $specificity        = 0,
                  $callback           = null,
                  $methods            = [],
                  $befores            = [],
                  $afters             = [],

                  $rules              = [],
                  $response           = '',
                  $tag                = '';


    /**
     * Create a new route definition. This method permits a fluid interface.
     *
     * @param string $URLPattern The URL pattern, can be used named parameters for variables extraction
     * @param $callback The callback to invoke on route match.
     * @param string $method The HTTP method for which the route must respond.
     * @return Route
     */
    public function __construct($URLPattern, $callback = null, $method='get'){
      $prefix  = static::$prefix ? rtrim(implode('',static::$prefix),'/') : '';
      $pattern = '/' . trim($URLPattern, "/");

      $this->callback         = $callback;

      // Adjust / optionality with dynamic patterns
      // Ex:  /test/(:a) ===> /test(/:a)
      $this->URLPattern       = str_replace('//','/',str_replace('/(','(/', rtrim("{$prefix}{$pattern}","/")));

      $this->dynamic          = $this->isDynamic($this->URLPattern);
      $this->optimized_segments = static::optimizedSegmentsFromPattern($this->URLPattern);
      if ($this->dynamic) {
        $this->param_names = static::paramNames($this->URLPattern);
      } else {
        $this->param_names = [];
      }

      $this->pattern          = $this->dynamic ? '' : $this->URLPattern;
      $this->matcher_pattern  = '';

      // We will use hash-checks, for O(1) complexity vs O(n)
      $this->methods[$method] = 1;
      return static::add($this);
    }

    /**
     * Check if route match on a specified URL and HTTP Method.
     * @param  [type] $URL The URL to check against.
     * @param  string $method The HTTP Method to check against.
     * @return boolean
     */
    public function match($URL, $method='get'){
      $method = strtolower($method);

      // * is an http method wildcard
      if (empty($this->methods[$method]) && empty($this->methods['*'])) return false;

      if ($this->dynamic) static::ensureDynamicRegexCompiled($this);

      return (bool) (
        $this->dynamic
           ? preg_match($this->matcher_pattern, '/'.trim($URL,'/'))
           : rtrim($URL,'/') == rtrim($this->pattern,'/')
      );
    }

    /**
     * Clears all stored routes definitions to pristine conditions.
     * @return void
     */
    public static function reset(){
      static::$routes = [];
      static::$base   = '';
      static::$prefix = [];
      static::$group  = [];
      static::$optimized_tree = [];
      static::$compiled_tree = null;
      static::$compiled_dispatcher = null;
      static::$compiled = false;
      static::$stats = [
        'dispatch'        => 0,
        'matched'         => 0,
        'unmatched'       => 0,
        'static_checks'   => 0,
        'dynamic_checks'  => 0,
        'static_hit'      => 0,
        'dynamic_hit'     => 0,
        'regex_checks'    => 0,
        'depth_total'     => 0,
        'depth_max'       => 0,
      ];
    }

    /**
     * Compile routes into a static trie with dynamic buckets.
     * @return array The compiled tree
     */
    public static function compile(){
      $root = static::compiledNode();
      $routes = [];
      $static_map = [];
      $dynamic_list = [];

      foreach ((array)static::$routes as $group => $list){
        foreach ((array)$list as $route) {
          if ($route instanceof self) $routes[] = $route;
        }
      }

      foreach ($routes as $route) {
        if ($route->dynamic) {
          static::collectDynamicRoute($dynamic_list, $route);
          static::insertDynamicRoute($root, $route);
        } else {
          static::collectStaticRoute($static_map, $route);
          static::insertStaticRoute($root, $route);
        }
      }

      static::sortDynamicRoutes($root);
      static::$compiled_tree = $root;
      static::$compiled_dispatcher = static::buildDispatcher($static_map, $dynamic_list);
      static::$compiled = true;
      return static::$compiled_dispatcher;
    }

    /**
     * Return router stats (only collected when core.route.debug is enabled).
     * @return array
     */
    public static function stats(){
      return static::$stats;
    }

    /**
     * Return a debug-friendly view of the compiled tree.
     * @return array
     */
    public static function debugTree(){
      $tree = static::$compiled_tree;
      if (!$tree) {
        return [
          'compiled' => false,
          'routes'   => static::countRoutes(),
        ];
      }
      return [
        'compiled' => true,
        'routes'   => static::countRoutes(),
        'tree'     => static::debugNode($tree),
      ];
    }

    protected static function compiledNode(){
      return [
        'static'         => [],
        'dynamic_routes' => [],
        'routes'         => [],
      ];
    }

    protected static function countRoutes(){
      $count = 0;
      foreach ((array)static::$routes as $group => $list){
        foreach ((array)$list as $route) {
          if ($route instanceof self) $count++;
        }
      }
      return $count;
    }

    protected static function debugNode($node){
      $children = [];
      foreach ($node['static'] as $seg => $child) {
        $children[$seg] = static::debugNode($child);
      }
      return [
        'static_children' => count($node['static']),
        'dynamic_routes'  => count($node['dynamic_routes']),
        'routes'          => count($node['routes']),
        'children'        => $children,
      ];
    }

    protected static function insertStaticRoute(array &$root, $route){
      $segments = $route->optimized_segments ?: array_filter(explode('/', trim($route->URLPattern,'/')), 'strlen');
      $node =& $root;
      foreach ($segments as $seg) {
        if (!isset($node['static'][$seg])) $node['static'][$seg] = static::compiledNode();
        $node =& $node['static'][$seg];
      }
      $node['routes'][] = $route;
    }

    protected static function insertDynamicRoute(array &$root, $route){
      static::ensureDynamicCompileMetadata($route);
      $prefix = $route->static_prefix;
      $node =& $root;
      foreach ($prefix as $seg) {
        if (!isset($node['static'][$seg])) $node['static'][$seg] = static::compiledNode();
        $node =& $node['static'][$seg];
      }

      $node['dynamic_routes'][] = [
        'route'       => $route,
        'matcher'     => $route->matcher_pattern,
        'methods'     => $route->methods,
        'specificity' => $route->specificity,
      ];
    }

    protected static function sortDynamicRoutes(array &$node){
      if (!empty($node['dynamic_routes'])) {
        usort($node['dynamic_routes'], function($a, $b){
          return $b['specificity'] <=> $a['specificity'];
        });
      }
      foreach ($node['static'] as &$child) {
        static::sortDynamicRoutes($child);
      }
    }

    protected static function collectStaticRoute(array &$static_map, $route){
      $path = rtrim($route->pattern, '/') ?: '/';
      foreach ($route->methods as $method => $v) {
        $static_map[$method][$path] = $route;
      }
    }

    protected static function collectDynamicRoute(array &$dynamic_list, $route){
      static::ensureDynamicCompileMetadata($route);
      static::ensureDynamicRegexCompiled($route);
      $bucket = $route->dynamic_bucket;
      $prefix = $bucket['prefix'];
      $hint = $bucket['hint'];
      $hint_pos = $bucket['hint_pos'];
      foreach ($route->methods as $method => $v) {
        if (!isset($dynamic_list[$method])) $dynamic_list[$method] = [];
        $dynamic_list[$method][] = [
          'prefix' => $prefix,
          'hint'   => $hint,
          'hint_pos' => $hint_pos,
          'route'  => $route,
        ];
      }
    }

    protected static function dynamicPrefix($pattern){
      $bucket = static::dynamicBucket($pattern);
      return $bucket['prefix'];
    }

    protected static function dynamicBucket($pattern){
      $segments = array_filter(explode('/', trim($pattern,'/')), 'strlen');
      return static::dynamicBucketFromSegments($segments, $pattern);
    }

    protected static function dynamicBucketFromSegments(array $segments, $pattern){
      $prefix = [];
      $found_dynamic = false;
      $hint = '';
      $hint_pos = null;
      $has_optional = strpos($pattern, '(') !== false;
      foreach ($segments as $idx => $seg) {
        $is_dynamic = static::isDynamic($seg) || strpos($seg,'(') !== false;
        if (!$found_dynamic && !$is_dynamic) {
          $prefix[] = $seg;
          continue;
        }
        $found_dynamic = true;
        if ($hint === '' && !$has_optional && !$is_dynamic) {
          $hint = $seg;
          $hint_pos = $idx;
          break;
        }
      }
      return [
        'prefix' => $prefix ? '/' . implode('/', $prefix) : '',
        'hint' => $hint,
        'hint_pos' => $hint_pos,
      ];
    }

    protected static function ensureDynamicCompileMetadata(self $route){
      if (!empty($route->segments)) {
        if (empty($route->static_prefix)) $route->static_prefix = static::routeStaticPrefix($route->segments);
        if ($route->specificity <= 0) $route->specificity = static::routeSpecificity($route->segments, $route->URLPattern);
        if ($route->dynamic_bucket === null) $route->dynamic_bucket = static::dynamicBucketFromSegments($route->segments, $route->URLPattern);
        return;
      }

      $route->segments = array_filter(explode('/', trim($route->URLPattern,'/')), 'strlen');
      $route->static_prefix = static::routeStaticPrefix($route->segments);
      $route->specificity = static::routeSpecificity($route->segments, $route->URLPattern);
      $route->dynamic_bucket = static::dynamicBucketFromSegments($route->segments, $route->URLPattern);
    }

    protected static function ensureDynamicRegexCompiled(self $route){
      if (!$route->dynamic) return;
      if ($route->pattern !== '' && $route->matcher_pattern !== '') return;
      $route->pattern = static::compilePatternAsRegex($route->URLPattern, $route->rules, true, $route->param_names);
      $route->matcher_pattern = static::compilePatternAsRegex($route->URLPattern, $route->rules, false, $route->param_names);
    }

    protected static function buildDispatcher(array $static_map, array $dynamic_list){
      $static_effective = [];
      $wildcard = $static_map['*'] ?? [];
      foreach ($static_map as $method => $map) {
        if ($method === '*') continue;
        $static_effective[$method] = $map + $wildcard;
      }
      if (!empty($wildcard)) {
        $static_effective['*'] = $wildcard;
      }
      $dispatcher = [
        'static'  => $static_map,
        'static_effective' => $static_effective,
        'dynamic' => [],
      ];
      $chunk = 20;

      foreach ($dynamic_list as $method => $list) {
        if (!empty($list)) {
          $hint_counts = [];
          foreach ($list as $entry) {
            if (empty($entry['hint'])) continue;
            $key = $entry['prefix'] . '|' . $entry['hint_pos'] . '|' . $entry['hint'];
            if (!isset($hint_counts[$key])) $hint_counts[$key] = 0;
            $hint_counts[$key]++;
          }
          if ($hint_counts) {
            foreach ($list as &$entry) {
              if (empty($entry['hint'])) continue;
              $key = $entry['prefix'] . '|' . $entry['hint_pos'] . '|' . $entry['hint'];
              if (($hint_counts[$key] ?? 0) < 3) {
                $entry['hint'] = '';
                $entry['hint_pos'] = null;
              }
            }
            unset($entry);
          }
        }
        foreach (static::chunkDynamicRoutes($list, $chunk) as $bundle) {
          $dispatcher['dynamic'][$method][] = static::buildRegexDispatcher($bundle['routes'], $bundle['prefix'], $bundle['hint'], $bundle['hint_pos']);
        }
      }
      return $dispatcher;
    }

    protected static function buildRegexDispatcher(array $routes, $prefix, $hint = '', $hint_pos = null){
      $branches = [];
      $meta = [];
      $group_indexes = [];
      $group_index = 1;
      foreach ($routes as $idx => $route) {
        $regex = static::compilePatternAsRegexNoNames($route->URLPattern, $route->rules, $route->param_names);
        $inner = static::regexInner($regex);
        $key = '__r' . $idx;
        $param_index = [];
        $param_names = $route->param_names;
        $index = $group_index + 1;
        foreach ($param_names as $param) {
          $param_index[$index] = $param;
          $index++;
        }
        $branches[] = '(?P<' . $key . '>' . $inner . ')';
        $group_indexes[] = $group_index;
        $meta[$group_index] = [
          'route'  => $route,
          'param_index' => $param_index,
        ];
        $group_index += 1 + count($param_names);
      }
      $regex = '#^(?:' . implode('|', $branches) . ')$#';
      return [
        'prefix' => $prefix,
        'hint' => $hint,
        'hint_pos' => $hint_pos,
        'regex' => $regex,
        'meta'  => $meta,
        'group_indexes' => $group_indexes,
      ];
    }

    protected static function chunkDynamicRoutes(array $list, $chunk){
      $bundles = [];
      $current_prefix = null;
      $current_hint = null;
      $current_hint_pos = null;
      $current_routes = [];

      foreach ($list as $entry) {
        $prefix = $entry['prefix'];
        $hint = $entry['hint'] ?? '';
        $hint_pos = $entry['hint_pos'] ?? null;
        $key = $prefix . '|' . $hint . '|' . ($hint_pos === null ? '' : $hint_pos);
        $current_key = $current_prefix === null ? null : ($current_prefix . '|' . $current_hint . '|' . ($current_hint_pos === null ? '' : $current_hint_pos));
        if ($current_prefix === null) {
          $current_prefix = $prefix;
          $current_hint = $hint;
          $current_hint_pos = $hint_pos;
          $current_key = $key;
        }
        if ($key !== $current_key || count($current_routes) >= $chunk) {
          if ($current_routes) {
            $bundles[] = [
              'prefix' => $current_prefix,
              'hint' => $current_hint,
              'hint_pos' => $current_hint_pos,
              'routes' => $current_routes,
            ];
          }
          $current_prefix = $prefix;
          $current_hint = $hint;
          $current_hint_pos = $hint_pos;
          $current_routes = [];
          $current_key = $key;
        }
        $current_routes[] = $entry['route'];
      }

      if ($current_routes) {
        $bundles[] = [
          'prefix' => $current_prefix,
          'hint' => $current_hint,
          'hint_pos' => $current_hint_pos,
          'routes' => $current_routes,
        ];
      }

      return $bundles;
    }

    protected static function regexInner($pattern){
      if (str_starts_with($pattern, '#^') && str_ends_with($pattern, '$#')) {
        return substr($pattern, 2, -2);
      }
      $trim = trim($pattern, '#');
      if (strpos($trim, '^') === 0) $trim = substr($trim, 1);
      if (substr($trim, -1) === '$') $trim = substr($trim, 0, -1);
      return $trim;
    }

    protected static function paramNames($pattern){
      if (!preg_match_all(pattern: '#:([a-zA-Z]\\w*)#', subject: $pattern, matches: $m)) return [];
      return $m[1];
    }

    protected static function compilePatternAsRegexNoNames($pattern, $rules=[], $param_names=null){
      $param_names = $param_names ?: static::paramNames($pattern);
      $prepared = str_replace(['.',')','*'],['\.',')?','.+'],$pattern);
      if (empty($param_names)) {
        return '#^' . $prepared . '$#';
      }
      return '#^'.preg_replace_callback(
        pattern: '#:([a-zA-Z]\\w*)#',
        callback: function($g) use (&$rules){
          $rule = isset($rules[$g[1]]) ? $rules[$g[1]] : '[^/]+';
          $rule = static::makeNonCapturing($rule);
          return '(' . '(?:' . $rule . ')' . ')';
        },
        subject: $prepared
      ).'$#';
    }

    protected static function splitPatternSegments($pattern){
      return array_values(array_filter(explode('/', trim($pattern,'/')), function($segment){
        return $segment !== '';
      }));
    }

    protected static function routeStaticPrefix(array $segments){
      $prefix = [];
      foreach ($segments as $seg) {
        if (static::isDynamic($seg) || strpos($seg,'(') !== false) break;
        $prefix[] = $seg;
      }
      return $prefix;
    }

    protected static function optimizedSegmentsFromPattern($pattern){
      $segments = [];
      $raw = preg_replace('#^(.+?)\(?:.+$#','$1',$pattern);
      foreach (explode('/', trim($raw,'/')) as $segment) {
        $segment = trim($segment, '(');
        if ($segment !== '') $segments[] = $segment;
      }
      return $segments;
    }

    protected static function countDynamicSegments(array $segments){
      $count = 0;
      foreach ($segments as $seg) {
        if (static::isDynamic($seg) || strpos($seg,'(') !== false) $count++;
      }
      return $count;
    }

    protected static function routeSpecificity(array $segments, $pattern){
      $dynamic_count = static::countDynamicSegments($segments);
      return ((count($segments) - $dynamic_count) * 100) + strlen($pattern);
    }

    protected static function makeNonCapturing($pattern){
      $len = strlen($pattern);
      $out = '';
      $in_class = false;
      for ($i = 0; $i < $len; $i++) {
        $ch = $pattern[$i];
        if ($ch === '\\') {
          $out .= $ch;
          if ($i + 1 < $len) {
            $out .= $pattern[$i + 1];
            $i++;
          }
          continue;
        }
        if ($ch === '[') {
          $in_class = true;
          $out .= $ch;
          continue;
        }
        if ($ch === ']' && $in_class) {
          $in_class = false;
          $out .= $ch;
          continue;
        }
        if ($ch === '(' && !$in_class) {
          $next = $i + 1 < $len ? $pattern[$i + 1] : '';
          if ($next === '?') {
            $next2 = $i + 2 < $len ? $pattern[$i + 2] : '';
            if ($next2 === 'P' || $next2 === '<') {
              $gt = strpos($pattern, '>', $i + 3);
              if ($gt !== false) {
                $out .= '(?:';
                $i = $gt;
                continue;
              }
            }
            $out .= $ch;
            continue;
          }
          $out .= '(?:';
          continue;
        }
        $out .= $ch;
      }
      return $out;
    }

    protected static function hasListeners(string $class, ?array $names = null){
      try {
        $getter = function() { return self::$_listeners; };
        $bound = $getter->bindTo(null, $class);
        $listeners = (array)$bound();
      } catch (Throwable $e) {
        return false;
      }
      if (empty($listeners)) return false;
      if ($names === null) return true;
      foreach ($names as $name) {
        if (!empty($listeners[$name])) return true;
      }
      return false;
    }

    protected static function hasRouteEvents(){
      if (static::hasListeners(static::class, ['start','before','after','end'])) return true;
      return static::hasListeners(Event::class, ['core.route.before','core.route.after','core.route.end']);
    }

    protected static function hasBeforeAfter($route){
      return !empty($route->befores) || !empty($route->afters);
    }

    /**
     * Run one of the mapped callbacks to a passed HTTP Method.
     * @param  array  $args The arguments to be passed to the callback
     * @param  string $method The HTTP Method requested.
     * @return array The callback response.
     */
    public function run(array $args, $method='get'){
      $method = strtolower($method);
      $append_echoed_text = Options::get('core.route.append_echoed_text',true);

      $fast_path = !$append_echoed_text
        && !static::hasBeforeAfter($this)
        && !static::hasRouteEvents();

      if (!$fast_path) {
        static::trigger('start', $this, $args, $method);
      }

      // Call direct befores
      if ( !$fast_path && $this->befores ) {
        // Reverse befores order
        foreach (array_reverse($this->befores) as $mw) {
          static::trigger('before', $this, $mw);
          Event::trigger('core.route.before', $this, $mw);
          ob_start();
          $mw_result  = call_user_func($mw);
          $raw_echoed = ob_get_clean();
          if ($append_echoed_text) Response::add($raw_echoed);
          if ( false  === $mw_result ) {
            return [''];
          } else {
            Response::add($mw_result);
          }
        }
      }

      $callback = (is_array($this->callback) && isset($this->callback[$method]))
                  ? $this->callback[$method]
                  : $this->callback;

      if (is_callable($callback) || is_a($callback, "View") ) {
        Response::type( Options::get('core.route.response_default_type', Response::TYPE_HTML) );

        ob_start();
        if (is_a($callback, "View")) {
          // Get the rendered view
          $view_results = (string)$callback;
        } else {
          $view_results = call_user_func_array($callback, $args);
        }
        $raw_echoed   = ob_get_clean();

        if ($append_echoed_text) Response::add($raw_echoed);
        Response::add($view_results);
      }

      // Apply afters
      if ( !$fast_path && $this->afters ) {
        foreach ($this->afters as $mw) {
          static::trigger('after', $this, $mw);
          Event::trigger('core.route.after', $this, $mw);
          ob_start();
          $mw_result  = call_user_func($mw);
          $raw_echoed = ob_get_clean();
          if ($append_echoed_text) Response::add($raw_echoed);
          if ( false  === $mw_result ) {
            return [''];
          } else {
            Response::add($mw_result);
          }
        }
      }

      if (!$fast_path) {
        static::trigger('end', $this, $args, $method);
        Event::trigger('core.route.end', $this);
      }

      return [Filter::with('core.route.response', Response::body())];
     }

    /**
     * Check if route match URL and HTTP Method and run if it is valid.
     * @param  [type] $URL The URL to check against.
     * @param  string $method The HTTP Method to check against.
     * @return array The callback response.
     */
    public function runIfMatch($URL, $method='get'){
      return $this->match($URL,$method) ? $this->run($this->extractArgs($URL),$method) : null;
    }

    /**
     * Start a route definition, default to HTTP GET.
     * @param  string $URLPattern The URL to match against, you can define named segments to be extracted and passed to the callback.
     * @param  $callback The callback to be invoked (with variables extracted from the route if present) when the route match the request URI.
     * @return Route
     */
    public static function on($URLPattern, $callback = null){
      return new Route($URLPattern,$callback);
    }

    /**
     * Start a route definition with HTTP Method via GET.
     * @param  string $URLPattern The URL to match against, you can define named segments to be extracted and passed to the callback.
     * @param  $callback The callback to be invoked (with variables extracted from the route if present) when the route match the request URI.
     * @return Route
     */
    public static function get($URLPattern, $callback = null){
      return (new Route($URLPattern,$callback))->via('get');
    }

    /**
     * Start a route definition with HTTP Method via POST.
     * @param  string $URLPattern The URL to match against, you can define named segments to be extracted and passed to the callback.
     * @param  $callback The callback to be invoked (with variables extracted from the route if present) when the route match the request URI.
     * @return Route
     */
    public static function post($URLPattern, $callback = null){
      return (new Route($URLPattern,$callback))->via('post');
    }

    /**
     * Start a route definition, for any HTTP Method (using * wildcard).
     * @param  string $URLPattern The URL to match against, you can define named segments to be extracted and passed to the callback.
     * @param  $callback The callback to be invoked (with variables extracted from the route if present) when the route match the request URI.
     * @return Route
     */
    public static function any($URLPattern, $callback = null){
      return (new Route($URLPattern,$callback))->via('*');
    }

    /**
     * Bind a callback to the route definition
     * @param  $callback The callback to be invoked (with variables extracted from the route if present) when the route match the request URI.
     * @return Route
     */
    public function & with($callback){
      $this->callback = $callback;
      return $this;
    }

    /**
     * Bind a middleware callback to invoked before the route definition
     * @param  callable $before The callback to be invoked ($this is binded to the route object).
     * @return Route
     */
    public function & before($callback){
      $this->befores[] = $callback;
      return $this;
    }

    /**
     * Bind a middleware callback to invoked after the route definition
     * @param  $callback The callback to be invoked ($this is binded to the route object).
     * @return Route
     */
    public function & after($callback){
      $this->afters[] = $callback;
      return $this;
    }

    /**
     * Defines the HTTP Methods to bind the route onto.
     *
     * Example:
     * <code>
     *  Route::on('/test')->via('get','post','delete');
     * </code>
     *
     * @return Route
     */
    public function & via(...$methods){
      $this->methods = [];
      foreach ($methods as $method){
        $this->methods[strtolower($method)] = true;
      }
      return $this;
    }

    /**
     * Defines the regex rules for the named parameter in the current URL pattern
     *
     * Example:
     * <code>
     *  Route::on('/proxy/:number/:url')
     *    ->rules([
     *      'number'  => '\d+',
     *      'url'     => '.+',
     *    ]);
     * </code>
     *
     * @param  array  $rules The regex rules
     * @return Route
     */
    public function & rules(array $rules){
      foreach ((array)$rules as $varname => $rule){
        $this->rules[$varname] = $rule;
      }
      if ($this->dynamic) {
        $this->pattern = '';
        $this->matcher_pattern = '';
      }
      return $this;
    }

    /**
     * Map a HTTP Method => callable array to a route.
     *
     * Example:
     * <code>
     *  Route::map('/test'[
     *      'get'     => function(){ echo "HTTP GET"; },
     *      'post'    => function(){ echo "HTTP POST"; },
     *      'put'     => function(){ echo "HTTP PUT"; },
     *      'delete'  => function(){ echo "HTTP DELETE"; },
     *    ]);
     * </code>
     *
     * @param  string $URLPattern The URL to match against, you can define named segments to be extracted and passed to the callback.
     * @param  array $callbacks The HTTP Method => callable map.
     * @return Route
     */
    public static function & map($URLPattern, $callbacks = []){
      $route           = new static($URLPattern);
      $route->callback = [];
      foreach ($callbacks as $method => $callback) {
        $method = strtolower($method);
        if (Request::method() !== $method) continue;
        $route->callback[$method] = $callback;
        $route->methods[$method]  = 1;
      }
      return $route;
    }

    /**
     * Assign a name tag to the route
     * @param  string $name The name tag of the route.
     * @return Route
     */
    public function & tag($name){
      if ($this->tag = $name) static::$tags[$name] =& $this;
      return $this;
    }

    /**
     * Reverse routing : obtain a complete URL for a named route with passed parameters
     * @param  array $params The parameter map of the route dynamic values.
     * @return URL
     */
    public function getURL($params = []){
      $params = (array)$params;
      return new URL(rtrim(preg_replace('(/+)','/',preg_replace_callback('(:(\w+))',function($m) use ($params){
        return isset($params[$m[1]]) ? $params[$m[1]].'/' : '';
      },strtr($this->URLPattern,['('=>'',')'=>'']))),'/')?:'/');
    }

    /**
     * Get a named route
     * @param  string $name The name tag of the route.
     * @return Route or false if not found
     */
    public static function tagged($name){
      return isset(static::$tags[$name]) ? static::$tags[$name] : false;
    }

   /**
     * Helper for reverse routing : obtain a complete URL for a named route with passed parameters
     * @param  string $name The name tag of the route.
     * @param  array $params The parameter map of the route dynamic values.
     * @return string
     */
    public static function URL($name, $params = []){
      return ($r = static::tagged($name)) ? $r-> getURL($params) : new URL();
    }

    /**
     * Compile an URL schema to a PREG regular expression.
     * @param  string $pattern The URL schema.
     * @return string The compiled PREG RegEx.
     */
    protected static function compilePatternAsRegex($pattern, $rules=[], $extract_params=true, $param_names=null){
      $param_names = $param_names ?: static::paramNames($pattern);
      $prepared = str_replace(['.',')','*'],['\.',')?','.+'],$pattern);
      if (empty($param_names)) {
        return '#^' . $prepared . '$#';
      }

      return '#^'.preg_replace_callback('#:([a-zA-Z]\w*)#',$extract_params
        // Extract named parameters
        ? function($g) use (&$rules){
            return '(?<' . $g[1] . '>' . (isset($rules[$g[1]])?$rules[$g[1]]:'[^/]+') .')';
          }
        // Optimized for matching
        : function($g) use (&$rules){
            return isset($rules[$g[1]]) ? $rules[$g[1]] : '[^/]+';
          },
      $prepared).'$#';
    }

    /**
     * Extract the URL schema variables from the passed URL.
     * @param  string  $pattern The URL schema with the named parameters
     * @param  string  $URL The URL to process, if omitted the current request URI will be used.
     * @param  boolean $cut If true don't limit the matching to the whole URL (used for group pattern extraction)
     * @return array The extracted variables
     */
    protected static function extractVariablesFromURL($pattern, $URL=null, $cut=false){
      $URL     = $URL ?: Request::URI();
      $pattern = $cut ? str_replace('$#','',$pattern).'#' : $pattern;
      $args    = [];
      if ( !preg_match(pattern: $pattern, subject: '/'.trim($URL,'/'), matches: $args) ) return false;
      foreach ($args as $key => $value) {
        if (false === is_string($key)) unset($args[$key]);
      }
      return $args;
    }


    public function extractArgs($URL){
      $args = [];
      if ( $this->dynamic ) {
        static::ensureDynamicRegexCompiled($this);
        preg_match($this->pattern, '/'.trim($URL,'/'), $args);
        foreach ($args as $key => $value) {
          if (false === is_string($key)) unset($args[$key]);
        }
      }
      return $args;
    }

    /**
     * Check if an URL schema need dynamic matching (regex).
     * @param  string  $pattern The URL schema.
     * @return boolean
     */
    protected static function isDynamic($pattern){
      return strlen($pattern) != strcspn($pattern,':(?[*+');
    }

    /**
     * Add a route to the internal route repository.
     * @param Route $route
     * @return Route
     */
    public static function add($route){
      if ($route instanceof self){
        static::$compiled = false;
        static::$compiled_tree = null;
        static::$compiled_dispatcher = null;

        // Add to tag map
        if ($route->tag) static::$tags[$route->tag] =& $route;

        // Optimize tree only for one-shot request mode.
        if (Options::get('core.route.auto_optimize', true) && !Options::get('core.route.loop_mode', false)){
          $base =& static::$optimized_tree;
          foreach (($route->optimized_segments ?: static::optimizedSegmentsFromPattern($route->URLPattern)) as $segment) {
            if (!isset($base[$segment])) $base[$segment] = [];
            $base =& $base[$segment];
          }
          $base[] =& $route;
        }
      }

      // Add route to active group
      if ( isset(static::$group[0]) ) static::$group[0]->add($route);

      return static::$routes[implode('', static::$prefix)][] = $route;
    }

    /**
     * Define a route group, if not immediately matched internal code will not be invoked.
     * @param  string $prefix The url prefix for the internal route definitions.
     * @param  string $callback This callback is invoked on $prefix match of the current request URI.
     */
    public static function group($prefix, $callback){
      $loop_mode = Options::get('core.route.loop_mode', false);
      if ($loop_mode) {
        static::$prefix[] = $prefix;
        if (empty(static::$group)) static::$group = [];
        array_unshift(static::$group, $group = new RouteGroup());

        call_user_func($callback);

        array_shift(static::$group);
        array_pop(static::$prefix);
        if (empty(static::$prefix)) static::$prefix = [''];
        return $group;
      }

      // Skip definition if current request doesn't match group.
      $pre_prefix = rtrim(implode('',static::$prefix),'/');
      $URI   = Request::URI();
      $args  = [];
      $group = false;

      switch (true) {

        // Dynamic group
        case static::isDynamic($prefix) :
          $args = static::extractVariablesFromURL($prx=static::compilePatternAsRegex("$pre_prefix$prefix"), null, true);
          if ( $args !== false ) {
            // Burn-in $prefix as static string
            $partial = preg_match_all(str_replace('$#', '#', $prx), $URI, $partial) ? $partial[0][0] : '';
            $prefix = $partial ? preg_replace('#^'.implode('',static::$prefix).'#', '', $partial) : $prefix;
          }

        // Static group
        case ( 0 === strpos("$URI/", "$pre_prefix$prefix/") )
             || ( ! Options::get('core.route.pruning', true) ) :

          static::$prefix[] = $prefix;
          if (empty(static::$group)) static::$group = [];
          array_unshift(static::$group, $group = new RouteGroup());

          // Call the group body function
          call_user_func_array($callback, $args ?: []);

          array_shift(static::$group);
          array_pop(static::$prefix);
          if (empty(static::$prefix)) static::$prefix = [''];
        break;

      }

      return $group ?: new RouteGroup();
    }

    public static function exitWithError($code, $message="Application Error"){
      Response::error($code,$message);
      Response::send();
      exit;
    }

    /**
     * Start the route dispatcher and resolve the URL request.
     * @param  string $URL The URL to match onto.
     * @param  string $method The HTTP method.
     * @param  bool $return_route If setted to true it will *NOT* execute the route but it will return her.
     * @return boolean true if a route callback was executed.
     */
    public static function dispatch($URL=null, $method=null, $return_route=false){
        if (!$URL)     $URL     = Request::URI();
        if (!$method)  $method  = Request::method();
        $method = strtolower($method);
        $debug = Options::get('core.route.debug', false);
        if ($debug) static::$stats['dispatch']++;

        Event::trigger('core.log', 'route.dispatch', ['url' => $URL, 'method' => $method]);

        $autosend = Options::get('core.response.autosend',true);
        try {
          $loop_mode = Options::get('core.route.loop_mode', false);
          if ($loop_mode) {
            $dispatcher_mode = Options::get('core.route.loop_dispatcher', 'fast');
            if ($dispatcher_mode === 'tree') {
              return static::dispatchCompiledTree($URL, $method, $return_route, $debug);
            }
            if (!static::$compiled || empty(static::$compiled_dispatcher)) static::compile();
            $dispatcher = static::$compiled_dispatcher;
            $path = rtrim($URL, '/') ?: '/';

            if ($debug) {
              static::$stats['static_checks']++;
            }
            $static_effective = $dispatcher['static_effective'][$method] ?? ($dispatcher['static_effective']['*'] ?? ($dispatcher['static']['*'] ?? []));
            $route = $static_effective[$path] ?? null;
            if ($route) {
              if ($return_route){
                return $route;
              } else {
                $route->run($route->extractArgs($URL),$method);
                if ($debug) {
                  static::$stats['matched']++;
                  static::$stats['static_hit']++;
                }
                return true;
              }
            }

            $method_dispatchers = $dispatcher['dynamic'][$method] ?? [];
            $wildcard_dispatchers = $dispatcher['dynamic']['*'] ?? [];
            $path_segments = null;

            $dispatch_lists = [$method_dispatchers, $wildcard_dispatchers];
            foreach ($dispatch_lists as $dispatchers) foreach ($dispatchers as $entry) {
              $prefix = $entry['prefix'];
              if ($prefix !== '' && $path !== $prefix && strpos($path, $prefix . '/') !== 0) {
                continue;
              }
              $hint = $entry['hint'] ?? '';
              if ($hint !== '') {
                if ($path_segments === null) {
                  $path_segments = array_values(array_filter(explode('/', trim($path,'/')), static function($segment){
                    return $segment !== '';
                  }));
                }
                $hint_pos = $entry['hint_pos'];
                if (!isset($path_segments[$hint_pos]) || $path_segments[$hint_pos] !== $hint) {
                  continue;
                }
              }
              if ($debug) {
                static::$stats['dynamic_checks']++;
                static::$stats['regex_checks']++;
              }
              if (!preg_match($entry['regex'], $path, $matches)) {
                continue;
              }
              foreach ($entry['group_indexes'] as $group_index) {
                if (!array_key_exists($group_index, $matches) || $matches[$group_index] === '') {
                  continue;
                }
                $meta = $entry['meta'][$group_index];
                $route = $meta['route'];
                if ($return_route){
                  return $route;
                } else {
                  $args = [];
                  foreach ($meta['param_index'] as $index => $param) {
                    if (isset($matches[$index])) $args[$param] = $matches[$index];
                  }
                  $route->run($args, $method);
                  if ($debug) {
                    static::$stats['matched']++;
                    static::$stats['dynamic_hit']++;
                  }
                  return true;
                }
              }
            }
          } else if (empty(static::$optimized_tree)) {
            foreach ((array)static::$routes as $group => $routes){
                foreach ($routes as $route) {
                    if ($debug) static::$stats['static_checks']++;
                    if ($route instanceof self && $route->match($URL,$method)){
                      if ($return_route){
                        return $route;
                      } else {
                        $route->run($route->extractArgs($URL),$method);
                        if ($debug) static::$stats['matched']++;
                        return true;
                      }
                    }
                }
            }
          } else {
            $routes =& static::$optimized_tree;
            foreach (explode('/',trim($URL,'/')) as $segment) {
              if (is_array($routes) && isset($routes[$segment])) $routes =& $routes[$segment];
                // Root-level dynamic routes Ex: "/:param"
                else if (is_array($routes) && isset($routes[''])) $routes =& $routes[''];
              else break;
            }
            if (is_array($routes) && isset($routes[0]) && !is_array($routes[0])) foreach ((array)$routes as $route) {
                if ($debug) static::$stats['static_checks']++;
                if ($route instanceof self && $route->match($URL, $method)){
                      if ($return_route){
                        return $route;
                      } else {
                        $route->run($route->extractArgs($URL),$method);
                        if ($debug) static::$stats['matched']++;
                        return true;
                      }
                }
            }
          }

          Response::status(404, '404 Resource not found.');
          foreach (array_filter(array_merge(
            (static::trigger(404)?:[]),
            (Event::trigger(404)?:[])
          )) as $res){
             Response::add($res);
          }
          if ($debug) static::$stats['unmatched']++;
          return false;
        } finally {
          if ($autosend){
            Response::send();
          }
        }
    }

    protected static function dispatchCompiledTree($URL, $method, $return_route, $debug){
      if (!static::$compiled || empty(static::$compiled_tree)) static::compile();
      $node = static::$compiled_tree;
      $depth = 0;

      foreach (explode('/',trim($URL,'/')) as $segment) {
        if (isset($node['static'][$segment])) {
          $node = $node['static'][$segment];
          $depth++;
        } else {
          break;
        }
      }

      if ($debug) {
        static::$stats['depth_total'] += $depth;
        static::$stats['depth_max'] = max(static::$stats['depth_max'], $depth);
      }

      foreach ((array)$node['routes'] as $route) {
        if ($debug) static::$stats['static_checks']++;
        if ($route instanceof self && $route->match($URL, $method)){
          if ($return_route){
            return $route;
          } else {
            $route->run($route->extractArgs($URL),$method);
            if ($debug) static::$stats['matched']++;
            return true;
          }
        }
      }

      foreach ((array)$node['dynamic_routes'] as $entry) {
        if ($debug) static::$stats['dynamic_checks']++;
        $route = $entry['route'];
        if ($route instanceof self && $route->match($URL, $method)){
          if ($return_route){
            return $route;
          } else {
            $route->run($route->extractArgs($URL),$method);
            if ($debug) static::$stats['matched']++;
            return true;
          }
        }
      }

      return false;
    }

    public function push($links, $type = 'text'){
      Response::push($links, $type);
      return $this;
    }

}

class RouteGroup {
  protected $routes;

  public function __construct(){
    $this->routes = new SplObjectStorage;
    return Route::add($this);
  }

  public function has($r){
    return $this->routes->offsetExists($r);
  }

  public function add($r){
    $this->routes[$r] = true;
    return $this;
  }

  public function remove($r){
    if ($this->routes->offsetExists($r)) $this->routes->offsetUnset($r);
    return $this;
  }

  public function before($callbacks){
    foreach ($this->routes as $route){
      $route->before($callbacks);
    }
    return $this;
  }

  public function after($callbacks){
    foreach ($this->routes as $route){
      $route->after($callbacks);
    }
    return $this;
  }

  public function push($links, $type = 'text'){
    Response::push($links, $type);
    return $this;
  }

}

