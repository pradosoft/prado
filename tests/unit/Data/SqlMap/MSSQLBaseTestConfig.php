<?php

namespace Prado\Test\Unit\Data\SqlMap;


class MSSQLBaseTestConfig extends BaseTestConfig
{
	public function __construct()
	{
		$this->_sqlmap = SQLMAP_TESTS . '/mssql.xml';
		$this->_connectionString = 'odbc_mssql://sqlmap_tests';
		$this->_scriptDir = SQLMAP_TESTS . '/scripts/mssql/';
		$this->_features = ['insert_id'];
	}
}
