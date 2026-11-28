<?php

namespace Prado\Test\Unit\IO\HttpClient;

use Prado\Exceptions\TConfigurationException;
use Prado\IO\Async\TAsync;
use Prado\IO\Http\TRequest;
use Prado\IO\Http\TResponse;
use Prado\IO\HttpClient\IHttpHandler;
use Prado\IO\HttpClient\TCurlHttpHandler;
use Prado\IO\HttpClient\TCurlMultiHttpHandler;
use Prado\IO\HttpClient\THttpClient;
use Prado\IO\HttpClient\THttpClientEventParameter;
use Prado\IO\HttpClient\THttpClientOption;
use Prado\IO\HttpClient\THttpNetworkException;
use Prado\IO\HttpClient\THttpRequestException;
use Prado\IO\HttpClient\THttpResponseException;
use Prado\IO\HttpClient\TSocketHttpHandler;
use Prado\IO\Stream\TPumpStream;
use Prado\IO\TResourceUri;
use Prado\Prado;
use Prado\TComponent;
use Prado\Util\TBehavior;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * A handler that records requests and answers from a queue.
 */
class THttpClientTestHandler extends TComponent implements IHttpHandler
{
	/** @var RequestInterface[] */
	public array $requests = [];

	/** @var array<int, array<string, mixed>> */
	public array $options = [];

	/** @var array<ResponseInterface|\Throwable> */
	public array $queue = [];

	public static function getIsAvailable(): bool
	{
		return true;
	}

	public function handle(RequestInterface $request, array $options = []): ResponseInterface
	{
		$this->requests[] = $request;
		$this->options[] = $options;
		$next = array_shift($this->queue) ?? new TResponse(200);
		if ($next instanceof \Throwable) {
			throw $next;
		}
		return $next;
	}
}

/**
 * Exposes the URI resolution of THttpClient.
 */
class THttpClientTestResolver extends THttpClient
{
	public function pubResolveUri(string $base, string $reference): string
	{
		return (string) $this->resolveUri(new TResourceUri($base), $reference);
	}
}

/**
 * A behavior that adds a header before sending and one to the response.
 */
class THttpClientTestBehavior extends TBehavior
{
	public function dyBeforeSend(RequestInterface $request, array $options, $chain)
	{
		return $chain->dyBeforeSend($request->withHeader('X-Behavior', 'before'), $options);
	}

	public function dyAfterResponse(ResponseInterface $response, RequestInterface $request, array $options, $chain)
	{
		return $chain->dyAfterResponse($response->withHeader('X-Behavior', 'after'), $request, $options);
	}
}

class THttpClientTest extends \PHPUnit\Framework\TestCase
{
	use HttpServerTestTrait;

	public static function setUpBeforeClass(): void
	{
		self::startHttpServer();
	}

	public static function tearDownAfterClass(): void
	{
		self::stopHttpServer();
	}

	private function client(array $queue = []): array
	{
		$handler = new THttpClientTestHandler();
		$handler->queue = $queue;
		$client = new THttpClient();
		$client->setHandler($handler);
		return [$client, $handler];
	}

	private function redirect(int $status, string $location): TResponse
	{
		return new TResponse($status, ['Location' => $location]);
	}

	public function testImplementsPsr18(): void
	{
		self::assertInstanceOf(ClientInterface::class, new THttpClient());
		self::assertTrue(THttpClient::getHasAvailableHandler());
	}

	public function testDefaults(): void
	{
		$client = new THttpClient();
		self::assertNull($client->getHandler());
		self::assertSame(30.0, $client->getTimeout());
		self::assertSame(10.0, $client->getConnectTimeout());
		self::assertTrue($client->getFollowRedirects());
		self::assertSame(5, $client->getMaxRedirects());
		self::assertTrue($client->getVerify());
		self::assertTrue($client->getDecodeContent());
		self::assertFalse($client->getHttpErrors());
		self::assertSame(['User-Agent' => 'Prado/' . Prado::getVersion()], $client->getHeaders());
		self::assertNull($client->getBaseUri());
	}

