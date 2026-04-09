# IO / ITextWriter

[./](../INDEX.md) > [IO](./INDEX.md) > [ITextWriter](./ITextWriter.md)

**Location:** `framework/IO/ITextWriter.php`
**Namespace:** `Prado\IO`

## Overview

Interface for text writers. Defines the contract for writing and flushing text output.

## Methods

### `write($str): void`

Writes a string to the output.

### `flush(): string`

Flushes and returns the accumulated content, clearing the buffer.

## Implementations

- [TTextWriter](./TTextWriter.md) - Memory-based writer
- [TOutputWriter](./TOutputWriter.md) - Writes to PHP output
- [TStdOutWriter](./TStdOutWriter.md) - Writes to STDOUT
- [TShellWriter](./TShellWriter.md) - Formatted terminal output

## See Also

- [TTextWriter](./TTextWriter.md) - Base memory-based implementation
