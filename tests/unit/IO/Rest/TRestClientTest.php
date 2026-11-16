<?php

namespace Prado\Test\Unit\IO\Rest;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\IO\Http\TRequest;
use Prado\IO\Http\TResponse;
use Prado\IO\HttpClient\THttpClient;
use Prado\IO\HttpClient\THttpClientException;
use Prado\IO\HttpClient\THttpNetworkException;
use Prado\IO\HttpClient\THttpResponseException;
use Prado\IO\Rest\TRestClient;
use Prado\Test\Unit\Harness\IO\TTestHttpClient;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

// ── Test client subclass exercising the dispatch surface ──────────────────────

class TestApiClient extends TRestClient
{
	public function __construct()
	{
		parent::__construct();
		$this->setBaseUrl('https://api.example.test/');
	}

	public function fetchUser(string $id): mixed
	{
		return $this->get('users/{id}', ['id' => $id]);
	}

	public function listUsers(int $page): mixed
	{
		return $this->get('users', [], ['page' => $page]);
	}

	public function createUser(array $data): mixed
	{
		return $this->post('users', [], $data);
	}

	public function updateUser(string $id, array $data): mixed
	{
		return $this->put('users/{id}', ['id' => $id], $data);
	}

	public function deleteUser(string $id): mixed
	{
		return $this->delete('users/{id}', ['id' => $id]);
	}

	public function exposeBuildUrl(string $path, array $params = [], array $query = []): string
	{
		return $this->buildUrl($path, $params, $query);
	}

	public function exposeBuildQuery(array $query): string
	{
		return $this->buildQuery($query);
	}

	/** @return array{0: ?string, 1: array} [rawBody, augmentedHeaders] */
	public function exposeEncodeBody(mixed $body, array $headers = []): array
	{
		$raw = $this->encodeBody($body, $headers);
		return [$raw, $headers];
	}

	public function exposeHandleResponse(ResponseInterface $response, ?RequestInterface $request = null): mixed
	{
		return $this->handleResponse($response, $request ?? new TRequest('GET', 'https://api.example.test/'));
	}

	public function exposeCreateRequest(string $method, string $path, array $pathParams = [], array $query = [], mixed $body = null, array $headers = []): RequestInterface
	{
		return $this->createRequest($method, $path, $pathParams, $query, $body, $headers);
	}

	public function exposeMergeHeaders(array $perCall): array
	{
		return $this->mergeHeaders($perCall);
	}
}

/**
 * Tests for TRestClient.
 */
class TRestClientTest extends \PHPUnit\Framework\TestCase
{
	private TestApiClient $client;
	private TTestHttpClient $http;

	protected function setUp(): void
	{
		$this->http = new TTestHttpClient();
		$this->client = new TestApiClient();
		$this->client->setHttpClient($this->http);
	}

	/** Queues a response on the recording client. */
	private function queue(int $status, string $body): void
	{
		$this->http->responses[] = new TResponse($status, [], $body);
	}

	// ── URL building ──────────────────────────────────────────────────────────

	public function testBuildUrlExpandsPlaceholders(): void
	{
		$this->assertSame(
			'https://api.example.test/users/42',
			$this->client->exposeBuildUrl('users/{id}', ['id' => '42'])
		);
	}

	public function testBuildUrlUrlEncodesPlaceholderValues(): void
	{
		$this->assertSame(
			'https://api.example.test/items/a%20b%2Fc',
			$this->client->exposeBuildUrl('items/{slug}', ['slug' => 'a b/c'])
		);
	}

	public function testBuildUrlLeavesUnknownPlaceholdersIntact(): void
	{
		$this->assertSame(
			'https://api.example.test/users/{id}',
			$this->client->exposeBuildUrl('users/{id}', [])
		);
	}

	public function testBuildUrlAppendsQueryString(): void
	{
		$url = $this->client->exposeBuildUrl('search', [], ['q' => 'hello world', 'page' => 2]);
		$this->assertSame('https://api.example.test/search?q=hello+world&page=2', $url);
	}

