<?php

use PHPUnit\Framework\TestCase;

class SnapshotModelForTest extends Model {
    const _PRIMARY_KEY_ = 'snapshot_items.id';
    public $id, $name;
}

class SnapshotTest extends TestCase {

    private string $dbPath;

    protected function setUp(): void {
        parent::setUp();
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite extension is not available.');
        }

        Route::reset();
        Schema::flush();

        SQL::close();
        $this->dbPath = rtrim(sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . 'core-snapshot-' . uniqid() . '.sqlite';
        SQL::connect('sqlite:' . $this->dbPath);
        SQL::defaultTo('default');

        SQL::exec('CREATE TABLE snapshot_items (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL)');
    }

    protected function tearDown(): void {
        Route::reset();
        SQL::close();
        if (!empty($this->dbPath) && is_file($this->dbPath)) {
            @unlink($this->dbPath);
        }
        SQL::connect('sqlite::memory:');
        parent::tearDown();
    }

    public function testContractsMapExists(): void {
        $contracts = Introspect::contracts();
        $this->assertArrayHasKey('agent_audit_schema_version', $contracts);
        $this->assertArrayHasKey('snapshot_schema_version', $contracts);
        $this->assertArrayHasKey('psr_bridge_version', $contracts);
    }

    public function testRouteSnapshotIsDeterministic(): void {
        Route::get('/zeta', function () { return 'z'; });
        Route::get('/alpha', function () { return 'a'; });
        Route::post('/alpha', function () { return 'ap'; });

        $first = Introspect::snapshotRoutes();
        $second = Introspect::snapshotRoutes();

        $this->assertSame($first, $second);
        $this->assertSame('/alpha', $first[0]['pattern']);
    }

    public function testSchemaAndModelSnapshots(): void {
        $schema = Schema::snapshotTables();
        $models = Model::snapshotFields(['SnapshotModelForTest']);

        $this->assertArrayHasKey('snapshot_items', $schema);
        $this->assertArrayHasKey('SnapshotModelForTest', $models);
        $this->assertSame(['id', 'name'], $models['SnapshotModelForTest']);
    }
}
