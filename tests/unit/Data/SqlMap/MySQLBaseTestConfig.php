<?php

namespace Prado\Test\Unit\Data\SqlMap;

use Prado\Data\TDbConnection;

class MySQLBaseTestConfig extends BaseTestConfig
{
	public function __construct()
	{
		$this->_sqlmapConfigFile = SQLMAP_TESTS . '/mysql.xml';
		$this->_scriptDir = SQLMAP_TESTS . '/scripts/mysql/';
		$this->_features = ['insert_id'];
		$dsn = 'mysql:host=localhost;dbname=prado_unitest;port=3306';
		$this->_connection = new TDbConnection($dsn, 'prado_unitest', 'prado_unitest');
	}
}
