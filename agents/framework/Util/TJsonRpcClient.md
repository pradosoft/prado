# Util/TJsonRpcClient

### Directories
[framework](../INDEX.md) / [Util](./INDEX.md) / **`TJsonRpcClient`**

## Class Info
**Location:** `framework/Util/TJsonRpcClient.php`
**Namespace:** `Prado\Util`
**Extends:** [`TRpcClient`](./TRpcClient.md)
**Since:** 3.2

## Overview
`TJsonRpcClient` implements a JSON-RPC 1.0 client. Method calls are made via PHP's magic `__call`, which serialises the method name and parameters to JSON, sends the request, and decodes the response. When `IsNotification` is `true`, responses are silently discarded. A static per-request ID counter is maintained automatically.

## Key Methods

| Method | Description |
|--------|-------------|
| `__call(string $method, array $params): mixed` | Calls a remote method; throws [`TRpcClientResponseException`](./TRpcClientResponseException.md) on a JSON error response. |
| `encodeRequest(string $method, array $params): string` | Serialises method, params, and request ID to JSON. |

## See Also

- [`TRpcClient`](./TRpcClient.md) — abstract base class and factory
- [`TXmlRpcClient`](./TXmlRpcClient.md) — XML-RPC alternative
- [`TRpcClientTypesEnumerable`](./TRpcClientTypesEnumerable.md) — type constant `JSON`
