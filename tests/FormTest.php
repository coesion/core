<?php

use PHPUnit\Framework\TestCase;

class FormTest extends TestCase {

    protected $serverBackup = [];
    protected $getBackup = [];
    protected $postBackup = [];
    protected $requestBackup = [];

    protected function setUp(): void {
        parent::setUp();
        $this->serverBackup = $_SERVER;
        $this->getBackup = $_GET;
        $this->postBackup = $_POST;
        $this->requestBackup = $_REQUEST;

        $_SERVER = [];
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];

        Options::set('core.form.flash_key', '_form_old');
        Session::set('_form_old', []);
    }

    protected function tearDown(): void {
        $_SERVER = $this->serverBackup;
        $_GET = $this->getBackup;
        $_POST = $this->postBackup;
        $_REQUEST = $this->requestBackup;
        Session::set('_form_old', []);
        parent::tearDown();
    }

    public function testSubmitFromInputWithDefaultsOnlyAndNormalizers(): void {
        $_REQUEST = [
            'email' => '  TEST@Example.com ',
            'name' => '  Alpha  ',
            'ignored' => 'drop',
        ];

        $res = Form::submit([
            'email' => 'required|email',
            'name' => 'required|min_length:3',
            'state' => 'required',
        ], [
            'csrf' => false,
            'defaults' => ['state' => 'draft'],
            'only' => ['email', 'name', 'state'],
            'normalizers' => [
                'email' => function ($value) {
                    return strtolower(trim((string) $value));
                },
                'name' => function ($value) {
                    return trim((string) $value);
                },
            ],
        ]);

        $this->assertTrue($res['valid']);
        $this->assertSame('test@example.com', $res['data']['email']);
        $this->assertSame('Alpha', $res['data']['name']);
        $this->assertSame('draft', $res['data']['state']);
        $this->assertArrayNotHasKey('ignored', $res['data']);
        $this->assertSame([], $res['errors']);
        $this->assertFalse($res['csrf']['checked']);
        $this->assertTrue($res['csrf']['valid']);
    }

    public function testSubmitSupportsPostAndGetSources(): void {
        $_POST = ['qty' => '15'];
        $_GET = ['qty' => '22'];

        $post = Form::submit(['qty' => 'numeric'], ['source' => 'post', 'csrf' => false]);
        $get = Form::submit(['qty' => 'numeric'], ['source' => 'get', 'csrf' => false]);

        $this->assertSame('15', $post['data']['qty']);
        $this->assertSame('22', $get['data']['qty']);
    }

    public function testSubmitFailureFlashesOldInputAndExposesErrors(): void {
        $_REQUEST = ['email' => 'not-an-email'];

        $res = Form::submit([
            'email' => 'required|email',
            'name' => 'required',
        ], [
            'csrf' => false,
        ]);

        $this->assertFalse($res['valid']);
        $this->assertArrayHasKey('email', $res['errors']);
        $this->assertArrayHasKey('name', $res['errors']);
        $this->assertSame('not-an-email', Form::old('email'));
        $this->assertSame(['email' => 'not-an-email'], Form::old());
    }

    public function testSubmitChecksCsrfOnMutatingMethod(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_REQUEST = ['email' => 'alpha@example.com'];

        $resFail = Form::submit(['email' => 'required|email'], [
            'csrf' => true,
        ]);

        $this->assertFalse($resFail['valid']);
        $this->assertTrue($resFail['csrf']['checked']);
        $this->assertFalse($resFail['csrf']['valid']);
        $this->assertArrayHasKey('_csrf', $resFail['errors']);

        $token = Form::csrfToken();
        $_SERVER['HTTP_X_CSRF_TOKEN'] = $token;

        $resPass = Form::submit(['email' => 'required|email'], [
            'csrf' => true,
        ]);

        $this->assertTrue($resPass['valid']);
        $this->assertTrue($resPass['csrf']['checked']);
        $this->assertTrue($resPass['csrf']['valid']);
    }

    public function testCsrfFieldAndManualFlashHelpers(): void {
        Form::flash(['title' => 'hello']);
        $this->assertSame('hello', Form::old('title'));

        $field = Form::csrfField();
        $this->assertStringContainsString('type="hidden"', $field);
        $this->assertStringContainsString('name="_csrf"', $field);
        $this->assertStringContainsString('value="', $field);
    }
}
