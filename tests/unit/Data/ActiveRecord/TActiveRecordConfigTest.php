<?php

require_once __DIR__ . '/../../PradoUnitRequires.php';

use Prado\Data\ActiveRecord\TActiveRecordConfig;
use Prado\IModuleDependency;

class TActiveRecordConfigTest extends PHPUnit\Framework\TestCase
{
	use PradoUnitModuleDependencyTrait;

	/**
	 * IModuleDependency contract for TActiveRecordConfig.
	 *
	 * TActiveRecordConfig::init() eagerly materializes the database connection via
	 * $manager->setDbConnection($this->getDbConnection()). When ConnectionID
	 * forwards to another TDataSourceConfig, that upstream's init() must have
	 * applied its <database> element first, so the upstream ID has been declared
	 * a hard init() dependency.
	 */
	public function testImplementsIModuleDependency()
	{
		$this->assertInstanceOf(IModuleDependency::class, new TActiveRecordConfig());
	}

	public function testGetModuleDependencies_noConnectionID_returnsNoDeps()
	{
		$config = new TActiveRecordConfig();
		$this->assertModuleDependency(null, $config->getModuleDependencies(false));
	}

	public function testGetModuleDependencies_connectionIDSet_returnsIt()
	{
		$config = new TActiveRecordConfig();
		$config->setConnectionID('db');
		$this->assertModuleDependency('db', $config->getModuleDependencies(false));
	}

	public function testGetModuleDependencies_returnsSameRegardlessOfIsPreInit()
	{
		$config = new TActiveRecordConfig();
		$config->setConnectionID('db');
		$this->assertModuleDependency(
			$config->getModuleDependencies(true),
			$config->getModuleDependencies(false)
		);
	}
}
