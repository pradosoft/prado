<?php

use Prado\Data\TDataSourceConfig;
use Prado\Data\TDbConnection;
use Prado\Exceptions\TConfigurationException;
use Prado\TApplication;

class TDataSourceConfigTest extends PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
		if (!\Prado\Prado::getApplication()) {
			new TApplication(__DIR__, false, TApplication::CONFIG_TYPE_PHP);
		}
	}

	public function testGetConnectionClassReturnsDefault(): void
	{
		$config = new TDataSourceConfig();
		$this->assertSame(TDbConnection::class, $config->ConnectionClass);
	}

	public function testSetConnectionClass(): void
	{
		$config = new TDataSourceConfig();
		$config->ConnectionClass = 'CustomDbConnection';
		$this->assertSame('CustomDbConnection', $config->ConnectionClass);
	}

	public function testSetConnectionClassThrowsWhenConnectionExists(): void
	{
		$config = new TDataSourceConfig();

		$connProp = new \ReflectionProperty(TDataSourceConfig::class, '_conn');
		$connProp->setAccessible(true);
		$connProp->setValue($config, new TDbConnection());

		$this->expectException(TConfigurationException::class);
		$config->ConnectionClass = 'NewClass';
	}

	public function testGetDatabaseIsAliasForGetDbConnection(): void
	{
		$config = new TDataSourceConfig();
		$this->assertSame($config->getDbConnection(), $config->getDatabase());
	}

	public function testConnectionIdGetterSetter(): void
	{
		$config = new TDataSourceConfig();
		$config->ConnectionID = 'testDb';
		$this->assertSame('testDb', $config->ConnectionID);

		$config->ConnectionID = 'anotherDb';
		$this->assertSame('anotherDb', $config->ConnectionID);
	}

	public function testGetHasDbConnectionInitiallyFalse(): void
	{
		$config = new TDataSourceConfig();
		$this->assertFalse($config->getHasDbConnection());
	}

	public function testFindConnectionByIdThrowsForNonExistentModule(): void
	{
		$finder = new TDataSourceConfig();
		$finder->ConnectionID = 'nonExistentModule';

		$this->expectException(TConfigurationException::class);
		$finder->getDbConnection();
	}

	public function testGetDbConnectionReturnsSameInstance(): void
	{
		$config = new TDataSourceConfig();
		$conn1 = $config->getDbConnection();
		$conn2 = $config->getDbConnection();
		$this->assertSame($conn1, $conn2);
	}
}