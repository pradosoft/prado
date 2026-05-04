# IO/SUMMARY.md

Input/output utilities: text writer abstractions, archive extraction, and stream notifications.

## Classes

- **`ITextWriter`** — Interface: `write(string)`, `writeLine(string)`, `flush(): string`.

- **`TTextWriter`** — In-memory buffer; `write()`/`writeLine()` accumulate output; `flush()` returns buffer and resets it.

- **`TOutputWriter`** — Writes directly to PHP output buffer (`echo`-equivalent).

- **`TStdOutWriter`** — Writes to `STDOUT` stream (useful in CLI context).

- **`TTarFileExtractor`** — Extracts TAR archives (local or remote `http://`/`ftp://` URLs); remote archives downloaded to temp file first; `extract($targetPath)` unpacks; `extractModify()` can rewrite extracted paths.

- **`TStreamNotificationCallback`** — Stream context notification handler for monitoring file downloads.

- **`TStreamNotificationParameter`** — Event parameter for stream notifications; properties: `notification`, `severity`, `message`, `messageCode`, `bytesTransferred`, `bytesMax`.
