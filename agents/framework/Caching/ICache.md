# Caching/ICache

### Directories
[framework](./INDEX.md) / [Caching](./Caching/INDEX.md) / **`ICache`**

**Location:** `framework/Caching/ICache.php`
**Namespace:** `Prado\Caching`

## Overview
`ICache` defines the contract that all PRADO cache implementations must follow.

## Interface Methods

```php
interface ICache {
    public function get($id): mixed;       // Returns cached value or false
    public function set($id, $value, $expire = 0, ICacheDependency $dependency = null): bool;
    public function add($id, $value, $expire = 0, ICacheDependency $dependency = null): bool;
    public function delete($id): bool;
    public function flush(): void;
}
```

## See Also

- [`TCache`](TCache.md) for full caching documentation including all implementations
