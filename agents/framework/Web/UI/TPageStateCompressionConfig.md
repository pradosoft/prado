# Web/UI/TPageStateCompressionConfig

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [UI](INDEX.md) / **`TPageStateCompressionConfig`**

**Location:** `framework/Web/UI/TPageStateCompressionConfig.php`
**Namespace:** `Prado\Web\UI`
**Since:** 4.4.0

## Overview
The compression settings the built-in page state persisters start from, and that `TPage::getStateCompression()` keeps for a persister that holds none. It subclasses `Prado\IO\Compression\TCompressionConfig`, changing two defaults through redeclared constants.

| Constant | Value | Reason |
|---|---|---|
| `DEFAULT_ENABLED` | `true` | page state has compressed by default since PRADO 3.1.6 |
| `DEFAULT_THRESHOLD` | `0` | every state compresses, whatever its length |
| `DEFAULT_METHOD` (inherited) | `deflate` | the zlib format PRADO has always written the state in |

## Why the threshold is zero

A page state carries no marker naming the coding that wrote it. The reader decompresses whenever the setting says the writer compressed, so a state skipped on length would be indistinguishable from one never compressed and would read back as corrupted. [TPageStateFormatter](./TPageStateFormatter.md) therefore consults `ShouldCompress` and never the length; the zero default makes that visible in the settings themselves.

## Configuration

Reached as page sub-properties, since `TPage::StatePersister` holds the persister:

```
<%@ StatePersister.Compression.Method="zstd" StatePersister.Compression.Level="9" %>
```

`StatePersister.Compression.*` reaches the settings of a persister implementing `ICompressionConfigurable`, which [TPageStatePersister](./TPageStatePersister.md), [TSessionPageStatePersister](./TSessionPageStatePersister.md) and [TCachePageStatePersister](./TCachePageStatePersister.md) all do; `StateCompression.*` reaches them whichever persister is in use. `TPage::EnableStateCompression` reads and writes their `Enabled`.

## See Also

- [TPageStatePersister](./TPageStatePersister.md) - The persister where compression pays: the whole state travels in the hidden field
- [TPageStateFormatter](./TPageStateFormatter.md) - Applies them to the state
- [TPage](./TPage.md) - `EnableStateCompression` delegates here
