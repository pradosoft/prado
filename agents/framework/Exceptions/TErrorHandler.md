# Exceptions/TErrorHandler

### Directories
[framework](../INDEX.md) / [Exceptions](./INDEX.md) / **`TErrorHandler`**

## Class Info
**Location:** `framework/Exceptions/TErrorHandler.php`
**Namespace:** `Prado\Exceptions`

## Overview
TErrorHandler is a module that handles all PHP errors and exceptions during request processing. It displays errors using configurable templates and supports multilingual error messages. It registers itself with `TApplication` via `setAppErrorHandler()` during `init()`.

## Key Features

- Displays errors using HTML templates
- Multilingual support (uses user's preferred language)
- Two error types: external (visitor errors, via `IUserException`) and internal (developer errors, debug mode)
- Visitor-facing messages translate through the `prado` catalogue, then the `OnMissingTranslation` event (4.4)
- Source code context around exceptions (12 lines)
- Re-entrance guard prevents infinite error-handling loops
- Strips private filesystem paths from output in non-Debug mode
- CLI mode outputs plain text stack traces

## Configuration

```xml
<modules>
    <module id="error" class="Prado\Exceptions\TErrorHandler" ErrorTemplatePath="Prado\Exceptions" />
</modules>
```

**PHP equivalent:**
```php
return [
    'modules' => [
        'error' => [
            'class' => 'Prado\Exceptions\TErrorHandler',
            'properties' => ['ErrorTemplate' => 'Prado\\Exceptions'],
        ],
    ],
];
```

## Template System

Templates use keyword replacement with `%%Keyword%%` syntax.

**Error templates** (`handleExternalError`):
- `%%StatusCode%%` - HTTP status code
- `%%ErrorMessage%%` - HTML-encoded error message
- `%%ErrorCode%%` - Exception error code
- `%%ServerAdmin%%` - Server admin from `$_SERVER['SERVER_ADMIN']`
- `%%Version%%` - Server + PRADO version (Debug mode only; empty otherwise)
- `%%Time%%` - Error timestamp (`Y-m-d H:i`)

**Exception templates** (`displayException`, Debug mode only):
- `%%ErrorType%%` - Exception class name
- `%%ErrorMessage%%` - HTML-encoded error message (with doc link for known Prado classes)
- `%%ErrorCode%%` - Exception error code
- `%%SourceFile%%` - File path + line number (private paths sanitized)
- `%%SourceCode%%` - HTML source context (12 lines around error)
- `%%StackTrace%%` - Stack trace string (private paths sanitized)
- `%%Version%%` - Server + PRADO version
- `%%Time%%` - Error timestamp

## Template Files

Selection priority (most specific to least):
- Error: `error{StatusCode}-{lang}.html` → `error{StatusCode}.html` → `error-{lang}.html` → `error.html`
- Exception: `exception-{lang}.html` → `exception.html`

Default templates live under `framework/Exceptions/templates/`.

## Constants

| Constant | Value | Description |
|----------|-------|-------------|
| `ERROR_FILE_NAME` | `'error'` | Basename for error templates |
| `EXCEPTION_FILE_NAME` | `'exception'` | Basename for exception template |
| `TRANSLATION_CATALOGUE` | `'prado'` | Catalogue `getCatalogueMessage()` looks error codes up in (4.4) |
| `SOURCE_LINES` | `12` | Lines of source context before/after error line |
| `FATAL_ERROR_TRACE_DROP_LINES` | `5` | Frames dropped from xdebug fatal error traces |

## Key Methods (4.3.3 additions)

| Method | Description |
|--------|-------------|
| `setAppErrorHandler()` | Registers `$this` with the application as error handler; called from `init()`; overridable by subclasses |
| `getDefaultErrorTemplatePath(): string` | Returns framework's built-in template directory |
| `getIsHandled(): bool` | Returns re-entrance flag (true if already handling an error) |
| `setIsHandled(bool $value): void` | Sets re-entrance flag |
| `getPrivatePathReplacements(): array` | Builds/caches the path→placeholder map used by `hidePrivatePathParts()` |
| `hidePrivatePathParts($value)` | Sanitizes private paths using the cached replacement map |
| `errorLog(string $message): void` | Wraps `error_log()` — overridable in tests |
| `headersSent(): bool` | Wraps `headers_sent()` — overridable in tests |
| `header(string $header, ...): void` | Wraps `header()` — overridable in tests |
| `restoreErrorHandler(): void` | Wraps `restore_error_handler()` — overridable in tests |
| `restoreExceptionHandler(): void` | Wraps `restore_exception_handler()` — overridable in tests |
| `phpSapiName(): string` | Wraps `php_sapi_name()` — overridable in tests |
| `serverGlobal(string $key): mixed` | Wraps `$_SERVER[$key]` access — overridable in tests |

## Visitor-Facing Message (4.4)

`handleExternalError()` shows the message of `getExternalErrorMessage($exception, $isDebug)`:

| Condition | Message |
|---|---|
| Debug mode | `$exception->getMessage()`, no translation |
| `IUserException` | its message, through `localizeErrorMessage()` |
| any other exception | `errorhandler_internal_error` as a `THttpException(500)`, through `localizeErrorMessage()`; the exception is logged |

`localizeErrorMessage(\Throwable)` resolves in order:

1. `getCatalogueMessage()` — the `TRANSLATION_CATALOGUE` entry of a `TException` error code when the globalization module has a translation configuration, with `TException::getErrorArguments()` as `{0}`… placeholders; `null` for a marker-wrapped or unchanged key, a non-`TException`, or no module.
2. `onMissingTranslation(TErrorMessageEventParameter)` — raised when step 1 gives `null`. The parameter's `Message` starts as the exception message (already in the `messages-<lang>.txt` language for a `THttpException`); a handler sets it. `Exception`, `ErrorCode`, `ErrorArguments`, and `Language` (`Prado::getApplicationLanguage()`) are read-only.
3. The parameter's `Message` is returned, then `hideSecurityRelated()` strips paths.

```php
$errorHandler->attachEventHandler('OnMissingTranslation', function ($sender, TErrorMessageEventParameter $param) use ($repository) {
	$text = $repository->find($param->getErrorCode(), $param->getLanguage());
	if ($text !== null) {
		$replacements = [];
		foreach ($param->getErrorArguments() as $i => $argument) {
			$replacements['{' . $i . '}'] = $argument;
		}
		$param->setMessage(strtr($text, $replacements));
	}
});
```

## Error Handling Flow

```
handleError($sender, $param)
  ├── restoreErrorHandler() + restoreExceptionHandler()  // prevent PHP loops
  ├── if already handling → handleRecursiveError()       // minimal fallback
  └── else
        ├── clear response, set Content-Type: text/html
        ├── THttpException → handleExternalError($statusCode, $exception)
        ├── Debug mode     → displayException($exception)
        └── else           → handleExternalError(500, $exception)
```

## Subclassing

Override the PHP-wrapper methods (`errorLog`, `headersSent`, `header`, `restoreErrorHandler`, `restoreExceptionHandler`, `phpSapiName`, `serverGlobal`) to inject test doubles without modifying PHP global state.

## See Also

- [TException](./TException.md) - Base exception class
- [THttpException](./THttpException.md) - HTTP status code exceptions
- [TExitException](./TExitException.md) - Graceful exit (handled by TApplication, not TErrorHandler)
