<?php

use Prado\Exceptions\TConfigurationException;
use Prado\Web\Services\Rest\TRestException;
use Prado\Web\Services\Rest\TRestResource;

// ── Concrete test resources ────────────────────────────────────────────────────

/**
 * Minimal resource — overrides only index and show.
 */
class TestReadOnlyResource extends TRestResource
{
	public function index(): array
	{
		return [['id' => 1], ['id' => 2]];
	}

	public function show(string $id): array
	{
		if ($id === '0') {
			$this->notFound("Item {$id} does not exist.");
		}
		return ['id' => $id];
	}
}

/**
 * Full CRUD resource for validation and status helper tests.
 */
class TestCrudResource extends TRestResource
{
	public function store(): array
	{
		$data = $this->validateBody([
			'name' => 'required|string|max:10',
			'age' => 'required|integer|min:0|max:150',
			'email' => 'required|email',
		]);
		return $this->created($data);
	}

	public function destroy(string $id): void
	{
		$this->noContent();
	}
}

/**
 * Resource that exercises every helper method accessible to subclasses.
 */
class TestHelperResource extends TRestResource
{
	public function index(): array
	{
		return $this->accepted(['queued' => true]);
	}

	public function show(): void
	{
		$this->header('X-Custom', 'value')->noContent();
	}
}

/**
 * Resource that enforces auth.
 */
class TestAuthResource extends TRestResource
{
	private bool $allowAll = false;

	public function setAllowAll(bool $v): void
	{
		$this->allowAll = $v;
	}

	public function authorize(string $method): void
	{
		if (!$this->allowAll) {
			$this->unauthorized('Must be authenticated.');
		}
	}

	public function index(): array
	{
		return [];
	}
}

// ── Test class ─────────────────────────────────────────────────────────────────

/**
 * Tests for TRestResource.
 */
class TRestResourceTest extends PHPUnit\Framework\TestCase
{
	/** @var array Snapshot of $_SERVER taken before each test. */
	private array $serverBackup = [];

	protected function setUp(): void
	{
		// Trigger THttpRequest::init() now so it doesn't later overwrite
		// the $_SERVER values our tests set up (init resets REQUEST_METHOD
		// to 'GET' in CLI mode).
		Prado::getApplication()->getRequest();
		$this->serverBackup = $_SERVER;
	}

	protected function tearDown(): void
	{
		$_SERVER = $this->serverBackup;
	}

	private function makeResource(string $class, array $pathParams = []): TRestResource
	{
		/** @var TRestResource $r */
		$r = new $class();
		$r->setPathParameters($pathParams);
		return $r;
	}

	// ── Default 405 behaviour ──────────────────────────────────────────────────

	public function testUndeclaredVerbsThrow405(): void
	{
		$r = $this->makeResource(TestReadOnlyResource::class);

		foreach (['doStore', 'doUpdate', 'doPatch', 'doDestroy'] as $method) {
			try {
				$r->$method();
				$this->fail("Expected TRestException for {$method}()");
			} catch (TRestException $e) {
				$this->assertSame(405, $e->getStatusCode(), "Wrong status for {$method}()");
			}
		}
	}

	// ── Path parameters ────────────────────────────────────────────────────────

	public function testGetPathParameters(): void
	{
		$r = $this->makeResource(TestReadOnlyResource::class, ['id' => '42', 'userId' => '7']);
		$this->assertSame(['id' => '42', 'userId' => '7'], $r->getPathParameters());
	}

	public function testGetPathParameterByName(): void
	{
		$r = $this->makeResource(TestReadOnlyResource::class, ['id' => '99']);
		$this->assertSame('99', $r->getPathParameter('id'));
	}

	public function testGetPathParameterReturnsDefaultWhenAbsent(): void
	{
		$r = $this->makeResource(TestReadOnlyResource::class);
		$this->assertSame('fallback', $r->getPathParameter('missing', 'fallback'));
	}

	// ── Status helpers ─────────────────────────────────────────────────────────

	public function testDefaultStatusCodeIs200(): void
	{
		$r = $this->makeResource(TestReadOnlyResource::class);
		$this->assertSame(200, $r->getStatusCode());
	}

