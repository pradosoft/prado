# TClientScriptManager

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [TClientScriptManager](./TClientScriptManager.md)

**Location:** `framework/Web/UI/TClientScriptManager.php`
**Namespace:** `Prado\Web\UI`

## Overview

TClientScriptManager manages JavaScript and CSS stylesheets for a page. It supports registering PRADO script libraries, custom scripts, stylesheets, hidden fields, callback/postback controls, default buttons, and focus controls. Scripts can be rendered in the head, at the beginning of the form, or at the end of the form.

## Key Properties/Methods

- `RequiresHead` - Whether THead is required for CSS/JS rendering
- `registerPradoScript($name)` - Registers a PRADO JavaScript library by name
- `registerPradoStyle($name)` - Registers a PRADO CSS library by name
- `registerScriptFile($key, $url)` - Registers a JavaScript file within the form
- `registerHeadScriptFile($key, $url, $async)` - Registers a JavaScript file in the page head
- `registerStyleSheetFile($key, $url, $media)` - Registers a CSS file
- `registerBeginScript($key, $script)` - Registers JavaScript at the beginning of the form
- `registerEndScript($key, $script)` - Registers JavaScript at the end of the form
- `registerHiddenField($name, $value)` - Registers a hidden field
- `registerDefaultButton($panel, $button)` - Registers a default button for a panel
- `registerFocusControl($target)` - Registers a control to receive default focus
- `getCallbackReference($callbackHandler, $options)` - Returns JavaScript for callback request

## See Also

- [TPage](./TPage.md)
- [TJavaScript](../Javascript/TJavaScript.md)
- [ICallbackEventHandler](./ActiveControls/ICallbackEventHandler.md)

(End of file - total 29 lines)
