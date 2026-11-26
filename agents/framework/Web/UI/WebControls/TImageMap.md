# Web/UI/WebControls/TImageMap

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TImageMap`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TImageMap.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TImageMap displays an image with clickable hotspot regions. Hotspots can navigate to URLs or trigger postbacks.

## Key Properties/Methods

- `HotSpots` - Collection of THotSpot objects
- `HotSpotMode` - Default behavior for hotspots
- `Target` - Default target window for navigation
- `onClick()` - Event raised when hotspot clicked
- `raisePostBackEvent()` - Handles postback from hotspots
- `addParsedObject()` - Adds hotspot objects from template

## Rendering

- The `<img>` carries the ClientID as its `id` and `usemap="#ImageMap{ClientID}"`; the `<map>` follows it.
- `getRenderHotSpot()` (@since 4.4.0) renders a copy of each hot spot: its own HotSpotMode and Target, else the image map's; NotSet on both is Navigate. The hot spots in `HotSpots` are not changed.
- A PostBack area gets the id `{ClientID}_{index}` and registers a `Prado.WebUI.TImageMap` postback script.
- In a disabled image map, or inside a disabled parent, every area renders as Inactive: no `href`, no postback script.

## See Also

- [TImage](./TImage.md)
- [THotSpot](./THotSpot.md)
