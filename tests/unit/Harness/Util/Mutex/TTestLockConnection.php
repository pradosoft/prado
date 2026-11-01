<?php

/**
 * TTestLockConnection class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Harness\Util\Mutex;

use Prado\Data\IDataConnection;

/**
 * TTestLockConnection is an {@see IDataConnection} of a named driver whose
 * {@see createCommand()} returns {@see TTestLockCommand}s, for testing the database
 * mutexes without a server.
 *
 * Each created command takes the next entry of {@see $results}, an array of
 * {@see TTestLockCommand} constructor arguments after the SQL: `[scalar, output,
 * executeResult]`.  {@see $commands} holds the created commands in order.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTestLockConnection implements IDataConnection
{
	/** @var array<int, array> the results of the next commands. */
	public array $results = [];

	/** @var TTestLockCommand[] the created commands. */
	public array $commands = [];

	private bool $_active = true;

	/**
	 * @param string $driver the driver name the connection reports.
	 */
	public function __construct(public string $driver)
	{
	}

	public function getDriverName()
	{
		return $this->driver;
	}

	public function getActive()
	{
		return $this->_active;
	}

	public function setActive($value)
	{
		$this->_active = (bool) $value;
	}

	public function createCommand($query)
	{
		$args = array_shift($this->results) ?? [];
		return $this->commands[] = new TTestLockCommand($query, ...$args);
	}

	public function beginTransaction()
	{
		return null;
	}

	public function getCurrentTransaction()
	{
		return null;
	}

	public function getLastInsertID($sequenceName = '')
	{
		return '';
	}

	public function getConnectionString()
	{
		return $this->driver . ':test';
	}

	public function quoteString($str)
	{
		return "'" . str_replace("'", "''", $str) . "'";
	}

	public function getColumnCase()
	{
		return null;
	}

	public function setColumnCase($value)
	{
	}

	public function getAttribute($name)
	{
		return null;
	}

	public function setAttribute($name, $value)
	{
	}
}
