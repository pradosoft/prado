<?php

Prado::using('System.Data.SqlMap.TSqlMapManager');

/**
 * @package System.Data.SqlMap
 */
class Ticket589Test extends PHPUnit_Framework_TestCase
{
	function test()
	{
		$manager = new TSqlMapManager();
		try
		{
			$manager->configureXml(dirname(__FILE__).'/sqlmap.xml');
			$this->fail();
		}catch(TSqlMapConfigurationException $e)
		{
			$expect = 'Invalid property \'parametrClass\' for class \'TSqlMapStatement\' for tag \'<statement id="findNotVisitedWatchedTopicList"';
			$this->assertEquals(strpos($e->getMessage(),$expect),0);
		}
	}
}
