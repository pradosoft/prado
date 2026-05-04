<?php

use Prado\Web\THttpRequest;
use Prado\Web\THttpRequestUrlFormat;
use Prado\Web\TUrlMapping;
use Prado\Web\TUrlMappingPattern;
use Prado\Web\TUrlManager;
use Prado\TApplication;
use Prado\Xml\TXmlDocument;

/**
 * Test class for TUrlMapping.
 */
class TUrlMappingTest extends PHPUnit\Framework\TestCase
{
	protected static $app = null;

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

		if (self::$app === null) {
			self::$app = new TApplication(__DIR__ . '/app');
		}
	}

	protected function tearDown(): void
	{
		parent::tearDown();
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
}