<?php

namespace Prado\Test\Unit\Util;

use Prado\Util\TSignalsDispatcher;

class TTestSignalsDispatcher extends TSignalsDispatcher {
	
	public function setupAlarms($handler)
	{
		$now = time();
		self::$_nextAlarmTime = $now - 1;
		static::$_alarms[$now - 1] = [$handler];
		static::$_alarms[$now] = [$handler];
		static::$_alarms[$now + 2] = [$handler];
		return $now;
	}
}
