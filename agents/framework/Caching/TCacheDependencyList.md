# Caching/TCacheDependencyList

### Directories
[framework](../INDEX.md) / [Caching](./INDEX.md) / **`TCacheDependencyList`**

## Class Info
**Location:** `framework/Caching/TCacheDependencyList.php`
**Namespace:** `Prado\Caching`

## Overview
[TCacheDependencyList](./TCacheDependencyList.md) is a typed collection that holds only [ICacheDependency](./ICacheDependency.md) objects. Extends [TList](../Collections/TList.md) with type validation.

## Usage

```php
$list = new TCacheDependencyList();
$list->add(new TFileCacheDependency('/path/to/file'));
$list->add(new TGlobalStateCacheDependency('myState'));
```

## See Also

- [TCache](./TCache.md) for full caching documentation