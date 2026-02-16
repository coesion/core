<?php

require_once __DIR__ . '/../classes/Loader.php';

/**
 * Refresh proof artifacts with graceful handling when benchmark input is unavailable.
 */
class ProofRefreshTool {

    /**
     * @return int
     */
    public static function run() {
        $root = dirname(__DIR__);
        $benchDir = $root . '/benchmarks/results';
        $bench = static::latestBenchmark($benchDir);

        if ($bench) {
            $cmd = PHP_BINARY . ' ' . escapeshellarg($root . '/tools/benchmark_report.php') . ' --file=' . escapeshellarg($bench);
            passthru($cmd, $status);
            if ($status !== 0) return $status;
        } else {
            fwrite(STDOUT, "[proof-refresh] benchmark input missing, skipping router benchmark refresh\n");
        }

        $caseStudyOut = $root . '/docs/guides/agent-case-study.baseline.json';
        $cmd = PHP_BINARY . ' ' . escapeshellarg($root . '/tools/agent-case-study.php') . ' --preset=baseline --out=' . escapeshellarg($caseStudyOut);
        passthru($cmd, $status);
        return $status;
    }

    /**
     * @param string $benchDir
     * @return string
     */
    protected static function latestBenchmark($benchDir) {
        if (!is_dir($benchDir)) return '';
        $files = glob($benchDir . '/bench_*.json');
        if (!$files) return '';
        usort($files, function ($a, $b) {
            return filemtime($b) <=> filemtime($a);
        });
        return $files[0] ?? '';
    }
}

exit(ProofRefreshTool::run());