	public function testPropertySetters(): void
	{
		$client = (new THttpClient())
			->setTimeout('2.5')->setConnectTimeout(-1)->setFollowRedirects('false')
			->setMaxRedirects(-3)->setVerify('false')->setDecodeContent('off')->setHttpErrors('true')
			->setHeaders([])->setBaseUri('https://api.test/v1/');
		self::assertSame(2.5, $client->getTimeout());
		self::assertSame(0.0, $client->getConnectTimeout());
		self::assertFalse($client->getFollowRedirects());
		self::assertSame(0, $client->getMaxRedirects());
		self::assertFalse($client->getVerify());
		self::assertFalse($client->getDecodeContent());
		self::assertTrue($client->getHttpErrors());
		self::assertSame([], $client->getHeaders());
		self::assertSame('https://api.test/v1/', (string) $client->getBaseUri());
		self::assertSame('/etc/ca.pem', $client->setVerify('/etc/ca.pem')->getVerify());
	}

	public function testSetHandlerByClassName(): void
	{
		$client = new THttpClient();
		$client->setHandler(TSocketHttpHandler::class);
		self::assertInstanceOf(TSocketHttpHandler::class, $client->getHandler());
		$this->expectException(TConfigurationException::class);
		$client->setHandler(\stdClass::class);
	}

	public function testOptionsReachTheHandler(): void
	{
		[$client, $handler] = $this->client();
		$client->setTimeout(3)->setVerify(false);
		$client->sendRequest(new TRequest('GET', 'http://a.test/'));
		self::assertSame(3.0, $handler->options[0][THttpClientOption::TIMEOUT]);
		self::assertFalse($handler->options[0][THttpClientOption::VERIFY]);
		$client->send(new TRequest('GET', 'http://a.test/'), [THttpClientOption::TIMEOUT => 1.0]);
		self::assertSame(1.0, $handler->options[1][THttpClientOption::TIMEOUT]);
	}

	public function testDefaultHeadersAreAddedWhenAbsent(): void
	{
		[$client, $handler] = $this->client();
		$client->setHeaders(['User-Agent' => 'Test/1', 'X-Default' => 'd']);
		$client->sendRequest(new TRequest('GET', 'http://a.test/', ['x-default' => 'mine']));
		self::assertSame('Test/1', $handler->requests[0]->getHeaderLine('User-Agent'));
		self::assertSame('mine', $handler->requests[0]->getHeaderLine('X-Default'));
	}

	public function testSendRequestNeverThrowsForErrorStatus(): void
	{
		[$client] = $this->client([new TResponse(500)]);
		$client->setHttpErrors(true);
		self::assertSame(500, $client->sendRequest(new TRequest('GET', 'http://a.test/'))->getStatusCode());
	}

	public function testHttpErrorsThrowResponseException(): void
	{
		[$client] = $this->client([new TResponse(404)]);
		try {
			$client->send(new TRequest('GET', 'http://a.test/x'), [THttpClientOption::HTTP_ERRORS => true]);
			self::fail('An error response throws.');
		} catch (THttpResponseException $e) {
			self::assertSame(404, $e->getStatusCode());
			self::assertSame(404, $e->getCode());
			self::assertSame('http://a.test/x', (string) $e->getRequest()->getUri());
		}
	}

	public function testFollowsRedirectWithGet(): void
	{
		[$client, $handler] = $this->client([$this->redirect(302, '/next'), new TResponse(200, [], 'done')]);
		$response = $client->sendRequest(new TRequest('GET', 'http://a.test/start'));
		self::assertSame('done', (string) $response->getBody());
		self::assertSame('http://a.test/next', (string) $handler->requests[1]->getUri());
		self::assertSame('a.test', $handler->requests[1]->getHeaderLine('Host'));
	}

