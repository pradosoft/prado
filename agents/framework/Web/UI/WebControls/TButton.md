# Web/UI/WebControls/TButton

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TButton`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TButton.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TButton creates a clickable button control on the page. It is primarily used for submitting data to a page and raises server-side events when clicked.

## Key Features
- **Server Events**: Raises OnClick and OnCommand events on button click
- **Form Validation**: Supports form validation with CausesValidation and ValidationGroup properties
- **Button Types**: Supports Submit, Button, and Reset button types
- **Client-Server Integration**: Works with client-side JavaScript and postback mechanisms  
- **Event Bubbling**: OnCommand events are bubbled up to ancestor controls
- **Command Parameters**: Supports command name and parameter for distinguishing multiple buttons

## Core Properties
- `ButtonTag` (TButtonTag): Tag name of button (Input or Button)
- `ButtonType` (TButtonType): Type of button (Submit, Button, or Reset)
- `CommandName` (string): Command name for command events
- `CommandParameter` (string): Command parameter for command events
- `CausesValidation` (bool): Whether validation occurs on click
- `ValidationGroup` (string): Validation group for restricting validation
- `Text` (string): Button caption text
- `EnableClientScript` (bool): Whether JavaScript is rendered for button
- `IsDefaultButton` (bool): Set by a [TPanel](./TPanel.md) to make this the panel's default button

## Core Events
- `OnClick`: Raised when button is clicked
- `OnCommand`: Raised when button is clicked, bubbled to parent controls with command parameters

## Button Types
- **Submit**: Standard form submission with browser default behavior
- **Button**: Button-type that can trigger postback when event handler is attached or validation group is non-empty
- **Reset**: Resets form fields when clicked (clears input values)

## Button Tags
- **Input**: Renders as `<input type="button|submit|reset">` with `Text` in the value attribute (default)
- **Button**: Renders as `<button type="button|submit|reset">` with `Text` as the body content (HTML5)

## Validation Integration
- `CausesValidation` (boolean): Controls whether clicking button triggers validation
- `ValidationGroup` (string): Restricts validation to specific group of validators
- On postback, `raisePostBackEvent()` runs the validators before raising `OnClick` and `OnCommand`

## Core Methods

### Event Handling
- `raisePostBackEvent($param)`: Validates, then raises OnClick and OnCommand
- `onClick($param)`: Raises OnClick event
- `onCommand($param)`: Raises OnCommand event and bubbles it to the ancestors
- `getCommandName()`: Gets command name for command event
- `setCommandName($value)`: Sets command name for command event
- `getCommandParameter()`: Gets command parameter for command event
- `setCommandParameter($value)`: Sets command parameter for command event

### Button Configuration
- `getButtonTag()`: Gets button tag type (Input or Button)
- `setButtonTag($value)`: Sets button tag type
- `getButtonType()`: Gets button type (Submit, Button, or Reset)
- `setButtonType($value)`: Sets button type
- `getCausesValidation()`: Gets whether validation occurs on click
- `setCausesValidation($value)`: Sets whether validation occurs on click
- `getValidationGroup()`: Gets validation group
- `setValidationGroup($value)`: Sets validation group
- `getEnableClientScript()`: Gets whether JavaScript is rendered
- `setEnableClientScript($value)`: Sets whether JavaScript is rendered

### Rendering
- `getTagName()`: Gets HTML tag name for button, the lowercase of `ButtonTag`
- `addAttributesToRender($writer)`: Adds button-specific attributes to HTML writer
- `renderContents($writer)`: Renders the body content, for the Button tag only
- `renderClientControlScript($writer)`: Registers the postback javascript for the button
- `getClientClassName()`: Returns `Prado.WebUI.TButton`

### Postback Behavior
- `canCauseValidation()`: True when `CausesValidation` is set and the validation group holds a validator
- `needPostBackScript()`: True when javascript is needed to post back
- `getPostBackOptions()`: Postback options passed to the client script manager

## Usage Example
```php
// Create button
$button = new TButton();
$button->ID = "submitButton";
$button->Text = "Submit";

// Set command properties
$button->setCommandName("Save");
$button->setCommandParameter("Document");

// Set validation
$button->setCausesValidation(true);
$button->setValidationGroup("FormValidation");

// Handle events
$button->attachEventHandler('OnClick', function($sender, $param) {
    // Handle button click
});
```

## See Also

- [TLinkButton](./TLinkButton.md)
- [TButtonType](./TButtonType.md) / [TButtonTag](./TButtonTag.md)
- [TWebControl](./TWebControl.md)