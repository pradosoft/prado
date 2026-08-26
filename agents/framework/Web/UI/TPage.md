# Web/UI/TPage

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [UI](./INDEX.md) / **`TPage`**

## Class Info
**Location:** `framework/Web/UI/TPage.php`
**Namespace:** `Prado\Web\UI`

## Overview
TPage is the base class for all web pages in PRADO framework. It extends [TTemplateControl](./TTemplateControl.md) and implements complete page functionality including form handling, validation, state management, client script management, and theming support.

## Key Features
- **Page Lifecycle Management**: Full implementation of page lifecycle (Init, Load, PreRender, Render, Unload)
- **Form Handling**: Integrated form processing with Postback event management
- **Validation System**: Built-in validation framework with validator collection
- **State Management**: Page state persistence with HMAC validation, encryption, and compression
- **Theming Support**: Theme and stylesheet application
- **Client Script Management**: JavaScript and CSS management for dynamic page content
- **Control State Tracking**: Monitoring of control state changes and postback data

## Core Properties
- `Form` ([TForm](./TForm.md)): Main form instance for the page
- `Head` ([THead](./WebControls/THead.md)): Page header element
- `Validators` ([TList](../../Collections/TList.md)): Collection of registered validators
- `Theme` ([TTheme](./TTheme.md)): Page theme for styling
- `StyleSheetTheme` ([TTheme](./TTheme.md)): Page stylesheet theme
- `ClientScript` ([TClientScriptManager](./TClientScriptManager.md)): Manages client-side scripts
- `Title` (string): Page title, held until a THead is set on the page
- `PagePath` (string): Path to the current page
- `IsPostBack` (bool): Whether the request is a postback (read-only)
- `IsCallback` (bool): Whether the request is a callback (read-only)
- `StatePersisterClass` / `StatePersister` ([IPageStatePersister](./IPageStatePersister.md)): Where page state is stored
- `EnableStateValidation` (bool): Whether page state should be HMAC validated
- `EnableStateEncryption` (bool): Whether page state should be encrypted
- `EnableStateCompression` (bool): Whether page state should be compressed
- `EnableStateIGBinary` (bool): Whether page state uses the igbinary serializer when available
- `ClientSupportsJavaScript` (bool): Whether client supports JavaScript
- `Focus` (string|[TControl](./TControl.md)): Control or element to be focused on page load (write-only)
- `CallbackClient` ([TCallbackClientScript](./ActiveControls/TCallbackClientScript.md)): Client-side commands for a callback response

## Core Methods

### Page Lifecycle
- `run($writer)`: Entry point called by [TPageService](../Services/TPageService.md)
- `processNormalRequest($writer)`, `processPostBackRequest($writer)`, `processCallbackRequest($writer)`: The three life cycles
- `onPreInit()`, `onInitComplete()`, `onPreLoad()`, `onLoadComplete()`, `onPreRenderComplete()`, `onSaveStateComplete()`: Page-only life cycle events
- `flushWriter()`: Flushes the content rendered so far to the response

### Form Management
- `getForm()` / `setForm($form)`: The single [TForm](./TForm.md) of the page
- `getHead()` / `setHead($head)`: The single [THead](./WebControls/THead.md) of the page
- `setFocus($control)`: Sets focus to a control or element ID
- `ensureRenderInForm($control)`: Throws when a control renders outside the form
- `getInFormRender()`, `beginFormRender($writer)`, `endFormRender($writer)`: Form render state, invoked by TForm

### Validation
- `getValidators($validationGroup = null)`: Gets the [TList](../../Collections/TList.md) of registered validators; validators add and remove themselves
- `validate($validationGroup = null)`: Performs page validation
- `getIsValid()`: Whether the input is valid; throws when `validate()` has not run

### State Management
- `getStatePersister()`: Gets page state persister instance
- `loadPageState()` / `savePageState()`: Reads and writes the page state through the persister
- `saveState()` / `loadState()`: Adds the controls requiring post data to the page's own state
- `getClientState()` / `setClientState($state)`: State to be written to the client
- `getRequestClientState()`: State posted back from the client

### Postback & Data Handling
- `getIsPostBack()`, `getIsCallback()`: Request type
- `processPostData($postData, $beforeLoad)`: Dispatches post data to the controls
- `registerRequiresPostData($control)`: Registers a control to load post data on the next postback
- `getIsLoadingPostData()`: Whether post data is being loaded
- `getPostBackEventTarget()` / `setPostBackEventTarget($control)`: Control raising the postback event
- `getPostBackEventParameter()` / `setPostBackEventParameter($value)`: Postback event parameter
- `raiseChangedEvents()`: Raises OnPostDataChanged for the controls whose data changed
- `isSystemPostField($field)`: Whether a post field is one of the framework `FIELD_*` fields

### Callbacks
- `getCallbackClient()`: Client-side script handler for the callback response
- `getCallbackEventTarget()` / `setCallbackEventTarget($control)`: Control raising the callback event
- `getCallbackEventParameter()` / `setCallbackEventParameter($value)`: JSON decoded callback parameter

### Theme Management
- `getTheme()` / `setTheme($value)`: Gets and sets the page theme
- `getStyleSheetTheme()` / `setStyleSheetTheme($value)`: Gets and sets the page stylesheet theme
- `applyControlSkin($control)` / `applyControlStyleSheet($control)`: Applies a skin to a control

### Client Script and Caching
- `getClientScript()`: Gets the client script manager, which registers scripts, stylesheets and hidden fields
- `getCachingStack()`: Stack of the active [TOutputCache](./WebControls/TOutputCache.md) controls
- `registerCachingAction($context, $funcName, $funcParams)`: Records an action to replay when cached content is served

## Constants
- `FIELD_POSTBACK_TARGET`: System postback target field name
- `FIELD_POSTBACK_PARAMETER`: System postback parameter field name
- `FIELD_LASTFOCUS`: System last focus field name
- `FIELD_PAGESTATE`: System page state field name
- `FIELD_CALLBACK_TARGET`: System callback target field name
- `FIELD_CALLBACK_PARAMETER`: System callback parameter field name

## Page Lifecycle Stages
`onPreInit` -> `initRecursive` -> `onInitComplete` -> `loadPageState` *(POST/Callback)* -> `processPostData` *(POST/Callback)* -> `onPreLoad` -> `loadRecursive` -> `processPostData` *(POST/Callback)* -> `raiseChangedEvents` *(POST/Callback)* -> `raisePostBackEvent` *(POST-only)* -> `processCallbackEvent` *(Callback-only)* -> `onLoadComplete` -> `preRenderRecursive` -> `onPreRenderComplete` -> `savePageState` -> `onSaveStateComplete` -> `renderControl` *(GET/POST)* / `renderCallbackResponse` *(Callback-only)* -> `unloadRecursive`

## Validation Flow
1. A validator adds itself to `Validators` when it is initialized, and removes itself when it is unloaded
2. A button with `CausesValidation` calls `validate($validationGroup)` from its `raisePostBackEvent()`
3. `raisePostBackEvent()` on the page validates when no control is registered as the event target
4. `getIsValid()` reports the outcome and throws when `validate()` has not run

## Usage Example
```php
class HomePage extends TPage
{
	public function onLoad($param)
	{
		parent::onLoad($param);
		if (!$this->getIsPostBack()) {
			$this->setTitle('Home');
		}
	}

	public function buttonClicked($sender, $param)
	{
		if ($this->getIsValid()) {
			// handle the click
		}
	}
}
```

(End of file - total 140 lines)
