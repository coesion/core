<?php

use PHPUnit\Framework\TestCase;

class CryptTest extends TestCase {

    public function testKeyGeneratesHexString(): void {
        $key = Crypt::key();
        $this->assertIsString($key);
        $this->assertTrue(ctype_xdigit($key));
        $this->assertGreaterThanOrEqual(64, strlen($key));
    }

    public function testEncryptDecryptRoundTrip(): void {
        $key = Crypt::key();
        $plaintext = 'Hello, World!';
        $encrypted = Crypt::encrypt($plaintext, $key);

        $this->assertIsString($encrypted);
        $this->assertNotEquals($plaintext, $encrypted);

        $decrypted = Crypt::decrypt($encrypted, $key);
        $this->assertSame($plaintext, $decrypted);
    }

    public function testEncryptDecryptEmptyString(): void {
        $key = Crypt::key();
        $encrypted = Crypt::encrypt('', $key);
        $decrypted = Crypt::decrypt($encrypted, $key);
        $this->assertSame('', $decrypted);
    }

    public function testEncryptDecryptUnicode(): void {
        $key = Crypt::key();
        $plaintext = 'Hëllö Wörld! 日本語テスト 🎉';
        $encrypted = Crypt::encrypt($plaintext, $key);
        $decrypted = Crypt::decrypt($encrypted, $key);
        $this->assertSame($plaintext, $decrypted);
    }

    public function testDecryptWithWrongKeyFails(): void {
        $key1 = Crypt::key();
        $key2 = Crypt::key();
        $encrypted = Crypt::encrypt('secret', $key1);
        $decrypted = Crypt::decrypt($encrypted, $key2);
        $this->assertFalse($decrypted);
    }

    public function testDecryptInvalidDataReturnsFalse(): void {
        $key = Crypt::key();
        $this->assertFalse(Crypt::decrypt('not-valid-base64!!!', $key));
    }

    public function testDecryptTruncatedDataReturnsFalse(): void {
        $key = Crypt::key();
        $encrypted = Crypt::encrypt('test', $key);
        $truncated = substr($encrypted, 0, 10);
        $this->assertFalse(Crypt::decrypt($truncated, $key));
    }

    public function testAvailable(): void {
        $this->assertTrue(Crypt::available());
    }

    public function testDifferentPlaintextsProduceDifferentCiphertexts(): void {
        $key = Crypt::key();
        $a = Crypt::encrypt('aaa', $key);
        $b = Crypt::encrypt('bbb', $key);
        $this->assertNotEquals($a, $b);
    }

    public function testSamePlaintextProducesDifferentCiphertexts(): void {
        $key = Crypt::key();
        $a = Crypt::encrypt('same', $key);
        $b = Crypt::encrypt('same', $key);
        // Due to random nonce, encryptions should differ
        $this->assertNotEquals($a, $b);
    }
}
