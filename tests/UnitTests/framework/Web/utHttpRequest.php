<?php

require_once(dirname(__FILE__).'/../common.php');

class utHttpRequest extends ModuleTestCase {
	
	/**
	 * @var THttpRequest protected. The THttpRequest object we're using for testing.
	 */
	protected $request;


	public function testSetUp() {
		$this->request =& new THttpRequest();
		$this->module =& $this->request;		// $this->module is used automatically by the underlying class
		$this->initModule($this->defaultConfig);
	}
	
	public function tearDown() {
		$this->request = null;
		parent::tearDown();
	}
	
	public function testInit($application,$config) {
		// Configuration case 1...
		$_SERVER = array(
			"REQUEST_URI" => "/foo.php?bar",
			"SCRIPT_NAME" => "/foo.php",
			"QUEST_STRING" => "bar",
			"PATH_INFO" => "/foo.php",
			"PHP_SELF" => "/foo.php",
		);
		$this->mockApplication->expectOnce("setRequest");
		$this->initModule();
		$this->mockApplication->tally();
		$this->assertIsA($this->request->getItems(), "TMap");

		$this->assertEqual($this->request->getRequestUri(), "/foo.php?bar");
		$this->assertEqual($this->request->getPathInfo(), "/foo.php");

		$_SERVER = array(
			"REQUEST_URI" => "/aaa/bbb/ccc/foo.php?bar",
			"SCRIPT_NAME" => "",
			"QUEST_STRING" => "",
			"PATH_INFO" => "/aaa/bbb/ccc/foo.php",
			"PHP_SELF" => "/aaa/bbb/ccc/foo.php",
		);
		$this->initModule();
		$this->assertEqual($this->request->getRequestUri(), "/aaa/bbb/ccc/foo.php?bar");
		$this->assertEqual($this->request->getPathInfo(), "/aaa/bbb/ccc/foo.php");
	
		$_SERVER = array(
			"SCRIPT_NAME" => "/foo.php",
			"QUEST_STRING" => "bar",
			"PATH_INFO" => "",
			"PHP_SELF" => "/foo.php",
		);
		$this->initModule();
		$this->assertEqual($this->request->getRequestUri(), "/foo.php?bar");
		$this->assertEqual($this->request->getPathInfo(), "/foo.php");
	
		$_SERVER = array(
			"REQUEST_URI" => "/foo.php?bar",
			"PATH_INFO" => "/foo.php?bar",
		);
		$this->initModule();
		$this->assertEqual($this->request->getRequestUri(), "/foo.php?bar");
		$this->assertEqual($this->request->getPathInfo(), "/foo.php");
	}
	
	/*public function testGetUrl() {
		if($this->_url===null)
		{
			$secure=$this->getIsSecureConnection();
			$url=$secure?'https://':'http://';
			if(empty($_SERVER['HTTP_HOST']))
			{
				$url.=$_SERVER['SERVER_NAME'];
				$port=$_SERVER['SERVER_PORT'];
				if(($port!=80 && !$secure) || ($port!=443 && $secure))
					$url.=':'.$port;
			}
			else
				$url.=$_SERVER['HTTP_HOST'];
			$url.=$this->getRequestUri();
			$this->_url=new TUri($url);
		}
		return $this->_url;
	}*/

	public function testGetRequestType() {
		$_SERVER = array("REQUEST_METHOD" => "GET");
		$this->assertEqual($this->request->getRequestType(), "GET");
	}

	public function testGetIsSecureConnection() {
		$_SERVER = array("HTTPS" => "true");
		$this->assertTrue($this->request->getIsSecureConnection());
		$_SERVER = array("HTTPS" => "");
		$this->assertFalse($this->request->getIsSecureConnection());
		$_SERVER = array();
		$this->assertFalse($this->request->getIsSecureConnection());
	}

	public function testGetQueryString() {
		$_SERVER = array("QUERY_STRING" => "foo=bar");
		$this->assertEqual($this->request->getQueryString(), "foo=bar");
		$_SERVER = array("QUERY_STRING" => "");
		$this->assertEqual($this->request->getQueryString(), "");
		$_SERVER = array();
		$this->assertEqual($this->request->getQueryString(), "");
	}

	public function testGetApplicationPath() {
		$_SERVER = array("SCRIPT_NAME" => "/foo/bar.php");
		$this->assertEqual($this->request->getApplicationPath(), "/foo/bar.php");
	}

	public function testGetPhysicalApplicationPath() {
		$_SERVER = array("SCRIPT_FILENAME" => "/var/www/foo.php");
		$this->assertEqual($this->request->getPhysicalApplicationPath(), "/var/www/foo.php");
		$_SERVER = array("SCRIPT_FILENAME" => "C:\\web\\foo.php");
		$this->assertEqual($this->request->getPhysicalApplicationPath(), "/web/foo.php");
	}

	public function testGetServerName() {
		$_SERVER = array("SERVER_NAME" => "foobar");
		$this->assertEqual($this->request->getApplicationPath(), "foobar");
	}

	public function testGetServerPort() {
		$_SERVER = array("SERVER_PORT" => "80");
		$this->assertEqual($this->request->getApplicationPath(), 80);
	}

	public function testGetUrlReferrer() {
		$_SERVER = array("HTTP_REFERRER" => "http://www.google.com/");
		$this->assertEqual($this->request->getPhysicalApplicationPath(), "http://www.google.com/");
		$_SERVER = array();
		$this->assertNull($this->request->getPhysicalApplicationPath());
	}

