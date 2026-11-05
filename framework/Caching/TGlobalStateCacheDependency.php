<?php

/**
 * TCache and cache dependency classes.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Caching;

use Prado\Prado;
use Prado\TPropertyValue;

/**
 * TGlobalStateCacheDependency class
 *
 * TGlobalStateCacheDependency reports a cache-dependency change when the
 * value of the global application state named by {@see setStateName StateName}
 * differs from the value captured when the dependency was created.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.1.0
 */
class TGlobalStateCacheDependency extends TCacheDependency
{
	/** @var string name of the global application state being tracked */
	private string $_stateName;
	/** @var mixed value of the global state captured at construction time */
	private mixed $_stateValue;

	/**
	 * @param string $name the name of the global state to track.
	 */
	public function __construct(string $name)
	{
		$this->setStateName($name);
		parent::__construct();
	}

	/**
	 * Returns the stored state name without side effects.
	 * @return string the tracked global state name.
	 * @since 4.4.0
	 */
	protected function getStateNameDirect(): string
	{
		return $this->_stateName;
	}

	/**
	 * Stores the state name directly without re-capturing the state value.
	 * @param string $value the global state name.
	 * @since 4.4.0
	 */
	protected function setStateNameDirect(string $value): void
	{
		$this->_stateName = $value;
	}

	/**
	 * @return string the name of the tracked global state.
	 */
	public function getStateName(): string
	{
		return $this->getStateNameDirect();
	}

	/**
	 * Sets the global state name and re-captures its current value.
	 * @param string $value the name of the global state to track.
	 * @see \Prado\TApplication::setGlobalState()
	 */
	public function setStateName($value)
	{
		$this->setStateNameDirect(TPropertyValue::ensureString($value));
		$this->updateStateValue();
	}

	/**
	 * Re-captures the current value of the tracked global state.
	 * Call this after the state has been intentionally updated so that the next
	 * `getHasChanged()` check uses the refreshed baseline.
	 * @since 4.4.0
	 */
	public function updateStateValue(): void
	{
		$this->_stateValue = Prado::getApplication()->getGlobalState($this->getStateName());
	}

	/**
	 * @return bool whether the tracked global state's current value differs from
	 *   the value captured at construction time (or the last {@see updateStateValue()} call).
	 */
	public function getHasChanged(): bool
	{
		return $this->_stateValue !== Prado::getApplication()->getGlobalState($this->getStateName());
	}
}
