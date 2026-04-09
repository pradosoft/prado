# TForm Class

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [TForm](./TForm.md)

## Overview
TForm is the base class for HTML forms in PRADO framework. It extends [TControl](./TControl.md) and provides form-specific functionality including form attributes, default button handling, and integration with page lifecycle for postback processing.

## Key Features
- **Form Rendering**: Generates proper HTML form tags with all necessary attributes
- **Default Button Support**: Allows setting a default button for form submission
- **Form Method/Encoding**: Supports different form submission methods and encoding types
- **JavaScript Integration**: Works with client script manager for dynamic form handling
- **Page Integration**: Registers itself with the page during initialization

## Core Properties
- `DefaultButton` (string): ID path to the default button control that gets clicked on Enter key press
- `Method` (string): Form submission method ('post' or 'get'), defaults to 'post'
- `Enctype` (string): Encoding type for form data submission
- `Name` (string): Form name, equal to UniqueID

## Core Methods

### Form Lifecycle
- `onInit($param)`: Registers form with the page during initialization
- `render($writer)`: Renders HTML form with all required attributes and child controls
- `addAttributesToRender($writer)`: Adds form-specific attributes to HTML renderer

### Form Attributes
- `getDefaultButton()`: Gets ID path to default button control
- `setDefaultButton($value)`: Sets default button control
- `getMethod()`: Gets form submission method
- `setMethod($value)`: Sets form submission method ('post' or 'get')
- `getEnctype()`: Gets form encoding type
- `setEnctype($value)`: Sets form encoding type
- `getName()`: Gets form name (equal to UniqueID)

### Rendering
- `render($writer)`: Main render method that generates complete HTML form
- `addAttributesToRender($writer)`: Adds form-specific attributes to HTML writer
- `renderChildren($writer)`: Renders child controls within form (inherited from [TControl](./TControl.md))

## Integration with Page
- Automatically registers itself with the page during `onInit()` 
- Works with [TClientScriptManager](./TClientScriptManager.md) to render JavaScript and hidden fields
- Integrates with page's form rendering lifecycle (`beginFormRender`, `endFormRender`)
- Manages form-specific client-side scripts and validation

## Form Submission Methods
- `post`: Standard form submission (default)
- `get`: URL parameter-based submission

## Encoding Types
- `application/x-www-form-urlencoded`: Standard name/value encoding (default)
- `multipart/form-data`: For file uploads
- `text/plain`: Plain text encoding

## Usage Example
```php
// Create form
$form = new TForm();
$form->ID = "myForm";

// Set form attributes
$form->setMethod("post");
$form->setEnctype("multipart/form-data");

// Set default button
$form->setDefaultButton("submitButton");

// Add to page
$page->getControls()->add($form);

// Render form
$writer = new THtmlWriter();
$form->render($writer);
```

(End of file - total 74 lines)