	public function testBuildUrlAppendsQueryWithExistingQueryString(): void
	{
		$url = $this->client->exposeBuildUrl('search?lang=en', [], ['q' => 'x']);
		$this->assertSame('https://api.example.test/search?lang=en&q=x', $url);
	}

	// ── Verb dispatch ─────────────────────────────────────────────────────────

	public function testGetDispatch(): void
	{
		$this->queue(200, '{"id":"42","name":"Alice"}');
		$user = $this->client->fetchUser('42');
		$this->assertSame(['id' => '42', 'name' => 'Alice'], $user);
		$this->assertSame('GET', $this->http->calls[0]['method']);
		$this->assertSame('https://api.example.test/users/42', $this->http->calls[0]['url']);
	}

	public function testGetWithQueryString(): void
	{
		$this->queue(200, '[]');
		$this->client->listUsers(3);
		$this->assertSame('https://api.example.test/users?page=3', $this->http->calls[0]['url']);
	}

	public function testPostJsonEncodesBodyAndSetsContentType(): void
	{
		$this->queue(201, '{"id":"new"}');
		$result = $this->client->createUser(['name' => 'Bob']);
		$call = $this->http->calls[0];
		$this->assertSame('POST', $call['method']);
		$this->assertSame('{"name":"Bob"}', $call['body']);
		$this->assertSame('application/json', $call['headers']['Content-Type']);
		$this->assertSame(['id' => 'new'], $result);
	}

	public function testPutDispatchesWithPathParams(): void
	{
		$this->queue(200, '{"ok":true}');
		$this->client->updateUser('7', ['name' => 'C']);
		$this->assertSame('PUT', $this->http->calls[0]['method']);
		$this->assertSame('https://api.example.test/users/7', $this->http->calls[0]['url']);
	}

	public function testPatchDispatchesWithJsonBody(): void
	{
		$this->queue(200, '{"patched":true}');
		$result = $this->client->patch('users/{id}', ['id' => '7'], ['name' => 'D']);
		$call = $this->http->calls[0];
		$this->assertSame('PATCH', $call['method']);
		$this->assertSame('https://api.example.test/users/7', $call['url']);
		$this->assertSame('{"name":"D"}', $call['body']);
		$this->assertSame(['patched' => true], $result);
	}

	public function testDeleteDispatch(): void
	{
		$this->queue(204, '');
		$result = $this->client->deleteUser('9');
		$this->assertSame('DELETE', $this->http->calls[0]['method']);
		$this->assertNull($result); // empty body → null
	}

	// ── Headers ───────────────────────────────────────────────────────────────

	public function testDefaultHeadersAreSentOnEveryCall(): void
	{
		$this->client->setDefaultHeader('Authorization', 'Bearer xyz');
		$this->client->setDefaultHeader('Accept', 'application/json');
		$this->queue(200, '{}');
		$this->client->fetchUser('1');
		$headers = $this->http->calls[0]['headers'];
		$this->assertSame('Bearer xyz', $headers['Authorization']);
		$this->assertSame('application/json', $headers['Accept']);
	}

	public function testPerCallHeadersOverrideDefaults(): void
	{
		$this->client->setDefaultHeader('Accept', 'application/json');
		$this->queue(200, '{}');
		// Reach the generic request via the get() verb helper with per-call header.
		$this->client->get('users/{id}', ['id' => '1'], [], ['Accept' => 'text/plain']);
		$this->assertSame('text/plain', $this->http->calls[0]['headers']['Accept']);
	}

	public function testExplicitContentTypeIsRespected(): void
	{
		$this->queue(200, '{}');
		$this->client->post('upload', [], 'raw-string-body', [], ['Content-Type' => 'text/plain']);
		$this->assertSame('raw-string-body', $this->http->calls[0]['body']);
		$this->assertSame('text/plain', $this->http->calls[0]['headers']['Content-Type']);
	}

