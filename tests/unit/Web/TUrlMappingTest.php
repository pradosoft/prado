<?php

namespace Prado\Test\Unit\Web;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TPhpErrorException;
use Prado\Prado;
use Prado\TApplication;
use Prado\Web\THttpRequest;
use Prado\Web\THttpRequestUrlFormat;
use Prado\Web\TUrlMapping;
use Prado\Web\TUrlMappingPattern;
use Prado\Web\TUrlManager;
use Prado\Xml\TXmlDocument;
use Prado\Test\Unit\Harness\TTestApplication;
use Prado\Test\Unit\Harness\Traits\PradoUnitModuleDependencyTrait;
use Prado\Test\Unit\Harness\Traits\TNestedPathTrait;
use Prado\Test\Unit\PradoUnit;

/**
 * A mapping pattern carrying a nested 'Cfg' property, to observe the order the
 * module applies a pattern's configured attributes in.
 */
class TNestedPathUrlMappingPattern extends TUrlMappingPattern
{
	use TNestedPathTrait;
}

/**
 * A mapping pattern that returns the matches it is given, to drive TUrlMapping::parseUrl()
 * with a match list of one element.
 */
class TFixedMatchUrlMappingPattern extends TUrlMappingPattern
{
	public array $matches = [];

	public function getPatternMatches($request)
	{
		return $this->matches;
	}
}

/**
 * Test class for TUrlMapping.
 */
class TUrlMappingTest extends \PHPUnit\Framework\TestCase
{
	use PradoUnitModuleDependencyTrait;

	protected ?TTestApplication $app = null;

	protected function setUp(): void
	{
		$_SERVER['HTTP_HOST'] = 'localhost';
		$_SERVER['SERVER_NAME'] = 'localhost';
		$_SERVER['SERVER_PORT'] = '80';
		$_SERVER['REQUEST_URI'] = '/index.php?page=Home';
		$_SERVER['SCRIPT_NAME'] = '/index.php';
		$_SERVER['PHP_SELF'] = '/index.php';
		$_SERVER['QUERY_STRING'] = 'page=Home';
		$_SERVER['SCRIPT_FILENAME'] = __FILE__;
		$_SERVER['PATH_INFO'] = '';
		$_SERVER['REQUEST_METHOD'] = 'GET';

		$this->app = new TTestApplication(__DIR__ . '/app');
	}

	protected function tearDown(): void
	{
		parent::tearDown();
		if ($this->app !== null) {
			$this->app->restoreApplication();
			$this->app = null;
		}
		$_GET = [];
		$_POST = [];
		$_SERVER['PATH_INFO'] = '';
		$_SERVER['QUERY_STRING'] = '';
		$_SERVER['REQUEST_METHOD'] = 'GET';
	}

	/**
	 * A pattern's configured attributes apply parent path first, so a nested
	 * value survives whatever order the `<url>` element declares them in. The
	 * XML branch supplies a TAttributeCollection rather than an array.
	 */
	public function testInitAppliesParentPathBeforeNestedPath()
	{
		foreach ([
			'Cfg.Size="child" Cfg="value"',
			'Cfg="value" Cfg.Size="child"',
		] as $attributes) {
			$confstr = '<config><url class="' . TNestedPathUrlMappingPattern::class . '" ServiceParameter="Posts.ViewPost" pattern="post/{id}/" parameters.id="\d+" ' . $attributes . '/></config>';
			$config = new TXmlDocument('1.0', 'utf8');
			$config->loadFromString($confstr);

			$module = new TUrlMapping();
			$module->init($config);

			$patterns = PradoUnit::getProp($module, '_patterns');
			$this->assertEquals('child', $patterns[0]->getCfg()->getSize(), "attributes: {$attributes}");
		}
	}

	public function testParseUrlWithMatchingPattern()
	{
		$confstr = '<config><url ServiceParameter="Posts.ViewPost" pattern="post/{id}/" parameters.id="\d+"/></config>';
		$config = new TXmlDocument('1.0', 'utf8');
		$config->loadFromString($confstr);
		
		$module = new TUrlMapping();
		$module->init($config);
		
		$request = new THttpRequest();
		$_SERVER['PATH_INFO'] = '/post/123/';
		$request->setUrlFormat(THttpRequestUrlFormat::Path);
		$request->init(null);
		
		$result = $module->parseUrl();
		$this->assertEquals(['id' => '123', 'page' => 'Posts.ViewPost'], $result);
	}

