<?php

/**
 * TSqliteFileDbLogRoute class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Util\Log;

use Prado\Data\IDataConnection;
use Prado\Data\TDbConnection;
use Prado\Util\Log\TDbLogRoute;

/**
 * A TDbLogRoute over a temporary SQLite file of its own.
 */
class TSqliteFileDbLogRoute extends TDbLogRoute
{
	public ?TDbConnection $testConnection = null;

	public string $testFile = '';

	public function __construct()
	{
		parent::__construct();
		$this->testFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado-dblogroute-' . bin2hex(random_bytes(6)) . '.db';
		$this->testConnection = new TDbConnection('sqlite:' . $this->testFile);
	}

	public function getDbConnection(): IDataConnection
	{
		return $this->testConnection;
	}

	/**
	 * Inserts a row logged at a time.
	 */
	public function insertAt(float $time, string $message = 'old'): void
	{
		$command = $this->testConnection->createCommand('INSERT INTO ' . $this->getLogTableName() . " (level, category, prefix, logtime, message) VALUES (4, 'test', '', :logtime, :message)");
		$command->bindValue(':logtime', sprintf('%F', $time));
		$command->bindValue(':message', $message);
		$command->execute();
	}

	/**
	 * Closes the connection and deletes the SQLite file.
	 */
	public function removeTestFile(): void
	{
		$this->testConnection->setActive(false);
		if (is_file($this->testFile)) {
			@unlink($this->testFile);
		}
	}
}