	public function testStringBodyPassesThroughWithoutContentType(): void
	{
		// String bodies skip JSON encoding entirely, so no Content-Type is added.
		$this->queue(200, '{}');
		$this->client->post('upload', [], 'raw-string-body');
		$call = $this->http->calls[0];
		$this->assertSame('raw-string-body', $call['body']);
		$this->assertArrayNotHasKey('Content-Type', $call['headers']);
	}

	public function testContentTypeDetectionIsCaseInsensitive(): void
	{
		// A lowercase per-call header must prevent the automatic JSON Content-Type.
		$this->queue(200, '{}');
		$this->client->post('upload', [], ['a' => 1], [], ['content-type' => 'application/xml']);
		$headers = $this->http->calls[0]['headers'];
		$this->assertSame('application/xml', $headers['content-type']);
		$this->assertArrayNotHasKey('Content-Type', $headers);
	}

	// ── Error handling ────────────────────────────────────────────────────────

	public function testThrowsOnHttpError(): void
	{
		$this->queue(404, '{"error":"not found"}');
		try {
			$this->client->fetchUser('missing');
			$this->fail('expected exception');
		} catch (THttpResponseException $e) {
			$this->assertSame(404, $e->getStatusCode());
			$this->assertSame(404, $e->getCode());
			$this->assertSame(['error' => 'not found'], json_decode((string) $e->getResponse()->getBody(), true));
		}
	}

	public function testHttpErrorCarriesTheRequestAndResponse(): void
	{
		$response = new TResponse(503, [], 'down');
		$this->http->responses[] = $response;
		try {
			$this->client->fetchUser('7');
			$this->fail('expected exception');
		} catch (THttpResponseException $e) {
			$this->assertInstanceOf(THttpClientException::class, $e);
			$this->assertInstanceOf(\Psr\Http\Client\ClientExceptionInterface::class, $e);
			$this->assertSame($response, $e->getResponse());
			$this->assertSame($this->http->calls[0]['request'], $e->getRequest());
			$this->assertSame('https://api.example.test/users/7', (string) $e->getRequest()->getUri());
		}
	}

	public function testHandleResponseDecodesTheBodyAfterAnEarlierRead(): void
	{
		$response = new TResponse(200, [], '{"a":1}');
		$response->getBody()->getContents();
		$this->assertSame(['a' => 1], $this->client->exposeHandleResponse($response));
	}

	public function testRequestRawReturnsErrorResponseWithoutThrowing(): void
	{
		$this->queue(500, '{"oops":true}');
		$result = $this->client->requestRaw('GET', 'users/{id}', ['id' => 'boom']);
		$this->assertInstanceOf(ResponseInterface::class, $result);
		$this->assertSame(500, $result->getStatusCode());
		$this->assertSame('https://api.example.test/users/boom', $this->http->calls[0]['url']);
	}

	public function testRequestRawReturnsSuccessResponseUndecoded(): void
	{
		$this->queue(200, '{"id":"1"}');
		$result = $this->client->requestRaw('GET', 'users/{id}', ['id' => '1']);
		$this->assertInstanceOf(ResponseInterface::class, $result);
		$this->assertSame('{"id":"1"}', (string) $result->getBody());
	}

	public function testRequestRawEncodesBodyAndQuery(): void
	{
		$this->queue(200, '{}');
		$this->client->requestRaw('POST', 'users', [], ['notify' => 1], ['name' => 'E']);
		$call = $this->http->calls[0];
		$this->assertSame('https://api.example.test/users?notify=1', $call['url']);
		$this->assertSame('{"name":"E"}', $call['body']);
		$this->assertSame('application/json', $call['headers']['Content-Type']);
	}

