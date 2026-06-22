<?php

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TIOException;
use Prado\Web\Services\Rest\TRestException;
use Prado\Web\Services\Rest\TRestResource;
use Prado\Web\Services\Rest\TRestService;
use Prado\Xml\TXmlDocument;

// ── Test double: exposes protected methods for white-box testing ───────────────

class TRestServiceExposed extends TRestService
{
	public function exposeCompilePattern(string $pattern, array $params): array
	{
		return $this->compilePattern($pattern, $params);
	}

	public function exposeMatchRoute(string $path): array
	{
		return $this->matchRoute($path);
	}

	public function exposeResolveMethod(string $verb, bool $isItem): string
	{
		return $this->resolveMethod($verb, $isItem);
	}

	public function exposeGetApiPath(string $pathInfo): string
	{
		// applyBasePath expects an already-ltrimmed path, mirroring what getApiPath() does.
		return $this->applyBasePath(ltrim($pathInfo, '/'));
	}

	/** Add a resource directly (bypass XML config). */
	public function addResourceDirect(string $pattern, string $class, array $params = [], array $props = []): void
	{
		$this->addResource($pattern, $class, $params, $props);
	}

	public function exposeLoadResources(mixed $config): void
	{
		$this->loadResources($config);
	}

	public function exposeXmlConfigToArray(\Prado\Xml\TXmlElement $config): array
	{
		return $this->xmlConfigToArray($config);
	}

	public function exposeRegisterResource(array $item, string $prefix = ''): void
	{
		$this->registerResource($item, $prefix);
	}

	public function exposeCreateResource(array $cfg): TRestResource
	{
		return $this->createResource($cfg);
	}

	public function exposeDispatch(TRestResource $r, string $method, array $params): mixed
	{
		return $this->dispatchToResource($r, $method, $params);
	}

	public function exposeIsEnabled(mixed $value): bool
	{
		return $this->isEnabled($value);
	}

	public function exposeLoadConfigFile(string $file): array
	{
		return $this->loadConfigFile($file);
	}

	public function getResources(): array
	{
		// matchRoute walks the table; we read it via reflection for assertions.
		$ref = new ReflectionProperty(TRestService::class, '_resources');
		$ref->setAccessible(true);
		return $ref->getValue($this);
	}

	// ── Injection seam for run() lifecycle tests ──────────────────────────────

	private ?CapturingResponse $injectedResponse = null;

	public function setInjectedResponse(CapturingResponse $r): void
	{
		$this->injectedResponse = $r;
	}

	public function getResponse()
	{
		return $this->injectedResponse ?? parent::getResponse();
	}
}

/**
 * THttpResponse subclass that captures status, headers, body, and content type
 * in memory so run() can be asserted against without touching real HTTP output.
 */
class CapturingResponse extends \Prado\Web\THttpResponse
{
	public int $status = 200;
	public array $headers = [];
	public string $body = '';
	public string $contentType = '';
	public string $charset = '';

	public function getStatusCode(): int { return $this->status; }
	public function setStatusCode($status, $reason = null): void { $this->status = (int) $status; }
	public function appendHeader($header, bool $replace = true, int $response_code = 0): void
	{
		$this->headers[] = $header;
	}
	public function write($str): void { $this->body .= $str; }
	public function setContentType($value): void { $this->contentType = $value; }
	public function setCharset($charset): void { $this->charset = $charset; }

	/** Convenience: find a header line matching the given Name. */
	public function headerLine(string $name): ?string
	{
		$needle = strtolower($name) . ':';
		foreach ($this->headers as $line) {
			if (str_starts_with(strtolower($line), $needle)) {
				return $line;
			}
		}
		return null;
	}
}

// ── Resources used by dispatch / run tests ────────────────────────────────────

class DoStyleResource extends TRestResource
{
	public static array $log = [];

	public function doIndex(): array
	{
		self::$log[] = 'doIndex';
		return ['list' => true];
	}

	public function doShow(string $id): array
	{
		self::$log[] = "doShow:{$id}";
		return ['id' => $id];
	}

	public function doStore(): array
	{
		self::$log[] = 'doStore';
		return $this->created(['created' => true]);
	}

	public function doDestroy(string $id): void
	{
		self::$log[] = "doDestroy:{$id}";
		$this->noContent();
	}
}

class DefaultParamResource extends TRestResource
{
	public function doShow(string $id, string $extra = 'default-extra'): array
	{
		return ['id' => $id, 'extra' => $extra];
	}
}

class MissingParamResource extends TRestResource
{
	public function doShow(string $missing): array
	{
		return ['missing' => $missing];
	}
}

class NotAResource
{
	// Intentionally does not extend TRestResource — used to test createResource() guard.
}

// ── Minimal concrete resource for dispatch tests ───────────────────────────────

class ServiceTestResource extends TRestResource
{
	public static array $log = [];

	public function index(): array
	{
		self::$log[] = 'index';
		return ['list' => true];
	}

	public function show(string $id): array
	{
		self::$log[] = "show:{$id}";
		return ['id' => $id];
	}

	public function store(): array
	{
		self::$log[] = 'store';
		return $this->created(['created' => true]);
	}

	public function update(string $id): array
	{
		self::$log[] = "update:{$id}";
		return ['updated' => true];
	}

	public function patch(string $id): array
	{
		self::$log[] = "patch:{$id}";
		return ['patched' => true];
	}

	public function destroy(string $id): void
	{
		self::$log[] = "destroy:{$id}";
		$this->noContent();
	}
}

// ── Nested-resource for multi-param injection test ────────────────────────────

class ServiceTestNestedResource extends TRestResource
{
	public static array $log = [];

	public function show(string $userId, string $id): array
	{
		self::$log[] = "show:{$userId}/{$id}";
		return ['userId' => $userId, 'id' => $id];
	}

	public function index(string $userId): array
	{
		self::$log[] = "index:{$userId}";
		return ['userId' => $userId];
	}
}

