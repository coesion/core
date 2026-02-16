<?php

use PHPUnit\Framework\TestCase;
use Interop\CoreMiddlewarePipelineAdapter;
use Interop\CoreRequestAdapter;
use Interop\CoreResponseAdapter;

class InteropTest extends TestCase {

    public function testContainerAdapter(): void {
        Service::register('interop_value', function () {
            return 'ok';
        });

        $container = new Interop\CoreServiceContainerAdapter();
        $this->assertTrue($container->has('interop_value'));
        $this->assertSame('ok', $container->get('interop_value'));
    }

    public function testMiddlewarePipelineAdapter(): void {
        $req = new CoreRequestAdapter('GET', '/interop');

        $pipeline = new CoreMiddlewarePipelineAdapter([
            function ($request, $next) {
                $res = $next->handle($request);
                return new CoreResponseAdapter($res->statusCode(), $res->headers(), $res->body() . '-m1');
            },
        ], function ($request) {
            return new CoreResponseAdapter(200, ['X-Test' => ['yes']], 'ok');
        });

        $res = $pipeline->handle($req);
        $this->assertSame(200, $res->statusCode());
        $this->assertSame('ok-m1', $res->body());
    }
}
