# Web/UI/WebControls/TContentPlaceHolder

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TContentPlaceHolder`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TContentPlaceHolder.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TContentPlaceHolder reserves a place on a template where a TContent control can inject itself and its children. It works with TContent to implement a template decoration pattern for PRADO templated controls.

## Key Features
- **Content Placeholder**: Reserves location for content injection
- **Template Inheritance**: Supports master-content template structure
- **ID Matching**: Controls matched by ID with corresponding TContent controls
- **Template Registration**: Automatically registers with template owner
- **Decoration Pattern**: Implements master-content template pattern

## Template Inheritance Pattern
TContentPlaceHolder and TContent work together as a decoration pattern:
- Master control defines template with TContentPlaceHolder controls
- Content control defines template with TContent controls  
- TContent controls replace corresponding TContentPlaceHolder controls
- Matching is done by ID property

## Core Properties
- `ID` (string): Unique identifier for placeholder (required)
- `TemplateControl` ([TTemplateControl](./TTemplateControl.md)): Template control that owns this placeholder
- `Parent` (TControl): Parent control in hierarchy

## Core Methods

### Template Management
- `createdOnTemplate($parent)`: Called after control is instantiated on template
- `registerContentPlaceHolder($id, $placeholder)`: Registers placeholder with template owner
- `getTemplateControl()`: Gets template control that owns this placeholder
- `setTemplateControl($value)`: Sets template control that owns this placeholder

### Content Matching
- `getID()`: Gets placeholder ID for matching with content
- `setID($value)`: Sets placeholder ID
- `getContentID()`: Gets corresponding content ID
- `matchWithContent()`: Matches placeholder with appropriate content

### Lifecycle
- `onInit()`: Initializes placeholder control
- `onLoad()`: Loads placeholder control
- `onPreRender()`: Pre-renders placeholder control
- `onUnload()`: Unloads placeholder control

### Rendering
- `renderControl($writer)`: Renders placeholder control
- `render($writer)`: Renders placeholder with child controls
- `renderChildren($writer)`: Renders child controls

## Usage Example
```php
// Master template (MasterPage.tpl)
// <com:TContentPlaceHolder ID="Header" />
// <com:TContentPlaceHolder ID="Body" />

// Content template (ContentPage.tpl)  
// <com:TContent ID="Header">
//   <h1>Welcome</h1>
// </com:TContent>
// <com:TContent ID="Body">
//   <p>Main content here</p>
// </com:TContent>

// The content will be injected into master template placeholders
```