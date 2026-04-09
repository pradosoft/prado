# TFlushOutput

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md) > [TFlushOutput](./TFlushOutput.md)

**Location:** `framework/Web/UI/WebControls/TFlushOutput.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview

TFlushOutput enables forced flushing of the output buffer at certain points in page rendering. When placed in a template, it sends buffered content to the client immediately while continuing to buffer subsequent output.

## Key Properties/Methods

- `getContinueBuffering()` / `setContinueBuffering()` - Gets or sets whether buffering continues after this point
- `render()` - Flushes output of completely rendered controls to the client

## See Also

- [TControl](./TControl.md)
