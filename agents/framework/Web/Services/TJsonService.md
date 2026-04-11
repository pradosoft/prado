# Web/Services/TJsonService

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [Services](./INDEX.md) / **`TJsonService`**

## Class Info
**Location:** `framework/Web/Services/TJsonService.php`
**Namespace:** `Prado\Web\Services`

## Overview
TJsonService provides JavaScript content response in JSON format for end-users. It manages a set of TJsonResponse objects, each representing specific JSON response content.

## Key Features
- Manages multiple JSON response services
- Configurable through XML or PHP formats
- Supports JSON encoding of content using TJavaScript::jsonEncode
- Handles service initialization and execution

## Configuration
### XML Format
```xml
<service id="json" class="Prado\Web\Services\TJsonService">
  <json id="get_article" class="Path\To\JsonResponseClass1" />
  <json id="register_rating" class="Path\To\JsonResponseClass2" />
</service>
```

### PHP Format
```php
'services' => array(
  'get_article' => array(
    'class' => 'Path\To\JsonResponseClass1',
    'properties' => array(
      ...
    )
  )
)
```

## Methods

### Service Management
- `init($xml)` - Initializes the service with configuration
- `run()` - Runs the service, handling JSON content requests
- `loadJsonServices()` - Loads service definitions from configuration
- `createJsonResponse()` - Renders content provided by TJsonResponse::getJsonContent() as JSON

### Request Handling
- Uses service parameter to determine which JSON response to provide
- Supports URL format: `index.php?json=get_article`

## Response Requirements
- JSON response classes must implement TJsonResponse interface
- Response classes must implement `getJsonContent()` method
- Content is encoded using TJavaScript::jsonEncode before sending