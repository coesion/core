<?php

use PHPUnit\Framework\TestCase;

class MigrationTest extends TestCase {

    private string $dbPath;

    protected function setUp(): void {
        parent::setUp();
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite extension is not available.');
        }

        SQL::close();
        $this->dbPath = rtrim(sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . 'core-migration-' . uniqid() . '.sqlite';
        SQL::connect('sqlite:' . $this->dbPath);
        SQL::defaultTo('default');
        Migration::flush();

        Migration::register('20260216_001_create_tasks', function () {
            SQL::exec('CREATE TABLE tasks (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL)');
        }, function () {
            SQL::exec('DROP TABLE IF EXISTS tasks');
        });
    }

    protected function tearDown(): void {
        Migration::flush();
        SQL::close();
        if (!empty($this->dbPath) && is_file($this->dbPath)) {
            @unlink($this->dbPath);
        }
        SQL::connect('sqlite::memory:');
        parent::tearDown();
    }

    public function testApplyAndStatus(): void {
        $this->assertFalse(Schema::hasTable('tasks'));

        $applied = Migration::apply('latest');
        $this->assertSame(['20260216_001_create_tasks'], $applied);
        $this->assertTrue(Schema::hasTable('tasks'));

        $status = Migration::status();
        $this->assertSame(['20260216_001_create_tasks'], $status['applied']);
        $this->assertSame([], $status['pending']);
    }

    public function testRollback(): void {
        Migration::apply('latest');
        $rolled = Migration::rollback(1);
        $this->assertSame(['20260216_001_create_tasks'], $rolled);
        $this->assertFalse(Schema::hasTable('tasks'));
    }
}
