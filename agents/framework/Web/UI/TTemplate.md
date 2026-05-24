# Web/UI/TTemplate

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [UI](./INDEX.md) / **`TTemplate`**

## Class Info
**Location:** `framework/Web/UI/TTemplate.php`
**Namespace:** `Prado\Web\UI`

## Overview
TTemplate implements PRADO template parsing logic. It represents a parsed PRADO control template and can instantiate the template as child controls of a specified control.

## Key Features
- **Template Parsing**: Parses PRADO templates with special tags and syntax
- **Component Instantiation**: Creates and initializes components specified in templates
- **Template Directives**: Supports template directive configuration
- **Expression Handling**: Processes PHP expressions and statements within templates
- **Subproperty Support**: Supports group subproperty tags for configuring nested properties
- **Comment Support**: Handles both HTML and template-specific comments
- **Attribute Name Transformation** (@since 4.3.3): Hyphens in attribute names are converted to underscores for PHP method lookup (e.g., `data-toggle="modal"` → `setData_toggle("modal")`). Property names are matched case-insensitively; the original case is preserved when calling setters.
- **TSkinTemplate support**: `TTheme` calls `getTemplateByFileName()` with `TSkinTemplate::class` as `$tplClass` to disable attribute validation during skin parsing.

## Template Syntax

### Component Tags
- Format: `<com:ComponentType attributes>`
- Component type is full class name (e.g., `com:TButton`)
- Attributes are treated as property initial values, event handlers, or regular attributes

### Property Tags  
- Format: `<prop:AttributeName>`
- Used to set large block of attribute values for properties

### Group Subproperty Tags
- Format: `<prop:MainProperty SubProperty1="Value1" SubProperty2="Value2" />`
- Configure subproperties of a common property

### Directive
- Format: `<%@ property name-value pairs %>`
- Specifies property values for the template owner

### Expressions
- `<%= PHP expression %>` - Outputs PHP expression result
- `<%% PHP statements %>` - Executes PHP statements

### Comments
- `<!-- comments -->` - Regular HTML comments treated as text strings
- `<!-- comments --!>` - Template comments stripped out

## Core Properties
- `Content` (string): Template content to be parsed
- `TemplateFile` (string): Template file path (if available)
- `ContextPath` (string): Context path for template resolution
- `Directive` (array): List of directive settings
- `Template` (array): List of component tags and strings

## Core Methods

### Template Processing
- `instantiateIn($control)`: Instantiates template as child controls of specified control
- `parseTemplate()`: Parses template content into component structure
- `parseComponentTag()`: Parses component tags and creates component instances  
- `parseDirective()`: Parses directive statements
- `parsePropertyTag()`: Parses property tags
- `parseGroupSubpropertyTag()`: Parses group subproperty tags
- `parseExpression()`: Processes PHP expressions and statements

### Expression Evaluation
- `evaluateExpression($expression)`: Evaluates PHP expression within template context
- `evaluateDynamicContent()`: Evaluates dynamic content in template
- `evaluateAttribute()`: Evaluates attribute values with expressions

### Template Management
- `getContent()`: Gets template content
- `setContent()`: Sets template content
- `getTemplateFile()`: Gets template file path
- `setTemplateFile()`: Sets template file path
- `getContextPath()`: Gets context path
- `setContextPath()`: Sets context path

### Parsing Helpers
- `getLineNo()`: Gets current line number during parsing
- `getStartingLine()`: Gets starting line number for parsing
- `setStartingLine()`: Sets starting line number
- `isDatabindProperty()`: Checks if property is data-bound
- `isExpressionProperty()`: Checks if property is expression-based

## Template Components
The parsed template is stored as a list of components and strings that get converted to actual controls during instantiation. Each component can have:
- Properties configured via attributes
- Events attached via attributes
- Child components defined within the tag
- Subproperties configured through group syntax

## New in 4.3.3 (Protected Parsing Helpers)

These protected methods are part of the internal parsing pipeline. Subclasses may override them for custom parsing behavior:

- `attributeToMethodName(string $propName): string` — Converts attribute names with dashes to underscores for PHP method name lookup.
- `packTemplate(int $parentIndex, string|array|TTemplate $type, ?array $attributes = null): array` — Creates a template item array with `TPL_PARENT_INDEX`, `TPL_TYPE`, and optionally `TPL_PROPS`.
- `packProperty($type, string $propName, mixed $value): array` — Returns property info with `PROP_TYPE`, `PROP_NAME`, `PROP_VALUE` keys.
- `propertyExpressionCharToType(string $strValue, string $propName): int` — Converts a template expression character (`=`, `%`, `#`, `$`) to a `CONFIG_*` constant.
- `parseExpression(string $tplType, string $literal): array` — Parses a `<%= %>` / `<%% %>` / `<%# %>` expression literal into `[TCompositeLiteral::TYPE_*, expression]`.
- `optimizeTemplate(): array` — Combines consecutive strings/expressions/statements/bindings with the same parent into `TCompositeLiteral` objects for rendering efficiency.

## Usage Example
```php
// Create template
$template = new TTemplate('<com:TButton ID="button1" Text="Click Me" />', '/path/to', '/path/to/file.tpl');

// Instantiate in control
$control = new TPanel();
$template->instantiateIn($control);
// button1 is now a child of control
```

(End of file - total 98 lines)
