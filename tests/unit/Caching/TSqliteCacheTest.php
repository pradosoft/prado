<?php
require_once dirname(__FILE__).'/../phpunit.php';

Prado::using('System.Caching.TSqliteCache');

/**
 * @package System.Caching
 */
class TSqliteCacheTest extends PHPUnit_Framework_TestCase {

	protected static $app = null;
	protected static $cache = null;

	protected function setUp() {
		if(!extension_loaded('sqlite')) {
			self::markTestSkipped('The SQLite extension is not available');
		} else {
			if(self::$app === null) {
				
				$basePath = dirname(__FILE__).'/mockapp';
				$runtimePath = $basePath.'/runtime';
				if(!is_writable($runtimePath)) {
					self::markTestSkipped("'$runtimePath' is writable");
				}
				self::$app = new TApplication($basePath);
				self::$cache = new TSqliteCache();
				self::$cache->init(null);
			}

		}
	}

	protected function tearDown() {
		/*Prado::setApplication(null);
		self::$cache = null;*/
	}

	public function testInit() {
		throw new PHPUnit_Framework_IncompleteTestError();
	}
	
	public function testPrimaryCache() {
		self::$cache->PrimaryCache = true;
		self::assertEquals(true, self::$cache->PrimaryCache);
		self::$cache->PrimaryCache = false;
		self::assertEquals(false, self::$cache->PrimaryCache);
	}
	
	public function testKeyPrefix() {
		self::$cache->KeyPrefix = 'prefix';
		self::assertEquals('prefix', self::$cache->KeyPrefix);
	}
	
	public function testDbFile() {
		self::assertEquals('sqlite.cache', basename(self::$cache->DbFile));
	}
	
	public function testSetAndGet() {
		self::$cache->set('key', 'value');
		self::assertEquals('value', self::$cache->get('key'));
	}
	
	public function testAdd() {
		self::$cache->add('key', 'value');
		self::assertEquals('value', self::$cache->get('key'));
	}
	
	public function testDelete() {
		self::$cache->delete('key');
		self::assertEquals(false, self::$cache->get('key'));
	}
	
	public function testFlush() {
		$this->testAdd();
		self::assertEquals(true, self::$cache->flush());
	}

}

?>
