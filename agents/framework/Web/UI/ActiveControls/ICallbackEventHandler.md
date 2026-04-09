# ICallbackEventHandler

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [ActiveControls](./INDEX.md) > [ICallbackEventHandler](./ICallbackEventHandler.md)

**Location:** `framework/Web/UI/ActiveControls/ICallbackEventHandler.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview

Interface for controls that respond to callback events. Extends [IActiveControl](./IActiveControl.md). Controls implementing this interface can be callback event targets.

## Key Properties/Methods

- `raiseCallbackEvent($eventArgument)` - Raises callback event, should trigger appropriate events (OnClick, OnCommand, etc.)

## See Also

- [IActiveControl](./IActiveControl.md), [TCallbackEventParameter](./TCallbackEventParameter.md)
