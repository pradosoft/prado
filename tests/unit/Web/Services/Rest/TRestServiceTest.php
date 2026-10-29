<?php

namespace Prado\Test\Unit\Web\Services\Rest;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\THttpException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Prado;
use Prado\TApplication;
use Prado\TApplicationMode;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\Services\Rest\IRestResource;
use Prado\Web\Services\Rest\TRestException;
use Prado\Web\Services\Rest\TRestResource;
use Prado\Web\Services\Rest\TRestRoute;
use Prado\Web\Services\Rest\TRestRoutePattern;
use Prado\Web\Services\Rest\TRestService;
use Prado\Web\THttpRequest;
use Prado\Web\THttpRequestUrlFormat;
use Prado\Web\THttpResponse;
use Prado\Web\TMediaType;
use Prado\Xml\TXmlDocument;

// ── Test doubles ──────────────────────────────────────────────────────────────

/**
 * Exposes the protected methods of TRestService and takes an injected response.
 */
class TRestServiceExposed extends TRestService
{
	private ?CapturingResponse $injectedResponse = null;

	public function setInjectedResponse(CapturingResponse $response): void
	{
		$this->injectedResponse = $response;
	}

	public function getResponse()
	{
		return $this->injectedResponse ?? parent::getResponse();
	}

	public function __call($name, $args)
	{
		if (method_exists($this, $name)) {
			return $this->$name(...$args);
		}
		return parent::__call($name, $args);
	}
}

/**
 * Records status, headers, content type and body in memory.
 */
class CapturingResponse extends THttpResponse
{
	public int $status = 200;
	public array $headers = [];
	public string $body = '';
	public string $contentType = '';

	public function getStatusCode(): int
	{
		return $this->status;
	}

	public function setStatusCode($status, $reason = null): void
	{
		$this->status = (int) $status;
	}

	public function appendHeader($header, bool $replace = true, int $response_code = 0): void
	{
		$this->headers[] = $header;
	}

	public function write($str): void
	{
		$this->body .= $str;
	}

	public function setContentType($value): void
	{
		$this->contentType = $value;
	}

	public function setCharset($charset): void
	{
	}

	public function headerLine(string $name): ?string
	{
		foreach ($this->headers as $line) {
			if (str_starts_with(strtolower($line), strtolower($name) . ':')) {
				return $line;
			}
		}
		return null;
	}
}

// ── Resources ─────────────────────────────────────────────────────────────────

class TypedParamsResource extends TRestResource
{
	#[TRestRoute('GET', 'typed/{n}/{f}/{b}')]
	public function typed(int $n, float $f, bool $b, string $rest = 'default'): array
	{
		return ['n' => $n, 'f' => $f, 'b' => $b, 'rest' => $rest];
	}

	#[TRestRoute('GET', 'inject')]
	public function inject(THttpRequest $request, TRestRoutePattern $route): array
	{
		return ['request' => $request::class, 'route' => $route->getName()];
	}

	#[TRestRoute('GET', 'unbound')]
	public function unbound(string $nope): array
	{
		return [$nope];
	}

	#[TRestRoute(null, 'any')]
	public function any(): array
	{
		return ['any' => true];
	}
}

class FailingResource extends TRestResource
{
	public function authorize(TRestRoutePattern $route): void
	{
		if ($route->getMethod() === 'auth') {
			$this->unauthorized('Sign in.');
		}
	}

	#[TRestRoute('GET', 'fail/notfound')]
	public function notFoundHandler(): array
	{
		$this->notFound('gone');
	}

	#[TRestRoute('GET', 'fail/http')]
	public function httpHandler(): array
	{
		throw new THttpException(410, 'Gone for good');
	}

	#[TRestRoute('GET', 'fail/boom')]
	public function boomHandler(): array
	{
		throw new \RuntimeException('boom');
	}

	#[TRestRoute('GET', 'fail/auth')]
	public function auth(): array
	{
		return [];
	}
}

class BadRoutesResource extends TRestResource
{
	public function getRoutes(): array
	{
		return ['index' => ['not a route']];
	}
}

