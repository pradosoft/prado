# Web/UI/WebControls/TCheckBox

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TCheckBox`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TCheckBox.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TCheckBox displays a check box on the page. It supports both single and tri-state check boxes with various configuration options for display, validation, and user experience.

## Key Features
- **Check Box States**: Supports checked, unchecked, and indeterminate (tri-state) modes
- **Validation Integration**: Works with PRADO validation framework
- **Postback Handling**: Supports AutoPostBack with validation
- **Event Handling**: Raises OnCheckedChanged event for state changes
- **Display Customization**: Caption positioning and appearance control
- **Accessibility**: Support for labels and screen readers

## Core Properties
- `Checked` (bool): Whether check box is checked (true/false or tri-state)
- `Text` (string): Text caption displayed beside check box
- `TextAlign` (string): Text alignment (Left or Right) relative to check box
- `AutoPostBack` (bool): Whether postback occurs on check box change
- `CausesValidation` (bool): Whether validation occurs on check box change
- `ValidationGroup` (string): Validation group for restriction
- `Value` (string): Value associated with check box state
- `CheckedValue` (string): Value when check box is checked
- `UncheckedValue` (string): Value when check box is unchecked
- `EnableClientScript` (bool): Whether JavaScript is rendered
- `TriState` (bool): Whether check box supports tri-state (checked/unchecked/indeterminate)

## Core Events
- `OnCheckedChanged`: Raised when check box state changes between posts
- `OnLoadPostData`: Raised when postback data is loaded

## Check Box States
- **False**: Unchecked state (default)
- **True**: Checked state
- **Null**: Indeterminate state (tri-state mode)

## Core Methods

### State Management
- `getChecked()`: Gets check box state (true, false, or null)
- `setChecked($value)`: Sets check box state
- `isChecked()`: Checks if check box is checked
- `isUnchecked()`: Checks if check box is unchecked
- `isIndeterminate()`: Checks if check box is in indeterminate state
- `getTriState()`: Gets tri-state support status
- `setTriState($value)`: Sets tri-state support status

### Text and Display
- `getText()`: Gets text caption
- `setText($value)`: Sets text caption
- `getTextAlign()`: Gets text alignment (Left or Right)
- `setTextAlign($value)`: Sets text alignment
- `getValue()`: Gets associated value
- `setValue($value)`: Sets associated value
- `getCheckedValue()`: Gets value when checked
- `setCheckedValue($value)`: Sets value when checked
- `getUncheckedValue()`: Gets value when unchecked
- `setUncheckedValue($value)`: Sets value when unchecked

### Validation
- `validate()`: Validates check box state
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
$writer = new [THtmlWriter](./THtmlWriter.md)();
$checkBox->render($writer);
```