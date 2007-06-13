<?php
require_once dirname(__FILE__).'/../phpunit.php';

Prado::using('System.Web.THttpRequest');
Prado::using('System.Security.TSecurityManager');

/**
 * @package System.Web
 */
class THttpRequestTest extends PHPUnit_Framework_TestCase {

	public static $app = null;

	public function setUp() {
		
		// Fake environment variables
		$_SERVER['HTTP_HOST'] = 'localhost';
		$_SERVER['SERVER_NAME'] = 'localhost';
		$_SERVER['SERVER_PORT'] = '80';
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['REQUEST_URI'] = '/demos/personal/index.php?page=Links';
		$_SERVER['SCRIPT_NAME'] = '/demos/personal/index.php';
		$_SERVER['PHP_SELF'] = '/demos/personal/index.php';
		$_SERVER['QUERY_STRING'] = 'page=Links';
		$_SERVER['SCRIPT_FILENAME'] = __FILE__;
		$_SERVER['PATH_INFO'] = __FILE__;
		$_SERVER['HTTP_REFERER'] = 'http://www.pradosoft.com';
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3';
		$_SERVER['REMOTE_HOST'] = 'localhost';
		$_SERVER['HTTP_ACCEPT'] = 'text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5';
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr,en-us;q=0.8,fr-fr;q=0.5,en;q=0.3';
		$_SERVER['HTTP_ACCEPT_ENCODING'] = 'gzip,deflate';
		$_SERVER['HTTP_ACCEPT_CHARSET'] = 'ISO-8859-1,utf-8;q=0.7,*;q=0.7';
		
		$_COOKIE['phpsessid']='0123456789abcdef';
		
		$_FILES['userfile']=array ('name' => 'test.jpg','type' => 'image/jpg', 'size' => 10240, 'tmp_name' => 'tmpXXAZECZ', 'error'=>0);
		if(self::$app === null) {
			self::$app = new TApplication(dirname(__FILE__).'/app');
		}
	}
	
	public function testInit() {
		$request = new THttpRequest();
		$request->init(null);
		self::assertEquals('', $request->getUrlManager());
		// Try with unsetted REQUEST_URL & PATH_INFO
		unset($_SERVER['REQUEST_URI']);
		unset($_SERVER['PATH_INFO']);
		$request = new THttpRequest();
		$request->init(null);
		self::assertEquals('', $request->getUrlManager());
		
	}

	public function testStripSlashes() {
		$request = new THttpRequest();
		$data = 'some\\text\\with\\slashes';
		self::assertEquals('sometextwithslashes', $request->stripSlashes($data));
	}
	
	public function testGetUrl() {
		$request = new THttpRequest();
		$request->init(null);
		self::assertType('TUri', $request->getUrl());
		// Try with $_SERVER['HTTP_HOST'] empty
		$request=null;
		$request = new THttpRequest();
		$request->init(null);
		$_SERVER['HTTP_HOST']='';
		self::assertType('TUri', $request->getUrl());
	}

	public function testGetUrlManager() {
		$request = new THttpRequest();
		$request->init(null);
		self::assertEquals(null, $request->getUrlManager());
	}
	
	public function testSetUrlManager() {
		$request = new THttpRequest();
		// Try invalid manager id
		$request->setUrlManager('invalidManagerId');
		try {
			$request->init(null);
			self::fail ('httprequest_urlmanager_inexist exception not thrown');
		} catch (TConfigurationException $e) {
			
		}
		$request=null;
		
		
		// Try with valid module id, but not instance of TUrlManager
		$module=new TAssetManager();
		self::$app->setModule('badmanager',$module);
		$request = new THttpRequest();
		$request->setUrlManager('badmanager');
		try {
			$request->init(null);
			self::fail ('httprequest_urlmanager_invalid exception not thrown');
		} catch (TConfigurationException $e) {
			
		}
		$request=null;
		
		// Finally, try with a valid manager
		$module=new TUrlManager ();
		self::$app->setModule('goodmanager',$module);
		$request = new THttpRequest();
		$request->setUrlManager('goodmanager');
		$request->init(null);
		self::assertEquals ('goodmanager', $request->getUrlManager());
		self::assertType ('TUrlManager',$request->getUrlManagerModule());
		
	}