class NotAResource
{
}

// ── Test class ────────────────────────────────────────────────────────────────

/**
 * Tests for TRestService.
 */
class TRestServiceTest extends \PHPUnit\Framework\TestCase
{
	private TRestServiceExposed $service;
	private array $requestSnapshot = [];
	private array $applicationSnapshot = [];

	protected function setUp(): void
	{
		UsersFixtureResource::$log = [];
		$application = Prado::getApplication();
		$this->requestSnapshot = PradoUnit::snapshot($application->getRequest(), ['_pathInfo', '_parsedBody', '_urlFormat']);
		$this->applicationSnapshot = PradoUnit::snapshot($application, ['_authRules', '_mode', '_configType', '_configFileExt', '_requestCompleted']);
		Prado::setPathOfAlias('RestFixtures', __DIR__ . '/fixtures');
		$this->service = new TRestServiceExposed();
		$this->service->setID('rest');
		$this->service->setBasePath('api');
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$this->setPath('/api/users');
	}

	protected function tearDown(): void
	{
		$application = Prado::getApplication();
		$application->detachEventHandler('onAuthorization', [$this->service, 'handleUnauthorized']);
		PradoUnit::restore($application->getRequest(), $this->requestSnapshot);
		PradoUnit::restore($application, $this->applicationSnapshot);
		PradoUnit::restoreInitialState();
	}

	private function setPath(string $pathInfo): void
	{
		PradoUnit::setProp(Prado::getApplication()->getRequest(), '_pathInfo', $pathInfo);
	}

	private function xml(string $xml): \Prado\Xml\TXmlElement
	{
		$dom = new TXmlDocument();
		$dom->loadFromString($xml);
		return $dom;
	}

	private function usersConfig(): array
	{
		return ['resources' => [['class' => UsersFixtureResource::class]]];
	}

	private function load(array|string $config): void
	{
		$this->service->loadConfig(is_string($config) ? $this->xml($config) : $config);
	}

	private function runWith(string $verb, string $pathInfo, array|string $config): CapturingResponse
	{
		$response = new CapturingResponse();
		$this->service->setInjectedResponse($response);
		$this->load($config);
		$_SERVER['REQUEST_METHOD'] = $verb;
		$this->setPath($pathInfo);
		$this->service->resolveRoute();
		$this->service->run();
		return $response;
	}

	/**
	 * @return array<string,string> route name => verbs and pattern.
	 */
	private function routeTable(): array
	{
		$table = [];
		foreach ($this->service->getRoutes() as $route) {
			$table[$route->getName()] = implode(',', $route->getVerbs() ?? ['*']) . ' ' . $route->getPattern();
		}
		return $table;
	}

	// ── Configuration ─────────────────────────────────────────────────────────

	public function testXmlToArrayNestsAttributesAndChildren(): void
	{
		$config = $this->service->xmlToArray($this->xml(
			'<service BasePath="api">
				<resource class="A" prefix="a" Tag="t"><authorization><allow roles="admin" verb="POST" /><deny users="?" /></authorization></resource>
				<route verbs="GET" pattern="x/{id}" class="A" method="m" parameters.id="\d+" query.mode="edit" />
				<group prefix="g"><include file="F" /></group>
			</service>'
		));
		$this->assertSame('api', $config['BasePath']);
		$this->assertSame(['class' => 'A', 'prefix' => 'a', 'Tag' => 't', 'authorization' => [
			['action' => 'allow', 'roles' => 'admin', 'verb' => 'POST'],
			['action' => 'deny', 'users' => '?'],
		]], $config['resources'][0]);
		$this->assertSame(['verbs' => 'GET', 'pattern' => 'x/{id}', 'class' => 'A', 'method' => 'm', 'parameters' => ['id' => '\d+'], 'query' => ['mode' => 'edit']], $config['routes'][0]);
		$this->assertSame(['prefix' => 'g', 'include' => ['F']], $config['groups'][0]);
	}

