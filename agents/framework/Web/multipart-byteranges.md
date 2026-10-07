# Web/multipart-byteranges — Research: multiple-range responses

### Directories
[framework](../INDEX.md) / [Web](./INDEX.md) / **`multipart-byteranges`**

**Status:** not implemented. Wanted eventually; see [Feature request](#feature-request).
**Related:** [THttpResponse](THttpResponse.md) (`writeFile()`, `dyWriteFile`), [THttpHeaderRange](HttpHeaders/THttpHeaderRange.md) (`resolve()`).
**Written:** 2026-10-06, during the 4.4.0 Range/206 work.

## Current behavior (4.4.0)

`THttpHeaderRange::resolve()` clamps each range to the size, drops unsatisfiable ranges, sorts the rest, and merges ranges that overlap or adjoin (`next.start <= end + 1`).

| Ranges after merging | `writeFile()` response |
|---|---|
| one span | `206` with `Content-Range: bytes s-e/size` |
| two or more disjoint spans | `200` with the full file |
| none satisfiable | `416` with `Content-Range: bytes */size` |

RFC 9110 §14.2 permits a server to ignore `Range`, so the `200` fallback is correct. The client receives more bytes than it asked for.

## What multipart would add

A request such as `Range: bytes=0-99,5000-5099` would get one `206` whose body holds each span as a part (RFC 9110 §14.6):

```
HTTP/1.1 206 Partial Content
Content-Type: multipart/byteranges; boundary=THIS_STRING_SEPARATES
Content-Length: <exact total>

--THIS_STRING_SEPARATES
Content-Type: application/pdf
Content-Range: bytes 0-99/120000

<100 bytes>
--THIS_STRING_SEPARATES
Content-Type: application/pdf
Content-Range: bytes 5000-5099/120000

<100 bytes>
--THIS_STRING_SEPARATES--
```

## Who sends multiple ranges

| Client | Range requests |
|---|---|
| Browser media seeking (`<video>`, `<audio>`) | one open range, `bytes=N-` |
| Resumed downloads | one range |
| Download managers | several connections, one range each |
| pdf.js | one range per request |
| Adobe Acrobat / Reader, **Fast Web View** | several ranges in one request |

**Use case: large PDFs with Fast Web View.** A Fast Web View PDF is linearized. Acrobat reads the first page, then fetches the objects for the page being viewed. It asks for several byte ranges in one request, so a large PDF opens without downloading in full. Under the `200` fallback the PDF still opens, but Acrobat downloads the whole file first. This is the main case where multipart saves real bandwidth and time.

## Available today: hand the file to the web server with `dyWriteFile`

`writeFile()` raises `dyWriteFile` before it sends any header. A behavior that returns `true` along the chain hands the file to the web server. Apache and nginx then serve `Range` themselves, multipart included. They run in C and do not hold a PHP worker for the transfer. This is the recommended path for any app that serves large files, PDFs to Acrobat included.

```php
use Prado\Util\TBehavior;
use Prado\Web\THttpHeaderName;

class TXSendfileBehavior extends TBehavior
{
	public function dyWriteFile($handled, $fileName, $content, $mimeType, $headers, $forceDownload, $clientFileName, $fileSize, $chain)
	{
		if (!$handled && $content === null) {
			$response = $this->getOwner();
			$response->appendHeader('X-Sendfile: ' . realpath($fileName));
			$response->appendHeader(THttpHeaderName::ContentType . ': ' . $mimeType);
			$response->appendHeader(THttpHeaderName::ContentDisposition . ': '
				. ($forceDownload ? 'attachment' : 'inline') . '; filename="' . $clientFileName . '"');
			$handled = true;
		}
		return $chain->dyWriteFile($handled, $fileName, $content, $mimeType, $headers, $forceDownload, $clientFileName, $fileSize);
	}
}
```

| Server | Handoff header | Value |
|---|---|---|
| Apache + `mod_xsendfile` | `X-Sendfile` | absolute file path |
| nginx | `X-Accel-Redirect` | URI of an `internal` location, not a file path |
| LiteSpeed | `X-LiteSpeed-Location` | URI or path, per server configuration |

- `$content` set → there is no file to hand off, so the behavior passes the call on unchanged.
- The behavior sends its own `Content-Type` and `Content-Disposition`; the server adds `Content-Length`, `Accept-Ranges`, and the range handling.

## Why it is deferred

1. **Low demand.** Of common clients, only Acrobat sends multiple ranges, and the `200` fallback works for it.
2. **The handoff is better for heavy byte serving.** A multipart PHP path would compete with the web server, which does this in C.
3. **Denial of service.** CVE-2011-3192 ("Apache Killer") sent hundreds of overlapping ranges in one request. RFC 9110 §17.15 asks servers to guard against this. The merge already absorbs overlap. Multipart also needs:
   - a cap on the number of spans;
   - a fallback when many tiny spans make the framing larger than the file.
4. **`$headers` conflict.** A caller's `$headers` replace the default headers, `Content-Type` included. Multipart needs `multipart/byteranges; boundary=…` at the top level, and the file's type moves into each part. `writeFile()` would have to find the caller's `Content-Type`, move it into the parts, and replace it at the top.
5. **Exact `Content-Length`.** The length counts every boundary, part header, and CRLF. A miscount corrupts the response.
6. **`TMultipartStream` does not fit.** It builds `multipart/form-data` and always writes `Content-Disposition: form-data` on each part. Byteranges parts have no `Content-Disposition`.

## Implementation sketch

1. **`THttpHeaderRange::resolveSpans(int $size): array|false|null`**
   - Returns the sorted, merged span list: `false` when none is satisfiable, `null` when the header is invalid or its unit is not `bytes`.
   - `resolve()` becomes "one span → it, several → null" on top of `resolveSpans()`. Its behavior stays the same.
2. **Abuse limits** on `THttpResponse`, for example a `MaxRanges` property:
   - more spans than the cap → `200` with the full file;
   - total framing larger than the bytes requested, or span bytes adding up to more than the file → `200` with the full file;
   - `MaxRanges` of `1` keeps the 4.4.0 behavior. The default is an open question.
3. **Content types:**
   - the part type is `$mimeType`, or the `Content-Type` in `$headers` (`findHeaderValue()`), which is then left out of the top-level headers;
   - the top level gets `multipart/byteranges; boundary=<random>`;
   - `Content-Disposition` stays at the top level.
4. **Exact length:**
   - sum, for each part, `--boundary\r\n` + the part headers + `\r\n\r\n` + the span length + `\r\n`;
   - add the closing `--boundary--\r\n`;
   - send the total as `Content-Length`.
5. **Streaming the body:**
   - Write the framing strings, and copy each span with `TStreamHelper::copyRange()` from one `TFileStream` into a `TOutputStream`. For `$content`, use `substr()` per span.
   - `copyRange()` seeks for each span, so one source handle serves all parts.
   - Composing a stream from `TAppendStream` and per-part `TLimitStream` does not work over one shared handle. `TLimitStream` seeks to its window only in its constructor, so every part would move the same file pointer. Each part would need its own handle.
6. **Unchanged:**
   - the `dyWriteFile` handoff runs first;
   - `If-Range` and `AcceptRanges` apply before span resolution;
   - a single merged span still gets a plain `206` with no multipart body.
7. **Tests:**
   - exact body bytes and `Content-Length` for two and three parts;
   - the part `Content-Type` taken from `$mimeType` and from `$headers`;
   - the cap fallback and the tiny-span fallback;
   - `$content` and file sources;
   - a single span stays non-multipart.

**Open questions:** the `MaxRanges` default; whether to tie multipart to `AcceptRanges` or give it its own switch; the boundary source (`random_bytes`), which must not appear in the file.

## Feature request

To do: open a GitHub issue on `pradosoft/prado`. Draft:

**Title:** `THttpResponse::writeFile()`: serve multiple byte ranges as `multipart/byteranges`

**Body:**
> 4.4.0 `writeFile()` serves one byte range (`206`/`416`). It merges ranges that overlap or adjoin, and sends the full file with `200` for disjoint ranges. Acrobat's Fast Web View requests several ranges at once to open large linearized PDFs page by page, so it currently downloads the whole PDF first.
>
> Proposal: when a `Range` request resolves to two or more disjoint spans, respond `206` with a `multipart/byteranges` body. Cap the span count, and fall back to `200` for abusive range sets (CVE-2011-3192). Move the file's `Content-Type` into the parts. Compute an exact `Content-Length`.
>
> Workaround today: a `dyWriteFile` behavior hands the file to the web server (`X-Sendfile`, `X-Accel-Redirect`), and the server serves multipart ranges.
>
> Design notes: `agents/framework/Web/multipart-byteranges.md`.

## References

- RFC 9110 §14.2 (Range), §14.6 (multipart/byteranges), §13.1.5 (If-Range), §17.15 (denial of service using Range).
- CVE-2011-3192, Apache httpd overlapping-range denial of service.
