<?php

/**
 * TServerEvent class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\Services\ServerEvents;

use Prado\TComponent;

/**
 * TServerEvent class
 *
 * TServerEvent is one event published through an {@see IServerEventBroker}: its
 * event ID, channel, event name, JSON-encodable data, and publish time. It is
 * immutable.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TServerEvent extends TComponent
{
	/** @var string The event ID. */
	private string $_id;

	/** @var string The channel name. */
	private string $_channel;

	/** @var string The event name. */
	private string $_name;

	/** @var mixed The event data. */
	private mixed $_data;

	/** @var float The Unix time of publishing, with microseconds. */
	private float $_time;

	/**
	 * Constructor.
	 * @param string $id The event ID.
	 * @param string $channel The channel name.
	 * @param string $name The event name.
	 * @param mixed $data The JSON-encodable event data.
	 * @param float $time The Unix time of publishing, with microseconds.
	 */
	public function __construct(string $id, string $channel, string $name, mixed $data = null, float $time = 0.0)
	{
		$this->_id = $id;
		$this->_channel = $channel;
		$this->_name = $name;
		$this->_data = $data;
		$this->_time = $time;
		parent::__construct();
	}

	/**
	 * @return string The event ID, the `id:` field of the stream.
	 */
	public function getID(): string
	{
		return $this->_id;
	}

	/**
	 * @return string The channel name.
	 */
	public function getChannel(): string
	{
		return $this->_channel;
	}

	/**
	 * @return string The event name the subscribers filter on.
	 */
	public function getName(): string
	{
		return $this->_name;
	}

	/**
	 * @return mixed The JSON-encodable event data.
	 */
	public function getData(): mixed
	{
		return $this->_data;
	}

	/**
	 * @return float The Unix time of publishing, with microseconds.
	 */
	public function getTime(): float
	{
		return $this->_time;
	}
}
