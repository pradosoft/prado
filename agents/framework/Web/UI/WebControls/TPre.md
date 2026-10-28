# Web/UI/WebControls/TPre

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TPre`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TPre.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `THtmlElement`

## Overview
TPre represents the HTML `<pre>` element: preformatted text whose whitespace and line breaks render as written. The browser drops one line break directly after the opening tag.

The default tag is `pre`. It can be overridden at runtime via the `TagName` property (inherited from [THtmlElement](./THtmlElement.md)).

## Template Usage

```xml
<com:TPre><com:TCode>foreach ($items as $item) {
	echo $item;
}</com:TCode></com:TPre>
```

## Accessibility

- `<pre>` has no ARIA role; screen readers read its text line by line.
- Text art → `Role="img"` and an `Aria.label` that describes it.
- Code inside a TPre is a [TCode](./TCode.md).

## Tests

- `tests/unit/Web/UI/WebControls/TPreTest.php`

## See Also

- [THtmlElement](./THtmlElement.md)
- [TCode](./TCode.md)

**@since 4.4.0**
