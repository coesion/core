<?php

require_once __DIR__ . '/../classes/Loader.php';

/**
 * Build deterministic snapshots for agent workflows and CI diff gates.
 */
class AgentSnapshotTool {

    /**
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

        $snapshot = static::buildSnapshot($opts['type']);
        if ($snapshot === null) {
            fwrite(STDERR, "[agent-snapshot] unknown type: {$opts['type']}\n");
            return 1;
        }

        if ($opts['fail_on_diff']) {
            $status = static::checkDiff($opts['fail_on_diff'], $snapshot);
            if ($status !== 0) return $status;
        }

        if ($opts['format'] === 'md') {
            fwrite(STDOUT, static::renderMarkdown($opts['type'], $snapshot));
            return 0;
        }

        $flags = JSON_UNESCAPED_SLASHES;
        if (!empty($opts['pretty'])) $flags |= JSON_PRETTY_PRINT;
        $json = json_encode($snapshot, $flags);
        if ($json === false) {
            fwrite(STDERR, "[agent-snapshot] cannot encode JSON\n");
            return 1;
        }
        fwrite(STDOUT, $json . "\n");
        return 0;
    }

    /**
     * @return void
     */
    protected static function configureRuntime() {
        $level = error_reporting();
        error_reporting($level & ~E_DEPRECATED & ~E_USER_DEPRECATED);
        ini_set('display_errors', '0');
    }

    /**
     * @param string $type
     * @return array|null
     */
    protected static function buildSnapshot($type) {
        switch ($type) {
            case 'routes':
                return [
                    'schema_version' => 1,
                    'type' => 'routes',
                    'items' => Introspect::snapshotRoutes(),
                ];
            case 'schema':
                return [
                    'schema_version' => 1,
                    'type' => 'schema',
                    'items' => Schema::snapshotTables(),
                ];
            case 'models':
                return [
                    'schema_version' => 1,
                    'type' => 'models',
                    'items' => Model::snapshotFields(),
                ];
            case 'capabilities':
                $caps = Introspect::capabilities();
                static::ksortDeep($caps);
                return [
                    'schema_version' => 1,
                    'type' => 'capabilities',
                    'items' => $caps,
                ];
            case 'contracts':
                return [
                    'schema_version' => 1,
                    'type' => 'contracts',
                    'items' => Introspect::contracts(),
                ];
            default:
                return null;
        }
    }

    /**
     * @param array $argv
     * @return array
     */
    protected static function parseOptions(array $argv) {
        $opts = [
            'type' => 'routes',
            'format' => 'json',
            'pretty' => false,
            'help' => false,
            'fail_on_diff' => '',
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
            if (strpos($arg, '--type=') === 0) {
                $opts['type'] = strtolower(trim(substr($arg, 7)));
                continue;
            }
            if (strpos($arg, '--format=') === 0) {
                $format = strtolower(trim(substr($arg, 9)));
                if (in_array($format, ['json', 'md'], true)) $opts['format'] = $format;
                continue;
            }
            if (strpos($arg, '--fail-on-diff=') === 0) {
                $opts['fail_on_diff'] = trim(substr($arg, 15));
                continue;
            }
        }

        return $opts;
    }

    /**
     * @param string $path
     * @param array $snapshot
     * @return int
     */
    protected static function checkDiff($path, array $snapshot) {
        $actual = json_encode($snapshot, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($actual === false) {
            fwrite(STDERR, "[agent-snapshot] cannot encode snapshot\n");
            return 1;
        }

        if (!is_file($path)) {
            fwrite(STDERR, "[agent-snapshot] baseline file missing: {$path}\n");
            return 1;
        }

        $expected = trim((string) file_get_contents($path));
        if (trim($actual) !== $expected) {
            fwrite(STDERR, "[agent-snapshot] snapshot differs from baseline: {$path}\n");
            return 1;
        }

        return 0;
    }

    /**
     * @param string $type
     * @param array $snapshot
     * @return string
     */
    protected static function renderMarkdown($type, array $snapshot) {
        $md = [];
        $md[] = '# Agent Snapshot';
        $md[] = '';
        $md[] = '- Type: `' . $type . '`';
        $md[] = '- Schema version: `' . $snapshot['schema_version'] . '`';
        $md[] = '';
        $md[] = '```json';
        $md[] = json_encode($snapshot['items'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $md[] = '```';
        $md[] = '';
        return implode("\n", $md);
    }

    /**
     * @param array $data
     * @return void
     */
    protected static function ksortDeep(array &$data) {
        ksort($data);
        foreach ($data as &$value) {
            if (is_array($value) && static::isAssoc($value)) static::ksortDeep($value);
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
     * @return string
     */
    protected static function usage() {
        return <<<TXT
Usage: php tools/agent-snapshot.php [options]

Options:
  --type=routes|schema|models|capabilities|contracts  Snapshot type (default: routes)
  --format=json|md                                     Output format (default: json)
  --pretty                                             Pretty print JSON
  --fail-on-diff=<path>                                Exit 1 when snapshot differs from baseline file
  --help, -h                                           Show this help

Examples:
  php tools/agent-snapshot.php --type=routes --format=json --pretty
  php tools/agent-snapshot.php --type=contracts --fail-on-diff=tests/fixtures/snapshots/contracts.json

TXT;
    }
}

exit(AgentSnapshotTool::run($argv));
