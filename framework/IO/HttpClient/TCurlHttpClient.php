<?php

/**
 * TCurlHttpClient class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\HttpClient;

/**
 * TCurlHttpClient class.
 *
 * TCurlHttpClient implements {@see THttpClient} on top of the PHP cURL
 * extension. It is the preferred transport when cURL is available, both
 * because it is significantly faster on warm connections and because it
 * supports finer-grained timeout and redirect control than the stream-based
 * fallback.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TCurlHttpClient extends THttpClient
{
	/**
	 * Performs an HTTP request using cURL.
	 *
	 * @param string $method HTTP verb.
	 * @param string $url Absolute URL to request.
	 * @param array<string,string> $headers Request headers.
	 * @param ?string $body Raw request body, or null.
	 * @throws THttpClientException on cURL-level failure.
	 * @return THttpClientResponse
	 */
	public function download(string $method, string $url, array $headers = [], ?string $body = null): THttpClientResponse
	{
		$ch = curl_init();
		try {
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $this->getFollowRedirects());
			curl_setopt($ch, CURLOPT_MAXREDIRS, $this->getMaxRedirects());
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->getTimeout());
			curl_setopt($ch, CURLOPT_TIMEOUT, $this->getTimeout());

			if ($headers !== []) {
				curl_setopt($ch, CURLOPT_HTTPHEADER, $this->formatHeaderLines($headers));
			}

			if ($body !== null) {
				curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
			}

			if (strtoupper($method) === 'HEAD') {
				curl_setopt($ch, CURLOPT_NOBODY, true);
			}

			// Collect response headers via a write-header callback so we can
			// preserve their order and handle duplicates predictably.
			$responseHeaders = [];
			curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $headerLine) use (&$responseHeaders) {
				$length = strlen($headerLine);
				$trimmed = trim($headerLine);
				// Skip status lines and empty separators between header blocks
				if ($trimmed === '' || str_starts_with($trimmed, 'HTTP/')) {
					return $length;
				}
				$colon = strpos($trimmed, ':');
				if ($colon !== false) {
					$name = trim(substr($trimmed, 0, $colon));
					$value = trim(substr($trimmed, $colon + 1));
					if (isset($responseHeaders[$name])) {
						$responseHeaders[$name] .= ', ' . $value;
					} else {
						$responseHeaders[$name] = $value;
					}
				}
				return $length;
			});

			$responseBody = curl_exec($ch);

			if ($responseBody === false) {
				throw new THttpClientException(
					'httpclient_transport_error',
					curl_errno($ch),
					curl_error($ch)
				);
			}

			$statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

			return new THttpClientResponse($statusCode, $responseHeaders, (string) $responseBody);
		} finally {
			curl_close($ch);
		}
	}
}
