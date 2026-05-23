# Util/Cron/TDbCronModule

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Cron](./INDEX.md) / **`TDbCronModule`**

## Class Info
**Location:** `framework/Util/Cron/TDbCronModule.php`
**Namespace:** `Prado\Util\Cron`
**Extends:** [`TDbCronManager`](TDbCronManager.md)
**Since:** 4.2.0
**Deprecated:** since 4.3.3 — use [`TDbCronManager`](TDbCronManager.md) instead. Scheduled for removal in v4.4.

## Overview
`TDbCronModule` is now a **backwards-compatible alias** for [`TDbCronManager`](TDbCronManager.md). The class body is empty — all functionality lives in `TDbCronManager`. Existing `application.xml` configurations that reference `Prado\Util\Cron\TDbCronModule` continue to work without modification.

```php
class TDbCronModule extends TDbCronManager
{
    // deprecated stub — all logic is in TDbCronManager
}
```

## Migration

Replace `TDbCronModule` with `TDbCronManager` in your configuration:

```xml
<modules>
    <!-- Before (deprecated) -->
    <module id="cron" class="Prado\Util\Cron\TDbCronModule" ConnectionID="db" />

    <!-- After -->
    <module id="cron" class="Prado\Util\Cron\TDbCronManager" ConnectionID="db" />
</modules>
```

**PHP equivalent:**
```php
return [
    'modules' => [
        'cron' => [
            'class' => 'Prado\Util\Cron\TDbCronModule',
            'properties' => ['ConnectionID' => 'db'],
        ],
    ],
];
```

## See Also

- [`TDbCronManager`](TDbCronManager.md) — the primary class (full documentation here)
- [`TCronModule`](TCronModule.md)
