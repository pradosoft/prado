# Util/TSysLogRoute

### Directories
[framework](../INDEX.md) / [Util](./INDEX.md) / **`TSysLogRoute`**

## Class Info
**Location:** `framework/Util/TSysLogRoute.php`
**Namespace:** `Prado\Util`
**Extends:** [`TLogRoute`](./TLogRoute.md)
**Since:** 4.3.0

## Overview
`TSysLogRoute` forwards log entries to the operating system's syslog daemon via `openlog()` / `syslog()` / `closelog()`. Prado log levels are translated to POSIX syslog priorities. The prefix, flags, and facility are all configurable by name string or integer constant.

## Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `SysLogPrefix` | `string\|false` | `false` | Identity string passed as `$ident` to `openlog()`; `false` omits the ident. |
| `SysLogFlags` | `int` | `LOG_ODELAY\|LOG_PID` | `openlog()` option flags; accepts int, comma/pipe-delimited string, or array of `LOG_*` names (`LOG_CONS`, `LOG_NDELAY`, `LOG_ODELAY`, `LOG_PERROR`, `LOG_PID`). |
| `Facility` | `int` | `LOG_USER` | `openlog()` facility; accepts int or a `LOG_*` name string (`LOG_AUTH`, `LOG_DAEMON`, `LOG_USER`, etc.). |

## Level Mapping

| Prado Level | Syslog Priority |
|-------------|----------------|
| Debug | `LOG_DEBUG` |
| Info | `LOG_INFO` |
| Notice | `LOG_NOTICE` |
| Warning | `LOG_WARNING` |
| Error | `LOG_ERR` |
| Alert | `LOG_ALERT` |
| Fatal | `LOG_CRIT` |

## Configuration

Configured as a `<route>` sub-element inside a `TLogRouter` module, not as a standalone module.

**application.xml:**
```xml
<modules>
  <module id="log" class="Prado\Util\TLogRouter">
    <route class="Prado\Util\TSysLogRoute" SysLogPrefix="myapp" Levels="Error|Fatal" />
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
                ['class' => 'Prado\Util\TSysLogRoute', 'properties' => ['SysLogPrefix' => 'myapp', 'Levels' => 'Error|Fatal']],
            ],
        ],
    ],
];
```

## See Also

- [`TLogRoute`](./TLogRoute.md) — abstract base class
- [`TStdOutLogRoute`](./TStdOutLogRoute.md) — stdout-based output route
- [`TLogRouter`](./TLogRouter.md) — module that manages all log routes