	public function testCreatedSets201AndReturnsData(): void
	{
		$r = $this->makeResource(TestCrudResource::class);
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_SERVER['CONTENT_TYPE'] = 'application/json';

		// Provide a valid body via input stream mock
		$data = ['name' => 'Alice', 'age' => 30, 'email' => 'alice@example.com'];
		// We simulate getBody() returning validated data by directly calling validateBody via store
		// We must mock php://input — use a workaround: set _parsedBody via reflection
		$ref = new ReflectionProperty(TRestResource::class, '_parsedBody');
		$ref->setAccessible(true);
		$ref->setValue($r, $data);

		$result = $r->store();
		$this->assertSame(201, $r->getStatusCode());
		$this->assertSame($data, $result);
	}

	public function testNoContentSets204(): void
	{
		$r = $this->makeResource(TestCrudResource::class, ['id' => '5']);
		$_SERVER['REQUEST_METHOD'] = 'DELETE';
		$r->destroy('5');
		$this->assertSame(204, $r->getStatusCode());
	}

	public function testAcceptedSets202AndReturnsData(): void
	{
		$r = $this->makeResource(TestHelperResource::class);
		$result = $r->index();
		$this->assertSame(202, $r->getStatusCode());
		$this->assertSame(['queued' => true], $result);
	}

	public function testHeaderAddsToResponseHeaders(): void
	{
		$r = $this->makeResource(TestHelperResource::class);
		$r->show();
		$this->assertSame(['X-Custom' => 'value'], $r->getResponseHeaders());
	}

	// ── Exception helpers ──────────────────────────────────────────────────────

	public function testNotFoundThrows404(): void
	{
		$r = $this->makeResource(TestReadOnlyResource::class, ['id' => '0']);
		$this->expectException(TRestException::class);
		$this->expectExceptionCode(404);
		$r->show('0');
	}

	public function testAbortThrowsWithGivenStatus(): void
	{
		$r = $this->makeResource(TestReadOnlyResource::class);
		$called = false;
		try {
			// Use reflection to call protected abort
			$ref = new ReflectionMethod($r, 'abort');
			$ref->setAccessible(true);
			$ref->invoke($r, 409, 'Conflict detail');
		} catch (TRestException $e) {
			$called = true;
			$this->assertSame(409, $e->getStatusCode());
			$this->assertSame('Conflict detail', $e->getDetail());
		}
		$this->assertTrue($called);
	}

	// ── Auth hook ──────────────────────────────────────────────────────────────

	public function testAuthorizeDoesNothingByDefault(): void
	{
		$r = $this->makeResource(TestReadOnlyResource::class);
		// Should not throw
		$r->authorize('index');
		$this->assertTrue(true);
	}

	public function testAuthorizeThrowsWhenDenied(): void
	{
		$r = new TestAuthResource();
		$this->expectException(TRestException::class);
		$r->authorize('index');
	}

	public function testAuthorizePassesWhenAllowed(): void
	{
		$r = new TestAuthResource();
		$r->setAllowAll(true);
		$r->authorize('index');
		$this->assertTrue(true);
	}

	// ── Validation ─────────────────────────────────────────────────────────────

	private function makeValidatingResource(array $body): TestCrudResource
	{
		$r = new TestCrudResource();
		$ref = new ReflectionProperty(TRestResource::class, '_parsedBody');
		$ref->setAccessible(true);
		$ref->setValue($r, $body);
		return $r;
	}

	public function testValidatePassesWithCorrectData(): void
	{
		$r = $this->makeValidatingResource([
			'name' => 'Alice',
			'age' => 30,
			'email' => 'alice@example.com',
		]);
		$result = $r->store();
		$this->assertSame('Alice', $result['name']);
		$this->assertSame(30, $result['age']);
	}

	public function testValidateThrows422OnMissingRequired(): void
	{
		$r = $this->makeValidatingResource(['name' => 'Bob']);
		try {
			$r->store();
			$this->fail('Expected TRestException');
		} catch (TRestException $e) {
			$this->assertSame(422, $e->getStatusCode());
			$errors = $e->getErrors();
			$this->assertArrayHasKey('age', $errors);
			$this->assertArrayHasKey('email', $errors);
		}
	}

