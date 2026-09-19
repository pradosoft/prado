# Web/UI/TPageStateFormatter

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [UI](./INDEX.md) / **`TPageStateFormatter`**

## Class Info
**Location:** `framework/Web/UI/TPageStateFormatter.php`
**Namespace:** `Prado\Web\UI`

## Overview
TPageStateFormatter is a utility class that serializes and unserializes page state for persistent storage. It handles optional HMAC validation, encryption, and compression based on TPage settings. State data is serialized, optionally validated with hashData, optionally compressed, optionally encrypted, and base64-encoded for transmission.

## Key Properties/Methods

- `serialize($page, $data)` - Serializes state data with optional validation, compression, and encryption
- `unserialize($page, $data)` - Unserializes state data, returning null if the state is missing or corrupted
- `compress($page, $str)` / `decompress($page, $str)` - Apply the page's content coding, protected so a subclass can change the codec selection

## Compression

`TPage::EnableStateCompression` gates compression and `TPage::StateCompressionMethod` names the content coding, resolved through `Prado\IO\Compression\TCompression` (`framework/IO/Compression/`, no knowledge file yet). The default `deflate` is the zlib format, byte-for-byte what `gzcompress()` wrote before 4.4.0, so a state written by an earlier version still reads.

| Condition | Result |
|---|---|
| `EnableStateCompression` false | state stored uncompressed |
| coding's codec unavailable here | state stored uncompressed |
| state written under another coding | decode fails, `unserialize()` returns null |
| corrupt compressed state | decode fails, `unserialize()` returns null |

The coding carries no marker in the state, so changing `StateCompressionMethod` reads the states already in flight as corrupted. `TCompression::decompress()` throws `TIOException` on a failed decode; `decompress()` catches it and reports the state as corrupted rather than letting it escape.

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

