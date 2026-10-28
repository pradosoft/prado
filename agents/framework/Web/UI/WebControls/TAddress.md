# Web/UI/WebControls/TAddress

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TAddress`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TAddress.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `THtmlElement`

## Overview
TAddress represents the HTML `<address>` element: the contact information for its nearest [TArticle](./TArticle.md) or, outside an article, for the page. A postal address that is not contact information is ordinary text.

The default tag is `address`. It can be overridden at runtime via the `TagName` property (inherited from [THtmlElement](./THtmlElement.md)).

## Template Usage

```xml
<com:TFooter>
	<com:TAddress>Contact <a href="mailto:web@example.com">the webmaster</a>.</com:TAddress>
</com:TFooter>
```

## Accessibility

- Browsers expose `<address>` with the `group` role; it has no name unless `Aria.label` or `Aria.labelledby` gives one.
- Browsers render it as a block in italics; no line breaks are added around it.

## Tests

- `tests/unit/Web/UI/WebControls/TAddressTest.php`

## See Also

- [THtmlElement](./THtmlElement.md)
- [TArticle](./TArticle.md)
- [TFooter](./TFooter.md)

**@since 4.4.0**
