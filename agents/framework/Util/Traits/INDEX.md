# Util/Traits/INDEX.md

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / **`Traits`**

## Purpose

Reusable PHP traits for common cross-cutting concerns in framework classes.

## Traits

- **[`TConstantReflectionTrait`](TConstantReflectionTrait.md)** — Static reflection helpers for class constants: `hasConstant`, `hasConstantValue`, `valueOfConstant`, `constantOfValue`. Supports case-insensitive and affix-filtered (prefix/suffix) matching. Uses a static `ReflectionClass` cache.

- **`TReflectionClassTrait`** — `getReflectionClass()` returns the cached `\ReflectionClass` of the calling class through `TComponentReflection`.

- **`TArrayIteratorTrait`** — Implements `\Iterator` over the array returned by reference from `getIteratorArray()`.

- **`TArrayCopyIteratorTrait`** — Implements `\Iterator` over a copy of `getIteratorArrayCopy()`, taken on first use and replaced with `setIteratorArrayDirect()`.

- **`TModuleConfigurationFileTrait`** — `readConfigurationFile()` reads a module's PHP or XML configuration file.

- **[`TInitializedTrait`](TInitializedTrait.md)** — Three-phase initialization state tracking (`null` → `false` → `true`). Provides `assertUninitialized()` to freeze configuration-phase setters after `init()` and `assertInitialized()` to guard runtime methods that require init to be complete.

> The clock traits `TClockTrait` and `TClockAwareTrait` live in [`Util/Clock/`](../Clock/INDEX.md), alongside the clock classes.
