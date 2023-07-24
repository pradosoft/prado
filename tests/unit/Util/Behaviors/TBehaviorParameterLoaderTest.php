<?php

use Prado\Util\Behaviors\TBehaviorParameterLoader;
use Prado\Util\TBehavior;
use Prado\Web\UI\TPage;

class TestModuleBehaviorLoader1 extends TBehavior
{
	private $_propertyA = 'default';
	public $config = null;
	
	public const NULL_CONFIG = "null-config";
	
	public function init($config)
	{
		if ($config === null)
			$config = self::NULL_CONFIG;
		$this->config = $config;
	}
	
	public function getPropertyA()
	{
		return $this->_propertyA;
	}
	public function setPropertyA($value)
	{
		$this->_propertyA = $value;
	}
}
class TestModuleBehaviorLoader2 extends TestModuleBehaviorLoader1
{
}
class TestModuleBehaviorLoader3 extends TestModuleBehaviorLoader2
{
}
class TestModuleBehaviorLoader4 extends TestModuleBehaviorLoader3
{
}

class TestModuleLoaderBM extends TModule
{
}

class TestModuleLoaderBM2 extends TModule
{
}


class TBehaviorParameterLoaderTest extends PHPUnit\Framework\TestCase
{
	protected $obj;

	protected function setUp(): void
	{
		$this->obj = new TBehaviorParameterLoader();
	}

	protected function tearDown(): void
	{
		$this->obj->reset();
	}

	public function testConstruct()
	{
		$this->assertInstanceOf(TBehaviorParameterLoader::class, $this->obj);
	}

	public function testDyInit()
	{
		$app = Prado::getApplication();
		$mod = new TestModuleBM2();
		$mod->init(null);
		$app->setModule('BPLoaderModuleTest', $mod);
		$modB = new TestModuleBM();
		$modB->init(null);
		$app->setModule('modB2', $modB);
		
		try {
			$this->obj->BehaviorName = 'test';
			$this->obj->BehaviorClass = 'TestModuleBehaviorLoader1';
			$this->obj->AttachTo = 'module:BPLoaderModuleTest';
			$this->obj->dyInit(null);
		} catch (Exception $e) {
			$this->fail($e::class ." should not have been raised on init(null)\n" . $e->__toString());
		}
		$this->obj->reset();
		try {
			$this->obj->BehaviorName = 'test';
			$this->obj->BehaviorClass = 'TestModuleBehaviorLoader1';
			$this->obj->AttachToClass = 'TPage';
			$this->obj->dyInit(null);
		} catch (Exception $e) {
			$this->fail($e::class ." should not have been raised on init(null)\n" . $e->__toString());
		}
		$this->obj->reset();
		
		try {
			$this->obj->BehaviorName = 'test';
			$this->obj->AttachTo = 'module:module';
			$this->obj->dyInit(null);
			$this->fail($e::class ." should have been raised on init(null) without a BehaviorClass");
		} catch (Exception $e) {
		}
		$this->obj->reset();
		try {
			$this->obj->BehaviorName = 'test';
			$this->obj->BehaviorClass = 'TestModuleBehaviorLoader1';
			$this->obj->dyInit(null);
			$this->fail($e::class ." should have been raised on init(null) without an AttachTo/Class");
		} catch (Exception $e) {
		}
		$this->obj->reset();
		
		{ // parameter loader
			$this->assertNull($app->asa('testBehavior1'));
			$this->assertEquals(0, count($app->onBeginRequest));
			
			$this->obj->BehaviorName = 'testBehavior1';
			$this->obj->BehaviorClass = 'TestModuleBehaviorLoader1';
			$this->obj->AttachTo = 'Application';
			$this->obj->propertya = 'value1';
			$this->obj->dyInit(['data123']);
			
			//Check was App behavior installed
			$this->assertInstanceOf('TestModuleBehaviorLoader1', $app->asa('testBehavior1'));
			$this->assertEquals('value1', $app->asa('testBehavior1')->propertyA);
			$this->assertEquals(['data123'], $app->asa('testBehavior1')->config);
			$app->detachBehavior('testBehavior1');
		}
		$mod->unlisten();
		$modB->unlisten();
	}
	
	public function testAttachModuleBehaviors()
	{
		$behaviorName = 'testBehavior';
		$app = Prado::getApplication();
		$this->obj->BehaviorName = $behaviorName;
		$this->obj->BehaviorClass = 'TestModuleBehaviorLoader1';
		$this->obj->AttachTo = 'module:TBPLoaderTest';
		$this->obj->propertya = 'value1';
		$this->assertEquals(0, count($app->onInitComplete));
		
		$app = Prado::getApplication();
		$module = new TestModuleLoaderBM;
		$module->init(null);
		$app->setModule('TBPLoaderTest', $module);
		$this->assertNull($module->asa($behaviorName));
		
		$this->obj->dyInit(null);
		
		$this->obj->attachModuleBehaviors(null, null);
		$this->assertEquals(1, count($app->onInitComplete));
		
		$this->assertInstanceOf('TestModuleBehaviorLoader1', $module->asa($behaviorName));
		$this->assertEquals('value1', $module->asa($behaviorName)->PropertyA);
		$module->detachBehavior($behaviorName);
		$module->unlisten();
	}
	
