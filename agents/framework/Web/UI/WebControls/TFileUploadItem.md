# TFileUploadItem

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md) > [TFileUploadItem](./TFileUploadItem.md)

**Location:** `framework/Web/UI/WebControls/TFileUploadItem.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview

TFileUploadItem represents a single uploaded file from TFileUpload, used especially when Multiple is enabled.

## Key Properties/Methods

- `FileName` - Original filename on client
- `FileSize` - Size in bytes
- `FileType` - MIME type
- `LocalName` - Server-side temporary filename
- `ErrorCode` - Upload error code
- `HasFile` - Whether file was uploaded successfully
- `saveAs()` - Saves uploaded file to specified location
- `toArray()` - Returns array representation

## See Also

- [TFileUpload](./TFileUpload.md)
