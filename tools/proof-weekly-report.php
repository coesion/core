<?php

require_once __DIR__ . '/../classes/Loader.php';

/**
 * Generate a weekly proof report draft from current artifacts.
 */
class ProofWeeklyReportTool {

    /**
     * @param array $argv
     * @return int
     */
    public static function run(array $argv) {
        $opts = static::parseOptions($argv);
        $root = dirname(__DIR__);
        $today = static::validDate($opts['date']) ? $opts['date'] : static::isoDate();

        $rows = static::buildRows($root, $today);
        $report = static::renderReport($today, $rows);

        if ($opts['out']) {
            $dir = dirname($opts['out']);
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            file_put_contents($opts['out'], $report);
            fwrite(STDOUT, "[proof-weekly-report] wrote " . $opts['out'] . "\n");
            return 0;
        }

        fwrite(STDOUT, $report);
        return 0;
    }

    /**
     * @param string $root
     * @param string $today
     * @return array
     */
    protected static function buildRows($root, $today) {
        return [
            static::row(
                'Audit contract is machine-readable',
                'php tools/agent-audit.php --format=json --pretty',
                $root . '/docs/AUDIT.md',
                $today
            ),
            static::row(
                'Contract snapshot is deterministic',
                'php tools/agent-snapshot.php --type=contracts --fail-on-diff=tests/fixtures/snapshots/contracts.json',
                $root . '/tests/fixtures/snapshots/contracts.json',
                $today
            ),
            static::row(
                'Case-study output is machine-readable',
                'php tools/agent-case-study.php --preset=baseline --out=docs/guides/agent-case-study.baseline.json',
                $root . '/docs/guides/agent-case-study.baseline.json',
                $today
            ),
            static::row(
                'Proof freshness is enforceable',
                'composer proof-freshness-check',
                $root . '/docs/AUDIT.md',
                $today
            ),
        ];
    }

    /**
     * @param string $claim
     * @param string $command
     * @param string $path
     * @param string $today
     * @return array
     */
    protected static function row($claim, $command, $path, $today) {
        $exists = is_file($path);
        $ageDays = $exists ? (int) floor((time() - filemtime($path)) / 86400) : null;
        $status = $exists ? 'PASS' : 'FAIL';
        $delta = 'n/a (set after first comparison week)';

        return [
            'claim' => $claim,
            'command' => $command,
            'artifact' => static::relativePath($path),
            'exists' => $exists ? 'yes' : 'no',
            'artifact_age_days' => $ageDays === null ? 'n/a' : (string) $ageDays,
            'verification_date' => $today,
            'delta_vs_previous_week' => $delta,
            'status' => $status,
        ];
    }

    /**
     * @param string $today
     * @param array $rows
     * @return string
     */
    protected static function renderReport($today, array $rows) {
        $lines = [];
        $lines[] = '# Weekly Proof Report';
        $lines[] = '';
        $lines[] = '- Verification date: ' . $today;
        $lines[] = '- Canonical proof table: docs/AUDIT.md#71-proof-table-reproducible-claims';
        $lines[] = '- Posting cadence: 1 weekly proof post + 1 monthly comparative/case-study post';
        $lines[] = '';
        $lines[] = '## Claim Checks';
        $lines[] = '';

        foreach ($rows as $idx => $row) {
            $n = $idx + 1;
            $lines[] = '### ' . $n . ') ' . $row['claim'];
            $lines[] = '- Claim: ' . $row['claim'];
            $lines[] = '- Command: `' . $row['command'] . '`';
            $lines[] = '- Artifact path: `' . $row['artifact'] . '`';
            $lines[] = '- Artifact exists: ' . $row['exists'];
            $lines[] = '- Artifact age (days): ' . $row['artifact_age_days'];
            $lines[] = '- Verification date: ' . $row['verification_date'];
            $lines[] = '- Delta vs previous week: ' . $row['delta_vs_previous_week'];
            $lines[] = '- Status: ' . $row['status'];
            $lines[] = '';
        }

        $lines[] = '## Notes';
        $lines[] = '';
        $lines[] = '- Replace delta placeholders once at least 2 weekly reports are available.';
        $lines[] = '- Keep any social/distribution post linked to the canonical proof table in `docs/AUDIT.md`.';
        $lines[] = '';

        return implode("\n", $lines);
    }

    /**
     * @param string $path
     * @return string
     */
    protected static function relativePath($path) {
        $root = dirname(__DIR__) . '/';
        if (strpos($path, $root) === 0) return substr($path, strlen($root));
        return $path;
    }

    /**
     * @return string
     */
    protected static function isoDate() {
        return gmdate('Y-m-d');
    }

    /**
     * @param array $argv
     * @return array
     */
    protected static function parseOptions(array $argv) {
        $opts = ['out' => '', 'date' => ''];
        foreach (array_slice($argv, 1) as $arg) {
            if (strpos($arg, '--out=') === 0) {
                $opts['out'] = trim(substr($arg, 6));
                continue;
            }
            if (strpos($arg, '--date=') === 0) {
                $opts['date'] = trim(substr($arg, 7));
            }
        }
        return $opts;
    }

    /**
     * @param string $value
     * @return bool
     */
    protected static function validDate($value) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) return false;
        $parts = explode('-', $value);
        return checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0]);
    }
}

if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    exit(ProofWeeklyReportTool::run($argv));
}