// ── Test class ─────────────────────────────────────────────────────────────────

/**
 * Tests for TRestService.
 */
class TRestServiceTest extends PHPUnit\Framework\TestCase
{
	private TRestServiceExposed $service;

	/** Snapshot of $_SERVER taken before each test so mutations don't leak. */
	private array $serverBackup = [];

	protected function setUp(): void
	{
		ServiceTestResource::$log = [];
		ServiceTestNestedResource::$log = [];

		// Full snapshot — restore any key we touch (PATH_INFO, REQUEST_METHOD,
		// CONTENT_TYPE) AND any key we don't, in case a future test adds more.
		$this->serverBackup = $_SERVER;

		$this->service = new TRestServiceExposed();
		$this->service->setBasePath('api/');
		$_SERVER['PATH_INFO'] = '/api/users';
		$_SERVER['REQUEST_METHOD'] = 'GET';
	}

	protected function tearDown(): void
	{
		$_SERVER = $this->serverBackup;
	}

	// ── compilePattern ─────────────────────────────────────────────────────────

	public function testCompilePatternNoParams(): void
	{
		[$regex, $paramOrder, $isItem] = $this->service->exposeCompilePattern('users', []);
		$this->assertMatchesRegularExpression($regex, 'users');
		$this->assertDoesNotMatchRegularExpression($regex, 'users/123');
		$this->assertSame([], $paramOrder);
		$this->assertFalse($isItem);
	}

	public function testCompilePatternOneParam(): void
	{
		[$regex, $paramOrder, $isItem] = $this->service->exposeCompilePattern('users/{id}', ['id' => '\d+']);
		$this->assertMatchesRegularExpression($regex, 'users/42');
		$this->assertDoesNotMatchRegularExpression($regex, 'users/abc');
		$this->assertDoesNotMatchRegularExpression($regex, 'users/');
		$this->assertSame(['id'], $paramOrder);
		$this->assertTrue($isItem);
	}

	public function testCompilePatternTwoParams(): void
	{
		[$regex, $paramOrder, $isItem] = $this->service->exposeCompilePattern(
			'users/{userId}/posts/{id}',
			['userId' => '\d+', 'id' => '\d+']
		);
		$this->assertMatchesRegularExpression($regex, 'users/7/posts/99');
		$this->assertDoesNotMatchRegularExpression($regex, 'users/7/posts');
		$this->assertSame(['userId', 'id'], $paramOrder);
		$this->assertTrue($isItem);
	}

	public function testCompilePatternNestedCollectionIsNotItem(): void
	{
		[$regex, $paramOrder, $isItem] = $this->service->exposeCompilePattern(
			'users/{userId}/posts',
			['userId' => '\d+']
		);
		$this->assertMatchesRegularExpression($regex, 'users/5/posts');
		$this->assertSame(['userId'], $paramOrder);
		$this->assertFalse($isItem); // last segment is 'posts', not {param}
	}

	public function testCompilePatternDefaultConstraintMatchesNonSlash(): void
	{
		[$regex] = $this->service->exposeCompilePattern('items/{slug}', []);
		$this->assertMatchesRegularExpression($regex, 'items/my-slug');
		$this->assertMatchesRegularExpression($regex, 'items/123');
		$this->assertDoesNotMatchRegularExpression($regex, 'items/a/b'); // no slash
	}

	public function testCompilePatternEscapesLiteralDots(): void
	{
		[$regex] = $this->service->exposeCompilePattern('v1.0/users', []);
		$this->assertMatchesRegularExpression($regex, 'v1.0/users');
		$this->assertDoesNotMatchRegularExpression($regex, 'v100/users'); // dot is literal
	}

	// ── matchRoute ─────────────────────────────────────────────────────────────

	private function serviceWithRoutes(): TRestServiceExposed
	{
		$s = new TRestServiceExposed();
		$s->setBasePath('api/');
		$s->addResourceDirect('users', ServiceTestResource::class);
		$s->addResourceDirect('users/{id}', ServiceTestResource::class, ['id' => '\d+']);
		$s->addResourceDirect('users/{userId}/posts', ServiceTestNestedResource::class, ['userId' => '\d+']);
		$s->addResourceDirect('users/{userId}/posts/{id}', ServiceTestNestedResource::class, ['userId' => '\d+', 'id' => '\d+']);
		return $s;
	}

	public function testMatchRouteCollectionRoute(): void
	{
		$s = $this->serviceWithRoutes();
		[$config, $params] = $s->exposeMatchRoute('users');
		$this->assertSame(ServiceTestResource::class, $config['class']);
		$this->assertSame([], $params);
		$this->assertFalse($config['isItem']);
	}

	public function testMatchRouteItemRoute(): void
	{
		$s = $this->serviceWithRoutes();
		[$config, $params] = $s->exposeMatchRoute('users/42');
		$this->assertSame(ServiceTestResource::class, $config['class']);
		$this->assertSame(['id' => '42'], $params);
		$this->assertTrue($config['isItem']);
	}

	public function testMatchRouteNestedCollection(): void
	{
		$s = $this->serviceWithRoutes();
		[$config, $params] = $s->exposeMatchRoute('users/7/posts');
		$this->assertSame(ServiceTestNestedResource::class, $config['class']);
		$this->assertSame(['userId' => '7'], $params);
		$this->assertFalse($config['isItem']);
	}

	public function testMatchRouteNestedItem(): void
	{
		$s = $this->serviceWithRoutes();
		[$config, $params] = $s->exposeMatchRoute('users/7/posts/3');
		$this->assertSame(ServiceTestNestedResource::class, $config['class']);
		$this->assertSame(['userId' => '7', 'id' => '3'], $params);
		$this->assertTrue($config['isItem']);
	}

	public function testMatchRouteThrows404WhenNoMatch(): void
	{
		$s = $this->serviceWithRoutes();
		$this->expectException(TRestException::class);
		$this->expectExceptionCode(404);
		$s->exposeMatchRoute('nonexistent/path');
	}

