# Util/TParameterModule

### Directories
[framework](../INDEX.md) / [Util](./INDEX.md) / **`TParameterModule`**

## Class Info
**Location:** `framework/Util/TParameterModule.php`
**Namespace:** `Prado\Util`

## Overview
`TParameterModule` is a `[TModule](TModule.md)` that loads named configuration parameters into the application's parameter store (`[TApplication](../TApplication.md)::getParameters()`). Parameters are simple name-value pairs or object instances that can be accessed anywhere in the application.

## Configuration

```xml
<module id="params" class="Prado\Util\TParameterModule">
    <parameter id="siteName" value="My PRADO App" />
    <parameter id="maxItems" value="50" />
    <!-- Object parameter: class is instantiated and properties set -->
    <parameter id="dbConfig" class="Prado\Data\TDbConnection"
               ConnectionString="mysql:host=localhost;dbname=mydb"
               Username="user" Password="secret" />
</module>
```

Alternatively, use an external parameter file:

```xml
<module id="params" class="Prado\Util\TParameterModule"
        ParameterFile="Application.Config.params" />
```

## PHP Config Format

```php
'parameters' => [
    'siteName' => 'My PRADO App',
    'maxItems' => 50,
    'dbConfig' => [
        'class' => 'Prado\Data\TDbConnection',
        'properties' => ['ConnectionString' => 'mysql:...'],
    ],
],
```

## Accessing Parameters

```php
$params = [Prado](../Prado.md)::getApplication()->getParameters();
$name = $params['siteName'];      // string
$db   = $params['dbConfig'];      // [TDbConnection](../Data/TDbConnection.md) instance
```

## Key Properties

| Property | Description |
|----------|-------------|
| `ParameterFile` | Path alias to an external XML/PHP parameters file |

## Key Methods

```php
$module->init($config): void         // loads inline + file parameters
protected function loadParameters($config): void  // internal; processes config node
```

## Patterns & Gotchas

- **Object parameters** — when `class` is specified, the object is instantiated once and stored. Properties from the config are set on the instance. Access via `$params['id']` returns the live object.
- **Simple values are strings** — all simple `value=` parameters are stored as strings. Cast with `TPropertyValue::ensureInteger()` etc. when needed.
- **`[TParameterizeBehavior](Behaviors/TParameterizeBehavior.md)`** — a behavior that reads properties from the parameter store and applies them to a module. Use this to configure modules from parameters rather than hard-coding values in XML.
- **Ordering** — parameters declared in `application.xml` are processed in declaration order. Modules that depend on parameters should be declared after `TParameterModule`.
- **`[TDbParameterModule](TDbParameterModule.md)`** extends this with database-backed storage and real-time capture of changes.
