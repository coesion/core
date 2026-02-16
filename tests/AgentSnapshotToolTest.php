<?php

use PHPUnit\Framework\TestCase;

class AgentSnapshotToolTest extends TestCase {

    protected function runTool($args, &$stdout = null, &$stderr = null) {
        $php = escapeshellarg(PHP_BINARY);
        $tool = escapeshellarg(__DIR__ . '/../tools/agent-snapshot.php');
        $cmd = $php . ' ' . $tool . ' ' . $args;

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $proc = proc_open($cmd, $descriptors, $pipes);
        $this->assertIsResource($proc, 'Cannot start agent-snapshot process');

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        return proc_close($proc);
    }

    public function testContractsSnapshotMatchesFixture(): void {
        $fixture = __DIR__ . '/fixtures/snapshots/contracts.json';
        $code = $this->runTool('--type=contracts --fail-on-diff=' . escapeshellarg($fixture), $out, $err);
        $this->assertSame(0, $code, $err);
    }

    public function testUnknownTypeFails(): void {
        $code = $this->runTool('--type=unknown', $out, $err);
        $this->assertSame(1, $code);
        $this->assertStringContainsString('unknown type', $err);
    }
}
