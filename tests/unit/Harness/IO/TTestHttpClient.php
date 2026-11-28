<?php

namespace Prado\Test\Unit\Harness\IO;

use Prado\IO\Http\TResponse;
use Prado\TComponent;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * TTestHttpClient is a recording PSR-18 {@see ClientInterface} for unit tests.
 *
 * {@see sendRequest()} records each request in {@see $calls} and returns the queued
 * responses in order; with none queued it returns a 200 with an empty JSON object.
 * {@see $throw} is thrown by the next call instead.  Each call records the `method`,
 * the `url`, the `headers` as name => joined line, the `body` string, and the `request`.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTestHttpClient extends TComponent implements ClientInterface
{
	/** @var array<int, array{method: string, url: string, headers: array<string, string>, body: string, request: RequestInterface}> the recorded requests. */
	public array $calls = [];

	/** @var ResponseInterface[] the responses to return, in order. */
	public array $responses = [];

	/** The exception the next call throws, if set. */
	public ?\Throwable $throw = null;

	public function sendRequest(RequestInterface $request): ResponseInterface
	{
		$headers = [];
		foreach (array_keys($request->getHeaders()) as $name) {
			$headers[$name] = $request->getHeaderLine($name);
		}
		$body = $request->getBody();
		if ($body->isSeekable()) {
			$body->rewind();
		}
		$this->calls[] = [
			'method' => $request->getMethod(),
			'url' => (string) $request->getUri(),
			'headers' => $headers,
			'body' => $body->getContents(),
			'request' => $request,
		];
		if ($this->throw !== null) {
			$e = $this->throw;
			$this->throw = null;
			throw $e;
		}
		return array_shift($this->responses) ?? new TResponse(200, [], '{}');
	}
}
