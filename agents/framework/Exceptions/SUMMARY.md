# Exceptions/SUMMARY.md

Exception hierarchy and error display for the Prado framework with English error messages, visitor-facing translations for `THttpException`, and override message files.

The English message catalog is the master. Removed error codes must also be removed from language variants.

## Classes

- **`TException`** — Base exception class supporting old style (`throw new TException('error_code', $param1)`) and new style (`throw new TException($intCode, 'error_code', $param1)`); message parameters use `{0}`, `{1}` placeholders.

- **`TErrorHandler`** — Application module for rendering errors/exceptions using templates in `Exceptions/templates/`.

- **`TErrorMessageEventParameter`** — Parameter of `TErrorHandler.OnMissingTranslation`; a handler sets `Message` for a visitor-facing message the `prado` catalogue lacks.

- **`TApplicationException`** — General application-level errors.

- **`TConfigurationException`** — Bad configuration values.

- **`TDbException`** / **`TDbConnectionException`** — Database errors.

- **`THttpException`** — HTTP status code errors (404, 500, etc.).

- **`TIOException`** / **`TSocketException`** / **`TNetworkException`** — I/O and network errors.

- **`TInvalidDataTypeException`** — Wrong data type passed.

- **`TInvalidDataValueException`** — Bad value for valid type.

- **`TInvalidOperationException`** — Operation not allowed in current state.

- **`TNotSupportedException`** — Feature not implemented/supported.

- **`TUnknownMethodException`** — Call to undefined method.

- **`TUserException`** — User-facing errors.

- **`TPhpErrorException`** / **`TPhpFatalErrorException`** — Wrapped PHP errors.

- **`TTemplateException`** — Template parsing/rendering errors.

- **`TLogException`** — Logging subsystem errors.

- **`TExitException`** — Controlled application exit.
