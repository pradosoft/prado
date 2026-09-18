# Web/UI/TPageStateFormatter

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [UI](./INDEX.md) / **`TPageStateFormatter`**

## Class Info
**Location:** `framework/Web/UI/TPageStateFormatter.php`
**Namespace:** `Prado\Web\UI`

## Overview
TPageStateFormatter is a utility class that serializes and unserializes page state for persistent storage. It handles optional HMAC validation, encryption, and compression based on TPage settings. State data is serialized, optionally validated with hashData, optionally encrypted, and base64-encoded for transmission.

## Key Properties/Methods

- `serialize($page, $data)` - Serializes state data with optional validation, compression, and encryption
- `unserialize($page, $data)` - Unserializes state data, returning null if the state is missing or corrupted

## Missing State

`unserialize()` accepts a null or empty `$data` and returns null for it. `TPage::getRequestClientState()` returns null when the request carries no `PRADO_PAGESTATE` field, which happens on a postback that only carries `PRADO_POSTBACK_TARGET` (for example when stray markup closes the form before the hidden fields render). The state persisters turn the null return into `THttpException` 400 "page state corrupted".

| `$data` | Result |
|---|---|
| null | null |
| `''` | null |
| base64 that decodes to `''` or fails | null |
| valid state | the restored state data |

## See Also

- [TPage](./TPage.md)
- [TCachePageStatePersister](./TCachePageStatePersister.md)
- [TSecurityManager](../../Security/TSecurityManager.md)