	public function testParseUrlNoMatchingPatternFallsBack()
	{
		$confstr = '<config><url ServiceParameter="Posts.ViewPost" pattern="post/{id}/" parameters.id="\d+"/></config>';
		$config = new TXmlDocument('1.0', 'utf8');
		$config->loadFromString($confstr);
		
		$module = new TUrlMapping();
		$module->init($config);
		
		$request = new THttpRequest();
		$_SERVER['PATH_INFO'] = '/other/';
		$request->setUrlFormat(THttpRequestUrlFormat::Path);
		$request->init(null);
		
		$result = $module->parseUrl();
		$this->assertEquals(['other' => ''], $result);
	}

	public function testConstructUrlEnabled()
	{
		$confstr = '<config><url ServiceParameter="Posts.ViewPost" pattern="post/{id}/" parameters.id="\d+"/></config>';
		$config = new TXmlDocument('1.0', 'utf8');
		$config->loadFromString($confstr);
		
		$module = new TUrlMapping();
		$module->setEnableCustomUrl(true);
		$module->init($config);
		
		$request = new THttpRequest();
		$request->setUrlFormat(THttpRequestUrlFormat::Path);
		$request->init(null);
		
		$url = $module->constructUrl('page', 'Posts.ViewPost', ['id' => '123'], true, true);
		$this->assertEquals('/index.php/post/123/', $url);
	}

	public function testParseUrlVerbWithParameters()
	{
		$confstr = '<config><url ServiceParameter="Posts.ViewPost" pattern="post/{id}" parameters.id="\d+" verbs="GET,POST"/></config>';
		$config = new TXmlDocument('1.0', 'utf8');
		$config->loadFromString($confstr);
		
		$module = new TUrlMapping();
		$module->init($config);
		
		$request = new THttpRequest();
		$_SERVER['PATH_INFO'] = '/post/123';
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$request->setUrlFormat(THttpRequestUrlFormat::Path);
		$request->init(null);
		
		$result = $module->parseUrl();
		$this->assertEquals(['id' => '123', 'page' => 'Posts.ViewPost'], $result);
	}

	public function testGetMatchingPattern()
	{
		$confstr = '<config><url ServiceParameter="Posts.ViewPost" pattern="post/{id}/" parameters.id="\d+"/></config>';
		$config = new TXmlDocument('1.0', 'utf8');
		$config->loadFromString($confstr);
		
		$module = new TUrlMapping();
		$module->init($config);
		
		$request = new THttpRequest();
		$_SERVER['PATH_INFO'] = '/post/123/';
		$request->setUrlFormat(THttpRequestUrlFormat::Path);
		$request->init(null);
		
		$module->parseUrl();
		$matched = $module->getMatchingPattern();
		$this->assertInstanceOf(TUrlMappingPattern::class, $matched);
		$this->assertEquals('Posts.ViewPost', $matched->getServiceParameter());
	}

	public function testParseUrlWithMultipleVerbs()
	{
		$confstr = '<config><url ServiceParameter="Posts.ListPost" pattern="posts/" verbs="GET,POST"/></config>';
		$config = new TXmlDocument('1.0', 'utf8');
		$config->loadFromString($confstr);
		
		$module = new TUrlMapping();
		$module->init($config);
		
		$request = new THttpRequest();
		$_SERVER['PATH_INFO'] = '/posts/';
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$request->setUrlFormat(THttpRequestUrlFormat::Path);
		$request->init(null);
		
		$result = $module->parseUrl();
		$this->assertEquals(['page' => 'Posts.ListPost'], $result);
	}

