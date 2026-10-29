# Web/UI/WebControls/TFileUploadItem

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TFileUploadItem`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TFileUploadItem.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TFileUploadItem represents a single uploaded file from TFileUpload, used especially when Multiple is enabled.

## Key Properties/Methods

- `FileName` - Original filename on client
- `FileSize` - Size in bytes
- `FileType` - MIME type
- `LocalName` - Server-side temporary filename
- `FullPath` - Path within the folder of a `TFileUpload.Directory` upload, from `full_path`; otherwise the file name. Client-sent: may hold `..`, never a server path as-is (@since 4.4.0)
- `ErrorCode` - Upload error code
- `HasFile` - Whether file was uploaded successfully
- `saveAs()` - Saves uploaded file to specified location
- `toArray()` - Returns array representation, with `fullPath` (@since 4.4.0)

## See Also

- [TFileUpload](./TFileUpload.md)
