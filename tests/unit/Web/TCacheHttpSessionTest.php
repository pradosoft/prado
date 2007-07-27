<?php
require_once dirname(__FILE__).'/../phpunit.php';

Prado::using('System.Web.TCacheHttpSession');
Prado::using('System.Caching.TMemCache');

/**
 * @package System.Web
 */
class TCacheHttpSessionTest extends PHPUnit_Framework_TestCase
{
    protected $app = null;
	protected static $cache = null;
	protected static $session = null;
    
    protected function setUp()
    {
		if(!extension_loaded('memcache'))
		{
			self::markTestSkipped('The memcache extension is not available');
		}
		else 
		{
			$basePath = dirname(__FILE__).'/app';
			$runtimePath = $basePath.'/runtime';
			if(!is_writable($runtimePath))
			{
				self::markTestSkipped("'$runtimePath' is not writable");
			}
			$this->app = new TApplication($basePath);
			self::$cache = new TMemCache();
			self::$cache->setKeyPrefix('MyCache');
			self::$cache->init(null);
			$this->app->setModule('MyCache',self::$cache);
		}
	}
	
	protected function tearDown()
    {
		$this->app = null;
		$this->cache = null;
		$this->session = null;
	}
    
    public function testInit()
    {
        $session = new TCacheHttpSession();
        try
        {
            $session->init(null);
            $this->fail("Expected TConfigurationException is not raised");
        }
        catch(TConfigurationException $e)
        {
        }
        unset($session);
        
        $session = new TCacheHttpSession();
        try
        {
            $session->setCacheModuleID('MaiCache');
            $session->init(null);
            $this->fail("Expected TConfigurationException is not raised");
            $session->open();
        }
        catch(TConfigurationException $e)
        {
        }
        unset($session);
        
        self::$session = new TCacheHttpSession();
        try
        {
            self::$session->setCacheModuleID('MyCache');
            self::$session->init(null);                
        }
        catch(TConfigurationException $e)
        {
            $this->fail('TConfigurationException is not expected');
            self::markTestSkipped('Cannot continue this test');
        }
    }
    
    public function testGetCache()
    {
        $cache = self::$session->getCache();
        $this->assertEquals(true, $cache instanceof TMemCache);
    }
    
    public function testCacheModuleID()
    {
        $id = 'value';
        self::$session->setCacheModuleID('value');
        self::assertEquals($id, self::$session->getCacheModuleID());
    }
    
    public function testKeyPrefix()
    {
        $id = 'value';
        self::$session->setKeyPrefix('value');
        self::assertEquals($id, self::$session->getKeyPrefix());
    }
    
    public function testSetAndGet()
    {
        self::$session['key'] = 'value';
		self::assertEquals('value', self::$session['key']);
	}
	
	public function testAdd()
	{
		self::$session->add('anotherkey', 'value');
		self::assertEquals('value', self::$session['anotherkey']);
	}
	
	public function testRemove()
	{
		self::$session->remove('key');
		self::assertEquals(false, self::$session['key']);
	}
	
	public function testDestroyAndIsStarted()
	{
		$this->testSetAndGet();
		self::$session->destroy();
		self::assertEquals(false, self::$session->getIsStarted());
	}
}
?>