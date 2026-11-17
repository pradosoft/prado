# Web/Services/TSoapService

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [Services](./INDEX.md) / **`TSoapService`**

## Class Info
**Location:** `framework/Web/Services/TSoapService.php`
**Namespace:** `Prado\Web\Services`

## Overview
TSoapService processes SOAP requests for a PRADO application. It requires PHP SOAP extension to be loaded and manages a set of SOAP providers.

## Key Features
- Manages multiple SOAP servers configured via application specification
- Generates WSDL automatically for SOAP providers
- Supports session persistence for SOAP providers
- Configurable through XML or PHP configuration formats
- `EnableCsrfValidation` (off by default) has `run()` refuse a SOAP POST without the CSRF token, sent in the `X-Prado-Csrf-Token` header, through `TService::ensureCsrfToken()` (4.4.0)

## Configuration

Registered as a service (`<services>` tag, not `<modules>`).

**application.xml:**
```xml
<services>
  <service id="soap" class="Prado\Web\Services\TSoapService">
    <soap id="stockquote" provider="MyStockQuote" />
  </service>
</services>
```

**PHP equivalent:**
```php
return [
    'services' => [
        'soap' => ['class' => 'Prado\Web\Services\TSoapService'],
    ],
];
```

## Methods

### Service Management
- `init($config)` - Initializes the service with configuration
- `run()` - Runs the service, handling WSDL requests or SOAP requests
- `createServer()` - Creates requested SOAP server with property values from configuration

### Request Handling
- `resolveRequest()` - Identifies server ID and whether request is for WSDL
- `constructUrl()` - Constructs URL with specified page path and GET parameters
- `getIsWsdlRequest()` - Returns whether this is a request for WSDL
- `getServerID()` - Returns the SOAP server ID

### Configuration
- `getConfigFile()` / `setConfigFile()` - Gets/sets the external configuration file, a namespace resolved with the application's configuration file extension. `init()` loads its servers before those of the service element: an XML document under XML configuration, and a PHP file returning an array (`['soap' => [id => ['class' => ..., 'properties' => [...]]]]`) under PHP configuration. A file that does not exist throws `soapservice_configfile_invalid`; one that holds no configuration throws `soapservice_configfile_unreadable`.
- `loadConfig()` - Loads servers from a PHP array or an XML element; a null configuration adds none.

## SOAP Provider Requirements
- Provider class must be specified with `@soapmethod` comment tags
- Methods with `@soapmethod` tag are exposed to SOAP clients
- Example:
```php
class MyStockQuote {
  /**
   * @param string $symbol the stock symbol
   * @return float the stock price
   * @soapmethod
   */
  public function getQuote($symbol) {...}
}
```

## Usage
```php
$client = new SoapClient("http://hostname/path/to/index.php?soap=stockquote.wsdl");
echo $client->getQuote("ibm");
```