	public function testValidateThrows422OnInvalidEmail(): void
	{
		$r = $this->makeValidatingResource([
			'name' => 'Bob',
			'age' => 25,
			'email' => 'not-an-email',
		]);
		try {
			$r->store();
			$this->fail('Expected TRestException');
		} catch (TRestException $e) {
			$this->assertSame(422, $e->getStatusCode());
			$this->assertArrayHasKey('email', $e->getErrors());
		}
	}

	public function testValidateThrows422OnStringTooLong(): void
	{
		$r = $this->makeValidatingResource([
			'name' => str_repeat('a', 11), // max:10
			'age' => 25,
			'email' => 'a@b.com',
		]);
		try {
			$r->store();
			$this->fail('Expected TRestException');
		} catch (TRestException $e) {
			$this->assertSame(422, $e->getStatusCode());
			$this->assertArrayHasKey('name', $e->getErrors());
		}
	}

	public function testValidateThrows422OnIntegerOutOfRange(): void
	{
		$r = $this->makeValidatingResource([
			'name' => 'Bob',
			'age' => 200, // max:150
			'email' => 'a@b.com',
		]);
		try {
			$r->store();
			$this->fail('Expected TRestException');
		} catch (TRestException $e) {
			$this->assertSame(422, $e->getStatusCode());
			$this->assertArrayHasKey('age', $e->getErrors());
		}
	}

	public function testValidateCoercesNumericStringToInteger(): void
	{
		// Expose validate() via a fresh resource
		$r = new class () extends TRestResource {
			public function callValidate(array $data, array $rules): array
			{
				return $this->validate($data, $rules);
			}
		};
		$result = $r->callValidate(['count' => '7'], ['count' => 'integer']);
		$this->assertSame(7, $result['count']);
	}

	public function testValidateNullableFieldAcceptsNull(): void
	{
		$r = new class () extends TRestResource {
			public function callValidate(array $data, array $rules): array
			{
				return $this->validate($data, $rules);
			}
		};
		$result = $r->callValidate(['bio' => null], ['bio' => 'nullable|string']);
		$this->assertNull($result['bio']);
	}

	public function testValidateInRuleAcceptsValidValue(): void
	{
		$r = new class () extends TRestResource {
			public function callValidate(array $data, array $rules): array
			{
				return $this->validate($data, $rules);
			}
		};
		$result = $r->callValidate(['status' => 'active'], ['status' => 'in:active,inactive,pending']);
		$this->assertSame('active', $result['status']);
	}

	public function testValidateInRuleRejectsInvalidValue(): void
	{
		$r = new class () extends TRestResource {
			public function callValidate(array $data, array $rules): array
			{
				return $this->validate($data, $rules);
			}
		};
		try {
			$r->callValidate(['status' => 'deleted'], ['status' => 'in:active,inactive']);
			$this->fail('Expected TRestException');
		} catch (TRestException $e) {
			$this->assertSame(422, $e->getStatusCode());
		}
	}

	// ── Input helpers ──────────────────────────────────────────────────────────

	public function testOnlyReturnsSubsetOfBody(): void
	{
		$r = new class () extends TRestResource {
			public function callOnly(array $keys): array
			{
				return $this->only($keys);
			}
		};
		$ref = new ReflectionProperty(TRestResource::class, '_parsedBody');
		$ref->setAccessible(true);
		$ref->setValue($r, ['a' => 1, 'b' => 2, 'c' => 3]);

		$result = $r->callOnly(['a', 'c']);
		$this->assertSame(['a' => 1, 'c' => 3], $result);
	}

	public function testExceptRemovesKeysFromBody(): void
	{
		$r = new class () extends TRestResource {
			public function callExcept(array $keys): array
			{
				return $this->except($keys);
			}
		};
		$ref = new ReflectionProperty(TRestResource::class, '_parsedBody');
		$ref->setAccessible(true);
		$ref->setValue($r, ['a' => 1, 'b' => 2, 'c' => 3]);

		$result = $r->callExcept(['b']);
		$this->assertSame(['a' => 1, 'c' => 3], $result);
	}

	// ── getBody (covers JSON, form-POST, form-PUT/PATCH, and GET paths) ────────

	private function bodyResource(string $rawBody): TRestResource
	{
		return new class ($rawBody) extends TRestResource {
			public function __construct(private string $rawBody)
			{
				parent::__construct();
			}
			protected function readRawRequestBody(): string
			{
				return $this->rawBody;
			}
			public function callGetBody(): array
			{
				return $this->getBody();
			}
		};
	}