	public function testParseUrlVerbNullAllowsAny()
	{
		$confstr = '<config><url ServiceParameter="Posts.ListPost" pattern="posts/"/></config>';
		$config = new TXmlDocument('1.0', 'utf8');
		$config->loadFromString($confstr);
		
		$module = new TUrlMapping();
		$module->init($config);
		
		$request = new THttpRequest();
		$_SERVER['PATH_INFO'] = '/posts/';
		$_SERVER['REQUEST_METHOD'] = 'DELETE';
		$request->setUrlFormat(THttpRequestUrlFormat::Path);
		$request->init(null);
		
		$result = $module->parseUrl();
		$this->assertEquals(['page' => 'Posts.ListPost'], $result);
	}

	public function testParseUrlWildcardPattern()
	{
		$confstr = '<config><url ServiceParameter="adminpages.*" pattern="admin/{*}"/></config>';
		$config = new TXmlDocument('1.0', 'utf8');
		$config->loadFromString($confstr);
		
		$module = new TUrlMapping();
		$module->init($config);
		
		$request = new THttpRequest();
		$_SERVER['PATH_INFO'] = '/admin/users/';
		$request->setUrlFormat(THttpRequestUrlFormat::Path);
		$request->init(null);
		
		$result = $module->parseUrl();
		$this->assertEquals(['page' => 'adminpages.users'], $result);
	}

	public function testParseUrlWithConstants()
	{
		$confstr = '<config><url ServiceParameter="MyPage" pattern="/mypage/" ServiceID="page" constants.type="detailed"/></config>';
		$config = new TXmlDocument('1.0', 'utf8');
		$config->loadFromString($confstr);
		
		$module = new TUrlMapping();
		$module->init($config);
		
		$request = new THttpRequest();
		$_SERVER['PATH_INFO'] = '/mypage/';
		$request->setUrlFormat(THttpRequestUrlFormat::Path);
		$request->init(null);
		
		$result = $module->parseUrl();
		$this->assertEquals(['page' => 'MyPage', 'type' => 'detailed'], $result);
	}

	public function testSetVerbsWithString()
	{
		$pattern = new TUrlMappingPattern(new TUrlManager());
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('test');
		$pattern->setVerbs('GET, POST');
		$this->assertEquals(['GET', 'POST'], $pattern->getVerbs());
	}

	public function testSetVerbsWithArray()
	{
		$pattern = new TUrlMappingPattern(new TUrlManager());
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('test');
		$pattern->setVerbs(['GET', 'POST', 'PUT']);
		$this->assertEquals(['GET', 'POST', 'PUT'], $pattern->getVerbs());
	}

	public function testSetVerbsWithNull()
	{
		$pattern = new TUrlMappingPattern(new TUrlManager());
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('test');
		$pattern->setVerbs(null);
		$this->assertNull($pattern->getVerbs());
	}

	public function testSetVerbsWithEmptyString()
	{
		$pattern = new TUrlMappingPattern(new TUrlManager());
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('test');
		$pattern->setVerbs('');
		$this->assertNull($pattern->getVerbs());
	}
	
	public function testSetVerbsWithTildaNegation()
	{
		$pattern = new TUrlMappingPattern(new TUrlManager());
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('test');
		$pattern->setVerbs('~GET,POST');
		$this->assertEquals(['~GET', 'POST'], $pattern->getVerbs());
	}
	
	public function testSetVerbsWithBangNegation()
	{
		$pattern = new TUrlMappingPattern(new TUrlManager());
		$pattern->setServiceParameter('Test.Page');
		$pattern->setPattern('test');
		$pattern->setVerbs('!GET,POST');
		$this->assertEquals(['!GET', 'POST'], $pattern->getVerbs());
	}

	/**
	 * Removes a module from the shared TApplication module map via reflection.
	 * TApplication::setModule() throws when overwriting an existing slot, so a
	 * direct property reset is required for safe per-test cleanup.
	 */
	private function unregisterAppModule(string $id): void
	{
		$modules = PradoUnit::getProp($this->app, '_modules');
		unset($modules[$id]);
		PradoUnit::setProp($this->app, '_modules', $modules);
	}

	public function testImplementsIModuleDependency()
	{
		$this->assertInstanceOf(\Prado\IModuleDependency::class, new TUrlMapping());
	}