	public function testResourceRoutesComeFromAttributes(): void
	{
		$this->load('<service><resource class="Prado\Test\Unit\Web\Services\Rest\UsersFixtureResource" /></service>');
		$this->assertSame([
			'UsersFixtureResource.index' => 'GET api/users',
			'UsersFixtureResource.show' => 'GET api/users/{id}',
			'UsersFixtureResource.store' => 'POST api/users',
			'UsersFixtureResource.destroy' => 'DELETE api/users/{id}',
		], $this->routeTable());
		$route = $this->service->getRouteByName('UsersFixtureResource.show');
		$this->assertSame(UsersFixtureResource::class, $route->getResource());
		$this->assertSame('show', $route->getMethod());
		$this->assertSame('\d+', $route->getParameters()->itemAt('id'));
		$this->assertSame('rest', $route->getServiceID());
		$this->assertInstanceOf(UsersFixtureResource::class, $this->service->getResource(UsersFixtureResource::class));
	}

	public function testResourcePrefixAndPropertiesFromConfiguration(): void
	{
		$this->load(['resources' => [['class' => UsersFixtureResource::class, 'prefix' => 'people', 'Tag' => 'cfg']]]);
		$this->assertSame('GET api/people/{id}', $this->routeTable()['UsersFixtureResource.show']);
		$this->assertSame('cfg', $this->service->getResource(UsersFixtureResource::class)->getTag());
		$this->assertSame(['Tag' => 'cfg'], $this->service->getRouteByName('UsersFixtureResource.show')->getResourceProperties());
	}

	public function testEmptyPrefixRemovesTheClassPrefix(): void
	{
		$this->load(['resources' => [['class' => UsersFixtureResource::class, 'prefix' => '']]]);
		$this->assertSame('GET api/{id}', $this->routeTable()['UsersFixtureResource.show']);
	}