	public function testTransportFailurePropagatesAsException(): void
	{
		$failure = new THttpNetworkException(new TRequest('GET', 'https://api.example.test/users/x'), 'httpclient_transport_error', 6, 'DNS failed');
		$this->http->throw = $failure;
		try {
			$this->client->fetchUser('x');
			$this->fail('expected exception');
		} catch (THttpClientException $e) {
			$this->assertSame($failure, $e);
			$this->assertNotInstanceOf(THttpResponseException::class, $e);
		}
	}

	// ── Body and response decoding ────────────────────────────────────────────

	public function testUnencodableBodyThrowsJsonException(): void
	{
		$this->expectException(\JsonException::class);
		$this->client->post('users', [], ['name' => "\xB1\x31"]); // invalid UTF-8
	}

	public function testNonJsonSuccessBodyReturnsRawString(): void
	{
		$this->queue(200, 'pong');
		$this->assertSame('pong', $this->client->get('ping'));
	}

	public function testDeleteSendsJsonBody(): void
	{
		$this->queue(200, '{"deleted":2}');
		$result = $this->client->delete('users', [], ['ids' => [1, 2]]);
		$call = $this->http->calls[0];
		$this->assertSame('DELETE', $call['method']);
		$this->assertSame('{"ids":[1,2]}', $call['body']);
		$this->assertSame('application/json', $call['headers']['Content-Type']);
		$this->assertSame(['deleted' => 2], $result);
	}

	// ── Auth helpers ──────────────────────────────────────────────────────────

	public function testSetBearerTokenSendsAuthorizationHeader(): void
	{
		$this->client->setBearerToken('tok-123');
		$this->queue(200, '{}');
		$this->client->fetchUser('1');
		$this->assertSame('Bearer tok-123', $this->http->calls[0]['headers']['Authorization']);
	}

	public function testSetBasicAuthSendsAuthorizationHeader(): void
	{
		$this->client->setBasicAuth('alice', 's3cret');
		$this->queue(200, '{}');
		$this->client->fetchUser('1');
		$this->assertSame(
			'Basic ' . base64_encode('alice:s3cret'),
			$this->http->calls[0]['headers']['Authorization']
		);
	}

	// ── Query encoding ────────────────────────────────────────────────────────

	public function testListQueryValuesEncodeAsRepeatedKeys(): void
	{
		$url = $this->client->exposeBuildUrl('search', [], ['tags' => ['a', 'b'], 'page' => 2]);
		$this->assertSame('https://api.example.test/search?tags=a&tags=b&page=2', $url);
	}

	public function testAssociativeQueryValuesKeepBracketSyntax(): void
	{
		$url = $this->client->exposeBuildUrl('search', [], ['filter' => ['status' => 'open']]);
		$this->assertSame('https://api.example.test/search?filter%5Bstatus%5D=open', $url);
	}

	// ── Client accessors ──────────────────────────────────────────────────────

	public function testClientLazilyInstantiatesDefaultHttpClient(): void
	{
		$c = new TestApiClient();
		$d = $c->getHttpClient();
		$this->assertInstanceOf(THttpClient::class, $d);
		// Subsequent access returns the same instance
		$this->assertSame($d, $c->getHttpClient());
	}

	public function testBaseUrlAccessor(): void
	{
		$c = new TestApiClient();
		$this->assertSame('https://api.example.test/', $c->getBaseUrl());
		$c->setBaseUrl('https://other.example.test/v2/');
		$this->assertSame('https://other.example.test/v2/', $c->getBaseUrl());
	}

	public function testDefaultHeadersAccessorReplacesEntireMap(): void
	{
		$c = new TestApiClient();
		$this->assertSame([], $c->getDefaultHeaders());
		$c->setDefaultHeaders(['Accept' => 'application/json']);
		$c->setDefaultHeaders(['User-Agent' => 'Test/1.0']);
		$this->assertSame(['User-Agent' => 'Test/1.0'], $c->getDefaultHeaders());
	}

