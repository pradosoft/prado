# PHPStan/INDEX.md - PHPSTAN_INDEX.md

This file provides guidance to Agents when working with code in this repository.

## Purpose

PHPStan static analysis extensions that teach PHPStan about Prado's dynamic method system (`dy*` / `fx*` methods) and the `TComponent::isa()` type-narrowing helper.

## Classes

- **`DynamicMethodsClassReflectionExtension`** — PHPStan `MethodsClassReflectionExtension`. Applies only to `TComponent` subclasses. Recognises any method name starting with `dy` or `fx` (case-insensitive) and reports it as a valid public, non-static method with `MixedType` return and variadic parameters. Prevents false "undefined method" PHPStan errors for dynamic behavior events.

- **`DynamicMethodReflection`** — Implements PHPStan's `MethodReflection`. Returns:
  - Visibility: public, non-static
  - No doc comment
  - Return type: `MixedType`
  - Parameters: variadic (accepts any arguments)
  - Side effects: `TrinaryLogic::createMaybe()`

- **`TComponentIsaTypeSpecifyingExtension`** — PHPStan type-specifying extension for `TComponent::isa()`. Narrows the type of the subject when `isa()` returns `true`, similar to `instanceof`.

## Configuration

These extensions are wired in `phpstan.neon.dist`:
```neon
services:
  -
    class: Prado\PHPStan\DynamicMethodsClassReflectionExtension
    tags:
      - phpstan.broker.methodsClassReflectionExtension
```

## When to Update

- **Adding new dynamic accessor prefixes** beyond `dy`/`fx` → update `DynamicMethodsClassReflectionExtension`.
- **Adding new type-narrowing helpers** similar to `isa()` → add a new type-specifying extension following the pattern in `TComponentIsaTypeSpecifyingExtension`.
- These extensions affect static analysis only — not runtime behaviour.

## Gotchas

- The `dy`/`fx` prefix check is **case-insensitive** (`strncasecmp`).
- All dynamic methods report `MixedType` return — no return type refinement is possible without explicit PHPDoc on the call site.
