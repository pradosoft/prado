<?php

use Prado\Collections\TPriorityList;
use Prado\Exceptions\TApplicationException;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\TComponent;
use Prado\TEventResults;
use Prado\Util\IDynamicMethods;
use Prado\Util\IInstanceCheck;
use Prado\Util\TBehavior;
use Prado\Util\TClassBehavior;

class NewComponent extends TComponent
{
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
	public function fxGlobalListener($sender, $param, $name)
	{
		$this->_callorder[] = 'fxGL';
	}
	public function fxPrimaryGlobalEvent($sender, $param, $name)
	{
		$this->_callorder[] = 'primary';
	}
	public function commonRaiseEventListener($sender, $param, $name)
	{
		$this->_callorder[] = 'com';
	}
	public function postglobalRaiseEventListener($sender, $param, $name)
	{
		$this->_callorder[] = 'postgl';
	}
	public function preglobalRaiseEventListener($sender, $param, $name)
	{
		$this->_callorder[] = 'pregl';
	}
}



class FooClassBehavior extends TClassBehavior
{
	private $_propertyA = 'default';
	private $_baseObject;
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



class FooBehavior extends TBehavior
{
	private $_propertyA = 'default';
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
	public function dyEnableBehaviors($chain)
	{
		$this->lastCall += 13;
		$this->arglist = func_get_args();
	}
	public function dyDisableBehaviors($chain)
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
	protected $component;

	protected function setUp(): void
	{
		$component = new TComponent();
		$component->getEventHandlers('fxAttachClassBehavior')->clear();
		$component->getEventHandlers('fxDetachClassBehavior')->clear();
		unset($component);
		
		$this->component = new NewComponent();
	}


	protected function tearDown(): void
	{
		$this->component = null;
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

	// ensure that the class is listening
		$this->assertEquals(1, $this->component->getEventHandlers('fxAttachClassBehavior')->getCount());

		//Test that the component is not a FooClassBehavior
		$this->assertNull($this->component->asa('FooClassBehavior'), "Component is already a FooClassBehavior and should not have this behavior");

		//Add the FooClassBehavior
		$b1 = $this->component->attachClassBehavior('FooClassBehavior', 'FooClassBehavior');
		$this->assertInstanceof('FooClassBehavior', $b1);
		$b2 = $this->component->attachClassBehavior('FooClassBehavior2', $ob2 = new FooClassBehavior());
		$this->assertInstanceof('FooClassBehavior', $b2);
		$this->assertEquals($ob2, $b2);
		$b3 = $this->component->attachClassBehavior('FooClassBehavior3', ['class' => 'FooClassBehavior', 'propertyA'=>'value']);
		$this->assertInstanceof('FooClassBehavior', $b3);
		$b4 = $this->component->attachClassBehavior('FooRegularBehavior', 'BehaviorTestBehavior');
		$this->assertEquals([$this->component->FooRegularBehavior], $b4);
		$b5 = $this->component->attachClassBehavior('FooRegularBehavior2', ['class' => 'BehaviorTestBehavior', 'Excitement'=>'behavior-value']);
		$this->assertEquals([$this->component->FooRegularBehavior2], $b5);

		//Test that the existing listening component can be a FooClassBehavior
		$this->assertNotNull($this->component->asa('FooClassBehavior'), "Component is does not have the FooClassBehavior and should have this behavior");
		$this->assertNotNull($this->component->asa('FooClassBehavior2'), "Component is does not have the FooClassBehavior2 and should have this behavior");
		$this->assertEquals('default', $this->component->asa('FooClassBehavior2')->PropertyA, "Component is does not have the FooClassBehavior2 and should have this behavior");
		$this->assertNotNull($this->component->asa('FooClassBehavior3'), "Component is does not have the FooClassBehavior3 and should have this behavior");
		$this->assertEquals('value', $this->component->asa('FooClassBehavior3')->PropertyA, "Component is does not have the FooClassBehavior2 and should have this behavior");
		$this->assertNotNull($this->component->asa('FooRegularBehavior'));
		$this->assertEquals('faa', $this->component->asa('FooRegularBehavior')->Excitement);
		$this->assertNotNull($this->component->asa('FooRegularBehavior2'));
		$this->assertEquals('behavior-value', $this->component->asa('FooRegularBehavior2')->Excitement);

		// test if the function modifies new instances of the object
		$anothercomponent = new NewComponent();

		//The new component should be a FooClassBehavior
		$this->assertNotNull($anothercomponent->asa('FooClassBehavior'), "anothercomponent does not have the FooClassBehavior and should");
		$this->assertNotNull($anothercomponent->asa('FooClassBehavior2'), "anothercomponent does not have the FooClassBehavior2 and should");
		$this->assertNotNull($anothercomponent->asa('FooClassBehavior3'), "anothercomponent does not have the FooClassBehavior3 and should");
		$this->assertNotNull($anothercomponent->asa('FooRegularBehavior'), "anothercomponent does not have the FooRegularBehavior and should");
		$this->assertNotNull($anothercomponent->asa('FooRegularBehavior2'), "anothercomponent does not have the FooRegularBehavior2 and should");

		// test when overwriting an existing class behavior, it should throw an TInvalidOperationException
		try {
			$this->component->attachClassBehavior('FooClassBehavior', new BarClassBehavior);
			$this->fail('TInvalidOperationException not raised when overwriting an existing behavior');
		} catch (TInvalidOperationException $e) {
		}
		
		// test when overwriting an existing class behavior, it should throw an TInvalidOperationException
		try {
			$this->component->attachClassBehavior('RegularBehaviorFail', new BehaviorTestBehavior());
			$this->fail('TInvalidOperationException not raised when attaching a regular object behavior to the entire class.  Regular behaviors cannot have more than one owner.');
		} catch (TInvalidOperationException $e) {
		}


		// test TInvalidOperationException when placing a behavior on TComponent
		try {
			$this->component->attachClassBehavior('FooBarBehavior', 'FooBarBehavior', 'Prado\\TComponent');
			$this->fail('TInvalidOperationException not raised when trying to place a behavior on the root object TComponent');
		} catch (TInvalidOperationException $e) {
		}


		// test if the function does not modify any existing objects that are not listening
		//	The FooClassBehavior is already a part of the class behaviors thus the new instance gets the behavior.
		$nolistencomponent = new NewComponentNoListen();

		// test if the function modifies all existing objects that are listening
		//	Adding a behavior to the first object, the second instance should automatically get the class behavior.
		//		This is because the second object is listening to the global events of class behaviors
		$this->component->attachClassBehavior('BarClassBehavior', new BarClassBehavior);
		$this->assertNotNull($anothercomponent->asa('BarClassBehavior'), "anothercomponent is does not have the BarClassBehavior");

		// The no listen object should not have the BarClassBehavior because it was added as a class behavior after the object was instanced
		$this->assertNull($nolistencomponent->asa('BarClassBehavior'), "nolistencomponent has the BarClassBehavior and should not");

		//	But the no listen object should have the FooClassBehavior because the class behavior was installed before the object was instanced
		$this->assertNotNull($nolistencomponent->asa('FooClassBehavior'), "nolistencomponent is does not have the FooClassBehavior");

		//Clear out what was done during this test
		$anothercomponent->unlisten();
		$this->component->detachClassBehavior('FooClassBehavior');
		$this->component->detachClassBehavior('FooClassBehavior2');
		$this->component->detachClassBehavior('FooClassBehavior3');
		$this->component->detachClassBehavior('BarClassBehavior');
		$this->component->detachClassBehavior('FooRegularBehavior');
		$this->component->detachClassBehavior('FooRegularBehavior2');

		// Test attaching of single object behaviors as class-wide behaviors
		$this->component->attachClassBehavior('BarBehaviorObject', 'BarBehavior');
		$this->assertTrue($this->component->asa('BarBehaviorObject') instanceof BarBehavior);
		$this->assertEquals($this->component->BarBehaviorObject->Owner, $this->component);
		$this->component->detachClassBehavior('BarBehaviorObject');
		$this->assertNull($this->component->asa('BarBehaviorObject'));
	}





