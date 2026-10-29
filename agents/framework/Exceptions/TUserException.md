# Exceptions/TUserException

### Directories
[framework](../INDEX.md) / [Exceptions](./INDEX.md) / **`TUserException`**

## Class Info
**Location:** `framework/Exceptions/TUserException.php`
**Namespace:** `Prado\Exceptions`

## Overview
Base class for exceptions whose message is written for the site visitor, typically caused by a visitor mistake (invalid input, wrong permissions). Extends `TApplicationException` and implements `IUserException` (4.4), the marker `TErrorHandler` uses to decide which messages the visitor may see.

## Hierarchy

```
TUserException
└── [TApplicationException](./TApplicationException.md)
    └── [TException](./TException.md)
        └── Exception
```

## Usage

```php
throw new TUserException(Prado::localize('The user name is not available.'));
throw new TUserException('app_username_taken', $name);   // key in the application's errormessages file
```

A string that is not a message key passes through `TException` unchanged, so the application translates at the throw site with `Prado::localize()`, or defines the key in its own `errormessages` file with `-<lang>` variants.

## Design Principle

- The message is for the visitor: `TErrorHandler::getExternalErrorMessage()` shows it in every application mode. Any other exception shows the generic `errorhandler_internal_error` sentence outside Debug mode.
- With a globalization module configured for translation, `TErrorHandler::localizeErrorMessage()` looks the error code up in the `prado` catalogue with `getErrorArguments()` as placeholders.
- Without a catalogue entry, `TErrorHandler` raises `OnMissingTranslation`; a handler sets the `Message` of its `TErrorMessageEventParameter` from the `ErrorCode`, `ErrorArguments`, and `Language`.
- Not for system or programming errors; those stay developer-facing and English.

## See Also

- `[TApplicationException](./TApplicationException.md)` - Application-level exceptions
- `[TErrorHandler](./TErrorHandler.md)` - Error display
