<?php

require_once __DIR__ . '/../classes/Loader.php';

/**
 * Run weekly marketing operations:
 * 1) Generate dated proof report file.
 * 2) Upsert KPI row for the week.
 * 3) Generate a proof-drop distribution draft.
 */
class MarketingWeeklyCycleTool {

    /**
     * @param array $argv
     * @return int
     */
    public static function run(array $argv) {
        $opts = static::parseOptions($argv);
        $root = dirname(__DIR__);
        $date = static::validDate($opts['date']) ? $opts['date'] : gmdate('Y-m-d');
        $weekStart = static::validDate($opts['week_start']) ? $opts['week_start'] : static::mondayOf($date);
        $weekEnd = gmdate('Y-m-d', strtotime($weekStart . ' UTC +6 days'));

        $reportPath = $root . '/docs/guides/proof-weekly-' . $date . '.md';
        $postPath = $root . '/docs/guides/distribution-proof-drop-' . $date . '.md';
        $kpiPath = $root . '/docs/guides/Agent-KPI-Log.md';

        static::applyGitHubMetrics($opts, $weekStart, $weekEnd);

        $status = static::runProofReport($root, $date, $reportPath);
        if ($status !== 0) return $status;

        if (!$opts['manual']['proof_commands_run_count']) {
            $opts['proof_commands_run_count'] = static::countProofCommands($reportPath);
        }

        $kpiStatus = static::upsertKpiRow($kpiPath, $weekStart, $opts);
        if ($kpiStatus !== 0) return $kpiStatus;

        $postStatus = static::writeDistributionDraft($reportPath, $postPath);
        if ($postStatus !== 0) return $postStatus;

        fwrite(STDOUT, "[marketing-weekly-cycle] report: docs/guides/proof-weekly-" . $date . ".md\n");
        fwrite(STDOUT, "[marketing-weekly-cycle] kpi: docs/guides/Agent-KPI-Log.md (week " . $weekStart . ")\n");
        fwrite(STDOUT, "[marketing-weekly-cycle] post: docs/guides/distribution-proof-drop-" . $date . ".md\n");
        if ($opts['github_fetch']) {
            fwrite(STDOUT, "[marketing-weekly-cycle] github metrics: " . $opts['github_owner'] . '/' . $opts['github_repo'] . "\n");
        }
        return 0;
    }

    /**
     * @param string $root
     * @param string $date
     * @param string $reportPath
     * @return int
     */
    protected static function runProofReport($root, $date, $reportPath) {
        $cmd = PHP_BINARY . ' ' . escapeshellarg($root . '/tools/proof-weekly-report.php')
            . ' --date=' . escapeshellarg($date)
            . ' --out=' . escapeshellarg($reportPath);
        passthru($cmd, $status);
        return (int) $status;
    }

    /**
     * @param string $path
     * @param string $weekStart
     * @param array $opts
     * @return int
     */
    protected static function upsertKpiRow($path, $weekStart, array $opts) {
        if (!is_file($path)) {
            fwrite(STDERR, "[marketing-weekly-cycle] missing KPI log: " . $path . "\n");
            return 1;
        }

        $content = (string) file_get_contents($path);
        $row = static::kpiRow($weekStart, $opts);
        $pattern = '/^\| ' . preg_quote($weekStart, '/') . ' \|.*$/m';

        if (preg_match($pattern, $content)) {
            $updated = preg_replace($pattern, $row, $content, 1);
            file_put_contents($path, $updated);
            return 0;
        }

        $insertBefore = "\n## Collection Notes\n";
        $needlePos = strpos($content, $insertBefore);
        if ($needlePos === false) {
            fwrite(STDERR, "[marketing-weekly-cycle] malformed KPI log, missing 'Collection Notes' section\n");
            return 1;
        }

        $updated = substr($content, 0, $needlePos);
        if (substr($updated, -1) !== "\n") $updated .= "\n";
        $updated .= $row . substr($content, $needlePos);
        file_put_contents($path, $updated);
        return 0;
    }

    /**
     * @param string $weekStart
     * @param array $opts
     * @return string
     */
    protected static function kpiRow($weekStart, array $opts) {
        $cols = [
            $weekStart,
            (string) $opts['repo_visits'],
            (string) $opts['clone_count'],
            (string) $opts['stars_delta'],
            (string) $opts['issues_agent_regression_report'],
            (string) $opts['issues_agent_workflow_request'],
            (string) $opts['issues_agent_proof_request'],
            (string) $opts['proof_commands_run_count'],
            static::sanitizeNotes($opts['notes']),
        ];
        return '| ' . implode(' | ', $cols) . ' |';
    }

