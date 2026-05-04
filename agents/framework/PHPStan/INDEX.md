# PHPStan/INDEX.md

### Directories
[framework](../INDEX.md) / **`PHPStan/INDEX.md`**

## Purpose

PHPStan static analysis extensions that teach PHPStan about Prado's dynamic method system (`dy*` / `fx*` methods), virtual property system (`get{X}()` / `set{X}()`), and TComponent type-narrowing helpers (`isa()`, `hasMethod()`, `canGetProperty()`, `canSetProperty()`, `Prado::method_visible()`).

## Classes

- **[DynamicMethodsClassReflectionExtension](./DynamicMethodsClassReflectionExtension.md)** — PHPStan `MethodsClassReflectionExtension`. Applies only to `TComponent` subclasses. Recognises any method name starting with `dy` or `fx` (case-insensitive) and reports it as a valid public, non-static method with `MixedType` return and variadic parameters. Prevents false "undefined method" PHPStan errors for dynamic behavior events.

- **[DynamicMethodReflection](./DynamicMethodReflection.md)** — Implements PHPStan's `MethodReflection`. Returns:
  - Visibility: public, non-static
  - No doc comment
  - Return type: `MixedType`
  - Parameters: variadic (accepts any arguments)
  - Side effects: `TrinaryLogic::createMaybe()`

- **TComponentPropertiesReflectionExtension** — PHPStan `PropertiesClassReflectionExtension`. Maps every `get{X}()` / `set{X}()` method pair on any `TComponent` subclass to a virtual property `X`, enabling `$obj->X` access without "undefined property" errors. The readable type is derived from the getter's return type; the writable type from the setter's first parameter type. The `getjs{X}()` / `setjs{X}()` JS-aware variants are also recognised.

- **TComponentPropertyReflection** — Implements PHPStan's `PropertyReflection` for PRADO virtual properties. Stores optional getter and setter `MethodReflection` references. `isReadable()` is true when a getter exists; `isWritable()` is true when a setter exists. `canChangeTypeAfterAssignment()` returns `false` (method-hook semantics).

- **TComponentHasMethodTypeSpecifyingExtension** — PHPStan `MethodTypeSpecifyingExtension` for `TComponent::hasMethod()`. When `$obj->hasMethod('foo')` is true inside an `if`-block, narrows the type of `$obj` to `OriginalType & HasMethodType('foo')`, making the guarded call `$obj->foo()` valid. Mirrors the behaviour of PHPStan's built-in `method_exists()` narrowing.

- **TComponentCanGetPropertyTypeSpecifyingExtension** — `MethodTypeSpecifyingExtension` for `TComponent::canGetProperty()`. When `$obj->canGetProperty('Foo')` is true, narrows `$obj` to have `HasMethodType('getFoo')`, allowing `$obj->getFoo()` inside the guarded block without errors.

- **TComponentCanSetPropertyTypeSpecifyingExtension** — `MethodTypeSpecifyingExtension` for `TComponent::canSetProperty()`. When `$obj->canSetProperty('Foo')` is true, narrows `$obj` to have `HasMethodType('setFoo')`, allowing `$obj->setFoo(...)` inside the guarded block without errors.

- **[TComponentIsaTypeSpecifyingExtension](./TComponentIsaTypeSpecifyingExtension.md)** — PHPStan type-specifying extension for `TComponent::isa()`. Narrows the type of the subject when `isa()` returns `true`, similar to `instanceof`.

- **PradoMethodVisibleStaticMethodTypeSpecifyingExtension** — PHPStan `StaticMethodTypeSpecifyingExtension` for `Prado::method_visible()`. When `Prado::method_visible($obj, 'foo')` is true, narrows the type of `$obj` to have `HasMethodType('foo')`, making the guarded call `$obj->foo()` valid. Mirrors the behaviour of PHPStan's built-in `method_exists()` narrowing.

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

Fixture files are in `tests/unit/PHPStan/fixtures/`:
- `HasMethodFixture.php` — `TComponent::hasMethod()` guard patterns
- `MethodVisibleFixture.php` — `Prado::method_visible()` guard patterns
- `DynamicMethodsFixture.php` — `dy*` / `fx*` dynamic method calls
- `IsaFixture.php` — `TComponent::isa()` type-narrowing patterns
- `CanGetPropertyFixture.php` — `canGetProperty()` guard patterns
- `CanSetPropertyFixture.php` — `canSetProperty()` guard patterns
- `PropertiesReflectionFixture.php` — virtual property access via `$obj->Prop`

## When to Update

- **Adding new dynamic accessor prefixes** beyond `dy`/`fx` → update `DynamicMethodsClassReflectionExtension`.
- **Adding new type-narrowing helpers** similar to `isa()` → add a new type-specifying extension following the existing patterns.
- **Adding new property-checking methods** similar to `canGetProperty()` → follow the `TComponentCanGetPropertyTypeSpecifyingExtension` pattern.
- These extensions affect static analysis only — not runtime behaviour.

## Gotchas

- The `dy`/`fx` prefix check in `DynamicMethodsClassReflectionExtension` is **case-insensitive** (`strncasecmp`).
- `TComponentHasMethodTypeSpecifyingExtension` only narrows when exactly **one** constant string is passed as the method name argument. Dynamic (variable) method names cannot be narrowed.
- `TComponentPropertiesReflectionExtension` uses `hasMethod('get' . $name)` which is case-insensitive in PHP — `getText` and `gettext` are the same method. PHPStan will ask for whatever case appears in source code (`$obj->Text` → `hasProperty('Text')` → `hasMethod('getText')`).
- Virtual properties use `canChangeTypeAfterAssignment() = false` because the setter/getter may apply custom logic, preventing type narrowing after writes.