	public function testGetBodyJsonPost(): void
	{
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_SERVER['CONTENT_TYPE'] = 'application/json';
		$r = $this->bodyResource('{"name":"Alice","age":30}');
		$this->assertSame(['name' => 'Alice', 'age' => 30], $r->callGetBody());
	}

	public function testGetBodyJsonPutAndPatch(): void
	{
		foreach (['PUT', 'PATCH'] as $verb) {
			$_SERVER['REQUEST_METHOD'] = $verb;
			$_SERVER['CONTENT_TYPE'] = 'application/json; charset=utf-8';
			$r = $this->bodyResource('{"v":1}');
			$this->assertSame(['v' => 1], $r->callGetBody(), "verb={$verb}");
		}
	}

	public function testGetBodyMalformedJsonThrows400(): void
	{
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_SERVER['CONTENT_TYPE'] = 'application/json';
		$r = $this->bodyResource('not json');
		$this->expectException(\Prado\Web\Services\Rest\TRestException::class);
		$this->expectExceptionCode(400);
		$r->callGetBody();
	}

	public function testGetBodyJsonEmptyBodyYieldsEmptyArray(): void
	{
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_SERVER['CONTENT_TYPE'] = 'application/json';
		$r = $this->bodyResource('');
		$this->assertSame([], $r->callGetBody());
	}

	public function testGetBodyFormPostReadsSuperglobal(): void
	{
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
		$_POST = ['x' => '1', 'y' => 'two'];
		$r = $this->bodyResource(''); // raw stream irrelevant for POST form
		$this->assertSame(['x' => '1', 'y' => 'two'], $r->callGetBody());
		$_POST = [];
	}

	public function testGetBodyFormPutParsesRawStream(): void
	{
		// Regression: PHP's $_POST is not populated for PUT — must parse php://input.
		$_SERVER['REQUEST_METHOD'] = 'PUT';
		$_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
		$r = $this->bodyResource('name=Bob&age=25');
		$this->assertSame(['name' => 'Bob', 'age' => '25'], $r->callGetBody());
	}

	public function testGetBodyFormPatchParsesRawStream(): void
	{
		$_SERVER['REQUEST_METHOD'] = 'PATCH';
		$_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
		$r = $this->bodyResource('role=admin');
		$this->assertSame(['role' => 'admin'], $r->callGetBody());
	}

	public function testGetBodyGetReturnsEmpty(): void
	{
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$r = $this->bodyResource('ignored');
		$this->assertSame([], $r->callGetBody());
	}

	public function testGetBodyIsCachedAfterFirstCall(): void
	{
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_SERVER['CONTENT_TYPE'] = 'application/json';
		$r = $this->bodyResource('{"a":1}');
		$first = $r->callGetBody();
		$second = $r->callGetBody();
		$this->assertSame($first, $second);
	}

	// ── input/query/hasInput ───────────────────────────────────────────────────

	private function inputResource(array $body = []): TRestResource
	{
		$r = new class () extends TRestResource {
			public function callInput(string $k, mixed $d = null): mixed
			{
				return $this->input($k, $d);
			}
			public function callQuery(string $k, mixed $d = null): mixed
			{
				return $this->query($k, $d);
			}
			public function callHasInput(string $k): bool
			{
				return $this->hasInput($k);
			}
			public function callOnly(array $keys): array
			{
				return $this->only($keys);
			}
			public function callExcept(array $keys): array
			{
				return $this->except($keys);
			}
		};
		$ref = new ReflectionProperty(TRestResource::class, '_parsedBody');
		$ref->setAccessible(true);
		$ref->setValue($r, $body);
		return $r;
	}

	public function testInputReadsFromBody(): void
	{
		$r = $this->inputResource(['name' => 'Alice']);
		$this->assertSame('Alice', $r->callInput('name'));
	}

	public function testInputFallsBackToQueryString(): void
	{
		$_GET['filter'] = 'active';
		try {
			$r = $this->inputResource([]);
			$this->assertSame('active', $r->callInput('filter'));
		} finally {
			unset($_GET['filter']);
		}
	}

	public function testInputReturnsDefaultWhenAbsent(): void
	{
		$r = $this->inputResource([]);
		$this->assertSame('fallback', $r->callInput('missing', 'fallback'));
	}

