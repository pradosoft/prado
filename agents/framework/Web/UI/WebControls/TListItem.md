# Web/UI/WebControls/TListItem

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TListItem`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TListItem.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TListItem represents an item in a list control. Each item has Text and Value properties, can be selected or enabled, and can have custom attributes rendered.

`Content` is HTML rendered inside the `<option>` of a TDropDownList or TListBox in place of the encoded Text. The template body sets it (`addParsedObject()` appends body text; other objects are ignored). Content is not encoded. It is serialized in viewstate and sent by `TCallbackClientScript::setListItems()`. A [RichOptions](./TRichOptionsTrait.md) select shows its elements; other browsers and the option's accessible name use its text, so it contains the item's text.

## Key Properties/Methods

- `getText()` / `setText()` - Gets or sets the item text
- `getValue()` / `setValue()` - Gets or sets the item value
- `getSelected()` / `setSelected()` - Gets or sets whether item is selected
- `getEnabled()` / `setEnabled()` - Gets or sets whether item is enabled
- `getContent()` / `setContent()` - Gets or sets the HTML content (trimmed); default `''`
- `getHasContent()` - Returns whether the item has content
- `addParsedObject()` - Appends template body text to Content
- `getAttributes()` - Returns TAttributeCollection for custom attributes
- `getHasAttributes()` - Returns whether item has custom attributes
- `hasAttribute()` - Checks if named attribute exists
- `getAttribute()` / `setAttribute()` - Gets or sets attribute value
- `removeAttribute()` - Removes an attribute
- `__clone()` - Clones the attribute collection

## See Also

- [TListControl](./TListControl.md)
- [TRichOptionsTrait](./TRichOptionsTrait.md)
