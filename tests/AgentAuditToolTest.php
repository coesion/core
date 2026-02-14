<?php

use PHPUnit\Framework\TestCase;

class AgentAuditToolTest extends TestCase {

    protected function runTool($args, &$stdout = null, &$stderr = null) {
        $php = escapeshellarg(PHP_BINARY);
        $tool = escapeshellarg(__DIR__ . '/../tools/agent-audit.php');
        $cmd = $php . ' ' . $tool . ' ' . $args;

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $proc = proc_open($cmd, $descriptors, $pipes);
        $this->assertIsResource($proc, 'Cannot start agent-audit tool process');

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        return proc_close($proc);
    }

    public function testJsonOutputSchema(): void {
        $code = $this->runTool('--format=json', $out, $err);
        $this->assertSame(0, $code, $err);

        $payload = json_decode($out, true);
        $this->assertIsArray($payload);
        $this->assertSame(1, $payload['schema_version']);
        $this->assertSame('core', $payload['framework']['name']);
        $this->assertArrayHasKey('capabilities', $payload);
        $this->assertArrayHasKey('counts', $payload);
        $this->assertArrayHasKey('core', $payload['capabilities']);
        $this->assertArrayHasKey('zero_runtime_dependencies', $payload['capabilities']['core']);
    }

    public function testJsonOutputIsDeterministic(): void {
        $code1 = $this->runTool('--format=json', $first, $err1);
        $code2 = $this->runTool('--format=json', $second, $err2);

        $this->assertSame(0, $code1, $err1);
        $this->assertSame(0, $code2, $err2);
        $this->assertSame($first, $second);
    }

    public function testFailOnMissingSuccessPath(): void {
        $code = $this->runTool('--format=json --fail-on-missing=capabilities.core.zero_runtime_dependencies', $out, $err);
        $this->assertSame(0, $code, $err);
    }

    public function testFailOnMissingFailurePath(): void {
        $code = $this->runTool('--format=json --fail-on-missing=capabilities.core.nonexistent_flag', $out, $err);
        $this->assertSame(1, $code);
        $this->assertStringContainsString('missing or falsy', $err);
    }
}