	public function testQueryReadsFromQueryStringOnly(): void
	{
		$_GET['q'] = 'search';
		try {
			$r = $this->inputResource(['q' => 'from-body']);
			$this->assertSame('search', $r->callQuery('q'));
		} finally {
			unset($_GET['q']);
		}
	}

	public function testQueryIgnoresNonQueryRequestParameters(): void
	{
		// Routing parameters and form-POST fields live in THttpRequest's merged
		// map but are not query-string values — query() must not see them.
		$request = Prado::getApplication()->getRequest();
		$request->add('routed', 'value');
		try {
			$r = $this->inputResource([]);
			$this->assertNull($r->callQuery('routed'));
		} finally {
			$request->remove('routed');
		}
	}

	public function testQueryReturnsDefaultWhenAbsent(): void
	{
		$r = $this->inputResource([]);
		$this->assertSame('def', $r->callQuery('missing', 'def'));
	}

	public function testHasInputTrueForBody(): void
	{
		$r = $this->inputResource(['x' => 1]);
		$this->assertTrue($r->callHasInput('x'));
	}

	public function testHasInputTrueForQuery(): void
	{
		$_GET['z'] = '1';
		try {
			$r = $this->inputResource([]);
			$this->assertTrue($r->callHasInput('z'));
		} finally {
			unset($_GET['z']);
		}
	}

	public function testHasInputFalseWhenAbsent(): void
	{
		$r = $this->inputResource([]);
		$this->assertFalse($r->callHasInput('missing'));
	}

	// ── Remaining validation rules ─────────────────────────────────────────────

	private function validator(): TRestResource
	{
		return new class () extends TRestResource {
			public function v(array $data, array $rules): array
			{
				return $this->validate($data, $rules);
			}
		};
	}

	public function testValidateBooleanRulePasses(): void
	{
		$v = $this->validator();
		foreach ([true, false, 1, 0, '1', '0', 'true', 'false'] as $val) {
			$result = $v->v(['flag' => $val], ['flag' => 'boolean']);
			$this->assertIsBool($result['flag']);
		}
	}

	public function testValidateBooleanRuleRejectsNonBoolean(): void
	{
		$this->expectException(TRestException::class);
		$this->validator()->v(['flag' => 'yes'], ['flag' => 'boolean']);
	}

	public function testValidateArrayRulePassesAndRejects(): void
	{
		$v = $this->validator();
		$this->assertSame(['tags' => ['a', 'b']], $v->v(['tags' => ['a', 'b']], ['tags' => 'array']));

		$this->expectException(TRestException::class);
		$v->v(['tags' => 'not-array'], ['tags' => 'array']);
	}

	public function testValidateUrlRulePassesAndRejects(): void
	{
		$v = $this->validator();
		$this->assertSame(['site' => 'https://example.com'], $v->v(['site' => 'https://example.com'], ['site' => 'url']));

		$this->expectException(TRestException::class);
		$v->v(['site' => 'not a url'], ['site' => 'url']);
	}

	public function testValidateFloatAndNumericCoerce(): void
	{
		$v = $this->validator();
		$result = $v->v(['p' => '3.14'], ['p' => 'numeric']);
		$this->assertSame(3.14, $result['p']);
		$result = $v->v(['p' => '2'], ['p' => 'float']);
		$this->assertSame(2.0, $result['p']);
	}

	public function testValidateNumericRejectsNonNumeric(): void
	{
		$this->expectException(TRestException::class);
		$this->validator()->v(['p' => 'abc'], ['p' => 'numeric']);
	}

	public function testValidateMinAndMaxOnNumber(): void
	{
		$v = $this->validator();
		// min on number
		$result = $v->v(['n' => 10], ['n' => 'integer|min:5']);
		$this->assertSame(10, $result['n']);
		try {
			$v->v(['n' => 1], ['n' => 'integer|min:5']);
			$this->fail('expected min violation');
		} catch (TRestException $e) {
			$this->assertSame(422, $e->getStatusCode());
		}
		// min on string length
		$result = $v->v(['s' => 'hello'], ['s' => 'string|min:3']);
		$this->assertSame('hello', $result['s']);
		try {
			$v->v(['s' => 'hi'], ['s' => 'string|min:3']);
			$this->fail('expected string min violation');
		} catch (TRestException $e) {
			$this->assertSame(422, $e->getStatusCode());
		}
	}

