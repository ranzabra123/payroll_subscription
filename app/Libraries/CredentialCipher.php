<?php

namespace App\Libraries;

/**
 * Encrypts/decrypts tenant DB credentials for storage in the landlord DB.
 * Uses CI4's Encryption service, keyed only from .env (encryption.key) —
 * the key never touches the database or version control.
 */
class CredentialCipher
{
    public static function encrypt(string $plain): string
    {
        return base64_encode(service('encrypter')->encrypt($plain));
    }

    public static function decrypt(string $cipherB64): string
    {
        return service('encrypter')->decrypt(base64_decode($cipherB64));
    }
}
