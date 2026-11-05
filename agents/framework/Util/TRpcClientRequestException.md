# Util/TRpcClientRequestException

### Directories
[framework](../INDEX.md) / [Util](./INDEX.md) / **`TRpcClientRequestException`**

## Class Info
**Location:** `framework/Util/TRpcClientRequestException.php`
**Namespace:** `Prado\Util`
**Extends:** `TApplicationException`
**Since:** 3.2

## Overview
`TRpcClientRequestException` is thrown when the HTTP transport layer fails — for example, when the server is unreachable or `file_get_contents()` returns `false`. It distinguishes network/transport errors from server-side RPC application errors (which throw [`TRpcClientResponseException`](./TRpcClientResponseException.md) instead).

## See Also

- [`TRpcClient`](./TRpcClient.md) — raises this exception on transport failure
- [`TRpcClientResponseException`](./TRpcClientResponseException.md) — raised on server-side RPC fault
