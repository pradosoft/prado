# Web/UI/WebControls/TOutputCacheTextWriterMulti

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TOutputCacheTextWriterMulti`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TOutputCacheTextWriterMulti.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TOutputCacheTextWriterMulti is an internal class used by TOutputCache to write simultaneously to multiple writers. It extends TTextWriter and routes write operations to multiple underlying writers.

## Key Properties/Methods

- `__construct(array $writers)` - Constructor accepting array of writers
- `write($s)` - Writes to all registered writers
- `flush()` - Flushes all writers and returns concatenated output

## See Also

- [TOutputCache](./TOutputCache.md)
- [TTextWriter](./TTextWriter.md)
