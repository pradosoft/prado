# Web/UI/WebControls/TPagerAccessibilityTrait

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TPagerAccessibilityTrait`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TPagerAccessibilityTrait.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TPagerAccessibilityTrait (@since 4.4.0, #1407) gives the pager buttons of `TPager` and `TDataGrid` accessible names and renders a pager as a named navigation landmark (`TPager`, `TDataGridPager`).

| Pager part | Rendering |
|---|---|
| pager element | `<nav aria-label="Pagination">` unless `aria-label` or `aria-labelledby` is set |
| `<<`, `<`, `>`, `>>` button | `aria-label` "First page", "Previous page", "Next page", "Last page" |
| numeric pager `<`, `>` button | `aria-label` "Previous pages", "Next pages" |
| numeric page button | `aria-label` "Page N" |
| current page | `aria-current="page"` |
| page dropdown list | `aria-label` "Page" |
| list layout | a `<ul>` with each pager button in an `<li>` |

## Methods

- `labelPagerButton($button, $text, $label)` - Names a button whose text is a default page text; custom text is already a name
- `labelPageNumberButton($button, $pageNumber, $current)` - "Page N" and `aria-current="page"`
- `labelPageList($list)` - Names the dropdown list pager
- `getIsPagerButtonNameable($button)` - A `TWebControl` other than `TLabel`: ARIA prohibits naming a generic `<span>`
- `addPagerLabelToRender($writer)` - Default `aria-label` without creating the ARIA collection
- `renderPagerList($writer)` - `<ul>`/`<li>` rendering; skips the `"\n"` literals between buttons

The labels are added in the build methods after `createPagerButton()`, so `TActivePager` and `TActiveDataGrid`, which override `createPagerButton()`, get them too. Names pass through `Prado::localize()`.

## See Also

- [TPager](./TPager.md)
- [TDataGridPager](./TDataGridPager.md)
