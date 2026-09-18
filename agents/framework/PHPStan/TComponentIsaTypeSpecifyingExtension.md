# PHPStan/TComponentIsaTypeSpecifyingExtension

### Directories
[framework](../INDEX.md) / [PHPStan](./INDEX.md) / **`TComponentIsaTypeSpecifyingExtension`**

## Class Info
**Location:** `framework/PHPStan/TComponentIsaTypeSpecifyingExtension.php`
**Namespace:** `Prado\PHPStan`

## Overview
PHPStan extension that makes `$component->isa(MyClass::class)` behave like `$component instanceof MyClass` for type specification.

**This class is `final`** and cannot be extended.

## Problem Solved

```php
// Without extension, PHPStan doesn't know the type narrowed by isa()
if ($component->isa(MyClass::class)) {
    $component->someMethod();  // PHPStan: method not found
}

// With extension, PHPStan knows $component is MyClass
```

## How It Works

Implements `MethodTypeSpecifyingExtension` and `TypeSpecifierAwareExtension`. Injects `ReflectionProvider` via constructor to validate that each class name constant actually exists before building a type. When multiple constant strings are provided, the narrowed type is a `UnionType` over all valid class names; when only one is provided, it is a plain `ObjectType`. Unknown class names are skipped. This correctly handles interfaces and abstract classes in addition to concrete classes.

## Usage

Add to `phpstan.neon`:
```neon
services:
    -
        class: Prado\PHPStan\TComponentIsaTypeSpecifyingExtension
        tags:
            - phpstan.typeSpecifier.methodTypeSpecifyingExtension
```

## Requirements

- Method must be `isa`
- Caller must resolve to an object type
- First argument must resolve to one or more constant class-name strings known to the `ReflectionProvider`
- Subject type must have **exactly one** object class name (see Multi-Class Subjects)

## Multi-Class Subjects

PHPStan's `MethodCallHandler::specifyTypes()` looks up method type-specifying extensions like this:

```php
$referencedClasses = $methodCalledOnType->getObjectClassNames();
if (count($referencedClasses) === 1 && $this->reflectionProvider->hasClass($referencedClasses[0])) {
    foreach ($typeSpecifier->getMethodTypeSpecifyingExtensionsForClass(...) as $extension) {
        if (!$extension->isMethodSupported($methodReflection, $normalizedExpr, $context)) {
            continue;
        }
        return $extension->specifyTypes($methodReflection, $normalizedExpr, $scope, $context);
    }
}
```

An intersection type returns one class name per member, so `IService&TComponent` returns two and the lookup is skipped. A union does the same: `TControl|TStyle` also returns two. `isMethodSupported()` is never called and this extension cannot narrow either subject, whatever it does internally.

The narrowing for those subjects lives on the method instead:

```php
/**
 * @template T of object
 * @param class-string<T>|T $class class or string
 * @phpstan-assert-if-true T $this
 */
public function isa($class)
```

PHPStan evaluates `getAsserts()` outside the single-class-name guard, so the tag narrows every subject type. It ships with the framework, so it needs no `phpstan.neon` entry.

```php
public function b(?IService $s): void
{
    if ($s !== null && $s instanceof TComponent && $s->isa(TPageService::class)) {
        $s->getRequestedPage();   // narrowed to TPageService by the assertion tag
    }
}
```

The shape arises wherever a guard mixes `instanceof` with a type the subject does not already satisfy, which is common when an interface-typed value is checked for `TComponent` before an `isa()` call.

## Redundancy Note

The assertion tag covers everything this extension covers, including objects passed to `isa()` in place of a class string. The extension stays registered so existing `phpstan.neon` service blocks keep resolving, and PHPStan dispatches it first for single-class-name subjects, where both mechanisms produce the same type.

## See Also

- [TComponent](../TComponent.md)::isa() — PRADO method for duck-typing with behaviors and interfaces
- [TComponentHasMethodTypeSpecifyingExtension](./TComponentHasMethodTypeSpecifyingExtension.md) — sibling narrowing extension for `hasMethod()`, subject to the same single-class-name limit
