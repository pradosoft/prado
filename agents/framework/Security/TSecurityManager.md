# Security/TSecurityManager

### Directories
[framework](../INDEX.md) / [Security](./INDEX.md) / **`TSecurityManager`**

## Class Info
**Location:** `framework/Security/TSecurityManager.php`
**Namespace:** `Prado\Security`

## Overview
Provides cryptographic utilities: HMAC generation, data hashing/validation, and encryption/decryption. Used internally for page-state signing and cookie token validation. Registered with the application via `init()` → `$app->setSecurityManager($this)`.

## Configuration

```xml
<modules>
    <module id="security" class="Prado\Security\TSecurityManager"
        ValidationKey="your-secret-validation-key"
        EncryptionKey="your-secret-encryption-key"
        HashAlgorithm="sha256"
        CryptAlgorithm="aes-256-cbc"
        UseEncryptionHmac="true" />
</modules>
```

**PHP equivalent:**
```php
return [
    'modules' => [
        'security' => [
            'class' => 'Prado\Security\TSecurityManager',
            'properties' => ['EncryptionKey' => 'your-secret-encryption-key', 'ValidationKey' => 'your-secret-validation-key'],
        ],
    ],
];
```

## Constants

| Constant | Description |
|----------|-------------|
| `STATE_VALIDATION_KEY` | Global state key for persisting the auto-generated validation key |
| `STATE_ENCRYPTION_KEY` | Global state key for persisting the auto-generated encryption key |

## Key Properties

| Property | Default | Description |
|----------|---------|-------------|
| `ValidationKey` | auto-generated | Private key for HMAC generation; persisted in global state when auto-generated |
| `EncryptionKey` | auto-generated | Key for encrypt/decrypt; persisted in global state when auto-generated |
| `HashAlgorithm` | `sha256` | Hash algorithm for HMAC (any value from `hash_hmac_algos()`) |
| `CryptAlgorithm` | `aes-256-cbc` | OpenSSL cipher algorithm |
| `EncryptionKeyAlgorithm` | `md5` | Algorithm used to hash `EncryptionKey` before passing to OpenSSL. Upgrade to `sha256` for new deployments. @since 4.3.3 |
| `UseEncryptionHmac` | `false` | When `true`, `encrypt()` prepends an HMAC over `[IV][ciphertext]` to enable authenticated encryption. `decrypt()` always probes for an HMAC header regardless of this flag. @since 4.3.3 |

## Key Methods

| Method | Description |
|--------|-------------|
| `hashData(string $data): string` | Prefixes data with HMAC (hex) |
| `validateData(string $data): string\|false` | Strips and validates HMAC; returns raw data or `false` |
| `encrypt(string $data): string` | Encrypts with OpenSSL; prepends HMAC when `UseEncryptionHmac=true` |
| `decrypt(string $data): string\|false` | Decrypts; always probes for HMAC header first; returns `false` on tamper |
| `computeHMAC(string $data): string` | Protected: computes `hash_hmac(algo, data, validationKey)` |
| `supportedHashAlgorithms(): array` | @since 4.3.3 — returns `hash_hmac_algos()` or `hash_algos()` |
| `supportedCipherAlgorithms(): array` | @since 4.3.3 — returns `openssl_get_cipher_methods()` |

## Authenticated Encryption (`UseEncryptionHmac`)

When `UseEncryptionHmac=true`, encrypted data layout is:

```
[HMAC (raw, N bytes)] [IV (raw, iv_len bytes)] [ciphertext (base64)]
```

`decrypt()` always tries HMAC verification first:
- HMAC valid → strips it, decrypts the authenticated payload, returns plaintext or `false` on cipher failure.
- HMAC absent/invalid → falls back to unauthenticated decryption of the full input.

This dual-path design means ciphertext produced before or after enabling `UseEncryptionHmac` is always decryptable — migration in both directions is seamless.

## Gotchas

- **OpenSSL required** — `encrypt()` and `decrypt()` throw `TNotSupportedException` if the `openssl` extension is absent.
- **`EncryptionKeyAlgorithm` defaults to `md5`** for backward compatibility. New deployments should set it to `sha256` or stronger.
- **`UseEncryptionHmac` defaults to `false`** for backward compatibility. New deployments should enable it.
- **Empty keys are rejected** — `setValidationKey('')` and `setEncryptionKey('')` throw `TInvalidDataValueException`.
- **mbstring** — when the `mbstring` extension is present, binary-safe `mb_strlen`/`mb_substr` with `'8bit'` encoding are used internally to avoid multibyte-string splitting errors.

## See Also

- [TAuthManager](./TAuthManager.md) - Uses security manager for cookies and session tokens
