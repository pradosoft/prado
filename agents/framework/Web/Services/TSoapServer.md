# TSoapServer

### Directories

[./](../INDEX.md) > [Web](../INDEX.md) > [Services](./INDEX.md) > [TSoapServer](./TSoapServer.md)

**Location:** `framework/Web/Services/TSoapServer.php`
**Namespace:** `Prado\Web\Services`

## Overview

TSoapServer wraps PHP's SoapServer class to provide SOAP/WSDL web services. It associates a provider class with the SOAP server, manages service URIs, and auto-generates WSDL from provider classes using reflection. Supports SOAP 1.1 and 1.2, session persistence, and WSDL caching.

## Key Properties/Methods

- **`getID()` / `setID($id)`** - Gets/sets the SOAP server ID.
- **`getProvider()` / `setProvider($provider)`** - Gets/sets the SOAP provider class (fully namespaced).
- **`getUri()` / `setUri($uri)`** - Gets/sets the SOAP service URI.
- **`getWsdlUri()` / `setWsdlUri($value)`** - Gets/sets the WSDL URI or auto-generates from provider.
- **`getWsdl()`** - Returns WSDL content, either from file or auto-generated from provider class.
- **`getVersion()` / `setVersion($value)`** - Gets/sets SOAP version ('1.1', '1.2', or empty).
- **`getActor()` / `setActor($value)`** - Gets/sets the SOAP actor.
- **`getEncoding()` / `setEncoding($value)`** - Gets/sets the encoding.
- **`getSessionPersistent()` / `setSessionPersistent($value)`** - Gets/sets session persistence (SOAP_PERSISTENCE_SESSION).
- **`getClassMaps()` / `setClassMaps($classes)`** - Gets/sets complex type class mappings.
- **`run()`** - Handles the SOAP request.
- **`fault($title, $details, $code, $actor, $name)`** - Generates a SOAP fault message.
- **`getRequestedMethod()`** - Returns the SOAP method requested from the message.

## See Also

- [TSoapService](../TSoapService.md) - The SOAP service
- [WsdlGenerator](../../Xml/WsdlGenerator.md) - WSDL auto-generation
