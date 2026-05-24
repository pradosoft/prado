# Web/UI/TModuleView

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [UI](./INDEX.md) / **`TModuleView`**

## Class Info
**Location:** `framework/Web/UI/TModuleView.php`
**Namespace:** `Prado\Web\UI`
**Extends:** `TCompositeControl`

## Overview
TModuleView conditionally shows its child controls only when a specified application module is present **and** an optional PHP condition expression evaluates to `true`. When the module is absent or the condition is false, the children are cleared and an optional `FallbackTemplate` is instantiated in their place.

Without a `ModuleId` it behaves like [TConditional](./WebControls/TConditional.md), except that children are **not created at all** when inactive (rather than just hidden).

## Key Properties

| Property | Type | Description |
|---|---|---|
| `ModuleId` | `string` | Application module ID to check. Empty = no module check. |
| `Condition` | `string` | PHP expression evaluated in the template control's context. Defaults to `'true'`. HTML entities are decoded before evaluation. |
| `FallbackTemplate` | `?ITemplate` | Template instantiated when the view is inactive. Set via `<prop:FallbackTemplate>` in a template. |
| `IsActive` | `bool` (read-only) | `true` if the module is present **and** `Condition` evaluates truthy. Lazily computed; reset when `ModuleId` or `Condition` changes. |
| `ModuleAvailable` | `bool` (read-only) | `true` if the module specified by `ModuleId` is registered in the application. |
| `Module` | `mixed` (read-only) | The module instance, or `null` if not found. |
| `AllowChildControls` | `bool` (read-only) | Returns `ModuleAvailable`. Controls whether the template instantiates children at all. |

## Usage

```xml
<!-- Show content only when the 'cache' module is loaded -->
<com:TModuleView ModuleId="cache">
    <com:TOutputCache ...>
        <!-- cached content here -->
    </com:TOutputCache>
    <prop:FallbackTemplate>
        <p>Caching is not available.</p>
    </prop:FallbackTemplate>
</com:TModuleView>

<!-- Use as a conditional (no module check) -->
<com:TModuleView Condition="$this->Page->User->isAdmin()">
    <!-- admin-only content -->
</com:TModuleView>
```

## Lifecycle

`createChildControls()` evaluates `IsActive`:
- **Active** — does nothing (parent/template instantiation proceeds normally).
- **Inactive** — clears the control collection, instantiates `FallbackTemplate` if set, then calls `parent::createChildControls()`.

## Patterns & Gotchas

- **Children not created when inactive** — unlike `TConditional` (which creates both branches), `TModuleView` skips child creation entirely when `AllowChildControls` is `false`. This avoids errors from controls that depend on the module being present.
- **Condition expression** — evaluated via `TTemplateControl::evaluateExpression()` in the context of the nearest template control. Throws `TInvalidDataValueException` if the expression is invalid.
- **`IsActive` is cached** — computed once per request. Changing `ModuleId` or `Condition` after `createChildControls` has run has no effect for the current request.
- **`createControlCollection()` always returns a plain `TControlCollection`** — regardless of `AllowChildControls`, so controls can be cleared/repopulated in `createChildControls`.

## See Also

- [TConditional](./WebControls/TConditional.md)
- [TCompositeControl](./TCompositeControl.md)

**@since 4.3.3**
