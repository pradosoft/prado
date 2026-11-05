# Web/UI/WebControls/TCiteTrait

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TCiteTrait`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TCiteTrait.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Since:** 4.4.0

## Overview
TCiteTrait backs the `Cite` property of an element with a `cite` attribute. `Cite` is the URL of the source of a quotation or of the explanation of an edit.

## Methods

| Method | Description |
|---|---|
| `getCite()` / `setCite($value)` | The `Cite` property, kept trimmed in viewstate under `Cite`; default `''` |
| `addCiteAttributeToRender($writer)` | Protected. Adds `cite` when `Cite` is set; the using class calls it from `addAttributesToRender()` |

## Using Classes

[TQuote](./TQuote.md), [TBlockQuote](./TBlockQuote.md), and through [TBaseEdit](./TBaseEdit.md), [TIns](./TIns.md) and [TDel](./TDel.md).

## Accessibility

Browsers do not show or announce `cite`. A source that readers need is a link or a [TCite](./TCite.md) in the text.
