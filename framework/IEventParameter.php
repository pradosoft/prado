<?php

/**
 * IEventParameter class.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado;

/**
 * IEventParameter class.
 *
 * This interface is for any event parameter to capture the event being raised.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.0
 */
interface IEventParameter
{
	/**
	 * Gets the Event Name specified.
	 * @return string The name of the event.
	 */
	public function getEventName(): string;

	/**
	 * Sets the Event Name.
	 * @param string $value The name of the event.
	 */
	public function setEventName(string $value);
}
