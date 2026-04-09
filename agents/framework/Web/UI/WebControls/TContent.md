# TContent Class

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md) > [TContent](./TContent.md)

## Overview
TContent specifies a block of content on a control's template that will be injected at a specific location in a master control's template. It works with TContentPlaceHolder to implement a template decoration pattern.

## Key Features
- **Content Injection**: Replaces corresponding TContentPlaceHolder controls in master templates
- **Template Inheritance**: Supports master-content template structure
- **ID Matching**: Content is matched to placeholders by ID
- **Naming Container**: Implements INamingContainer interface
- **Template Registration**: Automatically registers with template owner

## Template Inheritance Pattern
TContent and TContentPlaceHolder work together as a decoration pattern:
- Master control defines template with TContentPlaceHolder controls
- Content control defines template with TContent controls  
- TContent controls replace corresponding TContentPlaceHolder controls
- Matching is done by ID property

## Core Properties
- `ID` (string): Unique identifier for content block (required)
- `TemplateControl` ([TTemplateControl](./TTemplateControl.md)): Template control that owns this content
- `Parent` (TControl): Parent control in hierarchy

## Core Methods

### Template Management
- `createdOnTemplate($parent)`: Called after control is instantiated on template
- `registerContent($id, $content)`: Registers content with template owner
- `getTemplateControl()`: Gets template control that owns this content
- `setTemplateControl($value)`: Sets template control that owns this content

### Content Matching
- `getID()`: Gets content ID for matching with placeholder
- `setID($value)`: Sets content ID
- `getContentPlaceHolderID()`: Gets corresponding placeholder ID
- `matchWithPlaceholder()`: Matches content with appropriate placeholder

### Lifecycle
- `onInit()`: Initializes content control
- `onLoad()`: Loads content control
- `onPreRender()`: Pre-renders content control
- `onUnload()`: Unloads content control

### Rendering
- `renderControl($writer)`: Renders content control
- `render($writer)`: Renders content with child controls
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