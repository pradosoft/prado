# Util/TSerializableClosure

### Directories
[framework](../INDEX.md) / [Util](./INDEX.md) / **`TSerializableClosure`**

## Class Info
**Location:** `framework/Util/TSerializableClosure.php`
**Namespace:** `Prado\Util`
**Extends:** `\Laravel\SerializableClosure\SerializableClosure` (composer: `laravel/serializable-closure`)
**Since:** 4.4.0

## Overview
`TSerializableClosure` is a thin wrapper around `laravel/serializable-closure` that lets a PHP `\Closure` survive `serialize()`/`unserialize()`, which the language otherwise forbids. This makes closures storable — for example as a cron task handler in the database, or in the cache and session.

The instance is directly invokable and the underlying closure is recovered with `getClosure()`. Captured `use` variables, arrow-function bindings, `$this`, and the class scope are preserved across serialization.

```php
$wrapped = new TSerializableClosure(fn ($x) => $x * 2);
echo $wrapped(3);                       // 6

$data = serialize($wrapped);
$closure = unserialize($data)->getClosure();
echo $closure(3);                       // 6
```

## Key Methods

| Method | Description |
|--------|-------------|
| `__construct(\Closure $closure)` | Wraps a closure. |
| `__invoke(...$args)` | Calls the wrapped closure. |
| `getClosure(): \Closure` | Returns the wrapped closure. |
| `static setSecretKey(?string $secret)` | (inherited) Sets the HMAC signing key directly. The application `TSecurityManager` sets this on init. |
| `static setEncryptionUsing(?callable $encrypt, ?callable $decrypt)` | Sets the encrypter that makes serialized payloads unreadable. The application `TSecurityManager` sets this on init. |
| `static unsigned(\Closure $closure)` | (inherited) Creates an unsigned wrapper. |

## Security
Unserializing a closure reconstructs its code with `eval()`, so any stored payload is executable code and a tampered payload is remote code execution. The application `TSecurityManager` configures closure protection during its `init()`:

- **Signing** — the SHA-512 hash of `ClosureSecretKey` (which defaults to, or backs up to, the validation key) is set as the HMAC secret, so neither the raw closure key nor the raw validation key is ever the signing secret directly. Signing is on by default once the security manager initializes.
- **Encryption** — when `TSecurityManager::getShouldEncryptClosure()` is true (the `ClosureUnencrypted` property `?bool`: `false`, or `null` = Auto when the application mode is `Normal` or `Performance`), `setEncryptionUsing()` installs the manager's `encryptClosure`/`decryptClosure`, so the serialized payload is **not readable**. While developing (`Debug` or `Off`, with Auto) the payload stays readable. On restore, a decrypter returning `false` (tampered payload or changed key) raises `TConfigurationException`.

Once a key is set, serialized payloads are HMAC-signed and `unserialize()` rejects tampered or unsigned payloads with a `\Laravel\SerializableClosure\Exceptions\InvalidSignatureException`. When an encrypter is set, `__serialize` returns the encrypted payload and `__unserialize` decrypts it (throwing `TConfigurationException` if an encrypted payload has no decrypter).

## Limitations
- Closures created in `eval()` or an interactive shell (no source file) cannot be serialized.
- By-reference `use (&$var)` captures are copied by value, not by reference.
- A closure bound to a non-serializable `$this` cannot be serialized.

## Notes
- Registered in `framework/classes.php` as `TSerializableClosure`.
- Backed by the `laravel/serializable-closure` (MIT) runtime dependency.
