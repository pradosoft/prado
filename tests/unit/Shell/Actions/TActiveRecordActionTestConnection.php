<?php

namespace Prado\Test\Unit\Shell\Actions;

use Prado\Data\TDbConnection;

/**
 * An in-memory SQLite connection that reports a configurable driver name and records
 * each SQL statement passed to {@see createCommand()}. Every command it returns runs
 * `SELECT 1 WHERE 0`, which yields no rows, so `activerecord/generate-all` lists no
 * tables and the test inspects the table-listing SQL chosen for the driver.
 */
class TActiveRecordActionTestConnection extends TDbConnection
{
	/** @var string driver name returned by {@see getDriverName()} */
	public string $driverName = 'sqlite';

	/** @var string[] SQL statements passed to {@see createCommand()} */
	public array $commands = [];

	public function __construct(string $driverName)
	{
		parent::__construct('sqlite::memory:');
		$this->driverName = $driverName;
	}

	public function getDriverName()
	{
		return $this->driverName;
	}

	public function createCommand($sql)
	{
		$this->commands[] = $sql;
		return parent::createCommand('SELECT 1 WHERE 0');
	}
}
