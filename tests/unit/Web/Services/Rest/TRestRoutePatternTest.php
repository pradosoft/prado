<?php

namespace Prado\Test\Unit\Web\Services\Rest;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Prado;
use Prado\Security\TAuthorizationRule;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\Services\Rest\TRestRoutePattern;
use Prado\Web\THttpRequest;
use Prado\Web\TUrlManager;

/**
 * Tests for TRestRoutePattern.
 */
class TRestRoutePatternTest extends \PHPUnit\Framework\TestCase
{
	private array $requestSnapshot = [];

	protected function setUp(): void
	{
		$this->requestSnapshot = PradoUnit::snapshot($this->getRequest(), ['_pathInfo']);
	}

	protected function tearDown(): void
	{
		PradoUnit::restore($this->getRequest(), $this->requestSnapshot);
		PradoUnit::restoreInitialState();
	}

	private function getRequest(): THttpRequest
	{
		return Prado::getApplication()->getRequest();
	}

	private function requestFor(string $pathInfo): THttpRequest
	{
		PradoUnit::setProp($this->getRequest(), '_pathInfo', $pathInfo);
		return $this->getRequest();
	}

	private function route(string $pattern, array $parameters = [], ?string $verbs = null): TRestRoutePattern
	{
		$route = new TRestRoutePattern();
		$route->setPattern($pattern);
		$route->setVerbs($verbs);
		foreach ($parameters as $key => $value) {
			$route->getParameters()->add($key, $value);
		}
		$route->init([]);
		return $route;
	}

	// ── Compilation ────────────────────────────────────────────────────────────

	public function testPlaceholderWithoutConstraintMatchesOneSegment(): void
	{
		$route = $this->route('users/{id}');
		$this->assertSame(['id'], $route->getParameterNames());
		$this->assertSame(TRestRoutePattern::DEFAULT_CONSTRAINT, $route->getParameters()->itemAt('id'));
		$this->assertSame(['id' => '42'], $route->matchPath($this->requestFor('/users/42')));
		$this->assertNull($route->matchPath($this->requestFor('/users/42/posts')));
	}

	public function testInlineConstraintMovesToParameters(): void
	{
		$route = $this->route('users/{id:\d+}/posts/{slug:[a-z-]+}');
		$this->assertSame('users/{id}/posts/{slug}', $route->getPattern());
		$this->assertSame(['id', 'slug'], $route->getParameterNames());
		$this->assertSame('\d+', $route->getParameters()->itemAt('id'));
		$this->assertSame('[a-z-]+', $route->getParameters()->itemAt('slug'));
		$this->assertSame(['id' => '7', 'slug' => 'hello-world'], $route->matchPath($this->requestFor('/users/7/posts/hello-world')));
		$this->assertNull($route->matchPath($this->requestFor('/users/abc/posts/hello')));
	}

	public function testParametersConstraintIsKept(): void
	{
		$route = $this->route('users/{id}', ['id' => '\d{2}']);
		$this->assertSame('\d{2}', $route->getParameters()->itemAt('id'));
		$this->assertSame(['id' => '42'], $route->matchPath($this->requestFor('/users/42')));
		$this->assertNull($route->matchPath($this->requestFor('/users/4')));
	}

	public function testAlternationStaysInsideThePlaceholder(): void
	{
		$route = $this->route('items/{id:\d+|new}');
		$this->assertSame(['id' => 'new'], $route->matchPath($this->requestFor('/items/new')));
		$this->assertSame(['id' => '5'], $route->matchPath($this->requestFor('/items/5')));
		$this->assertNull($route->matchPath($this->requestFor('/new')));
	}

	public function testDuplicatePlaceholderThrows(): void
	{
		$this->expectException(TConfigurationException::class);
		$this->expectExceptionMessageMatches('/restroute_parameter_duplicate|repeated/');
		$this->route('a/{id}/b/{id}');
	}

	public function testMalformedPlaceholderThrows(): void
	{
		$this->expectException(TConfigurationException::class);
		$this->expectExceptionMessageMatches('/restroute_pattern_invalid|malformed/');
		$this->route('a/{bad-name}');
	}

	public function testServiceParameterDefaultsToName(): void
	{
		$route = new TRestRoutePattern();
		$route->setPattern('users');
		$route->setName('users.index');
		$route->init([]);
		$this->assertSame('users.index', $route->getServiceParameter());
		$this->assertSame('users.index', $route->getName());
	}

	public function testNameDefaultsToPattern(): void
	{
		$this->assertSame('users/{id}', $this->route('users/{id:\d+}')->getName());
	}

	public function testConstructorAcceptsUrlManager(): void
	{
		$manager = new TUrlManager();
		$route = new TRestRoutePattern($manager);
		$this->assertSame($manager, $route->getManager());
		$this->assertNull((new TRestRoutePattern())->getManager());
	}

	// ── Matching ──────────────────────────────────────────────────────────────

