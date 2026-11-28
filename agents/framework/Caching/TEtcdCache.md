# Caching/TEtcdCache

### Directories
[framework](../INDEX.md) / [Caching](./INDEX.md) / **`TEtcdCache`**

## Class Info
**Location:** `framework/Caching/TEtcdCache.php`
**Namespace:** `Prado\Caching`

## Overview
[TEtcdCache](./TEtcdCache.md) provides caching using the etcd distributed key-value store via HTTP API v2. It extends `TSerializingCache` and sends each request through a `Prado\IO\HttpClient\THttpClient`.

## Configuration

```xml
<modules>
    <module id="cache" class="Prado\Caching\TEtcdCache" 
            Host="localhost" Port="2379" Dir="pradocache" />
</modules>
```

**PHP equivalent:**
```php
return [
    'modules' => [
        'cache' => [
            'class' => 'Prado\Caching\TEtcdCache',
            'properties' => ['Host' => 'localhost', 'Port' => 2379, 'Dir' => 'pradocache'],
        ],
    ],
];
```

## Properties

- `Host` - etcd server host (default: `localhost`)
- `Port` - etcd server port (default: `2379`)
- `Dir` - Key prefix directory (default: `pradocache`)
- `HttpClient` - The PSR-18 client; created on first use by `createHttpClient()` as a `THttpClient` with `FollowRedirects` off. Settable at any time, e.g. to change `Timeout` (default 30 s).

`Host`, `Port` and `Dir` are frozen after `init()`.

## Protocol

| Operation | Request | Success |
|---|---|---|
| get | `GET /v2/keys/{Dir}/{key}` | no `errorCode`; value in `node.value` |
| set | `PUT` form body `value`, `ttl` when expire > 0 | no `errorCode` |
| add | `PUT` form body `value`, `prevExist=false`, `ttl` | no `errorCode` (412 / 105 when present) |
| delete | `DELETE /v2/keys/{Dir}/{key}` | always `true` |
| flush | `DELETE /v2/keys/{Dir}?recursive=true` | always `true` |

- `request()` returns the decoded `\stdClass`. A body that is not a JSON object → `(object)['errorCode' => <HTTP status>, ...]`, so it reads as a miss/failure.
- Unreachable server → the client's `ClientExceptionInterface` (a `THttpNetworkException` by default) propagates.
- Form body and `Content-Type: application/x-www-form-urlencoded` are sent only when parameters exist.

## Requirements

- An HTTP handler, unless an `HttpClient` is set. `getIsAvailable()` asks `THttpClient::getHasAvailableHandler()`, which is true when any handler in `THttpClient::HANDLERS` reports itself available; `TSocketHttpHandler` needs only `stream_socket_client()`. Without one, `init()` throws `etcdcache_transport_required`.
- etcd v2 server running

## Testing

`tests/unit/Caching/TEtcdCacheTest.php` drives the protocol through the recording `TTestHttpClient` harness injected with `setHttpClient()`; no etcd server is needed.

## See Also

- [TCache](./TCache.md) for full caching documentation