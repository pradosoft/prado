<?php

namespace Prado\Test\Unit\Web\Services\Rest;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Prado;
use Prado\Test\Unit\PradoUnit;
use Prado\Util\TInputValidator;
use Prado\Web\Services\Rest\TRestException;
use Prado\Web\Services\Rest\TRestResource;
use Prado\Web\Services\Rest\TRestRoute;
use Prado\Web\Services\Rest\TRestRoutePattern;

// ── Fixtures ──────────────────────────────────────────────────────────────────

/**
 * Overrides show() without the attribute: the parent's route must survive.
 */
class ExtendedUsersResource extends UsersFixtureResource
{
	public function show(int $id): array
	{
		return ['id' => $id, 'extended' => true];
	}

	#[TRestRoute('GET', 'export/{format:csv|json}')]
	#[TRestRoute('GET', 'download/{format:csv|json}', name: 'users.download')]
	public function export(string $format): array
	{
		return ['format' => $format];
	}
}

/**
 * Replaces the class prefix and declares routes as a table.
 */
#[TRestRoute(pattern: 'members')]
class TableUsersResource extends UsersFixtureResource
{
	public function getRoutes(): array
	{
		return parent::getRoutes() + [
			'search' => [new TRestRoute('GET', 'search')],
		];
	}

	public function search(): array
	{
		return [];
	}
}

/**
 * Exposes the protected helpers of TRestResource.
 */
class HelperResource extends TRestResource
{
	public function __call($name, $args)
	{
		if (method_exists($this, $name)) {
			return $this->$name(...$args);
		}
		return parent::__call($name, $args);
	}
}

// ── Test class ────────────────────────────────────────────────────────────────

/**
 * Tests for TRestResource.
 */
class TRestResourceTest extends \PHPUnit\Framework\TestCase
{
	private array $requestSnapshot = [];

	protected function setUp(): void
	{
		$request = Prado::getApplication()->getRequest();
		$this->requestSnapshot = PradoUnit::snapshot($request, ['_parsedBody', '_rawBody']);
	}

	protected function tearDown(): void
	{
		PradoUnit::restore(Prado::getApplication()->getRequest(), $this->requestSnapshot);
		PradoUnit::restoreInitialState();
	}

	private function setBody(array $body): void
	{
		PradoUnit::setProp(Prado::getApplication()->getRequest(), '_parsedBody', $body);
	}

	private function route(string $pattern = 'users/{id}'): TRestRoutePattern
	{
		$route = new TRestRoutePattern();
		$route->setPattern($pattern);
		$route->init([]);
		return $route;
	}

	// ── Routes ────────────────────────────────────────────────────────────────

	public function testGetRoutesReadsAttributes(): void
	{
		$routes = (new UsersFixtureResource())->getRoutes();
		$this->assertSame(['index', 'show', 'store', 'destroy'], array_keys($routes));
		$this->assertInstanceOf(TRestRoute::class, $routes['show'][0]);
		$this->assertSame('GET', $routes['show'][0]->verbs);
		$this->assertSame('{id:\d+}', $routes['show'][0]->pattern);
		$this->assertSame('', $routes['index'][0]->pattern);
		$this->assertSame('DELETE', $routes['destroy'][0]->verbs);
	}

	public function testGetRoutePrefixReadsClassAttribute(): void
	{
		$this->assertSame('users', (new UsersFixtureResource())->getRoutePrefix());
		$this->assertSame('', (new HelperResource())->getRoutePrefix());
	}

	public function testOverrideWithoutAttributeKeepsParentRoute(): void
	{
		$resource = new ExtendedUsersResource();
		$routes = $resource->getRoutes();
		$this->assertArrayHasKey('show', $routes);
		$this->assertSame('{id:\d+}', $routes['show'][0]->pattern);
		$this->assertSame('users', $resource->getRoutePrefix());
	}

	public function testRepeatedAttributeGivesOneRouteEach(): void
	{
		$routes = (new ExtendedUsersResource())->getRoutes();
		$this->assertCount(2, $routes['export']);
		$this->assertSame('export/{format:csv|json}', $routes['export'][0]->pattern);
		$this->assertSame('users.download', $routes['export'][1]->name);
	}

	public function testSubclassAttributeReplacesPrefixAndTableExtendsRoutes(): void
	{
		$resource = new TableUsersResource();
		$this->assertSame('members', $resource->getRoutePrefix());
		$routes = $resource->getRoutes();
		$this->assertArrayHasKey('index', $routes);
		$this->assertSame('search', $routes['search'][0]->pattern);
	}

	public function testSetRouteStoresRouteAndParameters(): void
	{
		$resource = new UsersFixtureResource();
		$this->assertNull($resource->getRoute());
		$this->assertSame([], $resource->getPathParameters());
		$this->assertSame('none', $resource->getPathParameter('id', 'none'));
		$route = $this->route();
		$resource->setRoute($route, ['id' => '42']);
		$this->assertSame($route, $resource->getRoute());
		$this->assertSame(['id' => '42'], $resource->getPathParameters());
		$this->assertSame('42', $resource->getPathParameter('id'));
	}

	public function testAuthorizeAcceptsByDefault(): void
	{
		$resource = new UsersFixtureResource();
		$resource->authorize($this->route());
		$this->assertTrue(true);
	}

	public function testGetServiceIsNullOutsideTheService(): void
	{
		$this->assertNull((new UsersFixtureResource())->getService());
	}

	// ── Response state ────────────────────────────────────────────────────────

	public function testStatusHelpers(): void
	{
		$resource = new HelperResource();
		$this->assertSame(200, $resource->getStatusCode());
		$this->assertSame(['a' => 1], $resource->created(['a' => 1]));
		$this->assertSame(201, $resource->getStatusCode());
		$this->assertSame('queued', $resource->accepted('queued'));
		$this->assertSame(202, $resource->getStatusCode());
		$resource->noContent();
		$this->assertSame(204, $resource->getStatusCode());
	}