    /**
     * @param string $notes
     * @return string
     */
    protected static function sanitizeNotes($notes) {
        $notes = trim(str_replace('|', '/', $notes));
        return $notes === '' ? 'Weekly update.' : $notes;
    }

    /**
     * @param string $reportPath
     * @param string $postPath
     * @return int
     */
    protected static function writeDistributionDraft($reportPath, $postPath) {
        if (!is_file($reportPath)) {
            fwrite(STDERR, "[marketing-weekly-cycle] missing report: " . $reportPath . "\n");
            return 1;
        }

        $report = (string) file_get_contents($reportPath);
        $claim = static::captureValue($report, '/^- Claim:\s+(.+)$/m');
        $command = static::captureValue($report, '/^- Command:\s+`(.+)`$/m');
        $artifact = static::captureValue($report, '/^- Artifact path:\s+`(.+)`$/m');
        $status = static::captureValue($report, '/^- Status:\s+(.+)$/m');

        $lines = [];
        $lines[] = '# Distribution Draft - Proof Drop';
        $lines[] = '';
        $lines[] = 'Core weekly proof drop:';
        $lines[] = '';
        $lines[] = 'Claim: ' . ($claim ?: 'Audit contract is machine-readable');
        $lines[] = 'Command: `' . ($command ?: 'php tools/agent-audit.php --format=json --pretty') . '`';
        $lines[] = 'Artifact: `' . ($artifact ?: 'docs/AUDIT.md') . '`';
        $lines[] = 'Result: ' . strtolower($status ?: 'PASS') . ' (see weekly report for full detail)';
        $lines[] = '';
        $lines[] = 'Full proof table:';
        $lines[] = 'https://github.com/coesion/core/blob/master/docs/AUDIT.md#71-proof-table-reproducible-claims';
        $lines[] = '';

        file_put_contents($postPath, implode("\n", $lines));
        return 0;
    }

    /**
     * @param array $opts
     * @param string $weekStart
     * @param string $weekEnd
     * @return void
     */
    protected static function applyGitHubMetrics(array &$opts, $weekStart, $weekEnd) {
        if (!$opts['github_fetch']) return;

        static::resolveRepo($opts);
        if ($opts['github_owner'] === '' || $opts['github_repo'] === '') {
            fwrite(STDERR, "[marketing-weekly-cycle] github repo not resolved; use --github-owner and --github-repo\n");
            return;
        }

        $token = $opts['github_token'] ?: getenv('GITHUB_TOKEN') ?: '';
        if ($token === '') {
            fwrite(STDERR, "[marketing-weekly-cycle] github token missing; set --github-token or GITHUB_TOKEN\n");
            return;
        }

        $trafficViews = static::fetchTrafficCount($opts, $token, 'views');
        $trafficClones = static::fetchTrafficCount($opts, $token, 'clones');
        $issuesRegression = static::fetchIssueCount($opts, $token, $weekStart, $weekEnd, ['agent', 'bug']);
        $issuesWorkflow = static::fetchIssueCount($opts, $token, $weekStart, $weekEnd, ['agent', 'enhancement']);
        $issuesProof = static::fetchIssueCount($opts, $token, $weekStart, $weekEnd, ['agent', 'documentation']);
        $starsDelta = static::fetchStarsDelta($opts, $token, $weekStart, $weekEnd);

        if (!$opts['manual']['repo_visits'] && $trafficViews !== null) {
            $opts['repo_visits'] = $trafficViews;
        }
        if (!$opts['manual']['clone_count'] && $trafficClones !== null) {
            $opts['clone_count'] = $trafficClones;
        }
        if (!$opts['manual']['issues_agent_regression_report'] && $issuesRegression !== null) {
            $opts['issues_agent_regression_report'] = $issuesRegression;
        }
        if (!$opts['manual']['issues_agent_workflow_request'] && $issuesWorkflow !== null) {
            $opts['issues_agent_workflow_request'] = $issuesWorkflow;
        }
        if (!$opts['manual']['issues_agent_proof_request'] && $issuesProof !== null) {
            $opts['issues_agent_proof_request'] = $issuesProof;
        }
        if (!$opts['manual']['stars_delta'] && $starsDelta !== null) {
            $opts['stars_delta'] = $starsDelta;
        }
    }

    /**
     * @param array $opts
     * @return void
     */
    protected static function resolveRepo(array &$opts) {
        if ($opts['github_owner'] !== '' && $opts['github_repo'] !== '') return;

        $envRepo = getenv('GITHUB_REPOSITORY') ?: '';
        if ($envRepo && strpos($envRepo, '/') !== false) {
            list($owner, $repo) = explode('/', $envRepo, 2);
            if ($opts['github_owner'] === '') $opts['github_owner'] = trim($owner);
            if ($opts['github_repo'] === '') $opts['github_repo'] = trim($repo);
            return;
        }

        $remote = trim((string) shell_exec('git config --get remote.origin.url 2>/dev/null'));
        if ($remote === '') return;

        if (preg_match('#github\.com[:/]+([^/]+)/([^/]+?)(?:\.git)?$#', $remote, $m)) {
            if ($opts['github_owner'] === '') $opts['github_owner'] = trim($m[1]);
            if ($opts['github_repo'] === '') $opts['github_repo'] = trim($m[2]);
        }
    }

