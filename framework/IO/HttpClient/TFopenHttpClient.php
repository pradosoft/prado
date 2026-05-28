<?php

/**
 * TFopenHttpClient class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\HttpClient;

/**
 * TFopenHttpClient class.
 *
 * TFopenHttpClient implements {@see THttpClient} using PHP's built-in
 * HTTP stream wrapper (`file_get_contents()` with a `stream_context_create()`
 * context). It serves as the fallback transport when the cURL extension is
 * unavailable. The PHP runtime must have `allow_url_fopen` enabled for this
 * transport to function.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TFopenHttpClient extends THttpClient
{
	/**
	 * Performs an HTTP request using PHP stream wrappers.
	 *
	 * @param string $method HTTP verb.
	 * @param string $url Absolute URL to request.
	 * @param array<string,string> $headers Request headers.
	 * @param ?string $body Raw request body, or null.
	 * @throws THttpClientException on transport failure.
	 * @return THttpClientResponse
	 */
	public function download(string $method, string $url, array $headers = [], ?string $body = null): THttpClientResponse
	{
		$httpOptions = [
			'method' => strtoupper($method),
			'header' => $this->formatHeaderLines($headers),
			'timeout' => $this->getTimeout(),
			'ignore_errors' => true, // expose 4xx/5xx response bodies
			'follow_location' => $this->getFollowRedirects() ? 1 : 0,
			'max_redirects' => $this->getMaxRedirects(),
			'protocol_version' => 1.1,
		];

		if ($body !== null) {
			$httpOptions['content'] = $body;
		}

		$context = stream_context_create(['http' => $httpOptions, 'https' => $httpOptions]);

		// Pre-initialize $http_response_header — the HTTP stream wrapper
		// overwrites this local when the request reached the server, but
		// leaves it untouched on transport-level failure (DNS, connection
		// refused, etc.), in which case it would otherwise be undefined.
		$http_response_header = [];

		// Suppress the warning file_get_contents emits on transport failure;
		// the failure is reported explicitly below.
		$responseBody = @file_get_contents($url, false, $context);

		if ($http_response_header === []) {
			throw new THttpClientException('httpclient_transport_error', 0, "Failed to fetch '{$url}'.");
		}

		[$statusCode, $responseHeaders] = $this->parseResponseHeaders($http_response_header);

		return new THttpClientResponse($statusCode, $responseHeaders, (string) $responseBody);
	}

	/**
	 * Splits `$http_response_header` into its status code and header map.
	 *
	 * The HTTP stream wrapper presents the *complete* header history when
	 * `follow_location` is enabled — every intermediate redirect block plus
	 * the final response. Only the final block is relevant, so this method
	 * walks forward to the last status line and reads headers from there.
	 *
	 * @param string[] $rawHeaders Raw `$http_response_header` array.
	 * @return array{0: int, 1: array<string,string>} `[statusCode, headers]`.
	 */
	protected function parseResponseHeaders(array $rawHeaders): array
	{
		$statusCode = 0;
		$headers = [];

		foreach ($rawHeaders as $line) {
			if (str_starts_with($line, 'HTTP/')) {
				$statusCode = $this->parseStatusLine($line);
				$headers = []; // reset on each status line to keep only the final block
				continue;
			}
			$colon = strpos($line, ':');
			if ($colon === false) {
				continue;
			}
			$name = trim(substr($line, 0, $colon));
			$value = trim(substr($line, $colon + 1));
			if (isset($headers[$name])) {
				$headers[$name] .= ', ' . $value;
			} else {
				$headers[$name] = $value;
			}
		}

		return [$statusCode, $headers];
	}
}
