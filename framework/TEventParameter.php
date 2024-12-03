<?php

/**
 * TComponent, TPropertyValue classes
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 *
 * Global Events, intra-object events, Class behaviors, expanded behaviors
 * @author Brad Anderson <javalizard@mac.com>
 *
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado;

/**
 * TEventParameter class.
 *
 * TEventParameter is the base class for all event parameter classes.
 * When raising an event, this class captures the event name being raised by being
 * an {@see \Prado\IEventParameter}. TEventParameter encapsulates the parameter data for
 * events. The event parameter is set via {@see setParameter Parameter} property
 * or by constructor parameter.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TEventParameter extends \Prado\TComponent implements IEventParameter
{
	private string $_eventName = '';
	private mixed $_param;

	/**
	 * Constructor.
	 * @param null|mixed $parameter parameter of the event
	 * @since 4.3.0
	 */
	public function __construct(mixed $parameter = null)
	{
		$this->setParameter($parameter);
		parent::__construct();
	}

	/**
	 * @return string name of the event
	 * @since 4.3.0
	 */
	public function getEventName(): string
	{
		return $this->_eventName;
	}

	/**
	 * @param string $value name of the event
	 * @since 4.3.0
	 */
	public function setEventName(string $value)
	{
		$this->_eventName = $value;
	}

	/**
	 * @return mixed parameter of the event
	 * @since 4.3.0
	 */
	public function getParameter(): mixed
	{
		return $this->_param;
	}

	/**
	 * @param mixed $value parameter of the event
	 * @since 4.3.0
	 */
	public function setParameter(mixed $value)
	{
		$this->_param = $value;
	}
}
