# TTemplateControl Class

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [TTemplateControl](./TTemplateControl.md)

## Overview
TTemplateControl is the base class for all controls that use templates in PRADO framework. It extends [TCompositeControl](./TCompositeControl.md) and provides template loading, management, and instantiation capabilities.

## Key Features
- **Template Loading**: Automatically loads templates from .tpl files
- **Template Inheritance**: Supports master-content template structure
- **Template Caching**: Caches parsed templates for performance
- **Template Management**: Handles local and shared template instances
- **Control Composition**: Works with [TContent](./WebControls/TContent.md) and [TContentPlaceHolder](./WebControls/TContentPlaceHolder.md) controls

## Template Structure
- Templates are stored in files with .tpl extension
- Template file location: Same directory as control class file
- Template naming convention: Same name as class file, different extension (.tpl)
- Supports master-content template inheritance

## Core Properties
- `Template` ([ITemplate](./ITemplate.md)): Gets the parsed template associated with this control
- `Master` ([TTemplateControl](./TTemplateControl.md)): Gets master control if this control uses a master template
- `MasterClass` (string): Gets master control class name
- `Contents` (array): Gets list of [TContent](./WebControls/TContent.md) controls
- `Placeholders` (array): Gets list of [TContentPlaceHolder](./WebControls/TContentPlaceHolder.md) controls
- `IsSourceTemplateControl` (bool): Whether control loads template from external storage

## Core Methods

### Template Loading
- `getTemplate()`: Gets parsed template for control instance
- `setTemplate($value)`: Sets parsed template for control
- `loadTemplate()`: Loads and parses template file
- `getTemplateFile()`: Gets template file path
- `setTemplateFile($value)`: Sets template file path
- `loadTemplateFromFile()`: Loads template from file
- `loadTemplateFromString()`: Loads template from string content

### Template Management
- `getMaster()`: Gets master control for template inheritance
- `setMaster($value)`: Sets master control 
- `getMasterClass()`: Gets master control class name
- `setMasterClass($value)`: Sets master control class name
- `getContents()`: Gets list of [TContent](./WebControls/TContent.md) controls
- `getPlaceholders()`: Gets list of [TContentPlaceHolder](./WebControls/TContentPlaceHolder.md) controls
- `getIsSourceTemplateControl()`: Checks if control is source template control

### Template Inheritance
- `applyMasterTemplate()`: Applies master template to current template
- `instantiateIn($control)`: Instantiates control within parent control
- `createChildControls()`: Creates child controls from template
- `applyTemplate()`: Applies template to control

### Template Caching
- `getTemplateCacheKey()`: Gets cache key for template
- `getCachedTemplate()`: Gets cached template instance
- `setCachedTemplate()`: Sets cached template instance
- `clearTemplateCache()`: Clears template cache

## Template Structure Concepts

### Master-Content Template
- Master templates define the overall structure
- Content templates provide specific content for placeholders
- Controls use [TContent](./WebControls/TContent.md) and [TContentPlaceHolder](./WebControls/TContentPlaceHolder.md) for content injection
- Inheritance hierarchy supports nested templates

### Template File Location
- Located in same directory as control class file
- File extension: `.tpl`
- File name: Same as class file name
- Example: `TButton.php` has `TButton.tpl`

### Template Parsing
- Templates are parsed using [TTemplate](./TTemplate.md) class
- Supports component tags with `com:` prefix
- Supports property tags with `prop:` prefix  
- Supports expression tags with `<%= %>` and `<%% %>`
- Supports comments with `<!-- -->` and `<!-- --!>`

## Usage Example
```php
// Create control with template
$control = new TTemplateControl();
$template = $control->getTemplate();

// Load template
$template->setContent('<com:TButton Text="Click Me" />');

// Instantiate control
$parent = new TPanel();
$control->instantiateIn($parent);

// Apply master template
$control->setMasterClass("MasterTemplate");
$control->applyMasterTemplate();
```

(End of file - total 96 lines)
