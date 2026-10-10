# Web/UI/ActiveControls/TActiveSuggestionList

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [ActiveControls](./INDEX.md) / **`TActiveSuggestionList`**

## Class Info
**Location:** `framework/Web/UI/ActiveControls/TActiveSuggestionList.php`
**Namespace:** `Prado\Web\UI\ActiveControls`
**Since:** 4.4.0

## Overview
Active counterpart of [`TSuggestionList`](../WebControls/TSuggestionList.md) (`<datalist>`). Items added, removed, or data bound during a callback, after OnLoad, replace the client-side options when the callback completes. Requires `ActiveControl.EnableUpdate` (default true).

Implements `IActiveControl` only; it raises no callback. Update it from another control's callback, typically the `TActiveTextBox` that uses it.

## Mechanics

| Piece | Role |
|---|---|
| `TActiveControlAdapter` | Adapter. Not `TActiveListControlAdapter`: a datalist has no selection, so `setSelectedIndex()` and friends send nothing |
| `TActiveListItemCollection` | Items; `insertAt`/`removeAt` after OnLoad set `ListHasChanged` |
| `onPreRender()` → `updateListItems()` | Guard `canUpdateClientSide()`; when changed, `TActiveListItemCollection::updateClientSide()` |
| `TCallbackClientScript::setListItems()` | Delegates a `TSuggestionList` to `setSuggestionListItems()` |
| `setSuggestionListItems()` | Builds `[value, label, attributes]` per item through `TSuggestionList::getOptionData()`; calls `Prado.Element.setDataListOptions` |
| `Prado.Element.setDataListOptions` (`prado.js`) | `replaceChildren()` with new `<option>`s; label through `textContent`, never HTML; ignores non-datalist elements |

The prompt and `Group` of `setOptions` for `<select>` are not sent. Disabled and empty-value items are skipped, matching the server render.

## Template Usage

```xml
<com:TActiveTextBox ID="City" SuggestionList="Cities" AutoPostBack="true" OnCallback="cityChanged" />
<com:TActiveSuggestionList ID="Cities" />
```

```php
public function cityChanged($sender, $param)
{
    $this->Cities->setDataSource($this->findCities($this->City->getText()));
    $this->Cities->dataBind();
}
```

`AutoPostBack` raises the callback on `change` (blur or Enter), not on each keystroke.

## Gotchas

- Changes to an existing item's properties (`setText`, `setValue`) are not tracked; remove and re-add, or rebind.
- Items changed before OnLoad do not update the client; they render with the page.
- `TActiveTextBox::setSuggestionList()` updates the input's `list` attribute; changing `TextMode` in a callback does not.

## See Also

- [TSuggestionList](../WebControls/TSuggestionList.md), [TActiveTextBox](./TActiveTextBox.md), [TActiveListItemCollection](./TActiveListItemCollection.md), [TCallbackClientScript](./TCallbackClientScript.md)

## Tests

`tests/unit/Web/UI/ActiveControls/TActiveSuggestionListTest.php` (uses the `TTestCallbackPage` harness) and `tests/playwright/web/TSuggestionListTestCase.spec.js`.
