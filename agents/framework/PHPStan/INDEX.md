# PHPStan/INDEX.md

### Directories
[framework](../INDEX.md) / **`PHPStan`**

## Purpose

PHPStan static analysis extensions that teach PHPStan about Prado's dynamic method system (`dy*` / `fx*` methods), virtual property system (`get{X}()` / `set{X}()`), and TComponent type-narrowing helpers (`isa()`, `hasMethod()`, `canGetProperty()`, `canSetProperty()`, `Prado::method_visible()`).

Narrowing that PHPStan cannot route to an extension is carried by assertion tags in the framework source. `TComponent::isa()` declares `@template T of object`, `@param class-string<T>|T $class` and `@phpstan-assert-if-true T $this`, so `isa()` narrows every subject type with no configuration at all.

## Classes

- **[DynamicMethodsClassReflectionExtension](./DynamicMethodsClassReflectionExtension.md)** — PHPStan `MethodsClassReflectionExtension`. Applies only to `TComponent` subclasses. Recognises any method name starting with `dy` or `fx` (case-insensitive) and reports it as a valid public, non-static method with `MixedType` return and variadic parameters. Prevents false "undefined method" PHPStan errors for dynamic behavior events.

- **[DynamicMethodReflection](./DynamicMethodReflection.md)** — Implements PHPStan's `MethodReflection`. Returns:
  - Visibility: public, non-static
  - No doc comment
  - Return type: `MixedType`
  - Parameters: variadic (accepts any arguments)
  - Side effects: `TrinaryLogic::createMaybe()`

- **[TComponentPropertiesReflectionExtension](./TComponentPropertiesReflectionExtension.md)** — PHPStan `PropertiesClassReflectionExtension`. Maps every `get{X}()` / `set{X}()` method pair on any `TComponent` subclass to a virtual property `X`, enabling `$obj->X` access without "undefined property" errors. The readable type is derived from the getter's return type; the writable type from the setter's first parameter type. The `getjs{X}()` / `setjs{X}()` JS-aware variants are also recognised.

- **[TComponentPropertyReflection](./TComponentPropertyReflection.md)** — Implements PHPStan's `PropertyReflection` for PRADO virtual properties. Stores optional getter and setter `MethodReflection` references. `isReadable()` is true when a getter exists; `isWritable()` is true when a setter exists. `canChangeTypeAfterAssignment()` returns `false` (method-hook semantics).

- **[TComponentHasMethodTypeSpecifyingExtension](./TComponentHasMethodTypeSpecifyingExtension.md)** — PHPStan `MethodTypeSpecifyingExtension` for `TComponent::hasMethod()`. When `$obj->hasMethod('foo')` is true inside an `if`-block, narrows the type of `$obj` to `OriginalType & HasMethodType('foo')`, making the guarded call `$obj->foo()` valid. If the method name follows PRADO's `get{X}`/`set{X}`/`getjs{X}`/`setjs{X}` convention, also narrows the virtual property via `HasPropertyType`, under both the `X` and `x` spellings. Mirrors the behaviour of PHPStan's built-in `method_exists()` narrowing.

- **[TComponentCanGetPropertyTypeSpecifyingExtension](./TComponentCanGetPropertyTypeSpecifyingExtension.md)** — `MethodTypeSpecifyingExtension` for `TComponent::canGetProperty()`. When `$obj->canGetProperty('Foo')` is true, narrows `$obj` to have `HasMethodType('getFoo')` and a `HasPropertyType` for both the `Foo` and `foo` spellings, allowing `$obj->getFoo()`, `$obj->Foo` and `$obj->foo` inside the guarded block without errors.

- **[TComponentCanSetPropertyTypeSpecifyingExtension](./TComponentCanSetPropertyTypeSpecifyingExtension.md)** — `MethodTypeSpecifyingExtension` for `TComponent::canSetProperty()`. When `$obj->canSetProperty('Foo')` is true, narrows `$obj` to have `HasMethodType('setFoo')` and a `HasPropertyType` for both the `Foo` and `foo` spellings, allowing `$obj->setFoo(...)`, `$obj->Foo = $v` and `$obj->foo = $v` inside the guarded block without errors.

- **[TComponentIsaTypeSpecifyingExtension](./TComponentIsaTypeSpecifyingExtension.md)** — PHPStan type-specifying extension for `TComponent::isa()`. Narrows the type of the subject when `isa()` returns `true`, similar to `instanceof`. Supports interfaces and multiple class-name constants (produces a `UnionType`). Validates class names via `ReflectionProvider`. Reached only for subjects with exactly one object class name; intersection subjects are narrowed by the `@phpstan-assert-if-true` tag on [TComponent](../TComponent.md)`::isa()` instead.

- **[PradoMethodVisibleStaticMethodTypeSpecifyingExtension](./PradoMethodVisibleStaticMethodTypeSpecifyingExtension.md)** — PHPStan `StaticMethodTypeSpecifyingExtension` for `Prado::method_visible()`. When `Prado::method_visible($obj, 'foo')` is true, narrows the type of `$obj` to have `HasMethodType('foo')`, making the guarded call `$obj->foo()` valid. Mirrors the behaviour of PHPStan's built-in `method_exists()` narrowing.