	public function testDetachClassBehavior()
	{
		// ensure that the component is listening
		$this->assertEquals(1, $this->component->getEventHandlers('fxDetachClassBehavior')->getCount());

		$prenolistencomponent = new NewComponentNoListen();

		//Attach a class behavior
		$b = $this->component->attachClassBehavior('FooClassBehavior', $cb = new FooClassBehavior());
		$this->assertEquals($cb, $b);
		$b = $this->component->attachClassBehavior('FooRegularBehavior', 'BehaviorTestBehavior');
		$rb = $this->component->FooRegularBehavior;
		$this->assertEquals([$rb], $b);

		//Create new components that listen and don't listen to global events
		$anothercomponent = new NewComponent();
		$postnolistencomponent = new NewComponentNoListen();
		$ancomb = $anothercomponent->FooRegularBehavior;

		//ensures that all the Components are properly initialized
		$this->assertEquals(2, $this->component->getEventHandlers('fxDetachClassBehavior')->getCount());
		$this->assertNotNull($this->component->asa('FooClassBehavior'), "Listening Component does not have the FooClassBehavior and should have this behavior");
		$this->assertNull($prenolistencomponent->asa('FooClassBehavior'), "Component has the FooClassBehavior and should _not_ have this behavior");
		$this->assertNotNull($anothercomponent->asa('FooClassBehavior'), "Component does not have the FooClassBehavior and should have this behavior");
		$this->assertNotNull($postnolistencomponent->asa('FooClassBehavior'), "Component does not have the FooClassBehavior and should have this behavior");
		$this->assertNotNull($anothercomponent->asa('FooRegularBehavior'), "Component does not have the FooRegularBehavior and should have this behavior");
		$this->assertNotNull($postnolistencomponent->asa('FooRegularBehavior'), "Component does not have the FooRegularBehavior and should have this behavior");


		$deb = $this->component->detachClassBehavior('FooClassBehavior');
		$this->assertEquals($cb, $deb);
		
		$derb = $this->component->detachClassBehavior('FooRegularBehavior');
		$this->assertEquals([$rb, $ancomb], $derb);
		
		$noReturnBehavior = $this->component->detachClassBehavior('NoBehaviorOfThisName');
		$this->assertNull($noReturnBehavior);

		$this->assertNull($this->component->asa('FooClassBehavior'), "Component has the FooClassBehavior and should _not_ have this behavior");
		$this->assertNull($prenolistencomponent->asa('FooClassBehavior'), "Component has the FooClassBehavior and should _not_ have this behavior");
		$this->assertNull($anothercomponent->asa('FooClassBehavior'), "Component has the FooClassBehavior and should _not_ have this behavior");
		$this->assertNotNull($postnolistencomponent->asa('FooClassBehavior'), "Component does not have the FooClassBehavior and should have this behavior");
		$this->assertNull($anothercomponent->asa('FooRegularBehavior'), "Component has the FooRegularBehavior and should _not_ have this behavior");
		$this->assertNotNull($postnolistencomponent->asa('FooRegularBehavior'), "Component does not have the FooRegularBehavior and should have this behavior");


		//tear down function variables
		$anothercomponent->unlisten();
	}

