# Web/UI/WebControls/THGroup

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`THGroup`**

## Class Info
**Location:** `framework/Web/UI/WebControls/THGroup.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** `THtmlElement`

## Overview
THGroup represents the HTML `<hgroup>` element: a heading grouped with its subtitle or tagline. When `HeaderText` is set, it renders in the `<h1>`–`<h6>` of `HeaderLevel`, followed by the child controls. The children are `<p>` elements; without HeaderText, one child is the heading, such as a [THeader2](./THeader2.md).

The default tag is `hgroup`. It can be overridden at runtime via the `TagName` property (inherited from [THtmlElement](./THtmlElement.md)).

## Template Usage

```xml
<com:THGroup HeaderText="Prado" HeaderLevel="1">
	<p>Component framework for PHP</p>
</com:THGroup>
```

## Key Properties

| Property | Description |
|---|---|
| `HeaderText` | Heading text, rendered before the children when set |
| `HeaderLevel` | 1–6; default 1; another value throws `hgroup_headerlevel_invalid` |
| `Encode` | HTML-encodes `HeaderText` ([TEncodeTrait](./TEncodeTrait.md)); default false |

## Accessibility

- Browsers expose `<hgroup>` with the `group` role.
- Only the heading is in the outline and the heading list of screen readers; the subtitle is read as text after it.
- An `<hgroup>` holds exactly one heading.

## Tests

- `tests/unit/Web/UI/WebControls/THGroupTest.php`

## See Also

- [THtmlElement](./THtmlElement.md)
- [THeader1](./THeader1.md)

**@since 4.4.0**
