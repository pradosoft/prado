# Util/TEmailLogRoute

### Directories
[framework](../INDEX.md) / [Util](./INDEX.md) / **`TEmailLogRoute`**

## Class Info
**Location:** `framework/Util/TEmailLogRoute.php`
**Namespace:** `Prado\Util`
**Extends:** [`TLogRoute`](./TLogRoute.md)

## Overview
`TEmailLogRoute` sends accumulated log messages as a single plain-text email to one or more recipients using PHP's `mail()` function. It validates email addresses on assignment and requires a sender address either via the `SentFrom` property or the `sendmail_from` PHP INI setting.

## Constants

| Constant | Value |
|----------|-------|
| `DEFAULT_SUBJECT` | `'Prado Application Log'` |

## Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `Emails` | `array` | `[]` | Destination addresses; accepts a comma-separated string or array. |
| `Subject` | `string` | `DEFAULT_SUBJECT` | Email subject line. |
| `SentFrom` | `string` | `''` | Sender address; falls back to `sendmail_from` INI setting if empty. |

## Configuration

Configured as a `<route>` sub-element inside a `TLogRouter` module, not as a standalone module.

**application.xml:**
```xml
<modules>
  <module id="log" class="Prado\Util\TLogRouter">
    <route class="Prado\Util\TEmailLogRoute" Emails="admin@example.com" Subject="Application Error" SentFrom="app@example.com" Levels="Error|Fatal" />
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
                ['class' => 'Prado\Util\TEmailLogRoute', 'properties' => ['Emails' => 'admin@example.com', 'Subject' => 'Application Error', 'SentFrom' => 'app@example.com']],
            ],
        ],
    ],
];
```

## See Also

- [`TLogRoute`](./TLogRoute.md) — abstract base class
- [`TLogRouter`](./TLogRouter.md) — module that manages all log routes