	public function testGetClassHierarchy()
	{
		$component = new DynamicCatchingComponent;
		$this->assertEquals(['Prado\\Util\\IDynamicMethods', 'DynamicCatchingTrait', 'DynamicCatchingComponent', 'NewComponentNoListen', 'NewComponent', 'Prado\TComponent'], $component->getClassHierarchy());
		$this->assertEquals(['Prado\\Util\\IDynamicMethods', 'DynamicCatchingTrait', 'DynamicCatchingComponent', 'NewComponentNoListen', 'NewComponent', 'Prado\TComponent'], $component->getClassHierarchy(false));
		$this->assertEquals(['prado\\util\\idynamicmethods', 'dynamiccatchingtrait', 'dynamiccatchingcomponent', 'newcomponentnolisten', 'newcomponent', 'prado\tcomponent'], $component->getClassHierarchy(true));
	}


	public function testAsA()
	{
		$anothercomponent = new NewComponent();

		// ensure the component does not have the FooClassBehavior
		$this->assertNull($this->component->asa('FooClassBehavior'));
		$this->assertNull($this->component->asa('FooFooClassBehavior'));
		$this->assertNull($this->component->asa('BarClassBehavior'));
		$this->assertNull($this->component->asa('NonExistantClassBehavior'));

		$this->assertNull($anothercomponent->asa('FooClassBehavior'));
		$this->assertNull($anothercomponent->asa('FooFooClassBehavior'));
		$this->assertNull($anothercomponent->asa('BarClassBehavior'));
		$this->assertNull($anothercomponent->asa('NonExistantClassBehavior'));

		// add the class behavior
		$this->component->attachClassBehavior('FooClassBehavior', new FooClassBehavior);

		//Check that the component has only the class behavior assigned
		$this->assertNotNull($this->component->asa('FooClassBehavior'));
		$this->assertNull($this->component->asa('FooFooClassBehavior'));
		$this->assertNull($this->component->asa('BarClassBehavior'));
		$this->assertNull($this->component->asa('NonExistantClassBehavior'));

		//Check that the component has only the class behavior assigned
		$this->assertNotNull($anothercomponent->asa('FooClassBehavior'));
		$this->assertNull($anothercomponent->asa('FooFooClassBehavior'));
		$this->assertNull($anothercomponent->asa('BarClassBehavior'));
		$this->assertNull($anothercomponent->asa('NonExistantClassBehavior'));

		// remove the class behavior
		$this->component->detachClassBehavior('FooClassBehavior');

		// Check the function doesn't have the behavior any more
		$this->assertNull($this->component->asa('FooClassBehavior'));
		$this->assertNull($this->component->asa('FooFooClassBehavior'));
		$this->assertNull($this->component->asa('BarClassBehavior'));
		$this->assertNull($this->component->asa('NonExistantClassBehavior'));

		$this->assertNull($anothercomponent->asa('FooClassBehavior'));
		$this->assertNull($anothercomponent->asa('FooFooClassBehavior'));
		$this->assertNull($anothercomponent->asa('BarClassBehavior'));
		$this->assertNull($anothercomponent->asa('NonExistantClassBehavior'));




		$this->component->attachBehavior('BarBehavior', new BarBehavior);

		//Check that the component has only the object behavior assigned
		$this->assertNull($this->component->asa('FooBehavior'));
		$this->assertNull($this->component->asa('FooFooBehavior'));
		$this->assertNotNull($this->component->asa('BarBehavior'));
		$this->assertNull($this->component->asa('NonExistantBehavior'));

		//Check that the component has the behavior assigned
		$this->assertNull($anothercomponent->asa('FooBehavior'));
		$this->assertNull($anothercomponent->asa('FooFooBehavior'));
		$this->assertNull($anothercomponent->asa('BarBehavior'));
		$this->assertNull($anothercomponent->asa('NonExistantBehavior'));

		$this->component->detachBehavior('BarBehavior');

		//Check that the component has no object behaviors assigned
		$this->assertNull($this->component->asa('FooBehavior'));
		$this->assertNull($this->component->asa('FooFooBehavior'));
		$this->assertNull($this->component->asa('BarBehavior'));
		$this->assertNull($this->component->asa('NonExistantBehavior'));

		//Check that the component has no behavior assigned
		$this->assertNull($anothercomponent->asa('FooBehavior'));
		$this->assertNull($anothercomponent->asa('FooFooBehavior'));
		$this->assertNull($anothercomponent->asa('BarBehavior'));
		$this->assertNull($anothercomponent->asa('NonExistantBehavior'));

		$anothercomponent->unlisten();
	}

