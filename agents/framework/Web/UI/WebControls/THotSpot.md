# Web/UI/WebControls/THotSpot

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`THotSpot`**

## Class Info
**Location:** `framework/Web/UI/WebControls/THotSpot.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
THotSpot is the abstract base class for hot spot shapes in TImageMap. Provides base functionality for circle, rectangle, and polygon hotspots.

## Key Properties/Methods

- `Shape` - Shape type (abstract)
- `Coordinates` - Coordinates defining the shape (abstract)
- `AccessKey` - Keyboard navigation key
- `AlternateText` - Alt text for the hotspot
- `HotSpotMode` - Behavior on click (Navigate, PostBack, Inactive)
- `NavigateUrl` - URL for navigation mode
- `PostBackValue` - Value for postback mode
- `Target` - Target window for navigation
- `TabIndex` - Tab order
- `CausesValidation` / `ValidationGroup` - Validation settings
- `Attributes` - Custom HTML attributes
- `render()` - Renders the hotspot area

## See Also

- [TCircleHotSpot](./TCircleHotSpot.md)
- [TImageMap](./TImageMap.md)