	public function testSetUrlFormat() {
		$request = new THttpRequest();
		$request->setUrlFormat('Path');
		self::assertEquals('Path', $request->getUrlFormat());
		// Test invalid
		try  {
			$request->setUrlFormat('Bad');
			self::fail ('Bad Value exception not thrown');
		} catch (TInvalidDataValueException $e) {} 
	}

	public function testGetRequestType() {
		$request = new THttpRequest();
		self::assertEquals('GET', $request->getRequestType());
	}

	public function testGetIsSecureConnection() {
		$request = new THttpRequest();
		self::assertEquals(false, $request->getIsSecureConnection());
	}

	public function testGetPathInfo() {
		$request = new THttpRequest();
		$request->init(null);
		self::assertEquals(__FILE__, $request->getPathInfo());
	}

	public function testGetQueryString() {
		$request = new THttpRequest();
		$request->init(null);
		self::assertEquals('page=Links', $request->getQueryString());
	}

	public function testGetRequestUri() {
		$request = new THttpRequest();
		$request->init(null);
		self::assertEquals('/demos/personal/index.php?page=Links', $request->getRequestUri());
	}
	
	public function testGetBaseUrl() {
		$request = new THttpRequest();
		$request->init(null);
		self::assertEquals('http://localhost', $request->getBaseUrl());
	}

	public function testGetApplicationUrl() {
		$request = new THttpRequest();
		$request->init(null);
		self::assertEquals('/demos/personal/index.php', $request->getApplicationUrl());
	}
	
	public function testGetAbsoluteApplicationUrl() {
		$request = new THttpRequest();
		$request->init(null);
		self::assertEquals('http://localhost/demos/personal/index.php', $request->getAbsoluteApplicationUrl());
	}

	public function testGetApplicationFilePath() {
		$request = new THttpRequest();
		$request->init(null);
		self::assertEquals(__FILE__, $request->getApplicationFilePath());
	}

	public function testGetServerName() {
		$request = new THttpRequest();
		$request->init(null);
		self::assertEquals('localhost', $request->getServerName());
	}

	public function testGetServerPort() {
		$request = new THttpRequest();
		$request->init(null);
		self::assertEquals('80', $request->getServerPort());
	}

	public function testGetUrlReferrer() {
		$request = new THttpRequest();
		$request->init(null);
		self::assertEquals('http://www.pradosoft.com', $request->getUrlReferrer());
	}

	public function testGetBrowser() {
		/*$request = new THttpRequest();
		$request->init(null);
		// Reset UserAgent, because constructor of THttpRequest unset it if called from cli !
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3';
		try {
			$browser=$request->getBrowser();
			self::assertType ('array', $browser);
			self::assertEquals('Firefox', $browser['browser']);
			self::assertEquals('2.0.0.3', $browser['version']);
		} catch (TPhpErrorException $e) {
				// If not supported, skip test
				if (strstr($e->getMessage(),'browscap ini directive not set')) 
					self::markTestSkipped('browscap ini directive not set in php.ini');
				else
					self::fail ('Exception raised : '.$e->getMessage());
		}*/
		throw new PHPUnit_Framework_IncompleteTestError();
	}

	public function testGetUserAgent() {
		$request = new THttpRequest();
		self::assertEquals('Mozilla/5.0 (Macintosh; U; Intel Mac OS X; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3', $request->getUserAgent());
	}

	public function testGetUserHostAddress() {
		$request = new THttpRequest();
		$request->init(null);
		self::assertEquals('127.0.0.1', $request->getUserHostAddress());
	}

