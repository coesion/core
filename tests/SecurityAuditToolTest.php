<?php

use PHPUnit\Framework\TestCase;

class SecurityAuditToolTest extends TestCase {

    private function runTool(string $args, ?string &$stdout = null, ?string &$stderr = null): int {
        $php = escapeshellarg(PHP_BINARY);
        $tool = escapeshellarg(__DIR__ . '/../tools/security-audit.php');
        $cmd = $php . ' ' . $tool . ' ' . $args;

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $proc = proc_open($cmd, $descriptors, $pipes);
        $this->assertIsResource($proc, 'Cannot start security-audit tool process');

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        return proc_close($proc);
    }

    public function testCleanFixturePasses(): void {
        $paths = escapeshellarg('tests/fixtures/security-audit/safe.php');
        $allowlist = escapeshellarg('tests/fixtures/security-audit/allowlist-empty.json');
        $code = $this->runTool("--paths={$paths} --allowlist={$allowlist}", $out, $err);

        $this->assertSame(0, $code, $err);
        $this->assertStringContainsString('security-audit: OK', $out);
    }

    public function testUnsafeFixtureFailsWithRuleIds(): void {
        $paths = escapeshellarg('tests/fixtures/security-audit/unsafe.php');
        $allowlist = escapeshellarg('tests/fixtures/security-audit/allowlist-empty.json');
        $code = $this->runTool("--paths={$paths} --allowlist={$allowlist}", $out, $err);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('SEC001', $err);
        $this->assertStringContainsString('SEC002', $err);
        $this->assertStringContainsString('security-audit: FAIL', $err);
    }

    public function testAllowlistedFindingIsSuppressed(): void {
        $paths = escapeshellarg('tests/fixtures/security-audit/allowlisted.php');
        $allowlist = escapeshellarg('tests/fixtures/security-audit/allowlist-only-sec001.json');
        $code = $this->runTool("--paths={$paths} --allowlist={$allowlist}", $out, $err);

        $this->assertSame(0, $code, $err);
        $this->assertStringContainsString('security-audit: OK', $out);
        $this->assertSame('', $err);
    }
}
