# TPageStateFormatter

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [TPageStateFormatter](./TPageStateFormatter.md)

**Location:** `framework/Web/UI/TPageStateFormatter.php`
**Namespace:** `Prado\Web\UI`

## Overview

TPageStateFormatter is a utility class that serializes and unserializes page state for persistent storage. It handles optional HMAC validation, encryption, and compression based on TPage settings. State data is serialized, optionally validated with hashData, optionally encrypted, and base64-encoded for transmission.

## Key Properties/Methods

- `serialize($page, $data)` - Serializes state data with optional validation, compression, and encryption
- `unserialize($page, $data)` - Unserializes state data, returning null if corrupted

## See Also

- [TPage](./TPage.md)
- [TCachePageStatePersister](./TCachePageStatePersister.md)
- [TSecurityManager](../Security/TSecurityManager.md)

(End of file - total 19 lines)