	public function testSeeOtherTurnsPostIntoGetWithoutBody(): void
	{
		[$client, $handler] = $this->client([$this->redirect(303, '/result'), new TResponse(200)]);
		$client->sendRequest(new TRequest('POST', 'http://a.test/form', ['Content-Type' => 'text/plain'], 'data'));
		self::assertSame('GET', $handler->requests[1]->getMethod());
		self::assertSame('', (string) $handler->requests[1]->getBody());
		self::assertFalse($handler->requests[1]->hasHeader('Content-Type'));
	}

	public function testFoundTurnsPostIntoGet(): void
	{
		[$client, $handler] = $this->client([$this->redirect(302, '/x'), new TResponse(200)]);
		$client->sendRequest(new TRequest('POST', 'http://a.test/', [], 'data'));
		self::assertSame('GET', $handler->requests[1]->getMethod());
	}

	public function testHeadStaysHeadOnSeeOther(): void
	{
		[$client, $handler] = $this->client([$this->redirect(303, '/x'), new TResponse(200)]);
		$client->sendRequest(new TRequest('HEAD', 'http://a.test/'));
		self::assertSame('HEAD', $handler->requests[1]->getMethod());
	}

	public static function providePreservingRedirects(): array
	{
		return [[307], [308]];
	}

	/**
	 * @dataProvider providePreservingRedirects
	 */
	public function testTemporaryAndPermanentRedirectsResendMethodAndBody(int $status): void
	{
		$handler = new THttpClientTestHandler();
		$handler->queue = [$this->redirect($status, '/again'), new TResponse(200)];
		$client = (new THttpClient())->setHandler($handler);
		$request = new TRequest('PUT', 'http://a.test/', [], 'payload');
		$request->getBody()->getContents();
		$client->sendRequest($request);
		self::assertSame('PUT', $handler->requests[1]->getMethod());
		self::assertSame('payload', $handler->requests[1]->getBody()->getContents());
	}

	public function testUnrewindableBodyCannotBeResent(): void
	{
		$parts = ['sent once'];
		$body = new TPumpStream(static function () use (&$parts) {
			return array_shift($parts) ?? false;
		});
		$body->getContents();
		[$client] = $this->client([$this->redirect(307, '/again')]);
		$this->expectException(THttpRequestException::class);
		$client->sendRequest(new TRequest('POST', 'http://a.test/', [], $body));
	}

	public function testCrossHostRedirectDropsCredentials(): void
	{
		[$client, $handler] = $this->client([$this->redirect(302, 'https://other.test/landing'), new TResponse(200)]);
		$client->sendRequest(new TRequest('GET', 'http://a.test/', ['Authorization' => 'Bearer s', 'Cookie' => 'c=1', 'X-Keep' => 'k']));
		$next = $handler->requests[1];
		self::assertSame('https://other.test/landing', (string) $next->getUri());
		self::assertFalse($next->hasHeader('Authorization'));
		self::assertFalse($next->hasHeader('Cookie'));
		self::assertSame('k', $next->getHeaderLine('X-Keep'));
		self::assertSame('other.test', $next->getHeaderLine('Host'));
	}

	public function testSameOriginRedirectKeepsCredentials(): void
	{
		[$client, $handler] = $this->client([$this->redirect(301, '/moved'), new TResponse(200)]);
		$client->sendRequest(new TRequest('GET', 'http://a.test/', ['Authorization' => 'Bearer s']));
		self::assertSame('Bearer s', $handler->requests[1]->getHeaderLine('Authorization'));
	}

	public function testRedirectToUnsupportedSchemeIsNotFollowed(): void
	{
		[$client, $handler] = $this->client([$this->redirect(302, 'file:///etc/passwd')]);
		$response = $client->sendRequest(new TRequest('GET', 'http://a.test/'));
		self::assertSame(302, $response->getStatusCode());
		self::assertCount(1, $handler->requests);
	}

	public function testRedirectWithoutLocationIsReturned(): void
	{
		[$client, $handler] = $this->client([new TResponse(302)]);
		self::assertSame(302, $client->sendRequest(new TRequest('GET', 'http://a.test/'))->getStatusCode());
		self::assertCount(1, $handler->requests);
	}

