# TTable

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md) > [TTable](./TTable.md)

**Location:** `framework/Web/UI/WebControls/TTable.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview

Renders an HTML `<table>` element. Child rows are managed via `TTableRowCollection`. Supports caption, cell spacing/padding, grid lines, horizontal alignment, and a background image. Rows may be grouped into `<thead>`, `<tbody>`, and `<tfoot>` sections.

Extends `[TWebControl](./TWebControl.md)`.

## Key Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `Caption` | string | '' | Text for `<caption>` element |
| `CaptionAlign` | TTableCaptionAlign | NotSet | Caption alignment (Top/Bottom/Left/Right/NotSet) |
| `CellSpacing` | int | -1 | `cellspacing` attribute (-1 = not rendered) |
| `CellPadding` | int | -1 | `cellpadding` attribute (-1 = not rendered) |
| `HorizontalAlign` | THorizontalAlign | NotSet | Table alignment (Left/Center/Right/NotSet) |
| `GridLines` | TTableGridLines | None | Grid line style (None/Horizontal/Vertical/Both) |
| `BackImageUrl` | string | '' | Background image URL |

## Key Methods

```php
$table->getRows(): TTableRowCollection   // collection of TTableRow children
$table->addParsedObject($obj): void      // auto-adds TTableRow / TTableSection children
```

## TTableRow

Represents `<tr>`. Child of `TTable` or `TTableSection`.

| Property | Description |
|----------|-------------|
| `Cells` | TTableCellCollection of TTableCell / TTableHeaderCell |
| `HorizontalAlign` | Row-level horizontal alignment |
| `VerticalAlign` | Row-level vertical alignment |
| `TableSection` | TTableRowSection enum: TableHeader / TableBody / TableFooter |

## TTableCell / TTableHeaderCell

`TTableCell` renders `<td>`; `TTableHeaderCell` renders `<th>`.

| Property | Description |
|----------|-------------|
| `Text` | Cell content (short form; overrides child controls) |
| `ColumnSpan` | `colspan` attribute |
| `RowSpan` | `rowspan` attribute |
| `HorizontalAlign` / `VerticalAlign` | Cell alignment |
| `Wrap` | Whether cell content wraps |
| `AssociatedHeaderCellID` | `headers` attribute for `<td>` |
| `AbbreviatedText` | `abbr` attribute for `<th>` |
| `CategoryText` | `axis` attribute for `<th>` |
| `Scope` | `scope` attribute (Row/Column/RowGroup/ColGroup) |

## TTableSection

Groups rows into `<thead>`, `<tbody>`, or `<tfoot>`.

```
TTableSection::TableHeader  → <thead>
TTableSection::TableBody    → <tbody>
TTableSection::TableFooter  → <tfoot>
```

**Section ordering is required** — render `<thead>` before `<tbody>` before `<tfoot>` in the template or in `renderContents()`.

## Template Usage

```xml
<com:TTable>
    <com:TTableRow>
        <com:TTableHeaderCell Text="Name" />
        <com:TTableHeaderCell Text="Value" />
    </com:TTableRow>
    <com:TTableRow>
        <com:TTableCell Text="Foo" />
        <com:TTableCell Text="Bar" />
    </com:TTableRow>
</com:TTable>
```

Or with sections:

```xml
<com:TTable>
    <prop:Rows>
        <com:TTableRow TableSection="TableHeader">
            <com:TTableHeaderCell Text="Column A" />
        </com:TTableRow>
        <com:TTableRow TableSection="TableBody">
            <com:TTableCell Text="Data 1" />
        </com:TTableRow>
    </prop:Rows>
</com:TTable>
```

## Patterns & Gotchas

- **Section ordering** — if using `TTableSection`, rows must be added in `TableHeader → TableBody → TableFooter` order. The renderer outputs sections in that fixed order regardless of insertion order; mismatched ordering produces invalid HTML.
- **`Caption` requires `CaptionAlign`** — if `Caption` is set but `CaptionAlign` is `NotSet`, the caption is still rendered without an `align` attribute (valid HTML5).
- **`TDataGrid` uses TTable internally** — `TDataGrid` is a specialized subclass; use `TTable` directly only for static or manually managed table layouts.
- **`addParsedObject()`** — accepts both `TTableRow` (direct child) and `TTableSection` (group container). Template parsing routes children automatically.
