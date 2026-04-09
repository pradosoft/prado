# TException Hierarchy

### Directories
[./](../INDEX.md) > [Exceptions](./INDEX.md) > [TException](./TException.md)

**Location:** `framework/Exceptions/`
**Namespace:** `Prado\Exceptions`

## Overview

Prado's exception hierarchy extends PHP's `Exception`. `TException` provides multilingual error messages loaded from `messages/messages.txt` keyed by error code string. `TErrorHandler` renders errors using HTML templates.

## TException

```php
// Old style (error code + params):
throw new [TConfigurationException](./TConfigurationException.md)('module_not_initialized', $moduleId);

// New style (HTTP-like int code + error code + params):
throw new [THttpException](./THttpException.md)(404, 'page_not_found', $page);

// With chained exception:
throw new [TDbException](./TDbException.md)('db_query_failed', $sql, $e);  // $e is Throwable
```

Message format in `messages.txt`:
```
module_not_initialized=The module '{0}' has not been initialized.
db_query_failed=Database query failed: {0}
```

`{0}`, `{1}`, … placeholders are replaced with constructor arguments.

### Loading Messages

Messages are loaded from:
1. `framework/Exceptions/messages/messages-{lang}.txt` (localized)
2. `framework/Exceptions/messages/messages.txt` (English fallback)

Loaded once per language per request; cached in `TException::$_messageCache`.

## Exception Class Reference

| Class | HTTP | Use |
|-------|------|-----|
| `[TException](./TException.md)` | — | Base class |
| `[TApplicationException](./TApplicationException.md)` | — | General application errors |
| `[TConfigurationException](./TConfigurationException.md)` | — | Bad config values |
| `[TDbException](./TDbException.md)` | — | Database errors |
| `[TDbConnectionException](./TDbConnectionException.md)` | — | PDO connection failures |
| `[THttpException](./THttpException.md)` | any | HTTP status code errors; renders HTTP response code |
| `[TIOException](./TIOException.md)` | — | File I/O errors |
| `[TSocketException](./TSocketException.md)` | — | Socket I/O errors |
| `[TNetworkException](./TNetworkException.md)` | — | Network/curl errors |
| `[TInvalidDataTypeException](./TInvalidDataTypeException.md)` | — | Wrong type passed to a method |
| `[TInvalidDataValueException](./TInvalidDataValueException.md)` | — | Bad value for valid type |
| `[TInvalidOperationException](./TInvalidOperationException.md)` | — | Operation not allowed in current state |
| `[TNotSupportedException](./TNotSupportedException.md)` | — | Feature not implemented |
| `[TUnknownMethodException](./TUnknownMethodException.md)` | — | Undefined method called |
| `[TUserException](./TUserException.md)` | — | User-facing error messages |
| `[TPhpErrorException](./TPhpErrorException.md)` | — | Wrapped PHP `E_*` errors |
| `TPhpFatalErrorException` | — | Wrapped PHP fatal errors |
| `[TTemplateException](./TTemplateException.md)` | — | Template parse/render failures |
| `[TLogException](./TLogException.md)` | — | Logging subsystem error |
| `[TExitException](./TExitException.md)` | — | Controlled exit (not an error; caught by TApplication) |

## TErrorHandler

Registered as a module in `application.xml`. Renders exceptions using HTML templates.

```xml
<module id="error" class="Prado\Exceptions\TErrorHandler"
        ErrorTemplatePath="Application.Pages.errors" />
```

Template files: `error{code}.html` (e.g., `error404.html`), `error.html` (generic). Template tokens: `%%ErrorMessage%%`, `%%Version%%`, `%%Time%%`, `%%TraceString%%`, `%%SourceCode%%` (12 lines of context around the error).

## Adding New Error Codes

1. Add entry to `framework/Exceptions/messages/messages.txt`:
   ```
   my_error_code=Something went wrong: {0}
   ```
2. Add translations to `messages-{lang}.txt` as needed.
3. Throw: `throw new TApplicationException('my_error_code', $detail);`

## Gotchas

- **Catch order** — catch `[TException](./TException.md)` before `Exception` when you need Prado-specific handling.
- **`[TExitException](./TExitException.md)`** — not really an error; caught by `TApplication::run()` to perform a clean exit. Don't let it propagate to user-facing error handlers.
- **`[THttpException](./THttpException.md)`** — triggers `[TErrorHandler](./TErrorHandler.md)` to emit the correct HTTP status code. Use for 404, 403, 500, etc.
- **Parameter count** — constructor parameters after the error code string are substituted positionally; extra parameters are silently ignored.
