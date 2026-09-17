<?php

namespace Prado\Test\Unit;

use Prado\Util\TBehavior;

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
