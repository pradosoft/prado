# Util/TRpcClientResponseException

### Directories
[framework](../INDEX.md) / [Util](./INDEX.md) / **`TRpcClientResponseException`**

## Class Info
**Location:** `framework/Util/TRpcClientResponseException.php`
**Namespace:** `Prado\Util`
**Extends:** `TApplicationException`
**Since:** 3.2

## Overview
`TRpcClientResponseException` is thrown when the RPC server returns an application-level error — a non-null JSON `error` field or an XML-RPC fault response. It carries an optional numeric error code from the remote fault payload alongside the error message.

## Constructor

`__construct(string $errorMessage, ?int $errorCode = null)`

The error code from the remote fault is accessible via `getErrorCode()`.

## See Also

- [`TRpcClient`](./TRpcClient.md) — base class
- [`TRpcClientRequestException`](./TRpcClientRequestException.md) — raised on transport failure
