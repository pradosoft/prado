<?php

require_once __DIR__ . '/../PradoUnitRequires.php';

use Prado\Web\THttpRequest;
use Prado\Web\THttpRequestUrlFormat;
use Prado\Web\TUrlMapping;
use Prado\Web\TUrlMappingPattern;
use Prado\Web\TUrlManager;
use Prado\Xml\TXmlDocument;

/**
 * Test class for TUrlMapping.
 */
class TUrlMappingTest extends PHPUnit\Framework\TestCase
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
}