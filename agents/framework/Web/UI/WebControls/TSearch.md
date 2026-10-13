# Web/UI/WebControls/TSearch

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TSearch`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TSearch.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `THtmlElement`

## Overview
TSearch represents the HTML5 `<search>` element: the group of controls for a search or filtering operation (query input, submit button, filter options).

The default tag is `search`. It can be overridden at runtime via the `TagName` property (inherited from [THtmlElement](./THtmlElement.md)).

## Template Usage

```xml
<com:TSearch Aria.label="Site">
    <com:TLabel ForControl="Query" Text="Search" />
    <com:TTextBox ID="Query" TextMode="Search" />
    <com:TButton Text="Go" OnClick="search" />
</com:TSearch>
```

## Key Properties

| Property | Description |
|---|---|
| `TagName` | HTML tag name. Default: `'search'`. Another tag renders `role="search"`. |
| `Role` | When set, replaces the `role="search"` of another tag. |
| `Aria.label` / `Aria.labelledby` | Names the landmark; needed when a page has more than one. |

## Accessibility

| Condition | Result |
|---|---|
| TagName `search` | No `role` attribute; the browser exposes a `search` landmark. |
| Another TagName (case-insensitive compare) | `role="search"`, unless `Role` is set. |
| Browser predating `<search>` (Chrome/Firefox 118, Safari 17) | No landmark and inline display; TagName `div` supports it. |

- More than one search landmark on a page → name each with `Aria.label` or `Aria.labelledby`.
- The landmark does not label the query input; the input keeps its own `TLabel`.
- Do not add `role="search"` to a `<search>`; it duplicates the implicit role.
- Verified with `Accessibility.getFullAXTree` in Chromium; Playwright's computed tree matches in Firefox and WebKit.

## Tests

- `tests/unit/Web/UI/WebControls/TSearchTest.php`
- `tests/playwright/web/TSearchTestCase.spec.js` with `tests/harness/web/protected/pages/SearchTest.page`

## See Also

- [THtmlElement](./THtmlElement.md)
- [TNav](./TNav.md)
- [TTextBox](./TTextBox.md) (`TextMode="Search"`)

**@since 4.4.0**
