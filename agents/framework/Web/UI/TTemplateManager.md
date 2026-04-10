# Web/UI/TTemplateManager

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [UI](./INDEX.md) / **`TTemplateManager`**

## Class Info
**Location:** `framework/Web/UI/TTemplateManager.php`
**Namespace:** `Prado\Web\UI`

## Overview
Module responsible for loading and parsing control templates (`.tpl` and `.page` files). Registered with [TPageService](../Services/TPageService.md) as the template manager; accessible via `TPageService::getTemplateManager()`.

Provides two loading paths:
1. **By class name** — `getTemplateByClassName()` derives the file path from the class's source file location.
2. **By file path** — `getTemplateByFileName()` loads directly from a known path.

Both paths support **culture-specific template variants** (via [TGlobalization](../I18N/TGlobalization.md)::`getLocalizedResource()`) and **application cache** for parsed templates. In `Performance` mode the mtime check is skipped entirely — cached templates are returned as-is.

## Key Constants

| Constant | Value | Description |
|---|---|---|
| `TEMPLATE_FILE_EXT` | `'.tpl'` | Extension for template files |
| `TEMPLATE_CACHE_PREFIX` | `'prado:template:'` | Cache key prefix for serialized templates |

## Key Properties

| Property | Type | Description |
|---|---|---|
| `$_defaultTemplateClass` | `string` | Default class used to instantiate templates. Default: `TTemplate::class`. Since 4.3.0. |

## Key Methods

### Initialization

- `init($config)` — Registers itself with the application via `Prado::getApplication()->setTemplateManager($this)`.

### Template Loading

- `getTemplateByClassName(string $className): ?TTemplate` — Reflects on `$className` to find its source file; appends `.tpl` extension; delegates to `getTemplateByFileName()`.
- `getTemplateByFileName(string $fileName, ?string $tplClass = null, ?string $culture = null): ?TTemplate` — Full loading pipeline:
  1. Resolves `$tplClass` to `$_defaultTemplateClass` if null; returns `null` if class doesn't implement [ITemplate](./ITemplate.md).
  2. Calls `getLocalizedTemplate()` to find a culture-variant file.
  3. If no application cache: returns `new $tplClass(file_get_contents($fileName), ...)` directly.
  4. If cache hit: in `Performance` mode, returns cached template immediately (no mtime check). Otherwise validates all included file timestamps.
  5. On cache miss or stale: parses template, collects `getIncludedFiles()` timestamps, stores `[$template, $timestamps]` in cache under key `TEMPLATE_CACHE_PREFIX . $fileName . ':' . $tplClass`.
- `getDefaultTemplateClass(): string` — Returns the current default template class. Since 4.3.0.
- `setDefaultTemplateClass(string $tplClass)` — Sets the default template class (string, coerced via `TPropertyValue::ensureString()`). Since 4.3.0.

### Culture Support (protected)

- `getLocalizedTemplate(string $filename, ?string $culture = null): ?string` — If globalization is active, iterates [TGlobalization](../I18N/TGlobalization.md)::`getLocalizedResource()` candidates and returns the first that exists as a real file. If no globalization module, returns `$filename` directly if it exists.

## Patterns & Gotchas

- **Cache key includes `$tplClass`** — `TEMPLATE_CACHE_PREFIX . $fileName . ':' . $tplClass`. Different template classes for the same file produce separate cache entries.
- **Performance mode skips mtime** — When `TApplication::getMode() === TApplicationMode::Performance`, cached templates are returned without checking whether any included file has changed on disk. This is a significant speed gain but requires manual cache clearing after template changes in production.
- **Included file timestamps** — the cache stores `filemtime()` for the main template and all files returned by `$template->getIncludedFiles()`. Any file newer than its cached timestamp invalidates the cache entry.
- **`DefaultTemplateClass` must implement `ITemplate`** — `getTemplateByFileName()` checks `is_subclass_of($tplClass, ITemplate::class)` and returns `null` if the check fails.
- **Culture variants** — [TGlobalization](../I18N/TGlobalization.md)::`getLocalizedResource()` returns a list of candidate paths in preference order (e.g., `MyPage.zh_CN.tpl`, `MyPage.zh.tpl`, `MyPage.tpl`). The first existing file wins.
- **Skin files use `TSkinTemplate`** — [TTheme](./TTheme.md) calls `getTemplateByFileName()` with [TSkinTemplate](./TSkinTemplate.md)::`class` as `$tplClass` to disable attribute validation during skin parsing.
- **`TPageService` is the default service** — `getService()` returns [TPageService](../Services/TPageService.md); the `@method` annotation reflects this.

(End of file - total 59 lines)
