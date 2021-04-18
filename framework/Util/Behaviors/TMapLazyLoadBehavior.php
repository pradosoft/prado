<?php

/**
 * TMapLazyLoadBehavior class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Behaviors;

use Prado\Util\TBehavior;

/**
 * TMapLazyLoadBehavior handles Application Parameters whene there is
 * no parameter.  This allows for lazy loading of the parameter.
 * <code>
 *		Prado::getApplication()->getParameters()->attachBehavior('name'
 *			new TMapLazyLoadBehavior([$obj, 'getParam']));
 * </code>
 * This code will call $obj->getParam($value) every time the parameter
 * is not found in the TMap or TPriorityMap.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @package Prado\Util\Behaviors
 * @since 4.2.0
 */
class TMapLazyLoadBehavior extends TBehavior
{
	
	/**
	 * @var callable the parameter to check for when there are changes
	 */
	private $_handler;
	
	/**
	 * @param $handler callable the handler for setting the parameter
	 */
	public function __construct($handler)
	{
		$this->_handler = $handler;
		parent::__construct();
	}
	
	/**
	 * This is the dynamic event for handling TMap dyAddItem
	 * @param $value mixed the value of the item being added
	 * @param $key string the key of the item being added
	 * @param $callchain {@link TCallChain} of event handlers
	 * @return mixed returns the argv[0], chained to all handlers
	 */
	 public function dyNoItem($value, $key, $callchain)
	{
		if($this->_handler) {
			$value = call_user_func($this->_handler, $key);
		}
		return $callchain->dyNoItem($value, $key);
	}
}
