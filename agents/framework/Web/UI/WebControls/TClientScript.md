# Web/UI/WebControls/TClientScript

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TClientScript`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TClientScript.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TClientScript allows importing PRADO client scripts and custom JavaScript from templates. Multiple scripts can be specified via the PradoScripts property.

## Key Properties/Methods

- `PradoScripts` - Comma-delimited list of JS libraries (prado, effects, ajax, validator, etc.)
- `ScriptUrl` - Custom JavaScript file URL
- `FlushScriptFiles` - Whether to flush script files before rendering script block
- `onPreRender()` - Registers requested client scripts

## See Also

- TClientScriptManager
