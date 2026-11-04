# Exceptions/INDEX.md

This file provides guidance to Agents when working with code in this repository.

### Directories
[framework](../INDEX.md) / **`Exceptions`**

## Purpose

Exception hierarchy and error display for the Prado framework. Provides English error messages with override files, a structured exception base class, and a module-based error handler.

## Classes

### Base & Handler

- **[`TException`](TException.md)** — Base exception class. Supports two usage styles:
  - *Old style:* `throw new TException('error_code', $param1, $param2)`
  - *New style:* `throw new TException($intCode, 'error_code', $param1, $param2)`
  - Message parameters are the `{0}`, `{1}`, … arguments of an ICU MessageFormat pattern.
  - Loads messages from `messages/messages.txt` (English master), then the files of `addMessageFile()`; a later file overrides. [`THttpException`](THttpException.md) inserts `messages/messages-<lang>.txt` after the master.
  - Static cache `TException::$_messageCache` — each file parsed once per request.
  - Last constructor argument may be a `Throwable` for exception chaining.

- **[`TErrorHandler`](TErrorHandler.md)** — Application module for rendering errors/exceptions. Configured in `application.xml` as a module, or created by [`TApplication`](../TApplication.md) if not configured. Uses templates in `Exceptions/templates/` (`error.html`, `exception.html`; language-suffixed variants supported). Replaces `%%ErrorMessage%%`, `%%Version%%`, etc. in templates. `SOURCE_LINES = 12` lines of context shown around the error location.

- **`TErrorMessageEventParameter`** — Parameter of `TErrorHandler::onMissingTranslation()`: `Exception`, `ErrorCode`, `ErrorArguments`, `Language`, and the writable `Message` (the event `Parameter`, so `ParameterChanged` reports a supplied translation).

### Specific Exception Classes

| Class | Use |
|---|---|
| [`TApplicationException`](TApplicationException.md) | General application-level errors |
| [`TConfigurationException`](TConfigurationException.md) | Bad configuration values |
| [`TDbException`](TDbException.md) / [`TDbConnectionException`](TDbConnectionException.md) | Database errors |
| [`THttpException`](THttpException.md) | HTTP status code errors (404, 500, …) |
| [`TIOException`](TIOException.md) / [`TSocketException`](TSocketException.md) / [`TNetworkException`](TNetworkException.md) | I/O and network errors |
| [`TCancelledException`](TCancelledException.md) | A wait cancelled by a `TCancellationToken` or a `TAsync::timeout()` deadline (`async_cancelled`, `async_timeout`) |
| [`TInvalidDataTypeException`](TInvalidDataTypeException.md) | Wrong data type passed |
| [`TInvalidDataValueException`](TInvalidDataValueException.md) | Bad value for valid type |
| [`TInvalidOperationException`](TInvalidOperationException.md) | Operation not allowed in current state |
| [`TNotSupportedException`](TNotSupportedException.md) | Feature not implemented/supported |
| [`TUnknownMethodException`](TUnknownMethodException.md) | Call to undefined method |
| [`TUserException`](TUserException.md) | Visitor-facing errors; implements `IUserException`, so the error page shows its message in every mode |
| [`TPhpErrorException`](TPhpErrorException.md) / `TPhpFatalErrorException` | Wrapped PHP errors |
| [`TTemplateException`](TTemplateException.md) | Template parsing/rendering errors |
| [`TLogException`](TLogException.md) | Logging subsystem errors |
| [`TExitException`](TExitException.md) | Controlled application exit |

## Placement

`Prado\Exceptions` holds exception kinds: the classes that classify an error and that code across the framework throws or catches without knowing which component raised it. An exception that belongs to one component lives in that component's namespace, beside the class that throws it, and extends the closest kind here.

| Condition | Location |
|---|---|
| Classifies an error for the whole framework (type, value, operation, configuration, HTTP, I/O, DB) | `Prado\Exceptions` |
| Named after a component, thrown only by it, or carries its data | the component's namespace |

Component exceptions:

