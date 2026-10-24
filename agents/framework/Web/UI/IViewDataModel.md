# Web/UI/IViewDataModel

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [UI](INDEX.md) / **`IViewDataModel`**

**Location:** `framework/Web/UI/IViewDataModel.php`
**Namespace:** `Prado\Web\UI`
**Since:** 4.4.0

## Overview
A model that exchanges its data with the controls of a [TTemplateControl](./TTemplateControl.md). `tryToUpdateView()` sets each value of `getViewData()` on the control whose ID is the key; `tryToUpdateModel()` reads the input controls and passes their values, keyed by control ID, to `setViewData()`. The model takes the keys it owns and ignores the rest.

The prado-activerecord `TActiveRecord` provides both methods by name (it also runs on PRADO 4.3, which has no interface): columns first, then one entry per `$RELATIONS` key, a `HAS_MANY` relation as a list; `setViewData()` writes columns only.

## Key Methods

| Method | Description |
|--------|-------------|
| `getViewData(): array` | Values keyed by field name; an iterable value is a collection for a data-bound control |
| `setViewData(array $data): void` | Takes the view values it owns, keyed by control ID |

## See Also

- [TTemplateControl](./TTemplateControl.md) - `tryToUpdateView()`, `tryToUpdateModel()`
- [IDataRenderer](../../IDataRenderer.md) - the control side: `getData()` / `setData()`