	public function testSetDefaultHeaderAddsWithoutDroppingPrior(): void
	{
		$c = new TestApiClient();
		$c->setDefaultHeader('Accept', 'application/json');
		$c->setDefaultHeader('User-Agent', 'Test/1.0');
		$this->assertSame(['Accept' => 'application/json', 'User-Agent' => 'Test/1.0'], $c->getDefaultHeaders());
	}

	public function testSetHttpClientInjectsAndGetterReturnsSameInstance(): void
	{
		$c = new TestApiClient();
		$mock = new TTestHttpClient();
		$c->setHttpClient($mock);
		$this->assertSame($mock, $c->getHttpClient());
	}

	// ── buildQuery edge cases ──────────────────────────────────────────────────

	public function testBuildQueryEmptyMapReturnsEmptyString(): void
	{
		$this->assertSame('', $this->client->exposeBuildQuery([]));
	}

	public function testBuildQueryNullValueIsOmittedWithNoDanglingSeparator(): void
	{
		// A null scalar produces no fragment; the surviving keys must not be
		// separated by a doubled or trailing '&'.
		$this->assertSame('a=1&b=2', $this->client->exposeBuildQuery(['a' => 1, 'x' => null, 'b' => 2]));
		$this->assertSame('', $this->client->exposeBuildQuery(['x' => null]));
	}

	public function testBuildQueryEmptyListIsOmitted(): void
	{
		$this->assertSame('keep=1', $this->client->exposeBuildQuery(['tags' => [], 'keep' => 1]));
	}

	public function testBuildQueryBooleanValuesEncodeAsOneAndZero(): void
	{
		$this->assertSame('active=1&inactive=0', $this->client->exposeBuildQuery(['active' => true, 'inactive' => false]));
	}

	public function testBuildQueryNestedListEmitsRepeatedKeys(): void
	{
		$this->assertSame('id=1&id=2&id=3', $this->client->exposeBuildQuery(['id' => [1, 2, 3]]));
	}

	public function testBuildUrlWithEmptyQueryAppendsNothing(): void
	{
		$this->assertSame('https://api.example.test/search', $this->client->exposeBuildUrl('search', [], []));
		// A query whose only value is null must not append a bare '?'.
		$this->assertSame('https://api.example.test/search', $this->client->exposeBuildUrl('search', [], ['x' => null]));
	}

	// ── buildUrl base-URL joining edge cases ───────────────────────────────────

	public function testBuildUrlJoinsBaseWithoutTrailingSlash(): void
	{
		$c = new TestApiClient();
		$c->setBaseUrl('https://h.test/api');
		$this->assertSame('https://h.test/api/users', $c->exposeBuildUrl('users'));
	}

	public function testBuildUrlWithEmptyBaseProducesRootRelative(): void
	{
		$c = new TestApiClient();
		$c->setBaseUrl('');
		$this->assertSame('/users', $c->exposeBuildUrl('users'));
	}

	public function testBuildUrlWithEmptyPathYieldsBaseRoot(): void
	{
		$this->assertSame('https://api.example.test/', $this->client->exposeBuildUrl(''));
	}

	public function testBuildUrlWithLeadingSlashPathDoesNotDoubleSlash(): void
	{
		$this->assertSame('https://api.example.test/users', $this->client->exposeBuildUrl('/users'));
	}

	// ── buildUrl placeholder edge cases ────────────────────────────────────────

	public function testBuildUrlPlaceholderZeroAndEmptyString(): void
	{
		$this->assertSame('https://api.example.test/users/0', $this->client->exposeBuildUrl('users/{id}', ['id' => 0]));
		$this->assertSame('https://api.example.test/users/', $this->client->exposeBuildUrl('users/{id}', ['id' => '']));
	}

	public function testBuildUrlPlaceholderNullLeavesTokenIntact(): void
	{
		// isset() is false for null, so an explicit null does NOT substitute.
		$this->assertSame('https://api.example.test/users/{id}', $this->client->exposeBuildUrl('users/{id}', ['id' => null]));
	}

