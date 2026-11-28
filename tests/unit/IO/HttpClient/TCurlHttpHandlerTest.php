<?php

namespace Prado\Test\Unit\IO\HttpClient;

use Prado\IO\HttpClient\IHttpHandler;
use Prado\IO\HttpClient\TCurlHttpHandler;

class TCurlHttpHandlerTest extends HttpHandlerConformanceTestCase
{
	protected function createHandler(): IHttpHandler
	{
		return new TCurlHttpHandler();
	}
}