    /**
     * @param array $opts
     * @param string $token
     * @param string $type
     * @return int|null
     */
    protected static function fetchTrafficCount(array $opts, $token, $type) {
        $url = rtrim($opts['github_api_base'], '/') . '/repos/'
            . rawurlencode($opts['github_owner']) . '/' . rawurlencode($opts['github_repo'])
            . '/traffic/' . $type;
        $res = static::apiGet($url, $token, 'application/vnd.github+json');
        if ($res['status'] !== 200 || !is_array($res['data'])) {
            fwrite(STDERR, "[marketing-weekly-cycle] github traffic/" . $type . " unavailable (status " . $res['status'] . ")\n");
            return null;
        }
        return isset($res['data']['count']) ? max(0, (int) $res['data']['count']) : null;
    }

    /**
     * @param array $opts
     * @param string $token
     * @param string $weekStart
     * @param string $weekEnd
     * @param array $labels
     * @return int|null
     */
    protected static function fetchIssueCount(array $opts, $token, $weekStart, $weekEnd, array $labels) {
        $q = 'repo:' . $opts['github_owner'] . '/' . $opts['github_repo']
            . ' is:issue created:' . $weekStart . '..' . $weekEnd;
        foreach ($labels as $label) {
            $q .= ' label:"' . $label . '"';
        }

        $url = rtrim($opts['github_api_base'], '/') . '/search/issues?q=' . rawurlencode($q) . '&per_page=1';
        $res = static::apiGet($url, $token, 'application/vnd.github+json');
        if ($res['status'] !== 200 || !is_array($res['data'])) {
            fwrite(STDERR, "[marketing-weekly-cycle] github issues count unavailable for labels: " . implode(',', $labels) . "\n");
            return null;
        }
        return isset($res['data']['total_count']) ? max(0, (int) $res['data']['total_count']) : null;
    }

    /**
     * @param array $opts
     * @param string $token
     * @param string $weekStart
     * @param string $weekEnd
     * @return int|null
     */
    protected static function fetchStarsDelta(array $opts, $token, $weekStart, $weekEnd) {
        $startTs = strtotime($weekStart . 'T00:00:00Z');
        $endTs = strtotime($weekEnd . 'T23:59:59Z');
        $count = 0;
        $sawStarredAt = false;

        for ($page = 1; $page <= 30; $page++) {
            $url = rtrim($opts['github_api_base'], '/') . '/repos/'
                . rawurlencode($opts['github_owner']) . '/' . rawurlencode($opts['github_repo'])
                . '/stargazers?per_page=100&page=' . $page;
            $res = static::apiGet($url, $token, 'application/vnd.github.star+json');
            if ($res['status'] !== 200 || !is_array($res['data'])) {
                if ($page === 1) fwrite(STDERR, "[marketing-weekly-cycle] github stargazer delta unavailable (status " . $res['status'] . ")\n");
                return $page === 1 ? null : $count;
            }
            if (count($res['data']) === 0) break;

            foreach ($res['data'] as $item) {
                if (!is_array($item) || !isset($item['starred_at'])) continue;
                $sawStarredAt = true;
                $ts = strtotime((string) $item['starred_at']);
                if ($ts >= $startTs && $ts <= $endTs) $count++;
            }
        }

        if (!$sawStarredAt) {
            fwrite(STDERR, "[marketing-weekly-cycle] github stargazer metadata missing; cannot compute stars_delta\n");
            return null;
        }
        return max(0, (int) $count);
    }

