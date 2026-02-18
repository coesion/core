<?php

final class SecurityAuditTool {
    private const RULE_DANGEROUS_PROCESS = 'SEC001';
    private const RULE_UNSAFE_UNSERIALIZE = 'SEC002';

    private const DEFAULT_PATHS = ['classes', 'tools', 'tests'];
    private const DANGEROUS_FUNCTIONS = ['exec', 'shell_exec', 'system', 'passthru', 'popen', 'proc_open'];

    public static function run(array $argv): int {
        [$allowlistPath, $paths, $customPaths] = self::parseArgs($argv);
        $allowlist = self::loadAllowlist($allowlistPath);
        $files = self::collectPhpFiles($paths, $customPaths);

        $findings = [];
        foreach ($files as $file) {
            $findings = array_merge($findings, self::scanFile($file));
        }

        $filtered = self::applyAllowlist($findings, $allowlist);
        usort($filtered, static function (array $a, array $b): int {
            return [$a['rule'], $a['path'], $a['line'], $a['message']]
                <=> [$b['rule'], $b['path'], $b['line'], $b['message']];
        });

        foreach ($filtered as $finding) {
            fwrite(STDERR, $finding['rule'] . ' ' . $finding['path'] . ':' . $finding['line'] . ' ' . $finding['message'] . PHP_EOL);
        }

        if ($filtered !== []) {
            fwrite(STDERR, 'security-audit: FAIL (' . count($filtered) . ' finding' . (count($filtered) === 1 ? '' : 's') . ')' . PHP_EOL);
            return 1;
        }

        fwrite(STDOUT, 'security-audit: OK' . PHP_EOL);
        return 0;
    }

    private static function parseArgs(array $argv): array {
        $allowlistPath = 'tools/security-audit.allowlist.json';
        $paths = self::DEFAULT_PATHS;
        $customPaths = false;

        foreach (array_slice($argv, 1) as $arg) {
            if (strpos($arg, '--allowlist=') === 0) {
                $allowlistPath = substr($arg, strlen('--allowlist='));
                continue;
            }
            if (strpos($arg, '--paths=') === 0) {
                $raw = substr($arg, strlen('--paths='));
                $parts = array_values(array_filter(array_map('trim', explode(',', $raw)), static fn($v) => $v !== ''));
                $paths = $parts !== [] ? $parts : self::DEFAULT_PATHS;
                $customPaths = true;
                continue;
            }
            if ($arg === '--help' || $arg === '-h') {
                self::printHelp();
                exit(0);
            }
            fwrite(STDERR, '[security-audit] unknown argument: ' . $arg . PHP_EOL);
            exit(1);
        }

        return [$allowlistPath, $paths, $customPaths];
    }

    private static function printHelp(): void {
        $lines = [
            'Usage: php tools/security-audit.php [options]',
            '',
            'Options:',
            '  --allowlist=<path>     Path to allowlist JSON file.',
            '  --paths=<a,b,c>        Comma-separated directories/files to scan.',
            '  -h, --help             Show this help.',
        ];
        fwrite(STDOUT, implode(PHP_EOL, $lines) . PHP_EOL);
    }

    private static function loadAllowlist(string $path): array {
        if (!is_file($path)) {
            fwrite(STDERR, '[security-audit] allowlist file not found: ' . $path . PHP_EOL);
            exit(1);
        }

        $decoded = json_decode((string)file_get_contents($path), true);
        if (!is_array($decoded)) {
            fwrite(STDERR, '[security-audit] allowlist is not valid JSON: ' . $path . PHP_EOL);
            exit(1);
        }

        $entries = [];
        foreach ($decoded as $idx => $entry) {
            if (!is_array($entry) || !isset($entry['rule'], $entry['path'], $entry['reason'])) {
                fwrite(STDERR, '[security-audit] invalid allowlist entry at index ' . $idx . PHP_EOL);
                exit(1);
            }
            $normalized = [
                'rule' => (string)$entry['rule'],
                'path' => self::normalizePath((string)$entry['path']),
                'reason' => (string)$entry['reason'],
            ];
            if (isset($entry['line'])) {
                $normalized['line'] = (int)$entry['line'];
            }
            if (isset($entry['line_range']) && is_array($entry['line_range']) && count($entry['line_range']) === 2) {
                $normalized['line_range'] = [(int)$entry['line_range'][0], (int)$entry['line_range'][1]];
            }
            $entries[] = $normalized;
        }

        return $entries;
    }

    private static function collectPhpFiles(array $targets, bool $customPaths): array {
        $tracked = self::trackedPhpFiles();
        $targetPrefixes = array_map([self::class, 'normalizePath'], $targets);

        $files = [];
        foreach ($tracked as $file) {
            $normalized = self::normalizePath($file);
            foreach ($targetPrefixes as $prefix) {
                if ($normalized === $prefix || strpos($normalized, rtrim($prefix, '/') . '/') === 0) {
                    $files[] = $normalized;
                    break;
                }
            }
        }

        if ($customPaths) {
            $files = array_merge($files, self::filesystemPhpFilesForTargets($targetPrefixes));
        }

        $files = array_values(array_unique($files));
        sort($files, SORT_STRING);
        return $files;
    }

