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
use Prado\Exceptions\TInvalidDataTypeException;

/**
 * TMapLazyLoadBehavior class.
 *
 * TMapLazyLoadBehavior handles Application Parameters when there is
 * no parameter key found.  This allows for lazy loading of the parameter.
 * ```php
 *		Prado::getApplication()->getParameters()->attachBehavior('name'
 *			new TMapLazyLoadBehavior([$obj, 'getParam']));
 * ```
 * This behavior will call $obj->getParam($key) every time the key
 * is not found in the TMap or TPriorityMap.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 */
class TMapLazyLoadBehavior extends TBehavior
{
	/**
	 * @var callable the parameter to check for when there are changes
	 */
	private $_handler;

	/**
	 * @param callable $handler the handler for setting the parameter
	 */
	public function __construct($handler)
	{
		if (!is_callable($handler)) {
			throw new TInvalidDataTypeException('maplazyloadbehavior_handler_not_callable');
		}
		$this->_handler = $handler;
		parent::__construct();
	}

	/**
	 * This is the dynamic event for handling TMap dyAddItem.
	 * This calls handler($key).
	 * @param mixed $value the value of the item being added
	 * @param string $key the key of the item being added
	 * @param \Prado\Util\TCallChain $callchain {@see TCallChain} of event handlers
	 * @return mixed returns the argv[0], chained to all handlers
	 */
	public function dyNoItem($value, $key, $callchain)
	{
		if (($nvalue = call_user_func($this->_handler, $key)) !== null) {
			$value = $nvalue;
		}
		return $callchain->dyNoItem($value, $key);
	}
}
