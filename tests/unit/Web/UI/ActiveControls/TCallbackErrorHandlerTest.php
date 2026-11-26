<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use Prado\Exceptions\TPhpErrorException;
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

	/**
	 * Builds an exception in a function called with an argument, so its trace holds the call.
	 */
	private function throwFrom(string $argument, ?string $class = null): \Exception
	{
		return $class === null ? new \Exception('failure') : new $class(E_WARNING, 'warned', __FILE__, 1);
	}

	public function testDisplayExceptionSendsTheTraceWithDumpedArguments()
	{
		$response = $this->createResponse();
		$saved = ini_set('zend.exception_ignore_args', '0');
		try {
			$exception = $this->throwFrom('arg');
		} finally {
			ini_set('zend.exception_ignore_args', $saved);
		}

		PradoUnit::invoke(new TCallbackErrorHandler(), 'displayException', $exception);

		$data = json_decode($response->content, true);
		self::assertSame(500, $data['code']);
		self::assertSame(\Exception::class, $data['type']);
		self::assertSame('failure', $data['message']);
		self::assertSame("'arg'", $data['trace'][0]['args'][0]);
		self::assertStringContainsString('TCallbackErrorHandlerTest.php', $data['trace'][0]['file']);
	}

	public function testDisplayExceptionOfAPhpErrorShowsTheCallerOfTheErrorHandler()
	{
		$response = $this->createResponse();
		$exception = $this->throwFrom('arg', TPhpErrorException::class);

		PradoUnit::invoke(new TCallbackErrorHandler(), 'displayException', $exception);

		$data = json_decode($response->content, true);
		self::assertSame($exception->getTrace()[0]['line'], $data['line']);
		self::assertSame(TPhpErrorException::class, $data['type']);
	}

	public function testDisplayExceptionOfANonUtf8MessageSendsTheAsciiOfTheSerializedTrace()
	{
		$response = $this->createResponse();

		PradoUnit::invoke(new TCallbackErrorHandler(), 'displayException', new \Exception("bad \xff byte"));

		self::assertStringStartsWith('a:', $response->content);
		self::assertStringContainsString('bad  byte', $response->content);
		self::assertStringNotContainsString("\xff", $response->content);
	}

	public function testDisplayExceptionOutsideDebugLogsAndSendsTheStatusHeader()
	{
		Prado::getApplication()->setMode(TApplicationMode::Normal);
		$response = $this->createResponse();
		$log = tempnam(sys_get_temp_dir(), 'prado');
		$saved = ini_set('error_log', $log);
		try {
			PradoUnit::invoke(new TCallbackErrorHandler(), 'displayException', new \Exception('hidden'));
			$logged = file_get_contents($log);
		} finally {
			ini_set('error_log', $saved);
			unlink($log);
		}

		self::assertSame(['HTTP/1.0 500 Internal Server Error'], $response->headers);
		self::assertSame('', $response->content);
		self::assertStringContainsString('hidden', $logged);
	}

	public function testDisplayExceptionOfANonUtf8MessageAfterTheHeaderKeepsTheStatus()
	{
		$response = $this->createResponse();
		PradoUnit::invoke($response, 'sendHttpHeader');

		PradoUnit::invoke(new TCallbackErrorHandler(), 'displayException', new \Exception("bad \xff byte"));

		self::assertSame(200, $response->getStatusCode());
		self::assertStringStartsWith('a:', $response->content);
	}

	public function testDisplayExceptionSendsTheCodeOfTheException()
	{
		$response = $this->createResponse();

		PradoUnit::invoke(new TCallbackErrorHandler(), 'displayException', new \Exception('failure', 42));

		self::assertSame(42, json_decode($response->content, true)['code']);
	}
}
