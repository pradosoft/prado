<?php

namespace Prado\Test\Unit\Data\SqlMap;


class BaseTestConfig
{
	protected $_scriptDir;
	protected $_connection;
	protected $_sqlmapConfigFile;

	public function hasFeature($type)
	{
		return false;
	}

	public function getScriptDir()
	{
		return $this->_scriptDir;
	}

	public function getConnection()
	{
		return $this->_connection;
	}

	public function getSqlMapConfigFile()
	{
		return $this->_sqlmapConfigFile;
	}

	public function getScriptRunner()
	{
		return new DefaultScriptRunner();
	}

	public static function createConfigInstance()
	{
		//change this to connection to a different database

		//return new MySQLBaseTestConfig();

		return new SQLiteBaseTestConfig();

		//return new MSSQLBaseTestConfig();
	}
}