	public function testIsA()
	{
		//This doesn't check the IInstanceCheck functionality, separate function

		$this->assertTrue($this->component->isa('Prado\TComponent'));
		$this->assertTrue($this->component->isa('NewComponent'));
		$this->assertFalse($this->component->isa(new FooBehavior));
		$this->assertFalse($this->component->isa('FooBehavior'));

		//Ensure there is no BarBehavior
		$this->assertNull($this->component->asa('FooFooBehavior'));

		$this->assertFalse($this->component->isa('FooBehavior'));
		$this->assertFalse($this->component->isa('FooFooBehavior'));

		$this->component->attachBehavior('FooFooBehavior', new FooFooBehavior);

		$this->assertNotNull($this->component->asa('FooFooBehavior'));

		$this->assertTrue($this->component->isa('FooBehavior'));
		$this->assertTrue($this->component->isa('FooFooBehavior'));

		$this->component->disableBehaviors();
		// It still has the behavior
		$this->assertNotNull($this->component->asa('FooFooBehavior'));

		// But it is not expressed
		$this->assertFalse($this->component->isa('FooBehavior'));
		$this->assertFalse($this->component->isa('FooFooBehavior'));

		$this->component->enableBehaviors();
		$this->assertNotNull($this->component->asa('FooFooBehavior'));

		$this->assertTrue($this->component->isa('FooFooBehavior'));



		$this->component->attachBehavior('FooBarBehavior', new FooBarBehavior);

		$this->assertTrue($this->component->isa('FooBehavior'));
		$this->assertTrue($this->component->isa('FooBarBehavior'));

		$this->component->disableBehavior('FooBarBehavior');

		$this->assertTrue($this->component->isa('FooBehavior'));
		$this->assertFalse($this->component->isa('FooBarBehavior'));

		$this->component->enableBehavior('FooBarBehavior');
		$this->component->disableBehavior('FooFooBehavior');
		$this->assertFalse($this->component->isa('FooBehavior'));
		$this->assertFalse($this->component->isa('FooFooBehavior'));
		$this->assertTrue($this->component->isa('FooBarBehavior'));

		$this->component->disableBehavior('FooBarBehavior');
		$this->component->disableBehavior('FooFooBehavior');

		$this->assertFalse($this->component->isa('FooBehavior'));
		$this->assertFalse($this->component->isa('FooFooBehavior'));
		$this->assertFalse($this->component->isa('FooBarBehavior'));

		$this->component->enableBehavior('FooBarBehavior');
		$this->component->enableBehavior('FooFooBehavior');

		$this->assertTrue($this->component->isa('FooFooBehavior'));
		$this->assertTrue($this->component->isa('FooBarBehavior'));


		$this->component->detachBehavior('FooFooBehavior');
		$this->component->detachBehavior('FooBarBehavior');

		$this->assertFalse($this->component->isa(new FooBehavior));
		$this->assertFalse($this->component->isa('FooBehavior'));
		$this->assertFalse($this->component->isa(new FooFooBehavior));
		$this->assertFalse($this->component->isa('FooFooBehavior'));
		$this->assertFalse($this->component->isa(new FooBarBehavior));
		$this->assertFalse($this->component->isa('FooBarBehavior'));
	}

	public function testIsA_with_IInstanceCheck()
	{
		$this->assertTrue($this->component->isa('NewComponent'));
		$this->assertFalse($this->component->isa('PreBarBehavior'));

		$this->component->attachBehavior('BarBehavior', $behavior = new BarBehavior);

		$behavior->setInstanceReturn(null);

		$this->assertTrue($this->component->isa('NewComponent'));
		$this->assertTrue($this->component->isa('PreBarBehavior'));
		$this->assertFalse($this->component->isa('FooBehavior'));

		// This forces the iso on the BarBehavior to respond to any class with false
		$behavior->setInstanceReturn(false);
		$this->assertFalse($this->component->isa('PreBarBehavior'));
		$this->assertFalse($this->component->isa('FooBehavior'));

		//This forces the isa on the BarBehavior to respond to any class with true
		$behavior->setInstanceReturn(true);
		$this->assertTrue($this->component->isa('FooBehavior'));
	}
	
	public function testGetBehaviors()
	{
		$this->assertEquals([], $this->component->getBehaviors());
		$b = new FooBarBehavior();
		$this->assertEquals($b, $this->component->attachBehavior('FooBarBehavior', $b));
		$this->assertEquals(['FooBarBehavior' => $b], $this->component->getBehaviors());
		$b->setEnabled(false);
		$this->assertEquals(['FooBarBehavior' => $b], $this->component->getBehaviors());
		$b->setEnabled(true);
		$this->assertEquals(['FooBarBehavior' => $b], $this->component->getBehaviors());
		$this->assertEquals(false, is_object($this->component->getBehaviors()));
		$this->assertEquals(true, is_array($this->component->getBehaviors()));
		$this->assertEquals($b, $this->component->detachBehavior('FooBarBehavior'));
		$this->assertEquals([], $this->component->getBehaviors());
	}

