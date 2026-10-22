<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use Prado\Prado;
use Prado\TApplicationMode;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\THttpResponse;
use Prado\Web\UI\ActiveControls\TCallbackErrorHandler;

class TCallbackErrorHandlerTestResponse extends THttpResponse
{
	public array $headers = [];
	public string $content = '';

	public function appendHeader($header, bool $replace = true, int $response_code = 0): void
	{
		$this->headers[] = $header;
	}

	public function createHtmlWriter($type = null)
	{
		$response = $this;
		$writer = new class ($response) {
			public function __construct(private TCallbackErrorHandlerTestResponse $response)
			{
			}

			public function setBoundary($boundary)
			{
			}

			public function write($str)
			{
				$this->response->content .= $str;
			}
		};
		return new class ($writer) {
			public function __construct(private object $writer)
			{
			}

			public function getWriter()
			{
				return $this->writer;
			}

			public function write($str)
			{
				$this->writer->write($str);
			}
		};
	}

	public function flush($continueBuffering = true)
	{
	}
}

class TCallbackErrorHandlerTest extends \PHPUnit\Framework\TestCase
{
	private array $snapshot;

	protected function setUp(): void
	{
		$app = Prado::getApplication();
		$this->snapshot = PradoUnit::snapshot($app, ['_mode', '_response']);
		$app->setMode(TApplicationMode::Debug);
	}

	protected function tearDown(): void
	{
		PradoUnit::restore(Prado::getApplication(), $this->snapshot);
	}

	private function createResponse(): TCallbackErrorHandlerTestResponse
	{
		$response = new TCallbackErrorHandlerTestResponse();
		Prado::getApplication()->setResponse($response);
		return $response;
	}

	public function testDisplayExceptionSetsStatus500()
	{
		$response = $this->createResponse();

		PradoUnit::invoke(new TCallbackErrorHandler(), 'displayException', new \Exception('failure'));

		self::assertSame(500, $response->getStatusCode());
		self::assertStringContainsString('"code":500', $response->content);
	}

	public function testDisplayExceptionAfterHttpHeaderSentKeepsStatus()
	{
		$response = $this->createResponse();
		PradoUnit::invoke($response, 'sendHttpHeader');
		self::assertTrue($response->getHttpHeaderSent());

		PradoUnit::invoke(new TCallbackErrorHandler(), 'displayException', new \Exception('failure'));

		self::assertSame(200, $response->getStatusCode());
		self::assertStringContainsString('"code":500', $response->content);
	}
}
