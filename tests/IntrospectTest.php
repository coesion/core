<?php

use PHPUnit\Framework\TestCase;

class IntrospectTest extends TestCase {

    public function testClassesReturnsArray(): void {
        $classes = Introspect::classes();
        $this->assertIsArray($classes);
        $this->assertNotEmpty($classes);
    }

    public function testClassesContainsCoreClasses(): void {
        class_exists('Hash');
        class_exists('SQL');
        class_exists('Route');
        $classes = Introspect::classes();
        $this->assertContains('Hash', $classes);
        $this->assertContains('SQL', $classes);
        $this->assertContains('Route', $classes);
    }

    public function testMethodsReturnsPublicMethods(): void {
        $methods = Introspect::methods('Hash');
        $this->assertIsArray($methods);
        $this->assertContains('make', $methods);
        $this->assertContains('verify', $methods);
        $this->assertContains('uuid', $methods);
    }

    public function testMethodsIncludesExtensions(): void {
        Hash::extend('testIntrospectMethod', function() { return 'test'; });
        $methods = Introspect::methods('Hash');
        $this->assertContains('testIntrospectMethod', $methods);
    }

    public function testExtensionsListsOnlyDynamic(): void {
        Hash::extend('testIntrospectDynamic', function() { return 42; });
        $extensions = Introspect::extensions('Hash');
        $this->assertContains('testIntrospectDynamic', $extensions);
        // Native methods should not appear
        $this->assertNotContains('make', $extensions);
    }

    public function testMethodsForNonexistentClassReturnsEmpty(): void {
        $methods = Introspect::methods('NonExistentClassXYZ');
        $this->assertSame([], $methods);
    }

    public function testExtensionsForClassWithoutModuleReturnsEmpty(): void {
        $extensions = Introspect::extensions('stdClass');
        $this->assertSame([], $extensions);
    }

    public function testRoutesReturnsArray(): void {
        $routes = Introspect::routes();
        $this->assertIsArray($routes);
    }

    public function testRoutesContainsRegisteredRoute(): void {
        Route::reset();
        Route::get('/introspect-test', function() { return 'ok'; });
        $routes = Introspect::routes();
        $found = false;
        foreach ($routes as $route) {
            if ($route['pattern'] === '/introspect-test') {
                $found = true;
                $this->assertContains('get', $route['methods']);
            }
        }
        $this->assertTrue($found, 'Registered route should appear in Introspect::routes()');
        Route::reset();
    }

    public function testCapabilitiesReturnsMap(): void {
        $caps = Introspect::capabilities();
        $this->assertIsArray($caps);
        $this->assertArrayHasKey('pdo', $caps);
        $this->assertArrayHasKey('json', $caps);
        $this->assertArrayHasKey('curl', $caps);
        $this->assertIsBool($caps['pdo']);
    }

    public function testCapabilitiesIncludeCoreSection(): void {
        Route::reset();
        Auth::boot();
        Schedule::flush();
        Schedule::register('test-job', '* * * * *', 'test.type');

        $caps = Introspect::capabilities();

        $this->assertArrayHasKey('core', $caps);
        $this->assertIsArray($caps['core']);
        $this->assertArrayHasKey('zero_runtime_dependencies', $caps['core']);
        $this->assertArrayHasKey('runtime_dependency_count', $caps['core']);
        $this->assertArrayHasKey('route', $caps['core']);
        $this->assertArrayHasKey('auth', $caps['core']);
        $this->assertArrayHasKey('cache', $caps['core']);
        $this->assertArrayHasKey('schedule', $caps['core']);

        $this->assertTrue($caps['core']['auth']['booted']);
        $this->assertSame(1, $caps['core']['schedule']['registered_jobs']);
        $this->assertIsString($caps['core']['cache']['driver']);
        $this->assertIsBool($caps['core']['cache']['driver_loaded']);
        $this->assertIsBool($caps['core']['zero_runtime_dependencies']);
        $this->assertIsInt($caps['core']['runtime_dependency_count']);

        Schedule::flush();
        Route::reset();
    }

    public function testCapabilitiesAreDeterministicAcrossCalls(): void {
        $first = Introspect::capabilities();
        $second = Introspect::capabilities();
        $this->assertSame($first, $second);
    }
}
