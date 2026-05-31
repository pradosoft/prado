# Prado

### Directories
[framework](./INDEX.md) / **`Prado`**

## Class Info
**Location:** `framework/Prado.php`
**Namespace:** `Prado`

## Overview
Static utility class that bootstraps the framework. Entry point for autoloading, error handling, namespace management, application access, and logging. All methods are static; instantiate `TApplication` separately.

## Constants and Globals

```php
PRADO_DIR          // absolute path to framework/
PRADO_VENDORDIR    // absolute path to composer's vendor/
PRADO_DIR_CHMOD    // default directory permission (0755)
PRADO_FILE_CHMOD   // default file permission (0644)
PRADO_CHMOD        // legacy compat alias (0777)
```

## Core Methods

### Initialization
- `init()` — Calls `initAutoloader()` then `initErrorHandlers()`. Called once at bootstrap.
- `initAutoloader()` — Loads `framework/classes.php` classmap; registers `Prado::autoload` with `spl_autoload_register`.
- `initErrorHandlers()` — Sets `phpErrorHandler`, `phpFatalErrorHandler`, and `exceptionHandler`; disables PHP's built-in `display_errors`.
- `getVersion(): string` — Returns the Prado framework version string.

### Error Handling
- `phpErrorHandler($errno, $errstr, $errfile, $errline): bool` — Converts PHP errors to `TPhpErrorException`.
- `phpFatalErrorHandler()` — Shutdown function; converts fatal errors to `TPhpFatalErrorException`.
- `exceptionHandler(\Throwable $e)` — Fallback exception handler for uncaught exceptions.

### Component and Namespace Management
- `createComponent(string $type, mixed ...$args): TComponent` — Instantiates a class by PRADO namespace or PHP class name.
- `usingClass(string $namespace): string|false|null` — Like `using()` but returns the resolved PHP FQN as a string, `false` if the namespace identifies a directory, or `null` if it cannot be resolved. Preferred over `using()` when a class name is needed. @since 4.3.3
- `using(string $namespace): ?string` — Resolves a namespace and loads the file if needed. Returns the PHP FQN (class/interface/trait), a string ending in `\` for directories, or `null` if unresolvable. (Return type changed from `void` in 4.3.3.)
- `autoload(string $className): void` — Called by SPL autoloader; delegates to `using()`.

### Application Management
- `setApplication(TApplication $app): void` — Stores the singleton. Called by `TApplication::__construct`.
- `getApplication(): TApplication` — Returns the application singleton.

### Path and Namespace Management
- `getPathOfNamespace(string $namespace, string $ext = ''): ?string` — Converts a dotted namespace to a filesystem path.
- `getPathOfAlias(string $alias): ?string` — Returns the path registered for an alias.
- `getPathAliases(): array` — Returns all registered path aliases.
- `setPathOfAlias(string $alias, string $path): void` — Registers or overrides an alias.

### Object Visibility Helpers (@since 4.3.0)
- `method_visible(object|string $objectOrClass, string $method): bool` — Returns `true` if `$method` is publicly callable from the calling context (respects public/protected/private visibility).
- `isCallingSelf(): bool` — Returns `true` if the object calling your method is the same object instance (protected-level check).
- `isCallingSelfClass(): bool` — Returns `true` if the calling object is the same instance **and** same class (private-level check).

Used internally (e.g., `TEventParameter::setReadOnly` uses `isCallingSelf()` to prevent external mutation).

### Logging and Debugging
- `getLogger(): TLogger` — Returns (or lazily creates) the message logger.
- `log(string $msg, int $level, string $category, float $time): void` — Writes a log entry.
- `trace(string $msg, string $cat)` / `debug` / `info` / `warning` / `error` / `fatal` — Severity shortcuts.
- `varDump(mixed $var): string` — Pretty-prints a variable via `TVarDumper`.

### Localization
- `getUserLanguages(): array` — Parses `Accept-Language` header into a priority-ordered list.
- `getPreferredLanguage(): string` — Returns the highest-priority language code.
- `localize(string $text, array $params, string $catalog, string $charset): string` — Translates text via `Translation`.

## Static Properties

| Property | Description |
|----------|-------------|
| `$classMap` | Class-name → file-path map loaded from `classes.php` |
| `$_aliases` | Path alias registry (`['Prado' => PRADO_DIR, 'Vendor' => PRADO_VENDORDIR, ...]`) |
| `$_usings` | Namespaces already imported |
| `$_logger` | Lazy `TLogger` instance |
| `$_application` | Singleton `TApplication` |