# Collections/TCollectionItemChangeParameter

### Directories
[framework](../INDEX.md) / [Collections](./INDEX.md) / **`TCollectionItemChangeParameter`**

## Class Info
**Location:** `framework/Collections/TCollectionItemChangeParameter.php`
**Namespace:** `Prado\Collections`
**Since:** 4.3.3

## Overview

Event parameter for collection change events that report a modification to a single keyed element (a map entry, global-state slot, or similar key→value structure). Extends `TEventParameter` and stores four values in the inherited parameter array: the changed key, new value, previous value, and a compact integer bitmask of state flags.

## Inheritance

Extends `Prado\TEventParameter` (which implements `ArrayAccess`).

## Flag Constants

Each constant occupies one bit and can be combined freely.

| Constant | Bit | Meaning |
|---|---|---|
| `IS_DEFAULT` | `1 << 0` | Value was reset to its default; the key was removed from the collection. |
| `IS_NEW` | `1 << 1` | The key did not previously exist; `oldValue` is `null` (placeholder). |
| `IS_UNSET` | `1 << 2` | The key was explicitly removed; `value`, `isDefault`, and `isNew` are not meaningful. |

## Parameter Array Key Constants

| Constant | Value | Description |
|---|---|---|
| `KEY` | `'key'` | Array key for the collection key that changed. |
| `VALUE` | `'value'` | Array key for the new value. |
| `OLD_VALUE` | `'oldValue'` | Array key for the previous value. |
| `FLAGS` | `'flags'` | Array key for the raw bitmask integer. |

## Constructor

```php
new TCollectionItemChangeParameter(
    string $key = '',
    mixed  $value = null,
    mixed  $oldValue = null,
    int    $flags = 0,
    bool   $readOnly = false
)
```

## Properties (Getters/Setters)

```php
$param->getKey(): string
$param->setValue(string $key): void

$param->getValue(): mixed          // null when IS_UNSET is set
$param->setValue(mixed $value): void

$param->getOldValue(): mixed       // null when IS_NEW is set (or old value really was null)
$param->setOldValue(mixed $oldValue): void

$param->getFlags(): int            // raw bitmask
$param->setFlags(int $flags): void

// Individual flag accessors (toggle one bit without disturbing others):
$param->getIsDefault(): bool
$param->setIsDefault(bool $value): void

$param->getIsNew(): bool
$param->setIsNew(bool $value): void

$param->getIsUnset(): bool
$param->setIsUnset(bool $value): void
```

## Array-Access Keys

All seven keys are readable via array access. `offsetExists` (`isset`) signals **semantic meaningfulness** for the current operation, not merely physical presence:

| Offset | `offsetExists` true when | Notes |
|---|---|---|
| `'key'` | always | The collection key that was modified. |
| `'value'` | `!isUnset` | Not meaningful when the key was removed. |
| `'isDefault'` | `!isUnset` | Derived from `flags`. |
| `'isNew'` | `!isUnset` | Derived from `flags`. |
| `'isUnset'` | `isUnset` | Derived from `flags`. |
| `'oldValue'` | `!isNew` | Not meaningful for brand-new keys. |
| `'flags'` | always | Raw bitmask; `0` when no flags are set. |

```php
// Array-access uses the constant strings:
$key      = $param[TCollectionItemChangeParameter::KEY];
$isNew    = $param['isNew'];
$oldValue = $param['oldValue'];
```

`offsetUnset` resets each named field to its zero/null default rather than removing the storage key.

## Patterns & Gotchas

- **`IS_NEW` as sentinel** — When `isNew` is `true`, `getOldValue()` returns `null` as a placeholder. When `isNew` is `false`, a `null` return from `getOldValue()` means the old value was legitimately `null`.
- **`IS_UNSET` supersedes others** — When set, `value`, `isDefault`, and `isNew` are undefined for the operation; check `isUnset` first.
- **Read-only enforcement** — Pass `$readOnly = true` to freeze the parameter after construction. All setters throw `TInvalidOperationException` when read-only (enforced by the parent `TEventParameter`).
- **Constant array keys** — Prefer the class constants (`KEY`, `VALUE`, etc.) over raw string literals in `offsetGet`/`offsetSet` calls.