	public function testValidateNullableSkipsTypeRulesWhenNull(): void
	{
		$v = $this->validator();
		// nullable + integer with null should not error
		$result = $v->v(['n' => null], ['n' => 'nullable|integer']);
		$this->assertNull($result['n']);
	}

	public function testValidateRequiredAndNullableTogether(): void
	{
		// When both are present and value is null, required wins (error).
		$this->expectException(TRestException::class);
		$this->validator()->v(['x' => null], ['x' => 'required|nullable|string']);
	}

	public function testValidateOmitsUndeclaredFields(): void
	{
		$result = $this->validator()->v(
			['name' => 'Alice', 'secret' => 'leak'],
			['name' => 'string']
		);
		$this->assertSame(['name' => 'Alice'], $result);
	}

	public function testValidateUnknownRuleThrowsConfigurationException(): void
	{
		// A typo in a rule name is a developer error, not a 422 for the client.
		$this->expectException(TConfigurationException::class);
		$this->validator()->v(['name' => 'Alice'], ['name' => 'requried|string']);
	}

	// ── Remaining exception helpers ────────────────────────────────────────────

	public function testForbiddenThrows403(): void
	{
		$r = new class () extends TRestResource {
			public function go(): void
			{
				$this->forbidden('nope');
			}
		};
		try {
			$r->go();
			$this->fail('expected exception');
		} catch (TRestException $e) {
			$this->assertSame(403, $e->getStatusCode());
			$this->assertSame('nope', $e->getDetail());
		}
	}

	public function testConflictThrows409(): void
	{
		$r = new class () extends TRestResource {
			public function go(): void
			{
				$this->conflict('dup');
			}
		};
		try {
			$r->go();
			$this->fail('expected exception');
		} catch (TRestException $e) {
			$this->assertSame(409, $e->getStatusCode());
		}
	}

	public function testUnprocessableThrowsWithFieldErrors(): void
	{
		$r = new class () extends TRestResource {
			public function go(): void
			{
				$this->unprocessable(['email' => ['bad']], 'invalid');
			}
		};
		try {
			$r->go();
			$this->fail('expected exception');
		} catch (TRestException $e) {
			$this->assertSame(422, $e->getStatusCode());
			$this->assertSame(['email' => ['bad']], $e->getErrors());
		}
	}

	// ── __call delegates to overridden methods ─────────────────────────────────

	public function testCallDelegatesToOverriddenConventionMethod(): void
	{
		// TestReadOnlyResource overrides index(), not doIndex(). __call on doIndex must throw 405.
		$r = new TestReadOnlyResource();
		try {
			$r->doIndex();
			$this->fail('expected 405');
		} catch (TRestException $e) {
			$this->assertSame(405, $e->getStatusCode());
		}
	}

	public function testCallUnknownMethodThrowsBadMethodCall(): void
	{
		$r = new TestReadOnlyResource();
		$this->expectException(BadMethodCallException::class);
		$r->somethingTotallyMadeUp();
	}

	// ── Validation edge cases (regressions + per-rule coverage) ────────────────

	public function testValidateIntegerRejectsDoubleMinus(): void
	{
		// Regression: '--5' previously passed ctype_digit(ltrim($v, '-')) and
		// got cast to 0. filter_var-based check rejects it cleanly.
		$this->expectException(TRestException::class);
		$this->validator()->v(['n' => '--5'], ['n' => 'integer']);
	}

	public function testValidateIntegerRejectsTrailingNoise(): void
	{
		$this->expectException(TRestException::class);
		$this->validator()->v(['n' => '5abc'], ['n' => 'integer']);
	}

	public function testValidateIntegerRejectsFloatString(): void
	{
		$this->expectException(TRestException::class);
		$this->validator()->v(['n' => '1.5'], ['n' => 'integer']);
	}

	public function testValidateIntegerRejectsScientificNotation(): void
	{
		$this->expectException(TRestException::class);
		$this->validator()->v(['n' => '5e2'], ['n' => 'integer']);
	}