    private static function filesystemPhpFilesForTargets(array $targets): array {
        $files = [];
        foreach ($targets as $target) {
            if (is_file($target)) {
                if (strtolower((string)pathinfo($target, PATHINFO_EXTENSION)) === 'php') {
                    $files[] = self::normalizePath($target);
                }
                continue;
            }
            if (!is_dir($target)) {
                continue;
            }
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($target, FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $fileInfo) {
                if (!$fileInfo->isFile()) {
                    continue;
                }
                if (strtolower((string)$fileInfo->getExtension()) !== 'php') {
                    continue;
                }
                $files[] = self::normalizePath((string)$fileInfo->getPathname());
            }
        }
        return $files;
    }

    private static function trackedPhpFiles(): array {
        $files = [];
        $command = 'git ls-files -- \'*.php\' 2>/dev/null';
        $output = [];
        $code = 0;
        exec($command, $output, $code);
        if ($code === 0 && $output !== []) {
            foreach ($output as $line) {
                $line = trim((string)$line);
                if ($line !== '') {
                    $files[] = self::normalizePath($line);
                }
            }
            return $files;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator('.', FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile()) {
                continue;
            }
            if (strtolower((string)$fileInfo->getExtension()) !== 'php') {
                continue;
            }
            $files[] = self::normalizePath((string)$fileInfo->getPathname());
        }
        return $files;
    }

    private static function scanFile(string $path): array {
        $code = (string)file_get_contents($path);
        $tokens = token_get_all($code, TOKEN_PARSE);
        $findings = [];

        $count = count($tokens);
        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];
            if (!is_array($token) || $token[0] !== T_STRING) {
                continue;
            }

            $name = strtolower($token[1]);
            $prevIdx = self::prevSignificantIndex($tokens, $i - 1);
            $nextIdx = self::nextSignificantIndex($tokens, $i + 1);
            if ($nextIdx === null || $tokens[$nextIdx] !== '(') {
                continue;
            }
            if (self::isMethodContext($tokens, $prevIdx) || self::isDeclarationContext($tokens, $prevIdx)) {
                continue;
            }

            if (in_array($name, self::DANGEROUS_FUNCTIONS, true)) {
                $findings[] = [
                    'rule' => self::RULE_DANGEROUS_PROCESS,
                    'path' => self::normalizePath($path),
                    'line' => (int)$token[2],
                    'message' => 'dangerous process execution call: ' . $name . '()',
                ];
                continue;
            }

