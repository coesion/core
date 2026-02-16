<?php

use PHPUnit\Framework\TestCase;

class SQLBuilderTest extends TestCase {

    private string $dbPath;

    protected function setUp(): void {
        parent::setUp();
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite extension is not available.');
        }

        SQL::close();
        $this->dbPath = rtrim(sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . 'core-sqlbuilder-' . uniqid() . '.sqlite';
        SQL::connect('sqlite:' . $this->dbPath);
        SQL::defaultTo('default');

        SQL::exec('CREATE TABLE qb_items (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, state TEXT NOT NULL)');
        $db = SQL::using('default');
        $db->insert('qb_items', ['name' => 'one', 'state' => 'ready']);
        $db->insert('qb_items', ['name' => 'two', 'state' => 'done']);
    }

    protected function tearDown(): void {
        SQL::close();
        if (!empty($this->dbPath) && is_file($this->dbPath)) {
            @unlink($this->dbPath);
        }
        SQL::connect('sqlite::memory:');
        parent::tearDown();
    }

    public function testToSQLAndGet(): void {
        $builder = SQL::selectFrom('qb_items', ['id', 'name'])
            ->whereEq(['state' => 'ready'])
            ->orderBy(['id' => 'desc'])
            ->limit(10, 0);

        $sql = $builder->toSQL();
        $this->assertStringContainsString('SELECT `id`, `name` FROM `qb_items`', $sql['query']);
        $this->assertStringContainsString('WHERE `state` = :__w_state_0', $sql['query']);

        $rows = $builder->get();
        $this->assertCount(1, $rows);
        $this->assertSame('one', $rows[0]->name);
    }
}
