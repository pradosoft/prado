<?php

use Prado\Web\Behaviors\TRequestConnectionUpgrade;
use Prado\Web\THttpRequestParameter;


class TRequestConnectionUpgradeTest extends PHPUnit\Framework\TestCase
{
	public static $app = null;

	protected function setUp(): void
	{
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
		$_SERVER['HTTP_REFERER'] = 'https://github.com/pradosoft/prado';
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3';
		$_SERVER['REMOTE_HOST'] = 'localhost';
		$_SERVER['HTTP_ACCEPT'] = 'text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5';
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr,en-us;q=0.8,fr-fr;q=0.5,en;q=0.3';
		$_SERVER['HTTP_ACCEPT_ENCODING'] = 'gzip,deflate';
		$_SERVER['HTTP_ACCEPT_CHARSET'] = 'ISO-8859-1,utf-8;q=0.7,*;q=0.7';
		$_SERVER['HTTP_CONNECTION'] = 'Upgrade';
		$_SERVER['HTTP_UPGRADE'] = 'websocket';
		
		$_COOKIE['phpsessid'] = '0123456789abcdef';
		
		$_FILES['userfile'] = ['name' => 'test.jpg', 'type' => 'image/jpg', 'size' => 10240, 'tmp_name' => 'tmpXXAZECZ', 'error' => 0];
		if (self::$app === null) {
			self::$app = new TApplication(__DIR__ . '/../app');
		}
	}

	protected function tearDown(): void
	{
		unset($_SERVER['HTTP_CONNECTION']);
		unset($_SERVER['HTTP_UPGRADE']);
	}

	public function testAttachedBehavior()
	{
		$request = new THttpRequest();
		$request->init(null);
		
		$_GET['page'] = 'Home';
		self::assertEquals('page', $request->resolveRequest(['page', 'websocket', 'testService']));
		
		$behaviorName = 'webSocketUpgrader';
		$request->attachBehavior('webSocketUpgrader', TRequestConnectionUpgrade::class);
		self::assertEquals('websocket', $request->resolveRequest(['page', 'websocket', 'testService']));
		$request->detachBehavior($behaviorName);
	}
	
	public function testProcessHeaders()
	{
		$request = new class() {
			public function getHeaders($case) {
				
				$result = [];
				foreach ($_SERVER as $key => $value) {
					if (strncasecmp($key, 'HTTP_', 5) !== 0) {
						continue;
					}
					$key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
					$result[$key] = $value;
				}
				
				if ($case !== null) {
					return array_change_key_case($result, $case);
				}
				
				return $result;
			}	
		};
		
		$upgrader = new TRequestConnectionUpgrade();
		$param = new THttpRequestParameter([], []);
		
		$urlParams = $upgrader->processHeaders($request, $param);
		self::assertTrue(array_key_exists('websocket', $urlParams));
		self::assertFalse(array_key_exists('tcp', $urlParams));
		
		$param = new THttpRequestParameter([], []);
		$_SERVER['HTTP_UPGRADE'] = 'websocket, tcp';
		$urlParams = $upgrader->processHeaders($request, $param);
		self::assertTrue(array_key_exists('websocket', $urlParams));
		self::assertTrue(array_key_exists('tcp', $urlParams));
		
		$param = new THttpRequestParameter([], []);
		$_SERVER['HTTP_CONNECTION'] = 'Keep-Alive';
		self::assertNull($upgrader->processHeaders($request, $param));
		
		$param = new THttpRequestParameter([], []);
		unset($_SERVER['HTTP_CONNECTION']);
		self::assertNull($upgrader->processHeaders($request, $param));
		
		$param = new THttpRequestParameter([], []);
		$_SERVER['HTTP_CONNECTION'] = 'Upgrade';
		unset($_SERVER['HTTP_UPGRADE']);
		self::assertNull($upgrader->processHeaders($request, $param));
			
	}
	
}
