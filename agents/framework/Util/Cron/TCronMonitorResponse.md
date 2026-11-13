# Util/Cron/TCronMonitorResponse

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Cron](./INDEX.md) / **`TCronMonitorResponse`**

## Class Info
**Location:** `framework/Util/Cron/TCronMonitorResponse.php`
**Namespace:** `Prado\Util\Cron`
**Extends:** `TJsonResponse` ([TJsonService](../../Web/Services/TJsonService.md))
**Since:** 4.4.0

## Overview
The monitoring endpoint of a [`TCronModule`](TCronModule.md#chains-priority-and-monitoring-440): a `TJsonService` provider that answers with `getMonitorReport()` as JSON, or with `getMonitorMetrics()` in the Prometheus text format (`text/plain; version=0.0.4`, written directly, so `getJsonContent()` returns null).

```xml
<service id="json" class="Prado\Web\Services\TJsonService">
  <json id="cron" class="Prado\Util\Cron\TCronMonitorResponse" />
  <json id="cronmetrics" class="Prado\Util\Cron\TCronMonitorResponse" Format="prometheus" />
</service>
```

`index.php?json=cron` is the report; `index.php?json=cronmetrics` the metrics.

| Property | Default | Meaning |
|---|---|---|
| `CronModuleID` | null | The cron module; null for the first `TCronModule` of the application. An unknown module throws `cronmonitor_module_invalid`. |
| `Tag` | null | The tag of the reported tasks (`TCronTask::Tags`); null for all tasks. |
| `Format` | `json` | `json` (`FORMAT_JSON`) or `prometheus` (`FORMAT_PROMETHEUS`). |

The report lists the tasks the requesting user may read (`cron_read_tasks`); restrict the service to monitoring users with the application's authorization rules.

## See Also

- [TCronModule](TCronModule.md)
