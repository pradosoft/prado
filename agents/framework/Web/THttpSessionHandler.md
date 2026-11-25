# Web/THttpSessionHandler

### Directories
[framework](../INDEX.md) / [Web](./INDEX.md) / **`THttpSessionHandler`**

## Class Info
**Location:** `framework/Web/THttpSessionHandler.php`
**Namespace:** `Prado\Web`

## Overview
THttpSessionHandler implements PHP's `SessionHandlerInterface` and is used internally when `THttpSession::UseCustomStorage` is enabled. It delegates session operations to [THttpSession](./THttpSession.md)'s internal methods.

## Key Properties/Methods

- `close()` - Session close handler
- `create_sid(): string` (4.4.0) - New session ID through `THttpSession::_createSid()`
- `destroy(string $id)` - Session destroy handler
- `gc(int $max_lifetime)` - Garbage collection handler
- `open(string $path, string $name)` - Session open handler
- `read(string $id)` - Session read handler
- `validateId(string $id): bool` (4.4.0) - Strict-mode ID check through `THttpSession::_validateId()`
- `write(string $id, string $data)` - Session write handler

PHP 8.6 warns when a `SessionHandlerInterface` class lacks `create_sid()` or `validateId()`; PHP 9.0 requires both.

## See Also

- [THttpSession](./THttpSession.md)
- [TCacheHttpSession](./TCacheHttpSession.md)