	public function testGetModuleDependencies_noRequestConfigured_returnsNoDeps()
	{
		$module = new TUrlMapping();
		$this->assertModuleDependency(null, $module->getModuleDependencies(false));
	}

	public function testGetModuleDependencies_singleRequestConfigured_returnsId()
	{
		$module = new TUrlMapping();
		$this->app->setModule('url_map_test_request_a', new THttpRequest());
		try {
			$this->assertModuleDependency('url_map_test_request_a', $module->getModuleDependencies(false));
		} finally {
			$this->unregisterAppModule('url_map_test_request_a');
		}
	}

	public function testGetModuleDependencies_multipleRequestsConfigured_returnsAllIds()
	{
		$module = new TUrlMapping();
		$this->app->setModule('url_map_test_request_b1', new THttpRequest());
		$this->app->setModule('url_map_test_request_b2', new THttpRequest());
		try {
			$this->assertModuleDependency(
				['url_map_test_request_b1', 'url_map_test_request_b2'],
				$module->getModuleDependencies(false)
			);
		} finally {
			$this->unregisterAppModule('url_map_test_request_b1');
			$this->unregisterAppModule('url_map_test_request_b2');
		}
	}

	public function testGetModuleDependencies_returnsSameRegardlessOfIsPreInit()
	{
		$module = new TUrlMapping();
		$this->app->setModule('url_map_test_request_c', new THttpRequest());
		try {
			$this->assertModuleDependency(
				$module->getModuleDependencies(true),
				$module->getModuleDependencies(false)
			);
		} finally {
			$this->unregisterAppModule('url_map_test_request_c');
		}
	}

	public function testParseUrlFullMatchModeWithQueryString()
	{
		$confstr = '<config><url ServiceParameter="Posts.EditPost" pattern="post/{id}?mode=edit" parameters.id="\d+" UrlMatchMode="Full"/></config>';
		$config = new TXmlDocument('1.0', 'utf8');
		$config->loadFromString($confstr);

		$module = new TUrlMapping();
		$module->init($config);

		$request = new THttpRequest();
		$_SERVER['PATH_INFO'] = '/post/123';
		$_SERVER['QUERY_STRING'] = 'mode=edit';
		$request->setUrlFormat(THttpRequestUrlFormat::Path);
		$request->init(null);

		$result = $module->parseUrl();
		$this->assertEquals(['id' => '123', 'page' => 'Posts.EditPost'], $result);
	}

	public function testParseUrlFullMatchModeQueryStringMismatch()
	{
		$confstr = '<config><url ServiceParameter="Posts.EditPost" pattern="post/{id}?mode=edit" parameters.id="\d+" UrlMatchMode="Full"/></config>';
		$config = new TXmlDocument('1.0', 'utf8');
		$config->loadFromString($confstr);

		$module = new TUrlMapping();
		$module->init($config);

		$request = new THttpRequest();
		$_SERVER['PATH_INFO'] = '/post/123';
		$_SERVER['QUERY_STRING'] = 'mode=view';
		$request->setUrlFormat(THttpRequestUrlFormat::Path);
		$request->init(null);

		$this->assertNull($module->getMatchingPattern());
		$module->parseUrl();
		$this->assertNull($module->getMatchingPattern());
	}

	public function testParseUrlQueryConstraintSelectsPattern()
	{
		$confstr = '<config>' .
			'<url ServiceParameter="Posts.EditPost" pattern="post/{id}" parameters.id="\d+" query.mode="edit"/>' .
			'<url ServiceParameter="Posts.ViewPost" pattern="post/{id}" parameters.id="\d+"/>' .
			'</config>';
		$config = new TXmlDocument('1.0', 'utf8');
		$config->loadFromString($confstr);

		$module = new TUrlMapping();
		$module->init($config);

		$request = new THttpRequest();
		$_SERVER['PATH_INFO'] = '/post/123';
		$_SERVER['QUERY_STRING'] = 'ref=list&mode=edit';
		$_GET = ['ref' => 'list', 'mode' => 'edit'];
		$request->setUrlFormat(THttpRequestUrlFormat::Path);
		$request->init(null);

		$result = $module->parseUrl();
		$this->assertEquals(['id' => '123', 'page' => 'Posts.EditPost'], $result);
	}

