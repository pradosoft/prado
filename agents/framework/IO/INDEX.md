# IO/INDEX.md

### Directories
[framework](./INDEX.md) / **`IO/INDEX.md`**

## Purpose

Input/output utilities for the Prado framework: text writer abstractions and archive extraction.

## Classes

### Text Writers

All writers implement [ITextWriter](./ITextWriter.md).

- **[ITextWriter](./ITextWriter.md)** — Interface: `write(string)`, `writeLine(string)`, `flush(): string`.

- **[TTextWriter](./TTextWriter.md)** — In-memory buffer. `write()` / `writeLine()` accumulate output; `flush()` returns the buffer and resets it. Use when you need to capture output as a string.

- **[TOutputWriter](./TOutputWriter.md)** — Writes directly to PHP output buffer (`echo`-equivalent).

- **[TStdOutWriter](./TStdOutWriter.md)** — Writes to `STDOUT` stream (useful in CLI context).

### Archive

- **[TTarFileExtractor](./TTarFileExtractor.md)** — Extracts TAR archives (local paths or remote `http://`/`ftp://` URLs). Remote archives are downloaded to a temp file first and cleaned up in `__destruct()`. Constructor takes the tar filename; call `extract($targetPath)` to unpack. `extractModify()` can rewrite extracted paths.

### Stream Notifications

- **[TStreamNotificationCallback](./TStreamNotificationCallback.md)** — Stream context notification handler. Wraps PHP stream notification callbacks for monitoring file downloads (progress, authentication prompts, etc.).

- **[TStreamNotificationParameter](./TStreamNotificationParameter.md)** — Event parameter for stream notifications. Properties: `notification`, `severity`, `message`, `messageCode`, `bytesTransferred`, `bytesMax`.

## Gotchas

- `TTextWriter::flush()` **clears** the internal buffer — only call it when you're done accumulating output.
- `TTarFileExtractor` stores the temp download path in `_temp_tarname`; it is deleted automatically in `__destruct()`.
- Remote TAR downloads are synchronous and block execution.
