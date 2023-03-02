<?php

use Prado\Data\SqlMap\TSqlMapManager;

class Ticket589Test extends PHPUnit\Framework\TestCase
{
	public function test()
	{
		$manager = new TSqlMapManager();
		try {
			$manager->configureXml(__DIR__ . '/sqlmap.xml');
			$this->fail();
		} catch (\Prado\Data\SqlMap\DataMapper\TSqlMapConfigurationException $e) {
			$expect = 'Invalid property \'parametrClass\' for class \'TSqlMapStatement\' for tag \'<statement id="findNotVisitedWatchedTopicList"';
			$this->assertEquals(strpos($e->getMessage(), $expect), 0);
		}
	}
}
