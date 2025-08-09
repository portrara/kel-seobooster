<?php

use KSEO\SEO_Booster\Security\Crypto;
use KSEO\SEO_Booster\Security\Keyring;

class CryptoTest extends \PHPUnit\Framework\TestCase {
    public function testEncryptDecryptRoundTrip(): void {
        if (!class_exists(Crypto::class)) {
            $this->markTestSkipped('Crypto class not autoloaded');
        }
        $plain = 'secret-value-ä✓';
        $enc = Crypto::encrypt($plain);
        $this->assertIsString($enc);
        $this->assertNotSame($plain, $enc);
        $dec = Crypto::decrypt($enc);
        $this->assertSame($plain, $dec);
    }

    public function testTamperDetection(): void {
        $plain = 'secret-value';
        $enc = Crypto::encrypt($plain);
        $tampered = substr($enc, 0, -2) . 'AA';
        $dec = Crypto::decrypt($tampered);
        $this->assertSame('', $dec, 'Tampered ciphertext should not decrypt');
    }
}

