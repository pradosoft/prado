<?php

/**
 * THttpClientOption class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\HttpClient;

use Prado\TEnumerable;

/**
 * THttpClientOption class.
 *
 * THttpClientOption names the keys of the `$options` array that {@see THttpClient::send()},
 * {@see THttpClient::request()}, and every {@see IHttpHandler} accept.  An option given to
 * a call overrides the client's property of the same meaning.
 *
 * | Key | Type | Meaning |
 * |---|---|---|
 * | {@see TIMEOUT} | float | Seconds for the whole transfer; 0 for no limit. |
 * | {@see CONNECT_TIMEOUT} | float | Seconds to connect; 0 for no limit. |
 * | {@see VERIFY} | bool\|string | Whether to verify the TLS peer, or the CA file or directory to verify it with. |
 * | {@see DECODE_CONTENT} | bool | Whether to ask for and decode a compressed body (`gzip`, `deflate`). |
 * | {@see SINK} | StreamInterface\|resource\|string | Where the response body is written: a stream, a stream resource, or a file path. |
 * | {@see PROGRESS} | callable | Called as `(int $downloadTotal, int $downloaded, int $uploadTotal, int $uploaded)`; a total is 0 when unknown. |
 * | {@see TOKEN} | {@see \Prado\IO\Async\TCancellationToken} | Cancels the transfer. |
 * | {@see ALLOW_REDIRECTS} | bool | Whether the client follows redirects. |
 * | {@see MAX_REDIRECTS} | int | The longest redirect chain the client follows. |
 * | {@see HTTP_ERRORS} | bool | Whether {@see THttpClient::send()} throws {@see THttpResponseException} for a 4xx or 5xx response. |
 * | {@see HEADERS} | array | {@see THttpClient::request()}: headers by name. |
 * | {@see BODY} | mixed | {@see THttpClient::request()}: the body as a string, stream, or resource. |
 * | {@see JSON} | mixed | {@see THttpClient::request()}: a value sent as a JSON body. |
 * | {@see FORM_PARAMS} | array | {@see THttpClient::request()}: fields sent as a URL-encoded form body. |
 * | {@see QUERY} | array\|string | {@see THttpClient::request()}: the query string, replacing the URI's. |
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class THttpClientOption extends TEnumerable
{
	public const TIMEOUT = 'timeout';
	public const CONNECT_TIMEOUT = 'connect_timeout';
	public const VERIFY = 'verify';
	public const DECODE_CONTENT = 'decode_content';
	public const SINK = 'sink';
	public const PROGRESS = 'progress';
	public const TOKEN = 'token';
	public const ALLOW_REDIRECTS = 'allow_redirects';
	public const MAX_REDIRECTS = 'max_redirects';
	public const HTTP_ERRORS = 'http_errors';
	public const HEADERS = 'headers';
	public const BODY = 'body';
	public const JSON = 'json';
	public const FORM_PARAMS = 'form_params';
	public const QUERY = 'query';
}