	public function testParseUrlQueryConstraintFallsThroughToNextPattern()
	{
		$confstr = '<config>' .
			'<url ServiceParameter="Posts.EditPost" pattern="post/{id}" parameters.id="\d+" query.mode="edit"/>' .
			'<url ServiceParameter="Posts.ViewPost" pattern="post/{id}" parameters.id="\d+"/>' .
			'</config>';
		$config = new TXmlDocument('1.0', 'utf8');
		$config->loadFromString($confstr);

		$module = new TUrlMapping();
		$module->init($config);

		$request = new THttpRequest();
		$_SERVER['PATH_INFO'] = '/post/123';
		$_SERVER['QUERY_STRING'] = '';
		$_GET = [];
		$request->setUrlFormat(THttpRequestUrlFormat::Path);
		$request->init(null);

		$result = $module->parseUrl();
		$this->assertEquals(['id' => '123', 'page' => 'Posts.ViewPost'], $result);
	}

	public function testConstructUrlWithQueryStringInPattern()
	{
		$confstr = '<config><url ServiceParameter="Posts.EditPost" pattern="post/{id}?mode=edit" parameters.id="\d+" UrlMatchMode="Full"/></config>';
		$config = new TXmlDocument('1.0', 'utf8');
		$config->loadFromString($confstr);

		$module = new TUrlMapping();
		$module->setEnableCustomUrl(true);
		$module->init($config);

		$request = new THttpRequest();
		$request->setUrlFormat(THttpRequestUrlFormat::Path);
		$request->init(null);

		$url = $module->constructUrl('page', 'Posts.EditPost', ['id' => '123'], false, true);
		$this->assertEquals('/index.php/post/123?mode=edit', $url);

		$url = $module->constructUrl('page', 'Posts.EditPost', ['id' => '123', 'ref' => 'list'], false, true);
		$this->assertEquals('/index.php/post/123?mode=edit&ref=list', $url);
	}

	public function testConstructUrlWithQueryConstraint()
	{
		$confstr = '<config><url ServiceParameter="Posts.EditPost" pattern="post/{id}" parameters.id="\d+" query.mode="edit"/></config>';
		$config = new TXmlDocument('1.0', 'utf8');
		$config->loadFromString($confstr);

		$module = new TUrlMapping();
		$module->setEnableCustomUrl(true);
		$module->init($config);

		$request = new THttpRequest();
		$request->setUrlFormat(THttpRequestUrlFormat::Path);
		$request->init(null);

		$url = $module->constructUrl('page', 'Posts.EditPost', ['id' => '123', 'mode' => 'edit'], false, true);
		$this->assertEquals('/index.php/post/123?mode=edit', $url);

		// the pattern is skipped, the URL falls back to the default format of the request
		$url = $module->constructUrl('page', 'Posts.EditPost', ['id' => '123', 'mode' => 'view'], false, true);
		$this->assertEquals('/index.php/page,Posts.EditPost/id,123/mode,view', $url);
	}

	/**
	 * Registers the alias of the URL mapping configuration fixtures.
	 */
	private function mappingDataAlias(): string
	{
		if (Prado::getPathOfAlias('TUrlMappingTestData') === null) {
			Prado::setPathOfAlias('TUrlMappingTestData', __DIR__ . '/data/urlmapping');
		}
		return 'TUrlMappingTestData';
	}

	private function assertErrorCode(string $code, callable $test): void
	{
		try {
			$test();
			$this->fail("Expected the {$code} exception.");
		} catch (TConfigurationException $e) {
			$this->assertSame($code, $e->getErrorCode());
		}
	}

	private function newPathRequest(string $pathInfo = '', string $format = THttpRequestUrlFormat::Path): THttpRequest
	{
		$_SERVER['PATH_INFO'] = $pathInfo;
		$request = new THttpRequest();
		$request->setUrlFormat($format);
		$request->init(null);
		return $request;
	}

	private function xmlConfig(string $urls): TXmlDocument
	{
		$config = new TXmlDocument('1.0', 'utf8');
		$config->loadFromString('<config>' . $urls . '</config>');
		return $config;
	}

