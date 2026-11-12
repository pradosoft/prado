# Util/Cron/THttpCronTask

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Cron](./INDEX.md) / **`THttpCronTask`**

## Class Info
**Location:** `framework/Util/Cron/THttpCronTask.php`
**Namespace:** `Prado\Util\Cron`
**Extends:** [`TCronTask`](TCronTask.md)
**Since:** 4.4.0

## Overview
Sends an HTTP request on each trigger: a page warm-up, a webhook, or a maintenance URL of another service. A 2xx status is a success; any other throws a `TIOException` (`httpcrontask_status`), so the run's result is an `error`.

```xml
<job name="warm" schedule="0 * * * *" task="Prado\Util\Cron\THttpCronTask" Url="https://example.com/sitemap.xml" />
<job name="hook" schedule="0 6 * * *" task="Prado\Util\Cron\THttpCronTask" Method="POST"
     Url="https://hooks.example.com/daily" Headers="Content-Type: application/json|Authorization: Bearer TOKEN"
     Body='{"event":"daily"}' />
```

## Properties

| Property | Default | Meaning |
|---|---|---|
| `Url` | null | The URL; none throws `httpcrontask_no_url`. |
| `Method` | `GET` | The HTTP method, uppercased. |
| `Headers` | `[]` | An array by name, or `Name: value` lines separated by newlines or `|`; a line without a name throws `httpcrontask_header_invalid`. |
| `Body` | null | The request body. |
| `LogFile` | null | Each run appends the response body. |
| `HttpClient` | `THttpClient::create()` | The client; set a `THttpClient` to change the transport. Not serialized. |
| `StatusCode` | null | Read-only: the status of the last run in this process. |

The task's `Timeout`, else the module's `TaskTimeout`, rounded up to whole seconds, is the client's timeout. The request blocks the fiber; give a slow endpoint a Parallel or Process mode.

## See Also

- [TCronTask](TCronTask.md)
- [TCommandCronTask](TCommandCronTask.md)
- [IO/HttpClient](../../IO/HttpClient/INDEX.md)
