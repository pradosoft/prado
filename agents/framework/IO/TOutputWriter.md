# IO / TOutputWriter

[./](../INDEX.md) > [IO](./INDEX.md) > [TOutputWriter](./TOutputWriter.md)

**Location:** `framework/IO/TOutputWriter.php`
**Namespace:** `Prado\IO`

## Overview

Extends [TTextWriter](./TTextWriter.md) to write buffer directly to PHP output (`echo`) when flushed.

## Constants

| Constant | Value |
|----------|-------|
| `OUTPUT_URI` | `'php://output'` |
| `OUTPUT_TYPE` | `'Output'` |

## Behavior

When `flush()` is called, the accumulated content is both returned and echoed to PHP's standard output.

```php
$writer = new TOutputWriter();
$writer->write('Hello');
$writer->flush();  // Echoes "Hello" and returns it
```

## Note

Does not call PHP's `flush()` - you must call it separately to ensure output reaches the client.

## See Also

- [TTextWriter](./TTextWriter.md) - Parent class
- [TStdOutWriter](./TStdOutWriter.md) - STDOUT writer
