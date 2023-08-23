<?php

use Prado\Collections\TPriorityList;
use Prado\Exceptions\TApplicationException;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Exceptions\TUnknownMethodException;
use Prado\TComponent;
use Prado\TEventResults;
use Prado\Util\IDynamicMethods;
use Prado\Util\IInstanceCheck;
use Prado\Util\TBehavior;
use Prado\Util\TClassBehavior;

trait NewComponentTestTrait {
}

trait UnusedNewComponentTestTrait {
}

class NewComponent extends TComponent
{
	use NewComponentTestTrait;
	
	private $_object;
	private $_text = 'default';
	private $_eventHandled = false;
	private $_return;
	private $_colorattribute;

	public function getAutoGlobalListen()
	{
		return true;
	}

	public function getText()
	{
		return $this->_text;
	}

	public function setText($value)
	{
		$this->_text = $value;
	}

	public function getReadOnlyProperty()
	{
		return 'read only';
	}

	public function getJsReadOnlyJsProperty()
	{
		return 'js read only';
	}

	public function getObject()
	{
		if (!$this->_object) {
			$this->_object = new NewComponent;
			$this->_object->_text = 'object text';
		}
		return $this->_object;
	}

	public function onMyEvent($param)
	{
		$this->raiseEvent('OnMyEvent', $this, $param);
	}

	public function myEventHandler($sender, $param)
	{
		$this->_eventHandled = true;
	}

	public function eventReturnValue($sender, $param)
	{
		return $param->Return;
	}

	public function isEventHandled()
	{
		return $this->_eventHandled;
	}

	public function resetEventHandled()
	{
		$this->_eventHandled = false;
	}
	public function getReturn()
	{
		return $this->_return;
	}
	public function setReturn($return)
	{
		$this->_return = $return;
	}
	public function getjsColorAttribute()
	{
		return $this->_colorattribute;
	}
	public function setjsColorAttribute($colorattribute)
	{
		$this->_colorattribute = $colorattribute;
	}
	
	public $_protectedValue = 'protectedData';
	protected function getProtectedValue()
	{
		return $this->_protectedValue;
	}
	protected function setProtectedValue($value )
	{
		$this->_protectedValue = $value;
	}
}

trait SubNewComponentTestTrait {
}
interface SubNewComponentInterface {
}

class SubNewComponent extends NewComponent implements SubNewComponentInterface
{
	use SubNewComponentTestTrait;
}

class NewComponentBehavior extends TBehavior
{
	public $data = 0;
	public function events()
	{
		return ['onMyEvent' => 'ncBehaviorHandler'];
	}
	
	public function ncBehaviorHandler($sender, $param)
	{
		
	}
}

class NewComponentStaticBehavior extends TBehavior
{
	public static function aStaticMethod(int $value)
	{
		return $value + $value;
	}
}

class NewComponentStaticClassBehavior extends TClassBehavior
{
	public static function aStaticMethod(int $value)
	{
		return $value * $value;
	}
}

class NewComponentNoListen extends NewComponent
{
	// this object does _not_ auto install global listeners during construction
	public function getAutoGlobalListen()
	{
		return false;
	}
}

trait DynamicCatchingTrait {
	public $_variable;
}

class DynamicCatchingComponent extends NewComponentNoListen implements IDynamicMethods
{
	use DynamicCatchingTrait;
	
	public function __dycall($method, $args)
	{
	}
}


class GlobalRaiseComponent extends NewComponent implements IDynamicMethods
{
	private $_callorder = [];

	public function getCallOrders()
	{
		return $this->_callorder;
	}
	public function __dycall($method, $args)
	{
		if (strncasecmp($method, 'fx', 2) !== 0) {
			return;
		}
		$this->_callorder[] = 'fxcall';
	}
	public function fxGlobalListener($sender, $param)
	{
		$this->_callorder[] = 'fxGL';
	}
	public function fxPrimaryGlobalEvent($sender, $param)
	{
		$this->_callorder[] = 'primary';
	}
	public function commonRaiseEventListener($sender, $param)
	{
		$this->_callorder[] = 'com';
	}
	public function postglobalRaiseEventListener($sender, $param)
	{
		$this->_callorder[] = 'postgl';
	}
	public function preglobalRaiseEventListener($sender, $param)
	{
		$this->_callorder[] = 'pregl';
	}
}



class FooClassBehavior extends TClassBehavior
{
	private $_propertyA = 'default';
	private $_baseObject;
	public $_config;
	public const NULL_CONFIG = "null-class-config";
	
	public function init ($config)
	{
		if ($config == null)
			$config = self::NULL_CONFIG;
		$this->_config = $config;
	}
	
