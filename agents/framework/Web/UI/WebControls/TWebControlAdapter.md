# TWebControlAdapter

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md) > [TWebControlAdapter](./TWebControlAdapter.md)

**Location:** `framework/Web/UI/WebControls/TWebControlAdapter.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview

TWebControlAdapter is the base class for adapters that customize rendering for Web controls. It may modify markup or behavior for specific browsers.

## Key Properties/Methods

- `render($writer)` - Renders control (calls renderBeginTag, renderContents, renderEndTag)
- `renderBeginTag($writer)` - Renders opening tag
- `renderContents($writer)` - Renders body contents
- `renderEndTag($writer)` - Renders closing tag

## See Also

- [TWebControl](./TWebControl.md)
- [TControlAdapter](./TControlAdapter.md)
