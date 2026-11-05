# Web/UI/WebControls/TPager

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TPager`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TPager.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TPager creates a pager that provides UI for end-users to interactively specify which page of data to be rendered in a TDataBoundControl-derived control (such as TDataList, TRepeater, TCheckBoxList). It can display NextPrev, Numeric, or DropDownList modes.

## Key Properties/Methods

- `getMode()` / `setMode($value)` - Pager mode (NextPrev, Numeric, DropDownList)
- `getButtonType()` / `setButtonType($value)` - Button type (LinkButton, PushButton, ImageButton)
- `getCurrentPageIndex()` / `setCurrentPageIndex($value)` - Current zero-based page index
- `getPageCount()` - Total number of pages
- `getControlToPaginate()` / `setControlToPaginate($value)` - ID path of control to paginate
- `getPageButtonCount()` / `setPageButtonCount($value)` - Max pager buttons to display
- `onPageIndexChanged($param)` - Event raised when page index changes
- `buildPager()` - Builds pager content based on mode
- `getFirstPageText()` / `getPrevPageText()` / `getNextPageText()` / `getLastPageText()` - Button texts; default `<<`, `<`, `>`, `>>`
- `createPagerButton(...)` - Creates a TLinkButton, TLabel, TButton, or TImageButton
- `getListLayout()` / `setListLayout($value)` - Renders the buttons as `<ul>` list items (default false; @since 4.4.0)

## Page Texts
In link-button and label pager buttons, the default page texts (`<`, `>`, `<<`, `>>`) render HTML-encoded (`encodeDefaultPagerText()`); any other text renders as HTML. Push and image buttons put the text in an encoded attribute.

## Accessibility (4.4.0)
TPager renders `<nav>` named "Pagination" (an `aria-label`/`aria-labelledby` on the pager replaces it); `<<`/`<`/`>`/`>>` buttons named "First/Previous/Next/Last page"; numeric buttons named "Page N"; numeric `<`/`>` named "Previous/Next pages"; the current page has `aria-current="page"`. Names pass through `Prado::localize()`. A `TLabel` (`<span>`, disabled link-mode button) takes no `aria-label`. The DropDownList mode `<select>` is named "Page". Shared code: [TPagerAccessibilityTrait](./TPagerAccessibilityTrait.md). Two pagers on a page need distinct names.

`<nav>` is a block element; the pager was an inline `<span>` before 4.4.0. `ListLayout` is opt-in because an unstyled `<ul>` stacks bulleted items.

## See Also

- [TPagerPageChangedEventParameter](./TPagerPageChangedEventParameter.md)
- [TDataBoundControl](./TDataBoundControl.md)