	public function testBuildUrlDuplicatePlaceholderSubstitutesBoth(): void
	{
		$this->assertSame(
			'https://api.example.test/a/7/b/7',
			$this->client->exposeBuildUrl('a/{id}/b/{id}', ['id' => 7])
		);
	}

	public function testBuildUrlMultibytePlaceholderIsEncoded(): void
	{
		$this->assertSame(
			'https://api.example.test/u/caf%C3%A9',
			$this->client->exposeBuildUrl('u/{name}', ['name' => 'café'])
		);
	}

	// ── encodeBody edge cases ──────────────────────────────────────────────────

	public function testEncodeBodyNullReturnsNullAndAddsNoContentType(): void
	{
		[$raw, $headers] = $this->client->exposeEncodeBody(null);
		$this->assertNull($raw);
		$this->assertArrayNotHasKey('Content-Type', $headers);
	}

	public function testEncodeBodyEmptyStringPassesThrough(): void
	{
		[$raw, $headers] = $this->client->exposeEncodeBody('');
		$this->assertSame('', $raw);
		$this->assertArrayNotHasKey('Content-Type', $headers);
	}

	public function testEncodeBodyEmptyArrayEncodesAsJsonObjectOrArray(): void
	{
		[$raw, $headers] = $this->client->exposeEncodeBody([]);
		$this->assertSame('[]', $raw);
		$this->assertSame('application/json', $headers['Content-Type']);
	}

	public function testEncodeBodyUppercaseExistingContentTypeIsNotOverwritten(): void
	{
		[$raw, $headers] = $this->client->exposeEncodeBody(['a' => 1], ['CONTENT-TYPE' => 'application/xml']);
		$this->assertSame('{"a":1}', $raw);
		$this->assertSame('application/xml', $headers['CONTENT-TYPE']);
		$this->assertArrayNotHasKey('Content-Type', $headers);
	}

	// ── handleResponse edge cases ──────────────────────────────────────────────

	public function testHandleResponseLiteralJsonNullDecodesToNull(): void
	{
		// A body of the four bytes "null" is valid JSON and must decode to null,
		// not be mistaken for the raw-string fallback.
		$this->assertNull($this->client->exposeHandleResponse(new TResponse(200, [], 'null')));
	}

	public function testHandleResponseScalarJsonBodiesDecode(): void
	{
		$this->assertFalse($this->client->exposeHandleResponse(new TResponse(200, [], 'false')));
		$this->assertSame(0, $this->client->exposeHandleResponse(new TResponse(200, [], '0')));
		$this->assertSame(42, $this->client->exposeHandleResponse(new TResponse(200, [], '42')));
	}

	public function testHandleResponseEmptyBodyReturnsNull(): void
	{
		$this->assertNull($this->client->exposeHandleResponse(new TResponse(204, [], '')));
	}

	public function testHandleResponseSuccessBoundary299Decodes(): void
	{
		$this->assertSame(['ok' => true], $this->client->exposeHandleResponse(new TResponse(299, [], '{"ok":true}')));
	}

	public function testHandleResponse300ThrowsAsError(): void
	{
		$this->expectException(THttpResponseException::class);
		$this->expectExceptionCode(300);
		$this->client->exposeHandleResponse(new TResponse(300, [], 'redirect'));
	}

	// ── createRequest ───────────────────────────────────────────────────────────

	public function testCreateRequestBuildsThePsr7Request(): void
	{
		$this->client->setDefaultHeader('Accept', 'application/json');
		$request = $this->client->exposeCreateRequest('POST', 'users/{id}', ['id' => '3'], ['notify' => 1], ['name' => 'F'], ['X-Trace' => 'abc']);
		$this->assertSame('POST', $request->getMethod());
		$this->assertSame('https://api.example.test/users/3?notify=1', (string) $request->getUri());
		$this->assertSame('api.example.test', $request->getHeaderLine('Host'));
		$this->assertSame('application/json', $request->getHeaderLine('Accept'));
		$this->assertSame('abc', $request->getHeaderLine('X-Trace'));
		$this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
		$this->assertSame('{"name":"F"}', (string) $request->getBody());
	}