	public function testHeaderAddsAndReplaces(): void
	{
		$resource = new HelperResource();
		$this->assertSame($resource, $resource->header('X-Total-Count', '5'));
		$resource->header('X-Total-Count', '6')->header('Location', '/x');
		$this->assertSame(['X-Total-Count' => '6', 'Location' => '/x'], $resource->getResponseHeaders());
	}

	public function testHeaderRejectsInvalidNameOrValue(): void
	{
		$resource = new HelperResource();
		try {
			$resource->header('Bad Name', 'v');
			$this->fail('invalid name accepted');
		} catch (TInvalidDataValueException $e) {
			$this->assertStringContainsString('Bad Name', $e->getMessage());
		}
		$this->expectException(TInvalidDataValueException::class);
		$resource->header('X-Ok', "a\r\nInjected: yes");
	}

	// ── Exception helpers ─────────────────────────────────────────────────────

	/**
	 * @return array<string,array{0:string,1:array,2:int}>
	 */
	public static function exceptionHelpers(): array
	{
		return [
			'notFound' => ['notFound', ['gone'], 404],
			'unauthorized' => ['unauthorized', ['login'], 401],
			'forbidden' => ['forbidden', ['no'], 403],
			'conflict' => ['conflict', ['dup'], 409],
			'abort' => ['abort', [418, 'teapot'], 418],
		];
	}

	/**
	 * @dataProvider exceptionHelpers
	 */
	public function testExceptionHelpers(string $helper, array $args, int $status): void
	{
		try {
			(new HelperResource())->$helper(...$args);
			$this->fail('no exception');
		} catch (TRestException $e) {
			$this->assertSame($status, $e->getStatusCode());
			$this->assertSame(end($args), $e->getDetail());
		}
	}

	public function testUnprocessableCarriesErrors(): void
	{
		try {
			(new HelperResource())->unprocessable(['name' => ['bad']], 'invalid');
			$this->fail('no exception');
		} catch (TRestException $e) {
			$this->assertSame(422, $e->getStatusCode());
			$this->assertSame(['name' => ['bad']], $e->getErrors());
			$this->assertSame('invalid', $e->getDetail());
		}
	}

	// ── Request helpers ───────────────────────────────────────────────────────

	public function testGetBodyReadsTheParsedRequestBody(): void
	{
		$this->setBody(['a' => 1]);
		$this->assertSame(['a' => 1], (new HelperResource())->getBody());
	}

	public function testInputPrefersBodyThenQuery(): void
	{
		$this->setBody(['a' => 'body']);
		$_GET = ['a' => 'query', 'b' => 'query-b'];
		$resource = new HelperResource();
		$this->assertSame('body', $resource->input('a'));
		$this->assertSame('query-b', $resource->input('b'));
		$this->assertSame('d', $resource->input('c', 'd'));
	}

	public function testQueryReadsTheQueryStringOnly(): void
	{
		$this->setBody(['a' => 'body']);
		$_GET = ['b' => 'q'];
		$resource = new HelperResource();
		$this->assertSame('q', $resource->query('b'));
		$this->assertNull($resource->query('a'));
		$this->assertSame('d', $resource->query('c', 'd'));
	}

	public function testHasInputOnlyExcept(): void
	{
		$this->setBody(['a' => 1, 'b' => 2, 'c' => 3]);
		$_GET = ['q' => 1];
		$resource = new HelperResource();
		$this->assertTrue($resource->hasInput('a'));
		$this->assertTrue($resource->hasInput('q'));
		$this->assertFalse($resource->hasInput('z'));
		$this->assertSame(['a' => 1, 'c' => 3], $resource->only(['a', 'c', 'z']));
		$this->assertSame(['b' => 2], $resource->except(['a', 'c']));
	}

	// ── Validation ────────────────────────────────────────────────────────────

	public function testValidateReturnsCoercedDeclaredFields(): void
	{
		$data = (new HelperResource())->validate(
			['name' => 'Ann', 'age' => '30', 'extra' => 'x'],
			['name' => 'required|string', 'age' => 'required|integer|min:0'],
		);
		$this->assertSame(['name' => 'Ann', 'age' => 30], $data);
	}

	public function testValidateBodyThrows422WithFieldErrors(): void
	{
		$this->setBody(['email' => 'nope']);
		try {
			(new HelperResource())->validateBody(['name' => 'required|string', 'email' => 'required|email']);
			$this->fail('no exception');
		} catch (TRestException $e) {
			$this->assertSame(422, $e->getStatusCode());
			$this->assertSame(['name', 'email'], array_keys($e->getErrors()));
			$this->assertSame('The given data was invalid.', $e->getDetail());
			$this->assertSame(422, $e->toArray()['status']);
			$this->assertArrayHasKey('errors', $e->toArray());
		}
	}

	public function testValidatorIsCreatedOnceAndReplaceable(): void
	{
		$resource = new HelperResource();
		$validator = $resource->getValidator();
		$this->assertInstanceOf(TInputValidator::class, $validator);
		$this->assertSame($validator, $resource->getValidator());
		$custom = new TInputValidator();
		$custom->addRule('even', fn (mixed &$value, ?string $parameter, string $field): ?string => ((int) $value % 2 === 0) ? null : "The {$field} must be even.");
		$resource->setValidator($custom);
		$this->assertSame(['n' => '4'], $resource->validate(['n' => '4'], ['n' => 'required|even']));
		$this->expectException(TRestException::class);
		$resource->validate(['n' => '3'], ['n' => 'required|even']);
	}
}
