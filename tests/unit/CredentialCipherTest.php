<?php

namespace Tests\Unit;

use App\Libraries\CredentialCipher;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class CredentialCipherTest extends CIUnitTestCase
{
    public function testEncryptDecryptRoundTrip(): void
    {
        $plain = 'super-secret-db-password';

        $cipher = CredentialCipher::encrypt($plain);

        $this->assertNotSame($plain, $cipher, 'Ciphertext must not equal the plaintext.');
        $this->assertSame($plain, CredentialCipher::decrypt($cipher));
    }

    public function testEncryptingTheSamePlaintextTwiceProducesDifferentCiphertext(): void
    {
        $plain = 'same-password';

        $first  = CredentialCipher::encrypt($plain);
        $second = CredentialCipher::encrypt($plain);

        // CI4's encrypter is randomized (fresh IV per call), so two
        // encryptions of the same plaintext must not be identical —
        // otherwise stored ciphertexts would be comparable/guessable.
        $this->assertNotSame($first, $second);
        $this->assertSame($plain, CredentialCipher::decrypt($first));
        $this->assertSame($plain, CredentialCipher::decrypt($second));
    }

    public function testEncryptHandlesEmptyStringRoundTrip(): void
    {
        // The demo/local dev credential legitimately has an empty
        // password (root with no password) — must round-trip cleanly.
        $cipher = CredentialCipher::encrypt('');

        $this->assertSame('', CredentialCipher::decrypt($cipher));
    }
}