	public function testFollowRedirectsOff(): void
	{
		[$client, $handler] = $this->client([$this->redirect(302, '/x')]);
		$client->setFollowRedirects(false);
		self::assertSame(302, $client->sendRequest(new TRequest('GET', 'http://a.test/'))->getStatusCode());
		self::assertCount(1, $handler->requests);
	}

	public function testTooManyRedirects(): void
	{
		[$client, $handler] = $this->client(array_fill(0, 10, $this->redirect(302, '/loop')));
		$client->setMaxRedirects(2);
		try {
			$client->sendRequest(new TRequest('GET', 'http://a.test/'));
			self::fail('The chain is too long.');
		} catch (THttpRequestException $e) {
			self::assertCount(3, $handler->requests);
		}
	}

	public function testRequestBuildsJsonBody(): void
	{
		[$client, $handler] = $this->client();
		$client->request('POST', 'http://a.test/items', [THttpClientOption::JSON => ['a' => 1, 'path' => '/x']]);
		$request = $handler->requests[0];
		self::assertSame('{"a":1,"path":"/x"}', (string) $request->getBody());
		self::assertSame('application/json', $request->getHeaderLine('Content-Type'));
	}

	public function testRequestBuildsFormBodyAndQuery(): void
	{
		[$client, $handler] = $this->client();
		$client->request('POST', 'http://a.test/form?old=1', [
			THttpClientOption::FORM_PARAMS => ['a' => 'b c'],
			THttpClientOption::QUERY => ['q' => 'x y'],
			THttpClientOption::HEADERS => ['content-type' => 'application/x-custom'],
		]);
		$request = $handler->requests[0];
		self::assertSame('a=b+c', (string) $request->getBody());
		self::assertSame('application/x-custom', $request->getHeaderLine('Content-Type'));
		self::assertSame('q=x%20y', $request->getUri()->getQuery());
	}

	public function testRequestResolvesAgainstBaseUri(): void
	{
		[$client, $handler] = $this->client();
		$client->setBaseUri('https://api.test/v1/');
		$client->request('GET', 'users/7', [THttpClientOption::BODY => 'raw']);
		self::assertSame('https://api.test/v1/users/7', (string) $handler->requests[0]->getUri());
		self::assertSame('raw', (string) $handler->requests[0]->getBody());
		$client->request('GET', 'http://other.test/x');
		self::assertSame('http://other.test/x', (string) $handler->requests[1]->getUri());
	}

	public static function provideRfc3986Resolution(): array
	{
		// RFC 3986 section 5.4, base http://a/b/c/d;p?q
		return [
			['g', 'http://a/b/c/g'], ['./g', 'http://a/b/c/g'], ['g/', 'http://a/b/c/g/'], ['/g', 'http://a/g'],
			['//g', 'http://g'], ['?y', 'http://a/b/c/d;p?y'], ['g?y', 'http://a/b/c/g?y'], ['#s', 'http://a/b/c/d;p?q#s'],
			['g#s', 'http://a/b/c/g#s'], [';x', 'http://a/b/c/;x'], ['', 'http://a/b/c/d;p?q'], ['.', 'http://a/b/c/'],
			['./', 'http://a/b/c/'], ['..', 'http://a/b/'], ['../', 'http://a/b/'], ['../g', 'http://a/b/g'],
			['../..', 'http://a/'], ['../../g', 'http://a/g'], ['../../../g', 'http://a/g'], ['/./g', 'http://a/g'],
			['/../g', 'http://a/g'], ['g.', 'http://a/b/c/g.'], ['..g', 'http://a/b/c/..g'], ['./../g', 'http://a/b/g'],
			['g/./h', 'http://a/b/c/g/h'], ['g/../h', 'http://a/b/c/h'], ['https://x.test/a/../b', 'https://x.test/b'],
		];
	}

	/**
	 * @dataProvider provideRfc3986Resolution
	 */
	public function testResolveUri(string $reference, string $expected): void
	{
		self::assertSame($expected, (new THttpClientTestResolver())->pubResolveUri('http://a/b/c/d;p?q', $reference));
	}

