# TJuiAutoComplete

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [JuiControls](./INDEX.md) > [TJuiAutoComplete](./TJuiAutoComplete.md)

**Location:** `framework/Web/UI/JuiControls/TJuiAutoComplete.php`
**Namespace:** `Prado\Web\UI\JuiControls`

## Overview

Textbox with jQuery UI autocomplete suggestions. Extends [TActiveTextBox](../ActiveControls/TActiveTextBox.md). Suggestions are requested via callbacks and rendered server-side. OnSuggest event handler sets the data source for suggestions.

## Key Properties/Methods

- `getSuggestions()` - Returns the TRepeater for suggestion items
- `getResultPanel()` - Returns the TPanel holding suggestion results
- `setDataSource($data)` - Sets data source for suggestions
- `getSeparator()` / `setSeparator($value)` - Token separators
- `getFrequency()` / `setFrequency($value)` - Delay before requesting suggestions
- `getMinChars()` / `setMinChars($value)` - Minimum characters before suggestions
- `onSuggest($param)` - Raises OnSuggest event with token
- `onSuggestionSelected($param)` - Raises OnSuggestionSelected event with index
- `dataBind()` - Renders suggestions during callback

## See Also

- [TJuiAutoCompleteEventParameter](TJuiAutoCompleteEventParameter.md)
- [TJuiAutoCompleteTemplate](TJuiAutoCompleteTemplate.md)
