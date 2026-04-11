# IO/TTextWriter

### Directories
[framework](../INDEX.md) / [IO](./INDEX.md) / **`TTextWriter`**

## Class Info
**Location:** `framework/IO/TTextWriter.php`
**Namespace:** `Prado\IO`

## Overview
Memory-based text writer. Accumulates written content in memory until flushed.

## Usage

```php
$writer = new TTextWriter();
$writer->write('Hello, ');
$writer->writeLine('World!');  // Adds newline
echo $writer->flush();  // "Hello, World!\n"
```

## Methods

| Method | Description |
|--------|-------------|
| `write($str)` | Appends string to buffer |
| `writeLine($str)` | Appends string with newline |
| `flush()` | Returns and clears buffer |

## See Also

- [ITextWriter](./ITextWriter.md) - Interface
- [TOutputWriter](./TOutputWriter.md) - Output-based writer
- [TStdOutWriter](./TStdOutWriter.md) - STDOUT writer
