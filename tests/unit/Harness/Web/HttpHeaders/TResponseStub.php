<?php

namespace Prado\Test\Unit\Harness\Web\HttpHeaders;

/**
 * TResponseStub — minimal duck-typed THttpResponse stand-in for HttpHeaders tests.
 *
 * Captures `appendHeader()` calls
 * without requiring a full {@see Prado\TApplication} lifecycle.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 */

class TResponseStub
{
	/** @var list<array{header:string,replace:bool}> */
	public array $capturedCalls = [];

	public function appendHeader(string $header, bool $replace): void
	{
		$this->capturedCalls[] = compact('header', 'replace');
	}
}