	public function testMatchRouteConstraintPreventsAlphaId(): void
	{
		$s = $this->serviceWithRoutes();
		// 'users/abc' does not match 'users/{id}' with id=\d+
		// but also does not match 'users' (needs exact match)
		$this->expectException(TRestException::class);
		$this->expectExceptionCode(404);
		$s->exposeMatchRoute('users/abc');
	}

	// ── resolveMethod ──────────────────────────────────────────────────────────

	public function testResolveMethodGetCollection(): void
	{
		$this->assertSame('doIndex', $this->service->exposeResolveMethod('GET', false));
	}

	public function testResolveMethodHeadCollection(): void
	{
		$this->assertSame('doIndex', $this->service->exposeResolveMethod('HEAD', false));
	}

	public function testResolveMethodGetItem(): void
	{
		$this->assertSame('doShow', $this->service->exposeResolveMethod('GET', true));
	}

	public function testResolveMethodHeadItem(): void
	{
		$this->assertSame('doShow', $this->service->exposeResolveMethod('HEAD', true));
	}

	public function testResolveMethodPost(): void
	{
		$this->assertSame('doStore', $this->service->exposeResolveMethod('POST', false));
		$this->assertSame('doStore', $this->service->exposeResolveMethod('POST', true));
	}

	public function testResolveMethodPutItem(): void
	{
		$this->assertSame('doUpdate', $this->service->exposeResolveMethod('PUT', true));
	}

	public function testResolveMethodPatchItem(): void
	{
		$this->assertSame('doPatch', $this->service->exposeResolveMethod('PATCH', true));
	}

	public function testResolveMethodDelete(): void
	{
		$this->assertSame('doDestroy', $this->service->exposeResolveMethod('DELETE', false));
		$this->assertSame('doDestroy', $this->service->exposeResolveMethod('DELETE', true));
	}

	public function testResolveMethodUnknownVerbThrows405(): void
	{
		$this->expectException(TRestException::class);
		$this->expectExceptionCode(405);
		$this->service->exposeResolveMethod('TRACE', false);
	}

	// ── getApiPath ─────────────────────────────────────────────────────────────

	public function testGetApiPathStripsBasePath(): void
	{
		$result = $this->service->exposeGetApiPath('/api/users/42');
		$this->assertSame('users/42', $result);
	}

	public function testGetApiPathStripsLeadingSlash(): void
	{
		$result = $this->service->exposeGetApiPath('/api/posts');
		$this->assertSame('posts', $result);
	}

	public function testGetApiPathWithEmptyBasePath(): void
	{
		$s = new TRestServiceExposed();
		$s->setBasePath('');
		$result = $s->exposeGetApiPath('/users/5');
		$this->assertSame('users/5', $result);
	}

	public function testGetApiPathWhenBasePathNotPresentThrows404(): void
	{
		// PATH_INFO does not start with the base path — routes must not be
		// reachable outside the configured prefix.
		$this->expectException(TRestException::class);
		$this->expectExceptionCode(404);
		$this->service->exposeGetApiPath('/other/path');
	}

	public function testGetApiPathBareBasePathYieldsRootPath(): void
	{
		// '/api' (no trailing slash) addresses the service root, not a 404.
		$this->assertSame('', $this->service->exposeGetApiPath('/api'));
	}

	// ── Property accessors ─────────────────────────────────────────────────────

	public function testBasePathAccessor(): void
	{
		$s = new TRestService();
		$s->setBasePath('api/v2/');
		$this->assertSame('api/v2/', $s->getBasePath());
	}

	public function testEnableCorsAccessor(): void
	{
		$s = new TRestService();
		$this->assertFalse($s->getEnableCors());
		$s->setEnableCors(true);
		$this->assertTrue($s->getEnableCors());
		$s->setEnableCors('false');
		$this->assertFalse($s->getEnableCors());
	}

	public function testAllowOriginAccessor(): void
	{
		$s = new TRestService();
		$this->assertSame('*', $s->getAllowOrigin());
		$s->setAllowOrigin('https://example.com');
		$this->assertSame('https://example.com', $s->getAllowOrigin());
	}

	public function testAllowMethodsAccessor(): void
	{
		$s = new TRestService();
		$s->setAllowMethods('GET, POST');
		$this->assertSame('GET, POST', $s->getAllowMethods());
	}

	public function testAllowHeadersAccessor(): void
	{
		$s = new TRestService();
		$s->setAllowHeaders('Authorization');
		$this->assertSame('Authorization', $s->getAllowHeaders());
	}

	public function testAllowCredentialsAccessor(): void
	{
		$s = new TRestService();
		$this->assertFalse($s->getAllowCredentials());
		$s->setAllowCredentials(true);
		$this->assertTrue($s->getAllowCredentials());
	}

	public function testMaxAgeAccessor(): void
	{
		$s = new TRestService();
		$this->assertSame(86400, $s->getMaxAge());
		$s->setMaxAge(3600);
		$this->assertSame(3600, $s->getMaxAge());
	}

	public function testExposeErrorsAccessor(): void
	{
		$s = new TRestService();
		$s->setExposeErrors(true);
		$this->assertTrue($s->getExposeErrors());
		$s->setExposeErrors(false);
		$this->assertFalse($s->getExposeErrors());
	}

	// ── compilePattern: constraint with alternation (regression for (?:…) wrap) ─

	public function testCompilePatternAlternationConstraint(): void
	{
		[$regex] = $this->service->exposeCompilePattern(
			'items/{id}',
			['id' => '\d+|new']
		);
		$this->assertMatchesRegularExpression($regex, 'items/42');
		$this->assertMatchesRegularExpression($regex, 'items/new');
		$this->assertDoesNotMatchRegularExpression($regex, 'items/old');
	}

