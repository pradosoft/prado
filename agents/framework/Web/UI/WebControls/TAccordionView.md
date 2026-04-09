# TAccordionView

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md) > [TAccordionView](./TAccordionView.md)

**Location:** `framework/Web/UI/WebControls/TAccordionView.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview

TAccordionView represents a single view in a TAccordion control. Each view has a header label (caption) and content area. Headers can optionally be hyperlinks with a NavigateUrl.

## Key Properties/Methods

- `Caption` - Text displayed on the view header
- `NavigateUrl` - URL for header hyperlink (if set, clicking redirects)
- `Text` - Text content displayed on the view (overrides child content)
- `Active` - Whether this view is currently active
- `renderHeader()` - Renders the header associated with the view
- `renderContents()` - Renders body contents

## See Also

- [TAccordion](./TAccordion.md)
