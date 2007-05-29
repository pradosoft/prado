<?php
require_once dirname(__FILE__).'/../phpunit.php';

Prado::using('System.Web.THttpRequest');

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
		
		if(self::$app === null) {
			self::$app = new TApplication(dirname(__FILE__).'/app');
		}
	}
	
	public function testInit() {
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
	}

	public function testGetUrlManager() {
		$request = new THttpRequest();
		$request->init(null);
		self::assertEquals(null, $request->getUrlManager());
	}
	
	public function testSetUrlManager() {
		throw new PHPUnit_Framework_IncompleteTestError();
	}

	public function testSetUrlFormat() {
		throw new PHPUnit_Framework_IncompleteTestError();
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
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testGetUserLanguages() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testSetEnableCookieValidation() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testGetCookies() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testGetUploadedFiles() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testGetServerVariables() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testGetEnvironmentVariables() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testConstructUrl() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testGetAvailableServices() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testSetAvailableServices() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testSetServiceID() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testGetIterator() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testGetCount() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testGetKeys() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testItemAt() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testAdd() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testRemove() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testContains() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testToArray() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testOffsetExists() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testOffsetGet() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testOffsetSet() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testOffsetUnset() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }
}
?>