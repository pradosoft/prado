<?php

namespace Prado\Test\Unit;

use Prado\TComponent;

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
