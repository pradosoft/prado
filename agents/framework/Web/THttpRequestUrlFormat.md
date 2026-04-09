# THttpRequestUrlFormat

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [THttpRequestUrlFormat](./THttpRequestUrlFormat.md)

**Location:** `framework/Web/THttpRequestUrlFormat.php`
**Namespace:** `Prado\Web`

## Overview

THttpRequestUrlFormat defines the enumerable type for URL formats that THttpRequest can recognize. It determines how URL parameters are parsed and structured.

## Key Properties/Methods

- `Get` - Standard query string format: `/path/index.php?name1=value1&name2=value2`
- `Path` - Path-based format: `/path/index.php/name1,value1/name2,value2`
- `HiddenPath` - Hidden path format: `/path/name1,value1/name2,value2`

## See Also

- [THttpRequest](./THttpRequest.md)
