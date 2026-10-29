# Web/UI/WebControls/TNoScript

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TNoScript`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TNoScript.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** [`THtmlElement`](./THtmlElement.md)

## Overview
TNoScript represents the HTML `<noscript>` element. A browser with scripting disabled shows its content; a browser with scripting enabled shows nothing. It holds the fallback of a feature that needs JavaScript.

## Template Usage

```xml
<com:TNoScript>
    <p>The chart needs JavaScript. <com:THyperLink NavigateUrl="report.csv" Text="Download the data" /></p>
</com:TNoScript>
```

## Rules

| Condition | Result |
|---|---|
| Every request | The content renders on the server; the browser decides whether it shows. |
| Controls inside | Post back only without JavaScript: no `AutoPostBack`, no callbacks. |
| Callback re-render | Inserts content a scripting browser does not show. |
| Scripting enabled | The content is absent from the accessibility tree. |

TReCaptcha and TProofOfWork write their own inline `<noscript>` fallbacks.

## Tests

- `tests/unit/Web/UI/WebControls/TNoScriptTest.php`

## See Also

- [THtmlElement](./THtmlElement.md)
- [TCanvas](./TCanvas.md)

**@since 4.4.0**
