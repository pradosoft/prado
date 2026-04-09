# TPage Class

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [TPage](./TPage.md)

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
- `Validators` ([TList](../Collections/TList.md)): Collection of registered validators
- `Theme` ([TTheme](./TTheme.md)): Page theme for styling
- `StyleSheet` ([TTheme](./TTheme.md)): Page stylesheet theme
- `ClientScript` ([TClientScriptManager](./TClientScriptManager.md)): Manages client-side scripts
- `PagePath` (string): Path to the current page
- `EnableStateValidation` (bool): Whether page state should be HMAC validated
- `EnableStateEncryption` (bool): Whether page state should be encrypted  
- `EnableStateCompression` (bool): Whether page state should be compressed
- `EnableJavaScript` (bool): Whether client supports JavaScript
- `Focus` (string|[TControl](./TControl.md)): Control or element to be focused on page load

## Core Methods

### Page Lifecycle
- `initRecursive()`: Initializes page and child controls
- `loadRecursive()`: Loads page and child controls  
- `preRenderRecursive()`: Pre-renders page and child controls
- `unloadRecursive()`: Unloads page and child controls
- `saveState()`: Saves page state to persister

### Form Management
- `getForm()`: Gets form instance
- `setForm()`: Sets form instance
- `setFocus($control)`: Sets focus to a control
- `getFocus()`: Gets focus control or element ID
- `renderForm()`: Renders HTML form

### Validation
- `getValidators()`: Gets list of registered validators
- `registerValidator($validator)`: Registers a validator
- `unregisterValidator($validator)`: Unregisters a validator
- `validate()`: Performs page validation
- `getIsValid()`: Checks if page is valid

### State Management
- `getPageStatePersister()`: Gets page state persister instance
- `loadPageState()`: Loads page state from request
- `savePageState()`: Saves page state to response
- `getPageState()`: Gets page state data
- `getControlState()`: Gets control state data for page
- `setStateValidation()`: Sets page state validation
- `getStateValidation()`: Gets page state validation
- `setViewState()`: Sets viewstate data for page
- `getViewState()`: Gets viewstate data for page

### Event Handling
- `raisePostBackEvent($sender, $param)`: Raises postback event
- `onLoadPostData()`: Handles loading postback data
- `onLoad()`: Raises OnLoad event
- `onPreRender()`: Raises OnPreRender event
- `onUnload()`: Raises OnUnload event

### Theme Management
- `getTheme()`: Gets page theme
- `setTheme($value)`: Sets page theme
- `getStyleSheet()`: Gets page stylesheet
- `setStyleSheet($value)`: Sets page stylesheet
- `applyControlSkin()`: Applies skin to controls

### Client Script Management
- `getClientScript()`: Gets client script manager
- `registerClientScript()`: Registers client script
- `registerStyleSheet()`: Registers CSS stylesheet
- `addStyleSheet()`: Adds CSS stylesheet
- `getJavaScript()`: Gets JavaScript object

### Postback & Data Handling
- `getPostBackEventTarget()`: Gets control that raised postback event
- `setPostBackEventTarget()`: Sets control that raised postback event
- `getPostBackEventParameter()`: Gets postback event parameter
- `setPostBackEventParameter()`: Sets postback event parameter
- `getPostBackData()`: Gets postback data
- `setPostBackData()`: Sets postback data
- `getHasChanged()`: Checks if page data has changed

### Rendering
- `render()`: Renders page to HTML output
- `renderChildren()`: Renders page child controls
- `renderFormContent()`: Renders form content
- `renderHead()`: Renders page head element
- `renderClientScript()`: Renders client-side scripts
- `renderPostData()`: Renders postback data fields

## Constants
- `FIELD_POSTBACK_TARGET`: System postback target field name
- `FIELD_POSTBACK_PARAMETER`: System postback parameter field name
- `FIELD_LASTFOCUS`: System last focus field name
- `FIELD_PAGESTATE`: System page state field name
- `FIELD_CALLBACK_TARGET`: System callback target field name
- `FIELD_CALLBACK_PARAMETER`: System callback parameter field name

## Page Lifecycle Stages
1. **Init**: Page and control initialization
2. **Load**: Loading of page state and postback data
3. **PreRender**: Page preparation for rendering
4. **Render**: Actual HTML output generation
5. **Unload**: Page cleanup

## Validation Flow
1. Validators are registered during Init lifecycle
2. Validation is triggered in PreRender lifecycle
3. Page is validated in Validate method
4. Validation results are stored in IsValid property

## Usage Example
```php
// Create page with form
$page = new TPage();
$form = new TForm();
$page->setForm($form);

// Register validator
$validator = new TRequiredFieldValidator();
$page->registerValidator($validator);

// Process page
$page->initRecursive();
$page->loadRecursive();
if ($page->validate()) {
    $page->preRenderRecursive();
    $page->render($writer);
}
```

(End of file - total 140 lines)
