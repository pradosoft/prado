<?php
/**
 * TControl, TControlCollection, TEventParameter and INamingContainer class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI;

/**
 * TBroadcastEventParameter class
 *
 * TBroadcastEventParameter encapsulates the parameter data for
 * events that are broadcasted. The name of of the event is specified via
 * {@see setName Name} property while the event parameter is via
 * {@see setParameter Parameter} property.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TBroadcastEventParameter extends \Prado\TEventParameter
{
	/**
	 * Constructor.
	 * @param string $name name of the broadcast event
	 * @param null|mixed $parameter parameter of the broadcast event
	 */
	public function __construct(string $name = '', mixed $parameter = null)
	{
		parent::setEventName($name);
		parent::__construct($parameter);
	}

	/**
	 * @return string name of the broadcast event
	 */
	public function getName()
	{
		return parent::getEventName();
	}

	/**
	 * @param string $value name of the broadcast event
	 */
	public function setName($value)
	{
		parent::setEventName($value);
	}

	/**
	 * @param string $value name of the broadcast event
	 * @since 4.2.3
	 */
	public function setEventName(string $value)
	{
		if ($this->getEventName() === '') {
			parent::setEventName($value);
		}
	}

}
