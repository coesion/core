<?php

use PHPUnit\Framework\TestCase;

class EventsTraitTestClass { use Events; }
class FiltersTraitTestClass { use Filters; }
class ModuleTraitTestClass { use Module; }

class TraitsTest extends TestCase {
  public function testEventsTrait(): void {
    EventsTraitTestClass::on('ping', function() {
      return 'pong';
    });

    $results = EventsTraitTestClass::trigger('ping');
    $this->assertSame(['pong'], $results);

    $resultsOnce = EventsTraitTestClass::triggerOnce('ping');
    $this->assertSame(['pong'], $resultsOnce);
    $this->assertNull(EventsTraitTestClass::trigger('ping'));
  }

  public function testFiltersTrait(): void {
    FiltersTraitTestClass::filter('calc', function($value, $args) {
      return $value + 2;
    });

    $this->assertSame(5, FiltersTraitTestClass::filterWith('calc', 3));

    FiltersTraitTestClass::filterRemove('calc');
    $this->assertSame(3, FiltersTraitTestClass::filterWith('calc', 3));
  }

  public function testModuleTrait(): void {
    ModuleTraitTestClass::extend('sum', function($a, $b) {
      return $a + $b;
    });

    $this->assertSame(5, ModuleTraitTestClass::sum(2, 3));
  }
}
