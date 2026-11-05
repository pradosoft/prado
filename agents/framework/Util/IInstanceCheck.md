# Util/IInstanceCheck

### Directories
[framework](../INDEX.md) / [Util](./INDEX.md) / **`IInstanceCheck`**

## Class Info
**Location:** `framework/Util/IInstanceCheck.php`
**Namespace:** `Prado\Util`

## Overview
`IInstanceCheck` allows an object to control its own `instanceof` result when checked via [`TComponent::isa()`](../TComponent.md). This is important for behaviors that want to present themselves as a different type — for example, a proxy behavior that should appear to be an instance of the proxied class.

For class behaviors, the `$instance` parameter receives the owner component so the behavior can make per-owner decisions.

## Interface Method

```php
public function isinstanceof(string $class, ?object $instance = null): ?bool;
```

- Returns `true` — treat as an instance of `$class`
- Returns `false` — not an instance (even if PHP says otherwise)
- Returns `null` — no opinion; fall through to default `instanceof` logic

## See Also

- [`TComponent::isa()`](../TComponent.md) — calls `isinstanceof()` when the object implements this interface
- [`IBaseBehavior`](IBaseBehavior.md)