	public function testExplicitRouteAndResourceRoutes(): void
	{
		$this->load('<service>
			<route verbs="GET,HEAD" pattern="health" class="Prado\Test\Unit\Web\Services\Rest\UsersFixtureResource" method="index" name="health" />
			<resource class="Prado\Test\Unit\Web\Services\Rest\TypedParamsResource" prefix="t">
				<route verbs="POST" pattern="extra" method="any" />
			</resource>
		</service>');
		$table = $this->routeTable();
		$this->assertSame('GET,HEAD api/health', $table['health']);
		$this->assertSame('POST api/t/extra', $table['TypedParamsResource.any']);
		$anyVerbs = [];
		foreach ($this->service->getRoutes() as $route) {
			if ($route->getPattern() === 'api/t/any') {
				$anyVerbs[] = $route->getVerbs();
			}
		}
		$this->assertSame([null], $anyVerbs);
		$this->assertCount(6, $this->service->getRoutes());
	}

	public function testGroupPrefixAndEnabled(): void
	{
		$this->load(['groups' => [
			['prefix' => 'v2', 'resources' => [['class' => UsersFixtureResource::class]]],
			['prefix' => 'off', 'enabled' => false, 'resources' => [['class' => TypedParamsResource::class]]],
			['prefix' => 'debug', 'enabled' => 'Debug', 'resources' => [['class' => FailingResource::class]]],
		]]);
		$table = $this->routeTable();
		$this->assertSame('GET api/v2/users/{id}', $table['UsersFixtureResource.show']);
		$this->assertArrayNotHasKey('TypedParamsResource.typed', $table);
		$this->assertSame(Prado::getApplication()->getMode() === TApplicationMode::Debug, isset($table['FailingResource.auth']));
	}

	public function testIsEnabled(): void
	{
		$this->assertTrue($this->service->isEnabled(true));
		$this->assertTrue($this->service->isEnabled('true'));
		$this->assertTrue($this->service->isEnabled('1'));
		$this->assertFalse($this->service->isEnabled('false'));
		PradoUnit::setProp(Prado::getApplication(), '_mode', TApplicationMode::Debug);
		$this->assertTrue($this->service->isEnabled('debug'));
		PradoUnit::setProp(Prado::getApplication(), '_mode', TApplicationMode::Normal);
		$this->assertFalse($this->service->isEnabled('Debug'));
	}

	public function testIncludeXml(): void
	{
		PradoUnit::setProp(Prado::getApplication(), '_mode', TApplicationMode::Debug);
		$this->load('<service><group prefix="v3"><include file="RestFixtures.rest-include" /></group></service>');
		$table = $this->routeTable();
		$this->assertSame('GET api/v3/included/{id}', $table['UsersFixtureResource.show']);
		$this->assertSame('GET api/v3/debug/dump', $table['dump']);
		$this->assertSame('from-xml', $this->service->getResource(UsersFixtureResource::class)->getTag());
	}

	public function testIncludePhp(): void
	{
		$application = Prado::getApplication();
		PradoUnit::setProp($application, '_mode', TApplicationMode::Normal);
		$application->setConfigurationType(TApplication::CONFIG_TYPE_PHP);
		PradoUnit::setProp($application, '_configFileExt', null);
		$this->load(['include' => 'RestFixtures.rest-include']);
		$table = $this->routeTable();
		$this->assertSame('GET api/included/{id}', $table['UsersFixtureResource.show']);
		$this->assertArrayNotHasKey('dump', $table);
		$this->assertSame('from-php', $this->service->getResource(UsersFixtureResource::class)->getTag());
	}

	public function testIncludeNotFoundThrows(): void
	{
		$this->expectException(TConfigurationException::class);
		$this->load(['include' => 'RestFixtures.missing']);
	}

	public function testAuthorizationRulesFollowTheHierarchy(): void
	{
		$this->load('<service>
			<group prefix="g">
				<authorization><deny users="?" /></authorization>
				<resource class="Prado\Test\Unit\Web\Services\Rest\UsersFixtureResource">
					<authorization><allow roles="admin" verb="POST" /></authorization>
				</resource>
				<route verbs="GET" pattern="open" class="Prado\Test\Unit\Web\Services\Rest\TypedParamsResource" method="any" />
			</group>
			<resource class="Prado\Test\Unit\Web\Services\Rest\FailingResource" />
		</service>');
		$rules = $this->service->getRouteByName('UsersFixtureResource.store')->getAuthorizationRules();
		$this->assertCount(2, $rules);
		$this->assertSame('deny', $rules[0]->getAction());
		$this->assertSame('allow', $rules[1]->getAction());
		$this->assertSame('post', $rules[1]->getVerb());
		$this->assertCount(1, $this->service->getRouteByName('TypedParamsResource.any')->getAuthorizationRules());
		$this->assertSame([], $this->service->getRouteByName('FailingResource.auth')->getAuthorizationRules());
	}

	public function testResourceRequiresClass(): void
	{
		$this->expectException(TConfigurationException::class);
		$this->load(['resources' => [['prefix' => 'x']]]);
	}

	public function testRouteRequiresMethod(): void
	{
		$this->expectException(TConfigurationException::class);
		$this->load(['routes' => [['class' => UsersFixtureResource::class, 'pattern' => 'x']]]);
	}

	public function testRouteRequiresPattern(): void
	{
		$this->expectException(TConfigurationException::class);
		$this->load(['routes' => [['class' => UsersFixtureResource::class, 'method' => 'index']]]);
	}

	public function testResourceMustBeAnIRestResource(): void
	{
		$this->expectException(TConfigurationException::class);
		$this->load(['resources' => [['class' => NotAResource::class]]]);
	}

	public function testRoutesMustBeTRestRoute(): void
	{
		$this->expectException(TConfigurationException::class);
		$this->load(['resources' => [['class' => BadRoutesResource::class]]]);
	}

	public function testRouteClassMustBeARoutePattern(): void
	{
		$this->service->setRouteClass(NotAResource::class);
		$this->expectException(TConfigurationException::class);
		$this->load($this->usersConfig());
	}

	public function testGetResourceAcceptsDotNotation(): void
	{
		$this->load($this->usersConfig());
		$this->assertInstanceOf(UsersFixtureResource::class, $this->service->getResource('Prado.Test.Unit.Web.Services.Rest.UsersFixtureResource'));
		$this->assertNull($this->service->getResource('Nope'));
	}

	public function testJoinPathAndDefaultRouteName(): void
	{
		$this->assertSame('a/b', $this->service->joinPath('/a/', '/b/'));
		$this->assertSame('b', $this->service->joinPath('', 'b'));
		$this->assertSame('a', $this->service->joinPath('a', ''));
		$this->assertSame('UsersFixtureResource.show', $this->service->getDefaultRouteName(UsersFixtureResource::class, 'show'));
		$this->assertSame('Plain.m', $this->service->getDefaultRouteName('Plain', 'm'));
	}

	// ── Routing ───────────────────────────────────────────────────────────────

	public function testResolveRouteMatchesPathThenVerb(): void
	{
		$this->load($this->usersConfig());
		$this->setPath('/api/users/42');
		$this->service->resolveRoute();
		$this->assertSame('UsersFixtureResource.show', $this->service->getRoute()->getName());
		$this->assertSame(['id' => '42'], $this->service->getPathParameters());
		$this->assertCount(2, $this->service->getPathMatches());
	}

	public function testResolveRouteNoPathMatch(): void
	{
		$this->load($this->usersConfig());
		$this->setPath('/api/nope');
		$this->service->resolveRoute();
		$this->assertNull($this->service->getRoute());
		$this->assertSame([], $this->service->getPathMatches());
		$this->assertSame([], $this->service->getAllowedVerbs());
	}

	public function testResolveRouteVerbMismatchListsAllowedVerbs(): void
	{
		$this->load($this->usersConfig());
		$_SERVER['REQUEST_METHOD'] = 'PATCH';
		$this->setPath('/api/users/42');
		$this->service->resolveRoute();
		$this->assertNull($this->service->getRoute());
		$this->assertSame(['GET', 'HEAD', 'DELETE'], $this->service->getAllowedVerbs());
	}

	public function testResolveRouteHeadUsesGet(): void
	{
		$this->load($this->usersConfig());
		$_SERVER['REQUEST_METHOD'] = 'HEAD';
		$this->setPath('/api/users');
		$this->service->resolveRoute();
		$this->assertSame('UsersFixtureResource.index', $this->service->getRoute()->getName());
	}

	public function testAllowedVerbsForARouteWithoutVerbs(): void
	{
		$this->load(['resources' => [['class' => TypedParamsResource::class]]]);
		$_SERVER['REQUEST_METHOD'] = 'PURGE';
		$this->setPath('/api/any');
		$this->service->resolveRoute();
		$this->assertSame('TypedParamsResource.any', $this->service->getRoute()->getName());
		$this->assertSame(TRestService::STANDARD_VERBS, $this->service->getAllowedVerbs());
	}

	public function testInitMergesTheMatchedRouteRules(): void
	{
		$application = Prado::getApplication();
		$before = $application->getAuthorizationRules()->getCount();
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->setPath('/api/users');
		$this->service->init($this->xml('<service>
			<resource class="Prado\Test\Unit\Web\Services\Rest\UsersFixtureResource">
				<authorization><deny users="?" verb="POST" /></authorization>
			</resource>
		</service>'));
		$this->assertSame('UsersFixtureResource.store', $this->service->getRoute()->getName());
		$this->assertSame($before + 1, $application->getAuthorizationRules()->getCount());
		$this->assertSame($application->getMode() === TApplicationMode::Debug, $this->service->getExposeErrors());
	}

	public function testConstructUrl(): void
	{
		$this->load($this->usersConfig());
		$request = Prado::getApplication()->getRequest();
		$base = (string) $request->getApplicationUrl();
		$this->assertSame($base . '/api/users/42', $this->service->constructUrl('UsersFixtureResource.show', ['id' => 42]));
		$this->assertSame($base . '/api/users?page=2', $this->service->constructUrl('UsersFixtureResource.index', [], ['page' => 2]));
		$request->setUrlFormat(THttpRequestUrlFormat::HiddenPath);
		$this->assertSame(rtrim(str_replace('\\', '/', dirname($base)), '/') . '/api/users/42', $this->service->constructUrl('UsersFixtureResource.show', ['id' => 42]));
	}

	public function testConstructUrlUnknownRouteThrows(): void
	{
		$this->expectException(TInvalidDataValueException::class);
		$this->service->constructUrl('nope');
	}

	// ── Dispatch ──────────────────────────────────────────────────────────────

	public function testDispatchCastsScalarsAndUsesDefaults(): void
	{
		$response = $this->runWith('GET', '/api/typed/5/2.5/true', ['resources' => [['class' => TypedParamsResource::class]]]);
		$this->assertSame(200, $response->status);
		$this->assertSame(['n' => 5, 'f' => 2.5, 'b' => true, 'rest' => 'default'], json_decode($response->body, true));
	}

	public function testDispatchRejectsAValueThatDoesNotFitTheType(): void
	{
		$response = $this->runWith('GET', '/api/typed/x/2.5/true', ['resources' => [['class' => TypedParamsResource::class]]]);
		$this->assertSame(404, $response->status);
	}

	public function testDispatchInjectsRequestAndRoute(): void
	{
		$response = $this->runWith('GET', '/api/inject', ['resources' => [['class' => TypedParamsResource::class]]]);
		$this->assertSame(['request' => Prado::getApplication()->getRequest()::class, 'route' => 'TypedParamsResource.inject'], json_decode($response->body, true));
	}

	public function testDispatchUnboundParameterIs500(): void
	{
		$this->service->setExposeErrors(true);
		$response = $this->runWith('GET', '/api/unbound', ['resources' => [['class' => TypedParamsResource::class]]]);
		$this->assertSame(500, $response->status);
		$this->assertStringContainsString('nope', json_decode($response->body, true)['detail']);
	}

	public function testDispatchMissingMethodIs405(): void
	{
		$this->load($this->usersConfig());
		$route = $this->service->getRouteByName('UsersFixtureResource.show');
		$route->setMethod('missing');
		$this->expectException(TRestException::class);
		$this->expectExceptionCode(405);
		$this->service->dispatch($this->service->getResource(UsersFixtureResource::class), $route, ['id' => '1']);
	}

	// ── run() ─────────────────────────────────────────────────────────────────

	public function testRunGetWritesJson(): void
	{
		$response = $this->runWith('GET', '/api/users', $this->usersConfig());
		$this->assertSame(200, $response->status);
		$this->assertSame(TMediaType::JSON, $response->contentType);
		$this->assertSame(['list' => true, 'tag' => ''], json_decode($response->body, true));
		$this->assertSame(['index'], UsersFixtureResource::$log);
	}

	public function testRunShowCastsTheId(): void
	{
		$response = $this->runWith('GET', '/api/users/42', $this->usersConfig());
		$this->assertSame(['id' => 42], json_decode($response->body, true));
		$this->assertSame(['show:42'], UsersFixtureResource::$log);
	}

	public function testRunPostCreated(): void
	{
		$response = $this->runWith('POST', '/api/users', $this->usersConfig());
		$this->assertSame(201, $response->status);
		$this->assertSame(['created' => true], json_decode($response->body, true));
	}

	public function testRunDeleteNoContent(): void
	{
		$response = $this->runWith('DELETE', '/api/users/9', $this->usersConfig());
		$this->assertSame(204, $response->status);
		$this->assertSame('', $response->body);
		$this->assertSame('', $response->contentType);
	}

	public function testRunHeadSendsNoBody(): void
	{
		$response = $this->runWith('HEAD', '/api/users', $this->usersConfig());
		$this->assertSame(200, $response->status);
		$this->assertSame('', $response->body);
		$this->assertSame(['index'], UsersFixtureResource::$log);
	}

	public function testRunNotFoundIsAProblemDocument(): void
	{
		$response = $this->runWith('GET', '/api/nope', $this->usersConfig());
		$this->assertSame(404, $response->status);
		$this->assertSame(TMediaType::PROBLEM_JSON, $response->contentType);
		$this->assertSame(['status' => 404, 'title' => 'Not Found'], json_decode($response->body, true));
	}

	public function testRunMethodNotAllowedCarriesAllow(): void
	{
		$response = $this->runWith('PATCH', '/api/users/3', $this->usersConfig());
		$this->assertSame(405, $response->status);
		$this->assertSame('Allow: GET, HEAD, DELETE', $response->headerLine('Allow'));
		$this->assertSame('Method Not Allowed', json_decode($response->body, true)['title']);
	}

	public function testRunResourceExceptionIsWritten(): void
	{
		$response = $this->runWith('GET', '/api/fail/notfound', ['resources' => [['class' => FailingResource::class]]]);
		$this->assertSame(404, $response->status);
		$this->assertSame('gone', json_decode($response->body, true)['detail']);
	}

	public function testRunHttpExceptionKeepsItsStatus(): void
	{
		$response = $this->runWith('GET', '/api/fail/http', ['resources' => [['class' => FailingResource::class]]]);
		$this->assertSame(410, $response->status);
		$this->assertSame('Gone for good', json_decode($response->body, true)['detail']);
	}

	public function testRunOtherExceptionIs500(): void
	{
		$config = ['resources' => [['class' => FailingResource::class]]];
		$this->service->setExposeErrors(true);
		$response = $this->runWith('GET', '/api/fail/boom', $config);
		$this->assertSame(500, $response->status);
		$this->assertSame('boom', json_decode($response->body, true)['detail']);

		$this->service = new TRestServiceExposed();
		$this->service->setID('rest');
		$this->service->setBasePath('api');
		$this->service->setExposeErrors(false);
		$response = $this->runWith('GET', '/api/fail/boom', $config);
		$this->assertSame(500, $response->status);
		$this->assertArrayNotHasKey('detail', json_decode($response->body, true));
	}

	public function testRunAuthorizeRefuses(): void
	{
		$response = $this->runWith('GET', '/api/fail/auth', ['resources' => [['class' => FailingResource::class]]]);
		$this->assertSame(401, $response->status);
		$this->assertSame('Sign in.', json_decode($response->body, true)['detail']);
	}

	public function testRunRaisesEvents(): void
	{
		$seen = [];
		$this->service->onPreRunResource[] = function ($sender, $resource) use (&$seen) {
			$seen[] = $resource instanceof IRestResource ? $resource->getRoute()->getName() : 'x';
		};
		$this->service->onError[] = function ($sender, $exception) use (&$seen) {
			$seen[] = $exception::class;
		};
		$this->runWith('GET', '/api/fail/boom', ['resources' => [['class' => FailingResource::class]]]);
		$this->assertSame(['FailingResource.boomHandler', \RuntimeException::class], $seen);
	}

	public function testHandleUnauthorizedWritesTheProblemDocument(): void
	{
		$application = Prado::getApplication();
		$response = new CapturingResponse();
		$this->service->setInjectedResponse($response);
		$this->service->handleUnauthorized($application, null);
		$this->assertSame('', $response->body);
		PradoUnit::setProp($application, '_requestCompleted', true);
		$response->status = 401;
		$this->service->handleUnauthorized($application, null);
		$this->assertSame(401, $response->status);
		$this->assertSame('Unauthorized', json_decode($response->body, true)['title']);
	}

	// ── Properties ────────────────────────────────────────────────────────────

	public function testProperties(): void
	{
		$service = new TRestService();
		$this->assertSame('', $service->getBasePath());
		$service->setBasePath('/api/v1/');
		$this->assertSame('api/v1', $service->getBasePath());
		$this->assertFalse($service->getExposeErrors());
		$service->setExposeErrors('true');
		$this->assertTrue($service->getExposeErrors());
		$this->assertSame(TRestRoutePattern::class, $service->getRouteClass());
		$this->assertSame([], $service->getRoutes());
		$this->assertSame([], $service->getResources());
		$this->assertNull($service->getRoute());
	}
}
