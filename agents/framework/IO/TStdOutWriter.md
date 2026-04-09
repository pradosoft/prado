# IO / TStdOutWriter

[./](../INDEX.md) > [IO](./INDEX.md) > [TStdOutWriter](./TStdOutWriter.md)

**Location:** `framework/IO/TStdOutWriter.php`
**Namespace:** `Prado\IO`

## Overview

Extends [TTextWriter](./TTextWriter.md) to write to STDOUT when flushed. Used for CLI testing.

## Constants

| Constant | Value |
|----------|-------|
| `STDOUT_URI` | `'php://stdout'` |

## Behavior

In CLI, writes to `STDOUT`. In other contexts, opens a handle to `php://stdout`.

```php
$writer = new TStdOutWriter();
$writer->write('Hello, World!');
$writer->flush();  // Writes to STDOUT
```

## See Also

- [TTextWriter](./TTextWriter.md) - Parent class
- [TOutputWriter](./TOutputWriter.md) - PHP output writer
