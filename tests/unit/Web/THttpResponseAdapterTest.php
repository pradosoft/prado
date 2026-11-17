<?php

namespace Prado\Test\Unit\Web;

use Prado\Exceptions\TExitException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Test\Unit\Harness\TTestApplication;
use Prado\Web\THttpResponse;
use Prado\Web\THttpResponseAdapter;
use Prado\Web\UI\THtmlWriter;

class THttpResponseAdapterTest extends \PHPUnit\Framework\TestCase
{
	protected ?TTestApplication $app = null;

	protected function setUp(): void
	{
		$this->app = new TTestApplication(__DIR__ . '/app');
	}

	protected function tearDown(): void
	{
		if ($this->app !== null) {
			$this->app->restoreApplication();
			$this->app = null;
		}
	}

	private function newResponse(): THttpResponse
	{
		return new class () extends THttpResponse {
			public array $headers = [];
			public array $redirects = [];

			public function httpRedirect($url)
			{
				$this->redirects[] = $url;
			}

			public function appendHeader($header, bool $replace = true, int $response_code = 0): void
			{
				$this->headers[] = $header;
			}
		};
	}

	public function testGetResponse()
	{
		$response = $this->newResponse();
		$adapter = new THttpResponseAdapter($response);
		$this->assertSame($response, $adapter->getResponse());
	}

	public function testFlushContentFlushesTheResponse()
	{
		$response = $this->newResponse();
		$response->setBufferOutput(false);
		$response->setAdapter($adapter = new THttpResponseAdapter($response));
		$adapter->flushContent(false);
		$this->assertTrue($response->getHttpHeaderSent());
		$this->assertSame('HTTP/1.1 200 OK', $response->headers[0]);
	}

	public function testHttpRedirectRedirectsTheResponse()
	{
		$response = $this->newResponse();
		$response->setAdapter($adapter = new THttpResponseAdapter($response));
		$response->redirect('https://elsewhere.example/');
		$adapter->httpRedirect('/page');
		$this->assertSame(['https://elsewhere.example/', '/page'], $response->redirects);
	}

	public function testHttpRedirectEndsTheRequest()
	{
		$response = new class () extends THttpResponse {
			public array $headers = [];

			public function appendHeader($header, bool $replace = true, int $response_code = 0): void
			{
				$this->headers[] = $header;
			}
		};
		$adapter = new THttpResponseAdapter($response);
		try {
			$adapter->httpRedirect('https://elsewhere.example/');
			$this->fail('httpRedirect() must end the request.');
		} catch (TExitException $e) {
		}
		$this->assertContains('Location: https://elsewhere.example/', $response->headers);
	}

	public function testCreateNewHtmlWriterUsesTheResponse()
	{
		$response = $this->newResponse();
		$adapter = new THttpResponseAdapter($response);
		$writer = $adapter->createNewHtmlWriter(THtmlWriter::class, $response);
		$this->assertInstanceOf(THtmlWriter::class, $writer);
	}

	public function testSetResponseDataThrows()
	{
		$adapter = new THttpResponseAdapter($this->newResponse());
		$this->expectException(TInvalidOperationException::class);
		$this->expectExceptionMessageMatches('/setResponseData/');
		$adapter->setResponseData(['a' => 1]);
	}

	public function testGetResponseDataThrows()
	{
		$adapter = new THttpResponseAdapter($this->newResponse());
		$this->expectException(TInvalidOperationException::class);
		$this->expectExceptionMessageMatches('/getResponseData/');
		$adapter->getResponseData();
	}
}