	public function testAttachDetachBehavior()
	{
		try {
			$this->component->faaEverMore(true, true);
			$this->fail('TApplicationException not raised trying to execute a undefined class method');
		} catch (TApplicationException $e) {
		}

		$this->assertNull($this->component->asa('FooBehavior'));
		$this->assertFalse($this->component->isa('FooBehavior'));
		$this->assertNull($this->component->asa('BarBehavior'));
		$this->assertFalse($this->component->isa('BarBehavior'));

		try {
			$this->component->attachBehavior('FooBehavior', new TComponent);
			$this->fail('TApplicationException trying to attach an object that is not a behavior without throwing error');
		} catch (TInvalidDataTypeException $e) {
		}

		$this->component->attachBehavior('FooBehavior', new FooBehavior);

		$this->assertNotNull($this->component->asa('FooBehavior'));
		$this->assertTrue($this->component->isa('FooBehavior'));
		$this->assertNull($this->component->asa('BarBehavior'));
		$this->assertFalse($this->component->isa('BarBehavior'));

		try {
			$this->assertTrue($this->component->faaEverMore(true, true));
		} catch (TApplicationException $e) {
			$this->fail('TApplicationException raised while trying to execute a behavior class method');
		}

		try {
			$this->component->noMethodHere(true);
			$this->fail('TApplicationException not raised trying to execute a undefined class method');
		} catch (TApplicationException $e) {
		}

		$this->assertTrue($this->component->disableBehavior('FooBehavior'));

		//BarBehavior is not a behavior at this time
		$this->assertNull($this->component->disableBehavior('BarBehavior'));

		try {
			$this->component->faaEverMore(true, true);
			$this->fail('TApplicationException not raised trying to execute a undefined class method');
		} catch (TApplicationException $e) {
		}

		$this->assertTrue($this->component->enableBehavior('FooBehavior'));

		//BarBehavior is not a behavior at this time
		$this->assertNull($this->component->enableBehavior('BarBehavior'));

		try {
			$this->assertTrue($this->component->faaEverMore(true, true));
		} catch (TApplicationException $e) {
			$this->fail('TApplicationException raised while trying to execute a behavior class method');
		}

		$this->component->detachBehavior('FooBehavior');

		$this->assertNull($this->component->asa('FooBehavior'));
		$this->assertFalse($this->component->isa('FooBehavior'));
		$this->assertNull($this->component->asa('BarBehavior'));
		$this->assertFalse($this->component->isa('BarBehavior'));
		
		
		$this->component->attachBehavior('FooBehavior', 'FooBehavior');

		$this->assertNotNull($this->component->asa('FooBehavior'));
		$this->assertTrue($this->component->isa('FooBehavior'));
		$this->assertNull($this->component->asa('BarBehavior'));
		$this->assertFalse($this->component->isa('BarBehavior'));
		$this->assertEquals('default',$this->component->asa('FooBehavior')->PropertyA);
		
		$this->component->detachBehavior('FooBehavior');

		$this->assertNull($this->component->asa('FooBehavior'));
		$this->assertFalse($this->component->isa('FooBehavior'));
		$this->assertNull($this->component->asa('BarBehavior'));
		$this->assertFalse($this->component->isa('BarBehavior'));
		
		
		$this->component->attachBehavior('FooBehavior', ['class' => 'FooBehavior', 'PropertyA'=>'value']);

		$this->assertNotNull($this->component->asa('FooBehavior'));
		$this->assertTrue($this->component->isa('FooBehavior'));
		$this->assertNull($this->component->asa('BarBehavior'));
		$this->assertFalse($this->component->isa('BarBehavior'));
		$this->assertEquals('value',$this->component->asa('FooBehavior')->PropertyA);
		
		$this->component->detachBehavior('FooBehavior');

		$this->assertNull($this->component->asa('FooBehavior'));
		$this->assertFalse($this->component->isa('FooBehavior'));
		$this->assertNull($this->component->asa('BarBehavior'));
		$this->assertFalse($this->component->isa('BarBehavior'));
	}

	public function testAttachDetachBehaviors()
	{
		$this->assertNull($this->component->asa('FooBehavior'));
		$this->assertNull($this->component->asa('BarBehavior'));
		$this->assertNull($this->component->asa('FooBarBehavior'));
		$this->assertNull($this->component->asa('PreBarBehavior'));

		$this->component->attachBehaviors(['FooFooBehavior' => new FooFooBehavior, 'BarBehavior' => new BarBehavior, 'PreBarBehavior' => new PreBarBehavior]);

		$this->assertNull($this->component->asa('FooBehavior'));
		$this->assertNotNull($this->component->asa('FooFooBehavior'));
		$this->assertNotNull($this->component->asa('BarBehavior'));
		$this->assertNull($this->component->asa('FooBarBehavior'));
		$this->assertNotNull($this->component->asa('PreBarBehavior'));

		$this->assertTrue($this->component->isa('FooFooBehavior'));
		$this->assertTrue($this->component->isa('FooBehavior'));
		$this->assertTrue($this->component->isa('BarBehavior'));
		$this->assertTrue($this->component->isa('PreBarBehavior'));
		$this->assertFalse($this->component->isa('FooBarBehavior'));

		$this->component->detachBehaviors(['FooFooBehavior' => new FooFooBehavior, 'BarBehavior' => new BarBehavior]);

		$this->assertNull($this->component->asa('FooBehavior'));
		$this->assertNull($this->component->asa('FooFooBehavior'));
		$this->assertNull($this->component->asa('BarBehavior'));
		$this->assertNull($this->component->asa('FooBarBehavior'));
		$this->assertNotNull($this->component->asa('PreBarBehavior'));

		$this->assertFalse($this->component->isa('FooFooBehavior'));
		$this->assertFalse($this->component->isa('FooBehavior'));
		$this->assertFalse($this->component->isa('BarBehavior'));
		$this->assertFalse($this->component->isa('FooBarBehavior'));
		$this->assertTrue($this->component->isa('PreBarBehavior'));



		//	testing if we can detachBehaviors just by the name of the behavior instead of an array of the behavior
		$this->component->attachBehaviors(['FooFooBehavior' => new FooFooBehavior, 'BarBehavior' => new BarBehavior]);

		$this->assertTrue($this->component->isa('FooBehavior'));
		$this->assertTrue($this->component->isa('BarBehavior'));

		$this->component->detachBehaviors(['FooFooBehavior', 'BarBehavior']);

		$this->assertNull($this->component->asa('FooBehavior'));
		$this->assertNull($this->component->asa('FooFooBehavior'));
		$this->assertNull($this->component->asa('BarBehavior'));
		$this->assertNull($this->component->asa('FooBarBehavior'));

		$this->assertFalse($this->component->isa('FooFooBehavior'));
		$this->assertFalse($this->component->isa('FooBehavior'));
		$this->assertFalse($this->component->isa('BarBehavior'));
		$this->assertFalse($this->component->isa('FooBarBehavior'));
	}