	public function testInitThrowsWhenRequestResolved()
	{
		$request = $this->newPathRequest();
		PradoUnit::setProp($request, '_requestResolved', true);
		$module = new TUrlMapping();
		$this->assertErrorCode('urlmapping_global_required', fn () => $module->init([]));
	}

	public function testInitLoadsXmlConfigFile()
	{
		$module = new TUrlMapping();
		$module->setConfigFile($this->mappingDataAlias() . '.mappings');
		$this->assertSame(implode(DIRECTORY_SEPARATOR, [__DIR__, 'data', 'urlmapping', 'mappings.xml']), $module->getConfigFile());
		$module->init([]);
		$this->newPathRequest('/post/7/');
		$this->assertEquals(['id' => '7', 'page' => 'Posts.ViewPost'], $module->parseUrl());
	}

	public function testInitLoadsPhpConfigFile()
	{
		$this->app->restoreApplication();
		$this->app = new TTestApplication(__DIR__ . '/app', false, TApplication::CONFIG_TYPE_PHP);
		$module = new TUrlMapping();
		$module->setConfigFile($this->mappingDataAlias() . '.mappings');
		$this->assertSame(implode(DIRECTORY_SEPARATOR, [__DIR__, 'data', 'urlmapping', 'mappings.php']), $module->getConfigFile());
		$module->init([]);
		$this->newPathRequest('/post/8/');
		$this->assertEquals(['id' => '8', 'page' => 'Posts.ViewPost'], $module->parseUrl());
	}

	public function testInitThrowsForMissingConfigFile()
	{
		$module = new TUrlMapping();
		$module->setConfigFile($this->mappingDataAlias() . '.missing');
		$this->assertErrorCode('urlmapping_configfile_inexistent', fn () => $module->init([]));
	}

	public function testSetConfigFileThrowsForUnknownAlias()
	{
		$module = new TUrlMapping();
		$this->assertErrorCode('urlmapping_configfile_invalid', fn () => $module->setConfigFile('NoSuchUrlMappingAlias.mappings'));
		$this->assertNull($module->getConfigFile());
	}

	public function testInitUrlPrefix()
	{
		$this->newPathRequest();
		$module = new TUrlMapping();
		$module->init([]);
		$this->assertSame('/index.php', $module->getUrlPrefix());

		$this->newPathRequest('', THttpRequestUrlFormat::HiddenPath);
		$module = new TUrlMapping();
		$module->init([]);
		$this->assertSame('', $module->getUrlPrefix());

		$module = new TUrlMapping();
		$module->setUrlPrefix('/prefix/');
		$this->assertSame('/prefix/', $module->getUrlPrefix());
		$module->init([]);
		$this->assertSame('/prefix', $module->getUrlPrefix());
	}

	public function testLoadUrlMappingsFromArray()
	{
		$module = new TUrlMapping();
		$module->setDefaultMappingClass(TNestedPathUrlMappingPattern::class);
		$this->assertSame(TNestedPathUrlMappingPattern::class, $module->getDefaultMappingClass());
		$module->init(['urls' => [
			['properties' => ['ServiceParameter' => 'Posts.ViewPost', 'Pattern' => 'post/{id}/', 'Parameters.id' => '\d+']],
			['class' => TUrlMappingPattern::class, 'properties' => ['ServiceParameter' => 'Posts.ListPost', 'Pattern' => 'posts/']],
		]]);
		$patterns = PradoUnit::getProp($module, '_patterns');
		$this->assertCount(2, $patterns);
		$this->assertInstanceOf(TNestedPathUrlMappingPattern::class, $patterns[0]);
		$this->assertSame(TUrlMappingPattern::class, $patterns[1]::class);
		$this->assertSame('Posts.ListPost', $patterns[1]->getServiceParameter());
	}

	public function testLoadUrlMappingsWithoutUrls()
	{
		foreach ([[], ['urls' => 'none'], ['urls' => []]] as $config) {
			$module = new TUrlMapping();
			$module->init($config);
			$this->assertSame([], PradoUnit::getProp($module, '_patterns'));
		}
		$module = new TUrlMapping();
		$module->init($this->xmlConfig(''));
		$this->assertSame([], PradoUnit::getProp($module, '_patterns'));
	}