	public function testGetUserHost() {
		$request = new THttpRequest();
		$request->init(null);
		self::assertEquals('localhost', $request->getUserHost());
	}

  public function testGetAcceptTypes() {
	$request = new THttpRequest();
	$request->init(null);
	self::assertEquals('text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5', $request->getAcceptTypes());
  }

  public function testGetUserLanguages() {
    $request = new THttpRequest();
    $request->init(null);
    // Browser sent fr,en-us;q=0.8,fr-fr;q=0.5,en;q=0.3
	// that means that browser want fr (1) first, next en-us (0.8), then fr-fr(0.5)n and last en (0.3)
	// So, we expect method to return an array with these languages, and this order 
    $acceptLanguages=array ('fr', 'en-us','fr-fr','en');
    self::assertEquals($acceptLanguages, $request->getUserLanguages());
  }

  public function testSetEnableCookieValidation() {
    $request = new THttpRequest();
    $request->init(null);
    $request->setEnableCookieValidation (true);
    self::assertEquals(true, $request->getEnableCookieValidation());
  }

  public function testGetCookies() {
  	$request = new THttpRequest ();
  	$request->init (null);
  	$request->setEnableCookieValidation (false);
  	$cookies=$request->getCookies();
  	self::assertType('THttpCookieCollection', $cookies);
  	self::assertEquals('0123456789abcdef', $cookies->itemAt('phpsessid')->getValue());
  	$request = null;
  	
  	// Test with cookie validation
	$security=new TSecurityManager();
	self::$app->setModule ('security', $security);
	$_COOKIE['phpsessid']=$security->hashData('0123456789abcdef');
	$request = new THttpRequest ();
  	$request->init (null);
  	$request->setEnableCookieValidation (true);
  	$cookies=$request->getCookies();
  	self::assertType('THttpCookieCollection', $cookies);
  	self::assertEquals('0123456789abcdef', $cookies->itemAt('phpsessid')->getValue());
  }

  public function testGetUploadedFiles() {
    $request = new THttpRequest ();
  	$request->init (null);
  	self::assertEquals($_FILES, $request->getUploadedFiles());
  }

  public function testGetServerVariables() {
    $request = new THttpRequest ();
  	$request->init (null);
  	self::assertEquals($_SERVER, $request->getServerVariables());
  }

  public function testGetEnvironmentVariables() {
    $request = new THttpRequest ();
  	$request->init (null);
  	self::assertEquals($_ENV, $request->getEnvironmentVariables());
  }

  public function testConstructUrl() {
    $request = new THttpRequest ();
  	$request->init (null);
  	// Try to construct an url to the pageservice with some parameters
  	$url=$request->constructURL('page','Home',array('param1'=>'value1','param2'=>'value2'), true);
  	self::assertEquals('/demos/personal/index.php?page=Home&amp;param1=value1&amp;param2=value2', $url);
  	// Try without encode &
	$url=$request->constructURL('page','Home',array('param1'=>'value1','param2'=>'value2'), false);	
	self::assertEquals('/demos/personal/index.php?page=Home&param1=value1&param2=value2', $url);
  }

  public function testSetServiceID() {
    $request = new THttpRequest ();
    $request->setServiceId('page');
    self::assertEquals('page', $request->getServiceId());
  }

  public function testGetIterator() {
    $request = new THttpRequest ();
    $request->init(null);
    self::assertType ('TMapIterator', $request->getIterator());
  }

  public function testGetCount() {
    $request = new THttpRequest ();
    $request->init(null);
    // Simulate a request with just a service
	$_GET['page']='Home';
    $request->resolveRequest(array('page'));
    // Should return 1 (service param, and no get param)
	self::assertEquals(1, $request->getCount());
	self::assertEquals(1, $request->count());

  }

  public function testGetKeys() {
    $request = new THttpRequest ();
    $request->init(null);
    // Simulate a request with just a service
	$_GET['page']='Home';
    $request->resolveRequest(array('page'));
	self::assertEquals(array('page'), $request->getKeys());
  }

