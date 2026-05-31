# Util/Traits/INDEX.md

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / **`Traits`**

## Purpose

Reusable PHP traits for common cross-cutting concerns in framework classes.

## Traits

- **[`TConstantReflectionTrait`](TConstantReflectionTrait.md)** — Static reflection helpers for class constants: `hasConstant`, `hasConstantValue`, `valueOfConstant`, `constantOfValue`. Supports case-insensitive and affix-filtered (prefix/suffix) matching. Uses a static `ReflectionClass` cache.

- **[`TInitializedTrait`](TInitializedTrait.md)** — Three-phase initialization state tracking (`null` → `false` → `true`). Provides `assertUninitialized()` to freeze configuration-phase setters after `init()` and `assertInitialized()` to guard runtime methods that require init to be complete.
