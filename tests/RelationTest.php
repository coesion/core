<?php

use PHPUnit\Framework\TestCase;

class RelTarget extends Model {}
class RelSource extends Model {}

class RelationTest extends TestCase {
  public function testHasOneRegistersRelation(): void {
    RelSource::hasOne('RelTarget');
    $source = new RelSource();
    $this->assertTrue(isset($source->rel_target));
  }
}
