# Web/UI/WebControls/TSkipLink

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TSkipLink`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TSkipLink.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** [THyperLink](./THyperLink.md)

## Overview
TSkipLink (@since 4.4.0, #1399) renders a link that skips the repeated blocks before the main content (WCAG 2.4.1 Bypass Blocks). The bundled stylesheet `assets/skiplink.css` hides the link until it receives focus. Place it first in the `<body>`.

## Template Usage

```xml
<com:TSkipLink TargetID="Content" />
<com:THeader>...</com:THeader>
<com:TMain ID="Content">...</com:TMain>
```

## Key Properties

| Property | Description |
|---|---|
| `TargetID` | Control ID resolved by `findControl()` to its ClientID; otherwise an HTML element ID |
| `TargetClientID` | Read-only: the resolved fragment ID |
| `NavigateUrl` | An explicit URL wins; otherwise `#` + `TargetClientID` |
| `CssUrl` | `'default'` publishes `skiplink.css`; `''` registers none; any other value is a stylesheet URL |
| `DefaultText` | Read-only: localized "Skip to main content", rendered when `Text`, `ImageUrl`, and body content are empty |

## Rendering

- `class="skip-link"` (`TSkipLink::CSS_CLASS`) renders before `CssClass`; a duplicate in `CssClass` is dropped. The stored `CssClass` is unchanged.
- The stylesheet clips the link with `clip-path` while unfocused, so screen readers still read it; on focus it shows at the top left in system colors (`Canvas`, `LinkText`).
- Following the fragment moves the browser's sequential focus starting point to the target, so the next Tab reaches the first focusable element in the main content. The target needs no `tabindex`.

## See Also

- [THyperLink](./THyperLink.md)
- [TMain](./TMain.md)