	public function testClearBehaviors()
	{
		$this->assertNull($this->component->asa('FooBehavior'));
		$this->assertNull($this->component->asa('BarBehavior'));
		$this->assertNull($this->component->asa('FooBarBehavior'));
		$this->assertNull($this->component->asa('PreBarBehavior'));

		$this->component->attachBehaviors(['FooFooBehavior' => new FooFooBehavior, 'BarBehavior' => new BarBehavior, 'PreBarBehavior' => new PreBarBehavior]);

		$this->assertNull($this->component->asa('FooBehavior'));
		$this->assertNotNull($this->component->asa('FooFooBehavior'));
		$this->assertNotNull($this->component->asa('BarBehavior'));
		$this->assertNull($this->component->asa('FooBarBehavior'));
		$this->assertNotNull($this->component->asa('PreBarBehavior'));

		$this->component->clearBehaviors();

		$this->assertNull($this->component->asa('FooBehavior'));
		$this->assertNull($this->component->asa('BarBehavior'));
		$this->assertNull($this->component->asa('FooBarBehavior'));
		$this->assertNull($this->component->asa('PreBarBehavior'));
	}

	public function testEnableDisableBehavior()
	{
		$this->assertNull($this->component->enableBehavior('FooBehavior'));
		$this->assertNull($this->component->disableBehavior('FooBehavior'));

		try {
			$this->component->faaEverMore(true, true);
			$this->fail('TApplicationException not raised trying to execute a undefined class method');
		} catch (TApplicationException $e) {
		}

		$this->component->attachBehavior('FooBehavior', new FooBehavior);

		$this->assertTrue($this->component->isa('FooBehavior'));
		try {
			$this->assertTrue($this->component->faaEverMore(true, true));
		} catch (TApplicationException $e) {
			$this->fail('TApplicationException raised while trying to execute a behavior class method');
		}

		$this->assertTrue($this->component->disableBehavior('FooBehavior'));

		$this->assertFalse($this->component->isa('FooBehavior'));

		try {
			$this->component->faaEverMore(true, true);
			$this->fail('TApplicationException not raised trying to execute a undefined class method');
		} catch (TApplicationException $e) {
		}

		$this->assertTrue($this->component->enableBehavior('FooBehavior'));

		$this->assertTrue($this->component->isa('FooBehavior'));
		try {
			$this->assertTrue($this->component->faaEverMore(true, true));
		} catch (TApplicationException $e) {
			$this->fail('TApplicationException raised while trying to execute a behavior class method');
		}



		$this->assertNull($this->component->enableBehavior('BarClassBehavior'));
		$this->assertNull($this->component->disableBehavior('BarClassBehavior'));

		try {
			$this->component->moreFunction(true, true);
			$this->fail('TApplicationException not raised trying to execute an undefined class method');
		} catch (TApplicationException $e) {
		}

		$this->component->attachClassBehavior('BarClassBehavior', new BarClassBehavior);

		$this->assertFalse($this->component->enableBehavior('BarClassBehavior'));
		$this->assertFalse($this->component->disableBehavior('BarClassBehavior'));

		try {
			$this->assertTrue($this->component->moreFunction(true, true));
		} catch (TApplicationException $e) {
			$this->fail('TApplicationException raised while trying to execute a behavior class method');
		}

		$this->component->detachClassBehavior('BarClassBehavior');
	}


