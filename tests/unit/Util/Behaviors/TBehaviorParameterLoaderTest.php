<?php

use Prado\Util\Behaviors\TBehaviorParameterLoader;
use Prado\Util\TBehavior;
use Prado\Web\UI\TPage;

class TestModuleBehaviorLoader1 extends TBehavior
{
	private $_propertyA = 'default';
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
		$this->assertInstanceOf('\\Prado\\Util\\Behaviors\\TBehaviorParameterLoader', $this->obj);
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
			$this->fail(get_class($e) ." should not have been raised on init(null)\n" . $e->__toString());
		}
		$this->obj->reset();
		try {
			$this->obj->BehaviorName = 'test';
			$this->obj->BehaviorClass = 'TestModuleBehaviorLoader1';
			$this->obj->AttachToClass = 'TPage';
			$this->obj->dyInit(null);
		} catch (Exception $e) {
			$this->fail(get_class($e) ." should not have been raised on init(null)\n" . $e->__toString());
		}
		$this->obj->reset();
		try {
			$this->obj->BehaviorClass = 'TestModuleBehaviorLoader1';
			$this->obj->AttachTo = 'module:module';
			$this->obj->dyInit(null);
			$this->fail(get_class($e) ." should have been raised on init(null) without a BehaviorName");
		} catch (Exception $e) {
		}
		$this->obj->reset();
		try {
			$this->obj->BehaviorName = 'test';
			$this->obj->AttachTo = 'module:module';
			$this->obj->dyInit(null);
			$this->fail(get_class($e) ." should have been raised on init(null) without a BehaviorClass");
		} catch (Exception $e) {
		}
		$this->obj->reset();
		try {
			$this->obj->BehaviorName = 'test';
			$this->obj->BehaviorClass = 'TestModuleBehaviorLoader1';
			$this->obj->dyInit(null);
			$this->fail(get_class($e) ." should have been raised on init(null) without an AttachTo/Class");
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
			$this->obj->dyInit(null);
			
			//Check was App behavior installed
			$this->assertInstanceOf('TestModuleBehaviorLoader1', $app->asa('testBehavior1'));
			$this->assertEquals('value1', $app->asa('testBehavior1')->propertyA);
			$app->detachBehavior('testBehavior1');
		}
	}
	
	public function testAttachModuleBehaviors()
	{
		$app = Prado::getApplication();
		$this->obj->BehaviorName = 'testBehavior';
		$this->obj->BehaviorClass = 'TestModuleBehaviorLoader1';
		$this->obj->AttachTo = 'module:TBPLoaderTest';
		$this->obj->propertya = 'value1';
		$this->assertEquals(0, count($app->onInitComplete));
		
		$app = Prado::getApplication();
		$module = new TestModuleLoaderBM;
		$module->init(null);
		$app->setModule('TBPLoaderTest', $module);
		$this->assertNull($module->asa('testBehavior'));
		
		$this->obj->dyInit(null);
		
		$this->obj->attachModuleBehaviors(null, null);
		$this->assertEquals(1, count($app->onInitComplete));
		
		$this->assertInstanceOf('TestModuleBehaviorLoader1', $module->asa('testBehavior'));
		$this->assertEquals('value1', $module->asa('testBehavior')->PropertyA);
	}
	
	public function testAttachTPageBehaviors()
	{
		$app = Prado::getApplication();
		$this->obj->BehaviorName = 'testBehavior';
		$this->obj->BehaviorClass = 'TestModuleBehaviorLoader2';
		$this->obj->AttachTo = 'page';
		$this->obj->propertya = 'value';
		$this->assertEquals(0, count($app->onBeginRequest));
		
		$this->obj->dyInit(null);
		
		$this->assertEquals(1, count($app->onBeginRequest));
		$page = new TPage;
		$this->obj->attachTPageBehaviors(null, $page);
		
		$this->assertInstanceOf('TestModuleBehaviorLoader2', $page->asa('testBehavior'));
		$this->assertEquals('value', $page->asa('testBehavior')->PropertyA);
	}
	
	public function testBehaviorName()
	{
		$this->assertNull($this->obj->getBehaviorName());
		$name = 'behaviorName123';
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