	// ── XML config parsing ────────────────────────────────────────────────────

	private function xmlConfig(string $xml): \Prado\Xml\TXmlElement
	{
		$doc = new TXmlDocument('1.0', 'UTF-8');
		$doc->loadFromString($xml);
		return $doc;
	}

	public function testXmlConfigSimpleResource(): void
	{
		$cfg = $this->xmlConfig('<service><resource pattern="users" class="DoStyleResource" /></service>');
		$arr = $this->service->exposeXmlConfigToArray($cfg);
		$this->assertCount(1, $arr['resources']);
		$this->assertSame('users', $arr['resources'][0]['pattern']);
		$this->assertSame('DoStyleResource', $arr['resources'][0]['class']);
	}

	public function testXmlConfigResourceWithParameters(): void
	{
		$cfg = $this->xmlConfig(
			'<service><resource pattern="users/{id}" class="DoStyleResource" parameters.id="\d+" /></service>'
		);
		$arr = $this->service->exposeXmlConfigToArray($cfg);
		$this->assertSame(['id' => '\d+'], $arr['resources'][0]['parameters']);
	}

	public function testXmlConfigGroupCollectsInlineResources(): void
	{
		$xml = '<service><group prefix="v1/" enabled="true">'
			. '<resource pattern="users" class="DoStyleResource" />'
			. '<resource pattern="users/{id}" class="DoStyleResource" parameters.id="\d+" />'
			. '</group></service>';
		$arr = $this->service->exposeXmlConfigToArray($this->xmlConfig($xml));
		$this->assertCount(1, $arr['groups']);
		$this->assertSame('v1/', $arr['groups'][0]['prefix']);
		$this->assertCount(2, $arr['groups'][0]['resources']);
		// Inline resources inside <group> should NOT be in top-level resources.
		$this->assertSame([], $arr['resources']);
	}

	public function testLoadResourcesAppliesGroupPrefix(): void
	{
		$xml = '<service><group prefix="v1/">'
			. '<resource pattern="users" class="DoStyleResource" />'
			. '</group></service>';
		$this->service->exposeLoadResources($this->xmlConfig($xml));
		$entries = $this->service->getResources();
		$this->assertCount(1, $entries);
		$this->assertSame('v1/users', $entries[0]['pattern']);
	}

	public function testLoadResourcesSkipsDisabledGroup(): void
	{
		$xml = '<service><group prefix="v3/" enabled="false">'
			. '<resource pattern="users" class="DoStyleResource" />'
			. '</group></service>';
		$this->service->exposeLoadResources($this->xmlConfig($xml));
		$this->assertSame([], $this->service->getResources());
	}

	public function testLoadResourcesViaPhpArray(): void
	{
		$cfg = [
			'resources' => [
				['pattern' => 'a', 'class' => 'DoStyleResource'],
			],
			'groups' => [
				['prefix' => 'v2/', 'resources' => [['pattern' => 'users', 'class' => 'DoStyleResource']]],
			],
		];
		// Force PHP config path by overriding configurationType — easier to just call
		// the underlying registration directly via the public seam:
		$this->service->exposeRegisterResource($cfg['resources'][0]);
		foreach ($cfg['groups'][0]['resources'] as $r) {
			$this->service->exposeRegisterResource($r, $cfg['groups'][0]['prefix']);
		}
		$entries = $this->service->getResources();
		$this->assertSame('a', $entries[0]['pattern']);
		$this->assertSame('v2/users', $entries[1]['pattern']);
	}

	public function testRegisterResourceThrowsWhenPatternMissing(): void
	{
		$this->expectException(TConfigurationException::class);
		$this->service->exposeRegisterResource(['class' => 'DoStyleResource']);
	}

	public function testRegisterResourceThrowsWhenClassMissing(): void
	{
		$this->expectException(TConfigurationException::class);
		$this->service->exposeRegisterResource(['pattern' => 'x']);
	}

	// ── Group file loading ────────────────────────────────────────────────────

	private function withFixtureAlias(callable $fn): void
	{
		Prado::setPathOfAlias('RestFixtures', __DIR__ . '/fixtures');
		try {
			$fn();
		} finally {
			// no remove API; alias persists per process — harmless for tests
		}
	}

	public function testLoadResourcesFromPhpGroupFile(): void
	{
		$this->withFixtureAlias(function () {
			$xml = '<service><group prefix="api/" groupfile="RestFixtures.rest-php" /></service>';
			$this->service->exposeLoadResources($this->xmlConfig($xml));
			$entries = $this->service->getResources();
			$patterns = array_column($entries, 'pattern');
			$this->assertContains('api/php-users', $patterns);
			$this->assertContains('api/php-users/{id}', $patterns);
		});
	}

	public function testLoadResourcesFromXmlGroupFile(): void
	{
		$this->withFixtureAlias(function () {
			$xml = '<service><group prefix="api/" groupfile="RestFixtures.rest-xml" /></service>';
			$this->service->exposeLoadResources($this->xmlConfig($xml));
			$entries = $this->service->getResources();
			$patterns = array_column($entries, 'pattern');
			$this->assertContains('api/xml-users', $patterns);
			$this->assertContains('api/xml-users/{id}', $patterns);
		});
	}

	public function testLoadResourcesGroupFileNotFoundThrows(): void
	{
		$xml = '<service><group prefix="x/" groupfile="NotAnAlias.nope" /></service>';
		$this->expectException(TIOException::class);
		$this->service->exposeLoadResources($this->xmlConfig($xml));
	}

	// ── createResource ────────────────────────────────────────────────────────

	public function testCreateResourceInstantiatesClass(): void
	{
		$r = $this->service->exposeCreateResource([
			'class' => DoStyleResource::class,
			'properties' => [],
		]);
		$this->assertInstanceOf(DoStyleResource::class, $r);
	}

