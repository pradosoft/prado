<?php

/**
 * Shared fixture classes for TComponentTest and TComponentRaiseEventTest.
 *
 * This file is require_once'd by both test files so they can share the same
 * fixture classes without redefinition errors.
 */

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
	protected function setProtectedValue($value)
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

class NewComponentStaticHiddenClassBehavior extends TClassBehavior
{
	public static function aStaticMethod(int $value)
	{
		return $value * $value;
	}
	public function getOwnerVisibleMethods(): null|string|array
	{
		return [];
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

	public function init($config)
	{
		if ($config == null) {
			$config = self::NULL_CONFIG;
		}
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
		return ['onMyEvent' => ['barClassEventHandler', function ($sender, $param) { return time(); }]];
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

	public function detach($obj)
	{
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
class OwnerVisibleMethodsBehavior extends TBehavior
{
	public function visibleMethod()
	{
		return 'visible';
	}
	public function hiddenMethod()
	{
		return 'hidden';
	}
	public function getOwnerVisibleMethods(): null|string|array
	{
		return ['visibleMethod'];
	}
}
class OwnerVisibleMethodsClassBehavior extends TClassBehavior
{
	public function visibleClassMethod($obj)
	{
		return 'visibleClass';
	}
	public function hiddenClassMethod($obj)
	{
		return 'hiddenClass';
	}
	public function getOwnerVisibleMethods(): null|string|array
	{
		return 'visibleClassMethod';
	}
}
class OwnerHiddenAllBehavior extends TBehavior
{
	protected $_dyCalled = false;
	public function visibleMethod()
	{
		return 'visible';
	}
	public function dyTextFilter($text, $callchain)
	{
		$this->_dyCalled = true;
		return str_replace('a', 'b', $callchain->dyTextFilter($text));
	}
	public function isDyCalled()
	{
		return $this->_dyCalled;
	}
	public function getOwnerVisibleMethods(): null|string|array
	{
		return [];
	}
}
class OwnerVisibleComposedBehavior extends OwnerVisibleMethodsBehavior
{
	public function anotherVisibleMethod()
	{
		return 'another';
	}
	public function getOwnerVisibleMethods(): null|string|array
	{
		return array_merge((array) parent::getOwnerVisibleMethods(), ['anotherVisibleMethod']);
	}
}
class FooBehaviorWithEvents extends FooBehavior
{
	public function events()
	{
		return ['onMyEvent' => ['fooEventHandler', function ($sender, $param) { return time(); }]];
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

	public function init($config)
	{
		if ($config == null) {
			$config = self::NULL_CONFIG;
		}
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
		return $chain->dyListen($fx);
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
		return $chain->dyPreRaiseEvent($name);
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