	public function testRequestSendsTheCreatedRequest(): void
	{
		$this->client->fetchUser('1');
		$this->assertCount(1, $this->http->calls);
		$this->assertInstanceOf(TRequest::class, $this->http->calls[0]['request']);
		$this->assertSame('api.example.test', $this->http->calls[0]['headers']['Host']);
	}

	// ── mergeHeaders ────────────────────────────────────────────────────────────

	public function testMergeHeadersPerCallWinsAndDefaultsSurvive(): void
	{
		$this->client->setDefaultHeader('Accept', 'application/json');
		$this->client->setDefaultHeader('X-Keep', 'yes');
		$merged = $this->client->exposeMergeHeaders(['Accept' => 'text/plain']);
		$this->assertSame('text/plain', $merged['Accept']);
		$this->assertSame('yes', $merged['X-Keep']);
	}

	// ── Header injection guards (HIGH) ─────────────────────────────────────────

	public function testSetDefaultHeaderRejectsCrlfInValue(): void
	{
		$this->expectException(TInvalidDataValueException::class);
		$this->client->setDefaultHeader('X-Test', "ok\r\nX-Injected: evil");
	}

	public function testSetDefaultHeaderRejectsInvalidName(): void
	{
		$this->expectException(TInvalidDataValueException::class);
		$this->client->setDefaultHeader("Bad Name", 'value');
	}

	public function testPerCallHeaderWithCrlfIsRejected(): void
	{
		$this->expectException(TInvalidDataValueException::class);
		$this->client->get('users', [], [], ['X-Evil' => "a\r\nHost: evil"]);
	}

	public function testSetBearerTokenRejectsCrlf(): void
	{
		$this->expectException(TInvalidDataValueException::class);
		$this->client->setBearerToken("tok\r\nX-Injected: 1");
	}

	public function testSetBasicAuthRejectsColonInUsername(): void
	{
		$this->expectException(TInvalidDataValueException::class);
		$this->client->setBasicAuth('al:ice', 'pw');
	}

	public function testSetBasicAuthEmptyCredentialsEncode(): void
	{
		$this->client->setBasicAuth('', '');
		$this->queue(200, '{}');
		$this->client->fetchUser('1');
		$this->assertSame('Basic ' . base64_encode(':'), $this->http->calls[0]['headers']['Authorization']);
	}

	public function testBearerThenBasicAuthLastOneWins(): void
	{
		$this->client->setBearerToken('tok');
		$this->client->setBasicAuth('u', 'p');
		$this->queue(200, '{}');
		$this->client->fetchUser('1');
		$this->assertSame('Basic ' . base64_encode('u:p'), $this->http->calls[0]['headers']['Authorization']);
	}

	// ── requestRaw default-argument paths ──────────────────────────────────────

	public function testRequestRawMinimalArgsMergesDefaultsNoBody(): void
	{
		$this->client->setDefaultHeader('Accept', 'application/json');
		$this->queue(200, '{}');
		$this->client->requestRaw('GET', 'ping');
		$call = $this->http->calls[0];
		$this->assertSame('https://api.example.test/ping', $call['url']);
		$this->assertSame('', $call['body']);
		$this->assertSame(0, $call['request']->getBody()->getSize());
		$this->assertSame('application/json', $call['headers']['Accept']);
	}

	// ── Verb helpers carry both pathParams and body+query ──────────────────────

	public function testPutCarriesPathParamsBodyAndQuery(): void
	{
		$this->queue(200, '{}');
		$this->client->put('users/{id}', ['id' => '5'], ['name' => 'Z'], ['notify' => 1]);
		$call = $this->http->calls[0];
		$this->assertSame('PUT', $call['method']);
		$this->assertSame('https://api.example.test/users/5?notify=1', $call['url']);
		$this->assertSame('{"name":"Z"}', $call['body']);
	}
}