	public function testCreateResourceRejectsNonResourceClass(): void
	{
		$this->expectException(TConfigurationException::class);
		$this->service->exposeCreateResource([
			'class' => NotAResource::class,
			'properties' => [],
		]);
	}

	// ── dispatchToResource ─────────────────────────────────────────────────────

	public function testDispatchInjectsPathParamsByName(): void
	{
		$r = new DoStyleResource();
		$result = $this->service->exposeDispatch($r, 'doShow', ['id' => '42']);
		$this->assertSame(['id' => '42'], $result);
	}

	public function testDispatchUsesDefaultWhenParamMissing(): void
	{
		$r = new DefaultParamResource();
		$result = $this->service->exposeDispatch($r, 'doShow', ['id' => '7']);
		$this->assertSame(['id' => '7', 'extra' => 'default-extra'], $result);
	}

	public function testDispatchThrows500WhenRequiredParamMissing(): void
	{
		$r = new MissingParamResource();
		try {
			$this->service->exposeDispatch($r, 'doShow', []);
			$this->fail('expected exception');
		} catch (TRestException $e) {
			$this->assertSame(500, $e->getStatusCode());
			$this->assertStringContainsString('missing', $e->getDetail());
		}
	}

	public function testDispatchThrows405WhenMethodMissing(): void
	{
		// Use a subclass that overrides __call so the method really doesn't exist.
		$r = new class () extends TRestResource {};
		try {
			$this->service->exposeDispatch($r, 'doNotARealVerb', []);
			$this->fail('expected exception');
		} catch (TRestException $e) {
			$this->assertSame(405, $e->getStatusCode());
		}
	}

	// ── isEnabled / Debug-aware group enable flag ──────────────────────────────

	public function testIsEnabledBooleanish(): void
	{
		$this->assertTrue($this->service->exposeIsEnabled(true));
		$this->assertTrue($this->service->exposeIsEnabled('true'));
		$this->assertTrue($this->service->exposeIsEnabled('1'));
		$this->assertFalse($this->service->exposeIsEnabled(false));
		$this->assertFalse($this->service->exposeIsEnabled('false'));
		$this->assertFalse($this->service->exposeIsEnabled('0'));
	}

	public function testIsEnabledDebugFollowsApplicationMode(): void
	{
		$app = Prado::getApplication();
		$originalMode = $app->getMode();
		try {
			$app->setMode(\Prado\TApplicationMode::Debug);
			$this->assertTrue($this->service->exposeIsEnabled('Debug'));
			$this->assertTrue($this->service->exposeIsEnabled('debug')); // case-insensitive
			$this->assertTrue($this->service->exposeIsEnabled('DEBUG'));

			$app->setMode(\Prado\TApplicationMode::Normal);
			$this->assertFalse($this->service->exposeIsEnabled('Debug'));

			$app->setMode(\Prado\TApplicationMode::Performance);
			$this->assertFalse($this->service->exposeIsEnabled('Debug'));
		} finally {
			$app->setMode($originalMode);
		}
	}

	public function testLoadResourcesGroupEnabledDebugRespectsMode(): void
	{
		$app = Prado::getApplication();
		$originalMode = $app->getMode();
		try {
			$xml = '<service><group prefix="dbg/" enabled="Debug">'
				. '<resource pattern="x" class="DoStyleResource" />'
				. '</group></service>';

			// Debug mode → group active
			$app->setMode(\Prado\TApplicationMode::Debug);
			$s = new TRestServiceExposed();
			$s->exposeLoadResources($this->xmlConfig($xml));
			$this->assertCount(1, $s->getResources());

			// Performance mode → group skipped
			$app->setMode(\Prado\TApplicationMode::Performance);
			$s = new TRestServiceExposed();
			$s->exposeLoadResources($this->xmlConfig($xml));
			$this->assertCount(0, $s->getResources());
		} finally {
			$app->setMode($originalMode);
		}
	}

	// ── Service-level configfile ───────────────────────────────────────────────

	public function testXmlConfigCapturesConfigfileAttribute(): void
	{
		$cfg = $this->xmlConfig('<service configfile="App.config.rest"><resource pattern="x" class="DoStyleResource"/></service>');
		$arr = $this->service->exposeXmlConfigToArray($cfg);
		$this->assertSame('App.config.rest', $arr['configfile']);
		$this->assertCount(1, $arr['resources']); // inline entries still captured
	}

	public function testLoadConfigFilePrefersPhpWhenBothExist(): void
	{
		$this->withFixtureAlias(function () {
			$cfg = $this->service->exposeLoadConfigFile('RestFixtures.rest-config');
			$patterns = array_column($cfg['resources'], 'pattern');
			// .php fixture should win over the .xml fixture in the same directory
			$this->assertContains('phpcfg-users', $patterns);
			$this->assertNotContains('cfg-users', $patterns);
		});
	}

	public function testLoadConfigFileFromXmlOnlyFixture(): void
	{
		$this->withFixtureAlias(function () {
			$cfg = $this->service->exposeLoadConfigFile('RestFixtures.rest-xmlonly-config');
			$patterns = array_column($cfg['resources'], 'pattern');
			$this->assertContains('xmlcfg-users', $patterns);
			$this->assertCount(1, $cfg['groups']);
			$this->assertSame('v9/', $cfg['groups'][0]['prefix']);
		});
	}

	public function testLoadResourcesUsesConfigfileAttribute(): void
	{
		$this->withFixtureAlias(function () {
			$xml = '<service configfile="RestFixtures.rest-config">'
				. '<resource pattern="inline-extra" class="DoStyleResource" />'
				. '</service>';
			$s = new TRestServiceExposed();
			$s->exposeLoadResources($this->xmlConfig($xml));
			$patterns = array_column($s->getResources(), 'pattern');

			// From the .php config file (preferred over .xml):
			$this->assertContains('phpcfg-users', $patterns);
			$this->assertContains('v2/things', $patterns);
			// Inline entry appended after external entries:
			$this->assertContains('inline-extra', $patterns);
		});
	}

