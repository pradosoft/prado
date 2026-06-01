# Util/TXmlRpcClient

### Directories
[framework](../INDEX.md) / [Util](./INDEX.md) / **`TXmlRpcClient`**

## Class Info
**Location:** `framework/Util/TXmlRpcClient.php`
**Namespace:** `Prado\Util`
**Extends:** [`TRpcClient`](./TRpcClient.md)
**Since:** 3.2

## Overview
`TXmlRpcClient` implements an XML-RPC client built on PHP's native `xmlrpc_*` extension. Method calls are made via PHP's magic `__call`. Fault detection uses `xmlrpc_is_fault()` and propagates the fault code and message via [`TRpcClientResponseException`](./TRpcClientResponseException.md).

## Key Methods

| Method | Description |
|--------|-------------|
| `__call(string $method, array $params): mixed` | Calls a remote method; throws [`TRpcClientResponseException`](./TRpcClientResponseException.md) on an XML-RPC fault. |
| `encodeRequest(string $method, array $params): string` | Delegates to PHP's `xmlrpc_encode_request()`; content-type is `text/xml`. |

## See Also

- [`TRpcClient`](./TRpcClient.md) — abstract base class and factory
- [`TJsonRpcClient`](./TJsonRpcClient.md) — JSON-RPC alternative
- [`TRpcClientTypesEnumerable`](./TRpcClientTypesEnumerable.md) — type constant `XML`