| Class | Namespace | Extends | Belongs because |
|---|---|---|---|
| `TInvalidCallbackException` | `Web\UI\ActiveControls` | `TException` | thrown only by callback handling (3.1) |
| `TMessageSourceIOException` | `I18N\core` | `TException` | thrown only by the message sources |
| `TMutexException` | `Util\Mutex` | `TSystemException` | thrown only by the mutexes |
| `THttpClientException` | `IO\HttpClient` | `TException` | carries a `THttpClientResponse` |
| `TPsrCacheException`, `TPsrCacheInvalidArgumentException` | `Caching` | `TConfigurationException`, `TInvalidDataValueException` | implement the PSR-6 and PSR-16 exception interfaces |
| `TInputValidationException` | `Util` | `TInvalidDataValueException` | carries the field errors of `TInputValidator` |
| `TRestException` | `Web\Services\Rest` | `THttpException` | carries the status, title and problem document |

Rules for a new exception:

- Extend the closest kind in `Prado\Exceptions`, never `\Exception` directly, so `catch (TException)`, `IUserException` and `TErrorHandler` apply. `TMessageSourceIOException` and `TInvalidCallbackException` predate the rule.
- Message keys go in `messages.txt` whatever the class location.
- No per-directory `Exception/` subfolders: the directories are flat, the `T` prefix disambiguates, and `agents/` and `classes.php` map one file to one class.
- `TDbException`, `TLogException` and `TTemplateException` are component-flavored kinds that stay here; moving a namespaced class breaks every `catch` on it.

Centralizing component exceptions inverts the dependency direction: this directory is the bottom layer, and a central `THttpClientException` would import `IO\HttpClient`. Yii2 (`yii\base` kinds, `yii\web` HTTP exceptions), CakePHP, CodeIgniter 4 and PSR draw the same line.

## Visitor-Facing Exceptions

`IUserException` (4.4) marks an exception whose message is written for the visitor. `THttpException` and `TUserException` implement it. `TErrorHandler::getExternalErrorMessage()` shows an `IUserException` message in every mode; any other exception shows `errorhandler_internal_error` outside Debug mode and is logged. `TErrorHandler::localizeErrorMessage()` looks the error code up in the globalization module's `prado` catalogue (`TErrorHandler::TRANSLATION_CATALOGUE`, `getCatalogueMessage()`) when translation is configured, with `TException::getErrorArguments()` as placeholders. Without a catalogue entry, also without a globalization module, it raises `OnMissingTranslation` with a `TErrorMessageEventParameter`; the page shows the handler's `Message`, else the exception message. Debug mode shows the exception message and raises nothing. `Prado::getApplicationLanguage()` gives the language for `messages-<lang>.txt` and the error templates: the globalization culture when the module is loaded, else the client's preferred language.

## Error Message Files

- **Location:** `framework/Exceptions/messages/`
- **Master (English):** `messages.txt`; every framework message is English.
- **Visitor-facing variants:** `messages-<lang>.txt` (`fr`, `id`, `zh`) hold exactly the keys thrown as `THttpException`; `TExceptionMessageFilesTest` enforces the set.
- **Developer-facing translations:** the `pradosoft/prado-dev-errormessages` package, one `messages-<lang>.txt` per language, registered through its Composer `extra.prado.error-messages`. The package is synced in one batch before each PRADO tag and release; a change here does not touch the package.
- **Format:** `error_code=ICU MessageFormat pattern`, such as `module_not_found=Module ''{0}'' has {1, plural, one{# error} other{# errors}}.` `TException::formatErrorMessage()` formats a file message with `\MessageFormatter` in the language of the file (`getMessageFileLanguage()`: `-xx` suffix, else `en`). An apostrophe before or after a brace is doubled (`''{0}''`); `'{0}'` is the literal text `{0}`, and `'{}'` is a literal brace pair. A code defined in no file is the message itself, with `{0}`, `{1}`, ... replaced. `TExceptionMessageFilesTest` checks that every entry parses and renders each argument.
- **Adding new error codes:** Add to `messages.txt`. A `THttpException` key also goes into every `messages-<lang>.txt` here. Any other key reaches the package at the pre-release batch sync.
- **Removing error codes:** Remove the key from the English master and from the variants here. The package drops it at the pre-release batch sync.
- **Display only:** The messages file content is for display. The error code string is the canonical identifier in code.

## Gotchas

- [`TException`](TException.md) extends PHP's `Exception` — catch `TException` before generic `Exception` when mixing both.
- Static message cache never expires during a request; dynamic messages are not cached differently.
- [`TErrorHandler`](TErrorHandler.md) templates use keyword replacement (`%%Keyword%%`), not PHP template syntax.