	public function testLoadResourcesConfigfileWithDebugGroupRespectsMode(): void
	{
		// rest-config.xml has a <group prefix="debug/" enabled="Debug">. We can't
		// reach that group via .php-preferred loading, so test via XML directly.
		$app = Prado::getApplication();
		$originalMode = $app->getMode();
		try {
			$this->withFixtureAlias(function () use ($app) {
				$xmlFile = __DIR__ . '/fixtures/rest-config.xml';
				$doc = new TXmlDocument('1.0', 'UTF-8');
				$doc->loadFromFile($xmlFile);

				$app->setMode(\Prado\TApplicationMode::Debug);
				$s = new TRestServiceExposed();
				$s->exposeLoadResources($doc);
				$patterns = array_column($s->getResources(), 'pattern');
				$this->assertContains('debug/dump', $patterns);

				$app->setMode(\Prado\TApplicationMode::Normal);
				$s = new TRestServiceExposed();
				$s->exposeLoadResources($doc);
				$patterns = array_column($s->getResources(), 'pattern');
				$this->assertNotContains('debug/dump', $patterns);
			});
		} finally {
			$app->setMode($originalMode);
		}
	}

	public function testLoadResourcesConfigfileWithNestedGroupfile(): void
	{
		// rest-config.xml has <group prefix="ext/" groupfile="RestFixtures.rest-php"/>.
		// Verify that loading the configfile transitively pulls in the groupfile's resources.
		$this->withFixtureAlias(function () {
			$xmlFile = __DIR__ . '/fixtures/rest-config.xml';
			$doc = new TXmlDocument('1.0', 'UTF-8');
			$doc->loadFromFile($xmlFile);

			$s = new TRestServiceExposed();
			$s->exposeLoadResources($doc);
			$patterns = array_column($s->getResources(), 'pattern');

			// from configfile root:
			$this->assertContains('cfg-users', $patterns);
			$this->assertContains('v2/things', $patterns);
			// from groupfile loaded by a group inside the configfile:
			$this->assertContains('ext/php-users', $patterns);
			$this->assertContains('ext/php-users/{id}', $patterns);
		});
	}

	public function testLoadConfigFileNotFoundThrows(): void
	{
		$this->expectException(TIOException::class);
		$this->service->exposeLoadConfigFile('NotAnAlias.nope');
	}

	// ── run() end-to-end lifecycle ────────────────────────────────────────────

	/**
	 * THttpRequest caches PATH_INFO after init, so updating $_SERVER mid-test
	 * is not enough — we have to overwrite the cached private property.
	 */
	private function forcePathInfo(string $pathInfo): void
	{
		$request = Prado::getApplication()->getRequest();
		$ref = new ReflectionProperty(\Prado\Web\THttpRequest::class, '_pathInfo');
		$ref->setAccessible(true);
		$ref->setValue($request, $pathInfo);
	}

	private function runWith(string $verb, string $pathInfo, ?string $contentType = null): CapturingResponse
	{
		$response = new CapturingResponse();
		$this->service->setInjectedResponse($response);
		$this->service->addResourceDirect('users', DoStyleResource::class);
		$this->service->addResourceDirect('users/{id}', DoStyleResource::class);

		$_SERVER['REQUEST_METHOD'] = $verb;
		if ($contentType !== null) {
			$_SERVER['CONTENT_TYPE'] = $contentType;
		}
		$this->forcePathInfo($pathInfo);

		$this->service->run();
		return $response;
	}

	public function testRunGetCollectionWritesJsonAndStatus200(): void
	{
		$r = $this->runWith('GET', '/api/users');
		$this->assertSame(200, $r->status);
		$this->assertSame('application/json', $r->contentType);
		$this->assertSame(['list' => true], json_decode($r->body, true));
	}

	public function testRunGetItemSendsIdParam(): void
	{
		$r = $this->runWith('GET', '/api/users/42');
		$this->assertSame(200, $r->status);
		$this->assertSame(['id' => '42'], json_decode($r->body, true));
	}

	public function testRunPostSets201ViaCreatedHelper(): void
	{
		$r = $this->runWith('POST', '/api/users');
		$this->assertSame(201, $r->status);
		$this->assertSame(['created' => true], json_decode($r->body, true));
	}

	public function testRunDeleteSets204WithEmptyBody(): void
	{
		$r = $this->runWith('DELETE', '/api/users/9');
		$this->assertSame(204, $r->status);
		$this->assertSame('', $r->body);
	}

	public function testRunHeadSuppressesBodyButStatusIs200(): void
	{
		$r = $this->runWith('HEAD', '/api/users');
		$this->assertSame(200, $r->status);
		$this->assertSame('', $r->body);
	}

	public function testRunUnknownVerbReturnsJson405Error(): void
	{
		$r = $this->runWith('TRACE', '/api/users');
		$this->assertSame(405, $r->status);
		$body = json_decode($r->body, true);
		$this->assertSame(405, $body['status']);
		$this->assertSame('Method Not Allowed', $body['title']);
		// DoStyleResource declares doIndex/doStore/doDestroy — on a collection
		// route every standard verb maps to one of those.
		$this->assertSame('Allow: GET, HEAD, POST, PUT, PATCH, DELETE', $r->headerLine('Allow'));
	}

	public function testRunUnimplementedVerbReturns405WithAllowHeader(): void
	{
		// DoStyleResource has no doPatch — PATCH on an item route is rejected,
		// and the Allow header lists what the resource does support.
		$r = $this->runWith('PATCH', '/api/users/3');
		$this->assertSame(405, $r->status);
		$this->assertSame('Allow: GET, HEAD, POST, DELETE', $r->headerLine('Allow'));
	}

	public function testRunPathNotMatchedYields404Json(): void
	{
		$r = $this->runWith('GET', '/api/nope');
		$this->assertSame(404, $r->status);
		$body = json_decode($r->body, true);
		$this->assertSame(404, $body['status']);
		$this->assertSame('Not Found', $body['title']);
	}