	public function testBehaviorFunctionCalls()
	{
		$this->component->attachBehavior('FooBarBehavior', $behavior = new FooBarBehavior);
		$this->component->attachClassBehavior('FooClassBehavior', $classbehavior = new FooClassBehavior);

		// Test the Class Methods
		$this->assertEquals(12, $this->component->faaEverMore(3, 4));

		// Check that the called object is shifted in front of the array of a class behavior call
		$this->assertEquals($this->component, $this->component->getLastClassObject());


		//Test the FooBarBehavior
		$this->assertEquals(27, $this->component->moreFunction(3, 3));

		$this->assertTrue($this->component->disableBehavior('FooBarBehavior'));
		try {
			$this->assertNull($this->component->moreFunction(3, 4));
			$this->fail('TApplicationException not raised trying to execute a disabled behavior');
		} catch (TApplicationException $e) {
		}
		$this->assertTrue($this->component->enableBehavior('FooBarBehavior'));

		// Test the global event space, this should work and return false because no function implements these methods
		$this->assertNull($this->component->fxSomeUndefinedGlobalEvent());
		$this->assertNull($this->component->dySomeUndefinedIntraObjectEvent());

		$this->component->detachClassBehavior('FooClassBehavior');



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
			$this->fail('TApplicationException not raised trying to execute a disabled behavior');
		} catch (TApplicationException $e) {
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

		$this->component->attachBehavior('BehaviorTestBehavior', new BehaviorTestBehavior);

		$this->assertTrue(isset($this->component->BehaviorTestBehavior));
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

		$this->component->attachBehavior('BehaviorTestBehavior', new BehaviorTestBehavior);
		$this->assertTrue($this->component->asa('BehaviorTestBehavior') instanceof BehaviorTestBehavior);
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
		$this->component->disableBehavior('BehaviorTestBehavior');
		unset($this->component->Excitement);
		$this->component->enableBehavior('BehaviorTestBehavior');
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
		$this->assertEquals(1, count($this->component->BehaviorTestBehavior->onBehaviorEvent));
		unset($this->component->onBehaviorEvent);
		$this->assertEquals(0, count($this->component->onBehaviorEvent));
		$this->assertEquals(0, count($this->component->BehaviorTestBehavior->onBehaviorEvent));

		// Remove behavior via unset
		unset($this->component->BehaviorTestBehavior);
		$this->assertFalse($this->component->asa('BehaviorTestBehavior') instanceof BehaviorTestBehavior);
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
		$this->assertTrue($this->component->hasMethod('eventReturnValue'));
		$this->assertTrue($this->component->hasMethod('eventreturnvalue'));
		$this->assertFalse($this->component->hasMethod('noeventreturnvalue'));
	
		// fx won't throw an error if any of these fx function are called on an object.
		//	It is a special prefix event designation that every object responds to all events/methods.
		$this->assertTrue($this->component->hasMethod('fxAttachClassBehavior'));
		$this->assertTrue($this->component->hasMethod('fxattachclassbehavior'));
	
		$this->assertTrue($this->component->hasMethod('fxNonExistantGlobalEvent'));
		$this->assertTrue($this->component->hasMethod('fxnonexistantglobalevent'));
	
		$this->assertTrue($this->component->hasMethod('dyNonExistantLocalEvent'));
		$this->assertTrue($this->component->hasMethod('dynonexistantlocalevent'));
	
	
		//Test behavior events
		$this->assertFalse($this->component->hasMethod('getExcitement'));
		$this->component->attachBehavior('BehaviorTestBehavior', new BehaviorTestBehavior());
		$this->assertTrue($this->component->hasMethod('getExcitement'));
		$this->assertTrue($this->component->BehaviorTestBehavior->hasMethod('getExcitement'));
		
		//Test behaviors within behaviors.
		$this->component->BehaviorTestBehavior->attachBehavior('SubBehavior', new FooFooClassBehavior());
		$this->assertTrue($this->component->BehaviorTestBehavior->hasMethod('faafaaEverMore'));
		$this->assertFalse($this->component->hasMethod('faafaaEverMore'));
		$this->assertEquals('ffemResult', $this->component->BehaviorTestBehavior->faafaaEverMore(null, null, null));
		try {
			$this->component->faafaaEverMore(null, null, null);
			$this->fail('TApplicationException not raised when calling a behaviors behaviors method');
		} catch (TApplicationException $e) {
		}
		
	
		$this->component->disableBehavior('BehaviorTestBehavior');
		$this->assertFalse($this->component->hasMethod('getExcitement'));
		$this->component->enableBehavior('BehaviorTestBehavior');
		$this->assertTrue($this->component->hasMethod('getExcitement'));
	
		$this->component->disableBehaviors();
		$this->assertFalse($this->component->hasMethod('getExcitement'));
		$this->component->enableBehaviors();
		$this->assertTrue($this->component->hasMethod('getExcitement'));
	}

	public function testHasEvent()
	{
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
		$this->component->attachBehavior('BehaviorTestBehavior', new BehaviorTestBehavior);
		$this->assertTrue($this->component->hasEvent('onBehaviorEvent'));
		$this->assertTrue($this->component->BehaviorTestBehavior->hasEvent('onBehaviorEvent'));

		$this->component->disableBehavior('BehaviorTestBehavior');
		$this->assertFalse($this->component->hasEvent('onBehaviorEvent'));
		$this->component->enableBehavior('BehaviorTestBehavior');
		$this->assertTrue($this->component->hasEvent('onBehaviorEvent'));
		
		$this->component->disableBehaviors();
		$this->assertFalse($this->component->hasEvent('onBehaviorEvent'));
		$this->component->enableBehaviors();
		$this->assertTrue($this->component->hasEvent('onBehaviorEvent'));
	}

