<?php

Prado::using('System.Data.SqlMap.TSqlMapManager');

class Ticket589Test extends UnitTestCase
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
			$this->assertEqual(strpos($e->getMessage(),$expect),0);
		}
	}
}

?>