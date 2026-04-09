# TCronMethodTask

### Directories

[Util](../) > [Cron](Cron/) > TCronMethodTask

**Location:** `framework/Util/Cron/TCronMethodTask.php`
**Namespace:** `Prado\Util\Cron`

## Overview

TCronMethodTask is a concrete cron task that evaluates and executes a specific method with parameters on a module when the task is triggered. It extends TCronTask and allows configuring tasks via module ID and method strings, with optional parameters.

## Key Properties/Methods

- `getModuleId()` / `setModuleId($moduleId)` - The module or module ID to call the method on
- `getMethod()` / `setMethod($method)` - The method and parameters to call on the module
- `getTask()` - Returns the task string (module ID + method separator + method)
- `execute($cron)` - Retrieves the module and evaluates the method expression
- `validateTask()` - Validates the method exists on the module
- `getModule()` - Gets the module instance by module ID, throws if not found

## See Also

- [TCronTask](TCronTask.md)
- [TCronModule](../TCronModule.md)
