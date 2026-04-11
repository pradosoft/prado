# Exceptions/TErrorHandler

### Directories
[framework](../INDEX.md) / [Exceptions](./INDEX.md) / **`TErrorHandler`**

## Class Info
**Location:** `framework/Exceptions/TErrorHandler.php`
**Namespace:** `Prado\Exceptions`

## Overview
TErrorHandler is a module that handles all PHP errors and exceptions during request processing. It displays errors using configurable templates and supports multilingual error messages.

## Key Features

- Displays errors using templates
- Multilingual support (uses user's preferred language)
- Two error types: external (user errors) and internal (developer errors)
- Source code context around exceptions

## Configuration

```xml
<module id="error" class="Prado\Exceptions\TErrorHandler" ErrorTemplatePath="Prado\Exceptions" />
```

## Template System

Templates use keyword replacement with `%%Keyword%%` syntax:

- `%%ErrorMessage%%` - The error message
- `%%Version%%` - PRADO version
- `%%Time%%` - Error timestamp
- `%%Type%%` - Exception type
- `%%Code%%` - Source code context

## Template Files

- `error[StatusCode][-LanguageCode].html` - For user-facing errors (THttpException)
- `exception[-LanguageCode].html` - For internal exceptions
- Default: `error.html`, `exception.html`

## Constants

- `SOURCE_LINES = 12` - Lines of source context
- `FATAL_ERROR_TRACE_DROP_LINES = 5` - Internal trace drops

## See Also

- `[TException](./TException.md)` - Base exception class
- `[THttpException](./THttpException.md)` - HTTP status code exceptions