	/*public function testGetBrowser() {
		return get_browser();
	}*/

	public function testGetUserAgent() {
		$_SERVER = array("HTTP_USER_AGENT" => "foo");
		$this->assertEqual($this->request->getUserAgent(), "foo");
	}

	public function testGetUserHostAddress() {
		$_SERVER = array("REMOTE_ADDR" => "121.212.121.212");
		$this->assertEqual($this->request->getUserHostAddress(), "121.212.121.212");
	}

	public function testGetUserHost() {
		$_SERVER = array("REMOTE_ADDR" => "foo");
		$this->assertEqual($this->request->getUserHostAddress(), "foo");
		$_SERVER = array();
		$this->assertNull($this->request->getUserHostAddress());
	}

	public function testGetAcceptTypes() {
		$_SERVER = array("REMOTE_ADDR" => "foo bar");
		$this->assertEqual($this->request->getAcceptTypes(), "foo bar");
	}

	public function testGetUserLanguages() {
		$_SERVER = array("HTTP_ACCEPT_LANGUAGE" => "foo");
		$this->assertEqual($this->request->getUserLanguages(), "foo");
	}

	public function testGetCookies() {
		$_COOKIES = array("foo" => "bar", "xxx" => "yyy");
		
		// Repeat this twice. The first time tests the parsing logic for $_COOKIES.
		// The second time tests the internal caching logic.
		for ($i = 0; $i < 2; $i++) {
			$cookies = $this->request->getCookies();
			$this->assertIsA($cookies, "THttpCookieCollection");
			$this->asserEqual($cookies->getCount(), 2);
			$first = $cookies->itemAt(0);
			$this->assertEqual($first->getName(), "foo");
			$this->assertEqual($first->getName(), "bar");
			$second = $cookies->itemAt(0);
			$this->assertEqual($second->getName(), "xxx");
			$this->assertEqual($second->getName(), "yyy");
		}
	}

	/*public function testGetUploadedFiles() {
		if($this->_files===null)
			$this->_files=new TMap($_FILES);
		return $this->_files;
	}

	public function testGetServerVariables() {
		if($this->_server===null)
			$this->_server=new TMap($_SERVER);
		return $this->_server;
	}

	public function testGetEnvironmentVariables() {
		if($this->_env===null)
			$this->_env=new TMap($_ENV);
		return $this->_env;
	}*/

	public function testConstructUrl($serviceID,$serviceParam,$getItems=null) {
		$_SERVER = array("SCRIPT_NAME" => "/foo/bar.php");
		$sv = THttpRequest::SERVICE_VAR;
		
		// Note: we can't undefine SID so we can't ensure that the case when SID is not present will be tested.
		// Therefore, we define it here so we can be sure that it *is* defined. If it was already defined before
		// entering this function then this define() will be ignored. This doesn't matter as immediately after defining
		// SID we read it into $sid.
		define("SID", "123");
		$sid = SID;
		
		$this->assertEqual($this->request->constructUrl("page", ""), "/foo/bar.php?$sv=page&$sid");
		$this->assertEqual($this->request->constructUrl("page", null), "/foo/bar.php?$sv=page&$sid");
		$this->assertEqual($this->request->constructUrl("page", "myPage"), "/foo/bar.php?$sv=page.myPage&$sid");
		$this->assertEqual($this->request->constructUrl("page", "myPage", array("aaa"=>"aaa")), "/foo/bar.php?$sv=page.myPage&aaa=aaa&$sid");
		$this->assertEqual($this->request->constructUrl("page", "myPage", array("aaa"=>"aaa","bbb"=>"bbb")), "/foo/bar.php?$sv=page.myPage&aaa=aaa&bbb=bbb&$sid");
		$this->assertEqual($this->request->constructUrl("page", "myPage", new TList(array("aaa"=>"aaa"))), "/foo/bar.php?$sv=page.myPage&aaa=aaa&$sid");
	}

	protected function testResolveRequest() {
		$sv = THttpRequest::SERVICE_VAR;
		
		$_POST = array($sv => "page");
		$this->initModule();
		$this->request->resolveRequest();
		$this->assertEqual($this->request->getServiceID(), "page");
		$this->assertEqual($this->request->getServiceParameter(), "");

		$_POST = array($sv => "page.xxx");
		$this->initModule();
		$this->request->resolveRequest();
		$this->assertEqual($this->request->getServiceID(), "page");
		$this->assertEqual($this->request->getServiceParameter(), "xxx");
				
		$_GET = array($sv => "page.xxx");
		$this->initModule();
		$this->request->resolveRequest();
		$this->assertEqual($this->request->getServiceID(), "page");
		$this->assertEqual($this->request->getServiceParameter(), "xxx");

		$_POST = array($sv => "page");
		$this->initModule();
		$this->request->resolveRequest();
		$this->assertEqual($this->request->getServiceID(), "page");
		$this->assertEqual($this->request->getServiceParameter(), "");
	}

	public function testGetSetServiceID() {
		$this->request->setServiceID("foo");
		$this->assertEqual($this->request->getServiceID(), "foo");
	}

	public function testGetSetServiceParameterD() {
		$this->request->setServiceParameter("foo");
		$this->assertEqual($this->request->getServiceParameter(), "foo");
	}
}

?>