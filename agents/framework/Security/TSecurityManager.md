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
| `CookieValidationKey` | derived | Private key for cookie HMACs; defaults to `hash_hmac(HashAlgorithm, 'prado:cookie', ValidationKey)`. Changing it invalidates every signed cookie and no other data. @since 4.4.0 |
| `EncryptionKey` | auto-generated | Key for encrypt/decrypt; persisted in global state when auto-generated |
| `HashAlgorithm` | `sha256` | Hash algorithm for HMAC (any value from `hash_hmac_algos()`) |
| `CryptAlgorithm` | `aes-256-cbc` | OpenSSL cipher algorithm |
| `EncryptionKeyAlgorithm` | `sha256` | Algorithm used to hash `EncryptionKey` before passing to OpenSSL. Prado 4.3 defaulted to `md5`. @since 4.3.3 |
| `UseEncryptionHmac` | `true` (4.4.0) | When `true`, `encrypt()` prepends an HMAC over `[IV][ciphertext]` and `decrypt()` returns `false` for input without a valid HMAC. When `false`, `decrypt()` still honors a valid HMAC prefix and otherwise decrypts plain input. @since 4.3.3 |

## Key Methods

| Method | Description |
|--------|-------------|
| `hashData(string $data): string` | Prefixes data with HMAC (hex) |
| `validateData(string $data): string\|false` | Strips and validates HMAC; returns raw data or `false` |
| `hashCookieData(string $data): string` | Prefixes cookie data with an HMAC keyed by `CookieValidationKey` @since 4.4.0 |
| `validateCookieData(string $data): string\|false` | Validates `hashCookieData()` output @since 4.4.0 |
| `encrypt(string $data): string` | Encrypts with OpenSSL; prepends HMAC when `UseEncryptionHmac=true` |
| `decrypt(string $data): string\|false` | Decrypts; a valid HMAC prefix is honored with either setting; with `UseEncryptionHmac=true`, input without one returns `false` |
| `computeHMAC(string $data): string` | Protected: computes `hash_hmac(algo, data, validationKey)` |
| `computeCookieHMAC(string $data): string` | Protected: computes `hash_hmac(algo, data, cookieValidationKey)` @since 4.4.0 |
| `supportedHashAlgorithms(): array` | @since 4.3.3 — returns `hash_hmac_algos()` or `hash_algos()` |
| `supportedCipherAlgorithms(): array` | @since 4.3.3 — returns `openssl_get_cipher_methods()` |

## Authenticated Encryption (`UseEncryptionHmac`)

When `UseEncryptionHmac=true`, encrypted data layout is:

```
[HMAC (raw, N bytes)] [IV (raw, iv_len bytes)] [ciphertext (base64)]
```

`decrypt()` checks the leading bytes as an HMAC over the remainder first:
- HMAC valid → strips it, decrypts the authenticated payload, returns plaintext or `false` on cipher failure.
- HMAC absent/invalid, `UseEncryptionHmac=true` → `false`. Altered or stripped ciphertext is rejected.
- HMAC absent/invalid, `UseEncryptionHmac=false` → unauthenticated decryption of the full input.

Turning the property off keeps HMAC-encrypted data readable. Turning it on makes data encrypted while it was off unreadable; an application keeping such data re-encrypts it first or leaves the property off until the data expires. Before 4.4.0, `decrypt()` fell back to unauthenticated decryption with the property on, which let altered or stripped ciphertext decrypt.

## Gotchas

- **OpenSSL required** — `encrypt()` and `decrypt()` throw `TNotSupportedException` if the `openssl` extension is absent.
- **`EncryptionKeyAlgorithm` defaults to `sha256`** since 4.4.0. Set it to `md5` to decrypt data encrypted under the Prado 4.3 default.
- **`UseEncryptionHmac` defaults to `true`** since 4.4.0, and `decrypt()` then rejects ciphertext without an HMAC. Data encrypted under the 4.3 default (no HMAC) decrypts only with `UseEncryptionHmac="false"`.
- **Empty keys are rejected** — `setValidationKey('')`, `setCookieValidationKey('')`, and `setEncryptionKey('')` throw `TInvalidDataValueException`.
- **Cookies use `CookieValidationKey`** — `THttpRequest`/`THttpResponse` cookie validation, the `TAuthManager` auto-login cookie, and the `TUserManager` auto-login token. Set it to sign out every remembered user without touching page state or encrypted data.
- **Bytes, not characters** — HMAC and IV splitting use `strlen()` and `substr()`, which count bytes; PHP 8 removed `mbstring.func_overload`, so the old `mb_strlen(..., '8bit')` guard is gone.

## See Also

- [TAuthManager](./TAuthManager.md) - Uses security manager for cookies and session tokens
