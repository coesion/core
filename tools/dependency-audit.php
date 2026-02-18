<?php

final class DependencyAuditTool {
    public static function run(): int {
        if (!self::composerHasAuditCommand()) {
            fwrite(STDOUT, "dependency-audit: SKIP (composer 'audit' command is unavailable in this environment)" . PHP_EOL);
            return 0;
        }

        $command = 'composer audit --locked --no-interaction --format=plain --abandoned=report';
        passthru($command, $code);
        return (int)$code;
    }

    private static function composerHasAuditCommand(): bool {
        $output = [];
        $code = 0;
        exec('composer list --raw 2>/dev/null', $output, $code);
        if ($code !== 0) {
            return false;
        }
        foreach ($output as $line) {
            $line = trim((string)$line);
            if (strpos($line, 'audit ') === 0 || $line === 'audit') {
                return true;
            }
        }
        return false;
    }
}

exit(DependencyAuditTool::run());
