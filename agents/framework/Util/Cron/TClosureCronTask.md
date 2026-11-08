# Util/Cron/TClosureCronTask

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Cron](./INDEX.md) / **`TClosureCronTask`**

## Class Info
**Location:** `framework/Util/Cron/TClosureCronTask.php`
**Namespace:** `Prado\Util\Cron`
**Extends:** [`TCronTask`](TCronTask.md)
**Implements:** `\ArrayAccess`, `\Countable`, `\IteratorAggregate`
**Since:** 4.4.0

## Overview
`TClosureCronTask` runs a PHP `\Closure` as a cron task. The closure is invoked with the task and the calling `TCronModule`:

```php
$task = new TClosureCronTask(function (TClosureCronTask $task, TCronModule $cron) {
    // ... do work ...
});
$task->setName('mytask');
$task->setSchedule('0 0 * * *');
$cron->addTask($task);
```

PHP cannot serialize a `\Closure`, so the closure is stored through [`TSerializableClosure`](../TSerializableClosure.md) and that payload is encrypted with a [`TSecurityManager`](../../Security/TSecurityManager.md) before being persisted in the cron table. Unserializing reconstructs the closure with `eval()`, so the default keeps the stored payload encrypted.

The task also carries a serializable key-value **`Data`** store for parameters/state the closure reads or mutates. The task is an `\ArrayAccess`/`\Countable`/`\IteratorAggregate` container, so the closure (which receives `$task`) can use array syntax:

```php
$task = new TClosureCronTask(function (TClosureCronTask $task, TCronModule $cron) {
    $task['runs'] = ($task['runs'] ?? 0) + 1;   // read/mutate carried state
    return $task['accountId'];
});
$task['accountId'] = 42;                          // or $task->setData(['accountId' => 42])
```

`Data` is serialized as a plain task property (not encrypted with the closure), so its values must be serializable and should not hold secrets.

## Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `Closure` | `\Closure` | — | The closure to run. Recovered on first access after unserialization. |
| `StoreRaw` | `bool\|string` | `false` | `false` encrypts the payload; `true` stores it raw/decrypted; `'Debug'` stores raw only when the application `Mode` is `Debug`, otherwise encrypts. |
| `SecurityManagerID` | `?string` | `null` | ID of the `TSecurityManager` module used for encryption. `null` or `''` uses the application security manager. |
| `SecurityManager` | `TSecurityManager` | — | (read-only) The resolved security manager from `SecurityManagerID`. |
| `Data` | `array` | `[]` | Serializable key-value store carried with the task. `setData()` accepts an array, a `\Traversable` (collected to an array), or `null` (clears). Also reachable via `\ArrayAccess` (`$task['k']`), `count($task)`, and iteration. |

Inherited from `TCronTask`: `Name`, `Schedule`, `UserName`, `ModuleId`, `AutoRemove`, `ProcessCount`, `LastExecTime`.

## Methods

| Method | Description |
|--------|-------------|
| `__construct(?\Closure $closure = null)` | Optionally sets the closure. |
| `execute(TCronModule $cron): mixed` | Runs the closure as `$closure($this, $cron)`. Throws if no closure is set. |
| `getClosure(): ?\Closure` / `setClosure(\Closure)` | Closure accessor; `setClosure` rejects non-closures. |
| `getSecurityManager(): TSecurityManager` | Resolves the security manager from `SecurityManagerID`. |
| `getData(): array` / `setData(null\|array\|\Traversable): static` | The carried data store; `setData` is chainable, accepts a `\Traversable` or `null` (clears). |
| `offsetGet/offsetSet/offsetExists/offsetUnset`, `count(): int`, `getIterator(): \Traversable` | Container access over `Data` (routed through `getData()`/`setData()`). |

## Serialization
On serialization, the closure is encoded into an internal payload (`TSerializableClosure` → `serialize()` → optional `encrypt()`); the raw `\Closure` is excluded. On unserialization, the payload is decrypted (when needed) and decoded lazily by `getClosure()`. The `Data` array is serialized alongside as a normal property (excluded when empty). The cron `options` column carries all of this, so **no schema change** is required.

## Security
- Default (`StoreRaw = false`) encrypts the payload — confidential and integrity-checked via the security manager HMAC.
- `StoreRaw = true` (and `'Debug'` while in Debug mode) stores the payload raw/decrypted. It stays integrity-protected: the security manager is resolved before signing and verifying, so every payload carries the HMAC signature the application `TSecurityManager` configures on init, and a tampered payload is rejected with `InvalidSignatureException` instead of being `eval()`ed.

## Notes
- Registered in `framework/classes.php` as `TClosureCronTask`.
- Error codes: `closurecrontask_no_closure`, `closurecrontask_closure_required`, `closurecrontask_invalid_securitymanager`, `closurecrontask_decrypt_failed`.
