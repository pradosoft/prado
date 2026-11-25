# Web/UI/WebControls/TTableCaptionAlign

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TTableCaptionAlign`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TTableCaptionAlign.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TTableCaptionAlign defines the enumerable type for the possible alignments of an HTML table caption. Used with TTable controls to specify caption positioning.

## Values

- `NotSet` - Alignment not specified
- `Top` - Caption above the table (`caption-side:top`)
- `Bottom` - Caption below the table (`caption-side:bottom`)
- `Left` - Caption above the table, text aligned left (`caption-side:top; text-align:left`)
- `Right` - Caption above the table, text aligned right (`caption-side:top; text-align:right`)

CSS has no left or right `caption-side`; before 4.4.0 `Left` and `Right` rendered `caption-side:left|right`, which browsers ignore.

## See Also

- [TTable](./TTable.md)