  public function testItemAt() {
    $request = new THttpRequest ();
    $request->init(null);
    // Simulate a request with just a service
	$_GET['page']='Home';
    $request->resolveRequest(array('page'));
	self::assertEquals('Home', $request->itemAt('page'));
  }

  public function testAdd() {
    $request = new THttpRequest ();
    $request->init(null);
    // Simulate a request with just a service
	$_GET['page']='Home';
	$request->resolveRequest(array('page'));
	$request->Add('param1','value1');
	self::assertEquals('value1', $request->itemAt('param1'));
  }

  public function testRemove() {
    $request = new THttpRequest ();
    $request->init(null);
    // Simulate a request with just a service
	$_GET['page']='Home';
	$request->resolveRequest(array('page'));
	// Remove an unknow key
	self::assertNull($request->remove('param1','value1'));
	// Remove a key
	self::assertEquals('Home', $request->remove('page'));
  }

  public function testContains() {
    $request = new THttpRequest ();
    $request->init(null);
    // Simulate a request with just a service
	$_GET['page']='Home';
	$request->resolveRequest(array('page'));
	self::assertTrue($request->contains('page'));
	self::assertFalse($request->contains('param'));
  }
  
  public function testClear() {
  	$request = new THttpRequest ();
    $request->init(null);
    // Simulate a request with just a service
	$_GET['page']='Home';
	$request->resolveRequest(array('page'));	
	$request->clear();
	self::assertEquals(0,$request->getCount());
  }

  public function testToArray() {
    $request = new THttpRequest ();
    $request->init(null);
    // Simulate a request with just a service
	$_GET['page']='Home';
	$request->resolveRequest(array('page'));
	self::assertEquals(array ('page'=>'Home'), $request->toArray());
  }

  public function testOffsetExists() {
    $request = new THttpRequest ();
    $request->init (null);
    // should not exists
    self::assertFalse($request->offsetExists(0));
  }

  public function testOffsetGet() {
    $request = new THttpRequest ();
    $request->init (null);
    // should not exists
    self::assertNull($request->offsetGet(0));
  }

  public function testOffsetSet() {
    $request = new THttpRequest ();
    $request->init (null);
    $request->offsetSet(0, 'test');
    // should not exists
    self::assertEquals('test',$request->offsetGet(0));
  }

  public function testOffsetUnset() {
    $request = new THttpRequest ();
    $request->init (null);
    $request->offsetSet(0, 'test');
    // Count should be 1
    self::assertEquals (1, $request->count());
    $request->offsetUnset(0);
    // Now, count should be zero
    self::assertEquals (0, $request->count());
  }
  
  public function testGetSetID() {
  	$request=new THttpRequest();
  	$request->init(null);
  	$request->setID('testId');
  	self::assertEquals('testId', $request->getID());
  }
  
  public function testGetSetUrlParamSeparator() {
  	$request=new THttpRequest();
  	$request->init(null);
  	// Try an invalid separator
	try {
		$request->setUrlParamSeparator('&&');
		self::fail('httprequest_separator_invalid exception not thrown');
	} catch (TInvalidDataValueException $e) {
	}
	// Try valid one
	$request->setUrlParamSeparator('&');
	self::assertEquals('&', $request->getUrlParamSeparator());
  }
  
  public function testGetServiceParameter() {
  	$request=new THttpRequest();
  	$request->init(null);
  	$_GET['page']='Home';
	$request->resolveRequest(array('page'));
	self::assertEquals('Home', $request->getServiceParameter());
  }
  
  public function testGetRequestResolved() {
  	$request=new THttpRequest();
  	$request->init(null);
  	self::assertFalse($request->getRequestResolved());
  	$_GET['page']='Home';
	$request->resolveRequest(array('page'));
	self::assertTrue($request->getRequestResolved());	
  }
  
}
?>