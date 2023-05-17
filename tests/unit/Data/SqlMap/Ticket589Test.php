<?php

use Prado\Data\SqlMap\TSqlMapManager;
use Prado\TApplicationComponent;

class Ticket589Test extends PHPUnit\Framework\TestCase
{
	
	
	// ****   Delete the fxevent.cache file from testing.   ****
	//  If there are further tests, this should be moved to the last test using this application.
	protected function tearDown(): void
	{
		$app = Prado::getApplication();
		if ($app !== null) {
			unlink($app->getRuntimePath() . DIRECTORY_SEPARATOR . TApplicationComponent::FX_CACHE_FILE);
		}
	}
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
