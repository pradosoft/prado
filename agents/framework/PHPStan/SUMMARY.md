# SUMMARY.md

PHPStan static analysis extensions teaching PHPStan about Prado's dynamic method system (`dy*`/`fx*` methods) and `TComponent::isa()`.

## Classes

- **`DynamicMethodsClassReflectionExtension`** — PHPStan `MethodsClassReflectionExtension`; recognizes any method starting with `dy` or `fx` as a valid public method with `MixedType` return and variadic parameters.

- **`DynamicMethodReflection`** — Implements PHPStan's `MethodReflection`; returns public, non-static visibility with `MixedType` return and variadic parameters.

- **`TComponentIsaTypeSpecifyingExtension`** — PHPStan type-specifying extension for `TComponent::isa()`; narrows type when `isa()` returns `true`.
