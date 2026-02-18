<?php

use PHPUnit\Framework\TestCase;

class CodegenToolTest extends TestCase {

    /**
     * @param string $args
     * @param string|null $stdout
     * @param string|null $stderr
     * @return int
     */
    private function runTool(string $args, ?string &$stdout = null, ?string &$stderr = null): int {
        $php = escapeshellarg(PHP_BINARY);
        $tool = escapeshellarg(__DIR__ . '/../tools/codegen.php');
        $cmd = $php . ' ' . $tool . ' ' . $args;

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $proc = proc_open($cmd, $descriptors, $pipes);
        $this->assertIsResource($proc, 'Cannot start codegen tool process');

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        return proc_close($proc);
    }

    public function testGeneratesClassDocAndTestScaffolds(): void {
        $root = rtrim(sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . 'core-codegen-' . uniqid();
        mkdir($root, 0775, true);

        $args = '--type=class --name=DemoReport --namespace=' . escapeshellarg('App\\Domain') . ' --root=' . escapeshellarg($root);
        $code = $this->runTool($args, $out, $err);
        $this->assertSame(0, $code, $err);

        $payload = json_decode($out, true);
        $this->assertIsArray($payload);
        $this->assertSame('DemoReport', $payload['name']);
        $this->assertSame('App\\Domain', $payload['namespace']);
        $this->assertCount(3, $payload['result']['created']);

        $classFile = $root . '/classes/DemoReport.php';
        $docFile = $root . '/docs/classes/DemoReport.md';
        $testFile = $root . '/tests/DemoReportTest.php';

        $this->assertFileExists($classFile);
        $this->assertFileExists($docFile);
        $this->assertFileExists($testFile);

        $this->assertStringContainsString('namespace App\\Domain;', (string) file_get_contents($classFile));

        @unlink($classFile);
        @unlink($docFile);
        @unlink($testFile);
        @rmdir($root . '/classes');
        @rmdir($root . '/docs/classes');
        @rmdir($root . '/docs');
        @rmdir($root . '/tests');
        @rmdir($root);
    }

    public function testRejectsInvalidClassName(): void {
        $code = $this->runTool('--type=class --name=not-valid', $out, $err);
        $this->assertSame(1, $code);

        $payload = json_decode($out, true);
        $this->assertIsArray($payload);
        $this->assertNotEmpty($payload['result']['errors']);
        $this->assertStringContainsString('invalid --name', $payload['result']['errors'][0]);
    }

    public function testSkipsExistingFileWithoutForceAndOverwritesWithForce(): void {
        $root = rtrim(sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . 'core-codegen-' . uniqid();
        mkdir($root . '/classes', 0775, true);
        mkdir($root . '/docs/classes', 0775, true);
        mkdir($root . '/tests', 0775, true);

        $classFile = $root . '/classes/Repeatable.php';
        file_put_contents($classFile, "<?php\n\nclass Repeatable {}\n");

        $args = '--type=class --name=Repeatable --root=' . escapeshellarg($root);

        $code1 = $this->runTool($args, $out1, $err1);
        $this->assertSame(0, $code1, $err1);
        $payload1 = json_decode($out1, true);
        $this->assertContains($classFile, $payload1['result']['skipped']);

        $code2 = $this->runTool($args . ' --force', $out2, $err2);
        $this->assertSame(0, $code2, $err2);
        $payload2 = json_decode($out2, true);
        $this->assertContains($classFile, $payload2['result']['created']);

        $this->assertStringContainsString('TODO: add class description.', (string) file_get_contents($classFile));

        @unlink($root . '/classes/Repeatable.php');
        @unlink($root . '/docs/classes/Repeatable.md');
        @unlink($root . '/tests/RepeatableTest.php');
        @rmdir($root . '/classes');
        @rmdir($root . '/docs/classes');
        @rmdir($root . '/docs');
        @rmdir($root . '/tests');
        @rmdir($root);
    }
}
