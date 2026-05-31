# Util/TStdOutLogRoute

### Directories
[framework](../INDEX.md) / [Util](./INDEX.md) / **`TStdOutLogRoute`**

## Class Info
**Location:** `framework/Util/TStdOutLogRoute.php`
**Namespace:** `Prado\Util`
**Extends:** [`TLogRoute`](./TLogRoute.md)
**Since:** 4.3.0

## Overview
`TStdOutLogRoute` writes colorized log entries to STDOUT using `TShellWriter`, making Prado logs visible in the PHP built-in web server's terminal output or in CLI applications. Each log line carries a color-coded level badge. The `OnlyDevServer` property restricts activation to the PHP built-in web server, preventing console pollution in production.

## Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `OnlyDevServer` | `bool` | `false` | When `true`, the route is active only inside the PHP built-in web server. |

## Configuration

Configured as a `<route>` sub-element inside a `TLogRouter` module, not as a standalone module.

**application.xml:**
```xml
<modules>
  <module id="log" class="Prado\Util\TLogRouter">
    <route class="Prado\Util\TStdOutLogRoute" Levels="Debug|Info|Warning|Error" />
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
                ['class' => 'Prado\Util\TStdOutLogRoute', 'properties' => ['Levels' => 'Debug|Info|Warning|Error']],
            ],
        ],
    ],
];
```

## See Also

- [`TLogRoute`](./TLogRoute.md) — abstract base class
- [`TSysLogRoute`](./TSysLogRoute.md) — syslog-based output route
- [`TLogRouter`](./TLogRouter.md) — module that manages all log routes
