# Util/Behaviors/TEvaluationFilterBehavior

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Behaviors](./INDEX.md) / **`TEvaluationFilterBehavior`**

## Class Info
**Location:** `framework/Util/Behaviors/TEvaluationFilterBehavior.php`
**Namespace:** `Prado\Util\Behaviors`
**Since:** 4.4.0

## Overview
TEvaluationFilterBehavior is an opt-in [TClassBehavior](../TClassBehavior.md) that checks the PHP code of [TComponent](../../TComponent.md)`::evaluateExpression()` and `evaluateStatements()` before `eval()` runs it. It implements `dyEvaluateExpressionFilter` and `dyEvaluateStatementsFilter`. Code that fails a check throws `TInvalidOperationException` (`evaluationfilter_code_denied` or `evaluationfilter_statements_denied`) and is not evaluated.

Each filter calls the rest of the chain first and checks the returned code, so it checks the code that runs. An expression is tokenized as `return <expression>;`, the same way `evaluateExpression()` passes it to `eval()`, so a `;` that adds a second statement is checked as well. The tokenizer uses `TOKEN_PARSE`. Code that does not parse is returned unchanged, and `eval()` reports the parse error.

## Checks

| Code | Result |
|---|---|
| PHP statements | Rejected unless `AllowStatements` is true |
| `eval`, `include`, `require`, `exit`, backticks, `$$`, `${`, `?>`, attributes, `function`, `class`, `interface`, `trait`, `enum`, `namespace`, `use`, `global`, `goto`, `declare` | Rejected |
| `name(...)` | `name` must be in `AllowedFunctions` |
| `Class::method(...)`, `Class::$property`, `new Class` | `Class::method`, `Class::__construct`, or `Class::*` must be in `AllowedStaticMethods` |
| `Class::CONSTANT`, `Class::class` | Allowed |
| `->name`, `?->name` | Rejected when `name` begins with `_` or, with or without a `get`/`set` prefix, is in `DeniedMembers` |
| `->$name`, `->{...}` | Rejected |
| A call on a variable, string, `)`, `]`, or `}` | Rejected |

`eval()` runs in the scope of `TComponent`, so evaluated code can read and write its private `_m` and `_e` properties. The `_` rule blocks that. The default `DeniedMembers` block re-evaluation (`evaluateExpression`, `evaluateStatements`) and changes to behaviors and event handlers (`asa`, `disableBehavior(s)`, `Behaviors`, `attachEventHandler`, and others), which would turn the filter off for later code.

The filter is not a sandbox. An allowed method can reach other objects, and an event handler added through an event property is a callable. Request data must not be evaluated.

## Properties

- `AllowStatements` (bool, default `false`) — whether `evaluateStatements()` runs. `<%% %>` tags and `TStatements` need it.
- `AllowedFunctions` (array or comma-separated string; `null` restores `DEFAULT_ALLOWED_FUNCTIONS`) — string, array, math, and date functions. None takes a callable or accesses files. Template `<%/ %>` tags use `rtrim()` and `dirname()`.
- `AllowedStaticMethods` (`null` restores `DEFAULT_ALLOWED_STATIC_METHODS`: `Prado::localize`, `Prado\Prado::localize`, used by `<%[ ]%>` tags).
- `DeniedMembers` (`null` restores `DEFAULT_DENIED_MEMBERS`). Members that begin with `_` are always denied.

Names are case-insensitive; a leading `\` is ignored. Class names are compared as written in the code: `eval()` runs in the global namespace, so `Prado` and `Prado\Prado` are separate entries.

## Methods

- `findDeniedCode(string $source): ?string` — returns the first denied code in `$source` as `eval()` receives it, or null.
- `isAllowedFunction(string $name)`, `isAllowedStaticMethod(string $class, string $method)`, `isDeniedMember(string $name)` — the individual checks. `isAllowedStaticMethod()` takes `__construct` for `new` and `*` for a static property.

## Configuration

Template controls, templates, themes, modules, services, and cron tasks are `TApplicationComponent`s; `TApplication` evaluates configuration conditions.

```xml
<module id="behaviors" class="Prado\Util\TBehaviorsModule">
    <behavior name="evaluationFilter" Class="Prado\Util\Behaviors\TEvaluationFilterBehavior" AttachToClass="Prado\TApplicationComponent" />
    <behavior name="appEvaluationFilter" Class="Prado\Util\Behaviors\TEvaluationFilterBehavior" AttachTo="Application" />
</module>
```

A class behavior attaches to each new instance, so every control carries it. Each evaluation tokenizes its code.

## See Also

- [TComponent](../../TComponent.md) — `evaluateExpression()`, `evaluateStatements()`
- [TCallChain](../TCallChain.md)
- [TBehaviorsModule](../TBehaviorsModule.md)
