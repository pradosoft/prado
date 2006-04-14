<?php

require_once(dirname(__FILE__).'/BaseTest.php');

/**
 * @package System.DataAccess.SQLMap
 */
class ConfigureTest extends BaseTest
{

	function testConfigureAbsolutePath()
	{
		$builder = new TDomSqlMapBuilder;
		$filename = realpath(dirname(__FILE__).'/resources/sqlmap.xml');
		$sqlmap = $builder->configure($filename);
		$this->assertNotNull($sqlmap);
	}
}

?>