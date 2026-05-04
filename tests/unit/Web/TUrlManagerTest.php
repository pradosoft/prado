<?php

use Prado\Web\THttpRequest;
use Prado\Web\THttpRequestUrlFormat;
use Prado\Web\TUrlManager;
use Prado\TApplication;

/**
 * Test class for TUrlManager.
 */
class TUrlManagerTest extends PHPUnit\Framework\TestCase
{
	protected static $app = null;

	protected function setUp(): void
	{
		// Fake environment variables
		$_SERVER['HTTP_HOST'] = 'localhost';
		$_SERVER['SERVER_NAME'] = 'localhost';
		$_SERVER['SERVER_PORT'] = '80';
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['REQUEST_URI'] = '/index.php?page=Home';
		$_SERVER['SCRIPT_NAME'] = '/index.php';
		$_SERVER['PHP_SELF'] = '/index.php';
		$_SERVER['QUERY_STRING'] = 'page=Home';
		$_SERVER['SCRIPT_FILENAME'] = __FILE__;
		$_SERVER['PATH_INFO'] = '';

		if (self::$app === null) {
			self::$app = new TApplication(__DIR__ . '/app/');
		}
	}

	protected function tearDown(): void
	{
		parent::tearDown();
		$_GET = [];
		$_POST = [];
		$_SERVER['PATH_INFO'] = '';
		$_SERVER['QUERY_STRING'] = '';
	}

	public function testConstructUrlGetFormat()
	{
		$urlManager = new TUrlManager();
		$urlManager->init(null);

		$request = new THttpRequest();
		$request->setUrlFormat(THttpRequestUrlFormat::Get);
		$request->init(null);

		$url = $urlManager->constructUrl('page', 'Home', ['param1' => 'value1'], true, true);
		$this->assertEquals('/index.php?page=Home&amp;param1=value1', $url);
	}

	public function testConstructUrlGetFormatNoEncodeAmpersand()
	{
		$urlManager = new TUrlManager();
		$urlManager->init(null);

		$request = new THttpRequest();
		$request->setUrlFormat(THttpRequestUrlFormat::Get);
		$request->init(null);

		$url = $urlManager->constructUrl('page', 'Home', ['param1' => 'value1'], false, true);
		$this->assertEquals('/index.php?page=Home&param1=value1', $url);
	}

	public function testConstructUrlPathFormat()
	{
		$urlManager = new TUrlManager();
		$urlManager->init(null);

		$request = new THttpRequest();
		$request->setUrlFormat(THttpRequestUrlFormat::Path);
		$request->init(null);

		$url = $urlManager->constructUrl('page', 'Home', ['param1' => 'value1'], true, true);
		$this->assertEquals('/index.php/page,Home/param1,value1', $url);
	}

	public function testConstructUrlHiddenPathFormat()
	{
		$urlManager = new TUrlManager();
		$urlManager->init(null);

		$request = new THttpRequest();
		$request->setUrlFormat(THttpRequestUrlFormat::HiddenPath);
		$request->init(null);

		$url = $urlManager->constructUrl('page', 'Home', ['param1' => 'value1'], true, true);
		$this->assertEquals('/page,Home/param1,value1', $url);
	}

	public function testConstructUrlWithArrayValues()
	{
		$urlManager = new TUrlManager();
		$urlManager->init(null);

		$request = new THttpRequest();
		$request->setUrlFormat(THttpRequestUrlFormat::Get);
		$request->init(null);

		$url = $urlManager->constructUrl('page', 'Home', ['items' => ['a', 'b', 'c']], true, true);
		$this->assertEquals('/index.php?page=Home&amp;items%5B%5D=a&amp;items%5B%5D=b&amp;items%5B%5D=c', $url);
	}

	public function testConstructUrlWithNullGetItems()
	{
		$urlManager = new TUrlManager();
		$urlManager->init(null);

		$request = new THttpRequest();
		$request->setUrlFormat(THttpRequestUrlFormat::Get);
		$request->init(null);

		$url = $urlManager->constructUrl('page', 'Home', null, true, true);
		$this->assertEquals('/index.php?page=Home', $url);
	}

