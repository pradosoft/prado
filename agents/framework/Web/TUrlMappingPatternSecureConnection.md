# Web/TUrlMappingPatternSecureConnection

### Directories
[framework](../INDEX.md) / [Web](./INDEX.md) / **`TUrlMappingPatternSecureConnection`**

## Class Info
**Location:** `framework/Web/TUrlMappingPatternSecureConnection.php`
**Namespace:** `Prado\Web`

## Overview
TUrlMappingPatternSecureConnection defines the enumerable type for SecureConnection URL prefix behavior used by `TUrlMappingPattern::constructUrl()`. It controls HTTPS/HTTP prefixing when building URLs.

## Key Properties/Methods

- `Automatic` - Keep current connection status, no prefixing
- `Enable` - Always prefix with `https://`
- `Disable` - Always prefix with `http://`
- `EnableIfNotSecure` - Prefix with `https://` if current connection is not secure
- `DisableIfSecure` - Prefix with `http://` if current connection is secure

## See Also

- [TUrlMapping](./TUrlMapping.md)
- [TUrlMappingPattern](./TUrlMappingPattern.md)