	public function testValidateIntegerAcceptsNegativeAndZero(): void
	{
		$v = $this->validator();
		$this->assertSame(-7, $v->v(['n' => '-7'], ['n' => 'integer'])['n']);
		$this->assertSame(0, $v->v(['n' => '0'], ['n' => 'integer'])['n']);
		$this->assertSame(42, $v->v(['n' => 42], ['n' => 'integer'])['n']);
	}

	/** @dataProvider booleanLikeValues */
	public function testValidateBooleanAcceptsEachLikeValue(mixed $value, bool $expected): void
	{
		$result = $this->validator()->v(['flag' => $value], ['flag' => 'boolean']);
		$this->assertSame($expected, $result['flag']);
	}

	public static function booleanLikeValues(): array
	{
		return [
			'bool true' => [true, true],
			'bool false' => [false, false],
			'int 1' => [1, true],
			'int 0' => [0, false],
			'string "1"' => ['1', true],
			'string "0"' => ['0', false],
			'string "true"' => ['true', true],
			'string "false"' => ['false', false],
		];
	}

	public function testValidateBoolAliasMatchesBoolean(): void
	{
		$result = $this->validator()->v(['flag' => '1'], ['flag' => 'bool']);
		$this->assertTrue($result['flag']);
	}

	public function testValidateMaxOnNumberRejectsAboveLimit(): void
	{
		$this->expectException(TRestException::class);
		$this->validator()->v(['n' => 200], ['n' => 'integer|max:150']);
	}

	public function testValidateMaxOnNumberAcceptsBoundary(): void
	{
		$result = $this->validator()->v(['n' => 150], ['n' => 'integer|max:150']);
		$this->assertSame(150, $result['n']);
	}

	public function testValidateMinOnNumberAcceptsBoundary(): void
	{
		$result = $this->validator()->v(['n' => 5], ['n' => 'integer|min:5']);
		$this->assertSame(5, $result['n']);
	}

	public function testValidateMaxOnStringUsesMultibyteLength(): void
	{
		// mb_strlen counts characters, not bytes; the four-character string
		// "héllo" (5 chars including é) should be rejected by max:4.
		$this->expectException(TRestException::class);
		$this->validator()->v(['s' => 'héllo'], ['s' => 'string|max:4']);
	}

	public function testValidateMinOnStringUsesMultibyteLength(): void
	{
		// "héllo" is 5 mb chars; min:5 must pass.
		$result = $this->validator()->v(['s' => 'héllo'], ['s' => 'string|min:5']);
		$this->assertSame('héllo', $result['s']);
	}

	public function testValidateInRuleTrimsWhitespace(): void
	{
		$result = $this->validator()->v(
			['status' => 'active'],
			['status' => 'in: active , inactive , pending ']
		);
		$this->assertSame('active', $result['status']);
	}

	public function testValidateEmailRejectsMissingTld(): void
	{
		$this->expectException(TRestException::class);
		$this->validator()->v(['e' => 'user@'], ['e' => 'email']);
	}

	public function testValidateUrlRejectsBareHostname(): void
	{
		$this->expectException(TRestException::class);
		$this->validator()->v(['site' => 'example.com'], ['site' => 'url']);
	}

	public function testValidateRequiredMissingField(): void
	{
		try {
			$this->validator()->v([], ['name' => 'required|string']);
			$this->fail('expected exception');
		} catch (TRestException $e) {
			$this->assertArrayHasKey('name', $e->getErrors());
		}
	}

	public function testValidateMultipleFieldErrorsCollected(): void
	{
		try {
			$this->validator()->v(
				['email' => 'bad', 'age' => 'x'],
				['email' => 'email', 'age' => 'integer']
			);
			$this->fail('expected exception');
		} catch (TRestException $e) {
			$errors = $e->getErrors();
			$this->assertArrayHasKey('email', $errors);
			$this->assertArrayHasKey('age', $errors);
		}
	}

	// ── in: rule scalar guard (bug regression) ─────────────────────────────────

	public function testValidateInRuleRejectsArrayValueWithoutWarning(): void
	{
		// An array value must fail 'in' validation rather than emitting an
		// "Array to string conversion" warning.
		$this->expectException(TRestException::class);
		$this->validator()->v(['role' => ['admin']], ['role' => 'in:admin,editor']);
	}

