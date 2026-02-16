<?php

require_once __DIR__ . '/../classes/Loader.php';

/**
 * Emit machine-readable agent case-study artifacts.
 */
class AgentCaseStudyTool {

    /**
     * @param array $argv
     * @return int
     */
    public static function run(array $argv) {
        $opts = static::parseOptions($argv);
        if (!empty($opts['help'])) {
            fwrite(STDOUT, static::usage());
            return 0;
        }

        $payload = static::buildPayload($opts);
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            fwrite(STDERR, "[agent-case-study] cannot encode JSON\n");
            return 1;
        }

        if (!empty($opts['out'])) {
            file_put_contents($opts['out'], $json . "\n");
            fwrite(STDOUT, "Wrote {$opts['out']}\n");
            return 0;
        }

        fwrite(STDOUT, $json . "\n");
        return 0;
    }

    /**
     * @param array $opts
     * @return array
     */
    protected static function buildPayload(array $opts) {
        $steps = max(1, (int) $opts['steps']);
        $edits = max(1, (int) $opts['edits']);
        $failures = max(0, (int) $opts['failures']);
        $seconds = max(1, (int) $opts['seconds']);

        return [
            'schema_version' => 1,
            'framework' => [
                'name' => 'core',
                'version' => Core::version(),
            ],
            'task_type' => (string) $opts['task_type'],
            'steps' => $steps,
            'edits_count' => $edits,
            'time_to_green_sec' => $seconds,
            'failures_recovered' => $failures,
            'env_fingerprint' => [
                'php' => PHP_VERSION,
                'os' => PHP_OS_FAMILY,
                'sapi' => PHP_SAPI,
            ],
            'generated_at_utc' => gmdate('c'),
            'preset' => $opts['preset'],
        ];
    }

    /**
     * @param array $argv
     * @return array
     */
    protected static function parseOptions(array $argv) {
        $opts = [
            'task_type' => 'endpoint_addition',
            'steps' => 3,
            'edits' => 2,
            'failures' => 0,
            'seconds' => 120,
            'preset' => 'custom',
            'out' => '',
            'help' => false,
        ];

        foreach (array_slice($argv, 1) as $arg) {
            if ($arg === '--help' || $arg === '-h') {
                $opts['help'] = true;
                continue;
            }
            if (strpos($arg, '--task-type=') === 0) {
                $opts['task_type'] = trim(substr($arg, 12));
                continue;
            }
            if (strpos($arg, '--steps=') === 0) {
                $opts['steps'] = (int) trim(substr($arg, 8));
                continue;
            }
            if (strpos($arg, '--edits=') === 0) {
                $opts['edits'] = (int) trim(substr($arg, 8));
                continue;
            }
            if (strpos($arg, '--failures=') === 0) {
                $opts['failures'] = (int) trim(substr($arg, 11));
                continue;
            }
            if (strpos($arg, '--seconds=') === 0) {
                $opts['seconds'] = (int) trim(substr($arg, 10));
                continue;
            }
            if (strpos($arg, '--out=') === 0) {
                $opts['out'] = trim(substr($arg, 6));
                continue;
            }
            if (strpos($arg, '--preset=') === 0) {
                $preset = trim(substr($arg, 9));
                if ($preset === 'baseline') {
                    $opts['preset'] = 'baseline';
                    $opts['task_type'] = 'health_endpoint_creation';
                    $opts['steps'] = 4;
                    $opts['edits'] = 2;
                    $opts['failures'] = 0;
                    $opts['seconds'] = 90;
                }
            }
        }

        return $opts;
    }

    /**
     * @return string
     */
    protected static function usage() {
        return <<<TXT
Usage: php tools/agent-case-study.php [options]

Options:
  --preset=baseline             Emit baseline case-study payload
  --task-type=<value>           Task type label
  --steps=<n>                   Number of steps
  --edits=<n>                   Number of edits
  --failures=<n>                Recovered failures
  --seconds=<n>                 Time-to-green in seconds
  --out=<path>                  Write JSON output file
  --help, -h                    Show this help

TXT;
    }
}

exit(AgentCaseStudyTool::run($argv));
