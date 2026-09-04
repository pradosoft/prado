# Util/Log/TFileLogRoute

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Log](./INDEX.md) / **`TFileLogRoute`**

## Class Info
**Location:** `framework/Util/Log/TFileLogRoute.php`
**Namespace:** `Prado\Util\Log`
**Extends:** [`TLogRoute`](./TLogRoute.md)

## Overview
`TFileLogRoute` appends formatted log messages to a file in the configured log directory. When the file exceeds `MaxFileSize`, it rotates existing files (`.1`, `.2`, …, up to `MaxLogFiles`) before continuing. The log path defaults to the application runtime directory.

## Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `LogPath` | `string` | app runtime path | Directory where log files are written. Accepts Prado namespace path format. |
| `LogFile` | `string` | `'prado.log'` | Log file name. |
| `MaxFileSize` | `int` | `512` (KB) | Maximum file size in kilobytes before rotation is triggered. |
| `MaxLogFiles` | `int` | `2` | Number of rotated backup files to keep. |

## Configuration

Configured as a `<route>` sub-element inside a `TLogRouter` module, not as a standalone module.

**application.xml:**
```xml
<modules>
  <module id="log" class="Prado\Util\Log\TLogRouter">
    <route class="Prado\Util\Log\TFileLogRoute" Levels="error,warning" LogFile="app.log" />
  </module>
</modules>
```

**PHP equivalent:**
```php
return [
    'modules' => [
        'log' => [
            'class' => 'Prado\Util\Log\TLogRouter',
            'routes' => [
                ['class' => 'Prado\Util\Log\TFileLogRoute', 'properties' => ['Levels' => 'error,warning', 'LogFile' => 'app.log']],
            ],
        ],
    ],
];
```

## See Also

- [`TLogRoute`](./TLogRoute.md) — abstract base class
- [`TLogRouter`](./TLogRouter.md) — module that manages all log routes