	public function testConstructUrlWithEmptyArrayGetItems()
	{
		$urlManager = new TUrlManager();
		$urlManager->init(null);

		$request = new THttpRequest();
		$request->setUrlFormat(THttpRequestUrlFormat::Get);
		$request->init(null);

		$url = $urlManager->constructUrl('page', 'Home', [], true, true);
		$this->assertEquals('/index.php?page=Home', $url);
	}

	public function testParseUrlGetFormat()
	{
		$urlManager = new TUrlManager();
		$urlManager->init(null);

		$request = new THttpRequest();
		$_GET['page'] = 'Home';
		$_GET['param1'] = 'value1';
		$request->setUrlFormat(THttpRequestUrlFormat::Get);
		$request->init(null);

		$result = $urlManager->parseUrl();
		$this->assertEquals([], $result);
	}

	public function testParseUrlPathFormat()
	{
		$urlManager = new TUrlManager();
		$urlManager->init(null);

		$request = new THttpRequest();
		$_SERVER['PATH_INFO'] = '/page,Home/param1,value1';
		$request->setUrlFormat(THttpRequestUrlFormat::Path);
		$request->init(null);

		$result = $urlManager->parseUrl();
		$this->assertEquals(['page' => 'Home', 'param1' => 'value1'], $result);
	}

	public function testParseUrlHiddenPathFormat()
	{
		$urlManager = new TUrlManager();
		$urlManager->init(null);

		$request = new THttpRequest();
		$_SERVER['PATH_INFO'] = '/page,Home/param1,value1';
		$request->setUrlFormat(THttpRequestUrlFormat::HiddenPath);
		$request->init(null);

		$result = $urlManager->parseUrl();
		$this->assertEquals(['page' => 'Home', 'param1' => 'value1'], $result);
	}

	public function testParseUrlEmptyPathInfo()
	{
		$urlManager = new TUrlManager();
		$urlManager->init(null);

		$request = new THttpRequest();
		$_SERVER['PATH_INFO'] = '';
		$request->setUrlFormat(THttpRequestUrlFormat::Path);
		$request->init(null);

		$result = $urlManager->parseUrl();
		$this->assertEquals([], $result);
	}

	public function testParseUrlWithArrayParameter()
	{
		$urlManager = new TUrlManager();
		$urlManager->init(null);

		$request = new THttpRequest();
		$_SERVER['PATH_INFO'] = '/items[],a/items[],b';
		$request->setUrlFormat(THttpRequestUrlFormat::Path);
		$request->init(null);

		$result = $urlManager->parseUrl();
		$this->assertEquals(['items' => ['a', 'b']], $result);
	}

	public function testParseUrlWithCustomSeparator()
	{
		$urlManager = new TUrlManager();
		$urlManager->init(null);

		$request = new THttpRequest();
		$_SERVER['PATH_INFO'] = '/page-Home/param1-value1';
		$request->setUrlFormat(THttpRequestUrlFormat::Path);
		$request->setUrlParamSeparator('-');
		$request->init(null);

		$result = $urlManager->parseUrl();
		$this->assertEquals(['page' => 'Home', 'param1' => 'value1'], $result);
	}

	public function testParseUrlWithTrailingSlash()
	{
		$urlManager = new TUrlManager();
		$urlManager->init(null);

		$request = new THttpRequest();
		$_SERVER['PATH_INFO'] = '/page,Home/';
		$request->setUrlFormat(THttpRequestUrlFormat::Path);
		$request->init(null);

		$result = $urlManager->parseUrl();
		$this->assertEquals(['page' => 'Home'], $result);
	}

	public function testParseUrlWithOnlyKey()
	{
		$urlManager = new TUrlManager();
		$urlManager->init(null);

		$request = new THttpRequest();
		$_SERVER['PATH_INFO'] = '/page,Home/flag';
		$request->setUrlFormat(THttpRequestUrlFormat::Path);
		$request->init(null);

		$result = $urlManager->parseUrl();
		$this->assertEquals(['page' => 'Home', 'flag' => ''], $result);
	}
}
