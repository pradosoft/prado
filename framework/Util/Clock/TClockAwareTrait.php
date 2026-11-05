<?php

/**
 * TClockAwareTrait class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Clock;

use Prado\Exceptions\TConfigurationException;
use Prado\Prado;

/**
 * TClockAwareTrait trait
 *
 * TClockAwareTrait gives a class a `Clock` dependency: an {@see \Prado\Util\Clock\IClock} read through
 * {@see getClock}/{@see setClock}. When no clock is set, {@see getClock} lazily creates one through
 * {@see createClock}, which instantiates the class named by {@see getClockClass} — a real-time
 * {@see \Prado\Util\Clock\TNativeClock} by default. Using a settable clock is therefore deliberate:
 * inject a {@see \Prado\Util\Clock\TMockClock} with {@see setClock} (then pin it with the mock's
 * `setNow()`), or override {@see getClockClass} to change the default class. This mirrors Symfony's
 * `ClockAwareTrait`.
 *
 * This is the inverse of {@see \Prado\Util\Clock\TClockTrait}: that trait makes a class *be* a clock;
 * this one makes a class *hold* one. A class that is both, an `IClock` that holds one, is a
 * {@see \Prado\Util\Clock\TClockDecorator}. Traits cannot declare constants, so {@see getClockClass} is
 * the overridable seam for the default class.
 *
 * ```php
 * class MyService extends \Prado\TComponent
 * {
 *     use \Prado\Util\Clock\TClockAwareTrait;
 *
 *     public function doWork()
 *     {
 *         $when = $this->getClock()->now();
 *     }
 * }
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
trait TClockAwareTrait
{
	// =========================================================================
	// Properties
	// =========================================================================

	/** @var ?IClock the clock dependency, lazily created on first {@see getClock} */
	private ?IClock $_clock = null;

	/**
	 * Returns the clock, creating the default one through {@see createClock} when none is set.
	 * @return IClock the clock dependency
	 */
	public function getClock(): IClock
	{
		if ($this->_clock === null) {
			$this->_clock = $this->createClock();
		}
		return $this->_clock;
	}

	/**
	 * @param ?IClock $clock the clock dependency, or null to recreate the default on next use
	 * @return $this for method chaining.
	 */
	public function setClock(?IClock $clock): static
	{
		$this->_clock = $clock;
		return $this;
	}

	// =========================================================================
	// Helpers
	// =========================================================================

	/**
	 * Creates the default clock through {@see \Prado\Prado::createComponent}. Encapsulates instantiation so
	 * subclasses can override either this or the lighter {@see getClockClass}, validating that the named
	 * class implements {@see \Prado\Util\Clock\IClock}.
	 * @throws TConfigurationException when {@see getClockClass} does not name an IClock
	 * @return IClock a new clock of the {@see getClockClass} class
	 */
	protected function createClock(): IClock
	{
		$class = $this->getClockClass();
		if (!is_a($class, IClock::class, true)) {
			throw new TConfigurationException('clockawaretrait_invalid_clock_class', $class);
		}
		return Prado::createComponent($class);
	}

	/**
	 * The default clock class instantiated by {@see createClock}. Override to change the default; this is
	 * the trait's stand-in for a class constant. {@see createClock} validates the returned class implements
	 * {@see \Prado\Util\Clock\IClock}.
	 * @return string the clock class name; must name a {@see \Prado\Util\Clock\IClock} implementation
	 */
	protected function getClockClass(): string
	{
		return TNativeClock::class;
	}
}