	public function testLoadUrlMappingsXmlClassAttribute()
	{
		$module = new TUrlMapping();
		$module->init($this->xmlConfig('<url class="' . TNestedPathUrlMappingPattern::class . '" ServiceParameter="Posts.ViewPost" pattern="post/"/>'));
		$patterns = PradoUnit::getProp($module, '_patterns');
		$this->assertInstanceOf(TNestedPathUrlMappingPattern::class, $patterns[0]);
		$this->assertSame('post/', $patterns[0]->getPattern());
	}

	public function testBuildUrlMappingRequiresPatternClass()
	{
		$module = new TUrlMapping();
		$this->assertErrorCode('urlmapping_urlmappingpattern_required', fn () => $module->init(['urls' => [['class' => TUrlManager::class]]]));
	}

	public function testBuildUrlMappingNodeValueIsRegularExpression()
	{
		$module = new TUrlMapping();
		$module->init($this->xmlConfig('<url ServiceParameter="Posts.ViewPost"> /^post\/(?P&lt;id&gt;\d+)$/ </url>'));
		$patterns = PradoUnit::getProp($module, '_patterns');
		$this->assertSame('/^post\/(?P<id>\d+)$/', $patterns[0]->getRegularExpression());

		$this->newPathRequest('post/12');
		$this->assertEquals(['id' => '12', 'page' => 'Posts.ViewPost'], $module->parseUrl());
	}

	public function testParseUrlSkipsPatternsAndIgnoresNumericGroups()
	{
		$module = new TUrlMapping();
		$module->init($this->xmlConfig(
			'<url ServiceParameter="Posts.ListPost" pattern="posts/"/>'
			. '<url ServiceParameter="Posts.ViewPost" pattern="post/{id}/" parameters.id="(\d+)"/>'
		));
		$this->newPathRequest('/post/5/');
		$this->assertEquals(['id' => '5', 'page' => 'Posts.ViewPost'], $module->parseUrl());
		$this->assertSame('Posts.ViewPost', $module->getMatchingPattern()->getServiceParameter());
	}

	public function testParseUrlWithoutPatternsFallsBack()
	{
		$module = new TUrlMapping();
		$module->init([]);
		$this->newPathRequest('/page,Home/');
		$this->assertEquals(['page' => 'Home'], $module->parseUrl());
		$this->assertNull($module->getMatchingPattern());
	}

	public function testConstructUrlDisabledUsesDefaultFormat()
	{
		$module = new TUrlMapping();
		$module->init($this->xmlConfig('<url ServiceParameter="Posts.ViewPost" pattern="post/{id}/" parameters.id="\d+"/>'));
		$this->newPathRequest();
		$this->assertSame('/index.php/page,Posts.ViewPost/id,3', $module->constructUrl('page', 'Posts.ViewPost', ['id' => 3], false, true));
	}

	public function testConstructUrlCustomRules()
	{
		$module = new TUrlMapping();
		$module->setEnableCustomUrl(true);
		$module->init($this->xmlConfig(
			'<url ServiceParameter="Posts.ViewPost" pattern="post/{id}/" parameters.id="\d+"/>'
			. '<url ServiceParameter="Posts.ViewPost" pattern="posts/"/>'
			. '<url ServiceParameter="Posts.ListPost" pattern="list/" EnableCustomUrl="false"/>'
			. '<url ServiceParameter="admin.*" pattern="admin/{*}/"/>'
			. '<url ServiceParameter="*" pattern="any/{*}/"/>'
		));
		$this->newPathRequest();

		// a rule without the parameter is skipped for the next one of the key
		$this->assertSame('/index.php/posts/', $module->constructUrl('page', 'Posts.ViewPost', null, false, true));
		$this->assertSame('/index.php/post/4/', $module->constructUrl('page', 'Posts.ViewPost', new \ArrayObject(['id' => 4]), false, true));

		// no rule of the key supports the URL
		$this->assertSame('/index.php/page,Posts.ListPost', $module->constructUrl('page', 'Posts.ListPost', [], false, true));

		// wildcard rules for a parameter with and without a dot
		$this->assertSame('/index.php/admin/users/', $module->constructUrl('page', 'admin.users', [], false, true));
		$this->assertSame('/index.php/any/Home/', $module->constructUrl('page', 'Home', [], false, true));

		// no rule for the key or its wildcard key
		$this->assertSame('/index.php/other,Home', $module->constructUrl('other', 'Home', [], false, true));
	}

