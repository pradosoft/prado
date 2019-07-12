<?php
/**
 * TCache and cache dependency classes.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Caching
 */

namespace Prado\Caching;

use Prado\Prado;

/**
 * TGlobalStateCacheDependency class.
 *
 * TGlobalStateCacheDependency checks if a global state is changed or not.
 * If the global state is changed, the dependency is reported as changed.
 * To specify which global state this dependency should check with,
 * set {@link setStateName StateName} to the name of the global state.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Caching
 * @since 3.1.0
 */
class TGlobalStateCacheDependency extends TCacheDependency
{
	private $_stateName;
	private $_stateValue;

	/**
	 * Constructor.
	 * @param string $name the name of the global state
	 */
	public function __construct($name)
	{
		$this->setStateName($name);
	}

	/**
	 * @return string the name of the global state
	 */
	public function getStateName()
	{
		return $this->_stateName;
	}

	/**
	 * @param string $value the name of the global state
	 * @see TApplication::setGlobalState
	 */
	public function setStateName($value)
	{
		$this->_stateName = $value;
		$this->_stateValue = Prado::getApplication()->getGlobalState($value);
	}

	/**
	 * Performs the actual dependency checking.
	 * This method returns true if the specified global state is changed.
	 * @return bool whether the dependency is changed or not.
	 */
	public function getHasChanged()
	{
		return $this->_stateValue !== Prado::getApplication()->getGlobalState($this->_stateName);
	}
}
