<?php

use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Util\TBehaviorsModule;
use Prado\Util\TBehavior;
use Prado\Web\UI\TPage;

class TestModuleBehavior1 extends TBehavior
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
class TestModuleClassBehavior1 extends TClassBehavior
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
class TestModuleBehavior2 extends TestModuleBehavior1
{
}
class TestModuleBehavior3 extends TestModuleBehavior2
{
}
class TestModuleBehavior4 extends TestModuleBehavior3
{
}
class TestModuleBehavior5 extends TestModuleBehavior4
{
}

class TestModuleBM extends TModule
{
}

class TestModuleBM2 extends TModule
{
}

class TBehaviorsModuleTest extends PHPUnit\Framework\TestCase
{
	protected $obj;

	protected function setUp(): void
	{
		$this->obj = new TBehaviorsModule();
	}

	protected function tearDown(): void
	{
		Prado::getApplication()->onBeginRequest->clear();
	}

	public function testConstruct()
	{
		$this->assertInstanceOf('\\Prado\\Util\\TBehaviorsModule', $this->obj);
	}

	public function testInit()
	{
		try {
			$this->obj->init(null);
		} catch (Exception $e) {
			$this->fail(get_class($e) .' should not have been raised on init(null)');
		}
		$behaviors = '<module id="bmod">
		<behavior name="testBehavior5" class="TestModuleBehavior5" attachto="behavior:testBehavior1" Priority="19" PropertyA="value5"/>
		<behavior name="testBehavior6" class="TestModuleBehavior5" attachto="behavior:testBehavior3" Priority="19" PropertyA="value6"/>
		<behavior name="testBehavior1" class="TestModuleBehavior1" attachto="Application" Priority="1" PropertyA="value1"/>
		<behavior name="testBehavior2" class="TestModuleBehavior2" attachto="Page" Priority="2" PropertyA="value2"/>
		<behavior name="testBehavior3" class="TestModuleClassBehavior1" attachtoclass="TestModuleBM2" Priority="3" PropertyA="value3"/>
		<behavior name="testBehavior4" class="TestModuleBehavior4" attachto="module:modB" Priority="4" PropertyA="value4"/>
			</module>';
		$xmldoc = new TXmlDocument('1.0', 'utf-8');
		$xmldoc->loadFromString($behaviors);
		
		$app = Prado::getApplication();
		$log = new TestModuleBM2();
		$log->init(null);
		$app->setModule('logger', $log);
		$modB = new TestModuleBM();
		$modB->init(null);
		$app->setModule('modB', $modB);
		
		{ // XML
			$this->assertNull($app->asa('testBehavior1'));
			$this->assertNull($log->asa('testBehavior3'));
			$this->assertNull($modB->asa('testBehavior4'));
			$this->assertEquals(0, count($app->onBeginRequest));
			
			$this->obj->init($xmldoc);
			
			//Check was App behavior installed
			$this->assertInstanceOf('TestModuleBehavior1', $b1 = $app->asa('testBehavior1'));
			$this->assertEquals('value1', $app->asa('testBehavior1')->propertyA);
			
			$this->assertInstanceOf('TestModuleBehavior5', $b5 = $b1->asa('testBehavior5'));
			$this->assertEquals('value5', $b1->asa('testBehavior5')->propertyA);
			
			
			// Check that the Page behavhiors will be installed onBeginRequest->OnPreRunPage
			$this->assertEquals(1, count($app->onBeginRequest));
			$app->onBeginRequest->clear();
			
			//This behavior is added via class behaviors for already instanced objects.
			$this->assertInstanceOf('TestModuleClassBehavior1', $b3 = $log->asa('testBehavior3'));
			$this->assertEquals('value3', $log->asa('testBehavior3')->propertyA);
			$this->assertEquals('value6', $b3->asa('testBehavior6')->propertyA);
			
			$this->assertInstanceOf('TestModuleBehavior4', $modB->asa('testBehavior4'));
			$this->assertEquals('value4', $modB->asa('testBehavior4')->propertyA);
			$app->detachBehavior('testBehavior1');
			$log->detachClassBehavior('testBehavior3');
			$modB->detachBehavior('testBehavior4');
			$this->assertNull($app->asa('testBehavior1'));
			$this->assertNull($log->asa('testBehavior3'));
			$this->assertNull($modB->asa('testBehavior4'));
		}
		$phpconfig = ['class' => 'TBehaviorsModule', 'properties' => [], 'behaviors' => [
			['name' => 'testBehavior1', 'class' => 'TestModuleBehavior1', 'attachto' => 'Application', 'priority' => 1, 'propertya' => 'value1'],
			['name' => 'testBehavior2', 'class' => 'TestModuleBehavior2', 'attachto' => 'Page', 'priority' => '2', 'propertya' => 'value2'],
			['name' => 'testBehavior3', 'class' => 'TestModuleBehavior3', 'attachtoclass' => 'TestModuleBM2', 'priority' => 3.0, 'propertya' => 'value3'],
			['name' => 'testBehavior4', 'class' => 'TestModuleBehavior4', 'attachto' => 'module:modB', 'priority' => 4, 'propertya' => 'value4']
		]];
		{ // PHP
			$this->obj->init($phpconfig);
			$this->assertInstanceOf('TestModuleBehavior1', $app->asa('testBehavior1'));
			$this->assertEquals('value1', $app->asa('testBehavior1')->propertyA);
			$this->assertEquals(1, count($app->onBeginRequest));
			$app->onBeginRequest->clear();
			
			//This behavior is added via class behaviors for already instanced objects.
			$this->assertInstanceOf('TestModuleBehavior3', $log->asa('testBehavior3'));
			$this->assertEquals('value3', $log->asa('testBehavior3')->propertyA);
			
			$this->assertInstanceOf('TestModuleBehavior4', $modB->asa('testBehavior4'));
			$this->assertEquals('value4', $modB->asa('testBehavior4')->propertyA);
			$app->detachBehavior('testBehavior1');
			$log->detachClassBehavior('testBehavior3');
			$modB->detachBehavior('testBehavior4');
			$this->assertNull($app->asa('testBehavior1'));
			$this->assertNull($log->asa('testBehavior3'));
			$this->assertNull($modB->asa('testBehavior4'));
		}
		{ // Additional Behaviors
			$this->obj->setAdditionalBehaviors($phpconfig['behaviors']);
			$this->obj->init(null);
			$this->assertInstanceOf('TestModuleBehavior1', $app->asa('testBehavior1'));
			$this->assertEquals('value1', $app->asa('testBehavior1')->propertyA);
			$this->assertEquals(1, count($app->onBeginRequest));
			$app->onBeginRequest->clear();
			
			//This behavior is added via class behaviors for already instanced objects.
			$this->assertInstanceOf('TestModuleBehavior3', $log->asa('testBehavior3'));
			$this->assertEquals('value3', $log->asa('testBehavior3')->propertyA);
			
			$this->assertInstanceOf('TestModuleBehavior4', $modB->asa('testBehavior4'));
			$this->assertEquals('value4', $modB->asa('testBehavior4')->propertyA);
			$app->detachBehavior('testBehavior1');
			$log->detachClassBehavior('testBehavior3');
			$modB->detachBehavior('testBehavior4');
			$this->assertNull($app->asa('testBehavior1'));
			$this->assertNull($log->asa('testBehavior3'));
			$this->assertNull($modB->asa('testBehavior4'));
			$this->obj->setAdditionalBehaviors(null);
		}
		
	}