    /**
     * @param string $url
     * @param string $token
     * @param string $accept
     * @return array
     */
    protected static function apiGet($url, $token, $accept) {
        $headers = [
            'User-Agent: coesion-core-marketing-weekly-cycle',
            'Authorization: Bearer ' . $token,
            'Accept: ' . $accept,
            'X-GitHub-Api-Version: 2022-11-28',
        ];

        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headers),
                'ignore_errors' => true,
                'timeout' => 20,
            ],
        ]);

        $body = @file_get_contents($url, false, $ctx);
        $status = static::httpStatus($http_response_header ?? []);
        $data = null;
        if (is_string($body) && $body !== '') {
            $parsed = json_decode($body, true);
            if (is_array($parsed)) $data = $parsed;
        }

        return ['status' => $status, 'data' => $data];
    }

    /**
     * @param array $headers
     * @return int
     */
    protected static function httpStatus(array $headers) {
        if (!$headers) return 0;
        $first = (string) ($headers[0] ?? '');
        if (preg_match('/\s(\d{3})\s/', $first, $m)) return (int) $m[1];
        return 0;
    }

    /**
     * @param string $reportPath
     * @return int
     */
    protected static function countProofCommands($reportPath) {
        if (!is_file($reportPath)) return 0;
        $content = (string) file_get_contents($reportPath);
        if ($content === '') return 0;
        return (int) preg_match_all('/^- Command:\s+`.+`$/m', $content);
    }

    /**
     * @param string $source
     * @param string $pattern
     * @return string
     */
    protected static function captureValue($source, $pattern) {
        if (preg_match($pattern, $source, $m)) return trim($m[1]);
        return '';
    }

    /**
     * @param array $argv
     * @return array
     */
    protected static function parseOptions(array $argv) {
        $opts = [
            'date' => '',
            'week_start' => '',
            'repo_visits' => 0,
            'clone_count' => 0,
            'stars_delta' => 0,
            'issues_agent_regression_report' => 0,
            'issues_agent_workflow_request' => 0,
            'issues_agent_proof_request' => 0,
            'proof_commands_run_count' => 0,
            'notes' => 'Weekly update generated by marketing-weekly-cycle.',
            'github_fetch' => false,
            'github_owner' => '',
            'github_repo' => '',
            'github_token' => '',
            'github_api_base' => 'https://api.github.com',
            'manual' => [
                'repo_visits' => false,
                'clone_count' => false,
                'stars_delta' => false,
                'issues_agent_regression_report' => false,
                'issues_agent_workflow_request' => false,
                'issues_agent_proof_request' => false,
                'proof_commands_run_count' => false,
            ],
        ];

        foreach (array_slice($argv, 1) as $arg) {
            if (strpos($arg, '--date=') === 0) {
                $opts['date'] = trim(substr($arg, 7));
                continue;
            }
            if (strpos($arg, '--week-start=') === 0) {
                $opts['week_start'] = trim(substr($arg, 13));
                continue;
            }
            if (strpos($arg, '--notes=') === 0) {
                $opts['notes'] = trim(substr($arg, 8));
                continue;
            }
            if (strpos($arg, '--github-fetch=') === 0) {
                $opts['github_fetch'] = trim(substr($arg, 15)) === '1';
                continue;
            }
            if (strpos($arg, '--github-owner=') === 0) {
                $opts['github_owner'] = trim(substr($arg, 15));
                continue;
            }
            if (strpos($arg, '--github-repo=') === 0) {
                $opts['github_repo'] = trim(substr($arg, 14));
                continue;
            }
            if (strpos($arg, '--github-token=') === 0) {
                $opts['github_token'] = trim(substr($arg, 15));
                continue;
            }
            if (strpos($arg, '--github-api-base=') === 0) {
                $opts['github_api_base'] = rtrim(trim(substr($arg, 18)), '/');
                continue;
            }
            static::assignIntOpt($opts, $arg, 'repo_visits', '--repo-visits=');
            static::assignIntOpt($opts, $arg, 'clone_count', '--clone-count=');
            static::assignIntOpt($opts, $arg, 'stars_delta', '--stars-delta=');
            static::assignIntOpt($opts, $arg, 'issues_agent_regression_report', '--issues-agent-regression-report=');
            static::assignIntOpt($opts, $arg, 'issues_agent_workflow_request', '--issues-agent-workflow-request=');
            static::assignIntOpt($opts, $arg, 'issues_agent_proof_request', '--issues-agent-proof-request=');
            static::assignIntOpt($opts, $arg, 'proof_commands_run_count', '--proof-commands-run-count=');
        }

        return $opts;
    }

    /**
     * @param array $opts
     * @param string $arg
     * @param string $key
     * @param string $prefix
     * @return void
     */
    protected static function assignIntOpt(array &$opts, $arg, $key, $prefix) {
        if (strpos($arg, $prefix) !== 0) return;
        $value = (int) trim(substr($arg, strlen($prefix)));
        $opts[$key] = max(0, $value);
        if (isset($opts['manual'][$key])) $opts['manual'][$key] = true;
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

    /**
     * @param string $date
     * @return string
     */
    protected static function mondayOf($date) {
        $ts = strtotime($date . ' UTC');
        $day = (int) gmdate('N', $ts);
        $offset = $day - 1;
        return gmdate('Y-m-d', $ts - ($offset * 86400));
    }
}

exit(MarketingWeeklyCycleTool::run($argv));
