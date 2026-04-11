# Security/TSecurityManager

### Directories
[framework](../INDEX.md) / [Security](./INDEX.md) / **`TSecurityManager`**

## Class Info
**Location:** `framework/Security/TSecurityManager.php`
**Namespace:** `Prado\Security`

## Overview
Provides cryptographic utilities: HMAC generation, data hashing/validation, and encryption/decryption. Used internally for page-state signing and cookie token validation.

## Constants

| Constant | Description |
|----------|-------------|
| `STATE_VALIDATION_KEY` | Global state key for validation key |
| `STATE_ENCRYPTION_KEY` | Global state key for encryption key |

## Key Properties

| Property | Default | Description |
|----------|---------|-------------|
| `ValidationKey` | auto-generated | Private key for HMAC generation |
| `EncryptionKey` | auto-generated | Key for encrypt/decrypt |
| `HashAlgorithm` | `sha256` | Hash algorithm for HMAC |
| `CryptAlgorithm` | `aes-256-cbc` | Encryption algorithm |

## Key Methods

| Method | Description |
|--------|-------------|
| `getValidationKey(): string` | Returns validation key (auto-generates if not set) |
| `setValidationKey(string $value)` | Sets validation key |
| `getEncryptionKey(): string` | Returns encryption key (auto-generates if not set) |
| `setEncryptionKey(string $value)` | Sets encryption key |
| `hashData(string $data): string` | Prefixes data with HMAC |
| `validateData(string $data): string\|false` | Validates HMAC and returns data, or false if tampered |
| `encrypt(string $data): string` | Encrypts data (requires OpenSSL) |
| `decrypt(string $data): string` | Decrypts data (requires OpenSSL) |
| `computeHMAC(string $data): string` | Computes HMAC for data |

## See Also

- [TAuthManager](./TAuthManager.md) - Uses security manager for cookies