            if ($name === 'unserialize') {
                $endIdx = self::findMatchingParen($tokens, $nextIdx);
                if ($endIdx === null) {
                    continue;
                }
                $args = self::parseCallArgs($tokens, $nextIdx + 1, $endIdx - 1);
                if (!self::hasSecureUnserializeOptions($args)) {
                    $findings[] = [
                        'rule' => self::RULE_UNSAFE_UNSERIALIZE,
                        'path' => self::normalizePath($path),
                        'line' => (int)$token[2],
                        'message' => 'unserialize() without options[allowed_classes=false]',
                    ];
                }
            }
        }

        return $findings;
    }

    private static function prevSignificantIndex(array $tokens, int $idx): ?int {
        for ($i = $idx; $i >= 0; $i--) {
            $tok = $tokens[$i];
            if (is_array($tok) && in_array($tok[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            return $i;
        }
        return null;
    }

    private static function nextSignificantIndex(array $tokens, int $idx): ?int {
        $count = count($tokens);
        for ($i = $idx; $i < $count; $i++) {
            $tok = $tokens[$i];
            if (is_array($tok) && in_array($tok[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            return $i;
        }
        return null;
    }

    private static function isMethodContext(array $tokens, ?int $prevIdx): bool {
        if ($prevIdx === null) {
            return false;
        }
        $prev = $tokens[$prevIdx];
        if (is_array($prev)) {
            return in_array($prev[0], [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON], true);
        }
        return false;
    }

    private static function isDeclarationContext(array $tokens, ?int $prevIdx): bool {
        if ($prevIdx === null) {
            return false;
        }
        $prev = $tokens[$prevIdx];
        if (!is_array($prev)) {
            return false;
        }
        return in_array($prev[0], [T_FUNCTION, T_FN, T_NEW], true);
    }

    private static function findMatchingParen(array $tokens, int $openIdx): ?int {
        $depth = 0;
        $count = count($tokens);
        for ($i = $openIdx; $i < $count; $i++) {
            $tok = $tokens[$i];
            if ($tok === '(') {
                $depth++;
                continue;
            }
            if ($tok === ')') {
                $depth--;
                if ($depth === 0) {
                    return $i;
                }
            }
        }
        return null;
    }

    private static function parseCallArgs(array $tokens, int $from, int $to): array {
        $args = [];
        $current = [];
        $depthParen = 0;
        $depthBracket = 0;
        $depthBrace = 0;

        for ($i = $from; $i <= $to; $i++) {
            $tok = $tokens[$i];
            if ($tok === '(') {
                $depthParen++;
            } elseif ($tok === ')') {
                $depthParen--;
            } elseif ($tok === '[') {
                $depthBracket++;
            } elseif ($tok === ']') {
                $depthBracket--;
            } elseif ($tok === '{') {
                $depthBrace++;
            } elseif ($tok === '}') {
                $depthBrace--;
            }

            if ($tok === ',' && $depthParen === 0 && $depthBracket === 0 && $depthBrace === 0) {
                $args[] = self::parseArg($current);
                $current = [];
                continue;
            }
            $current[] = $tok;
        }

        if ($current !== []) {
            $args[] = self::parseArg($current);
        }
        return $args;
    }

    private static function parseArg(array $tokens): array {
        $tokens = self::trimTokenEdges($tokens);
        $name = null;

        $firstSig = self::nextSignificantIndex($tokens, 0);
        if ($firstSig !== null && isset($tokens[$firstSig + 1]) && $tokens[$firstSig + 1] === ':') {
            $first = $tokens[$firstSig];
            if (is_array($first) && $first[0] === T_STRING) {
                $name = strtolower($first[1]);
                $tokens = array_slice($tokens, $firstSig + 2);
                $tokens = self::trimTokenEdges($tokens);
            }
        }

        return [
            'name' => $name,
            'tokens' => $tokens,
            'text' => self::tokensToText($tokens),
        ];
    }

    private static function trimTokenEdges(array $tokens): array {
        $start = 0;
        $end = count($tokens) - 1;
        while ($start <= $end) {
            $tok = $tokens[$start];
            if (is_array($tok) && in_array($tok[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                $start++;
                continue;
            }
            break;
        }
        while ($end >= $start) {
            $tok = $tokens[$end];
            if (is_array($tok) && in_array($tok[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                $end--;
                continue;
            }
            break;
        }
        if ($start > $end) {
            return [];
        }
        return array_slice($tokens, $start, $end - $start + 1);
    }

    private static function tokensToText(array $tokens): string {
        $text = '';
        foreach ($tokens as $tok) {
            $text .= is_array($tok) ? $tok[1] : $tok;
        }
        return $text;
    }

    private static function hasSecureUnserializeOptions(array $args): bool {
        if (count($args) < 2) {
            return false;
        }

        foreach ($args as $index => $arg) {
            if ($index === 0) {
                continue;
            }

            $isOptionsArg = $index === 1 || $arg['name'] === 'options';
            if (!$isOptionsArg) {
                continue;
            }

            if (self::arrayContainsAllowedClassesFalse($arg['tokens'])) {
                return true;
            }
        }

        return false;
    }

    private static function arrayContainsAllowedClassesFalse(array $tokens): bool {
        $tokens = self::trimTokenEdges($tokens);
        if ($tokens === []) {
            return false;
        }

        $isArraySyntax = false;
        if (is_array($tokens[0]) && $tokens[0][0] === T_ARRAY) {
            $isArraySyntax = true;
        } elseif ($tokens[0] === '[') {
            $isArraySyntax = true;
        }
        if (!$isArraySyntax) {
            return false;
        }

        $text = strtolower(self::tokensToText($tokens));
        $text = preg_replace('/\s+/', '', $text);
        if ($text === null) {
            return false;
        }

        $patterns = [
            '/[\'"]allowed_classes[\'"]=>false/',
            '/allowed_classes=>false/',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text) === 1) {
                return true;
            }
        }
        return false;
    }

    private static function applyAllowlist(array $findings, array $allowlist): array {
        $filtered = [];
        foreach ($findings as $finding) {
            if (self::isAllowlisted($finding, $allowlist)) {
                continue;
            }
            $filtered[] = $finding;
        }
        return $filtered;
    }

    private static function isAllowlisted(array $finding, array $allowlist): bool {
        foreach ($allowlist as $entry) {
            if ($entry['rule'] !== $finding['rule']) {
                continue;
            }
            if ($entry['path'] !== $finding['path']) {
                continue;
            }

            if (isset($entry['line']) && (int)$entry['line'] !== (int)$finding['line']) {
                continue;
            }

            if (isset($entry['line_range'])) {
                [$from, $to] = $entry['line_range'];
                $line = (int)$finding['line'];
                if ($line < $from || $line > $to) {
                    continue;
                }
            }

            return true;
        }
        return false;
    }

    private static function normalizePath(string $path): string {
        $normalized = str_replace('\\', '/', $path);
        if (strpos($normalized, './') === 0) {
            $normalized = substr($normalized, 2);
        }
        return trim($normalized, '/');
    }
}

exit(SecurityAuditTool::run($argv));
