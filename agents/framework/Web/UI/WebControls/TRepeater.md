# Web/UI/WebControls/TRepeater

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TRepeater`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TRepeater.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TRepeater is a lightweight data-bound container that renders a repeated set of items based on a data source. It is the most flexible and lowest-overhead data control in Prado — it has no built-in paging, sorting, or editing. Layout is entirely controlled by templates or renderer classes.

TRepeater implements `INamingContainer`, so each item has a unique prefixed client ID. It renders no outer HTML element — its items are rendered inline.

Content types are specified via template properties (inline `.tpl` declarations) or renderer properties (external class names). When both are set for the same slot, the renderer takes precedence.

## Inheritance

`TRepeater` → `TDataBoundControl` → `TWebControl` → `TControl` → `TComponent`

Implements: `INamingContainer`

## Key Constants / Enums

**`TListItemType`** (item type assigned to each created item):
- `Item` — even-indexed data rows
- `AlternatingItem` — odd-indexed data rows (falls back to `Item` template/renderer if not set)
- `Header` — created once before the first item (only if data is non-empty)
- `Footer` — created once after the last item (only if data is non-empty)
- `Separator` — inserted between items (only if separator template/renderer is configured)

## Key Properties

**Data / key field:**

| Property | Type | Default | Description |
|---|---|---|---|
| `DataSource` | mixed | `null` | Traversable data (array, TList, TMap, etc.). Inherited from `TDataBoundControl`. |
| `DataKeyField` | string | `''` | Field name used to populate `DataKeys`. If empty, the array key is used. |
| `DataKeys` | TList | (auto) | Read-only after binding. Contains one key per data row in order. |

**Template properties** (set inline in `.tpl` using `<prop:ItemTemplate>…</prop:ItemTemplate>`):

| Property | Description |
|---|---|
| `ItemTemplate` | Template for each Item and (by default) AlternatingItem row |
| `AlternatingItemTemplate` | Template for alternating rows (overrides ItemTemplate for odd rows) |
| `HeaderTemplate` | Template rendered before the first item |
| `FooterTemplate` | Template rendered after the last item |
| `SeparatorTemplate` | Template inserted between items |
| `EmptyTemplate` | Template rendered when data source is empty |

**Renderer properties** (class names in namespace format; take precedence over templates):

| Property | Description |
|---|---|
| `ItemRenderer` | Class for Item / fallback AlternatingItem |
| `AlternatingItemRenderer` | Class for alternating rows |
| `HeaderRenderer` | Class for the header |
| `FooterRenderer` | Class for the footer |
| `SeparatorRenderer` | Class for separators |
| `EmptyRenderer` | Class rendered when data is empty |

**Item access:**

| Property | Type | Description |
|---|---|---|
| `Items` | TRepeaterItemCollection | Collection of Item and AlternatingItem controls |
| `Header` | TControl | The header control (null if no header template/renderer) |
| `Footer` | TControl | The footer control (null if no footer template/renderer) |

## Key Methods

| Method | Description |
|---|---|
| `dataBind()` | Inherited from `TDataBoundControl`. Calls `performDataBinding()` with the data source. |
| `performDataBinding($data)` | Builds all items from the data. Clears existing items first. Populates `DataKeys` and raises `OnItemCreated` / `OnItemDataBound` for each item. |
| `createItem($itemIndex, $itemType)` | Creates a single item control (renderer or template-based). Override to customise item creation. |
| `reset()` | Clears all child controls, items, header, and footer. |
| `getDataFieldValue($data, $field)` | Extracts a field value from a data row (array key, TMap/TList index, or component property). |
| `bubbleEvent($sender, $param)` | Intercepts `TRepeaterCommandEventParameter` bubbled from child buttons and raises `OnItemCommand`. |

## Events

| Event | Parameter Type | Raised When |
|---|---|---|
| `OnItemCreated` | `TRepeaterItemEventParameter` | After an item is instantiated and the template is applied, before it is added to the control tree |
| `OnItemDataBound` | `TRepeaterItemEventParameter` | After an item is data-bound (`dataBind()` called on the item) |
| `OnItemCommand` | `TRepeaterCommandEventParameter` | A button inside an item raises `OnCommand` (bubbled up) |

`TRepeaterItemEventParameter::getItem()` returns the newly created `TRepeaterItem` (or renderer instance).

`TRepeaterCommandEventParameter` extends `TRepeaterItemEventParameter` and adds `getCommandName()`, `getCommandParameter()`, and `getCommandSource()`.

## Patterns & Gotchas

- **No outer element** — TRepeater does not wrap items in a `<table>`, `<ul>`, or `<div>`. Use templates to supply your own wrapper markup.
- **Header/footer only when data is non-empty** — `Header` and `Footer` are created only if there is at least one data row. They do not appear for empty data; use `EmptyTemplate`/`EmptyRenderer` for that case.
- **Separator placement** — Separators are inserted between items (count = item count − 1). They are not placed before the first item or after the last.
- **`DataKeyField` for postback access** — Data rows are not stored in viewstate. After postback, access the original data via `getDataKeys()` to re-fetch from a database or cache.
- **Renderer vs template precedence** — If both a renderer and a template are configured for the same slot, the renderer wins. An unset renderer defaults to `''` (empty string), which means "use the template."
- **Renderer with `IDataRenderer`** — If the renderer class implements `IDataRenderer`, `setData($row)` is called with the current data row during databinding.
- **Renderer with `IItemDataRenderer`** — The renderer also receives `setItemIndex($i)` and `setItemType($type)`. Use `TRepeaterItemRenderer` as a convenient base.
- **AlternatingItem fallback** — If neither `AlternatingItemTemplate` nor `AlternatingItemRenderer` is set, odd rows use the same Item template/renderer.
- **`OnItemCreated` fires before `dataBind()`** — The item exists but its data is not bound yet. Use `OnItemDataBound` when you need to read bound data values.
- **`OnItemCommand` centralises button handling** — Instead of attaching an `OnCommand` handler to every button inside every item, attach a single `OnItemCommand` handler to the repeater.
- **Empty data rendering** — The repeater renders nothing (not even an outer tag) when there are no items and no EmptyTemplate/EmptyRenderer is set.
