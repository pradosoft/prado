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
- `unserialize($page, $data)` - Unserializes state data, returning null if corrupted

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

(End of file - total 19 lines)
