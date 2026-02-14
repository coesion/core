<?php

use PHPUnit\Framework\TestCase;

class SchemaTestModel extends Model {
    const _PRIMARY_KEY_ = 'schema_test_items.id';
    public $id, $name, $value;
}

class SchemaTest extends TestCase {

    private string $dbPath;

    protected function setUp(): void {
        parent::setUp();
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite extension is not available.');
        }
        SQL::close();
        $this->dbPath = rtrim(sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . 'core-schema-' . uniqid() . '.sqlite';
        SQL::connect('sqlite:' . $this->dbPath);
        SQL::defaultTo('default');

        SQL::exec("CREATE TABLE schema_test_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            value TEXT DEFAULT 'none'
        )");

        Schema::flush();
    }

    protected function tearDown(): void {
        SQL::close();
        if (!empty($this->dbPath) && is_file($this->dbPath)) {
            @unlink($this->dbPath);
        }
        // Restore default in-memory connection
        SQL::connect('sqlite::memory:');
        parent::tearDown();
    }

    public function testDescribeReturnsColumns(): void {
        $columns = Schema::describe('schema_test_items');

        $this->assertIsArray($columns);
        $this->assertCount(3, $columns);

        $names = array_column($columns, 'name');
        $this->assertContains('id', $names);
        $this->assertContains('name', $names);
        $this->assertContains('value', $names);
    }

    public function testDescribeColumnAttributes(): void {
        $columns = Schema::describe('schema_test_items');
        $idCol = null;
        foreach ($columns as $col) {
            if ($col['name'] === 'id') { $idCol = $col; break; }
        }

        $this->assertNotNull($idCol);
        $this->assertArrayHasKey('type', $idCol);
        $this->assertArrayHasKey('nullable', $idCol);
        $this->assertArrayHasKey('key', $idCol);
    }

    public function testTablesListsCreatedTable(): void {
        $tables = Schema::tables();
        $this->assertIsArray($tables);
        $this->assertContains('schema_test_items', $tables);
    }

    public function testColumnsReturnsFlat(): void {
        $cols = Schema::columns('schema_test_items');
        $this->assertEquals(['id', 'name', 'value'], $cols);
    }

    public function testHasTable(): void {
        $this->assertTrue(Schema::hasTable('schema_test_items'));
        $this->assertFalse(Schema::hasTable('nonexistent_table_xyz'));
    }

    public function testModelSchema(): void {
        $schema = SchemaTestModel::schema();
        $this->assertIsArray($schema);
        $this->assertCount(3, $schema);
    }

    public function testModelFields(): void {
        $fields = SchemaTestModel::fields();
        $this->assertEquals(['id', 'name', 'value'], $fields);
    }

    public function testDescribeWithModelClass(): void {
        $columns = Schema::describe('SchemaTestModel');
        $this->assertCount(3, $columns);
    }

    public function testFlushClearsCache(): void {
        Schema::describe('schema_test_items');
        Schema::flush();
        // Should re-query without error
        $columns = Schema::describe('schema_test_items');
        $this->assertCount(3, $columns);
    }
}
