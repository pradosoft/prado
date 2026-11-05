# Web/UI/WebControls/TCheckBox

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TCheckBox`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TCheckBox.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TCheckBox displays a check box on the page, with a caption, validation, AutoPostBack, and an indeterminate (mixed) display state.

## Key Features
- **Check Box States**: Checked or unchecked; `Indeterminate` displays the mixed state (@since 4.4.0)
- **Validation Integration**: Works with PRADO validation framework
- **Postback Handling**: Supports AutoPostBack with validation
- **Event Handling**: Raises OnCheckedChanged event for state changes
- **Display Customization**: Caption positioning and appearance control
- **Accessibility**: Support for labels and screen readers

## Core Properties
- `Checked` (bool): Whether check box is checked
- `Text` (string): Text caption displayed beside check box, rendered as HTML unless `Encode` is true
- `Encode` (bool): Whether `Text` is HTML-encoded when rendered, default false; TRadioButton inherits it (4.4.0)
- `TextAlign` (string): Text alignment (Left or Right) relative to check box
- `AutoPostBack` (bool): Whether postback occurs on check box change
- `CausesValidation` (bool): Whether validation occurs on check box change
- `ValidationGroup` (string): Validation group for restriction
- `Value` (string): Value associated with check box state
- `EnableClientScript` (bool): Whether JavaScript is rendered
- `Indeterminate` (bool, @since 4.4.0): Shows the mixed state. HTML has no attribute, so `registerIndeterminateScript()` registers an end script (`prado:indeterminate:<ClientID>`) that sets the DOM property; the box still posts `Checked`. A postback that changes `Checked` clears it.
- `Switch` (bool, @since 4.4.0): Renders `switch` (Safari's native switch) and `role="switch"` on the input; a switch has no mixed state, so it ignores `Indeterminate`. TRadioButton renders its own input and has neither.

## Core Events
- `OnCheckedChanged`: Raised when check box state changes between posts
- `OnLoadPostData`: Raised when postback data is loaded

## Check Box States
- **False**: Unchecked state (default)
- **True**: Checked state
- **Indeterminate**: a display state over either; a click clears it in the browser

## Core Methods

### State Management
- `getChecked()`: Gets check box state
- `setChecked($value)`: Sets check box state
- `getIndeterminate()` / `setIndeterminate($value)`: Mixed display state
- `getSwitch()` / `setSwitch($value)`: Switch presentation

### Text and Display
- `getText()`: Gets text caption
- `setText($value)`: Sets text caption
- `getTextAlign()`: Gets text alignment (Left or Right)
- `setTextAlign($value)`: Sets text alignment
- `getValue()`: Gets associated value
- `setValue($value)`: Sets associated value

### Validation
- `getIsValid()`: Gets validation status
- `setIsValid($value)`: Sets validation status
- `getCausesValidation()`: Gets validation trigger status
- `setCausesValidation($value)`: Sets validation trigger status
- `getValidationGroup()`: Gets validation group
- `setValidationGroup($value)`: Sets validation group

### Postback Handling
- `loadPostData($key, $values)`: Loads postback data for check box
- `raisePostDataChangedEvent()`: Raises post data changed event
- `getAutoPostBack()`: Gets auto-postback status
- `setAutoPostBack($value)`: Sets auto-postback status
- `getHasChanged()`: Checks if check box state has changed
- `raisePostBackEvent()`: Raises postback event

### Client Script
- `getEnableClientScript()`: Gets JavaScript rendering status
- `setEnableClientScript($value)`: Sets JavaScript rendering status

### Rendering
- `getTagName()`: Gets HTML tag name ('input')
- `addAttributesToRender($writer)`: Adds input attributes to HTML writer
- `renderBeginTag($writer)`: Renders opening input tag
- `renderEndTag($writer)`: Renders closing input tag (not applicable for input tags)
- `renderContents($writer)`: Renders text content (if applicable)
- `render($writer)`: Renders complete HTML input tag

### HTML Generation
- Renders as `<input type="checkbox">` with appropriate attributes
- Supports both checked and unchecked states
- Includes value attributes for form submission
- Supports label association for accessibility

## Usage Example
```php
// Create check box
$checkBox = new TCheckBox();
$checkBox->ID = "agreeCheckBox";
$checkBox->Text = "I agree to the terms and conditions";
$checkBox->setChecked(true);

// Set validation
$checkBox->setCausesValidation(true);
$checkBox->setValidationGroup("Terms");

// Set auto-postback
$checkBox->setAutoPostBack(true);

// Handle events
$checkBox->onCheckedChanged = function($sender, $param) {
    if ($sender->getChecked()) {
        // User agreed
    } else {
        // User disagreed
    }
};

// Render
$writer = new [THtmlWriter](../THtmlWriter.md)();
$checkBox->render($writer);
```