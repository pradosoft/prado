# TOutputCache

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md) > [TOutputCache](./TOutputCache.md)

**Location:** `framework/Web/UI/WebControls/TOutputCache.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview

Caches the rendered HTML of its child controls in the application cache. When a valid cached version exists, the control's children are **not initialized, loaded, or rendered** — only the cached output is replayed, along with any registered actions (client scripts, etc.).

Implements `INamingContainer`. Requires the application to have a cache module (`TCache` or compatible).

## Constants

```php
TOutputCache::CACHE_ID_PREFIX = 'prado:outputcache'
```

## Key Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `Duration` | int | 60 | Cache lifetime in seconds (0 = never expire; throws on negative) |
| `CacheModuleID` | string | '' | ID of the cache module to use (default: application default cache) |
| `CacheKeyPrefix` | string | '' | Additional string mixed into the cache key |
| `VaryByParam` | string | '' | Comma-separated URL parameter names to include in cache key |
| `VaryBySession` | bool | false | Include session ID in cache key |
| `CachingPostBack` | bool | false | Allow caching POST/postback requests (default: only GET) |
| `ContentCached` | bool | — | True if content was served from cache (read-only) |

## Key Methods

```php
$cache->getCacheKey(): string             // computed cache key
$cache->getContentCached(): bool          // was this request served from cache?
$cache->registerAction(string $context, string $funcName, array $funcParams): void
// Registers a client-side action to replay when serving from cache.
// $context: control's ClientID or page-level context
// $funcName: JavaScript function name
// $funcParams: parameters to pass
```

## Events

| Event | When |
|-------|------|
| `OnCheckDependency` | Check if cache is still valid; set `$param->setIsValid(false)` to invalidate |
| `OnCalculateKey` | Customize the cache key; set `$param->setCacheKey($key)` |

## Cache Key Calculation

Default key incorporates:
1. Control's UniqueID (its position in the page)
2. `CacheKeyPrefix`
3. Named URL parameters from `VaryByParam`
4. Session ID (if `VaryBySession=true`)
5. Current culture (for I18N)

Override via `OnCalculateKey` event or extend `calculateCacheKey()`.

## registerAction() Pattern

Use `registerAction()` instead of direct `TClientScriptManager` calls inside cached content. When served from cache, registered actions are replayed:

```php
// Instead of this inside a cached block:
$this->getPage()->getClientScript()->registerScript('key', $js);

// Do this:
$outputCache->registerAction('page', 'registerScript', ['key', $js]);
```

## Lifecycle When Content Is Cached

When `ContentCached=true`:
- `initRecursive()` for child controls is **skipped**
- `loadRecursive()` for child controls is **skipped**
- `preRenderRecursive()` for child controls is **skipped**
- Cached HTML is output directly
- Registered actions are replayed

**Never access child controls** when `ContentCached=true` — they do not exist.

## Template Usage

```xml
<com:TOutputCache Duration="300" VaryByParam="page,category">
    <!-- expensive content here -->
    <com:TDataGrid ID="grid" ... />
</com:TOutputCache>
```

```php
// Check if content came from cache:
if (!$this->outputCache->ContentCached) {
    $this->grid->DataSource = $this->loadExpensiveData();
    $this->grid->dataBind();
}
```

## Patterns & Gotchas

- **Do not access children when `ContentCached=true`** — child controls are not created when serving from cache. Always guard data binding with `if (!$this->outputCache->ContentCached)`.
- **Postback caching disabled by default** — set `CachingPostBack=true` only if the cached content is independent of postback state.
- **`Duration=0`** — caches indefinitely (until the application cache is cleared or the dependency invalidates it).
- **`OnCheckDependency`** — use this to invalidate the cache based on external conditions (e.g., database record updated):
  ```php
  protected function checkCacheDependency($sender, $param) {
      if ($this->dataVersion !== $cachedVersion) {
          $param->setIsValid(false);
      }
  }
  ```
- **`VaryBySession`** — include when cached content is user-specific. Without this, all users share the same cached output.
