<?php

require_once __DIR__ . '/../classes/Loader.php';

/**
 * Build and render deterministic agent-audit diagnostics.
 */
class AgentAuditTool {

    /**
     * Entry point.
     *
     * @param array $argv
     * @return int
     */
    public static function run(array $argv) {
        static::configureRuntime();
        $opts = static::parseOptions($argv);
        if (!empty($opts['help'])) {
            fwrite(STDOUT, static::usage());
            return 0;
        }

        $report = static::buildReport();
        $missing = static::collectMissingPaths($opts['fail_on_missing'], $report);
        if ($missing) {
            foreach ($missing as $path) {
                fwrite(STDERR, "[agent-audit] missing or falsy: $path\n");
            }
            return 1;
        }

        if ($opts['format'] === 'md') {
            fwrite(STDOUT, static::renderMarkdown($report));
            return 0;
        }

        $flags = JSON_UNESCAPED_SLASHES;
        if (!empty($opts['pretty'])) $flags |= JSON_PRETTY_PRINT;
        $json = json_encode($report, $flags);
        if ($json === false) {
            fwrite(STDERR, "[agent-audit] cannot encode JSON\n");
            return 1;
        }
        fwrite(STDOUT, $json . "\n");
        return 0;
    }

    /**
     * Keep structured output deterministic by silencing deprecation display noise.
     *
     * @return void
     */
    protected static function configureRuntime() {
        $level = error_reporting();
        error_reporting($level & ~E_DEPRECATED & ~E_USER_DEPRECATED);
        ini_set('display_errors', '0');
    }

    /**
     * Build deterministic report map.
     *
     * @return array
     */
    protected static function buildReport() {
        $caps = Introspect::capabilities();
        ksort($caps);
        if (isset($caps['core']) && is_array($caps['core'])) {
            static::ksortDeep($caps['core']);
        }

        return [
            'schema_version' => 1,
            'framework' => [
                'name' => 'core',
                'version' => Core::version(),
            ],
            'capabilities' => $caps,
            'counts' => [
                'classes' => count(Introspect::classes()),
                'routes' => count(Introspect::routes()),
            ],
        ];
    }

    /**
     * Parse CLI options.
     *
     * @param array $argv
     * @return array
     */
    protected static function parseOptions(array $argv) {
        $opts = [
            'format' => 'json',
            'pretty' => false,
            'help' => false,
            'fail_on_missing' => [],
        ];

        foreach (array_slice($argv, 1) as $arg) {
            if ($arg === '--help' || $arg === '-h') {
                $opts['help'] = true;
                continue;
            }
            if ($arg === '--pretty') {
                $opts['pretty'] = true;
                continue;
            }
            if (strpos($arg, '--format=') === 0) {
                $format = strtolower(trim(substr($arg, 9)));
                if (in_array($format, ['json', 'md'], true)) {
                    $opts['format'] = $format;
                }
                continue;
            }
            if (strpos($arg, '--fail-on-missing=') === 0) {
                $raw = trim(substr($arg, 18));
                foreach (explode(',', $raw) as $path) {
                    $path = trim($path);
                    if ($path !== '') $opts['fail_on_missing'][] = $path;
                }
                continue;
            }
        }

        return $opts;
    }

    /**
     * Find missing/falsy dot-notation paths.
     *
     * @param array $paths
     * @param array $data
     * @return array
     */
    protected static function collectMissingPaths(array $paths, array $data) {
        $missing = [];
        foreach ($paths as $path) {
            $value = static::dotGet($data, $path, '__MISSING__');
            if ($value === '__MISSING__' || !$value) {
                $missing[] = $path;
            }
        }
        return $missing;
    }

    /**
     * Dot-notation array getter.
     *
     * @param array $data
     * @param string $path
     * @param mixed $default
     * @return mixed
     */
    protected static function dotGet(array $data, $path, $default = null) {
        $current = $data;
        foreach (explode('.', $path) as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) return $default;
            $current = $current[$segment];
        }
        return $current;
    }

    /**
     * Deep sort associative arrays for stable output.
     *
     * @param array $data
     * @return void
     */
    protected static function ksortDeep(array &$data) {
        ksort($data);
        foreach ($data as &$value) {
            if (is_array($value) && static::isAssoc($value)) {
                static::ksortDeep($value);
            }
        }
    }

    /**
     * @param array $array
     * @return bool
     */
    protected static function isAssoc(array $array) {
        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Render markdown report.
     *
     * @param array $report
     * @return string
     */
    protected static function renderMarkdown(array $report) {
        $core = $report['capabilities']['core'];
        $md = [];
        $md[] = '# Agent Audit Snapshot';
        $md[] = '';
        $md[] = '- Framework: `core`';
        $md[] = '- Version: `' . $report['framework']['version'] . '`';
        $md[] = '- Zero runtime dependencies: `' . ($core['zero_runtime_dependencies'] ? 'true' : 'false') . '`';
        $md[] = '- Runtime dependency count: `' . $core['runtime_dependency_count'] . '`';
        $md[] = '- Route loop mode: `' . ($core['route']['loop_mode'] ? 'true' : 'false') . '`';
        $md[] = '- Route dispatcher: `' . $core['route']['loop_dispatcher'] . '`';
        $md[] = '- Auth booted: `' . ($core['auth']['booted'] ? 'true' : 'false') . '`';
        $md[] = '- Cache driver: `' . $core['cache']['driver'] . '`';
        $md[] = '- Registered scheduled jobs: `' . $core['schedule']['registered_jobs'] . '`';
        $md[] = '- Loaded classes: `' . $report['counts']['classes'] . '`';
        $md[] = '- Registered routes: `' . $report['counts']['routes'] . '`';
        $md[] = '';
        $md[] = '## Capabilities JSON';
        $md[] = '';
        $md[] = '```json';
        $md[] = json_encode($report['capabilities'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $md[] = '```';
        $md[] = '';
        return implode("\n", $md);
    }

    /**
     * Render usage text.
     *
     * @return string
     */
    protected static function usage() {
        return <<<TXT
Usage: php tools/agent-audit.php [options]

Options:
  --format=json|md                Output format (default: json)
  --pretty                        Pretty print JSON
  --fail-on-missing=<dot.path>    Exit 1 when path is missing/falsy (repeatable, comma-separated)
  --help, -h                      Show this help

Examples:
  php tools/agent-audit.php --format=json --pretty
  php tools/agent-audit.php --format=md
  php tools/agent-audit.php --fail-on-missing=capabilities.core.zero_runtime_dependencies

TXT;
    }
}

exit(AgentAuditTool::run($argv));
