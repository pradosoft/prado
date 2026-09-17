<?php

namespace Prado\Test\Unit\Data\SqlMap;

use Prado\Data\TDbConnection;

class SQLiteBaseTestConfig extends BaseTestConfig
{
	protected $baseFile;
	protected $targetFile;

	public function __construct()
	{
		$this->_sqlmapConfigFile = SQLMAP_TESTS . '/sqlite.xml';
		$this->_scriptDir = SQLMAP_TESTS . '/scripts/sqlite/';

		$this->targetFile = SQLMAP_TESTS . '/sqlite/tests.db';
		$this->baseFile = realpath(SQLMAP_TESTS . '/sqlite/backup.db');
		$this->_connection = new TDbConnection("sqlite:{$this->targetFile}");
	}

	public function getScriptRunner()
	{
		return new CopyFileScriptRunner($this->baseFile, $this->targetFile);
	}
}
