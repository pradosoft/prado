# Web/UI/WebControls/TFileUpload

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TFileUpload`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TFileUpload.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TFileUpload renders an `<input type="file">` element. On postback it reads the uploaded file(s) from `$_FILES` and provides methods to inspect and save them. It implements `IPostBackDataHandler` and `IValidatable`.

Since Prado 4.0, TFileUpload supports multiple-file uploads via the `Multiple` property. When enabled, the `name` attribute gains `[]` brackets and the `multiple` attribute is added. All file-info methods accept an optional `$index` parameter for backward compatibility — single-file uploads default to index 0.

TFileUpload automatically sets `enctype="multipart/form-data"` on the page form during `onPreRender` (via `TForm::setEnctype` or, in callback mode, via `TCallbackClient::setAttribute`).

## Inheritance

`TFileUpload` → `TWebControl` → `TControl` → `TComponent`

Implements: `IPostBackDataHandler`, `IValidatable`

## Key Constants / Enums

| Constant | Value | Description |
|---|---|---|
| `MAX_FILE_SIZE` | `1048576` | Default advisory maximum file size (1 MB). Sent as a hidden `MAX_FILE_SIZE` field. |

## Key Properties

| Property | Type | Default | Description |
|---|---|---|---|
| `Multiple` | bool | `false` | Enables multi-file upload. Adds `multiple` attribute and `[]` to the `name`. |
| `MaxFileSize` | int | `1048576` | Advisory maximum size (bytes). Written to a hidden field; enforced by PHP, not the browser. |
| `IsValid` | bool | `true` | Writeable by validators. |

## Key Methods

| Method | Signature | Description |
|---|---|---|
| `getHasFile` | `getHasFile(int $index = 0): bool` | Whether the file at `$index` was successfully uploaded. |
| `getHasAllFiles` | `getHasAllFiles(): bool` | Whether every file in a multi-upload succeeded. |
| `getFileName` | `getFileName(int $index = 0): string` | Original client-side filename. |
| `getFileSize` | `getFileSize(int $index = 0): int` | Actual size of the uploaded file in bytes. |
| `getFileType` | `getFileType(int $index = 0): string` | Client-reported MIME type (e.g. `image/gif`). **Not validated server-side.** |
| `getLocalName` | `getLocalName(int $index = 0): string` | Temporary file path on the server. PHP deletes it after the request unless saved. |
| `getErrorCode` | `getErrorCode(int $index = 0): int` | PHP upload error code (`UPLOAD_ERR_*`). |
| `saveAs` | `saveAs(string $fileName, bool $deleteTempFile = true, int $index = 0): bool` | Moves/copies the temporary file to `$fileName`. Returns `false` if no file at `$index`. |
| `getFiles` | `getFiles(): TFileUploadItem[]` | Returns the full array of `TFileUploadItem` objects for multi-upload scenarios. |
| `getValidationPropertyValue` | `(): string` | Returns a comma-separated list of original filenames (used by validators). |
| `getDataChanged` | `(): bool` | Whether any file was submitted this postback. |

### `TFileUploadItem`

Each entry in `getFiles()` is a `TFileUploadItem` with the same per-file properties: `FileName`, `FileSize`, `FileType`, `LocalName`, `ErrorCode`, `HasFile`, and `saveAs()`.

## Events

| Event | Raised When |
|---|---|
| `OnFileUpload` | A file is submitted during postback (regardless of success/failure). Raised via `raisePostDataChangedEvent`. |

## Patterns & Gotchas

- **`enctype` is set automatically** — TFileUpload calls `$form->setEnctype('multipart/form-data')` in `onPreRender`. You do not need to set it manually on `TForm`, but the upload will silently fail if TFileUpload is not in the control tree before that event.
- **Check `getHasFile()` before `saveAs()`** — If `getHasFile()` is false, `saveAs()` returns `false` and does nothing.
- **`getFileType()` is untrusted** — The MIME type is reported by the browser and can be forged. Always validate file content server-side (e.g., with `finfo`).
- **`MAX_FILE_SIZE` hidden field** — This is a browser hint only. PHP enforces its own `upload_max_filesize` and `post_max_size` INI settings independently.
- **Temporary file lifetime** — `getLocalName()` points to a temp file that PHP deletes at end-of-request. Call `saveAs()` during `OnFileUpload` or before the response is flushed.
- **`saveAs($path, false)`** — Passing `false` for `$deleteTempFile` copies instead of moves, allowing multiple saves. After the request ends the temp file is still removed by PHP.
- **Multi-upload validation** — `getValidationPropertyValue()` returns a comma-separated list of filenames; validators see this as a single string. Use `getFiles()` for per-file logic.
- **Subclassing `TFileUploadItem`** — The item class is controlled by `static::$fileUploadItemClass`. Override this in a subclass to use a custom item class.
- **Callback mode** — In an AJAX callback, `enctype` cannot be changed with a normal attribute setter; TFileUpload detects this and uses `TCallbackClient::setAttribute` instead.
