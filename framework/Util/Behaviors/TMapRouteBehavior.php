<?php

/**
 * TMapRouteBehavior class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Behaviors;

use Prado\Util\TBehavior;

/**
 * TMapRouteBehavior routes the changes to Application Parameters to
 * actual functions to affect change.
 * <code>
 *		Prado::getApplication()->getParameters()->attachBehavior('name'
 *			new TMapRouteBehavior('parameterToHook', [$obj, 'setParam']));
 * </code>
 * This code will call $obj->setParam($value) every time the parameter
 * 'parameterToHook' changes in the Application Parameters.
 *
 * <code>
 *		Prado::getApplication()->getParameters()->attachBehavior('name'
 *			new TMapRouteBehavior(null, [$obj, 'setParam']));
 * </code>
 * This code will call $obj->setParam($key, $value) every time the parameter
 * 'parameterToHook' changes in the Application Parameters.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @package Prado\Util\Behaviors
 * @since 4.2.0
 */
class TMapRouteBehavior extends TBehavior
{
	/**
	 * @var string the parameter to check for when there are changes.  This
	 * is public because this call shouldn't add any other getter/setter functions.
	 * it is possible for the Parameter to change its key
	 */
	public $_parameter;
	
	/**
	 * @var callable the parameter to check for when there are changes
	 */
	private $_handler;
	
	/**
	 * @param $parameter string  the name of the map key parameter to hook
	 * @param $handler callable the handler for setting the parameter
	 */
	public function __construct($parameter, $handler)
	{
		$this->_parameter = $parameter;
		$this->_handler = $handler;
		parent::__construct();
	}
	
	/**
	 * This is the dynamic event for handling TMap dyAddItem.
	 * When there is a parameter, when the key is equal to the parameter,
	 * this calls handler($value).
	 * When parameter is null, this calls handler($key, $value).
	 * @param $key string the key of the item being added
	 * @param $value mixed the value of the item being added
	 * @param $callchain TCallChain of event handlers
	 * @return mixed returns the argv[0], chained to all handlers
	 */
	public function dyAddItem($key, $value, $callchain)
	{
		if ($key == $this->_parameter && $key != null && $this->_handler) {
			call_user_func($this->_handler, $value);
		} elseif ($this->_parameter === null) {
			call_user_func($this->_handler, $key, $value);
		}
		return $callchain->dyAddItem($key, $value);
	}
	
	
	/**
	 * This is the dynamic event for handling TMap dyRemoveItem.
	 * When there is a parameter, when the key is equal to the parameter,
	 * this calls handler(null).
	 * When parameter is null, this calls handler($key, null).
	 * @param $key string the key of the item being added
	 * @param $value mixed the value of the item being added
	 * @param $callchain TCallChain of event handlers
	 * @return mixed returns the argv[0], chained to all handlers
	 */
	public function dyRemoveItem($key, $value, $callchain)
	{
		if ($key == $this->_parameter && $key != null && $this->_handler) {
			call_user_func($this->_handler, null);
		} elseif ($this->_parameter == null) {
			call_user_func($this->_handler, $key, null);
		}
		return $callchain->dyRemoveItem($key, $value);
	}
}
