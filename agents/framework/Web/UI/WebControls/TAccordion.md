# Web/UI/WebControls/TAccordion

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TAccordion`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TAccordion.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TAccordion displays an accordion control where users can click on view headers to switch among different accordion views. Only one accordion view is visible (active) at a time. Views can be activated by index, ID, or direct view instance.

## Key Properties/Methods

- `ActiveViewIndex` - Zero-based index of the active view
- `ActiveViewID` - Text ID of the visible view
- `ActiveView` - The active view instance
- `CssUrl` - URL for custom CSS file
- `HeaderCssClass` / `ActiveHeaderCssClass` - CSS classes for headers
- `ViewCssClass` - CSS class for view content div
- `ViewHeight` - Maximum height for views (auto-sizes if not set)
- `AnimationDuration` - Animation length in seconds (default 1s)
- `Views` - Collection of TAccordionView controls

## See Also

- [TAccordionView](./TAccordionView.md)
- [TAccordionViewCollection](./TAccordionViewCollection.md)

## Color Scheme

The header and active-header backgrounds, and the active-header text and border, adapt through `light-dark()`. The shipped stylesheet declares no `color-scheme` of its own, so an
application that declares none keeps the light rendering it already has. See
[assets/INDEX.md](./assets/INDEX.md) for the contract.
