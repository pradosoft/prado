# Web/Services/TJsonResponse

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [Services](./INDEX.md) / **`TJsonResponse`**

## Class Info
**Location:** `framework/Web/Services/TJsonResponse.php`
**Namespace:** `Prado\Web\Services`

## Overview
TJsonResponse is the abstract base class for JSON response providers used by TJsonService. Derived classes implement `getJsonContent()` to return data that gets serialized to JSON format. The response can be suppressed by returning null.

## Key Properties/Methods

- **`getID()` / `setID($value)`** - Gets/sets the response identifier.
- **`init($config)`** - Initializes the response provider with configuration from TJsonService.
- **`getJsonContent()`** - Abstract method returning the JSON content object/null to suppress output.

## See Also

- [TJsonService](../TJsonService.md) - The JSON service that uses TJsonResponse providers
