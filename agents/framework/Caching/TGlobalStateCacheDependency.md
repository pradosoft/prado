# Caching/TGlobalStateCacheDependency

### Directories
[framework](../INDEX.md) / [Caching](./INDEX.md) / **`TGlobalStateCacheDependency`**

## Class Info
**Location:** `framework/Caching/TGlobalStateCacheDependency.php`
**Namespace:** `Prado\Caching`

## Overview
[TGlobalStateCacheDependency](./TGlobalStateCacheDependency.md) watches a PRADO global state value. The cache is invalidated when the global state value changes.

## Usage

```php
$dependency = new TGlobalStateCacheDependency('app_version');
$cache->set('key', $value, 0, $dependency);
```

The constructor argument is optional. A dependency created without it, as `Prado::createComponent()` does for a component configured by attributes, is given its state through `StateName`:

```php
$dependency = Prado::createComponent(TGlobalStateCacheDependency::class);
$dependency->StateName = 'app_version';
```

## Properties

- `StateName` - Name of the global state to watch; `''` before it is set. Setting it captures the state's current value.

## Behavior

- `getHasChanged()` compares the state's current value with the captured value by strict comparison.
- `getHasChanged()` returns `false` before `StateName` is set.
- `updateStateValue()` captures the current value as the new baseline.

## See Also

- [TCache](./TCache.md) for full caching documentation