<?php

namespace Prado\Test\Unit;

use Prado\Util\TBehavior;

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
