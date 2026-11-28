<?php

namespace Prado\Test\Unit\IO\Http;

use Prado\IO\Http\TResponse;
use Psr\Http\Message\ResponseInterface;

class TResponseTest extends \PHPUnit\Framework\TestCase
{
	public function testImplementsPsr7()
	{
		self::assertInstanceOf(ResponseInterface::class, new TResponse());
	}

	public function testDefaults()
	{
		$response = new TResponse();
		self::assertSame(200, $response->getStatusCode());
		self::assertSame('OK', $response->getReasonPhrase());
		self::assertSame([], $response->getHeaders());
		self::assertSame('', (string) $response->getBody());
	}

	public function testConstructorArguments()
	{
		$response = new TResponse(404, ['X-A' => '1'], 'gone', '2', 'Nope');
		self::assertSame(404, $response->getStatusCode());
		self::assertSame('Nope', $response->getReasonPhrase());
		self::assertSame('gone', (string) $response->getBody());
		self::assertSame('2', $response->getProtocolVersion());
	}

	public function testUnregisteredStatusHasEmptyReason()
	{
		self::assertSame('', (new TResponse(299))->getReasonPhrase());
		self::assertSame('', TResponse::getStandardReasonPhrase(299));
		self::assertSame('Not Found', TResponse::getStandardReasonPhrase(404));
	}

	public function testWithStatus()
	{
		$response = new TResponse();
		$moved = $response->withStatus(301);
		self::assertSame(301, $moved->getStatusCode());
		self::assertSame('Moved Permanently', $moved->getReasonPhrase());
		self::assertSame('Custom', $response->withStatus(301, 'Custom')->getReasonPhrase());
		self::assertSame(200, $response->getStatusCode());
	}

	public static function provideInvalidStatus(): array
	{
		return [[0], [99], [600], [-1]];
	}

	/**
	 * @dataProvider provideInvalidStatus
	 */
	public function testInvalidStatusThrows(int $status)
	{
		$this->expectException(\InvalidArgumentException::class);
		(new TResponse())->withStatus($status);
	}

	public function testInvalidStatusInConstructorThrows()
	{
		$this->expectException(\InvalidArgumentException::class);
		new TResponse(1000);
	}

	public function testStatusClasses()
	{
		$cases = [
			100 => 'isInformational',
			204 => 'isSuccess',
			304 => 'isRedirect',
			404 => 'isClientError',
			503 => 'isServerError',
		];
		$all = array_values($cases);
		foreach ($cases as $status => $expected) {
			$response = new TResponse($status);
			foreach ($all as $method) {
				self::assertSame($method === $expected, $response->$method(), "{$status} {$method}");
			}
		}
	}

	public function testWebResponseSharesReasonPhrases()
	{
		$property = new \ReflectionProperty(\Prado\Web\THttpResponse::class, 'HTTP_STATUS_CODES');
		self::assertSame(TResponse::REASON_PHRASES, $property->getValue());
	}
}
