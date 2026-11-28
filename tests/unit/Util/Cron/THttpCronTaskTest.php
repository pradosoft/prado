<?php

namespace Prado\Test\Unit\Util\Cron;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TIOException;
use Prado\IO\Http\TResponse;
use Prado\IO\HttpClient\THttpClient;
use Prado\IO\HttpClient\THttpClientOption;
use Prado\Test\Unit\Harness\IO\TTestHttpClient;
use Prado\Util\Cron\TCronModule;
use Prado\Util\Cron\THttpCronTask;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Records the requests of a THttpCronTask and answers with a set status and body.
 */
class THttpCronTaskTestClient extends THttpClient
{
	public array $requests = [];
	public int $status = 200;
	public string $body = 'done';

	public function send(RequestInterface $request, array $options = []): ResponseInterface
	{
		$this->requests[] = [$request->getMethod(), (string) $request->getUri(), $request->getHeaderLine('X-Run'), (string) $request->getBody(), $options[THttpClientOption::TIMEOUT] ?? null];
		return new TResponse($this->status, [], $this->body);
	}
}

class THttpCronTaskTest extends \PHPUnit\Framework\TestCase
{
	private THttpCronTask $task;

	private THttpCronTaskTestClient $client;

	protected function setUp(): void
	{
		$this->task = new THttpCronTask();
		$this->task->setName('http');
		$this->client = new THttpCronTaskTestClient();
		$this->task->setHttpClient($this->client);
	}

	public function testProperties()
	{
		self::assertNull($this->task->getUrl());
		self::assertSame('GET', $this->task->getMethod());
		self::assertSame([], $this->task->getHeaders());
		self::assertNull($this->task->getBody());
		self::assertNull($this->task->getLogFile());
		self::assertNull($this->task->getStatusCode());
		$this->task->setUrl('https://example.test/hook')->setMethod('post')->setBody('{"a":1}')
			->setHeaders("Content-Type: application/json|Authorization: Bearer x:y\nX-Empty:");
		self::assertSame('POST', $this->task->getMethod());
		self::assertSame(['Content-Type' => 'application/json', 'Authorization' => 'Bearer x:y', 'X-Empty' => ''], $this->task->getHeaders());
		$this->task->setHeaders(['Accept' => 'text/plain']);
		self::assertSame(['Accept' => 'text/plain'], $this->task->getHeaders());
		$copy = unserialize(serialize($this->task));
		self::assertSame('https://example.test/hook', $copy->getUrl());
		self::assertSame('POST', $copy->getMethod());
		self::assertSame(['Accept' => 'text/plain'], $copy->getHeaders());
		self::assertStringNotContainsString('httpClient', serialize($this->task), 'the client is not serialized');
		self::expectException(TInvalidDataValueException::class);
		$this->task->setHeaders('no colon here');
	}

	public function testExecute_SendsTheRequest()
	{
		$this->task->setUrl('https://example.test/hook')->setMethod('POST')->setBody('payload')->setHeaders('X-Run: 1');
		$this->task->setTimeout(2.5);
		$this->task->execute(new TCronModule());
		self::assertSame([['POST', 'https://example.test/hook', '1', 'payload', 2.5]], $this->client->requests, 'the timeout is the request option');
		self::assertSame(200, $this->task->getStatusCode());
	}

	public function testExecute_AnyPsr18Client()
	{
		$client = new TTestHttpClient();
		$client->responses[] = new TResponse(500, [], 'down');
		$this->task->setHttpClient($client)->setUrl('https://example.test/hook')->setMethod('PUT')->setBody('x');
		try {
			$this->task->execute(new TCronModule());
			self::fail('a 500 did not throw');
		} catch (TIOException $e) {
			self::assertStringContainsString('500', $e->getMessage());
		}
		self::assertSame('PUT', $client->calls[0]['method']);
		self::assertSame('x', $client->calls[0]['body']);
		self::assertSame(500, $this->task->getStatusCode());
		$this->task->setHttpClient(null);
		self::assertInstanceOf(THttpClient::class, $this->task->getHttpClient(), 'null restores the default');
	}

	public function testExecute_ANon2xxStatusThrows()
	{
		$this->task->setUrl('https://example.test/missing');
		$this->client->status = 404;
		try {
			$this->task->execute(new TCronModule());
			self::fail('a 404 did not throw');
		} catch (TIOException $e) {
			self::assertStringContainsString('404', $e->getMessage());
		}
		self::assertSame(404, $this->task->getStatusCode());
	}

