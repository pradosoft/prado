<?php

/**
 * TApplicationClockAwareTrait class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Clock;

use Prado\Prado;

/**
 * TApplicationClockAwareTrait trait
 *
 * TApplicationClockAwareTrait extends {@see \Prado\Util\Clock\TClockAwareTrait} for request-scoped
 * classes that share the one application "now". {@see getClock} resolves in three steps:
 *
 * | Condition | Clock returned |
 * |---|---|
 * | An explicit clock was {@see setClock}-ed | that clock (local override) |
 * | {@see \Prado\Prado::getApplication} is non-null | the application clock, read live |
 * | No application exists | a local {@see \Prado\Util\Clock\TNativeClock}, lazily created |
 *
 * The application clock is read on every call rather than cached, so a clock set on the application
 * after this holder is constructed still applies. Calling `setClock(null)` clears a local override and
 * resumes following the application. A class independent of an application uses plain
 * {@see \Prado\Util\Clock\TClockAwareTrait} instead.
 *
 * ```php
 * class MyControl extends \Prado\Web\UI\TControl
 * {
 *     use \Prado\Util\Clock\TApplicationClockAwareTrait;
 *
 *     public function stamp()
 *     {
 *         return $this->getClock()->time();
 *     }
 * }
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
trait TApplicationClockAwareTrait
{
	use TClockAwareTrait {
		getClock as protected getLocalClock;
	}

	/**
	 * Returns the clock: an explicit local clock, otherwise the application clock read live, otherwise
	 * a lazily created local default.
	 * @return IClock the clock dependency
	 */
	public function getClock(): IClock
	{
		if ($this->_clock !== null) {
			return $this->_clock;
		}
		$application = Prado::getApplication();
		if ($application !== null) {
			return $application->getClock();
		}
		return $this->getLocalClock();
	}
}
