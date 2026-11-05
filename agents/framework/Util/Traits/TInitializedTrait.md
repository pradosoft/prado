# Util/Traits/TInitializedTrait

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Traits](./INDEX.md) / **`TInitializedTrait`**

## Class Info
**Location:** `framework/Util/Traits/TInitializedTrait.php`
**Namespace:** `Prado\Util\Traits`
**Since:** 4.3.3

## Overview
`TInitializedTrait` manages three-phase initialization state (`null` → `false` → `true`) for modules and other classes. It lets configuration-phase property setters be frozen after `init()` finishes, and lets runtime methods guard themselves against being called too early.

The three states:

| `$_initialized` | Meaning | Query method |
|-----------------|---------|--------------|
| `null` | Not yet started | `getIsUninitialized()` |
| `false` | Currently initializing | `getIsInitializing()` |
| `true` | Fully initialized | `getIsInitialized()` |

## Standard Usage

```php
use Prado\Util\Traits\TInitializedTrait;

class MyModule extends TModule
{
    use TInitializedTrait;

    private string $_tableName = 'mytable';

    public function setTableName(string $value): void
    {
        $this->assertUninitialized('TableName');
        $this->_tableName = $value;
    }

    public function init($config): void
    {
        // … setup …
        parent::init($config);
        $this->markInitialized();
    }
}
```

## Safe Initialization (with error handling)

```php
public function init($config): void
{
    try {
        $this->markStartInitialize();
        // … setup …
        parent::init($config);
        $this->markInitialized();
    } catch (\Exception $e) {
        $this->resetInitialized();
        throw $e;
    }
}
```

## State Methods

```php
$obj->getIsUninitialized(): bool    // true while $_initialized === null
$obj->getIsInitializing(): bool     // true while $_initialized === false
$obj->getIsInitialized(): bool      // true once markInitialized() is called
```

## Lifecycle Methods (protected)

```php
$this->markStartInitialize(): void  // null → false; idempotent
$this->markInitialized(): void      // * → true; idempotent
$this->resetInitialized(): void     // * → null (use in catch block)
```

## Guard Methods (protected)

```php
// Throw if already initialized — use in setters:
$this->assertUninitialized(string $property, ?string $exceptionKey = null): void

// Throw if NOT yet initialized — use in runtime methods:
$this->assertInitialized(string $property, ?string $exceptionKey = null): void
```

Both throw `TInvalidOperationException`. The exception message includes the property name and short class name.

## Overridable Exception Keys

Override to supply module-specific catalogue keys for error messages:

```php
protected function getIsInitializedExceptionKey(): string
{
    return 'mymodule_property_unchangeable';  // default: 'initialized_property_unchangeable'
}

protected function getIsNotInitializedExceptionKey(): string
{
    return 'mymodule_requires_initialization';  // default: 'initialized_requires_initialization'
}
```

## Users in Prado

- [`TParameterModule`](../TParameterModule.md)
- [`TDbParameterModule`](../TDbParameterModule.md)
- [`TCronModule`](../Cron/TCronModule.md)
- [`TDbCronManager`](../Cron/TDbCronManager.md)

## Patterns & Gotchas

- **`markInitialized()` after `parent::init()`** — call it last so that parent setup (which may also call setters) is not blocked by the guard.
- **`markStartInitialize()` is optional** — use it only when you need to prevent property mutation during the init phase itself (in addition to after).
- **`resetInitialized()` in catch** — restores the uninitialized state so that a module can be re-configured and re-initialized after a failure.
- **`assertUninitialized` via `hasMethod`** — `TDbModule.setConnectionID()` uses `$this->hasMethod('assertUninitialized')` to detect whether the concrete subclass mixes in this trait without requiring it directly.