	public function testRunOptionsPreflightReturns204WhenCorsEnabled(): void
	{
		$this->service->setEnableCors(true);
		$r = $this->runWith('OPTIONS', '/api/users');
		$this->assertSame(204, $r->status);
		$this->assertNotNull($r->headerLine('Access-Control-Allow-Origin'));
		$this->assertNotNull($r->headerLine('Access-Control-Allow-Methods'));
	}

	public function testRunCorsEmitsAllowOriginOnRegularRequest(): void
	{
		$this->service->setEnableCors(true);
		$this->service->setAllowOrigin('https://example.com');
		$r = $this->runWith('GET', '/api/users');
		$this->assertSame('Access-Control-Allow-Origin: https://example.com', $r->headerLine('Access-Control-Allow-Origin'));
		$this->assertSame('Vary: Origin', $r->headerLine('Vary'));
	}

	public function testRunCorsCredentialsWithExplicitOriginEmitsHeaders(): void
	{
		$this->service->setEnableCors(true);
		$this->service->setAllowCredentials(true);
		$this->service->setAllowOrigin('https://app.example.org');
		$r = $this->runWith('GET', '/api/users');
		$this->assertSame('Access-Control-Allow-Origin: https://app.example.org', $r->headerLine('Access-Control-Allow-Origin'));
		$this->assertSame('Access-Control-Allow-Credentials: true', $r->headerLine('Access-Control-Allow-Credentials'));
		$this->assertSame('Vary: Origin', $r->headerLine('Vary'));
	}

	public function testInitRejectsCorsCredentialsWithWildcardOrigin(): void
	{
		$s = new TRestServiceExposed();
		$s->setEnableCors(true);
		$s->setAllowCredentials(true);
		$this->expectException(TConfigurationException::class);
		$s->init(null);
	}

	public function testRunCorsCredentialsWithWildcardOriginYields500(): void
	{
		// sendCorsHeaders() re-validates so programmatic misconfiguration after
		// init() surfaces as a 500 instead of reflecting arbitrary origins.
		$this->service->setEnableCors(true);
		$this->service->setAllowCredentials(true);
		$r = $this->runWith('GET', '/api/users');
		$this->assertSame(500, $r->status);
	}

	public function testRunRegularRequestOmitsPreflightOnlyHeaders(): void
	{
		$this->service->setEnableCors(true);
		$r = $this->runWith('GET', '/api/users');
		$this->assertNotNull($r->headerLine('Access-Control-Allow-Origin'));
		$this->assertNull($r->headerLine('Access-Control-Allow-Methods'));
		$this->assertNull($r->headerLine('Access-Control-Allow-Headers'));
		$this->assertNull($r->headerLine('Access-Control-Max-Age'));
	}

	public function testRunCorsWildcardSkipsVaryHeader(): void
	{
		$this->service->setEnableCors(true);
		$this->service->setAllowOrigin('*');
		$r = $this->runWith('GET', '/api/users');
		$this->assertSame('Access-Control-Allow-Origin: *', $r->headerLine('Access-Control-Allow-Origin'));
		$this->assertNull($r->headerLine('Vary'));
	}

	public function testRunUncaughtExceptionProducesJson500(): void
	{
		// Resource whose doIndex throws a plain Exception.
		$throwing = new class () extends TRestResource {
			public function doIndex(): array { throw new \RuntimeException('boom'); }
		};
		$this->service->addResourceDirect('throw', $throwing::class);
		// Re-register the throwing class as a NAMED class is impossible for
		// anonymous; use a different fixture instead.
		$service = new TRestServiceExposed();
		$response = new CapturingResponse();
		$service->setInjectedResponse($response);
		$service->setBasePath('api/');
		$service->addResourceDirect('boom', ThrowingResource::class);
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$this->forcePathInfo('/api/boom');
		$service->run();

		$this->assertSame(500, $response->status);
		$body = json_decode($response->body, true);
		$this->assertSame(500, $body['status']);
		$this->assertSame('Internal Server Error', $body['title']);
	}

	public function testRunExposeErrorsTrueIncludesExceptionMessage(): void
	{
		$service = new TRestServiceExposed();
		$service->setBasePath('api/');
		$service->setExposeErrors(true);
		$response = new CapturingResponse();
		$service->setInjectedResponse($response);
		$service->addResourceDirect('boom', ThrowingResource::class);
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$this->forcePathInfo('/api/boom');
		$service->run();

		$body = json_decode($response->body, true);
		$this->assertSame('boom!', $body['detail']);
	}

	public function testRunExposeErrorsFalseHidesExceptionMessage(): void
	{
		$service = new TRestServiceExposed();
		$service->setBasePath('api/');
		$service->setExposeErrors(false);
		$response = new CapturingResponse();
		$service->setInjectedResponse($response);
		$service->addResourceDirect('boom', ThrowingResource::class);
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$this->forcePathInfo('/api/boom');
		$service->run();

		$body = json_decode($response->body, true);
		$this->assertArrayNotHasKey('detail', $body);
	}

	public function testRunTRestExceptionThrownByResourceIsSerialized(): void
	{
		$service = new TRestServiceExposed();
		$service->setBasePath('api/');
		$response = new CapturingResponse();
		$service->setInjectedResponse($response);
		$service->addResourceDirect('missing/{id}', NotFoundingResource::class);
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$this->forcePathInfo('/api/missing/42');
		$service->run();

		$this->assertSame(404, $response->status);
		$body = json_decode($response->body, true);
		$this->assertSame('User 42 not found.', $body['detail']);
	}

	// ── 422 / 429 end-to-end (HIGH regression) ─────────────────────────────────