	public function faaEverMore($object, $laa, $sol)
	{
		$this->_baseObject = $object;
		return $laa * $sol;
	}
	public function getLastClassObject()
	{
		return $this->_baseObject;
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

class FooFooClassBehavior extends FooClassBehavior
{
	public function faafaaEverMore($object, $laa, $sol)
	{
		return 'ffemResult';
	}
}

class BarClassBehavior extends TClassBehavior
{
	public function moreFunction($object, $laa, $sol)
	{
		return true;
	}
}

class BarClassBehaviorWithEvents extends BarClassBehavior
{
	public function events()
	{
		return ['onMyEvent' => ['barClassEventHandler', function($sender, $param) { return time();}]];
	}
	public function barClassEventHandler($sender, $param)
	{
		
	}
}

interface FooInterface
{
}

class FooBehavior extends TBehavior implements FooInterface
{
	private $_propertyA = 'default';
	
	public $detached = 0;
	
	public function detach($obj) {
		$this->detached++;
		parent::detach($obj);
	}
	
	public function faaEverMore($laa, $sol)
	{
		return true;
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
class FooFooBehavior extends FooBehavior
{
	public function faafaaEverMore($laa, $sol)
	{
		return sqrt($laa * $laa + $sol * $sol);
	}
}
class FooBehaviorWithEvents extends FooBehavior
{
	public function events()
	{
		return ['onMyEvent' => ['fooEventHandler', function($sender, $param) { return time();}] ];
	}
	public function fooEventHandler($sender, $param)
	{
		return true;
	}
}

class FooBarBehavior extends TBehavior
{
	public function moreFunction($laa, $sol)
	{
		return $laa * $sol * $sol;
	}
}

class PreBarBehavior extends TBehavior
{
}

class BarBehavior extends PreBarBehavior implements IInstanceCheck
{
	private $_instanceReturn;

	public function moreFunction($laa, $sol)
	{
		return pow($laa + $sol + 1, 2);
	}

	public function isinstanceof($class, $instance = null)
	{
		return $this->_instanceReturn;
	}
	public function setInstanceReturn($value)
	{
		$this->_instanceReturn = $value;
	}
}
class DynamicCallComponent extends NewComponent implements IDynamicMethods
{
	public function __dycall($method, $args)
	{
		if ($method === 'dyPowerFunction') {
			return pow($args[0], $args[1]);
		}
		if ($method === 'dyDivisionFunction') {
			return $args[0] / $args[1];
		}
		if ($method === 'fxPowerFunction') {
			return 2 * pow($args[0], $args[1]);
		}
		if ($method === 'fxDivisionFunction') {
			return 2 * $args[0] / $args[1];
		}
	}
}



class BehaviorTestBehavior extends TBehavior
{
	private $excitement = 'faa';
	public $_config;
	public const NULL_CONFIG = "null-config";
	
	public function init ($config)
	{
		if ($config == null)
			$config = self::NULL_CONFIG;
		$this->_config = $config;
	}
	public function getExcitement()
	{
		return $this->excitement;
	}
	public function setExcitement($value)
	{
		$this->excitement = $value;
	}
	public function getReadOnly()
	{
		return true;
	}

	public function onBehaviorEvent($sender, $param, $responsetype = null, $postfunction = null)
	{
		return $this->getOwner()->raiseEvent('onBehaviorEvent', $sender, $param, $responsetype, $postfunction);
	}
	public function fxGlobalBehaviorEvent($sender, $param)
	{
	}
}

class dy1TextReplace extends TBehavior
{
	protected $_called = false;
	public function dyTextFilter($text, $callchain)
	{
		$this->_called = true;
		return str_replace("..", '__', $callchain->dyTextFilter($text));
	}
	public function isCalled()
	{
		return $this->_called;
	}
	public function dyPowerFunction($x, $y, $callchain)
	{
		return pow($x / $callchain->dyPowerFunction($x, $y), $y);
	}
}
class dy2TextReplace extends dy1TextReplace
{
	public function dyTextFilter($text, $callchain)
	{
		$this->_called = true;
		return str_replace("++", '||', $callchain->dyTextFilter($text));
	}
}
class dy3TextReplace extends dy1TextReplace
{
	public function dyTextFilter($text, $callchain)
	{
		$this->_called = true;
		return str_replace("!!", '??', $callchain->dyTextFilter($text));
	}
}

class dy1ClassTextReplace extends TClassBehavior
{
	protected $_called = false;
	public function dyTextFilter($hostobject, $text, $callchain)
	{
		$this->_called = true;
		return str_replace("__", '..', $callchain->dyTextFilter($text));
	}
	public function isCalled()
	{
		return $this->_called;
	}
	public function dyPowerFunction($hostobject, $x, $y, $callchain)
	{
		return pow($x / $callchain->dyPowerFunction($x, $y), $y);
	}
}
class dy2ClassTextReplace extends dy1ClassTextReplace
{
	public function dyTextFilter($hostobject, $text, $callchain)
	{
		$this->_called = true;
		return str_replace("||", '++', $callchain->dyTextFilter($text));
	}
}
class dy3ClassTextReplace extends dy1ClassTextReplace
{
	public function dyTextFilter($hostobject, $text, $callchain)
	{
		$this->_called = true;
		return str_replace("??", '^_^', $callchain->dyTextFilter($text));
	}
}


class IntraObjectExtenderBehavior extends TBehavior
{
	private $lastCall;
	private $arglist;

	public function getLastCall()
	{
		$v = $this->lastCall;
		$this->lastCall = null;
		return $v;
	}

	public function getLastArgumentList()
	{
		$v = $this->arglist;
		$this->arglist = null;
		return $v;
	}



	public function dyListen($fx, $chain)
	{
		$this->lastCall = 1;
		$this->arglist = func_get_args();

		return $chain->dyListen($fx); // Calls the next event, within a chain
	}
	public function dyUnlisten($fx, $chain)
	{
		$this->lastCall = 2;
		$this->arglist = func_get_args();

		return $chain->dyUnlisten($fx);
	}
	public function dyPreRaiseEvent($name, $sender, $param, $responsetype, $postfunction, $chain)
	{
		$this->lastCall = 3;
		$this->arglist = func_get_args();

		return $chain->dyPreRaiseEvent($name);// Calls the next event, within a chain, if parameters are left off, they are filled in with
		//	 the original parameters passed to the dynamic event. Parameters can be passed if they are changed.
	}
	public function dyIntraRaiseEventTestHandler($handler, $sender, $param, $name, $chain)
	{
		$this->lastCall += 4;
		$this->arglist = func_get_args();
	}
	public function dyIntraRaiseEventPostHandler($name, $sender, $param, $handler, $chain)
	{
		$this->lastCall += 5;
		$this->arglist = func_get_args();
	}
	public function dyPostRaiseEvent($responses, $name, $sender, $param, $responsetype, $postfunction, $chain)
	{
		$this->lastCall += 6;
		$this->arglist = func_get_args();
	}
	public function dyEvaluateExpressionFilter($expression, $chain)
	{
		$this->lastCall = 7;
		$this->arglist = func_get_args();
		return $expression;
	}
	public function dyEvaluateStatementsFilter($statement, $chain)
	{
		$this->lastCall = 8;
		$this->arglist = func_get_args();
		return $statement;
	}
	public function dyCreatedOnTemplate($parent, $chain)
	{
		$this->lastCall = 9;
		$this->arglist = func_get_args();
		return $parent;
	}
	public function dyAddParsedObject($object, $chain)
	{
		$this->lastCall = 10;
		$this->arglist = func_get_args();
	}
	public function dyAttachBehavior($name, $behavior, $chain)
	{
		$this->lastCall = 11;
		$this->arglist = func_get_args();
	}
	public function dyDetachBehavior($name, $behavior, $chain)
	{
		$this->lastCall = 12;
		$this->arglist = func_get_args();
	}
	public function dyEnableBehaviors($chain = null)
	{
		$this->lastCall += 13;
		$this->arglist = func_get_args();
	}
	public function dyDisableBehaviors($chain = null)
	{
		$this->lastCall = 14;
		$this->arglist = func_get_args();
	}
	public function dyEnableBehavior($name, $behavior, $chain)
	{
		$this->lastCall = 15;
		$this->arglist = func_get_args();
	}
	public function dyDisableBehavior($name, $behavior, $chain)
	{
		$this->lastCall = 16;
		$this->arglist = func_get_args();
	}
}


class IntraClassObjectExtenderBehavior extends TClassBehavior
{
}


class TDynamicBehavior extends TBehavior implements IDynamicMethods
{
	private $_dyMethod;
	public function getLastBehaviorDynamicMethodCalled()
	{
		return $this->_dyMethod;
	}
	public function __dycall($method, $args)
	{
		$this->_dyMethod = $method;
		if ($method == 'dyTestDynamicBehaviorMethod') {
			return $args[0] / $args[1];
		}
	}
	public function dyTestIntraEvent($param1, $param2, $chain)
	{
		return $chain->dyTestIntraEvent($param1 * 2 * $param2, $param2);
	}
	public function TestBehaviorMethod($param1, $param2)
	{
		return $param1 * $param2;
	}
}


class TDynamicClassBehavior extends TClassBehavior implements IDynamicMethods
{
	private $_dyMethod;
	public function getLastBehaviorDynamicMethodCalled()
	{
		return $this->_dyMethod;
	}
	//Dynamic Calls within class behaviors contain the main object as the first parameter within the args
	public function __dycall($method, $args)
	{
		$this->_dyMethod = $method;
		$object = array_shift($args);
		if ($method == 'dyTestDynamicClassBehaviorMethod') {
			return $args[0] / $args[1];
		}
	}
	public function dyTestIntraEvent($object, $param1, $param2, $chain)
	{
		return $chain->dyTestIntraEvent($param1 * 2 * $param2, $param2);
	}
	public function TestBehaviorMethod($object, $param1, $param2)
	{
		return $param1 * $param2;
	}
}

//we add this as a callable
function foo($sender, $param)
{
	
}
//we add this as a callable
function foopre($sender, $param)
{
	
}
//we add this as a callable
function foopost($sender, $param)
{
	
}
//we add this as a callable
function foobar($sender, $param)
{
	
}
//we add this as a callable
function foobarfoobar($sender, $param)
{
	
}



/**
 * @package System
 */
class TComponentTest extends PHPUnit\Framework\TestCase
{
	protected $tearDownScripts = [];
	protected $anonymousClassIndex = 0;
	protected $component;

	protected function setUp(): void
	{
		$component = new TComponent();
		$component->getEventHandlers('fxAttachClassBehavior')->clear();
		$component->getEventHandlers('fxDetachClassBehavior')->clear();
		unset($component);
		$this->tearDownScripts = [];
		$this->component = new NewComponent();
	}


	protected function tearDown(): void
	{
		//Closure may do something with the component.
		foreach ($this->tearDownScripts as $closure) {
			$closure();
		}
		$this->tearDownScripts = [];
		$this->component = null;
		
		$component = new NewComponent();
		
		$this->assertEquals([], $component->getBehaviors());
	}


	public function testGetListeningToGlobalEvents()
	{
		$this->assertEquals(true, $this->component->getListeningToGlobalEvents());
		$this->component->unlisten();
		$this->assertEquals(false, $this->component->getListeningToGlobalEvents());
	}


	public function testConstructorAutoListen()
	{
		// the default object auto installs class behavior hooks
		$this->assertEquals(1, $this->component->getEventHandlers('fxAttachClassBehavior')->getCount());
		$this->assertEquals(1, $this->component->getEventHandlers('fxDetachClassBehavior')->getCount());
		$this->assertTrue($this->component->getListeningToGlobalEvents());

		// this object does not auto install class behavior hooks, thus not changing the global event structure.
		//	Creating a new instance should _not_ influence the fxAttachClassBehavior and fxDetachClassBehavior
		//	count.
		$component_nolisten = new NewComponentNoListen();
		$this->assertEquals(1, $this->component->getEventHandlers('fxAttachClassBehavior')->getCount());
		$this->assertEquals(1, $this->component->getEventHandlers('fxDetachClassBehavior')->getCount());
		$this->assertEquals(1, $component_nolisten->getEventHandlers('fxAttachClassBehavior')->getCount());
		$this->assertEquals(1, $component_nolisten->getEventHandlers('fxDetachClassBehavior')->getCount());

		// tests order of class behaviors when a parent and class have class behavior.
		//	The child should override the parent object-oriented programming style
		$this->component->attachClassBehavior('Bar', 'BarBehavior', 'NewComponentNoListen');
		$this->component->attachClassBehavior('FooBar', 'FooBarBehavior', 'NewComponent');

		//create new object with new class behaviors built in, defined in the two lines above
		$component = new NewComponentNoListen;

		$this->assertEquals(25, $component->moreFunction(2, 2));

		$this->assertEquals(25, $component->Bar->moreFunction(2, 2));
		$this->assertEquals(8, $component->FooBar->moreFunction(2, 2));

		$component->unlisten();// unwind object and class behaviors
		$this->component->detachClassBehavior('FooBar', 'NewComponent');
		$this->component->detachClassBehavior('Bar', 'NewComponentNoListen');
	}


	public function testListenAndUnlisten()
	{
		$component = new NewComponentNoListen();

		$this->assertEquals(false, $component->getListeningToGlobalEvents());

		//This is from $this->component being instanced and listening.  $component is accessing the global event structure
		$this->assertEquals(1, $component->getEventHandlers('fxAttachClassBehavior')->getCount());

		$this->assertEquals(2, $component->listen());

		$this->assertEquals(true, $component->getListeningToGlobalEvents());

		//This is from $this->component being instanced and listening.  $component is accessing the global event structure
		$this->assertEquals(2, $component->getEventHandlers('fxAttachClassBehavior')->getCount());

		$this->assertEquals(2, $component->unlisten());

		$this->assertEquals(false, $component->getListeningToGlobalEvents());

		//This is from $this->component being instanced and listening.  $component is accessing the global event structure
		$this->assertEquals(1, $component->getEventHandlers('fxAttachClassBehavior')->getCount());
	}


	public function testListenAndUnlistenWithDynamicEventCatching()
	{
		$component = new DynamicCatchingComponent();

		$this->assertEquals(false, $component->getListeningToGlobalEvents());

		//This is from $this->component being instanced and listening.  $component is accessing the global event structure
		$this->assertEquals(0, $component->getEventHandlers(TComponent::GLOBAL_RAISE_EVENT_LISTENER)->getCount());

		// this adds the fxAttachClassBehavior, fxDetachClassBehavior, and __dycall of the component
		$this->assertEquals(3, $component->listen());

		$this->assertEquals(true, $component->getListeningToGlobalEvents());

		//This is from $this->component being instanced and listening.  $component is accessing the global event structure
		$this->assertEquals(1, $component->getEventHandlers(TComponent::GLOBAL_RAISE_EVENT_LISTENER)->getCount());

		$this->assertEquals(3, $component->unlisten());

		$this->assertEquals(false, $component->getListeningToGlobalEvents());

		//This is from $this->component being instanced and listening.  $component is accessing the global event structure
		$this->assertEquals(0, $component->getEventHandlers(TComponent::GLOBAL_RAISE_EVENT_LISTENER)->getCount());
	}



	//Test Class behaviors
	public function testAttachClassBehavior()
	{
		$fooClassBehaviorName = 'FooClassBehaviorName';
		$this->assertEquals([], $this->component->getBehaviors());

	// ensure that the class is listening
		$this->assertEquals(1, $this->component->getEventHandlers('fxAttachClassBehavior')->getCount());

		//Test that the component is not a FooClassBehavior
		$this->assertNull($this->component->asa($fooClassBehaviorName), "Component is already a FooClassBehavior and should not have this behavior");


		//Add the FooClassBehavior
		// Add class behavior as IClassBehavior string
		$b1 = $this->component->attachClassBehavior($fooClassBehaviorName, 'FooClassBehavior');
		$this->tearDownScripts[] = function() use ($fooClassBehaviorName) {$this->component->detachClassBehavior($fooClassBehaviorName);};
		$this->assertInstanceof('FooClassBehavior', $b1);
		$this->assertNotNull($this->component->asa($fooClassBehaviorName), "Component is does not have the FooClassBehavior and should have this behavior");
		$this->assertEquals(FooClassBehavior::NULL_CONFIG, $this->component->asa($fooClassBehaviorName)->_config, "Component did not initialize the behavior when it should");
		
		// Add class behavior as instanced IClassBehavior behavior
		$b2 = $this->component->attachClassBehavior('FooClassBehavior2', $ob2 = new FooClassBehavior());
		$this->tearDownScripts[] = function() {$this->component->detachClassBehavior('FooClassBehavior2');};
		$this->assertInstanceof('FooClassBehavior', $b2);
		$this->assertEquals($ob2, $b2);
		$this->assertNotNull($this->component->asa('FooClassBehavior2'), "Component is does not have the FooClassBehavior2 and should have this behavior");
		$this->assertEquals('default', $this->component->asa('FooClassBehavior2')->PropertyA, "Component is does not have the FooClassBehavior2 and should have this behavior");
		$this->assertNull($this->component->asa('FooClassBehavior2')->_config, "Component initialized existing behavior when it should not have");
		
		// add class behavior as array of properties
		$b3 = $this->component->attachClassBehavior('FooClassBehavior3', ['class' => 'FooClassBehavior', 'propertyA'=>'value', IBaseBehavior::CONFIG_KEY => $foo3classdata = 'class-config-data']);
		$this->assertInstanceof('FooClassBehavior', $b3);
		$this->tearDownScripts[] = function() {$this->component->detachClassBehavior('FooClassBehavior3');};
		$this->assertNotNull($this->component->asa('FooClassBehavior3'), "Component is does not have the FooClassBehavior3 and should have this behavior");
		$this->assertEquals('value', $this->component->asa('FooClassBehavior3')->PropertyA, "Component is does not have the FooClassBehavior2 and should have this behavior");
		$this->assertEquals($foo3classdata, $this->component->asa('FooClassBehavior3')->_config, "Component did not initialize the behavior when it should");
		
		// add class behavior as IBehavior string
		$b4 = $this->component->attachClassBehavior('FooRegularBehavior', 'BehaviorTestBehavior');
		$this->tearDownScripts[] = function() {$this->component->detachClassBehavior('FooRegularBehavior');};
		$this->assertEquals([$this->component->FooRegularBehavior], $b4);
		$this->assertNotNull($this->component->asa('FooRegularBehavior'));
		$this->assertEquals('faa', $this->component->asa('FooRegularBehavior')->Excitement);
		$this->assertEquals(BehaviorTestBehavior::NULL_CONFIG, $this->component->asa('FooRegularBehavior')->_config, "Component did not initialize the behavior when it should");
		
		// add class behavior as IBehavior array of properties
		$b5 = $this->component->attachClassBehavior('FooRegularBehavior2', ['class' => 'BehaviorTestBehavior', 'Excitement'=>'behavior-value', IBaseBehavior::CONFIG_KEY => $foo2data = 'config-data']);
		$this->assertEquals([$this->component->FooRegularBehavior2], $b5);
		$this->tearDownScripts[] = function() {$this->component->detachClassBehavior('FooRegularBehavior2');};
		$this->assertNotNull($this->component->asa('FooRegularBehavior2'));
		$this->assertEquals('behavior-value', $this->component->asa('FooRegularBehavior2')->Excitement);
		$this->assertEquals($foo2data, $this->component->asa('FooRegularBehavior2')->_config, "Component did not initialize the behavior when it should");
		
		// Add class behavior as instance of IBehavior to be cloned
		$b6 = $this->component->attachClassBehavior('FooRegularBehavior3', $ob6 = new BehaviorTestBehavior());
		$this->tearDownScripts[] = function() {$this->component->detachClassBehavior('FooRegularBehavior3');};
		$this->assertEquals($this->component, $b6[0]->getOwner());
		$this->assertNotNull($this->component->asa('FooRegularBehavior3'));
		$this->assertEquals('faa', $this->component->asa('FooRegularBehavior3')->Excitement);
		$this->assertNull($this->component->asa('FooRegularBehavior3')->_config, "Component did not initialize the behavior when it should");
		
		// Add anonymous class behavior, numeric
		$this->assertNull($this->component->asa(0));
		$b7 = $this->component->attachClassBehavior('11', $ob7 = new BehaviorTestBehavior());
		$b7name = $this->anonymousClassIndex++;
		$this->tearDownScripts[] = function() use ($b7name) { $this->component->detachClassBehavior($b7name);};
		$ob7->Excitement = 'anon_behavior';
		$this->assertEquals($this->component, $b7[0]->getOwner());
		$this->assertNotNull($this->component->asa(0));
		$this->assertEquals('faa', $this->component->asa(0)->Excitement, "The original IBehavior attached to a class should have been cloned to attach and not be the original behavior in this instance."); 
		$this->assertNull($this->component->asa(0)->_config, "Component did not initialize the behavior when it should");
		
		// Add anonymous class behavior, null
		$b8 = $this->component->attachClassBehavior(null, $ob8 = new BehaviorTestBehavior());
		$b8name = $this->anonymousClassIndex++;
		$this->tearDownScripts[] = function() use ($b8name) {$this->component->detachClassBehavior($b8name);};
		$ob7->Excitement = 'anon_null_behavior';
		$this->assertEquals($this->component, $b8[0]->getOwner());
		$this->assertNotNull($this->component->asa(1));
		$this->assertEquals('faa', $this->component->asa(1)->Excitement);
		$this->assertNull($this->component->asa(1)->_config, "Component did not initialize the behavior when it should");


		// test if the function modifies new instances of the object
		$anothercomponent = new NewComponent();

		//The new component should be a FooClassBehavior
		$this->assertNotNull($anothercomponent->asa(0), "anothercomponent does not have the numeric named anonymous behavior");
		$this->assertNotNull($anothercomponent->asa(1), "anothercomponent does not have the null named anonymous behavior");
		$this->assertNotNull($anothercomponent->asa($fooClassBehaviorName), "anothercomponent does not have the FooClassBehavior and should");
		$this->assertNotNull($anothercomponent->asa('FooClassBehavior2'), "anothercomponent does not have the FooClassBehavior2 and should");
		$this->assertNotNull($anothercomponent->asa('FooClassBehavior3'), "anothercomponent does not have the FooClassBehavior3 and should");
		$this->assertNotNull($anothercomponent->asa('FooRegularBehavior'), "anothercomponent does not have the FooRegularBehavior and should");
		$this->assertNotNull($anothercomponent->asa('FooRegularBehavior2'), "anothercomponent does not have the FooRegularBehavior2 and should");
		$this->assertNotNull($anothercomponent->asa('FooRegularBehavior3'), "anothercomponent does not have the FooRegularBehavior3 and should");
		$anothercomponent->asa('FooRegularBehavior')->Excitement = 'foo-regular-behavior-test-value';
		
		// Class behaviors have both classes as owners, behaviors have their owner
		$this->assertEquals([$this->component, $anothercomponent], $this->component->asa('FooClassBehavior')->getOwners());
		$this->assertEquals($this->component, $this->component->asa('FooRegularBehavior')->getOwner());
		$this->assertEquals($anothercomponent, $anothercomponent->asa('FooRegularBehavior')->getOwner());
		$this->assertNotEquals($this->component->asa('FooRegularBehavior'), $anothercomponent->asa('FooRegularBehavior'));
		
		// Clone adds owner to class behaviors
		$thirdcomponent = clone $anothercomponent;
		$this->assertEquals([$this->component, $anothercomponent, $thirdcomponent], $this->component->asa($fooClassBehaviorName)->getOwners());

		// test when overwriting an existing class behavior, it should throw an TInvalidOperationException
		try {
			$this->component->attachClassBehavior($fooClassBehaviorName, new BarClassBehavior);
			$this->fail('TInvalidOperationException not raised when overwriting an existing behavior');
		} catch (TInvalidOperationException $e) {
		}
		
		// test when using non-class regular behavior, TComponent clones IBehaviors in class context.


		// test TInvalidOperationException when placing a behavior on TComponent
		try {
			$this->component->attachClassBehavior('FooBarBehavior', 'FooBarBehavior', TComponent::class);
			$this->fail('TInvalidOperationException not raised when trying to place a behavior on the root object TComponent');
		} catch (TInvalidOperationException $e) {
		}

		// test if the function does not modify any existing objects that are not listening
		//	The FooClassBehavior is already a part of the class behaviors thus the new instance gets the behavior.
		$nolistencomponent = new NewComponentNoListen();

		// test if the function modifies all existing objects that are listening
		//	Adding a behavior to the first object, the second instance should automatically get the class behavior.
		//		This is because the second object is listening to the global events of class behaviors
		$this->component->attachClassBehavior($className = 'BarClassBehaviorName', new BarClassBehavior);
		$this->tearDownScripts[] = function() use ($className) {$this->component->detachClassBehavior($className);};
		$this->assertNotNull($anothercomponent->asa($className), "anothercomponent is does not have the BarClassBehavior");

		// The no listen object should not have the BarClassBehavior because it was added as a class behavior after the object was instanced
		$this->assertNull($nolistencomponent->asa($className), "nolistencomponent has the BarClassBehavior and should not");

		//	But the no listen object should have the FooClassBehavior because the class behavior was installed before the object was instanced
		$this->assertNotNull($nolistencomponent->asa($fooClassBehaviorName), "nolistencomponent is does not have the FooClassBehavior");

		//Clear out what was done during this test
		$anothercomponent->unlisten();
		$thirdcomponent->unlisten();
		$this->component->detachClassBehavior($className);
		array_pop($this->tearDownScripts);

		// Test attaching of single object behaviors as class-wide behaviors
		$this->component->attachClassBehavior('BarBehaviorObject', 'BarBehavior');
		$this->assertTrue($this->component->asa('BarBehaviorObject') instanceof BarBehavior);
		$this->assertEquals($this->component->BarBehaviorObject->Owner, $this->component);
		$this->component->detachClassBehavior('BarBehaviorObject');
		$this->assertNull($this->component->asa('BarBehaviorObject'));
	}


	public function testAttachClassBehavior_AnonymousOverExistingAnon()
	{
		$component = new NewComponent();
		$component->attachBehavior(null, $behavior = new BehaviorTestBehavior());
		$component->attachClassBehavior(null, $classBehaviors = new FooClassBehavior());
		$indexName = $this->anonymousClassIndex++;
		$this->tearDownScripts[] = function() use ($indexName) {NewComponent::detachClassBehavior($indexName);};
		$this->assertEquals([$classBehaviors], $this->component->getBehaviors());
		$this->assertEquals([$behavior, $classBehaviors], $component->getBehaviors());
		$this->assertEquals($behavior, $component->asa(0));
		$this->assertEquals($classBehaviors, $component->asa(1));
		$this->assertEquals($classBehaviors, $this->component->asa(0));
	}



	public function testDetachClassBehavior()
	{
		$fooClassBehaviorName = 'FooClassBehaviorName';
		$this->assertEquals([], $this->component->getBehaviors());
		
		// ensure that the component is listening
		$this->assertEquals(1, $this->component->getEventHandlers('fxDetachClassBehavior')->getCount());

		$prenolistencomponent = new NewComponentNoListen();

		//Attach a class behavior
		$b = $this->component->attachClassBehavior($fooClassBehaviorName, $cb = new FooClassBehavior());
		$this->tearDownScripts[$fooClassBehaviorName] = function() use ($fooClassBehaviorName) {$this->component->detachClassBehavior($fooClassBehaviorName);};
		$this->assertEquals($cb, $b);
		$b = $this->component->attachClassBehavior('FooRegularBehavior', 'BehaviorTestBehavior');
		$this->tearDownScripts['FooRegularBehavior'] = function() {$this->component->detachClassBehavior('FooRegularBehavior');};
		$rb = $this->component->FooRegularBehavior;
		$this->assertEquals([$rb], $b);
		
		$this->assertEquals(1, $this->component->getEventHandlers('fxDetachClassBehavior')->getCount());

		//Create new components that listen and don't listen to global events
		$anothercomponent = new NewComponent();
		$postnolistencomponent = new NewComponentNoListen();
		$ancomb = $anothercomponent->FooRegularBehavior;

		//ensures that all the Components are properly initialized
		$this->assertNotNull($this->component->asa($fooClassBehaviorName), "Listening Component does not have the FooClassBehavior and should have this behavior");
		$this->assertNull($prenolistencomponent->asa($fooClassBehaviorName), "Component has the FooClassBehavior and should _not_ have this behavior");
		$this->assertNotNull($anothercomponent->asa($fooClassBehaviorName), "Component does not have the FooClassBehavior and should have this behavior");
		$this->assertNotNull($postnolistencomponent->asa($fooClassBehaviorName), "Component does not have the FooClassBehavior and should have this behavior");
		$this->assertNotNull($anothercomponent->asa('FooRegularBehavior'), "Component does not have the FooRegularBehavior and should have this behavior");
		$this->assertNotNull($postnolistencomponent->asa('FooRegularBehavior'), "Component does not have the FooRegularBehavior and should have this behavior");
		$this->assertEquals(2, $this->component->getEventHandlers('fxDetachClassBehavior')->getCount());


		unset($this->tearDownScripts[$fooClassBehaviorName]);
		$deb = $this->component->detachClassBehavior($fooClassBehaviorName);
		$this->assertEquals($cb, $deb);
		
		unset($this->tearDownScripts['FooRegularBehavior']);
		$derb = $this->component->detachClassBehavior('FooRegularBehavior');
		$this->assertEquals([$rb, $ancomb], $derb);
		
		$noReturnBehavior = $this->component->detachClassBehavior('NoBehaviorOfThisName');
		$this->assertNull($noReturnBehavior);

		$this->assertNull($this->component->asa($fooClassBehaviorName), "Component has the FooClassBehavior and should _not_ have this behavior");
		$this->assertNull($prenolistencomponent->asa($fooClassBehaviorName), "Component has the FooClassBehavior and should _not_ have this behavior");
		$this->assertNull($anothercomponent->asa($fooClassBehaviorName), "Component has the FooClassBehavior and should _not_ have this behavior");
		$this->assertNotNull($postnolistencomponent->asa($fooClassBehaviorName), "Component does not have the FooClassBehavior and should have this behavior");
		$this->assertNull($anothercomponent->asa('FooRegularBehavior'), "Component has the FooRegularBehavior and should _not_ have this behavior");
		$this->assertNotNull($postnolistencomponent->asa('FooRegularBehavior'), "Component does not have the FooRegularBehavior and should have this behavior");


		//tear down function variables
		$anothercomponent->unlisten();
	}

	public function testGetClassHierarchy()
	{
		$component = new DynamicCatchingComponent;
		$this->assertEquals([IDynamicMethods::class, DynamicCatchingTrait::class, DynamicCatchingComponent::class, NewComponentNoListen::class, NewComponentTestTrait::class, NewComponent::class, TComponent::class], $component->getClassHierarchy());
		$this->assertEquals([IDynamicMethods::class, DynamicCatchingTrait::class, DynamicCatchingComponent::class, NewComponentNoListen::class, NewComponentTestTrait::class, NewComponent::class, TComponent::class], $component->getClassHierarchy(false));
		$this->assertEquals([strtolower(IDynamicMethods::class), strtolower(DynamicCatchingTrait::class), strtolower(DynamicCatchingComponent::class), strtolower(NewComponentNoListen::class), strtolower(NewComponentTestTrait::class), strtolower(NewComponent::class), strtolower(TComponent::class)], $component->getClassHierarchy(true));
	}


	public function testAsA()
	{
		$fooClassBehaviorName = 'FooClassBehaviorName';
		$foofooClassBehaviorName = 'FooFooClassBehavior';
		$barClassName = 'BarClassBehaviorName';
		$noBehaviorName = 'NoBehaviorInTheClassName';
		$anothercomponent = new NewComponent();

		// ensure the component does not have the FooClassBehavior
		$this->assertNull($this->component->asa($fooClassBehaviorName));
		$this->assertNull($this->component->asa($foofooClassBehaviorName));
		$this->assertNull($this->component->asa($barClassName));
		$this->assertNull($this->component->asa($noBehaviorName));

		$this->assertNull($anothercomponent->asa($fooClassBehaviorName));
		$this->assertNull($anothercomponent->asa($foofooClassBehaviorName));
		$this->assertNull($anothercomponent->asa($barClassName));
		$this->assertNull($anothercomponent->asa($noBehaviorName));

		// add the class behavior
		$this->component->attachClassBehavior($fooClassBehaviorName, new FooClassBehavior);
		$this->tearDownScripts[] = function() {$this->component->detachClassBehavior($fooClassBehaviorName);};
		
		//Check that the component has only the class behavior assigned
		$this->assertNotNull($this->component->asa($fooClassBehaviorName));
		$this->assertNotNull($this->component->asa(strtoupper($fooClassBehaviorName)));
		$this->assertNull($this->component->asa($foofooClassBehaviorName));
		$this->assertNull($this->component->asa($barClassName));
		$this->assertNull($this->component->asa($noBehaviorName));

		//Check that the component has only the class behavior assigned
		$this->assertNotNull($anothercomponent->asa($fooClassBehaviorName));
		$this->assertNull($anothercomponent->asa($foofooClassBehaviorName));
		$this->assertNull($anothercomponent->asa($barClassName));
		$this->assertNull($anothercomponent->asa($noBehaviorName));

		// remove the class behavior
		array_pop($this->tearDownScripts);
		$this->component->detachClassBehavior($fooClassBehaviorName);

		// Check the function doesn't have the behavior any more
		$this->assertNull($this->component->asa($fooClassBehaviorName));
		$this->assertNull($this->component->asa($foofooClassBehaviorName));
		$this->assertNull($this->component->asa($barClassName));
		$this->assertNull($this->component->asa($noBehaviorName));

		$this->assertNull($anothercomponent->asa($fooClassBehaviorName));
		$this->assertNull($anothercomponent->asa($foofooClassBehaviorName));
		$this->assertNull($anothercomponent->asa($barClassName));
		$this->assertNull($anothercomponent->asa($noBehaviorName));


		$fooBehaviorName = 'FooBehaviorName';
		$fooFooBehaviorName = 'FooFooBehavior';
		$behaviorName = 'BarBehaviorName';
		$noRegularBehaviorName = 'NonExistantBehavior';
		$this->component->attachBehavior($behaviorName, $bar = new BarBehavior);

		//Check that the component has only the object behavior assigned
		$this->assertNull($this->component->asa($fooBehaviorName));
		$this->assertNull($this->component->asa($fooFooBehaviorName));
		$this->assertEquals($bar, $this->component->asa($behaviorName));
		$this->assertNull($this->component->asa($noRegularBehaviorName));

		//Check that the component has the behavior assigned
		$this->assertNull($anothercomponent->asa($fooBehaviorName));
		$this->assertNull($anothercomponent->asa($fooFooBehaviorName));
		$this->assertNull($anothercomponent->asa($behaviorName));
		$this->assertNull($anothercomponent->asa($noRegularBehaviorName));
		
		$this->component->attachBehavior($fooBehaviorName, $foo = new FooBehavior);
		$this->component->attachBehavior($fooFooBehaviorName, $foofoo = new FooFooBehavior);

		$this->assertEquals($foo, $this->component->asa(FooBehavior::class));
		$this->assertEquals($foofoo, $this->component->asa(FooFooBehavior::class));
		$this->assertEquals($bar, $this->component->asa(BarBehavior::class));
		
		$this->component->detachBehavior($fooBehaviorName);
		$this->component->detachBehavior($fooFooBehaviorName);
		$this->component->detachBehavior($behaviorName);

		//Check that the component has no object behaviors assigned
		$this->assertNull($this->component->asa($fooBehaviorName));
		$this->assertNull($this->component->asa($fooFooBehaviorName));
		$this->assertNull($this->component->asa($behaviorName));
		$this->assertNull($this->component->asa($noRegularBehaviorName));

		//Check that the component has no behavior assigned
		$this->assertNull($anothercomponent->asa($fooBehaviorName));
		$this->assertNull($anothercomponent->asa($fooFooBehaviorName));
		$this->assertNull($anothercomponent->asa($behaviorName));
		$this->assertNull($anothercomponent->asa($noRegularBehaviorName));

		$anothercomponent->unlisten();
	}

	public function testIsA()
	{
		$this->component = new SubNewComponent();
		//This doesn't check the IInstanceCheck functionality, separate function

		$this->assertTrue($this->component->isa(TComponent::class));
		$this->assertTrue($this->component->isa(NewComponent::class));
		$this->assertTrue($this->component->isa(NewComponentTestTrait::class));
		$this->assertTrue($this->component->isa(SubNewComponentTestTrait::class));
		$this->assertTrue($this->component->isa(SubNewComponentInterface::class));
		$this->assertTrue($this->component->isa(new SubNewComponent));
		$this->assertFalse($this->component->isa(new FooBehavior));
		$this->assertFalse($this->component->isa(FooBehavior::class));
		$this->assertFalse($this->component->isa(UnusedNewComponentTestTrait::class));
		
		$fooFooBehaviorName = 'FooFooBehaviorName';
		//Ensure there is no BarBehavior
		$this->assertNull($this->component->asa($fooFooBehaviorName));

		$this->assertFalse($this->component->isa(FooBehavior::class));
		$this->assertFalse($this->component->isa(FooFooBehavior::class));

		$this->component->attachBehavior($fooFooBehaviorName, new FooFooBehavior);

		$this->assertNotNull($this->component->asa($fooFooBehaviorName));

		$this->assertTrue($this->component->isa(FooBehavior::class));
		$this->assertTrue($this->component->isa(FooFooBehavior::class));

		$this->component->disableBehaviors();
		// It still has the behavior
		$this->assertNotNull($this->component->asa($fooFooBehaviorName));

		// But it is not expressed
		$this->assertFalse($this->component->isa(FooBehavior::class));
		$this->assertFalse($this->component->isa(FooFooBehavior::class));

		$this->component->enableBehaviors();
		$this->assertNotNull($this->component->asa($fooFooBehaviorName));

		$this->assertTrue($this->component->isa(FooFooBehavior::class));


		$fooBarBehaviorName = 'FooBarBehaviorName';
		$this->component->attachBehavior($fooBarBehaviorName, new FooBarBehavior);

		$this->assertTrue($this->component->isa(FooBehavior::class));
		$this->assertTrue($this->component->isa(FooBarBehavior::class));

		$this->component->disableBehavior($fooBarBehaviorName);

		$this->assertTrue($this->component->isa(FooBehavior::class));
		$this->assertFalse($this->component->isa(FooBarBehavior::class));

		$this->component->enableBehavior($fooBarBehaviorName);
		$this->component->disableBehavior($fooFooBehaviorName);
		$this->assertFalse($this->component->isa(FooBehavior::class));
		$this->assertFalse($this->component->isa(FooFooBehavior::class));
		$this->assertTrue($this->component->isa(FooBarBehavior::class));

		$this->component->disableBehavior($fooBarBehaviorName);
		$this->component->disableBehavior($fooFooBehaviorName);

		$this->assertFalse($this->component->isa(FooBehavior::class));
		$this->assertFalse($this->component->isa(FooFooBehavior::class));
		$this->assertFalse($this->component->isa(FooBarBehavior::class));

		$this->component->enableBehavior($fooBarBehaviorName);
		$this->component->enableBehavior($fooFooBehaviorName);

		$this->assertTrue($this->component->isa(FooFooBehavior::class));
		$this->assertTrue($this->component->isa(FooBarBehavior::class));


		$this->component->detachBehavior($fooFooBehaviorName);
		$this->component->detachBehavior($fooBarBehaviorName);

		$this->assertFalse($this->component->isa(new FooBehavior));
		$this->assertFalse($this->component->isa(FooBehavior::class));
		$this->assertFalse($this->component->isa(new FooFooBehavior));
		$this->assertFalse($this->component->isa(FooFooBehavior::class));
		$this->assertFalse($this->component->isa(new FooBarBehavior));
		$this->assertFalse($this->component->isa(FooBarBehavior::class));
	}

	public function testIsA_with_IInstanceCheck()
	{
		$this->assertTrue($this->component->isa('NewComponent'));
		$this->assertFalse($this->component->isa(PreBarBehavior::class));

		$this->component->attachBehavior('BarBehaviorName', $behavior = new BarBehavior);

		$behavior->setInstanceReturn(null);

		$this->assertTrue($this->component->isa('NewComponent'));
		$this->assertTrue($this->component->isa(PreBarBehavior::class));
		$this->assertFalse($this->component->isa(FooBehavior::class));

		// This forces the iso on the BarBehavior to respond to any class with false
		$behavior->setInstanceReturn(false);
		$this->assertFalse($this->component->isa(PreBarBehavior::class));
		$this->assertFalse($this->component->isa(FooBehavior::class));

		//This forces the isa on the BarBehavior to respond to any class with true
		$behavior->setInstanceReturn(true);
		$this->assertTrue($this->component->isa(FooBehavior::class));
	}
	
	public function testGetBehaviors()
	{
		$this->assertEquals([], $this->component->getBehaviors());
		$b = new FooFooBehavior();
		$behaviorName = 'aFooFooBehaviorName';
		$this->assertEquals($b, $this->component->attachBehavior($behaviorName, $b));
		$behaviorName = strtolower($behaviorName);
		$this->assertEquals([$behaviorName => $b], $this->component->getBehaviors());
		$b->setEnabled(false);
		$this->assertEquals([$behaviorName => $b], $this->component->getBehaviors());
		$b->setEnabled(true);
		$this->assertEquals([$behaviorName => $b], $this->component->getBehaviors());
		
		$b2 = new BarBehavior();
		$behaviorName2 = 'aBarBehaviorName';
		$this->assertEquals($b2, $this->component->attachBehavior($behaviorName2, $b2));
		$behaviorName2 = strtolower($behaviorName2);
		$this->assertEquals([$behaviorName => $b], $this->component->getBehaviors(FooFooBehavior::class));
		$this->assertEquals([$behaviorName2 => $b2], $this->component->getBehaviors(BarBehavior::class));
		$this->assertEquals([$behaviorName => $b], $this->component->getBehaviors(FooBehavior::class));
		$this->assertEquals([$behaviorName => $b], $this->component->getBehaviors(FooInterface::class));
		$this->assertEquals($b2, $this->component->detachBehavior($behaviorName2));
		$this->assertEquals(false, is_object($this->component->getBehaviors()));
		$this->assertEquals(true, is_array($this->component->getBehaviors()));
		$this->assertEquals($b, $this->component->detachBehavior($behaviorName));
		$this->assertEquals([], $this->component->getBehaviors());
	}

	public function testAttachDetachBehavior()
	{
		try {
			$this->component->faaEverMore(true, true);
			$this->fail('TUnknownMethodException not raised trying to execute a undefined class method');
		} catch (TUnknownMethodException $e) {
		}

		$this->assertNull($this->component->asa('FooBehavior'));
		$this->assertFalse($this->component->isa(FooBehavior::class));
		$this->assertNull($this->component->asa('BarBehavior'));
		$this->assertFalse($this->component->isa(BarBehavior::class));

		try {
			$this->component->attachBehavior('FooBehavior', new TComponent());
			$this->fail('TApplicationException trying to attach an object that is not a behavior without throwing error');
		} catch (TInvalidDataTypeException $e) {
		}
		
		//Instance TBehavior
		$behavior = new FooBehavior();
		try {	//  detaching without any attachment
			$behavior->detach(new TComponent());
			$this->fail("Failed to throw TInvalidOperationException when detaching a TBehavior that isn't attached.");
		} catch (TInvalidOperationException $e) {
		}

		$this->component->attachBehavior('FooBehavior', $behavior);
		try {	//  attaching when already attached
			$behavior->attach(new TComponent());
			$this->fail("Failed to throw TInvalidOperationException when attaching to a TBehavior that already has an owner.");
		} catch (TInvalidOperationException $e) {
		}
		try {	// detaching the wrong component.
			$behavior->detach(new TComponent());
			$this->fail("Failed to throw TInvalidOperationException when detaching a TBehavior from the wrong owner.");
		} catch (TInvalidOperationException $e) {
		}

		$this->assertNotNull($this->component->asa('FooBehavior'));
		$this->assertTrue($this->component->isa(FooBehavior::class));
		$this->assertNull($this->component->asa('BarBehavior'));
		$this->assertFalse($this->component->isa(BarBehavior::class));

		try {
			$this->assertTrue($this->component->faaEverMore(true, true));
		} catch (TApplicationException $e) {
			$this->fail('TApplicationException raised while trying to execute a behavior class method');
		}

		try {
			$this->component->noMethodHere(true);
			$this->fail('TUnknownMethodException not raised trying to execute a undefined class method');
		} catch (TUnknownMethodException $e) {
		}

		$this->assertTrue($this->component->disableBehavior('FooBehavior'));

		//BarBehavior is not a behavior at this time
		$this->assertFalse($this->component->disableBehavior('BarBehavior'));

		try {
			$this->component->faaEverMore(true, true);
			$this->fail('TUnknownMethodException not raised trying to execute a undefined class method');
		} catch (TUnknownMethodException $e) {
		}

		$this->assertTrue($this->component->enableBehavior('FooBehavior'));

		//BarBehavior is not a behavior at this time
		$this->assertFalse($this->component->enableBehavior('BarBehavior'));

		try {
			$this->assertTrue($this->component->faaEverMore(true, true));
		} catch (TApplicationException $e) {
			$this->fail('TApplicationException raised while trying to execute a behavior class method');
		}
		
		// Instance from string, replace first behavior.
		
		$behavior->detached = 0;
		$this->component->attachBehavior('FooBehavior', 'FooBehavior');
		$this->assertEquals(1, $behavior->detached,  "Attaching a behavior over an existing behavior did not call detach on the prior behavior.");

		$this->component->detachBehavior('FooBehavior');

		$this->assertNull($this->component->asa('FooBehavior'));
		$this->assertFalse($this->component->isa(FooBehavior::class));
		$this->assertNull($this->component->asa('BarBehavior'));
		$this->assertFalse($this->component->isa(BarBehavior::class));
		
		
		$this->component->attachBehavior(strtoupper('FooBehavior'), 'FooBehavior');

		$this->assertNotNull($this->component->asa(strtoupper('FooBehavior')));
		$this->assertNotNull($this->component->asa('FooBehavior'));
		$this->assertTrue($this->component->isa(FooBehavior::class));
		$this->assertNull($this->component->asa('BarBehavior'));
		$this->assertFalse($this->component->isa(BarBehavior::class));
		$this->assertEquals('default',$this->component->asa('FooBehavior')->PropertyA);
		
		$this->component->detachBehavior(strtolower('FooBehavior'));

		$this->assertNull($this->component->asa('FooBehavior'));
		$this->assertFalse($this->component->isa(FooBehavior::class));
		$this->assertNull($this->component->asa('BarBehavior'));
		$this->assertFalse($this->component->isa(BarBehavior::class));
		
		// Anonymous null named behavior
		$this->component->attachBehavior(null, ['class' => 'FooBehavior', 'PropertyA'=>'anon_name_null']);

		$this->assertNotNull($this->component->asa(0));
		$this->assertTrue($this->component->isa(FooBehavior::class));
		$this->assertNull($this->component->asa('BarBehavior'));
		$this->assertFalse($this->component->isa(BarBehavior::class));
		$this->assertEquals('anon_name_null',$this->component->asa(0)->PropertyA);
		
		$this->component->detachBehavior(0);

		$this->assertNull($this->component->asa(0));
		$this->assertFalse($this->component->isa(FooBehavior::class));
		$this->assertNull($this->component->asa('BarBehavior'));
		$this->assertFalse($this->component->isa(BarBehavior::class));
		
		
		// Anonymous number behavior
		$this->component->attachBehavior(11, ['class' => 'FooBehavior', 'PropertyA'=>'anon_name']);
		
		$this->assertNotNull($this->component->asa(1));
		$this->assertTrue($this->component->isa(FooBehavior::class));
		$this->assertNull($this->component->asa('BarBehavior'));
		$this->assertFalse($this->component->isa(BarBehavior::class));
		$this->assertEquals('anon_name',$this->component->asa(1)->PropertyA);
		
		$this->component->detachBehavior(1);
		
		$this->assertNull($this->component->asa(1));
		$this->assertFalse($this->component->isa(FooBehavior::class));
		$this->assertNull($this->component->asa('BarBehavior'));
		$this->assertFalse($this->component->isa(BarBehavior::class));
		
		
		
		
		//Instance TClassBehavior
		$behavior = new FooClassBehavior();
		try {	//  detaching without any attachment
			$behavior->detach(new TComponent());
			$this->fail("Failed to throw TInvalidOperationException when detaching a TClassBehavior that isn't attached.");
		} catch (TInvalidOperationException $e) {
		}
		$fooClassBehaviorName = 'FooClassBehavior';
		$this->component->attachBehavior($fooClassBehaviorName, $behavior);
		try {	//  attaching the same owner twice.
			$behavior->attach($this->component);
			$this->fail("Failed to throw TInvalidOperationException when attaching the same object twice to a TClassBehavior.");
		} catch (TInvalidOperationException $e) {
		}
		try {	// detaching the wrong component.
			$behavior->detach(new TComponent());
			$this->fail("Failed to throw TInvalidOperationException when detaching from the wrong owner from a TClassBehavior.");
		} catch (TInvalidOperationException $e) {
		}
		
		$this->assertNotNull($this->component->asa($fooClassBehaviorName));
		$this->assertFalse($this->component->isa(FooBehavior::class));
		$this->assertNull($this->component->asa('BarBehavior'));
		$this->assertFalse($this->component->isa(BarBehavior::class));
	}
	
	
	public function testAttachBehaviorEventHandlersAtPriority()
	{
		$this->component->attachBehavior($name1 = 'TestWithEvents1', $b1 = new FooBehaviorWithEvents());
		$this->component->attachBehavior($name2 = 'TestWithEvents2', $b2 = new FooBehaviorWithEvents(), 3);
		
		$this->assertEquals('fooEventHandler', $b1->eventsLog()['onMyEvent'][0]);
		$this->assertInstanceOf('\Closure', $b1->eventsLog()['onMyEvent'][1]);
		$this->assertEquals(10, $this->component->onMyEvent->priorityOf([$b1, $b1->eventsLog()['onMyEvent'][0]]));
		$this->assertEquals(10, $this->component->onMyEvent->priorityOf($b1->eventsLog()['onMyEvent'][1]));
		$this->assertEquals(3, $this->component->onMyEvent->priorityOf([$b2, $b2->eventsLog()['onMyEvent'][0]]));
		$this->assertEquals(3, $this->component->onMyEvent->priorityOf($b2->eventsLog()['onMyEvent'][1]));
	}


	public function testAttachDetachBehaviors()
	{
		$fooBehaviorName = 'FooBehaviorName';
		$barBehaviorName = 'BarBehaviorName';
		$fooBarBehaviorName = 'FooBarBehaviorName';
		$preBarBehaviorName = 'PreBarBehaviorName';
		$fooFooBehaviorName = 'FooFooBehaviorName';
		$this->assertNull($this->component->asa($fooBehaviorName));
		$this->assertNull($this->component->asa($barBehaviorName));
		$this->assertNull($this->component->asa($fooBarBehaviorName));
		$this->assertNull($this->component->asa($preBarBehaviorName));

		$this->component->attachBehaviors([$fooFooBehaviorName => new FooFooBehavior, $barBehaviorName => new BarBehavior, $preBarBehaviorName => new PreBarBehavior]);

		$this->assertNull($this->component->asa($fooBehaviorName));
		$this->assertNotNull($this->component->asa($fooFooBehaviorName));
		$this->assertNotNull($this->component->asa($barBehaviorName));
		$this->assertNull($this->component->asa($fooBarBehaviorName));
		$this->assertNotNull($this->component->asa($preBarBehaviorName));

		$this->assertTrue($this->component->isa(FooFooBehavior::class));
		$this->assertTrue($this->component->isa(FooBehavior::class));
		$this->assertTrue($this->component->isa(BarBehavior::class));
		$this->assertTrue($this->component->isa(PreBarBehavior::class));
		$this->assertFalse($this->component->isa(FooBarBehavior::class));

		$this->component->detachBehaviors([$fooFooBehaviorName => new FooFooBehavior, $barBehaviorName => new BarBehavior]);

		$this->assertNull($this->component->asa($fooBehaviorName));
		$this->assertNull($this->component->asa($fooFooBehaviorName));
		$this->assertNull($this->component->asa($barBehaviorName));
		$this->assertNull($this->component->asa($fooBarBehaviorName));
		$this->assertNotNull($this->component->asa($preBarBehaviorName));

		$this->assertFalse($this->component->isa(FooFooBehavior::class));
		$this->assertFalse($this->component->isa(FooBehavior::class));
		$this->assertFalse($this->component->isa(BarBehavior::class));
		$this->assertFalse($this->component->isa(FooBarBehavior::class));
		$this->assertTrue($this->component->isa(PreBarBehavior::class));



		//	testing if we can detachBehaviors just by the name of the behavior instead of an array of the behavior
		$this->component->attachBehaviors([$fooFooBehaviorName => new FooFooBehavior, $barBehaviorName => new BarBehavior]);

		$this->assertTrue($this->component->isa(FooBehavior::class));
		$this->assertTrue($this->component->isa(BarBehavior::class));

		$this->component->detachBehaviors([$fooFooBehaviorName, $barBehaviorName]);

		$this->assertNull($this->component->asa($fooBehaviorName));
		$this->assertNull($this->component->asa($fooFooBehaviorName));
		$this->assertNull($this->component->asa($barBehaviorName));
		$this->assertNull($this->component->asa($fooBarBehaviorName));

		$this->assertFalse($this->component->isa(FooFooBehavior::class));
		$this->assertFalse($this->component->isa(FooBehavior::class));
		$this->assertFalse($this->component->isa(BarBehavior::class));
		$this->assertFalse($this->component->isa(FooBarBehavior::class));
	}


	public function testClearBehaviors()
	{
		$fooBehaviorName = 'FooBehaviorName';
		$barBehaviorName = 'BarBehaviorName';
		$fooBarBehaviorName = 'FooBarBehaviorName';
		$preBarBehaviorName = 'PreBarBehaviorName';
		$fooFooBehaviorName = 'FooFooBehaviorName';
		
		$this->assertNull($this->component->asa($fooBehaviorName));
		$this->assertNull($this->component->asa($barBehaviorName));
		$this->assertNull($this->component->asa($fooBarBehaviorName));
		$this->assertNull($this->component->asa($preBarBehaviorName));

		$this->component->attachBehaviors([$fooFooBehaviorName => new FooFooBehavior, $barBehaviorName => new BarBehavior, $preBarBehaviorName => new PreBarBehavior]);

		$this->assertNull($this->component->asa($fooBehaviorName));
		$this->assertNotNull($this->component->asa($fooFooBehaviorName));
		$this->assertNotNull($this->component->asa($barBehaviorName));
		$this->assertNull($this->component->asa($fooBarBehaviorName));
		$this->assertNotNull($this->component->asa($preBarBehaviorName));

		$this->component->clearBehaviors();

		$this->assertNull($this->component->asa($fooBehaviorName));
		$this->assertNull($this->component->asa($barBehaviorName));
		$this->assertNull($this->component->asa($fooBarBehaviorName));
		$this->assertNull($this->component->asa($preBarBehaviorName));
	}

	public function testEnableDisableBehavior()
	{
		$behaviorName = 'FooBehaviorName';
		
		$this->assertFalse($this->component->enableBehavior($behaviorName));
		$this->assertFalse($this->component->disableBehavior($behaviorName));
		$this->assertEquals(0, $this->component->onMyEvent->getCount());
		
		try {
			$this->component->faaEverMore(true, true);
			$this->fail('TUnknownMethodException not raised trying to execute a undefined class method');
		} catch (TUnknownMethodException $e) {
		}
		
		// *** Test TBehavior
		
		$fooB = new FooBehaviorWithEvents();
		try { // set name without owner
			$fooB->setName('initialName');
		} catch(TInvalidOperationException $e) {
			$this->fail("TBehavior wasn't able to set the name. \n" . $e->getErrorMessage());
		}
		$eventsLog = $fooB->eventsLog();
		$this->assertEquals(1, count($eventsLog), "TBehavior::eventsLog not returning the 1 test event with handlers");
		$this->assertEquals(2, count($eventsLog['onMyEvent']), "TBehavior::eventsLog not returning the 2 event handlers");
		$this->assertEquals('fooEventHandler', $eventsLog['onMyEvent'][0]);
		$this->assertInstanceOf(\Closure::class, $eventsLog['onMyEvent'][1]);
		$this->assertNull($fooB->getOwner());
		$this->assertEquals([], $fooB->getOwners());
		$this->assertFalse($fooB->hasOwner());
		$this->assertFalse($fooB->isOwner($this->component));

		//  Attach TBehavior
		
		$this->component->attachBehavior($behaviorName, $fooB);
		$this->assertEquals($this->component, $fooB->getOwner());
		$this->assertEquals([$this->component], $fooB->getOwners());
		$this->assertTrue($fooB->hasOwner());
		$this->assertTrue($fooB->isOwner($this->component));
		$this->assertFalse($fooB->isOwner(new TComponent()));
		$this->assertEquals(2, $this->component->onMyEvent->getCount(), "TBehavior not adding events to the owner properly.");
		$fooB->syncEventHandlers(null, null);
		$this->assertEquals(0, $this->component->onMyEvent->getCount());
		$fooB->syncEventHandlers(null, false);
		$this->assertEquals(2, $this->component->onMyEvent->getCount());
		
		try {// set name after attaching to owner error.
			$fooB->setName('initialName');
			$this->fail("TInvalidOperationException was not thrown.  Names can't change after they have owners.");
		} catch(TInvalidOperationException $e) {
		}
		try { // set the when the name doesn't change, no error
			$fooB->setName($behaviorName);
		} catch(TInvalidOperationException $e) {
			$this->fail("TBehavior has an error when setting the name to the same set name (in the owner) and shouldn't have an error. \n" . $e->getErrorMessage());
		}

		$this->assertTrue($this->component->isa(FooBehavior::class));
		try {
			$this->assertTrue($this->component->faaEverMore(true, true));
		} catch (TApplicationException $e) {
			$this->fail('TApplicationException raised while trying to execute a behavior class method');
		}
		$fooB->syncEventHandlers(null, null);
		$this->assertEquals(0, $this->component->onMyEvent->getCount());
		$fooB->syncEventHandlers(null, false);
		$this->assertEquals(2, $this->component->onMyEvent->getCount());

		//Test upper case name as well.
		$this->assertTrue($this->component->disableBehavior(strtoupper($behaviorName)));
		$this->assertEquals(0, $this->component->onMyEvent->getCount());
		$this->assertFalse($this->component->isa(FooBehavior::class));
		$this->assertEquals(0, $this->component->onMyEvent->getCount());
		$fooB->syncEventHandlers(null, true);
		$this->assertEquals(2, $this->component->onMyEvent->getCount());
		$fooB->syncEventHandlers(null, false);
		$this->assertEquals(0, $this->component->onMyEvent->getCount());

		try {
			$this->component->faaEverMore(true, true);
			$this->fail('TUnknownMethodException not raised trying to execute a undefined class method');
		} catch (TUnknownMethodException $e) {
		}
		
		//Test upper case name as well.
		$this->assertTrue($this->component->enableBehavior(strtoupper($behaviorName)));
		$this->assertTrue($this->component->isa(FooBehavior::class));
		$this->assertEquals(2, $this->component->onMyEvent->getCount());

		try {
			$this->assertTrue($this->component->faaEverMore(true, true));
		} catch (TApplicationException $e) {
			$this->fail('TApplicationException raised while trying to execute a behavior class method');
		}
		
		//  *** Test TClassBehavior
		
		$className = 'BarClassBehaviorName'; //Name of the TBehavior test object.
		
		$this->assertFalse($this->component->enableBehavior($className));
		$this->assertEquals(2, $this->component->onMyEvent->getCount());
		$this->assertFalse($this->component->disableBehavior($className));
		$this->assertEquals(2, $this->component->onMyEvent->getCount());

		try {
			$this->component->moreFunction(true, true);
			$this->fail('TUnknownMethodException not raised trying to execute an undefined class method');
		} catch (TUnknownMethodException $e) {
		}
		
		// instance
	
		$classBehavior = new BarClassBehaviorWithEvents();
		try { // set name without owner
			$classBehavior->setName('initialName');
		} catch(TInvalidOperationException $e) {
			$this->fail("TClassBehavior wasn't able to set the name. \n" . $e->getErrorMessage());
		}
		$this->assertEquals([], $classBehavior->getOwners());
		$this->assertFalse($classBehavior->hasOwner());
		$this->assertFalse($classBehavior->isOwner($this->component));
		
		// Attach
	
		$this->component->attachClassBehavior($className, $classBehavior);
		$this->assertEquals([$this->component], $classBehavior->getOwners());
		$this->assertTrue($classBehavior->hasOwner());
		$this->assertTrue($classBehavior->isOwner($this->component));
		$this->assertFalse($classBehavior->isOwner(new TComponent()));
		
		try {// set name after attaching to owner error.
			$classBehavior->setName('initialName');
			$this->fail("TInvalidOperationException was not thrown.  Names can't change after they have owners.");
		} catch(TInvalidOperationException $e) {
		}
		try { // set the when the name doesn't change, no error
			$classBehavior->setName($className);
		} catch(TInvalidOperationException $e) {
			$this->fail("TBehavior has an error when setting the name to the same set name (in the owner) and shouldn't have an error. \n" . $e->getErrorMessage());
		}
		
		$this->tearDownScripts[] = function() use ($className) {$this->component->detachClassBehavior($className);};
		$this->assertEquals([$this->component], $classBehavior->getOwners());

		$this->assertInstanceOf(BarClassBehaviorWithEvents::class, $this->component->asa($className));
		$this->assertEquals(4, $this->component->onMyEvent->getCount(), "TClassBehavior did not attach its handlers.");
		$this->assertTrue($this->component->enableBehavior($className));
		$classBehavior->syncEventHandlers(null, null);
		$this->assertEquals(2, $this->component->onMyEvent->getCount());
		$classBehavior->syncEventHandlers(null, false);
		$this->assertEquals(4, $this->component->onMyEvent->getCount());
		$this->assertEquals(4, $this->component->onMyEvent->getCount());
		$this->assertTrue($this->component->disableBehavior($className));
		$this->assertEquals(2, $this->component->onMyEvent->getCount());
		$classBehavior->syncEventHandlers(null, true);
		$this->assertEquals(4, $this->component->onMyEvent->getCount());
		$classBehavior->syncEventHandlers(null, false);
		$this->assertEquals(2, $this->component->onMyEvent->getCount());

		try {
			$this->assertTrue($this->component->moreFunction(true, true));
			$this->fail('TUnknownMethodException not raised while trying to execute a disabled behavior class method');
		} catch (TUnknownMethodException $e) {
		}
		$this->assertTrue($this->component->enableBehavior($className));
		$this->assertEquals(4, $this->component->onMyEvent->getCount());
		
		{
			$this->component->disableBehaviors();
			$this->assertEquals(0, $this->component->onMyEvent->getCount(), "The behaviors were not turned off when the component behaviors were flagged as off.");
			
			$this->assertTrue($this->component->disableBehavior($behaviorName));
			$this->assertEquals(0, $this->component->onMyEvent->getCount());
			$this->assertTrue($this->component->enableBehavior($behaviorName));
			$this->assertEquals(0, $this->component->onMyEvent->getCount());
			
			$this->assertTrue($this->component->disableBehavior($className));
			$this->assertEquals(0, $this->component->onMyEvent->getCount());
			$this->assertTrue($this->component->enableBehavior($className));
			$this->assertEquals(0, $this->component->onMyEvent->getCount());
		}
		
		$this->component->enableBehaviors();
		$this->assertEquals(4, $this->component->onMyEvent->getCount());
		$this->component->disableBehaviors();
		$this->assertTrue($this->component->disableBehavior($behaviorName));
		$this->assertTrue($this->component->disableBehavior($className));
		$this->component->enableBehaviors();
		$this->assertEquals(0, $this->component->onMyEvent->getCount());
		$this->component->disableBehaviors();
		$this->assertTrue($this->component->enableBehavior($behaviorName));
		$this->component->enableBehaviors();
		$this->assertEquals(2, $this->component->onMyEvent->getCount());

		$this->assertTrue($this->component->enableBehavior($className));
		$this->assertEquals(4, $this->component->onMyEvent->getCount());
		
		{	// Test RetainDisabledHandlers = false on TBehavior and TClassBehavior
			$this->assertTrue($this->component->disableBehavior($behaviorName));
			$this->assertTrue($this->component->disableBehavior($className));
			$this->assertEquals(0, $this->component->onMyEvent->getCount());
			$fooB->setRetainDisabledHandlers(true);
			$this->assertTrue($fooB->getRetainDisabledHandlers());
			$this->assertEquals(2, $this->component->onMyEvent->getCount());
			$fooB->setRetainDisabledHandlers(null);
			$this->assertNull($fooB->getRetainDisabledHandlers());
			$fooB->setRetainDisabledHandlers('null');
			$this->assertNull($fooB->getRetainDisabledHandlers());
			$fooB->setRetainDisabledHandlers(0);
			$this->assertNull($fooB->getRetainDisabledHandlers());
			$fooB->setRetainDisabledHandlers('0');
			$this->assertNull($fooB->getRetainDisabledHandlers());
			$this->assertEquals(0, $this->component->onMyEvent->getCount());
			$fooB->setRetainDisabledHandlers(true);
			$this->assertEquals(2, $this->component->onMyEvent->getCount());
			$fooB->setRetainDisabledHandlers('false');
			$this->assertFalse($fooB->getRetainDisabledHandlers());
			$this->assertEquals(0, $this->component->onMyEvent->getCount());
			$fooB->setRetainDisabledHandlers(true);
			$this->assertEquals(2, $this->component->onMyEvent->getCount());
			$classBehavior->setRetainDisabledHandlers(true);
			$this->assertEquals(4, $this->component->onMyEvent->getCount());
			$classBehavior->setRetainDisabledHandlers(null);
			$this->assertEquals(2, $this->component->onMyEvent->getCount());
			$classBehavior->setRetainDisabledHandlers(true);
			$this->assertEquals(4, $this->component->onMyEvent->getCount());
			$this->assertTrue($this->component->enableBehavior($behaviorName));
			$this->assertTrue($this->component->enableBehavior($className));
			$this->assertEquals(4, $this->component->onMyEvent->getCount());
			$fooB->setRetainDisabledHandlers(null);
			$this->assertEquals(2, $this->component->onMyEvent->getCount());
			$fooB->setRetainDisabledHandlers(false);
			$this->assertEquals(4, $this->component->onMyEvent->getCount());
			$fooB->setRetainDisabledHandlers(true);
			$this->assertEquals(4, $this->component->onMyEvent->getCount());
			
			$this->assertTrue($this->component->disableBehavior($behaviorName));
			$this->assertTrue($this->component->disableBehavior($className));
			$this->assertEquals(4, $this->component->onMyEvent->getCount());
			$this->component->disableBehaviors();
			$this->assertEquals(4, $this->component->onMyEvent->getCount());
			$this->assertTrue($this->component->enableBehavior($behaviorName));
			$this->assertTrue($this->component->enableBehavior($className));
			$this->assertEquals(4, $this->component->onMyEvent->getCount());
			$this->assertTrue($this->component->disableBehavior($behaviorName));
			$this->assertTrue($this->component->disableBehavior($className));
			$this->assertEquals(4, $this->component->onMyEvent->getCount());
			$this->component->enableBehaviors();
			$this->assertEquals(4, $this->component->onMyEvent->getCount());
			$this->assertTrue($this->component->enableBehavior($behaviorName));
			$this->assertTrue($this->component->enableBehavior($className));
			$this->assertEquals(4, $this->component->onMyEvent->getCount());
		}
		
	}


	public function testCall_ForBehaviorFunction()
	{
		$fooClassBehaviorName = 'FooClassBehaviorName';
		$fooBarBehaviorName = 'FooBarBehaviorName';
		$this->component->attachBehavior($fooBarBehaviorName, $behavior = new FooBarBehavior);
		$this->component->attachClassBehavior($fooClassBehaviorName, $classbehavior = new FooClassBehavior);

		$this->tearDownScripts[$fooClassBehaviorName] = function() use ($fooClassBehaviorName) {NewComponent::detachClassBehavior($fooClassBehaviorName);};

		// Test the Class Methods
		$this->assertEquals(12, $this->component->faaEverMore(3, 4));

		// Check that the called object is shifted in front of the array of a class behavior call
		$this->assertEquals($this->component, $this->component->getLastClassObject());


		//Test the FooBarBehavior
		$this->assertEquals(27, $this->component->moreFunction(3, 3));

		$this->assertTrue($this->component->disableBehavior($fooBarBehaviorName));
		try {
			$this->assertNull($this->component->moreFunction(3, 4));
			$this->fail('TUnknownMethodException not raised trying to execute a disabled behavior');
		} catch (TUnknownMethodException $e) {
		}
		$this->assertTrue($this->component->enableBehavior($fooBarBehaviorName));

		// Test the global event space, this should work and return false because no function implements these methods
		$this->assertNull($this->component->fxSomeUndefinedGlobalEvent());
		$this->assertNull($this->component->dySomeUndefinedIntraObjectEvent());

		$this->component->detachClassBehavior($fooClassBehaviorName);
		unset($this->tearDownScripts[$fooClassBehaviorName]);


		// test object instance behaviors implemented through class-wide behaviors
		$this->component->attachClassBehavior('FooFooBehaviorAsClass', 'FooFooBehavior');

		$component = new NewComponent;

		$this->assertEquals(5, $this->component->faafaaEverMore(3, 4));
		$this->assertEquals(10, $component->faafaaEverMore(6, 8));

		$this->component->detachClassBehavior('FooFooBehaviorAsClass');
		$component->unlisten();
		$component = null;

		try {
			$this->component->faafaaEverMore(3, 4);
			$this->fail('TUnknownMethodException not raised trying to execute a disabled behavior');
		} catch (TUnknownMethodException $e) {
		}



		// make a call to an unpatched fx and dy call so that it's passed through to the __dycall function
		$dynamicComponent = new DynamicCallComponent;

		$this->assertNull($dynamicComponent->fxUndefinedEvent());
		$this->assertNull($dynamicComponent->dyUndefinedEvent());

		//This tests the dynamic __dycall function
		$this->assertEquals(1024, $dynamicComponent->dyPowerFunction(2, 10));
		$this->assertEquals(5, $dynamicComponent->dyDivisionFunction(10, 2));

		$this->assertEquals(2048, $dynamicComponent->fxPowerFunction(2, 10));
		$this->assertEquals(10, $dynamicComponent->fxDivisionFunction(10, 2));

		$dynamicComponent->unlisten();
	}

	
	public function testCallStatic_singleton()
	{
		$app = Prado::getApplication();
		try {
			TApplication::aStaticMethod(3);
			self::fail("failed to raise TUnknownMethodException when calling an undefined static method.");
		} catch(TUnknownMethodException $e) {
		}
		
		$behaviorName = 'aStaticMethodBehavior';
		$app->attachBehavior($behaviorName, NewComponentStaticBehavior::class);
		
		self::assertEquals(6, TApplication::aStaticMethod(3));
			
		// Disable Behavior has no singleton behavior static function
		$app->disableBehavior($behaviorName);
		try {
			TApplication::aStaticMethod(3);
			self::fail("failed to raise TUnknownMethodException when calling an undefined static method.");
		} catch(TUnknownMethodException $e) {
		}
		$app->enableBehavior($behaviorName);
		self::assertEquals(8, TApplication::aStaticMethod(4));
		
		// Disable Behaviors of application, has no singleton behavior static function
		$app->disableBehaviors();
		try {
			TApplication::aStaticMethod(3);
			self::fail("failed to raise TUnknownMethodException when calling an undefined static method.");
		} catch(TUnknownMethodException $e) {
		}
		$app->enableBehaviors();
		self::assertEquals(10, TApplication::aStaticMethod(5));
		
		$app->detachBehavior($behaviorName);
		
		try {
			TApplication::aStaticMethod(3);
			self::fail("failed to raise TUnknownMethodException when calling an undefined static method.");
		} catch(TUnknownMethodException $e) {
		}
	}
	
	public function testCallStatic_classBehavior()
	{
		try {
			NewComponent::aStaticMethod(3);
			self::fail("failed to raise TUnknownMethodException when calling an undefined static method.");
		} catch(TUnknownMethodException $e) {
		}
		
		$behaviorName = 'aStaticMethodBehavior';
		
		// Class Behavior as String
		NewComponent::attachClassBehavior($behaviorName, NewComponentStaticClassBehavior::class);
		$this->tearDownScripts[$behaviorName] = function() use ($behaviorName) { NewComponent::detachClassBehavior($behaviorName);};
		
		self::assertEquals(9, NewComponent::aStaticMethod(3));
		NewComponent::detachClassBehavior($behaviorName);
			
		// Class Behavior as Array
		NewComponent::attachClassBehavior($behaviorName, ['class' => NewComponentStaticClassBehavior::class]);
		
		self::assertEquals(9, NewComponent::aStaticMethod(3));
		NewComponent::detachClassBehavior($behaviorName);
		
		// Class Behavior as instanced class
		NewComponent::attachClassBehavior($behaviorName, $behavior = new NewComponentStaticClassBehavior());
		
		self::assertEquals(9, NewComponent::aStaticMethod(3));
		$behavior->setEnabled(false);
		try {
			NewComponent::aStaticMethod(3);
			self::fail("failed to raise TUnknownMethodException when calling an undefined static method.");
		} catch(TUnknownMethodException $e) {
		}
		$behavior->setEnabled(true);
		self::assertEquals(16, NewComponent::aStaticMethod(4));
			
		NewComponent::detachClassBehavior($behaviorName);
			
			
		unset($this->tearDownScripts[$behaviorName]);
	}

	public function testHasProperty()
	{
		$this->assertTrue($this->component->hasProperty('Text'), "Component hasn't property Text");
		$this->assertTrue($this->component->hasProperty('text'), "Component hasn't property text");
		$this->assertFalse($this->component->hasProperty('Caption'), "Component has property Caption");

		$this->assertTrue($this->component->hasProperty('ColorAttribute'), "Component hasn't property JsColorAttribute");
		$this->assertTrue($this->component->hasProperty('colorattribute'), "Component hasn't property JsColorAttribute");
		$this->assertFalse($this->component->canGetProperty('PastelAttribute'), "Component has property JsPastelAttribute");

		$this->assertTrue($this->component->hasProperty('JSColorAttribute'), "Component hasn't property JsColorAttribute");
		$this->assertTrue($this->component->hasProperty('jscolorattribute'), "Component hasn't property JsColorAttribute");
		$this->assertFalse($this->component->hasProperty('jsPastelAttribute'), "Component has property JsPastelAttribute");

		$this->assertFalse($this->component->hasProperty('Excitement'), "Component has property Excitement");
		$this->component->attachBehavior('ExcitementPropBehavior', new BehaviorTestBehavior);
		$this->assertTrue($this->component->hasProperty('Excitement'), "Component hasn't property Excitement");
		$this->component->disableBehaviors();
		$this->assertFalse($this->component->hasProperty('Excitement'), "Component has property Excitement");
		$this->component->enableBehaviors();
		$this->assertTrue($this->component->hasProperty('Excitement'), "Component hasn't property Excitement");
		$this->component->disableBehavior('ExcitementPropBehavior');
		$this->assertFalse($this->component->hasProperty('Excitement'), "Component has property Excitement");
		$this->component->enableBehavior('ExcitementPropBehavior');
		$this->assertTrue($this->component->hasProperty('Excitement'), "Component hasn't property Excitement");

		$this->component->detachBehavior('ExcitementPropBehavior');

		$this->assertFalse($this->component->hasProperty('Excitement'), "Component has property Excitement");
	}

	public function testCanGetProperty()
	{
		$this->assertTrue($this->component->canGetProperty('Text'));
		$this->assertTrue($this->component->canGetProperty('text'));
		$this->assertFalse($this->component->canGetProperty('Caption'));

		$this->assertTrue($this->component->canGetProperty('ColorAttribute'));
		$this->assertTrue($this->component->canGetProperty('colorattribute'));
		$this->assertFalse($this->component->canGetProperty('PastelAttribute'));

		$this->assertTrue($this->component->canGetProperty('JSColorAttribute'));
		$this->assertTrue($this->component->canGetProperty('jscolorattribute'));
		$this->assertFalse($this->component->canGetProperty('jsPastelAttribute'));


		$this->assertFalse($this->component->canGetProperty('Excitement'), "Component has property Excitement");
		$this->component->attachBehavior('ExcitementPropBehavior', new BehaviorTestBehavior);
		$this->assertTrue($this->component->canGetProperty('Excitement'), "Component hasn't property Excitement");
		$this->component->disableBehaviors();
		$this->assertFalse($this->component->canGetProperty('Excitement'), "Component has property Excitement");
		$this->component->enableBehaviors();
		$this->assertTrue($this->component->canGetProperty('Excitement'), "Component hasn't property Excitement");
		$this->component->disableBehavior('ExcitementPropBehavior');
		$this->assertFalse($this->component->canGetProperty('Excitement'), "Component has property Excitement");
		$this->component->enableBehavior('ExcitementPropBehavior');
		$this->assertTrue($this->component->canGetProperty('Excitement'), "Component hasn't property Excitement");

		$this->component->detachBehavior('ExcitementPropBehavior');

		$this->assertFalse($this->component->canGetProperty('Excitement'), "Component has property Excitement");
	}

	public function testCanSetProperty()
	{
		$this->assertTrue($this->component->canSetProperty('Text'));
		$this->assertTrue($this->component->canSetProperty('text'));
		$this->assertFalse($this->component->canSetProperty('Caption'));

		$this->assertTrue($this->component->canSetProperty('ColorAttribute'));
		$this->assertTrue($this->component->canSetProperty('colorattribute'));
		$this->assertFalse($this->component->canSetProperty('PastelAttribute'));

		$this->assertTrue($this->component->canSetProperty('JSColorAttribute'));
		$this->assertTrue($this->component->canSetProperty('jscolorattribute'));
		$this->assertFalse($this->component->canSetProperty('jsPastelAttribute'));

		$this->assertFalse($this->component->canSetProperty('Excitement'), "Component has property Excitement");
		$this->component->attachBehavior('ExcitementPropBehavior', new BehaviorTestBehavior);
		$this->assertTrue($this->component->canSetProperty('Excitement'), "Component hasn't property Excitement");
		$this->component->disableBehaviors();
		$this->assertFalse($this->component->canSetProperty('Excitement'), "Component has property Excitement");
		$this->component->enableBehaviors();
		$this->assertTrue($this->component->canSetProperty('Excitement'), "Component hasn't property Excitement");
		$this->component->disableBehavior('ExcitementPropBehavior');
		$this->assertFalse($this->component->canSetProperty('Excitement'), "Component has property Excitement");
		$this->component->enableBehavior('ExcitementPropBehavior');
		$this->assertTrue($this->component->canSetProperty('Excitement'), "Component hasn't property Excitement");

		$this->component->detachBehavior('ExcitementPropBehavior');
	}

	public function testGetProperty()
	{
		$this->assertTrue('default' === $this->component->Text);
		try {
			$value2 = $this->component->Caption;
			$this->fail('exception not raised when getting undefined property');
		} catch (TInvalidOperationException $e) {
		}

		$this->assertTrue($this->component->OnMyEvent instanceof TPriorityList);
		try {
			$value2 = $this->component->onUndefinedEvent;
			$this->fail('exception not raised when getting undefined property');
		} catch (TInvalidOperationException $e) {
		}

		//Without the function parenthesis, the function is _not_ called but the __get
		//	method is called and the global events (list) are accessed
		$this->assertTrue($this->component->fxAttachClassBehavior instanceof TPriorityList);
		$this->assertTrue($this->component->fxDetachClassBehavior instanceof TPriorityList);

		// even undefined global events have a list as every object is able to access every event
		$this->assertTrue($this->component->fxUndefinedEvent instanceof TPriorityList);


		// Test the behaviors within the __get function
		$this->component->enableBehaviors();

		try {
			$value2 = $this->component->Excitement;
			$this->fail('exception not raised when getting undefined property');
		} catch (TInvalidOperationException $e) {
		}

		$this->component->attachBehavior('BehaviorTestBehavior', $behavior = new BehaviorTestBehavior);
		$this->assertEquals('faa', $this->component->Excitement);

		$this->component->disableBehaviors();

		try {
			$this->assertEquals('faa', $this->component->Excitement);
			$this->fail('exception not raised when getting undefined property');
		} catch (TInvalidOperationException $e) {
		}

		$this->component->enableBehaviors();
		$this->assertEquals('faa', $this->component->getExcitement());

		$this->component->disableBehavior('BehaviorTestBehavior');

		$this->assertEquals($behavior, $this->component->BehaviorTestBehavior);
		$this->assertEquals($behavior, $this->component->behaviortestbehavior);
		$this->assertEquals($behavior, $this->component->BEHAVIORTESTBEHAVIOR);
		try {
			$behavior = $this->component->BehaviorTestBehavior2;
			$this->fail('exception not raised when getting undefined property');
		} catch (TInvalidOperationException $e) {
		}

		try {
			$this->assertEquals('faa', $this->component->Excitement);
			$this->fail('exception not raised when getting undefined property');
		} catch (TInvalidOperationException $e) {
		}
		$this->component->enableBehavior('BehaviorTestBehavior');
		$this->assertEquals('faa', $this->component->getExcitement());


		// behaviors allow on and fx events to be passed through.
		$this->assertTrue($this->component->onBehaviorEvent instanceof TPriorityList);
	}

	public function testSetProperty()
	{
		$value = 'new value';
		$this->component->Text = $value;
		$text = $this->component->Text;
		$this->assertTrue($value === $this->component->Text);
		try {
			$this->component->NewMember = $value;
			$this->fail('exception not raised when setting undefined property');
		} catch (TInvalidOperationException $e) {
		}

		// Test get only properties is a set function
		try {
			$this->component->ReadOnlyProperty = 'setting read only';
			$this->fail('a property without a set function was set to a new value without error');
		} catch (TInvalidOperationException $e) {
		}

		try {
			$this->component->ReadOnlyJsProperty = 'jssetting read only';
			$this->fail('a js property without a set function was set to a new value without error');
		} catch (TInvalidOperationException $e) {
		}

		try {
			$this->component->JsReadOnlyJsProperty = 'jssetting read only';
			$this->fail('a js property without a set function was set to a new value without error');
		} catch (TInvalidOperationException $e) {
		}

		$this->assertEquals(0, $this->component->getEventHandlers('onMyEvent')->getCount());
		$this->component->onMyEvent = [$this->component, 'myEventHandler'];
		$this->assertEquals(1, $this->component->getEventHandlers('onMyEvent')->getCount());
		$this->component->onMyEvent[] = [$this->component, 'Object.myEventHandler'];
		$this->assertEquals(2, $this->component->getEventHandlers('onMyEvent')->getCount());
		$c1 = new NewComponent();
		$c2 = new NewComponent();
		$this->component->onMyEvent = [[$c1, 'myEventHandler'], [$c2, 'myEventHandler']];
		$this->assertEquals(4, $this->component->getEventHandlers('onMyEvent')->getCount());

		$this->component->getEventHandlers('onMyEvent')->clear();

		// Test the behaviors within the __get function
		$this->component->enableBehaviors();

		try {
			$this->component->Excitement = 'laa';
			$this->fail('exception not raised when getting undefined property');
		} catch (TInvalidOperationException $e) {
		}

		$this->component->attachBehavior('BehaviorTestBehavior', $behavior1 = new BehaviorTestBehavior);
		$this->component->Excitement = 'laa';
		$this->assertEquals('laa', $this->component->Excitement);
		$this->assertEquals('sol', $this->component->Excitement = 'sol');


		$this->component->disableBehaviors();

		try {
			$this->component->Excitement = false;
			$this->assertEquals(false, $this->component->Excitement);
			$this->fail('exception not raised when getting undefined property');
		} catch (TInvalidOperationException $e) {
		}

		$this->component->enableBehaviors();
		$this->component->Excitement = 'faa';
		$this->assertEquals('faa', $this->component->getExcitement());

		$this->component->disableBehavior('BehaviorTestBehavior');

		try {
			$this->component->Excitement = false;
			$this->assertEquals(false, $this->component->Excitement);
			$this->fail('exception not raised when getting undefined property');
		} catch (TInvalidOperationException $e) {
		}
		$this->component->enableBehavior('BehaviorTestBehavior');
		$this->component->Excitement = 'sol';
		$this->assertEquals('sol', $this->component->Excitement);


		$this->component->attachBehavior('BehaviorTestBehavior2', $behavior2 = new BehaviorTestBehavior);

		$this->assertEquals('sol', $this->component->Excitement);
		$this->assertEquals('faa', $behavior2->Excitement);

		// this sets Excitement for both because they are not uniquely named
		$this->component->Excitement = 'befaad';

		$this->assertEquals('befaad', $this->component->Excitement);
		$this->assertEquals('befaad', $behavior1->Excitement);
		$this->assertEquals('befaad', $behavior2->Excitement);


		$this->component->detachBehavior('BehaviorTestBehavior2');

		// behaviors allow on and fx events to be passed through.
		$this->assertTrue($this->component->BehaviorTestBehavior->onBehaviorEvent instanceof TPriorityList);

		$this->assertEquals(0, $this->component->BehaviorTestBehavior->getEventHandlers('onBehaviorEvent')->getCount());
		$this->component->onBehaviorEvent = [$this->component, 'myEventHandler'];
		$this->assertEquals(1, $this->component->BehaviorTestBehavior->getEventHandlers('onBehaviorEvent')->getCount());
		$this->component->onBehaviorEvent[] = [$this->component, 'Object.myEventHandler'];
		$this->assertEquals(2, $this->component->BehaviorTestBehavior->getEventHandlers('onBehaviorEvent')->getCount());

		$this->component->BehaviorTestBehavior->getEventHandlers('onBehaviorEvent')->clear();
	}


	public function testIsSetFunction()
	{
		$this->assertTrue(isset($this->component->fxAttachClassBehavior));
		$this->component->unlisten();

		$this->assertFalse(isset($this->component->onMyEvent));
		$this->assertFalse(isset($this->component->undefinedEvent));
		$this->assertFalse(isset($this->component->fxAttachClassBehavior));

		$this->assertFalse(isset($this->component->BehaviorTestBehavior));
		$this->assertFalse(isset($this->component->onBehaviorEvent));

		$this->component->attachBehavior('BehaviorTestBehavior', new BehaviorTestBehavior());

		$this->assertTrue(isset($this->component->behaviortestbehavior));
		$this->assertTrue(isset($this->component->BehaviorTestBehavior));
		$this->assertTrue(isset($this->component->BEHAVIORTESTBEHAVIOR));
		$this->assertFalse(isset($this->component->onBehaviorEvent));

		$this->component->attachEventHandler('onBehaviorEvent', 'foo');
		$this->assertTrue(isset($this->component->onBehaviorEvent));

		$this->component->attachEventHandler('onMyEvent', 'foo');
		$this->assertTrue(isset($this->component->onMyEvent));

		$this->assertTrue(isset($this->component->Excitement));
		$this->component->Excitement = null;
		$this->assertFalse(isset($this->component->Excitement));
		$this->assertFalse(isset($this->component->UndefinedBehaviorProperty));
	}


	public function testUnsetFunction()
	{
		$this->assertEquals('default', $this->component->getText());
		unset($this->component->Text);
		$this->assertNull($this->component->getText());

		unset($this->component->UndefinedProperty);

		// object events
		$this->assertEquals(0, $this->component->onMyEvent->Count);
		$this->component->attachEventHandler('onMyEvent', 'foo');
		$this->assertEquals(1, $this->component->onMyEvent->Count);
		unset($this->component->onMyEvent);
		$this->assertEquals(0, $this->component->onMyEvent->Count);

		//global events
		$this->assertEquals(1, $this->component->fxAttachClassBehavior->Count);
		$component = new NewComponent();
		$this->assertEquals(2, $this->component->fxAttachClassBehavior->Count);
		unset($this->component->fxAttachClassBehavior);
		$this->assertEquals(1, $this->component->fxAttachClassBehavior->Count);
		try {
			unset($this->component->fxAttachClassBehaviors);
			$this->fail('TInvalidDataValueException not raised when unsetting an fxEvent that is not attached');
		} catch (Prado\Exceptions\TInvalidDataValueException $e) {
		}
		$this->component->fxAttachClassBehavior[] = [$this->component, 'fxAttachClassBehavior'];
		$this->assertEquals(2, $this->component->fxAttachClassBehavior->Count);
		unset($this->component->fxAttachClassBehavior);

		// retain the other object event
		$this->assertEquals(1, $this->component->fxAttachClassBehavior->Count);
		$component->unlisten();

		try {
			unset($this->component->Object);
			$this->fail('TInvalidOperationException not raised when unsetting get only property');
		} catch (Prado\Exceptions\TInvalidOperationException $e) {
		}
		$behaviorTestBehaviorName = 'BehaviorTestBehaviorName';
		$this->component->attachBehavior($behaviorTestBehaviorName, new BehaviorTestBehavior);
		$this->assertTrue($this->component->asa($behaviorTestBehaviorName) instanceof BehaviorTestBehavior);
		$this->assertFalse($this->component->asa('BehaviorTestBehavior2') instanceof BehaviorTestBehavior);

		$this->assertEquals('faa', $this->component->Excitement);
		unset($this->component->Excitement);
		$this->assertNull($this->component->Excitement);
		$this->component->Excitement = 'sol';
		$this->assertEquals('sol', $this->component->Excitement);

		// Test the disabling of unset within behaviors
		$this->component->disableBehaviors();
		unset($this->component->Excitement);
		$this->component->enableBehaviors();
		// This should still be 'sol'  because the unset happened inside behaviors being disabled
		$this->assertEquals('sol', $this->component->Excitement);
		$this->component->disableBehavior($behaviorTestBehaviorName);
		unset($this->component->Excitement);
		$this->component->enableBehavior($behaviorTestBehaviorName);
		$this->assertEquals('sol', $this->component->Excitement);

		unset($this->component->Excitement);
		$this->assertNull($this->component->Excitement);

		try {
			unset($this->component->ReadOnly);
			$this->fail('TInvalidOperationException not raised when unsetting get only property');
		} catch (TInvalidOperationException $e) {
		}

		$this->component->onBehaviorEvent = 'foo';
		$this->assertEquals(1, count($this->component->onBehaviorEvent));
		$this->assertEquals(1, count($this->component->$behaviorTestBehaviorName->onBehaviorEvent));
		unset($this->component->onBehaviorEvent);
		$this->assertEquals(0, count($this->component->onBehaviorEvent));
		$this->assertEquals(0, count($this->component->$behaviorTestBehaviorName->onBehaviorEvent));

		// Remove behavior via unset
		unset($this->component->$behaviorTestBehaviorName);
		$this->assertFalse($this->component->asa($behaviorTestBehaviorName) instanceof BehaviorTestBehavior);
	}

	public function testGetSubProperty()
	{
		$this->assertTrue('object text' === $this->component->getSubProperty('Object.Text'));
	}

	public function testSetSubProperty()
	{
		$this->component->setSubProperty('Object.Text', 'new object text');
		$this->assertEquals('new object text', $this->component->getSubProperty('Object.Text'));
	}
	
	public function testHasMethod()
	{
		$behaviorTestBehaviorName = 'BehaviorTestBehaviorName';
		$this->assertTrue($this->component->hasMethod('eventReturnValue'));
		$this->assertTrue($this->component->hasMethod('eventreturnvalue'));
		$this->assertFalse($this->component->hasMethod('noeventreturnvalue'));
	
		// fx won't throw an error if any of these fx function are called on an object.
		//	It is a special prefix event designation that every object responds to all events/methods.
		$this->assertTrue($this->component->hasMethod('fxAttachClassBehavior'));
		$this->assertTrue($this->component->hasMethod('fxattachclassbehavior'));
	
		$this->assertFalse($this->component->hasMethod('fxNonExistantGlobalEvent'));
		$this->assertFalse($this->component->hasMethod('fxnonexistantglobalevent'));
	
		$this->assertTrue($this->component->hasMethod('dyNonExistantLocalEvent'));
		$this->assertTrue($this->component->hasMethod('dynonexistantlocalevent'));
	
	
		//Test behavior events
		$this->assertFalse($this->component->hasMethod('getExcitement'));
		$this->component->attachBehavior($behaviorTestBehaviorName, new BehaviorTestBehavior());
		$this->assertTrue($this->component->hasMethod('getExcitement'));
		$this->assertTrue($this->component->$behaviorTestBehaviorName->hasMethod('getExcitement'));
		
		//Test behaviors within behaviors.
		$this->component->$behaviorTestBehaviorName->attachBehavior('SubBehavior', new FooFooClassBehavior());
		$this->assertTrue($this->component->$behaviorTestBehaviorName->hasMethod('faafaaEverMore'));
		$this->assertFalse($this->component->hasMethod('faafaaEverMore'));
		$this->assertEquals('ffemResult', $this->component->$behaviorTestBehaviorName->faafaaEverMore(null, null, null));
		try {
			$this->component->faafaaEverMore(null, null, null);
			$this->fail('TUnknownMethodException not raised when calling a behaviors behaviors method');
		} catch (TUnknownMethodException $e) {
		}
		
	
		$this->component->disableBehavior($behaviorTestBehaviorName);
		$this->assertFalse($this->component->hasMethod('getExcitement'));
		$this->component->enableBehavior($behaviorTestBehaviorName);
		$this->assertTrue($this->component->hasMethod('getExcitement'));
	
		$this->component->disableBehaviors();
		$this->assertFalse($this->component->hasMethod('getExcitement'));
		$this->component->enableBehaviors();
		$this->assertTrue($this->component->hasMethod('getExcitement'));
	}

	public function testHasEvent()
	{
		$behaviorTestBehaviorName = 'BehaviorTestBehaviorName';
		
		$this->assertTrue($this->component->hasEvent('OnMyEvent'));
		$this->assertTrue($this->component->hasEvent('onmyevent'));
		$this->assertFalse($this->component->hasEvent('onYourEvent'));

		// fx won't throw an error if any of these fx function are called on an object.
		//	It is a special prefix event designation that every object responds to all events.
		$this->assertTrue($this->component->hasEvent('fxAttachClassBehavior'));
		$this->assertTrue($this->component->hasEvent('fxattachclassbehavior'));

		$this->assertTrue($this->component->hasEvent('fxNonExistantGlobalEvent'));
		$this->assertTrue($this->component->hasEvent('fxnonexistantglobalevent'));

		$this->assertTrue($this->component->hasEvent('dyNonExistantLocalEvent'));
		$this->assertTrue($this->component->hasEvent('dynonexistantlocalevent'));


		//Test behavior events
		$this->assertFalse($this->component->hasEvent('onBehaviorEvent'));
		$this->component->attachBehavior($behaviorTestBehaviorName, new BehaviorTestBehavior);
		$this->assertTrue($this->component->hasEvent('onBehaviorEvent'));
		$this->assertTrue($this->component->$behaviorTestBehaviorName->hasEvent('onBehaviorEvent'));

		$this->component->disableBehavior($behaviorTestBehaviorName);
		$this->assertFalse($this->component->hasEvent('onBehaviorEvent'));
		$this->component->enableBehavior($behaviorTestBehaviorName);
		$this->assertTrue($this->component->hasEvent('onBehaviorEvent'));
		
		$this->component->disableBehaviors();
		$this->assertFalse($this->component->hasEvent('onBehaviorEvent'));
		$this->component->enableBehaviors();
		$this->assertTrue($this->component->hasEvent('onBehaviorEvent'));
	}

	public function testHasEventHandler()
	{
		$behaviorTestBehaviorName = 'BehaviorTestBehaviorName';
		
		$this->assertFalse($this->component->hasEventHandler('OnMyEvent'));
		$this->component->attachEventHandler('OnMyEvent', 'foo');
		$this->assertTrue($this->component->hasEventHandler('OnMyEvent'));

		$this->assertFalse($this->component->hasEventHandler('fxNonExistantGlobalEvent'));
		$this->component->attachEventHandler('fxNonExistantGlobalEvent', 'foo');
		$this->assertTrue($this->component->hasEventHandler('fxNonExistantGlobalEvent'));

		//Test behavior events
		$this->assertFalse($this->component->hasEventHandler('onBehaviorEvent'));
		$this->component->attachBehavior($behaviorTestBehaviorName, new BehaviorTestBehavior);
		$this->assertFalse($this->component->hasEventHandler('onBehaviorEvent'));
		$this->assertFalse($this->component->$behaviorTestBehaviorName->hasEventHandler('onBehaviorEvent'));

		$this->component->attachEventHandler('onBehaviorEvent', 'foo');
		$this->assertTrue($this->component->hasEventHandler('onBehaviorEvent'));

		$this->component->disableBehavior($behaviorTestBehaviorName);
		$this->assertFalse($this->component->hasEvent('onBehaviorEvent'));
		$this->assertFalse($this->component->hasEventHandler('onBehaviorEvent'));
		$this->component->enableBehavior($behaviorTestBehaviorName);
		$this->assertTrue($this->component->hasEvent('onBehaviorEvent'));
		$this->assertTrue($this->component->hasEventHandler('onBehaviorEvent'));
	}

	public function testGetEventHandlers()
	{
		$list = $this->component->getEventHandlers('OnMyEvent');
		$this->assertTrue(($list instanceof TPriorityList) && ($list->getCount() === 0));
		$this->component->attachEventHandler('OnMyEvent', 'foo');
		$this->assertTrue(($list instanceof TPriorityList) && ($list->getCount() === 1));
		try {
			$list = $this->component->getEventHandlers('YourEvent');
			$this->fail('exception not raised when getting event handlers for undefined event');
		} catch (TInvalidOperationException $e) {
		}

		$list = $this->component->getEventHandlers('fxRandomEvent');
		$this->assertTrue(($list instanceof TPriorityList) && ($list->getCount() === 0));
		$this->component->attachEventHandler('fxRandomEvent', 'foo');
		$this->assertTrue(($list instanceof TPriorityList) && ($list->getCount() === 1));
		try {
			$list = $this->component->getEventHandlers('fxSomeUndefinedGlobalEvent');
		} catch (TInvalidOperationException $e) {
			$this->fail('exception raised when getting event handlers for universal global event');
		}



		//Test behavior events
		try {
			$list = $this->component->getEventHandlers('onBehaviorEvent');
			$this->fail('exception not raised when getting event handlers for undefined event');
		} catch (TInvalidOperationException $e) {
		}
		$this->assertFalse($this->component->hasEventHandler('onBehaviorEvent'));

		$behaviorTestBehaviorName = 'BehaviorTestBehaviorName';
		$this->component->attachBehavior($behaviorTestBehaviorName, new BehaviorTestBehavior);
		$list = $this->component->getEventHandlers('onBehaviorEvent');
		$this->assertTrue(($list instanceof TPriorityList) && ($list->getCount() === 0));
		$this->component->attachEventHandler('onBehaviorEvent', 'foo');
		$this->assertTrue(($list instanceof TPriorityList) && ($list->getCount() === 1));

		$this->component->disableBehavior($behaviorTestBehaviorName);
		try {
			$list = $this->component->getEventHandlers('onBehaviorEvent');
			$this->fail('exception not raised when getting event handlers for undefined event');
		} catch (TInvalidOperationException $e) {
		}
		$this->component->enableBehavior($behaviorTestBehaviorName);
		$this->assertTrue(($this->component->getEventHandlers('onBehaviorEvent') instanceof TPriorityList) && ($list->getCount() === 1));
	}

	public function testAttachEventHandler()
	{
		$this->component->attachEventHandler('OnMyEvent', 'foo');
		$this->assertEquals(1, $this->component->getEventHandlers('OnMyEvent')->getCount());
		try {
			$this->component->attachEventHandler('YourEvent', 'foo');
			$this->fail('exception not raised when attaching event handlers for undefined event');
		} catch (TInvalidOperationException $e) {
		}

		//Testing the priorities of attaching events
		$this->component->attachEventHandler('OnMyEvent', 'foopre', 5);
		$this->component->attachEventHandler('OnMyEvent', 'foopost', 15);
		$this->component->attachEventHandler('OnMyEvent', 'foobar', 10);
		$this->assertEquals(4, $this->component->getEventHandlers('OnMyEvent')->getCount());
		$list = $this->component->getEventHandlers('OnMyEvent');
		$this->assertEquals('foopre', $list[0]);
		$this->assertEquals('foo', $list[1]);
		$this->assertEquals('foobar', $list[2]);
		$this->assertEquals('foopost', $list[3]);


		//Test attaching behavior events
		try {
			$this->component->attachEventHandler('onBehaviorEvent', 'foo');
			$this->fail('exception not raised when getting event handlers for undefined event');
		} catch (TInvalidOperationException $e) {
		}
		$behaviorTestBehaviorName = 'BehaviorTestBehaviorName';
		$this->component->attachBehavior($behaviorTestBehaviorName, new BehaviorTestBehavior);

		$this->component->attachEventHandler('onBehaviorEvent', 'foo');

		//Testing the priorities of attaching behavior events
		$this->component->attachEventHandler('onBehaviorEvent', 'foopre', 5);
		$this->component->attachEventHandler('onBehaviorEvent', 'foopost', 15);
		$this->component->attachEventHandler('onBehaviorEvent', 'foobar', 10);
		$this->component->attachEventHandler('onBehaviorEvent', 'foobarfoobar', 10);
		$this->assertEquals(5, $this->component->getEventHandlers('onBehaviorEvent')->getCount());
		$list = $this->component->getEventHandlers('onBehaviorEvent');
		$this->assertEquals('foopre', $list[0]);
		$this->assertEquals('foo', $list[1]);
		$this->assertEquals('foobar', $list[2]);
		$this->assertEquals('foobarfoobar', $list[3]);
		$this->assertEquals('foopost', $list[4]);

		$this->component->disableBehavior($behaviorTestBehaviorName);
		try {
			$this->component->attachEventHandler('onBehaviorEvent', 'bar');
			$this->fail('exception not raised when getting event handlers for undefined event');
		} catch (TInvalidOperationException $e) {
		}
		$this->component->enableBehavior($behaviorTestBehaviorName);
		
		unset($this->component->OnMyEvent);
	}

	public function testDetachEventHandler()
	{
		$this->component->attachEventHandler('OnMyEvent', 'foo');
		$this->assertEquals(1, $this->component->getEventHandlers('OnMyEvent')->getCount());

		$this->component->attachEventHandler('OnMyEvent', 'foopre', 5);
		$this->component->attachEventHandler('OnMyEvent', 'foopost', 15);
		$this->component->attachEventHandler('OnMyEvent', 'foobar', 10);
		$this->component->attachEventHandler('OnMyEvent', 'foobarfoobar', 10);



		$this->component->detachEventHandler('OnMyEvent', 'foo');
		$list = $this->component->getEventHandlers('OnMyEvent');
		$this->assertEquals(4, $list->getCount());

		$this->assertEquals('foopre', $list[0]);
		$this->assertEquals('foobar', $list[1]);
		$this->assertEquals('foobarfoobar', $list[2]);
		$this->assertEquals('foopost', $list[3]);

		$this->component->detachEventHandler('OnMyEvent', 'foopre', null);
		$this->assertEquals(4, $list->getCount());

		$this->component->detachEventHandler('OnMyEvent', 'foopre', 5);
		$this->assertEquals(3, $list->getCount());


		// Now do detaching of behavior on events
		try {
			$this->component->attachEventHandler('onBehaviorEvent', 'foo');
			$this->fail('exception not raised when getting event handlers for undefined event');
		} catch (TInvalidOperationException $e) {
		}
		$behaviorTestBehaviorName = 'BehaviorTestBehaviorName';
		$this->component->attachBehavior($behaviorTestBehaviorName, new BehaviorTestBehavior);

		$this->component->attachEventHandler('onBehaviorEvent', 'foo');
		$this->assertEquals(1, $this->component->getEventHandlers('onBehaviorEvent')->getCount());

		$this->component->attachEventHandler('onBehaviorEvent', 'foopre', 5);
		$this->component->attachEventHandler('onBehaviorEvent', 'foopost', 15);
		$this->component->attachEventHandler('onBehaviorEvent', 'foobar', 10);
		$this->component->attachEventHandler('onBehaviorEvent', 'foobarfoobar', 10);



		$this->component->detachEventHandler('onBehaviorEvent', 'foo');
		$list = $this->component->getEventHandlers('onBehaviorEvent');
		$this->assertEquals(4, $list->getCount());

		$this->assertEquals('foopre', $list[0]);
		$this->assertEquals('foobar', $list[1]);
		$this->assertEquals('foobarfoobar', $list[2]);
		$this->assertEquals('foopost', $list[3]);

		$this->component->detachEventHandler('onBehaviorEvent', 'foopre', null);
		$this->assertEquals(4, $list->getCount());

		$this->component->detachEventHandler('onBehaviorEvent', 'foopre', 5);
		$this->assertEquals(3, $list->getCount());
	}




	public function testRaiseEvent()
	{
		$component = new NewComponent();
		
		// object method callable
		$component->attachEventHandler('OnMyEvent', [$this->component, 'myEventHandler']);
		$this->assertFalse($this->component->isEventHandled());
		$this->assertFalse($this->component->Object->isEventHandled());
		$component->raiseEvent('OnMyEvent', $this, null);
		$this->assertTrue($this->component->isEventHandled());
		$this->assertFalse($this->component->Object->isEventHandled());
		
		$this->component->resetEventHandled();
		$this->component->Object->resetEventHandled();
		$component->detachEventHandler('OnMyEvent', [$this->component, 'myEventHandler']);
		
		// object sub-property method
		$component->attachEventHandler('OnMyEvent', [$this->component, 'Object.myEventHandler']);
		$this->assertFalse($this->component->isEventHandled());
		$this->assertFalse($this->component->Object->isEventHandled());
		$component->raiseEvent('OnMyEvent', $this, null);
		$this->assertFalse($this->component->isEventHandled());
		$this->assertTrue($this->component->Object->isEventHandled());
		
		$component->detachEventHandler('OnMyEvent', [$this->component, 'myEventHandler']);
		$this->component->resetEventHandled();
		$this->component->Object->resetEventHandled();
		
		// closure
		$raised = false;
		$eventCount = $component->OnMyEvent->count();
		$component->attachEventHandler('OnMyEvent', $closure = function () use (&$raised) {
			$raised = true;
		});
		$this->assertEquals($eventCount + 1, $component->OnMyEvent->count());
		$component->raiseEvent('OnMyEvent', $this, null);
		$this->assertTrue($raised);
		
		$component->detachEventHandler('OnMyEvent', $closure);
		$this->assertEquals($eventCount, $component->OnMyEvent->count());
		$this->component->resetEventHandled();
		$this->component->Object->resetEventHandled();


		// Test a behavior on event
		$this->component->attachBehavior('test', new BehaviorTestBehavior);

		$this->component->attachEventHandler('onBehaviorEvent', [$this->component, 'myEventHandler']);
		$this->assertFalse($this->component->isEventHandled());
		$this->component->raiseEvent('onBehaviorEvent', $this, null);
		$this->assertTrue($this->component->isEventHandled());
		$this->component->attachEventHandler('onBehaviorEvent', [$this->component, 'Object.myEventHandler']);
		$this->assertFalse($this->component->Object->isEventHandled());
		$this->component->raiseEvent('onBehaviorEvent', $this, null);
		$this->assertTrue($this->component->Object->isEventHandled());

		//test behavior enabled/disabled events
		$this->component->disableBehavior('test');

		$this->component->resetEventHandled();
		$this->component->Object->resetEventHandled();

		try {
			$this->component->attachEventHandler('onBehaviorEvent', [$this->component, 'myEventHandler']);
			$this->fail('exception not raised when getting event handlers for undefined event');
		} catch (Prado\Exceptions\TInvalidOperationException $e) {
		}
		$this->assertFalse($this->component->isEventHandled());
		try {
			$this->component->raiseEvent('onBehaviorEvent', $this, null);
			$this->fail('exception not raised when getting event handlers for undefined event');
		} catch (Prado\Exceptions\TInvalidOperationException $e) {
		}
		$this->assertFalse($this->component->isEventHandled());

		$this->component->enableBehavior('test');



		//Test the return types of this function

		$this->assertFalse($this->component->isEventHandled());
		$this->assertFalse($this->component->Object->isEventHandled());
		$this->assertEquals([], $this->component->onBehaviorEvent($this, $this->component));
		$this->assertTrue($this->component->isEventHandled());
		$this->assertTrue($this->component->Object->isEventHandled());

		// This accumulates all the responses from each of the events
		$arr = $this->component->onBehaviorEvent($this, $this->component, TEventResults::EVENT_RESULT_ALL);
		$this->assertEquals($this, $arr[0]['sender']);
		$this->assertEquals($this->component, $arr[0]['param']);
		$this->assertTrue(null === $arr[0]['response']);

		$this->assertEquals($this, $arr[1]['sender']);
		$this->assertEquals($this->component, $arr[1]['param']);
		$this->assertTrue(null === $arr[1]['response']);

		$this->assertEquals(2, count($arr));

		// This tests without the default filtering-out of null
		$arr = $this->component->onBehaviorEvent($this, $this->component, false);
		$this->assertEquals([null, null], $arr);


		unset($this->component->onBehaviorEvent);
		$this->assertEquals(0, $this->component->onBehaviorEvent->Count);

		$this->component->onBehaviorEvent = [$this, 'returnValue4'];
		$this->component->onBehaviorEvent = [$this, 'returnValue1'];

		// Test the per event post processing function
		$arr = $this->component->onBehaviorEvent($this, $this->component, [$this, 'postEventFunction']);
		$this->assertEquals([exp(4), exp(1)], $arr);
		$arr = $this->component->onBehaviorEvent($this, $this->component, [$this, 'postEventFunction2']);
		$this->assertEquals([sin(4), sin(1)], $arr);


		//Testing Feed-forward functionality
		unset($this->component->onBehaviorEvent);

		$this->component->onBehaviorEvent = [$this, 'ffValue4'];
		$this->component->onBehaviorEvent = [$this, 'ffValue2'];
		$arr = $this->component->onBehaviorEvent($this, 5, TEventResults::EVENT_RESULT_FEED_FORWARD);
		$this->assertEquals([20, 40], $arr);
		
		$arr = $this->component->onBehaviorEvent($this, 5, 0);
		$this->assertEquals([20, 10], $arr);
		
		$arr = $this->component->onBehaviorEvent($this, 5, TEventResults::EVENT_REVERSE);
		$this->assertEquals([10, 20], $arr);


		unset($this->component->onBehaviorEvent);

		//Order of these events affects the response order in feed forward
		$this->component->onBehaviorEvent = [$this, 'ffValue2'];
		$this->component->onBehaviorEvent = [$this, 'ffValue4'];
		$arr = $this->component->onBehaviorEvent($this, 5, TEventResults::EVENT_RESULT_FEED_FORWARD);
		$this->assertEquals([10, 40], $arr);
	}

	public function returnValue1()
	{
		return 1;
	}
	public function returnValue4()
	{
		return 4;
	}
	public function postEventFunction($sender, $param, $caller, $response)
	{
		return exp($response);
	}
	public function postEventFunction2($sender, $param, $caller, $response)
	{
		return sin($response);
	}
	public function ffValue2($sender, $param)
	{
		return $param * 2;
	}
	public function ffValue4($sender, $param)
	{
		return $param * 4;
	}


	public function testGlobalEventListenerInRaiseEvent()
	{
		//TODO Test the Global Event Listener
		throw new PHPUnit\Framework\IncompleteTestError();
	}


	public function testIDynamicMethodsOnBehavior()
	{

	//Add Behavior with dynamic call
		$this->component->attachBehavior('TDynamicBehavior', new TDynamicBehavior);

		//Check that the behavior is working as it should
		$this->assertTrue($this->component->isa('TDynamicBehavior'));
		$this->assertEquals('dyAttachBehavior', $this->component->getLastBehaviorDynamicMethodCalled());

		// call basic behavior implemented method from object (containing behavior)
		$this->assertEquals(42, $this->component->TestBehaviorMethod(6, 7));

		//Test out undefined behavior/host object method
		try {
			$this->component->objectAndBehaviorUndefinedMethod();
			$this->fail('TUnknownMethodException not raised when evaluating an undefined method by the object and behavior');
		} catch (TUnknownMethodException $e) {
		}

		// calling undefined dynamic method, caught by the __dycall method in the behavior and implemented
		//	this behavior catches undefined dynamic event and divides param1 by param 2
		$this->assertEquals(22, $this->component->dyTestDynamicBehaviorMethod(242, 11));
		$this->assertEquals('dyTestDynamicBehaviorMethod', $this->component->getLastBehaviorDynamicMethodCalled());

		// calling undefined dynamic method, caught by the __dycall in the behavior and ignored
		$this->assertNull($this->component->dyUndefinedIntraEvent(242, 11));
		$this->assertEquals('dyUndefinedIntraEvent', $this->component->getLastBehaviorDynamicMethodCalled());

		//call behavior defined dynamic event
		//	param1 * 2 * param2
		$this->assertEquals(2420, $this->component->dyTestIntraEvent(121, 10));

		$this->component->detachBehavior('TDynamicBehavior');
		$this->assertFalse($this->component->isa('TDynamicBehavior'));



		//Add Class Behavior with dynamic call
		$this->component->attachBehavior('TDynamicClassBehavior', new TDynamicClassBehavior);

		//Check that the behavior is working as it should
		$this->assertTrue($this->component->isa('TDynamicClassBehavior'));
		$this->assertEquals('dyAttachBehavior', $this->component->getLastBehaviorDynamicMethodCalled());

		// call basic behavior implemented method from object (containing behavior)
		$this->assertEquals(42, $this->component->TestBehaviorMethod(6, 7));

		//Test out undefined behavior/host object method
		try {
			$this->component->objectAndBehaviorUndefinedMethod();
			$this->fail('TUnknownMethodException not raised when evaluating an undefined method by the object and behavior');
		} catch (TUnknownMethodException $e) {
		}

		// calling undefined dynamic method, caught by the __dycall method in the behavior and implemented
		//	this behavior catches undefined dynamic event and divides param1 by param 2
		$this->assertEquals(22, $this->component->dyTestDynamicClassBehaviorMethod(242, 11));
		$this->assertEquals('dyTestDynamicClassBehaviorMethod', $this->component->getLastBehaviorDynamicMethodCalled());

		// calling undefined dynamic method, caught by the __dycall in the behavior and ignored
		$this->assertNull($this->component->dyUndefinedIntraEvent(242, 11));
		$this->assertEquals('dyUndefinedIntraEvent', $this->component->getLastBehaviorDynamicMethodCalled());

		//call behavior defined dynamic event
		//	param1 * 2 * param2
		$this->assertEquals(2420, $this->component->dyTestIntraEvent(121, 10));

		$this->component->detachBehavior('TDynamicClassBehavior');
		$this->assertFalse($this->component->isa('TDynamicClassBehavior'));
	}

	// This also tests the priority of the common global raiseEvent events
	public function testIDynamicMethodsOnBehaviorGlobalEvents()
	{
		$component = new GlobalRaiseComponent();

		// common function has a default priority of 10
		$component->attachEventHandler(TComponent::GLOBAL_RAISE_EVENT_LISTENER, [$component, 'commonRaiseEventListener']);
		$component->attachEventHandler(TComponent::GLOBAL_RAISE_EVENT_LISTENER, [$component, 'postglobalRaiseEventListener'], 1);
		$component->attachEventHandler(TComponent::GLOBAL_RAISE_EVENT_LISTENER, [$component, 'preglobalRaiseEventListener'], -1);

		$this->assertEquals(5, $this->component->fxGlobalListener->getCount());
		$this->assertEquals(1, $this->component->fxPrimaryGlobalEvent->getCount());
		$this->assertEquals(1, $this->component->fxPrimaryGlobalEvent->getCount(), 'fxPrimaryGlobalEvent is not installed on test object');

		// call the global event on a different object than the test object
		$res = $this->component->raiseEvent('fxPrimaryGlobalEvent', $this, null, TEventResults::EVENT_RESULT_ALL);

		$this->assertEquals(6, count($res));
		$this->assertEquals(['pregl', 'primary', 'postgl', 'fxGL', 'fxcall', 'com'], $component->getCallOrders());

		$component->unlisten();
		
		//These are not 'fx' so these need to be removed individually.
		$component->detachEventHandler(TComponent::GLOBAL_RAISE_EVENT_LISTENER, [$component, 'commonRaiseEventListener']);
		$component->detachEventHandler(TComponent::GLOBAL_RAISE_EVENT_LISTENER, [$component, 'postglobalRaiseEventListener'], 1);
		$component->detachEventHandler(TComponent::GLOBAL_RAISE_EVENT_LISTENER, [$component, 'preglobalRaiseEventListener'], -1);
	}




	public function testEvaluateExpression()
	{
		$expression = "1+2";
		$this->assertTrue(3 === $this->component->evaluateExpression($expression));
		try {
			$button = $this->component->evaluateExpression('$this->button');
			$this->fail('exception not raised when evaluating an invalid exception');
		} catch (Exception $e) {
		}
	}




	public function testEvaluateStatements()
	{
		$statements = '$a="test string"; echo $a;';
		$this->assertEquals('test string', $this->component->evaluateStatements($statements));
		try {
			$statements = '$a=new NewComponent; echo $a->button;';
			$button = $this->component->evaluateStatements($statements);
			$this->fail('exception not raised when evaluating an invalid statement');
		} catch (TInvalidOperationException $e) {
			ob_end_flush();
		}
	}


	public function testDynamicFunctionCall()
	{
		$this->assertEquals(' aa bb cc __ .. ++ || !! ?? ', $this->component->dyTextFilter(' aa bb cc __ .. ++ || !! ?? '));

		$this->component->attachBehavior('dy1', new dy1TextReplace);
		$this->assertFalse($this->component->dy1->isCalled());
		$this->assertEquals(' aa bb cc __ __ ++ || !! ?? ', $this->component->dyTextFilter(' aa bb cc __ .. ++ || !! ?? '));
		$this->assertTrue($this->component->dy1->isCalled());

		$this->component->attachBehavior('dy2', new dy2TextReplace);
		$this->assertFalse($this->component->dy2->isCalled());
		$this->assertEquals(' aa bb cc __ __ || || !! ?? ', $this->component->dyTextFilter(' aa bb cc __ .. ++ || !! ?? '));
		$this->assertTrue($this->component->dy2->isCalled());

		$this->component->attachBehavior('dy3', new dy3TextReplace);
		$this->assertFalse($this->component->dy3->isCalled());
		$this->assertEquals(' aa bb cc __ __ || || ?? ?? ', $this->component->dyTextFilter(' aa bb cc __ .. ++ || !! ?? '));
		$this->assertTrue($this->component->dy3->isCalled());

		$this->assertEquals(' aa bb cc __ .. ++ || !! ?? ', $this->component->dyUndefinedEvent(' aa bb cc __ .. ++ || !! ?? '));

		$this->assertEquals(0.25, $this->component->dyPowerFunction(2, 2));


		$this->component->detachBehavior('dy1');
		$this->component->detachBehavior('dy2');
		$this->component->detachBehavior('dy3');

		//test class behaviors of dynamic events and the argument list order

		$this->assertEquals(' aa bb cc __ .. ++ || !! ?? ', $this->component->dyTextFilter(' aa bb cc __ .. ++ || !! ?? '));

		$this->component->attachBehavior('dy1', new dy1ClassTextReplace);
		$this->assertFalse($this->component->dy1->isCalled());
		$this->assertEquals(' aa bb cc .. .. ++ || !! ?? ', $this->component->dyTextFilter(' aa bb cc __ .. ++ || !! ?? '));
		$this->assertTrue($this->component->dy1->isCalled());

		$this->component->attachBehavior('dy2', new dy2ClassTextReplace);
		$this->assertFalse($this->component->dy2->isCalled());
		$this->assertEquals(' aa bb cc .. .. ++ ++ !! ?? ', $this->component->dyTextFilter(' aa bb cc __ .. ++ || !! ?? '));
		$this->assertTrue($this->component->dy2->isCalled());

		$this->component->attachBehavior('dy3', new dy3ClassTextReplace);
		$this->assertFalse($this->component->dy3->isCalled());
		$this->assertEquals(' aa bb cc .. .. ++ ++ !! ^_^ ', $this->component->dyTextFilter(' aa bb cc __ .. ++ || !! ?? '));
		$this->assertTrue($this->component->dy3->isCalled());

		$this->assertEquals(' aa bb cc __ .. ++ || !! ?? ', $this->component->dyUndefinedEvent(' aa bb cc __ .. ++ || !! ?? '));

		$this->assertEquals(0.25, $this->component->dyPowerFunction(2, 2));
	}




	public function testDynamicIntraObjectEvents()
	{
		$this->component->attachBehavior('IntraEvents', new IntraObjectExtenderBehavior);

		$this->assertEquals(11, $this->component->IntraEvents->LastCall);

		//unlisten first, this object listens upon instantiation.
		$this->component->unlisten();
		$this->assertEquals(2, $this->component->IntraEvents->LastCall);

		// ensures that IntraEvents nulls the last call variable when calling this getter
		$this->assertNull($this->component->IntraEvents->LastCall);

		//listen next to undo the unlisten
		$this->component->listen();
		$this->assertEquals(1, $this->component->IntraEvents->LastCall);


		$this->assertEquals(3, $this->component->evaluateExpression('1+2'));
		$this->assertEquals(7, $this->component->IntraEvents->LastCall);

		$statements = '$a="test string"; echo $a;';
		$this->assertEquals('test string', $this->component->evaluateStatements($statements));
		$this->assertEquals(8, $this->component->IntraEvents->LastCall);

		$component2 = new NewComponentNoListen();
		$this->assertNull($this->component->createdOnTemplate($component2));
		$this->assertEquals(9, $this->component->IntraEvents->LastCall);

		$this->assertNull($this->component->addParsedObject($component2));
		$this->assertEquals(10, $this->component->IntraEvents->LastCall);


		//  Attach new Barbehavior
		$behavior = new BarBehavior;
		$this->assertEquals($behavior, $this->component->attachBehavior('BarBehavior', $behavior));
		$this->assertEquals(11, $this->component->IntraEvents->LastCall);

		$this->assertNull($this->component->disableBehaviors());
		$this->assertNull($this->component->enableBehaviors());
		$this->assertEquals(27, $this->component->IntraEvents->LastCall);

		$this->assertTrue($this->component->disableBehavior('BarBehavior'));
		$this->assertEquals(16, $this->component->IntraEvents->LastCall);

		$this->assertTrue($this->component->enableBehavior('BarBehavior'));
		$this->assertEquals(15, $this->component->IntraEvents->LastCall);

		$this->assertEquals($behavior, $this->component->detachBehavior('BarBehavior'));
		$this->assertEquals(12, $this->component->IntraEvents->LastCall);


		$this->component->attachEventHandler('OnMyEvent', [$this->component, 'myEventHandler']);
		$this->component->raiseEvent('OnMyEvent', $this, null);

		//3 + 4 + 5 + 6 = 18 (the behavior adds these together when each raiseEvent dynamic intra event is called)
		$this->assertEquals(18, $this->component->IntraEvents->LastCall);
	}



	public function testJavascriptGetterSetter()
	{
		$this->assertFalse(isset($this->component->ColorAttribute));
		$this->assertFalse(isset($this->component->JsColorAttribute));

		$this->component->ColorAttribute = "('#556677', '#abcdef', 503987)";
		$this->assertEquals("('#556677', '#abcdef', 503987)", $this->component->ColorAttribute);

		$this->assertTrue(isset($this->component->ColorAttribute));
		$this->assertTrue(isset($this->component->JsColorAttribute));

		$this->component->ColorAttribute = "new Array(1, 2, 3, 4, 5)";
		$this->assertEquals("new Array(1, 2, 3, 4, 5)", $this->component->JsColorAttribute);

		$this->component->JsColorAttribute = "['#112233', '#fedcba', 22009837]";
		$this->assertEquals("['#112233', '#fedcba', 22009837]", $this->component->ColorAttribute);
	}


	public function testJavascriptIssetUnset()
	{
		$this->component->JsColorAttribute = "['#112233', '#fedcba', 22009837]";
		$this->assertEquals("['#112233', '#fedcba', 22009837]", $this->component->ColorAttribute);

		unset($this->component->ColorAttribute);

		$this->assertFalse(isset($this->component->ColorAttribute));
		$this->assertFalse(isset($this->component->JsColorAttribute));

		$this->component->JsColorAttribute = "['#112233', '#fedcba', 22009837]";

		$this->assertTrue(isset($this->component->ColorAttribute));
		$this->assertTrue(isset($this->component->JsColorAttribute));

		unset($this->component->JsColorAttribute);

		$this->assertFalse(isset($this->component->ColorAttribute));
		$this->assertFalse(isset($this->component->JsColorAttribute));
	}

	public function testClone()
	{
		$obj = new NewComponent();
		$this->component = new NewComponent();
		$this->component->attachBehavior('CopyBehavior', $b = new NewComponentBehavior());
		$this->component->onMyEvent[] = [$this->component, 'myEventHandler'];
		$this->component->onMyEvent[] = [$obj, 'myEventHandler'];
		$this->assertEquals(3, count($this->component->onMyEvent));
		
		$this->assertNotNull($this->component->asa('CopyBehavior'));
		$this->assertEquals($this->component, $this->component->CopyBehavior->getOwner());
	
		$copy = clone $this->component;
		$copy->Text = 'copyObject';
		
		$this->assertNotNull($copy->asa('COPYBehavior'));
		$this->assertEquals($this->component, $this->component->CopyBehavior->getOwner());
		$this->assertEquals($copy, $copy->CopyBehavior->getOwner());
		$this->assertTrue($copy->CopyBehavior !== $this->component->CopyBehavior);
		$this->assertEquals(3, count($this->component->onMyEvent));
		$this->assertEquals(-1, $this->component->onMyEvent->indexOf([$copy->CopyBehavior, 'ncBehaviorHandler']));
		$this->assertEquals(3, count($copy->onMyEvent));
		$this->assertEquals(-1, $copy->onMyEvent->indexOf([$b, 'ncBehaviorHandler']));
		$this->assertEquals(2, $copy->onMyEvent->indexOf([$copy->CopyBehavior, 'ncBehaviorHandler']));
	}
	
	public function testWakeUp()
	{
		NewComponent::attachClassBehavior('ClassBehavior1', $cb1 = new FooClassBehavior());
		$this->tearDownScripts['ClassBehavior1'] = function() {NewComponent::detachClassBehavior('ClassBehavior1');};
		$cb1->propertya = 'second value';
		NewComponent::attachClassBehavior('ClassBehavior2', $cb2 = new FooFooClassBehavior());
		$this->tearDownScripts['ClassBehavior2'] = function() {NewComponent::detachClassBehavior('ClassBehavior2');};
		NewComponent::attachClassBehavior('ClassBehavior4', $cb4 = new FooClassBehavior());
		$this->tearDownScripts['ClassBehavior4'] = function() {NewComponent::detachClassBehavior('ClassBehavior4');};
		
		// Anonynmous behavior
		$behavior5Name = $this->anonymousClassIndex++;
		NewComponent::attachClassBehavior(null, $cb5 = new FooClassBehavior());
		$this->tearDownScripts['ClassBehavior5'] = function() use ($behavior5Name) {NewComponent::detachClassBehavior($behavior5Name);};
		$cb5->propertya = '5th value';
		$this->assertEquals('classbehavior1', $cb1->getName());
		$this->assertEquals('classbehavior2', $cb2->getName());
		$this->assertEquals('classbehavior4', $cb4->getName());
		
		
		$obj = new NewComponent();
		$this->component = new NewComponent();
		
		$this->component->attachBehavior('CopyBehavior', $b = new NewComponentBehavior());
		$this->component->onMyEvent[] = [$this->component, 'myEventHandler'];
		$this->component->onMyEvent[] = [$obj, 'myEventHandler'];
		$this->assertEquals(3, count($this->component->onMyEvent));
		
		$this->assertNotNull($this->component->asa('CopyBehavior'));
		$this->assertNotNull($this->component->asa('ClassBehavior1'));
		$this->assertNotNull($this->component->asa('ClassBehavior2'));
		$this->assertNull($this->component->asa('ClassBehavior3'));
		$this->assertEquals($this->component, $this->component->CopyBehavior->getOwner());
	
		// Serialize
		$data = serialize($this->component);
		
		// Change environment
		NewComponent::detachClassBehavior('ClassBehavior2'); // Without an existing class behavior
		unset($this->tearDownScripts['ClassBehavior2']);
		$this->assertNull($this->component->asa('ClassBehavior2'));
		NewComponent::attachClassBehavior('ClassBehavior3', $cb3 = new BarClassBehavior());
		
		// ClassBehavior3 is new between sleep and wake up.
		$this->tearDownScripts['ClassBehavior3'] = function() {NewComponent::detachClassBehavior('ClassBehavior3');};
		$this->assertNotNull($this->component->asa('ClassBehavior3')); // With new class behavior
		NewComponent::detachClassBehavior('ClassBehavior4');	// with a replacement class behavior.
		NewComponent::attachClassBehavior('ClassBehavior4', $cb4a = new FooClassBehavior());
		$cb4a->propertya = 'new 4th value';
		$cb2->propertya = '3rd value';
		$cb5->propertya = 'old 5th value';
		
		// anonymous 1 is new between sleep and wake up.
		$behavior6Name = $this->anonymousClassIndex++;
		NewComponent::attachClassBehavior(null, $cb6 = new FooClassBehavior());
		$this->tearDownScripts['ClassBehavior6'] = function() use ($behavior6Name) {NewComponent::detachClassBehavior($behavior6Name);};
		$cb6->propertya = '6th value';
		
		// Unserialize
		$copy = unserialize($data);
		$copy->Text = 'copyObject';
		
		$this->assertNotEquals($cb5, $copy->asa(0));
		$this->assertInstanceOf($cb5::class, $copy->asa(0));
		$this->assertNull($copy->asa(1), "anonymous behavior added between sleep and wake up was attached when it should not have been");
		$this->assertEquals($cb5, $this->component->asa(0));
		$this->assertEquals($cb6, $this->component->asa(1));
		$this->assertEquals($cb3, $this->component->asa('ClassBehavior3'));
		$this->assertEquals($cb4a, $this->component->asa('ClassBehavior4'));
		$this->assertNotNull($copy->asa('CopyBehavior'));
		$this->assertEquals($this->component, $this->component->CopyBehavior->getOwner());
		$this->assertEquals($copy, $copy->CopyBehavior->getOwner());
		$this->assertTrue($copy->CopyBehavior !== $this->component->CopyBehavior);
		$this->assertEquals(3, count($this->component->onMyEvent));
		$this->assertEquals(-1, $this->component->onMyEvent->indexOf([$copy->CopyBehavior, 'ncBehaviorHandler']));
		$this->assertEquals(-1, $copy->onMyEvent->indexOf([$this->component->CopyBehavior, 'ncBehaviorHandler']));
		$this->assertEquals(0, $copy->onMyEvent->indexOf([$copy->CopyBehavior, 'ncBehaviorHandler']));
		$this->assertEquals(1, count($copy->onMyEvent));
		$this->assertEquals($this->component->asa('ClassBehavior1'), $copy->asa('ClassBehavior1'));
		$this->assertNull($this->component->asa('ClassBehavior2'));
		$this->assertNotNull($copy->asa('ClassBehavior2'));
		$this->assertNotEquals($cb2, $copy->asa('ClassBehavior2'));
		$this->assertEquals($this->component->asa('ClassBehavior3'), $copy->asa('ClassBehavior3'));
		$this->assertEquals($cb4a, $copy->asa('ClassBehavior4'));
	}
	
	public function testProtectedSetter()
	{
		
		// Calling protected method doesn't fail, but doesn't call the method
		//   and returns null.
		$this->assertEquals(null, $this->component->getProtectedValue());
		
		//
		$value = null;
		try {
			$value = $this->component->ProtectedValue;
			$this->fail("TInvalidOperationException was not properly thrown when accessing a protected property.");
		} catch(TInvalidOperationException $e) {
		}
		$this->assertEquals(null, $value);
		
		
	}
}
