<?php

namespace Prado\Test\Unit\Util\Cron;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TIOException;
use Prado\IO\HttpClient\THttpClient;
use Prado\IO\HttpClient\THttpClientResponse;
use Prado\Util\Cron\TCronModule;
use Prado\Util\Cron\THttpCronTask;

/**
 * Records the requests of a THttpCronTask and answers with a set status and body.
 */
class THttpCronTaskTestClient extends THttpClient
{
	public array $requests = [];
	public int $status = 200;
	public string $body = 'done';

	public function download(string $method, string $url, array $headers = [], ?string $body = null): THttpClientResponse
	{
		$this->requests[] = [$method, $url, $headers, $body, $this->getTimeout()];
		return new THttpClientResponse($this->status, [], $this->body);
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
		self::assertSame([['POST', 'https://example.test/hook', ['X-Run' => '1'], 'payload', 3]], $this->client->requests, 'the timeout rounds up to whole seconds');
		self::assertSame(200, $this->task->getStatusCode());
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
}