	public function testMatchPathIgnoresTrailingSlashAndVerb(): void
	{
		$route = $this->route('users', [], 'POST');
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$this->assertSame([], $route->matchPath($this->requestFor('/users/')));
		$this->assertSame([], $route->matchPath($this->requestFor('users')));
		$this->assertNull($route->matchPath($this->requestFor('/user')));
	}

	public function testMatchPathRootPattern(): void
	{
		$route = $this->route('');
		$this->assertSame([], $route->matchPath($this->requestFor('/')));
		$this->assertSame([], $route->matchPath($this->requestFor('')));
		$this->assertNull($route->matchPath($this->requestFor('/x')));
	}

	public function testMatchPathHonorsQueryConstraints(): void
	{
		$route = new TRestRoutePattern();
		$route->setPattern('posts');
		$route->getQuery()->add('mode', 'edit|preview');
		$route->init([]);
		$_GET = ['mode' => 'edit'];
		$this->assertSame([], $route->matchPath($this->requestFor('/posts')));
		$_GET = ['mode' => 'view'];
		$this->assertNull($route->matchPath($this->requestFor('/posts')));
		$_GET = [];
		$this->assertNull($route->matchPath($this->requestFor('/posts')));
	}

	public function testMatchPathCaseInsensitive(): void
	{
		$route = new TRestRoutePattern();
		$route->setPattern('Users/{id}');
		$route->setCaseSensitive(false);
		$route->init([]);
		$this->assertSame(['id' => '1'], $route->matchPath($this->requestFor('/users/1')));
		$this->assertNull($this->route('Users/{id}')->matchPath($this->requestFor('/users/1')));
	}

	public function testMatchPathIncludesConstants(): void
	{
		$route = new TRestRoutePattern();
		$route->setPattern('list/{page}');
		$route->getConstants()->add('kind', 'summary');
		$route->init([]);
		$this->assertSame(['page' => '2', 'kind' => 'summary'], $route->matchPath($this->requestFor('/list/2')));
	}

	public function testAcceptsVerb(): void
	{
		$this->assertTrue($this->route('a')->acceptsVerb('DELETE'));
		$route = $this->route('a', [], 'get, post');
		$this->assertSame(['GET', 'POST'], $route->getVerbs());
		$this->assertTrue($route->acceptsVerb('GET'));
		$this->assertTrue($route->acceptsVerb('post'));
		$this->assertFalse($route->acceptsVerb('DELETE'));
		$negated = $this->route('a', [], '!DELETE');
		$this->assertTrue($negated->acceptsVerb('GET'));
		$this->assertFalse($negated->acceptsVerb('DELETE'));
	}

	// ── Reverse routing ───────────────────────────────────────────────────────

	public function testConstructPath(): void
	{
		$route = $this->route('api/users/{id}/posts/{slug}');
		$this->assertSame('api/users/42/posts/a%20b', $route->constructPath(['id' => 42, 'slug' => 'a b']));
		$this->assertSame('api/users/1/posts/x?page=2&q=a%20b', $route->constructPath(['id' => 1, 'slug' => 'x'], ['page' => 2, 'q' => 'a b']));
		$this->assertSame('api/users/1/posts/x?page=2&amp;q=1', $route->constructPath(['id' => 1, 'slug' => 'x'], ['page' => 2, 'q' => 1], true));
	}

	public function testConstructPathWithoutPlaceholders(): void
	{
		$this->assertSame('api/users', $this->route('/api/users/')->constructPath());
	}

	public function testConstructPathMissingParameterThrows(): void
	{
		$this->expectException(TInvalidDataValueException::class);
		$this->route('users/{id}')->constructPath([]);
	}

	public function testConstructUrlSplitsPlaceholdersFromQuery(): void
	{
		$route = $this->route('users/{id}');
		$this->assertSame('users/3?sort=name', $route->constructUrl(['id' => 3, 'sort' => 'name'], false, true));
	}

	// ── Properties ────────────────────────────────────────────────────────────

	public function testHandlerProperties(): void
	{
		$route = new TRestRoutePattern();
		$this->assertNull($route->getResource());
		$this->assertNull($route->getMethod());
		$this->assertSame([], $route->getResourceProperties());
		$route->setResource(UsersFixtureResource::class);
		$route->setMethod('show');
		$route->setResourceProperties(['Tag' => 'x']);
		$this->assertSame(UsersFixtureResource::class, $route->getResource());
		$this->assertSame('show', $route->getMethod());
		$this->assertSame(['Tag' => 'x'], $route->getResourceProperties());
	}

	public function testAuthorizationRules(): void
	{
		$route = new TRestRoutePattern();
		$this->assertSame([], $route->getAuthorizationRules());
		$rule = new TAuthorizationRule('deny', '?', '', 'POST');
		$route->addAuthorizationRule($rule);
		$this->assertSame([$rule], $route->getAuthorizationRules());
	}

	public function testSetPatternAcceptsArrayForm(): void
	{
		$route = new TRestRoutePattern();
		$route->setPattern(['Pattern' => 'a/b']);
		$this->assertSame('a/b', $route->getPattern());
	}
}
