# Web/Services/Rest/TRestException

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [Services](../INDEX.md) / [Rest](INDEX.md) / **`TRestException`**

## Class Info
**Location:** `framework/Web/Services/Rest/TRestException.php`
**Namespace:** `Prado\Web\Services\Rest`
**Extends:** `THttpException` (an `IUserException`)
**Since:** 4.4.0

## Overview
An HTTP error of the REST service: status code, `Title` (defaults to the reason phrase), `Detail`, and `Errors` (field name => messages). `toArray()` is the RFC 9457 problem document (`status`, `title`, `detail` and `errors` when set) that `TRestService` writes as `application/problem+json`. `getCode()` equals the status code.

## Factories
`badRequest()`, `unauthorized()`, `forbidden()`, `notFound()`, `methodNotAllowed()`, `conflict()`, `unsupportedMediaType()`, `unprocessable($errors, $detail)`, `tooManyRequests()`, `internalError()`, each taking the detail message.

## Gotchas
- The message code is `restservice_http_error` (`HTTP {0} {1}`); the detail is the visitor-facing text, so localize it at the throw site.
- The service converts any other `THttpException` into one with the same status and message.
