# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Purpose

Exception hierarchy and error display for the Prado framework. Provides multilingual error messages, a structured exception base class, and a module-based error handler.

## Classes

### Base & Handler

- **`TException`** — Base exception class. Supports two usage styles:
  - *Old style:* `throw new TException('error_code', $param1, $param2)`
  - *New style:* `throw new TException($intCode, 'error_code', $param1, $param2)`
  - Message parameters use `{0}`, `{1}`, … placeholders.
  - Loads messages from `messages/messages.txt` (English master) or `messages/messages-<lang>.txt`.
  - Static cache `TException::$_messageCache` — loaded once per language per request.
  - Last constructor argument may be a `Throwable` for exception chaining.

- **`TErrorHandler`** — Application module for rendering errors/exceptions. Configured in `application.xml` as a module. Uses templates in `Exceptions/templates/` (`error.html`, `exception.html`; language-suffixed variants supported). Replaces `%%ErrorMessage%%`, `%%Version%%`, etc. in templates. `SOURCE_LINES = 12` lines of context shown around the error location.

### Specific Exception Classes

| Class | Use |
|---|---|
| `TApplicationException` | General application-level errors |
| `TConfigurationException` | Bad configuration values |
| `TDbException` / `TDbConnectionException` | Database errors |
| `THttpException` | HTTP status code errors (404, 500, …) |
| `TIOException` / `TSocketException` / `TNetworkException` | I/O and network errors |
| `TInvalidDataTypeException` | Wrong data type passed |
| `TInvalidDataValueException` | Bad value for valid type |
| `TInvalidOperationException` | Operation not allowed in current state |
| `TNotSupportedException` | Feature not implemented/supported |
| `TUnknownMethodException` | Call to undefined method |
| `TUserException` | User-facing errors |
| `TPhpErrorException` / `TPhpFatalErrorException` | Wrapped PHP errors |
| `TTemplateException` | Template parsing/rendering errors |
| `TLogException` | Logging subsystem errors |
| `TExitException` | Controlled application exit |

## Error Message Files

- **Location:** `framework/Exceptions/messages/`
- **Master (English):** `messages.txt`
- **Language variants:** `messages-<lang>.txt` (e.g., `messages-de.txt`, `messages-zh.txt`)
- **Format:** `error_code=Human readable message with {0} placeholders`
- **Adding new error codes:** Add to `messages.txt` first, then add translations as needed.
- **Display only:** The messages file content is for user display. The error code string is the canonical identifier in code.

## Gotchas

- `TException` extends PHP's `Exception` — catch `TException` before generic `Exception` when mixing both.
- Static message cache never expires during a request; dynamic messages are not cached differently.
- `TErrorHandler` templates use keyword replacement (`%%Keyword%%`), not PHP template syntax.
