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
- `destroy(string $id)` - Session destroy handler
- `gc(int $max_lifetime)` - Garbage collection handler
- `open(string $path, string $name)` - Session open handler
- `read(string $id)` - Session read handler
- `write(string $id, string $data)` - Session write handler

## See Also

- [THttpSession](./THttpSession.md)
- [TCacheHttpSession](./TCacheHttpSession.md)
