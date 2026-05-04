# Util/TBehaviorsModule

### Directories
[framework](../INDEX.md) / [Util](./INDEX.md) / **`TBehaviorsModule`**

## Class Info
**Location:** `framework/Util/TBehaviorsModule.php`
**Namespace:** `Prado\Util`

## Overview
`[TModule](../TModule.md)` that loads behavior configuration from `application.xml` and attaches behaviors to application components.

```xml
<module id="behaviors" class="Prado\Util\TBehaviorsModule">
    <!-- Attach behavior to a specific component: -->
    <behavior name="noCache"
              class="Prado\Util\Behaviors\TPageNoCacheBehavior"
              AttachTo="Page" />

    <!-- Attach class-wide behavior: -->
    <behavior name="audit"
              class="MyApp\AuditBehavior"
              AttachToClass="MyApp\Models\PostRecord" />

    <!-- Priority (lower = earlier): -->
    <behavior name="signals"
              class="Prado\Util\Behaviors\TApplicationSignals"
              AttachTo="Application"
              Priority="5" />
</module>
```

Behaviors can also be attached programmatically:
```php
$component->attachBehavior('myBehavior', new MyBehavior());
[TComponent](../TComponent.md)::attachClassBehavior('audit', new AuditBehavior(), PostRecord::class);
```

---

## Pre-built Behaviors (Util/Behaviors/)

| Class | Attach To | Purpose |
|-------|-----------|---------|
| `[TApplicationSignals](Behaviors/TApplicationSignals.md)` | `[TApplication](../TApplication.md)` | Routes POSIX signals (SIGTERM/SIGINT/SIGHUP) to application lifecycle events for graceful shutdown |
| `[TBehaviorParameterLoader](Behaviors/TBehaviorParameterLoader.md)` | Any component | Loads behavior config from a parameter module; allows dynamic behavior configuration |
| `[TCaptureForkLog](Behaviors/TCaptureForkLog.md)` | `[TApplication](../TApplication.md)` | Captures log entries from forked child processes |
| `[TGlobalClassAware](Behaviors/TGlobalClassAware.md)` | Any component | Makes component aware of global class-level behaviors |
| `[TMapLazyLoadBehavior](Behaviors/TMapLazyLoadBehavior.md)` | `[TMap](../Collections/TMap.md)` subclass | Lazy-loads TMap items on first property access |
| `[TMapRouteBehavior](Behaviors/TMapRouteBehavior.md)` | `[TMap](../Collections/TMap.md)` subclass | Routes TMap read/write through a configurable handler |
| `[TPageGlobalizationCharsetBehavior](Behaviors/TPageGlobalizationCharsetBehavior.md)` | `[TPage](../Web/UI/TPage.md)` | Sets page charset from `[TGlobalization](../I18N/TGlobalization.md)` settings |
| `[TPageNoCacheBehavior](Behaviors/TPageNoCacheBehavior.md)` | `[TPage](../Web/UI/TPage.md)` | Adds `Cache-Control: no-store` headers |
| `[TPageTopAnchorBehavior](Behaviors/TPageTopAnchorBehavior.md)` | `[TPage](../Web/UI/TPage.md)` | Injects `<a id="top"></a>` at page top |
| `[TParameterizeBehavior](Behaviors/TParameterizeBehavior.md)` | Any component | Sets component properties from `[TApplication](../TApplication.md)::getParameters()` at init time |
| `[TTimeZoneParameterBehavior](Behaviors/TTimeZoneParameterBehavior.md)` | `[TApplication](../TApplication.md)` | Sets PHP default timezone from an application parameter |

### TNoUnserializeBehaviorTrait

PHP trait for behaviors that should not survive serialization (stateless class behaviors):
```php
class MyStatelessBehavior extends [TClassBehavior](TClassBehavior.md)
{
    use [TNoUnserializeBehaviorTrait](Behaviors/TNoUnserializeBehaviorTrait.md);
    // ...
}
```

### TForkable Trait

For classes that need safe process forking with log capture:
```php
class MyWorker
{
    use [TForkable](Behaviors/TForkable.md);

    public function process(): void
    {
        $pid = $this->fork();
        if ($pid === 0) {
            // child process
        }
        // parent process
    }
}
```

---

## Patterns & Gotchas

- **`[TApplicationSignals](Behaviors/TApplicationSignals.md)`** — Enables graceful SIGTERM/SIGINT handling. Attach to `[TApplication](../TApplication.md)` for long-running processes (CLI workers, daemons).
- **`[TParameterizeBehavior](Behaviors/TParameterizeBehavior.md)`** — Useful for loading secrets (API keys, credentials) from parameters without hardcoding them in XML. Attach to any module.
- **Behavior priority** — lower numbers run first. Default is `10`. Use `Priority="1"` for behaviors that must run before others.
