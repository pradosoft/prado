# TButton Class

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md) > [TButton](./TButton.md)

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
- `ButtonTag` (TButtonTag): Tag name of button (Input, Button, or Reset)
- `ButtonType` (TButtonType): Type of button (Submit, Button, or Reset)
- `CommandName` (string): Command name for command events
- `CommandParameter` (string): Command parameter for command events
- `CausesValidation` (bool): Whether validation occurs on click
- `ValidationGroup` (string): Validation group for restricting validation
- `Text` (string): Button caption text
- `EnableClientScript` (bool): Whether JavaScript is rendered for button
- `UseSubmitBehavior` (bool): Whether to use submit behavior for postback

## Core Events
- `OnClick`: Raised when button is clicked
- `OnCommand`: Raised when button is clicked, bubbled to parent controls with command parameters

## Button Types
- **Submit**: Standard form submission with browser default behavior
- **Button**: Button-type that can trigger postback when event handler is attached or validation group is non-empty
- **Reset**: Resets form fields when clicked (clears input values)

## Button Tags
- **Input**: Renders as `<input type="button/submit/reset">` (default)
- **Button**: Renders as `<button type="button/submit/reset">` (HTML5)
- **Reset**: Renders as `<input type="reset">`

## Validation Integration
- `CausesValidation` (boolean): Controls whether clicking button triggers validation
- `ValidationGroup` (string): Restricts validation to specific group of validators
- Validation occurs before postback if successful

## Core Methods

### Event Handling
- `raisePostBackEvent($sender, $param)`: Raises postback event on button click
- `onClick($sender, $param)`: Raises OnClick event
- `onCommand($sender, $param)`: Raises OnCommand event with command parameters
- `getCommandName()`: Gets command name for command event
- `setCommandName($value)`: Sets command name for command event
- `getCommandParameter()`: Gets command parameter for command event
- `setCommandParameter($value)`: Sets command parameter for command event

### Button Configuration
- `getButtonTag()`: Gets button tag type (Input, Button, or Reset)  
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
- `getTagName()`: Gets HTML tag name for button
- `addAttributesToRender($writer)`: Adds button-specific attributes to HTML writer
- `renderBeginTag($writer)`: Renders opening button tag  
- `renderEndTag($writer)`: Renders closing button tag
- `renderContents($writer)`: Renders button text content
- `render($writer)`: Renders complete button HTML output

### Validation
- `validate()`: Validates button and associated controls
- `getHasValidation()`: Checks if button has validation

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
$button->onClick = function($sender, $param) {
    // Handle button click
};

// Render
$writer = new [THtmlWriter](./THtmlWriter.md)();
$button->render($writer);
```