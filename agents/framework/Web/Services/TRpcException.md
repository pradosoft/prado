# TRpcException

### Directories

[./](../INDEX.md) > [Web](../INDEX.md) > [Services](./INDEX.md) > [TRpcException](./TRpcException.md)

**Location:** `framework/Web/Services/TRpcException.php`
**Namespace:** `Prado\Web\Services`

## Overview

TRpcException represents an RPC fault error caused by invalid input data from the client. It extends TException and is used to signal method-not-found, internal errors, and protocol-level errors in RPC responses.

## Key Properties/Methods

- **`__construct($message, $errorCode = -1)`** - Creates an RPC exception with a message and numeric error code.

## See Also

- [TRpcProtocol](./TRpcProtocol.md) - Uses this exception for error responses
- [TJsonRpcProtocol](./TJsonRpcProtocol.md) - JSON-RPC error handling
- [TXmlRpcProtocol](./TXmlRpcProtocol.md) - XML-RPC error handling
