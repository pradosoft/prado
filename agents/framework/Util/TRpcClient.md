# Util/TRpcClient

### Directories
[framework](../INDEX.md) / [Util](./INDEX.md) / **`TRpcClient`**

## Class Info
**Location:** `framework/Util/TRpcClient.php`
**Namespace:** `Prado\Util`
**Extends:** [`TApplicationComponent`](../TApplicationComponent.md)
**Since:** 3.2

## Overview
`TRpcClient` is the abstract base for JSON-RPC and XML-RPC client implementations. It manages the server URL, notification mode, and HTTP transport. The static factory `create()` selects the correct concrete subclass from [`TRpcClientTypesEnumerable`](./TRpcClientTypesEnumerable.md).

## Properties

| Property | Type | Description |
|----------|------|-------------|
| `ServerUrl` | `string` | URL of the remote RPC server. |
| `IsNotification` | `bool` | When `true`, requests are fire-and-forget (responses are discarded). |

## Static Factory

```php
$client = TRpcClient::create('json', 'http://api.example.com/rpc');
$result = $client->someMethod($arg1, $arg2);
```

`create(string $type, string $serverUrl, bool $isNotification = false): TRpcClient` — resolves `$type` against `TRpcClientTypesEnumerable` (`'json'` or `'xml'`) and returns the matching concrete client.

## See Also

- [`TJsonRpcClient`](./TJsonRpcClient.md) — JSON-RPC implementation
- [`TXmlRpcClient`](./TXmlRpcClient.md) — XML-RPC implementation
- [`TRpcClientTypesEnumerable`](./TRpcClientTypesEnumerable.md) — type registry
- [`TRpcClientRequestException`](./TRpcClientRequestException.md) — transport error
- [`TRpcClientResponseException`](./TRpcClientResponseException.md) — server-side RPC error