	public function testRunUnprocessableEntityEmits422JsonEnvelope(): void
	{
		// Regression: THttpResponse must know 422 so sendErrorResponse() does not
		// raise a secondary exception; the validation envelope must reach the client.
		$service = new TRestServiceExposed();
		$service->setBasePath('api/');
		$response = new CapturingResponse();
		$service->setInjectedResponse($response);
		$service->addResourceDirect('signup', ValidatingResource::class);
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->forcePathInfo('/api/signup');
		$service->run();

		$this->assertSame(422, $response->status);
		$body = json_decode($response->body, true);
		$this->assertSame(422, $body['status']);
		$this->assertSame('Unprocessable Entity', $body['title']);
		$this->assertArrayHasKey('errors', $body);
		$this->assertSame(['email' => ['required']], $body['errors']);
	}

	public function testRunTooManyRequestsEmits429(): void
	{
		$service = new TRestServiceExposed();
		$service->setBasePath('api/');
		$response = new CapturingResponse();
		$service->setInjectedResponse($response);
		$service->addResourceDirect('limited', RateLimitedResource::class);
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$this->forcePathInfo('/api/limited');
		$service->run();

		$this->assertSame(429, $response->status);
		$this->assertSame(429, json_decode($response->body, true)['status']);
	}

	// ── compilePattern parameter-name validation ───────────────────────────────

	public function testCompilePatternRejectsInvalidParamName(): void
	{
		$this->expectException(TConfigurationException::class);
		$this->service->exposeCompilePattern('users/{1bad}', []);
	}

	public function testCompilePatternRejectsDuplicateParamName(): void
	{
		$this->expectException(TConfigurationException::class);
		$this->service->exposeCompilePattern('a/{id}/b/{id}', []);
	}

	// ── applyBasePath segment boundary ─────────────────────────────────────────

	public function testGetApiPathRejectsPartialSegmentPrefix(): void
	{
		// BasePath "api" must not capture "apidocs/x"; it lies outside the base.
		$s = new TRestServiceExposed();
		$s->setBasePath('api');
		$this->expectException(TRestException::class);
		$this->expectExceptionCode(404);
		$s->exposeGetApiPath('/apidocs/x');
	}

	public function testGetApiPathStripsExactSegment(): void
	{
		$s = new TRestServiceExposed();
		$s->setBasePath('api');
		$this->assertSame('users/1', $s->exposeGetApiPath('/api/users/1'));
		$this->assertSame('', $s->exposeGetApiPath('/api'));
	}

	// ── enabled on individual resource ─────────────────────────────────────────

	public function testRegisterResourceSkipsDisabledResource(): void
	{
		$this->service->exposeRegisterResource(['pattern' => 'x', 'class' => DoStyleResource::class, 'enabled' => 'false']);
		$this->assertSame([], $this->service->getResources());
	}

	public function testRegisterResourceKeepsEnabledResource(): void
	{
		$this->service->exposeRegisterResource(['pattern' => 'x', 'class' => DoStyleResource::class, 'enabled' => 'true']);
		$this->assertCount(1, $this->service->getResources());
	}

	// ── loadResources early returns ────────────────────────────────────────────

	public function testLoadResourcesNullConfigIsNoOp(): void
	{
		$this->service->exposeLoadResources(null);
		$this->assertSame([], $this->service->getResources());
	}

	public function testLoadResourcesNonArrayNonXmlConfigIsNoOp(): void
	{
		$this->service->exposeLoadResources('a string');
		$this->assertSame([], $this->service->getResources());
	}

	// ── isEnabled edge cases ───────────────────────────────────────────────────

	public function testIsEnabledDebugIgnoresSurroundingWhitespaceIsLiteral(): void
	{
		// ' Debug ' is not the literal 'Debug', so it falls through to boolean parsing.
		$this->assertFalse($this->service->exposeIsEnabled(' Debug '));
	}

	public function testIsEnabledEmptyStringIsFalse(): void
	{
		$this->assertFalse($this->service->exposeIsEnabled(''));
	}

	// ── 405 Allow header ───────────────────────────────────────────────────────

	public function testRun405EmitsAllowHeader(): void
	{
		// DoStyleResource implements doIndex/doShow/doStore/doDestroy but not doUpdate.
		$r = $this->runWith('PUT', '/api/users/5');
		$this->assertSame(405, $r->status);
		$this->assertNotNull($r->headerLine('Allow'));
	}

	// ── CORS preflight emits the documented values ─────────────────────────────

	public function testCorsPreflightEmitsMethodsHeadersAndMaxAge(): void
	{
		$this->service->setEnableCors(true);
		$this->service->setAllowMethods('GET, POST');
		$this->service->setAllowHeaders('Authorization');
		$this->service->setMaxAge(120);
		$r = $this->runWith('OPTIONS', '/api/users');
		$this->assertSame(204, $r->status);
		$this->assertSame('Access-Control-Allow-Methods: GET, POST', $r->headerLine('Access-Control-Allow-Methods'));
		$this->assertSame('Access-Control-Allow-Headers: Authorization', $r->headerLine('Access-Control-Allow-Headers'));
		$this->assertSame('Access-Control-Max-Age: 120', $r->headerLine('Access-Control-Max-Age'));
	}
}

/** Resource whose doIndex throws a generic exception — used for 500 tests. */
class ThrowingResource extends TRestResource
{
	public function doIndex(): array
	{
		throw new \RuntimeException('boom!');
	}
}

/** Resource whose doShow throws a TRestException 404 with detail. */
class NotFoundingResource extends TRestResource
{
	public function doShow(string $id): array
	{
		$this->notFound("User {$id} not found.");
	}
}

/** Resource whose doStore raises a 422 validation fault. */
class ValidatingResource extends TRestResource
{
	public function doStore(): array
	{
		$this->unprocessable(['email' => ['required']]);
	}
}

/** Resource whose doIndex raises a 429. */
class RateLimitedResource extends TRestResource
{
	public function doIndex(): array
	{
		$this->abort(429, 'Slow down.');
	}
}
