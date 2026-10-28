# TComponentReflection

### Directories
[framework](./INDEX.md) / **`TComponentReflection`**

## Class Info
**Location:** `framework/TComponentReflection.php`
**Namespace:** `Prado`
**Since:** 3.0 (static caches and attribute lookup: 4.4.0)

## Overview
`TComponentReflection` has two sides.

- An instance describes one class: its Prado properties (`getXxx()` methods), events (`onXxx()` methods), other public and protected methods, and class attributes. `TErrorHandler` and the template system use the description.
- The static `...ByType` methods are the framework's reflection cache. `ReflectionClass`, `ReflectionMethod`, `ReflectionProperty` and attribute instances are built once per process and shared. [`TReflectionClassTrait`](Util/Traits/TReflectionClassTrait.md) delegates to `getReflectionClassByType()`.

The static side accepts any class or object; only the instance side is specific to [`TComponent`](TComponent.md).

## Static Cache

| Method | Returns | Cache key (lowercased) |
|---|---|---|
| `getReflectionClassByType($class)` | `?ReflectionClass` | `{class}` |
| `getReflectionMethodByType($class, $method)` | `?ReflectionMethod` | `{class}::{method}`; a behavior method `{spl_object_id}::{method}` |
| `getReflectionPropertyByType($class, $property)` | `?ReflectionProperty` | `{class}::{property}` |
| `getClassAttributesByType($class, $attribute, $inherit = true)` | `object[]` | `class::{class}::{0\|1}::{attribute}` |
| `getMethodAttributesByType($class, $method, $attribute)` | `object[]` | `method::{declaring class}::{method}::{attribute}` |
| `getPropertyAttributesByType($class, $property, $attribute)` | `object[]` | `property::{declaring class}::{property}::{attribute}` |
| `getMethodsWithAttributeByType($class, $attribute)` | `[method => object[]]` | `methods::{class}::{attribute}` |
| `getPropertiesWithAttributeByType($class, $attribute)` | `[property => object[]]` | `properties::{class}::{attribute}` |

Rules:

- A class that does not exist is not cached; it may be autoloaded later, so each call retries. The attribute methods return `[]` for it.
- A method missing from a class is cached as `null`. A property missing from the hierarchy is cached as `null` unless the argument is a `TComponent` instance with behaviors enabled, since a behavior may contribute it.
- `getReflectionMethodByType()` given a `TComponent` instance with enabled behaviors falls back to the attached behaviors through `Prado::method_visible()`. The entry is keyed by the behavior's `spl_object_id`, shared by every component the behavior is attached to.
- `getReflectionPropertyByType()` walks the hierarchy, so an ancestor's private property is found. `ReflectionClass::getProperty()` cannot see it.

## Attributes

PHP does not inherit attributes. The cache resolves that in two ways:

| Lookup | Inheritance |
|---|---|
| Class | `$inherit` (default true) walks `getParentClass()`; the class's own attributes come first, then each parent's. Interfaces and traits are not walked. |
| Method, property | The member resolves to its declaring class, so an attribute on a parent's method or property is found on the child, and the entry is shared by every subclass. |

- The filter is `ReflectionAttribute::IS_INSTANCEOF`: a subclass of `$attribute` matches. `$attribute` must be a class or PHP throws `Error`.
- Instances come from `newInstance()`, built once and shared. Attributes are value objects; do not mutate a returned instance.
- Empty results are cached. "No attribute" is the common case.
- `getMethodAttributesByType()` finds a behavior method when given the component instance. `getMethodsWithAttributeByType()` does not include behavior methods, since its map is cached by class; query the behavior class instead.
- `getPropertiesWithAttributeByType()` lists public and protected properties, inherited ones included, and not an ancestor's private property. `getPropertyAttributesByType()` reaches those.

```php
// Every method on $task carrying #[CronTask], with the attribute instances
foreach (TComponentReflection::getMethodsWithAttributeByType($task, CronTask::class) as $method => $attributes) {
	$schedule = $attributes[0]->schedule;
}

// A behavior method, resolved through the component it is attached to
$found = TComponentReflection::getMethodAttributesByType($component, 'behaviorMethod', EventHandler::class);
```

Discovery is pull-based. A consumer asks for the attributes on the classes it already knows about; there is no boot-time scan of the class map and no attribute registry.

The test harness `Prado\Test\Unit\PradoUnit` mirrors the five lookups on its own cache as `classAttributes()`, `methodAttributes()`, `propertyAttributes()`, `methodsWithAttribute()` and `propertiesWithAttribute()`, so a test reads fixture attributes without touching the framework cache. Its `methodAttributes()` does not resolve behavior methods.

## Instance Description

```php
$r = new TComponentReflection(TDataGrid::class);   // class name or instance
$r->getProperties();   // [name => [type, readonly, protected, class, comments]]
$r->getEvents();       // [OnName => [class, protected, comments]]
$r->getMethods();      // [name => [class, protected, static, comments]]
$r->getAttributes();   // [[name, arguments, class], ...] own first, then parents
```

- Properties and events are collected only when the class is a `TComponent`; methods are collected for any class.
- `getAttributes()` reads `getName()` and `getArguments()` and does not instantiate, so an attribute whose class is not loaded is still listed.
- The constructor throws `TInvalidDataTypeException` for a string that is not a loaded class (`class_exists($name, false)`).

## Patterns & Gotchas

- Keys are lowercased, so `getReflectionClassByType('prado\tcomponent')` and `TComponent::class` share one entry.
- `spl_object_id` is reused after an object is collected. A behavior cache entry can outlive its behavior; a new behavior with the same id and a same-named method reuses the entry. The entry holds a `ReflectionMethod` on the behavior's class, so the reuse is harmless when the class matches.
- The caches are process-lifetime statics with no eviction. Under a long-running process they grow with the number of distinct classes reflected, not with requests.
