<?php

namespace Prado\Test\Unit\IO\HttpClient;

use Prado\IO\HttpClient\IHttpHandler;
use Prado\IO\HttpClient\TStreamHttpHandler;

class TStreamHttpHandlerTest extends HttpHandlerConformanceTestCase
{
	protected function createHandler(): IHttpHandler
	{
		return new TStreamHttpHandler();
	}

	protected function decodesContent(): bool
	{
		return false;
	}

	public function testSendsNoAcceptEncoding(): void
	{
		$echo = $this->echoed($this->send('GET', '/echo'));
		self::assertArrayNotHasKey('Accept-Encoding', $echo['headers']);
	}
}