	public function testAttachTPageBehaviors()
	{
		$this->obj->init(['behaviors' => [['name' => 'testBehavior', 'class' => 'TestModuleBehavior1', 'attachto' => 'page', 'priority' => 12, 'propertya' => 'value']] ]);
		$page = new TPage;
		$this->obj->attachTPageBehaviors(null, $page);
		
		$this->assertInstanceOf('TestModuleBehavior1', $page->asa('testBehavior'));
		$this->assertEquals('value', $page->asa('testBehavior')->PropertyA);
	}
	
	public function testAdditionalBehaviors()
	{
		//is zero array
		$this->assertEquals([], $this->obj->getAdditionalBehaviors());
		
		//null is still zero array
		$this->obj->setAdditionalBehaviors(null);
		$this->assertEquals([], $this->obj->getAdditionalBehaviors());
		
		//invalid has an error
		try {
			$this->obj->setAdditionalBehaviors(99);
			self::fail('TInvalidDataTypeException not raised when setting an invalid value');
		} catch(TInvalidDataTypeException $e) {}
		
		// zero array is a zero array
		$this->obj->setAdditionalBehaviors([]);
		$this->assertEquals([], $this->obj->getAdditionalBehaviors());
		
		//Behavior becomes array of behaviors.
		$behaviors = ['name' => 'testBehavior', 'class' => 'TestModuleBehavior1', 'attachto' => 'Application', 'priority' => 12, 'propertya' => 'value'];
		$this->obj->setAdditionalBehaviors($behaviors);
		$this->assertEquals([$behaviors], $this->obj->getAdditionalBehaviors());
		
		$behaviors = [$behaviors];
		
		// array of behaviors is an array of behaviors
		$this->obj->setAdditionalBehaviors($behaviors);
		$this->assertEquals($behaviors, $this->obj->getAdditionalBehaviors());
		
		// serialized array of behaviors is an array of behaviors
		$this->obj->setAdditionalBehaviors(serialize($behaviors));
		$this->assertEquals($behaviors, $this->obj->getAdditionalBehaviors());
		
		// serialized array of behaviors is an array of behaviors
		$this->obj->setAdditionalBehaviors(json_encode($behaviors));
		$this->assertEquals($behaviors, $this->obj->getAdditionalBehaviors());
		
		// serialized array of behaviors is an array of behaviors
		$this->obj->setAdditionalBehaviors('<module id="bmod"><behavior name="testBehavior" class="TestModuleBehavior1" attachto="Application" Priority="12" PropertyA="value2"/></module>');
		$this->assertInstanceOf('\\Prado\\Xml\\TXmlDocument', $this->obj->getAdditionalBehaviors());
		
	}
}
