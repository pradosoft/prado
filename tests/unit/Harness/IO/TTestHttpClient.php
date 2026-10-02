<?php

/**
 * TTestHttpClient class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Harness\IO;

use Prado\IO\HttpClient\THttpClient;
use Prado\IO\HttpClient\THttpClientResponse;

/**
 * TTestHttpClient is a recording {@see THttpClient} for unit tests.
 *
 * {@see download()} records each request in {@see $calls} and returns the queued
 * responses in order; with none queued it returns a 200 with an empty JSON object.
 * {@see $throw} is thrown by the next call instead.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTestHttpClient extends THttpClient
{
	/** @var array<int, array{method: string, url: string, headers: array, body: ?string}> the recorded requests. */
	public array $calls = [];

	/** @var THttpClientResponse[] the responses to return, in order. */
	public array $responses = [];

	/** The exception the next call throws, if set. */
	public ?\Throwable $throw = null;

	public function download(string $method, string $url, array $headers = [], ?string $body = null): THttpClientResponse
	{
		$this->calls[] = compact('method', 'url', 'headers', 'body');
		if ($this->throw !== null) {
			$e = $this->throw;
			$this->throw = null;
			throw $e;
		}
		return array_shift($this->responses) ?? new THttpClientResponse(200, [], '{}');
	}
}
