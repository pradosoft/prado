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
- `Downloader` - `THttpClient` transport; created on first use by `createDownloader()` from `THttpClient::create()` with `FollowRedirects` off. Settable at any time, e.g. to change `Timeout` (default 30 s).

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
- Unreachable server → `THttpClientException` propagates.
- Form body and `Content-Type: application/x-www-form-urlencoded` are sent only when parameters exist.

## Requirements

- An HTTP transport: the PHP cURL extension or `allow_url_fopen` (`getIsAvailable()`); otherwise `init()` throws `etcdcache_transport_required`.
- etcd v2 server running

## Testing

`tests/unit/Caching/TEtcdCacheTest.php` drives the protocol through a recording `THttpClient` fixture injected with `setDownloader()`; no etcd server is needed.

## See Also

- [TCache](./TCache.md) for full caching documentation