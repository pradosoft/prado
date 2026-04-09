# TThemeManager

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [TThemeManager](./TThemeManager.md)

**Location:** `framework/Web/UI/TThemeManager.php`
**Namespace:** `Prado\Web\UI`

## Overview

Module that manages themes for a Prado application. Each theme is a subdirectory under `BasePath`; the theme name equals the subdirectory name. Registered via `application.xml` and accessible via `Prado::getApplication()->getThemeManager()`.

`BasePath` and `BaseUrl` are both lazily resolved: if not explicitly configured they are auto-derived from the application entry script location and request URL at first use. Both become immutable after `init()` is called.

## Key Constants

| Constant | Value | Description |
|---|---|---|
| `DEFAULT_BASEPATH` | `'themes'` | Default subdirectory name relative to app entry script |
| `DEFAULT_THEMECLASS` | `TTheme::class` | Default class used to instantiate theme objects |

## Key Properties

| Property | Type | Description |
|---|---|---|
| `$_themeClass` | `string` | Class used to create `TTheme` instances; default `TTheme::class` |
| `$_initialized` | `bool` | Set to `true` in `init()`; guards against post-init BasePath changes |
| `$_basePath` | `?string` | Absolute filesystem path to themes directory |
| `$_baseUrl` | `?string` | Base URL for themes (trailing `/` stripped on set) |

## Key Methods

### Initialization

- `init($config)` — Sets `$_initialized = true`; registers self with application via `Prado::getApplication()->setThemeManager($this)`.

### Theme Access

- `getTheme(string $name): TTheme` — Returns a theme instance by name. Constructs the path as `getBasePath() . DIRECTORY_SEPARATOR . $name` and URL as `getBaseUrl() . '/' . $name`. Instantiates via `Prado::createComponent($this->getThemeClass(), $themePath, $themeUrl)`.
- `getAvailableThemes(): array` — Lists subdirectory names under `BasePath` (excludes `.`, `..`, `.svn`).

### Path & URL Resolution

- `getBasePath(): string` — Returns absolute path. If `$_basePath` is null, auto-derives as `dirname(applicationFilePath) . '/themes'` and resolves with `realpath()`. Throws `TConfigurationException` if the directory does not exist.
- `setBasePath(string $value)` — Accepts a **namespace-format** path (e.g., `'Application.themes'`). Resolves via `Prado::getPathOfNamespace()`. Throws `TInvalidOperationException` if called after `init()`; throws `TInvalidDataValueException` if the resolved path is not a valid directory.
- `getBaseUrl(): string` — Returns base URL. If null, auto-derives by computing the offset of `BasePath` relative to the application file's directory and appending to the application URL. Throws `TConfigurationException` if `BasePath` is outside the web root (cannot auto-derive a URL).
- `setBaseUrl(string $value)` — Stores the URL with trailing `/` stripped.

### Theme Class

- `getThemeClass(): string` — Returns current theme class name.
- `setThemeClass(?string $class)` — Sets theme class; `null` resets to `DEFAULT_THEMECLASS`.

## Patterns & Gotchas

- **Immutable after `init()`** — `setBasePath()` throws `TInvalidOperationException` if called after the module has been initialized. Configure `BasePath` and `BaseUrl` only in `application.xml` or before the module initializes.
- **`BasePath` must be namespace format** — `setBasePath()` calls `Prado::getPathOfNamespace()`; passing a raw filesystem path will fail. Use the Prado namespace format: `'Application.themes'` or `'Vendor.Package.themes'`.
- **`BaseUrl` auto-derivation requires `BasePath` inside web root** — if the themes directory is outside the web-accessible tree, `getBaseUrl()` throws `TConfigurationException('thememanager_baseurl_required')`. Set `BaseUrl` explicitly in that case.
- **Default `BasePath` is not validated until first use** — the `themes` directory doesn't need to exist until `getBasePath()` is called. Missing directory at that point throws `TConfigurationException('thememanager_basepath_invalid2')`.
- **`getAvailableThemes()` uses `opendir()`** with `@` suppression — silently returns empty array if `BasePath` is unreadable.
- **`getTheme()` does not validate theme existence** — it constructs the path and passes it to the [TTheme](./TTheme.md) constructor without checking if the directory exists first; [TTheme](./TTheme.md) will throw or silently operate on a missing directory.
- **Configuring in XML:**
  ```xml
  <module id="themes" class="Prado\Web\UI\TThemeManager"
          BasePath="Application.themes" BaseUrl="/themes" />
  ```

(End of file - total 63 lines)
