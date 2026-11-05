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
- **Attribute Name Handling**: Property names are matched case-insensitively; the original case and any dashes are preserved when the property is applied. `Attributes.aria-label="Primary"` stores `aria-label`, and `Style.font-size="14px"` reaches the `font-size` CSS field.
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

### Hyphenated HTML Attributes
- Format: `<com:TNav Attributes.aria-label="Primary" Attributes.data-role="menu" />`
- A subproperty name is applied as written, so an `aria-*` or `data-*` attribute reaches the control's
  `TAttributeCollection` under the hyphenated name and renders verbatim.
- `validateAttributes()` accepts the form because the first path segment (`Attributes`) is readable.
  A bare hyphenated attribute (`<com:TNav aria-label="Primary" />`) is not a property of the control and
  throws `template_property_unknown`.

### Directive
- Format: `<%@ property name-value pairs %>`
- Specifies property values for the template owner

### Expressions
- `<%= PHP expression %>` - Outputs PHP expression result
- `<%! PHP expression %>` - Attribute-only. Applied once at the start of the control's `initRecursive()` (`TControl::initBindProperty()`), or at instantiation for non-control components; literal text in body content
- `<%% PHP statements %>` - Executes PHP statements
- `<%# PHP expression %>` - Data-bound expression, evaluated on `dataBind()`
- `<%$ ParamName %>` - Application parameter
- `<%~ path %>` - Published asset URL relative to the template context path
- `<%/ path %>` - URL relative to the application URL
- `<%[ text ]%>` - Localized text via `Prado::localize()`

### Attribute Values (`parseAttribute()`)
| Attribute value | Result |
|---|---|
| plain text | `CONFIG_VALUE` |
| exactly one `<%~ %>`, `<%$ %>`, `<%[ ]%>` or `<%/ %>` tag (surrounding whitespace allowed) | typed `CONFIG_ASSET` / `CONFIG_PARAMETER` / `CONFIG_LOCALIZATION` / `CONFIG_EXPRESSION` |
| text mixed with one or more `=`, `!`, `#`, `~`, `$`, `[`, `/` tags | `CONFIG_EXPRESSION` concatenating text and tag results; any `#` tag makes it `CONFIG_DATABIND`; otherwise any `!` tag makes it `CONFIG_INIT_EXPRESSION` |

- The typed single-tag check runs first. `ID` and `SkinID` accept `CONFIG_VALUE`, `CONFIG_PARAMETER`, `CONFIG_EXPRESSION` and `CONFIG_INIT_EXPRESSION`; other tag types throw.
- Mixed `CONFIG_EXPRESSION` values are auto-bind expressions. `TControl::autoDataBindProperties()` evaluates them in `preRenderRecursive()` with the template control as `$this`, so a lone `<%[ ]%>` localizes at instantiation while `<%[ ]%><br>` localizes at pre-render.
- `CONFIG_INIT_EXPRESSION` on a `TControl` calls `TControl::initBindProperty()`. `TControl::initDataBindProperties()` evaluates the stored expressions as the first step of `initRecursive()`, with the template control as `$this`, passes the bindings to `evaluateBoundProperties()`, and discards them. This runs before the control's `createChildControls()`, before theme skins, before `onInit`, and before `loadPageState`/`processPostData`, so on postback viewstate and posted data override the init value. Late-added controls receive the same treatment through the lifecycle catch-up in `addedControl()`. Non-control `TComponent` targets have no lifecycle and are set by `configureProperty()` during `instantiateIn()`.
- Visibility rule for `<%! %>`: every control in the template is discoverable by ID (`$this->SomeId`), with `Parent`, `Page`, `NamingContainer`, `ClientID` and `UniqueID` resolved. A later sibling's own `<%! %>` values are not yet applied, because its `initRecursive()` has not run. Use `<%! %>` for `ValidationGroup`, `CausesValidation`, input defaults that posted data must override, and properties a control reads in `createChildControls()`/`onInit`. Properties that depend on `onLoad` state or must be recomputed after viewstate loads stay `<%= %>`.
- A property takes exactly one tag type. Declaring the same property twice throws `template_property_duplicated`; mixing tags in one value resolves to a single type with precedence `#` → `!` → `=`.
- `ID` tag values (`=`, `!`, `$`) are evaluated in `instantiateIn()` before `registerObject()`, and the property is retyped to `CONFIG_VALUE` so `configureProperty()` sets the evaluated string instead of evaluating it a second time.
- Attributes are configured in property path order, not declaration order. `instantiateIn()` passes the attribute map through `TComponent::sortPropertyPaths()`, so `Style` is applied before `Style.Width` however the tag declares them, and a nested write survives a parent setter that replaces the object beneath it. A template cannot hand its map to `setSubProperties()` because `configureControl()` switches on a per-attribute `CONFIG_*` type and routes events to `attachEventHandler()`.
- `<%% %>` statement tags are not recognized in attribute values and stay literal text.
- Mixing `~`, `$`, `[`, `/` tags with text and the `<%! %>` tag are supported since 4.4.0 (issue #450).

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

- `packTemplate(int $parentIndex, string|array|TTemplate $type, ?array $attributes = null): array` — Creates a template item array with `TPL_PARENT_INDEX`, `TPL_TYPE`, and optionally `TPL_PROPS`.
- `packProperty($type, string $propName, mixed $value): array` — Returns property info with `PROP_TYPE`, `PROP_NAME`, `PROP_VALUE` keys.
- `propertyExpressionCharToType(string $strValue, string $propName): int` — Converts a template expression character (`=`, `!`, `#`, `~`, `[`, `$`, `/`) to a `CONFIG_*` constant.
- `parseExpression(string $tplType, string $literal): array` — Parses a `<%= %>` / `<%% %>` / `<%# %>` / `<%$ %>` / `<%~ %>` / `<%/ %>` / `<%[ ]%>` expression literal into `[TCompositeLiteral::TYPE_*, expression]`.
- `parseAttributeTag(string $token): string` (@since 4.4.0) — Converts one complete tag inside a mixed attribute value to a PHP expression; `=`/`!`/`#` bodies pass through verbatim, other types go through `parseExpression()`.
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