## Configuration

All extensions are wired in `phpstan.neon.dist`. Tags used:
- `phpstan.broker.methodsClassReflectionExtension` — for dynamic method extensions
- `phpstan.broker.propertiesClassReflectionExtension` — for virtual property extensions
- `phpstan.typeSpecifier.methodTypeSpecifyingExtension` — for instance-method type narrowing
- `phpstan.typeSpecifier.staticMethodTypeSpecifyingExtension` — for static-method type narrowing

## Tests

PHPUnit tests live in `tests/unit/PHPStan/PHPStanExtensionsTest.php`. Each extension has a pair of tests:
1. **Without extension** (`phpstan-no-extensions.neon`) — verifies the fixture file DOES produce PHPStan errors.
2. **With extension** (`phpstan.neon.dist`) — verifies the fixture file produces ZERO errors.

`isa()` is the exception. Its narrowing comes from an assertion tag in the framework source, which no configuration can switch off, so both `isa()` passes expect zero errors. `IsaIntersectionNegativeFixture.php` supplies the missing signal: it calls a method that exists nowhere and the test asserts the reported error names the narrowed class, which fails if the narrowing stops working.

Fixture files are in `tests/unit/PHPStan/Fixtures/`:
- `HasMethodFixture.php` — `TComponent::hasMethod()` guard patterns
- `MethodVisibleFixture.php` — `Prado::method_visible()` guard patterns
- `DynamicMethodsFixture.php` — `dy*` / `fx*` dynamic method calls
- `IsaFixture.php` — `TComponent::isa()` type-narrowing patterns
- `IsaInterfaceFixture.php` — `isa()` narrowing to an interface
- `IsaIntersectionFixture.php` — `isa()` narrowing of intersection-typed subjects
- `IsaIntersectionNegativeFixture.php` — one expected error, asserted to name the narrowed class
- `PropertyCaseFixture.php` — virtual property spelling under `canGetProperty()` / `canSetProperty()` / `hasMethod()` guards
- `CanGetPropertyFixture.php` — `canGetProperty()` guard patterns
- `CanSetPropertyFixture.php` — `canSetProperty()` guard patterns
- `PropertiesReflectionFixture.php` — virtual property access via `$obj->Prop`

## When to Update

- **Adding new dynamic accessor prefixes** beyond `dy`/`fx` → update `DynamicMethodsClassReflectionExtension`.
- **Adding new type-narrowing helpers** similar to `isa()` → add a new type-specifying extension following the existing patterns, and add an `@phpstan-assert-if-true` tag to the helper so intersection subjects narrow too.
- **Adding new property-checking methods** similar to `canGetProperty()` → follow the `TComponentCanGetPropertyTypeSpecifyingExtension` pattern.
- These extensions affect static analysis only — not runtime behaviour.

## Gotchas

- The `dy`/`fx` prefix check in `DynamicMethodsClassReflectionExtension` is **case-insensitive** (`strncasecmp`).
- PHPStan hands a method call to a `MethodTypeSpecifyingExtension` only when the subject type has **exactly one** object class name (`MethodCallHandler::specifyTypes()`). An intersection (`IService&TComponent`) and a union (`TControl|TStyle`) both have two, so no method type-specifying extension ever sees either, and `isMethodSupported()` is never called. Narrowing for such subjects belongs in an `@phpstan-assert-if-true` tag on the method, which PHPStan evaluates for every subject type. `TComponentHasMethodTypeSpecifyingExtension`, `TComponentCanGetPropertyTypeSpecifyingExtension` and `TComponentCanSetPropertyTypeSpecifyingExtension` share the limitation. Their narrowing targets `HasMethodType` and `HasPropertyType`, which no PHPDoc tag expresses, so intersection and union subjects stay unnarrowed for those three.
- **Workaround for multi-class subjects**: `Prado::method_visible($obj, 'foo')` narrows where `$obj->hasMethod('foo')` cannot. `PradoMethodVisibleStaticMethodTypeSpecifyingExtension` is a **static** method extension: PHPStan finds it through the `Prado` class and it narrows one of its arguments, so the subject's class-name count never enters into the lookup. Reach for it on an interface-typed or union-typed subject.
- The three property-aware extensions register the virtual property under **both** the given spelling and its lowercase-first form (`Title` and `title`). PHPStan asks for a property with the spelling written in the source, and PRADO writes `Title`; PHP method names are case-insensitive, so `title` reaches the same accessor.
- `TComponentHasMethodTypeSpecifyingExtension` only narrows when exactly **one** constant string is passed as the method name argument. Dynamic (variable) method names cannot be narrowed.
- `TComponentPropertiesReflectionExtension` uses `hasMethod('get' . $name)` which is case-insensitive in PHP — `getText` and `gettext` are the same method. PHPStan will ask for whatever case appears in source code (`$obj->Text` → `hasProperty('Text')` → `hasMethod('getText')`).
- Virtual properties use `canChangeTypeAfterAssignment() = false` because the setter/getter may apply custom logic, preventing type narrowing after writes.
