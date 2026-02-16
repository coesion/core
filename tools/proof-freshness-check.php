<?php

require_once __DIR__ . '/../classes/Loader.php';

/**
 * Validate freshness of proof artifacts.
 */
class ProofFreshnessCheckTool {

    /**
     * @param array $argv
     * @return int
     */
    public static function run(array $argv) {
        $opts = static::parseOptions($argv);

        $targets = [
            'audit' => dirname(__DIR__) . '/docs/AUDIT.md',
            'router_benchmark' => dirname(__DIR__) . '/docs/guides/Router-Benchmarks.md',
            'case_study' => dirname(__DIR__) . '/docs/guides/agent-case-study.baseline.json',
        ];

        $now = time();
        $failed = false;
        $report = [];

        foreach ($targets as $name => $path) {
            if (!is_file($path)) {
                $report[$name] = ['exists' => false, 'age_days' => null, 'path' => $path];
                $failed = true;
                continue;
            }

            $ageDays = (int) floor(($now - filemtime($path)) / 86400);
            $report[$name] = ['exists' => true, 'age_days' => $ageDays, 'path' => $path];
            if ($ageDays > $opts['max_days']) $failed = true;
        }

        fwrite(STDOUT, json_encode([
            'schema_version' => 1,
            'max_days' => $opts['max_days'],
            'report' => $report,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

        return $failed ? 1 : 0;
    }

    /**
     * @param array $argv
     * @return array
     */
    protected static function parseOptions(array $argv) {
        $opts = ['max_days' => 30];
        foreach (array_slice($argv, 1) as $arg) {
            if (strpos($arg, '--max-days=') === 0) {
                $opts['max_days'] = max(1, (int) trim(substr($arg, 11)));
            }
        }
        return $opts;
    }
}

exit(ProofFreshnessCheckTool::run($argv));
