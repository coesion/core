<?php

use PHPUnit\Framework\TestCase;

class ScheduleTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        Schedule::flush();
    }

    protected function tearDown(): void {
        Schedule::flush();
        parent::tearDown();
    }

    public function testRegisterAndAll(): void {
        Schedule::register('test', '* * * * *', 'test.type');
        $all = Schedule::all();
        $this->assertArrayHasKey('test', $all);
        $this->assertSame('test.type', $all['test']['type']);
    }

    public function testUnregister(): void {
        Schedule::register('test', '* * * * *', 'test.type');
        Schedule::unregister('test');
        $this->assertEmpty(Schedule::all());
    }

    public function testWildcardCronAlwaysMatches(): void {
        $this->assertTrue(Schedule::matches('* * * * *', time()));
    }

    public function testExactMinuteMatch(): void {
        // Create a timestamp at minute 30
        $time = mktime(12, 30, 0, 6, 15, 2025);
        $this->assertTrue(Schedule::matches('30 * * * *', $time));
        $this->assertFalse(Schedule::matches('15 * * * *', $time));
    }

    public function testExactHourMatch(): void {
        $time = mktime(14, 0, 0, 6, 15, 2025);
        $this->assertTrue(Schedule::matches('0 14 * * *', $time));
        $this->assertFalse(Schedule::matches('0 10 * * *', $time));
    }

    public function testStepValues(): void {
        // minute = 0, should match */5
        $time = mktime(12, 0, 0, 6, 15, 2025);
        $this->assertTrue(Schedule::matches('*/5 * * * *', $time));

        // minute = 3, should NOT match */5
        $time = mktime(12, 3, 0, 6, 15, 2025);
        $this->assertFalse(Schedule::matches('*/5 * * * *', $time));

        // minute = 15, should match */5
        $time = mktime(12, 15, 0, 6, 15, 2025);
        $this->assertTrue(Schedule::matches('*/15 * * * *', $time));
    }

    public function testRangeMatch(): void {
        // hour = 14, should match 9-17
        $time = mktime(14, 0, 0, 6, 15, 2025);
        $this->assertTrue(Schedule::matches('0 9-17 * * *', $time));

        // hour = 20, should NOT match 9-17
        $time = mktime(20, 0, 0, 6, 15, 2025);
        $this->assertFalse(Schedule::matches('0 9-17 * * *', $time));
    }

    public function testListMatch(): void {
        // minute = 0, should match 0,15,30,45
        $time = mktime(12, 0, 0, 6, 15, 2025);
        $this->assertTrue(Schedule::matches('0,15,30,45 * * * *', $time));

        // minute = 10, should NOT match
        $time = mktime(12, 10, 0, 6, 15, 2025);
        $this->assertFalse(Schedule::matches('0,15,30,45 * * * *', $time));
    }

    public function testWeekdayMatch(): void {
        // 2025-06-16 is a Monday (weekday=1)
        $time = mktime(12, 0, 0, 6, 16, 2025);
        $this->assertTrue(Schedule::matches('0 12 * * 1', $time));
        $this->assertFalse(Schedule::matches('0 12 * * 0', $time));
    }

    public function testRangeWithStep(): void {
        // minute = 2, range 0-10/2 should match
        $time = mktime(12, 2, 0, 6, 15, 2025);
        $this->assertTrue(Schedule::matches('0-10/2 * * * *', $time));

        // minute = 3, range 0-10/2 should NOT match
        $time = mktime(12, 3, 0, 6, 15, 2025);
        $this->assertFalse(Schedule::matches('0-10/2 * * * *', $time));
    }

    public function testDueReturnsMatchingJobs(): void {
        $time = mktime(14, 30, 0, 6, 15, 2025);
        Schedule::register('always', '* * * * *', 'job.always');
        Schedule::register('never', '0 0 1 1 *', 'job.never');
        Schedule::register('exact', '30 14 * * *', 'job.exact');

        $due = Schedule::due($time);
        $names = array_column($due, 'name');
        $this->assertContains('always', $names);
        $this->assertContains('exact', $names);
        $this->assertNotContains('never', $names);
    }

    public function testInvalidCronReturnsFalse(): void {
        $this->assertFalse(Schedule::matches('invalid', time()));
        $this->assertFalse(Schedule::matches('* * *', time()));
    }
}