	public function testValidateInRuleAcceptsValidScalar(): void
	{
		$out = $this->validator()->v(['role' => 'admin'], ['role' => 'in:admin,editor']);
		$this->assertSame(['role' => 'admin'], $out);
	}

	// ── validate() rule-as-array and presence/null branches ────────────────────

	public function testValidateRuleSuppliedAsArray(): void
	{
		$out = $this->validator()->v(['n' => '5'], ['n' => ['required', 'integer']]);
		$this->assertSame(['n' => 5], $out);
	}

	public function testValidateRequiredPresentButNullFails(): void
	{
		$this->expectException(TRestException::class);
		$this->validator()->v(['name' => null], ['name' => 'required|string']);
	}

	public function testValidateNullableAbsentOmitsField(): void
	{
		$out = $this->validator()->v([], ['nickname' => 'nullable|string']);
		$this->assertSame([], $out);
	}

	public function testValidateNullablePresentNullKeepsNull(): void
	{
		$out = $this->validator()->v(['nickname' => null], ['nickname' => 'nullable|string']);
		$this->assertArrayHasKey('nickname', $out);
		$this->assertNull($out['nickname']);
	}

	public function testValidateBooleanRuleCoercesValue(): void
	{
		$out = $this->validator()->v(['flag' => 'true'], ['flag' => 'boolean']);
		$this->assertTrue($out['flag']);
		$out2 = $this->validator()->v(['flag' => '0'], ['flag' => 'boolean']);
		$this->assertFalse($out2['flag']);
	}

	// ── getBody() edge cases ───────────────────────────────────────────────────

	public function testGetBodyValidJsonScalarYieldsEmptyArray(): void
	{
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_SERVER['CONTENT_TYPE'] = 'application/json';
		$r = $this->bodyResource('42');
		$this->assertSame([], $r->callGetBody());
	}

	public function testGetBodyJsonListIsReturned(): void
	{
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_SERVER['CONTENT_TYPE'] = 'application/json';
		$r = $this->bodyResource('[1,2,3]');
		$this->assertSame([1, 2, 3], $r->callGetBody());
	}

	public function testGetBodyGetVerbReturnsEmptyArray(): void
	{
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$r = $this->bodyResource('{"a":1}');
		$this->assertSame([], $r->callGetBody());
	}

	// ── input()/hasInput() null-value branch ───────────────────────────────────

	public function testInputReturnsExplicitNullBodyValue(): void
	{
		$r = $this->inputResource(['opt' => null]);
		// array_key_exists path: present-but-null returns null, not the default.
		$this->assertNull($r->callInput('opt', 'fallback'));
	}

	public function testHasInputTrueForNullBodyValue(): void
	{
		$r = $this->inputResource(['opt' => null]);
		$this->assertTrue($r->callHasInput('opt'));
	}

	// ── only()/except() edge cases ─────────────────────────────────────────────

	public function testOnlyAndExceptWithMissingAndEmptyKeys(): void
	{
		$r = $this->inputResource(['a' => 1, 'b' => 2]);
		$this->assertSame(['a' => 1], $r->callOnly(['a', 'missing']));
		$this->assertSame([], $r->callOnly([]));
		$this->assertSame(['a' => 1, 'b' => 2], $r->callExcept([]));
		$this->assertSame(['b' => 2], $r->callExcept(['a']));
	}

	// ── response header injection guard (security) ─────────────────────────────

	public function testHeaderRejectsCrlfInValue(): void
	{
		$r = new class () extends TRestResource {
			public function call(string $n, string $v): void
			{
				$this->header($n, $v);
			}
		};
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		$r->call('X-Test', "ok\r\nX-Injected: evil");
	}

	public function testHeaderRejectsInvalidName(): void
	{
		$r = new class () extends TRestResource {
			public function call(string $n, string $v): void
			{
				$this->header($n, $v);
			}
		};
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		$r->call('Bad Name', 'value');
	}

	public function testHeaderReturnsSelfForFluency(): void
	{
		$r = new class () extends TRestResource {
			public function call(): mixed
			{
				return $this->header('X-A', '1')->header('X-B', '2');
			}
			public function headers(): array
			{
				return $this->getResponseHeaders();
			}
		};
		$this->assertSame($r, $r->call());
		$this->assertSame(['X-A' => '1', 'X-B' => '2'], $r->headers());
	}
}
