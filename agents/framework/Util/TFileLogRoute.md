# Util/TFileLogRoute

### Directories
[framework](../INDEX.md) / [Util](./INDEX.md) / **`TFileLogRoute`**

## Class Info
**Location:** `framework/Util/TFileLogRoute.php`
**Namespace:** `Prado\Util`
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
  <module id="log" class="Prado\Util\TLogRouter">
    <route class="Prado\Util\TFileLogRoute" Levels="error,warning" LogFile="app.log" />
  </module>
</modules>
```

**PHP equivalent:**
```php
return [
    'modules' => [
        'log' => [
            'class' => 'Prado\Util\TLogRouter',
            'routes' => [
                ['class' => 'Prado\Util\TFileLogRoute', 'properties' => ['Levels' => 'error,warning', 'LogFile' => 'app.log']],
            ],
        ],
    ],
];
```

## See Also

- [`TLogRoute`](./TLogRoute.md) — abstract base class
- [`TLogRouter`](./TLogRouter.md) — module that manages all log routes
