<?php
require_once(dirname(__FILE__).'/BaseTest.php');

/**
 * @package System.DataAccess.SQLMap
 */
class ConnectionTest extends BaseTest
{
	function __construct()
	{
		parent::__construct();
		$this->initSqlmap();
	}

	function testOpenConnection()
	{
		$conn = $this->sqlmap->openConnection();
		$this->assertFalse($conn->getIsClosed());
		$this->sqlmap->closeConnection();
		$this->assertTrue($conn->getIsClosed());
		$this->sqlmap->openConnection();
		$this->assertFalse($conn->getIsClosed());
	}
}

?>