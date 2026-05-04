# Util/Behaviors/TPageTopAnchorBehavior

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Behaviors](./INDEX.md) / **`TPageTopAnchorBehavior`**

## Class Info
**Location:** `framework/Util/Behaviors/TPageTopAnchorBehavior.php`
**Namespace:** `Prado\Util\Behaviors`

## Overview
TPageTopAnchorBehavior adds a `<a name='top'>` anchor at the top of every page just before the TForm. This enables page navigation to return to the top from elsewhere on the page.

## Key Properties/Methods

- `events()` - Returns `['OnSaveStateComplete' => 'addFormANameAnchor']`
- `addFormANameAnchor($page, $param)` - Inserts the anchor before the TForm
- `getTopAnchor()` / `setTopAnchor($value)` - Gets/sets the anchor name (default: 'top')

## See Also

- [TPage](../../Web/UI/TPage.md)
