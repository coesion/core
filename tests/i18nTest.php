<?php

use PHPUnit\Framework\TestCase;

class i18nTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        i18n::flush();
        i18n::locale('en');
        i18n::fallback('en');
    }

    protected function tearDown(): void {
        i18n::flush();
        parent::tearDown();
    }

    public function testLocaleGetSet(): void {
        $this->assertSame('en', i18n::locale());
        i18n::locale('fr');
        $this->assertSame('fr', i18n::locale());
    }

    public function testFallbackGetSet(): void {
        $this->assertSame('en', i18n::fallback());
        i18n::fallback('de');
        $this->assertSame('de', i18n::fallback());
    }

    public function testLoadArrayAndTranslate(): void {
        i18n::loadArray('en', [
            'greeting' => 'Hello',
            'farewell' => 'Goodbye',
        ]);

        $this->assertSame('Hello', i18n::t('greeting'));
        $this->assertSame('Goodbye', i18n::t('farewell'));
    }

    public function testDotNotationKeys(): void {
        i18n::loadArray('en', [
            'user' => [
                'welcome' => 'Welcome, friend!',
                'profile' => [
                    'title' => 'Your Profile',
                ],
            ],
        ]);

        $this->assertSame('Welcome, friend!', i18n::t('user.welcome'));
        $this->assertSame('Your Profile', i18n::t('user.profile.title'));
    }

    public function testParameterSubstitution(): void {
        i18n::loadArray('en', [
            'greeting' => 'Hello, {{ name }}!',
        ]);

        $this->assertSame('Hello, Alice!', i18n::t('greeting', ['name' => 'Alice']));
    }

    public function testMissingKeyReturnsKey(): void {
        $this->assertSame('missing.key', i18n::t('missing.key'));
    }

    public function testFallbackLocale(): void {
        i18n::loadArray('en', ['title' => 'Title EN']);
        i18n::loadArray('fr', ['subtitle' => 'Sous-titre']);
        i18n::locale('fr');
        i18n::fallback('en');

        // 'title' not in 'fr', should fallback to 'en'
        $this->assertSame('Title EN', i18n::t('title'));
        // 'subtitle' is in 'fr'
        $this->assertSame('Sous-titre', i18n::t('subtitle'));
    }

    public function testHas(): void {
        i18n::loadArray('en', ['exists' => 'yes']);
        $this->assertTrue(i18n::has('exists'));
        $this->assertFalse(i18n::has('nope'));
    }

    public function testAll(): void {
        $data = ['a' => '1', 'b' => '2'];
        i18n::loadArray('en', $data);
        $this->assertSame($data, i18n::all('en'));
    }

    public function testFlush(): void {
        i18n::loadArray('en', ['key' => 'value']);
        i18n::flush();
        $this->assertSame('key', i18n::t('key'));
    }

    public function testLoadFromJsonFile(): void {
        $tmpFile = sys_get_temp_dir() . '/core_i18n_test_' . uniqid() . '.json';
        file_put_contents($tmpFile, json_encode(['hello' => 'Hola']));

        i18n::load('es', $tmpFile);
        i18n::locale('es');
        $this->assertSame('Hola', i18n::t('hello'));

        @unlink($tmpFile);
    }

    public function testLoadFromPhpFile(): void {
        $tmpFile = sys_get_temp_dir() . '/core_i18n_test_' . uniqid() . '.php';
        file_put_contents($tmpFile, '<?php return ["hello" => "Hallo"];');

        i18n::load('de', $tmpFile);
        i18n::locale('de');
        $this->assertSame('Hallo', i18n::t('hello'));

        @unlink($tmpFile);
    }

    public function testMergeTranslations(): void {
        i18n::loadArray('en', ['a' => '1']);
        i18n::loadArray('en', ['b' => '2']);
        $this->assertSame('1', i18n::t('a'));
        $this->assertSame('2', i18n::t('b'));
    }
}
