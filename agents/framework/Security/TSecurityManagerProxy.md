# Security/TSecurityManagerProxy

### Directories
[framework](../INDEX.md) / [Security](./INDEX.md) / **`TSecurityManagerProxy`**

## Class Info
**Location:** `framework/Security/TSecurityManagerProxy.php`
**Namespace:** `Prado\Security`
**Extends:** [`TSecurityManager`](./TSecurityManager.md); implements `IModuleDependency`, [`IProxy`](../IProxy.md); uses [`TComponentProxyTrait`](../TComponentProxyTrait.md) (@since 4.4.0)

## Overview
Transparent security manager module that delegates every `TSecurityManager` operation to the module named by `BackingSecurityManagerId`. Lets the application security manager be swapped in configuration without touching consumers. The proxy holds no keys of its own.

## Configuration

```xml
<modules>
    <module id="security" class="Prado\Security\TSecurityManagerProxy" BackingSecurityManagerId="realSecurity" />
    <module id="realSecurity" class="Prado\Security\TSecurityManager" ValidationKey="..." EncryptionKey="..." />
</modules>
```

Keys and algorithms belong on the backing module. A key or algorithm attribute on the proxy is forwarded to the backing when applied.

## Properties

- `BackingSecurityManagerId` (string) — required by `init()` (`securitymanagerproxy_backing_security_manager_id_required`). Replacing a non-empty ID calls `detachProxy()`, logs a `TLogger::WARNING` (`prado.security`), and drops the resolved backing.
- `SecurityManager` (read-only `TSecurityManager`) — resolves the backing on first call; throws `securitymanagerproxy_security_manager_not_found` or `securitymanagerproxy_invalid_security_manager_type`, then calls `attachProxy()`.
- `ValidationKey`, `EncryptionKey`, `HashAlgorithm`, `EncryptionKeyAlgorithm`, `UseEncryptionHmac`, `CryptAlgorithm`, `ClosureSecretKey`, `ClosureUnencrypted` — every getter and setter forwards to the backing.

## Behavior

- `encrypt()`, `decrypt()`, `hashData()`, `validateData()`, `encryptClosure()`, `decryptClosure()`, `getShouldEncryptClosure()`, `supportedHashAlgorithms()`, `supportedCipherAlgorithms()`, and `getCSPNonce()` call the backing's public methods. Base-class methods are not reached by `__call`, so each is overridden explicitly.
- `init()` calls `TSecurityManager::init()`, which registers the proxy as the application security manager (`setAppSecurityManager()`). `setupSerializableClosure()` is overridden as a no-op: the backing already configured `TSerializableClosure` in its own `init()`, which runs first through the dependency, and the proxy resolves no keys during init.
- `getModuleDependencies()` returns the backing ID so the backing initializes first.
- The backing and forwarder list are excluded from serialization and re-resolved after `unserialize()`.

## See Also
- [TSecurityManager](./TSecurityManager.md), [TModuleProxy](../TModuleProxy.md), [TCacheProxy](../Caching/TCacheProxy.md)
