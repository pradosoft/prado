# Exceptions/TException

### Directories
[framework](../INDEX.md) / [Exceptions](./INDEX.md) / **`TException`**

## Class Info
**Location:** `framework/Exceptions/TException.php`
**Namespace:** `Prado\Exceptions`

## Overview
Prado's exception hierarchy extends PHP's `Exception`. `TException` provides multilingual error messages loaded from `messages/messages.txt` keyed by error code string. `TErrorHandler` renders errors using HTML templates.

Supports two constructor styles: old-style (string key as first argument) and new-style (integer PHP error code as first argument followed by string message key). Additional string arguments fill `{0}`, `{1}`, … placeholders; a trailing `Throwable` is chained as `$previous`.

## TException

```php
// Old style (error code + params):
throw new [TConfigurationException](./TConfigurationException.md)('module_not_initialized', $moduleId);

// New style (HTTP-like int code + error code + params):
throw new [THttpException](./THttpException.md)(404, 'page_not_found', $page);

// With chained exception:
throw new [TDbException](./TDbException.md)('db_query_failed', $sql, $e);  // $e is Throwable
```

Message format in `messages.txt`, an ICU MessageFormat pattern (since 4.3.3, as in 4.4):
```
module_not_initialized=The module ''{0}'' has not been initialized.
db_query_failed=Database query failed: {0}
cache_items_expired={0, plural, one{# item} other{# items}} expired.
```

The constructor arguments, as strings, are the `{0}`, `{1}`, … arguments. `formatErrorMessage()` formats a message defined in a file with `\MessageFormatter::formatMessage()` in the language of `getMessageFileLanguage()` (the file's `-xx` suffix, else `en`); ICU converts a string argument of a `plural`, `number`, or `date` argument. A code defined in no file, a pattern ICU cannot parse, or a pattern written before 4.3.3 that quotes a placeholder in single apostrophes (`isPlainMessagePattern()`, `'{0}'`) has its `{0}`, `{1}`, … replaced with `strtr()`. 4.4 has no such exception for `'{0}'`.

ICU apostrophe quoting:

| In the file | Renders |
|---|---|
| `''{0}''` | `'value'` |
| `'{0}'` | `'value'` on 4.3.3 (plain replacement); `{0}` on 4.4 |
| `'{}'` | `{}` |
| `''''` | `''` |
| `can't` | `can't` (an apostrophe not next to a brace is literal) |

### Loading Messages

`getErrorMessageFiles()` lists, in order of increasing precedence, the files of `addMessageFile()` and then `getErrorMessageFile()`:
1. `framework/Exceptions/messages/messages-{lang}.txt` (localized), or
2. `framework/Exceptions/messages/messages.txt` (English fallback).

`findErrorMessageFile()` returns the file with the highest precedence that defines a key, and `translateErrorMessage()` returns its message; a key defined nowhere is the message itself. Each file is parsed once per request, an unreadable file as no entries; cached in `TException::$_messageCache`. `TActiveRecordException` and `TSqlMapException` override `getErrorMessageFile()` with their own `messages.txt`, also ICU patterns.

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
<modules>
    <module id="error" class="Prado\Exceptions\TErrorHandler"
            ErrorTemplatePath="Application.Pages.errors" />
</modules>
```

Template files: `error{code}.html` (e.g., `error404.html`), `error.html` (generic). Template tokens: `%%ErrorMessage%%`, `%%Version%%`, `%%Time%%`, `%%TraceString%%`, `%%SourceCode%%` (12 lines of context around the error).

## Adding New Error Codes

1. Add entry to `framework/Exceptions/messages/messages.txt`:
   ```
   my_error_code=Something went wrong in ''{0}''.
   ```
2. Add translations to `messages-{lang}.txt` as needed.
3. Throw: `throw new TApplicationException('my_error_code', $detail);`

## Extra Message Files

Third-party modules can register additional message files:
```php
TException::addMessageFile('path/to/messages.txt');
```
The method automatically resolves a language-specific variant if one exists.

## Gotchas

- **Catch order** — catch `[TException](./TException.md)` before `Exception` when you need Prado-specific handling.
- **`[TExitException](./TExitException.md)`** — not really an error; caught by `TApplication::run()` to perform a clean exit. Don't let it propagate to user-facing error handlers.
- **`[THttpException](./THttpException.md)`** — triggers `[TErrorHandler](./TErrorHandler.md)` to emit the correct HTTP status code. Use for 404, 403, 500, etc.
- **Quoted placeholders** — write `''{0}''`. `'{0}'` is replaced as before on 4.3.3, but 4.4 renders it as `{0}`. The message-file lint (`TExceptionMessageFilesTest`) fails on it in a framework file.
- **Parameter count** — constructor parameters after the error code string are substituted positionally; extra parameters are silently ignored.
- **New-style vs old-style** — first argument is `int` for new-style (PHP error code), `string` for old-style (message key). Do not pass `0` as an integer to mean "no code" when an old-style string is intended.
