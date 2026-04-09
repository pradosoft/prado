# TRequestConnectionUpgrade

### Directories

[./](../INDEX.md) > [Web](../INDEX.md) > [Behaviors](./INDEX.md) > [TRequestConnectionUpgrade](./TRequestConnectionUpgrade.md)

**Location:** `framework/Web/Behaviors/TRequestConnectionUpgrade.php`
**Namespace:** `Prado\Web\Behaviors`

## Overview

A behavior for `THttpRequest` that injects HTTP "Connection: Upgrade" and "Upgrade" headers into URL parameters. This enables service selection (e.g., WebSocket) via HTTP upgrade headers without requiring explicit URL parameters. Attaches to the `onResolveRequest` event via the `events()` method.

## Key Properties/Methods

- **`events()`** - Returns `['onResolveRequest' => 'processHeaders']` to attach the handler
- **`processHeaders($request, $param)`** - Parses the Upgrade header, extracts requested services, and merges them into URL parameters as keys. Logs a NOTICE if "Connection: Upgrade" exists without an "Upgrade" header.

## See Also

- `Prado\Web\THttpRequest` - The HTTP request class this behavior attaches to
- `Prado\Util\TBehavior` - Base behavior class