	public function testBehaviorsFilterRequestAndResponse(): void
	{
		[$client, $handler] = $this->client();
		$client->attachBehavior('test', new THttpClientTestBehavior());
		$response = $client->sendRequest(new TRequest('GET', 'http://a.test/'));
		self::assertSame('before', $handler->requests[0]->getHeaderLine('X-Behavior'));
		self::assertSame('after', $response->getHeaderLine('X-Behavior'));
	}

	public function testEventsAreRaised(): void
	{
		[$client] = $this->client([new TResponse(201), new THttpNetworkException(new TRequest('GET', 'http://a.test/'), 'httpclient_transport_error', 7, 'refused')]);
		$events = [];
		$client->attachEventHandler('OnBeforeSend', function ($sender, THttpClientEventParameter $param) use (&$events) {
			$events[] = 'before:' . $param->getRequest()->getMethod();
		});
		$client->attachEventHandler('OnResponse', function ($sender, THttpClientEventParameter $param) use (&$events) {
			$events[] = 'response:' . $param->getResponse()->getStatusCode();
		});
		$client->attachEventHandler('OnError', function ($sender, THttpClientEventParameter $param) use (&$events) {
			$events[] = 'error:' . get_class($param->getException());
		});
		$client->sendRequest(new TRequest('GET', 'http://a.test/'));
		try {
			$client->sendRequest(new TRequest('DELETE', 'http://a.test/'));
		} catch (THttpNetworkException $e) {
		}
		self::assertSame(['before:GET', 'response:201', 'before:DELETE', 'error:' . THttpNetworkException::class], $events);
	}

	public function testHandlerChoiceFollowsTheFiber(): void
	{
		$client = new THttpClient();
		$method = new \ReflectionMethod($client, 'resolveHandler');
		$main = $method->invoke($client);
		$inFiber = TAsync::run(fn () => $method->invoke($client))->await();
		if (TCurlHttpHandler::getIsAvailable()) {
			self::assertInstanceOf(TCurlHttpHandler::class, $main);
			self::assertNotInstanceOf(TCurlMultiHttpHandler::class, $main);
			self::assertInstanceOf(TCurlMultiHttpHandler::class, $inFiber);
		} else {
			self::assertInstanceOf(TSocketHttpHandler::class, $main);
			self::assertInstanceOf(TSocketHttpHandler::class, $inFiber);
		}
		self::assertSame($main, $method->invoke($client), 'A handler class is created once per client');
	}

	public function testLiveRedirectAndAsync(): void
	{
		$this->requireRunningHttpServer();
		$client = new THttpClient();
		$echo = json_decode((string) $client->request('POST', self::url('/redirect?status=303'), [THttpClientOption::BODY => 'x'])->getBody(), true);
		self::assertSame('GET', $echo['method']);
		self::assertStringStartsWith('Prado/', $echo['headers']['User-Agent']);

		[$a, $b] = TAsync::all([
			$client->requestAsync('GET', self::url('/')),
			$client->sendAsync(new TRequest('GET', self::url('/status/204'))),
		])->await();
		self::assertSame('hello', (string) $a->getBody());
		self::assertSame(204, $b->getStatusCode());
	}

	public function testLiveRedirectLoopStops(): void
	{
		$this->requireRunningHttpServer();
		$this->expectException(THttpRequestException::class);
		(new THttpClient())->setMaxRedirects(3)->sendRequest(new TRequest('GET', self::url('/redirect-loop')));
	}

	public function testLiveCrossHostRedirectDropsAuthorization(): void
	{
		$this->requireRunningHttpServer();
		$target = 'http://localhost:' . self::$serverPort . '/echo';
		$client = new THttpClient();
		$echo = json_decode((string) $client->request('GET', self::url('/redirect?to=' . rawurlencode($target)), [
			THttpClientOption::HEADERS => ['Authorization' => 'Bearer secret'],
		])->getBody(), true);
		self::assertArrayNotHasKey('Authorization', $echo['headers']);
	}
}
