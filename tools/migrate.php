<?php

require_once __DIR__ . '/../classes/Loader.php';

/**
 * Run deterministic migration workflows for agent automation.
 */
class MigrateTool {

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

        if ($opts['file']) {
            if (!is_file($opts['file'])) {
                fwrite(STDERR, "[migrate] file not found: {$opts['file']}\n");
                return 1;
            }
            require $opts['file'];
        }

        switch ($opts['action']) {
            case 'status':
                return static::print(Migration::status(), $opts['format']);
            case 'plan':
                return static::print(['plan' => Migration::plan($opts['to'])], $opts['format']);
            case 'apply':
                return static::print(['applied' => Migration::apply($opts['to'])], $opts['format']);
            case 'rollback':
                return static::print(['rolled_back' => Migration::rollback($opts['steps'])], $opts['format']);
            default:
                fwrite(STDERR, "[migrate] unknown action: {$opts['action']}\n");
                return 1;
        }
    }

    /**
     * @param array $argv
     * @return array
     */
    protected static function parseOptions(array $argv) {
        $opts = [
            'action' => 'status',
            'format' => 'json',
            'to' => 'latest',
            'steps' => 1,
            'file' => '',
            'help' => false,
        ];

        foreach (array_slice($argv, 1) as $arg) {
            if ($arg === '--help' || $arg === '-h') {
                $opts['help'] = true;
                continue;
            }
            if (strpos($arg, '--action=') === 0) {
                $opts['action'] = strtolower(trim(substr($arg, 9)));
                continue;
            }
            if (strpos($arg, '--format=') === 0) {
                $format = strtolower(trim(substr($arg, 9)));
                if (in_array($format, ['json', 'md'], true)) $opts['format'] = $format;
                continue;
            }
            if (strpos($arg, '--to=') === 0) {
                $opts['to'] = trim(substr($arg, 5));
                continue;
            }
            if (strpos($arg, '--steps=') === 0) {
                $opts['steps'] = max(1, (int) trim(substr($arg, 8)));
                continue;
            }
            if (strpos($arg, '--file=') === 0) {
                $opts['file'] = trim(substr($arg, 7));
                continue;
            }
        }

        return $opts;
    }

    /**
     * @param array $payload
     * @param string $format
     * @return int
     */
    protected static function print(array $payload, $format) {
        if ($format === 'md') {
            fwrite(STDOUT, "# Migration Output\n\n```json\n" . json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n```\n");
            return 0;
        }

        fwrite(STDOUT, json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n");
        return 0;
    }

    /**
     * @return string
     */
    protected static function usage() {
        return <<<TXT
Usage: php tools/migrate.php [options]

Options:
  --action=status|plan|apply|rollback   Action (default: status)
  --format=json|md                      Output format (default: json)
  --to=<migration-id|latest>            Target migration for plan/apply
  --steps=<n>                           Rollback steps (default: 1)
  --file=<path>                         PHP file that registers migrations
  --help, -h                            Show this help

Examples:
  php tools/migrate.php --action=status
  php tools/migrate.php --action=apply --to=latest --file=database/migrations.php
  php tools/migrate.php --action=rollback --steps=1 --file=database/migrations.php

TXT;
    }
}

exit(MigrateTool::run($argv));