	public function testExecute_AppendsTheBodyToTheLogFile()
	{
		$log = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado-httptask-' . bin2hex(random_bytes(4)) . '.log';
		$this->task->setUrl('https://example.test/')->setLogFile($log);
		try {
			$this->task->execute(new TCronModule());
			$this->task->execute(new TCronModule());
			self::assertSame('donedone', file_get_contents($log));
		} finally {
			if (is_file($log)) {
				unlink($log);
			}
		}
	}

	public function testExecute_WithoutAUrlThrows()
	{
		self::expectException(TConfigurationException::class);
		$this->task->execute(new TCronModule());
	}

	public function testHeadersSkipBlankLinesAndDefaultsSerializeSmall()
	{
		$task = new THttpCronTask();
		$task->setHeaders("Accept: text/plain\n\n|X-Id: 7");
		self::assertSame(['Accept' => 'text/plain', 'X-Id' => '7'], $task->getHeaders());
		$text = serialize(new THttpCronTask());
		foreach (['_method', '_headers', '_body', '_logFile', '_httpClient', '_statusCode'] as $prop) {
			self::assertStringNotContainsString($prop, $text);
		}
		self::assertSame('GET', unserialize($text)->getMethod());
	}

	public function testNullAndEmptyPropertyValues()
	{
		$task = new \Prado\Util\Cron\THttpCronTask();
		$task->setUrl('https://example.test/');
		$task->setUrl('');
		self::assertNull($task->getUrl());
		$task->setUrl('https://example.test/');
		$task->setUrl(null);
		self::assertNull($task->getUrl());
		$task->setBody('hello');
		$task->setBody(null);
		self::assertNull($task->getBody());
		$task->setLogFile('/tmp/http.log');
		$task->setLogFile('');
		self::assertNull($task->getLogFile());
		$task->setLogFile('/tmp/http.log');
		$task->setLogFile(null);
		self::assertNull($task->getLogFile());
	}

	public function testExecute_TimeoutLogAndStatusCombinations()
	{
		$log = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado-httptask-' . bin2hex(random_bytes(4)) . '.log';
		$timed = new TCronModule();
		$timed->setTaskTimeout(4.2);
		$modules = ['TaskTimeout' => [$timed, 4.2], 'no TaskTimeout' => [new TCronModule(), null], 'no module' => [null, null]];
		try {
			foreach ($modules as $label => [$module, $timeout]) {
				foreach ([null, $log] as $logFile) {
					foreach ([200, 500] as $status) {
						$client = new THttpCronTaskTestClient();
						$client->status = $status;
						$task = new THttpCronTask();
						$task->setName('http');
						$task->setUrl('https://example.test/run')->setLogFile($logFile)->setHttpClient($client);
						@unlink($log);
						try {
							$task->execute($module);
							self::assertSame(200, $status, $label . ': a 2xx status does not throw');
						} catch (TIOException $e) {
							self::assertSame(500, $status, $label . ': a non-2xx status throws');
						}
						self::assertSame($status, $task->getStatusCode());
						self::assertSame($timeout, $client->requests[0][4], $label . ': the request timeout option');
						self::assertSame($logFile !== null, is_file($log), $label . ': the log file');
					}
				}
			}
		} finally {
			$timed->unlisten();
			if (is_file($log)) {
				unlink($log);
			}
		}
	}

	public function testSerialize_LeavesOutEachDefault()
	{
		$values = ['_method' => 'POST', '_headers' => ['X-A' => '1'], '_body' => 'payload', '_logFile' => '/tmp/http.log'];
		$setters = ['_method' => 'setMethod', '_headers' => 'setHeaders', '_body' => 'setBody', '_logFile' => 'setLogFile'];
		for ($mask = 0; $mask < 16; $mask++) {
			$task = new THttpCronTask();
			$set = [];
			foreach (array_keys($values) as $i => $prop) {
				if ($mask & (1 << $i)) {
					$task->{$setters[$prop]}($values[$prop]);
					$set[] = $prop;
				}
			}
			$text = serialize($task);
			foreach (array_keys($values) as $prop) {
				self::assertSame(in_array($prop, $set, true), str_contains($text, "\0Prado\\Util\\Cron\\THttpCronTask\0" . $prop), $prop . ' in mask ' . $mask);
			}
		}
	}
}