	public function testHasEventHandler()
	{
		$this->assertFalse($this->component->hasEventHandler('OnMyEvent'));
		$this->component->attachEventHandler('OnMyEvent', 'foo');
		$this->assertTrue($this->component->hasEventHandler('OnMyEvent'));

		$this->assertFalse($this->component->hasEventHandler('fxNonExistantGlobalEvent'));
		$this->component->attachEventHandler('fxNonExistantGlobalEvent', 'foo');
		$this->assertTrue($this->component->hasEventHandler('fxNonExistantGlobalEvent'));

		//Test behavior events
		$this->assertFalse($this->component->hasEventHandler('onBehaviorEvent'));
		$this->component->attachBehavior('BehaviorTestBehavior', new BehaviorTestBehavior);
		$this->assertFalse($this->component->hasEventHandler('onBehaviorEvent'));
		$this->assertFalse($this->component->BehaviorTestBehavior->hasEventHandler('onBehaviorEvent'));

		$this->component->attachEventHandler('onBehaviorEvent', 'foo');
		$this->assertTrue($this->component->hasEventHandler('onBehaviorEvent'));

		$this->component->disableBehavior('BehaviorTestBehavior');
		$this->assertFalse($this->component->hasEvent('onBehaviorEvent'));
		$this->assertFalse($this->component->hasEventHandler('onBehaviorEvent'));
		$this->component->enableBehavior('BehaviorTestBehavior');
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

		$this->component->attachBehavior('BehaviorTestBehavior', new BehaviorTestBehavior);
		$list = $this->component->getEventHandlers('onBehaviorEvent');
		$this->assertTrue(($list instanceof TPriorityList) && ($list->getCount() === 0));
		$this->component->attachEventHandler('onBehaviorEvent', 'foo');
		$this->assertTrue(($list instanceof TPriorityList) && ($list->getCount() === 1));

		$this->component->disableBehavior('BehaviorTestBehavior');
		try {
			$list = $this->component->getEventHandlers('onBehaviorEvent');
			$this->fail('exception not raised when getting event handlers for undefined event');
		} catch (TInvalidOperationException $e) {
		}
		$this->component->enableBehavior('BehaviorTestBehavior');
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
		$this->component->attachBehavior('BehaviorTestBehavior', new BehaviorTestBehavior);

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

		$this->component->disableBehavior('BehaviorTestBehavior');
		try {
			$this->component->attachEventHandler('onBehaviorEvent', 'bar');
			$this->fail('exception not raised when getting event handlers for undefined event');
		} catch (TInvalidOperationException $e) {
		}
		$this->component->enableBehavior('BehaviorTestBehavior');
		
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
		$this->component->attachBehavior('BehaviorTestBehavior', new BehaviorTestBehavior);

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
		$component->attachEventHandler('OnMyEvent', [$this->component, 'myEventHandler']);
		$this->assertFalse($this->component->isEventHandled());
		$this->assertFalse($this->component->Object->isEventHandled());
		$component->raiseEvent('OnMyEvent', $this, null);
		$this->assertTrue($this->component->isEventHandled());
		$this->assertFalse($this->component->Object->isEventHandled());
		
		$this->component->resetEventHandled();
		$this->component->Object->resetEventHandled();
		$component->detachEventHandler('OnMyEvent', [$this->component, 'myEventHandler']);
		
		
		$component->attachEventHandler('OnMyEvent', [$this->component, 'Object.myEventHandler']);
		$this->assertFalse($this->component->isEventHandled());
		$this->assertFalse($this->component->Object->isEventHandled());
		$component->raiseEvent('OnMyEvent', $this, null);
		$this->assertFalse($this->component->isEventHandled());
		$this->assertTrue($this->component->Object->isEventHandled());
		
		$component->detachEventHandler('OnMyEvent', [$this->component, 'myEventHandler']);
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
			$this->fail('exception not raised when evaluating an undefined method by the object and behavior');
		} catch (TApplicationException $e) {
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
			$this->fail('exception not raised when evaluating an undefined method by the object and behavior');
		} catch (TApplicationException $e) {
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
		
		$this->assertNotNull($copy->asa('CopyBehavior'));
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
		$cb1->propertya = 'second value';
		NewComponent::attachClassBehavior('ClassBehavior2', $cb2 = new FooFooClassBehavior());
		NewComponent::attachClassBehavior('ClassBehavior4', $cb4 = new FooClassBehavior());
		$cb4->propertya = '4th value';
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
	
		$data = serialize($this->component);
		NewComponent::detachClassBehavior('ClassBehavior2');
		$this->assertNull($this->component->asa('ClassBehavior2'));
		NewComponent::attachClassBehavior('ClassBehavior3', $cb3 = new BarClassBehavior());
		$this->assertNotNull($this->component->asa('ClassBehavior3'));
		NewComponent::detachClassBehavior('ClassBehavior4');
		NewComponent::attachClassBehavior('ClassBehavior4', $cb4a = new FooClassBehavior());
		$cb4a->propertya = 'new 4th value';
		$cb2->propertya = '3rd value';
		$copy = unserialize($data);
		$copy->Text = 'copyObject';
		
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
		
		NewComponent::detachClassBehavior('ClassBehavior1');
		NewComponent::detachClassBehavior('ClassBehavior3');
		
	}
}