	public function testConstructUrlWildcardRuleNotSupported()
	{
		$module = new TUrlMapping();
		$module->setEnableCustomUrl(true);
		$module->init($this->xmlConfig('<url ServiceParameter="admin.*" pattern="admin/{*}/{id}/" parameters.id="\d+"/>'));
		$this->newPathRequest();
		$this->assertSame('/index.php/page,admin.users', $module->constructUrl('page', 'admin.users', [], false, true));
		$this->assertSame('/index.php/admin/users/9/', $module->constructUrl('page', 'admin.users', ['id' => 9], false, true));
	}

	public function testParseUrlSingleMatchElement()
	{
		foreach ([
			// [ServiceParameter, matches, expected]
			['Posts.ViewPost', ['id' => '1'], ['id' => '1', 'page' => 'Posts.ViewPost']],
			['Posts.*', ['page' => 'Posts.View'], ['page' => 'Posts.View']],
			['Posts.*', ['/posts/'], []],
		] as [$serviceParameter, $matches, $expected]) {
			$module = new TUrlMapping();
			$module->init(['urls' => [['class' => TFixedMatchUrlMappingPattern::class, 'properties' => ['ServiceParameter' => $serviceParameter]]]]);
			PradoUnit::getProp($module, '_patterns')[0]->matches = $matches;
			$this->assertSame($expected, $module->parseUrl(), $serviceParameter);
		}
	}

	public function testInitConfigFileWithUrlPrefix()
	{
		$this->newPathRequest('', THttpRequestUrlFormat::HiddenPath);
		$module = new TUrlMapping();
		$module->setConfigFile($this->mappingDataAlias() . '.mappings');
		$module->init([]);
		$this->assertSame('', $module->getUrlPrefix());
		$this->assertCount(1, PradoUnit::getProp($module, '_patterns'));

		$module = new TUrlMapping();
		$module->setConfigFile($this->mappingDataAlias() . '.mappings');
		$module->setUrlPrefix('/app/');
		$module->init([]);
		$this->assertSame('/app', $module->getUrlPrefix());
		$this->assertCount(1, PradoUnit::getProp($module, '_patterns'));
	}

	public function testInitWithNullConfigLoadsNoPatterns()
	{
		$module = new TUrlMapping();
		$module->init(null);
		$this->assertSame([], PradoUnit::getProp($module, '_patterns'));
	}

	public function testNodeValueReplacingRegularExpressionLogsNotice()
	{
		$config = new TXmlDocument('1.0', 'utf8');
		$config->loadFromString('<config><url ServiceParameter="Posts.ViewPost" RegularExpression="/^old$/">/^new$/</url></config>');
		$logger = \Prado\Prado::getLogger();
		$before = count($logger->getLogs(\Prado\Util\Log\TLogger::NOTICE, TUrlMapping::class));
		$module = new TUrlMapping();
		$module->init($config);
		$patterns = PradoUnit::getProp($module, '_patterns');
		$this->assertSame('/^new$/', $patterns[0]->getRegularExpression());
		$logs = $logger->getLogs(\Prado\Util\Log\TLogger::NOTICE, TUrlMapping::class);
		$this->assertCount($before + 1, $logs);
		$this->assertStringContainsString('"/^old$/" for ServiceID="page" and ServiceParameter="Posts.ViewPost" was replaced by node value "/^new$/"', end($logs)[0]);
	}

	public function testInitWithXmlConfigWithoutUrlsLoadsNoPatterns()
	{
		$module = new TUrlMapping();
		$module->init($this->xmlConfig('<other/>'));
		$this->assertSame([], PradoUnit::getProp($module, '_patterns'));
	}
}
