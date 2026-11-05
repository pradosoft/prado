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
- `compress($page, $str)` / `decompress($page, $str)` - Apply the persister's content coding, protected so a subclass can change the codec selection

## Compression

`$page->getStateCompression()` returns the settings, a [TPageStateCompressionConfig](./TPageStateCompressionConfig.md). A persister implementing `Prado\IO\Compression\ICompressionConfigurable` holds them, reached in configuration as `StatePersister.Compression.*`; the three built-in persisters do. For any other persister the page keeps them. `TPage::EnableStateCompression` reads and writes their `Enabled`. The default `deflate` is the zlib format, byte-for-byte what `gzcompress()` wrote before 4.4.0, so a state written by an earlier version still reads.

| Condition | Result |
|---|---|
| `Compression.Enabled` false | state stored uncompressed |
| coding's codec unavailable here | state stored uncompressed |
| state written under another coding | decode fails, `unserialize()` returns null |
| corrupt compressed state | decode fails, `unserialize()` returns null |

The coding carries no marker in the state, so changing `Compression.Method` reads the states already in flight as corrupted. For the same reason `Compression.Threshold` is never consulted and defaults to 0: a state that skipped compression on length could not be told from one that was never compressed. `TCompressionConfig::decompress()` throws `TIOException` on a failed decode; `decompress()` catches it and reports the state as corrupted rather than letting it escape.

## Missing State

`unserialize()` accepts a null or empty `$data` and returns null for it. `TPage::getRequestClientState()` returns null when the request carries no `PRADO_PAGESTATE` field, which happens on a postback that only carries `PRADO_POSTBACK_TARGET` (for example when stray markup closes the form before the hidden fields render). The state persisters turn the null return into `THttpException` 400 "page state corrupted".

| `$data` | Result |
|---|---|
| null | null |
| `''` | null |
| base64 that decodes to `''` or fails | null |
| valid state | the restored state data |

## Integrity

The inner `hashData()` HMAC covers the serialized state; it is what makes tampered state come back null. The encryption layer (`aes-256-cbc`, without `UseEncryptionHmac`) adds no integrity of its own, so some ciphertext changes leave the state intact:

- When the compressed state is a multiple of 16 bytes, PKCS7 appends a block of padding only.
- Changing the final cipher block garbles only that padding block. About 1 time in 256, the garbage ends in `0x01`, which is valid padding, so `decrypt()` succeeds.
- The decrypted state is the real compressed bytes followed by 15 garbage bytes. `gzuncompress()` stops at the end of the zlib stream and ignores the rest.
- The result is the original state, and the inner HMAC passes.

No state content changes, so this is not a forgery. A test that tampers with encrypted state must change a byte inside the state's cipher blocks, such as the middle byte. A change to the last byte is not rejected every time, because whether the state fills whole blocks depends on the `ValidationKey`, through the HMAC's compressibility.

## See Also

- [TPage](./TPage.md)
- [TCachePageStatePersister](./TCachePageStatePersister.md)
- [TSecurityManager](../../Security/TSecurityManager.md)