	public function testAttachModuleBehaviors_anonymous()
	{
		$app = Prado::getApplication();
		$this->obj->BehaviorName = null;
		$this->obj->BehaviorClass = 'TestModuleBehaviorLoader1';
		$this->obj->AttachTo = 'module:TBPLoaderTest';
		$this->obj->propertya = 'value1';
		$this->assertEquals(0, count($app->onInitComplete));
		
		$module = $app->getModule('TBPLoaderTest');
		$this->assertNull($module->asa('testBehavior'));
		$this->assertNull($module->asa(0));
		
		$this->obj->dyInit(null);
		
		$this->obj->attachModuleBehaviors(null, null);
		$this->assertEquals(1, count($app->onInitComplete));
		
		$this->assertInstanceOf('TestModuleBehaviorLoader1', $module->asa(0));
		$this->assertEquals('value1', $module->asa(0)->PropertyA);
		$module->detachBehavior(0);
	}
	
	public function testAttachTPageBehaviors()
	{
		$behaviorName = 'testBehavior';
		$app = Prado::getApplication();
		$this->obj->BehaviorName = $behaviorName;
		$this->obj->BehaviorClass = 'TestModuleBehaviorLoader2';
		$this->obj->AttachTo = 'page';
		$this->obj->propertya = 'value';
		$this->assertEquals(0, count($app->onBeginRequest));
		
		$this->obj->dyInit(null);
		
		$this->assertEquals(1, count($app->onBeginRequest));
		$page = new TPage;
		$this->obj->attachTPageBehaviors(null, $page);
		
		$this->assertInstanceOf('TestModuleBehaviorLoader2', $page->asa($behaviorName));
		$this->assertEquals('value', $page->asa($behaviorName)->PropertyA);
		$page->detachBehavior($behaviorName);
		$page->unlisten();
	}
	
	public function testAttachTPageBehaviors_anonymous()
	{
		$app = Prado::getApplication();
		$this->obj->BehaviorName = null;
		$this->obj->BehaviorClass = 'TestModuleBehaviorLoader2';
		$this->obj->AttachTo = 'page';
		$this->obj->propertya = 'value';
		$this->assertEquals(0, count($app->onBeginRequest));
		
		$this->obj->dyInit(null);
		
		$this->assertEquals(1, count($app->onBeginRequest));
		$page = new TPage;
		$this->obj->attachTPageBehaviors(null, $page);
		
		$this->assertInstanceOf('TestModuleBehaviorLoader2', $page->asa(0));
		$this->assertEquals('value', $page->asa(0)->PropertyA);
		$page->detachBehavior(0);
		$page->unlisten();
	}
	
	public function testBehaviorName()
	{
		$this->assertNull($this->obj->getBehaviorName());
		$name = 'behaviorName123';
		$this->obj->setBehaviorName(null);
		$this->assertNull($this->obj->getBehaviorName());
		$this->obj->setBehaviorName(0);
		$this->assertNull($this->obj->getBehaviorName());
		$this->obj->setBehaviorName(1);
		$this->assertNull($this->obj->getBehaviorName());
		$this->obj->setBehaviorName(1000);
		$this->assertNull($this->obj->getBehaviorName());
		
		$this->obj->setBehaviorName($name);
		$this->assertEquals($name, $this->obj->getBehaviorName());
	}
	
	public function testBehaviorClass()
	{
		$this->assertNull($this->obj->getBehaviorClass());
		$class = 'TestModuleBehaviorLoader3';
		$this->obj->setBehaviorClass($class);
		$this->assertEquals($class, $this->obj->getBehaviorClass());
	}
	
	public function testBehaviorPriority()
	{
		$this->assertNull($this->obj->getPriority());
		$priority = 20;
		$this->obj->setPriority($priority);
		$this->assertEquals($priority, $this->obj->getPriority());
	}
	
	public function testAttachTo()
	{
		$this->assertNull($this->obj->getAttachTo());
		$attachTo = 'Application';
		$this->obj->setAttachTo($attachTo);
		$this->assertEquals($attachTo, $this->obj->getAttachTo());
		$attachTo = 'Page';
		$this->obj->setAttachTo($attachTo);
		$this->assertEquals($attachTo, $this->obj->getAttachTo());
		$attachTo = 'module:auth';
		$this->obj->setAttachTo($attachTo);
		$this->assertEquals($attachTo, $this->obj->getAttachTo());
	}
	
	public function testAttachToClass()
	{
		$this->assertNull($this->obj->getAttachToClass());
		$attachToClass = 'TApplication';
		$this->obj->setAttachToClass($attachToClass);
		$this->assertEquals($attachToClass, $this->obj->getAttachToClass());
		$this->obj->setAttachToClass(null);
	}
	
	public function testProperties()
	{
		$this->assertEquals([], $this->obj->getProperties());
		$this->obj->PropertyA = 'valueA';
		$this->assertEquals(['PropertyA' => 'valueA'], $this->obj->getProperties());
	}
	
}
