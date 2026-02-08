<?php

use PHPUnit\Framework\TestCase;

class JobTest extends TestCase {
  public function testJobErrorAndRetryStateChanges(): void {
    $job = new Job();
    $this->assertSame('PENDING', $job->status);

    $job->error('failed');
    $this->assertSame('ERROR', $job->status);
    $this->assertSame('failed', $job->error);

    $job->retry('again');
    $this->assertSame('PENDING', $job->status);
    $this->assertSame('again', $job->error);
  }
}
