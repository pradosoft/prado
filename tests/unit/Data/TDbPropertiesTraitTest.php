<?php

use Prado\Caching\TDbCache;
use Prado\Data\TDataSourceConfig;
use Prado\Data\TDbConnection;
use Prado\Data\TDbPropertiesTrait;
use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\TComponent;

/**
 * Minimal user of the trait.
 *
 * It has no getApplication(), so createDbConnection() takes its application
 * from Prado::getApplication().
 */
class DbPropertiesTraitFixture extends TComponent
{
	use TDbPropertiesTrait;
}

/**
 * A user of the trait that names its own invalid-connection message key.
 */
class DbPropertiesTraitCustomKeyFixture extends TComponent
{
	use TDbPropertiesTrait;

	protected function getConnectionInvalidExceptionKey(): string
	{
		return 'dblogroute_connectionid_invalid';
	}
}

/**
 * A user of the trait that supplies its own connection when no ConnectionID is set.
 */
class DbPropertiesTraitCustomConnFixture extends TComponent
{
	use TDbPropertiesTrait;

	public ?TDbConnection $customConnection = null;

	protected function getCustomDbConnection(): ?TDbConnection
	{
		return $this->customConnection;
	}
}

/**
 * A user of the trait that keeps its data in a sqlite file in the runtime path.
 */
class DbPropertiesTraitSqliteFixture extends TComponent
{
	use TDbPropertiesTrait;

	protected function getSqliteDatabaseName(): ?string
	{
		return 'dbpropertiestrait.db';
	}
}

/**
 * Covers createDbConnection() resolving its TDataSourceConfig module.
 *
 * The application is reached through Prado::getApplication() when the using
 * class has none of its own, and that returns null outside of a running
 * application. createDbConnection() reads the module through the null-safe
 * operator, so a missing application produces the same TConfigurationException
 * as an unknown module ID rather than an Error.
 */
class TDbPropertiesTraitTest extends PHPUnit\Framework\TestCase
{
	/**
	 * Runs $fn with no application registered and restores the application after.
	 * The PRADO_TEST_RUN constant lets setApplication() bypass the singleton guard.
	 * @param callable $fn the code to run without an application
	 * @return mixed whatever $fn returns
	 */
	private function withoutApplication(callable $fn): mixed
	{
		$app = Prado::getApplication();
		Prado::setApplication(null);

		try {
			return $fn();
		} finally {
			Prado::setApplication($app);
		}
	}

	/**
	 * Calls the protected createDbConnection() and returns the exception it throws.
	 * @param object $subject the object using the trait
	 * @param ?string $connectionID the argument to pass, null to use the property
	 * @return ?TConfigurationException the exception thrown, null when none was
	 */
	private function connectionException(object $subject, ?string $connectionID = null): ?TConfigurationException
	{
		try {
			PradoUnit::invoke($subject, 'createDbConnection', $connectionID);
		} catch (TConfigurationException $e) {
			return $e;
		}
		return null;
	}

	// ------- no application, a ConnectionID is set -------

	public function testCreateDbConnectionWithoutApplicationThrowsConfigurationException()
	{
		$subject = new DbPropertiesTraitFixture();
		$subject->setConnectionID('db');

		$e = $this->withoutApplication(fn() => $this->connectionException($subject));

		$this->assertInstanceOf(TConfigurationException::class, $e);
	}

	public function testCreateDbConnectionWithoutApplicationReportsTheInvalidConnectionKey()
	{
		$subject = new DbPropertiesTraitFixture();
		$subject->setConnectionID('db');

		$e = $this->withoutApplication(fn() => $this->connectionException($subject));

		$this->assertSame('dbproperties_connectionid_invalid', $e->getErrorCode());
		$this->assertStringContainsString('db', $e->getMessage());
		$this->assertStringContainsString('DbPropertiesTraitFixture', $e->getMessage());
	}

	public function testCreateDbConnectionWithoutApplicationHonorsACustomExceptionKey()
	{
		$subject = new DbPropertiesTraitCustomKeyFixture();
		$subject->setConnectionID('db');

		$e = $this->withoutApplication(fn() => $this->connectionException($subject));

		$this->assertSame('dblogroute_connectionid_invalid', $e->getErrorCode());
	}

	public function testCreateDbConnectionWithoutApplicationAppliesToTheConnectionIDArgument()
	{
		$subject = new DbPropertiesTraitFixture();

		$e = $this->withoutApplication(fn() => $this->connectionException($subject, 'argument-db'));

		$this->assertSame('dbproperties_connectionid_invalid', $e->getErrorCode());
		$this->assertStringContainsString('argument-db', $e->getMessage());
	}

	/**
	 * A missing application and an unknown module ID reach the same exception,
	 * which is what reading the module null-safely buys.
	 */
	public function testAMissingApplicationAndAnUnknownModuleGiveTheSameError()
	{
		$subject = new DbPropertiesTraitFixture();
		$subject->setConnectionID('no-such-module');

		$without = $this->withoutApplication(fn() => $this->connectionException($subject));
		$with = $this->connectionException($subject);

		$this->assertNotNull($with);
		$this->assertSame($with->getErrorCode(), $without->getErrorCode());
		$this->assertSame($with->getMessage(), $without->getMessage());
	}

	// ------- an application is present -------

	public function testCreateDbConnectionReturnsTheConnectionOfTheDataSourceModule()
	{
		$app = Prado::getApplication();
		$modules = PradoUnit::getProp($app, '_modules');

		try {
			$app->setModule('dbpropertiestrait.datasource', new TDataSourceConfig());
			$subject = new DbPropertiesTraitFixture();
			$subject->setConnectionID('dbpropertiestrait.datasource');

			$conn = PradoUnit::invoke($subject, 'createDbConnection');

			$this->assertInstanceOf(TDbConnection::class, $conn);
			$this->assertFalse($conn->getActive());
		} finally {
			PradoUnit::setProp($app, '_modules', $modules);
		}
	}

	public function testCreateDbConnectionRejectsAModuleThatIsNotADataSourceConfig()
	{
		$app = Prado::getApplication();
		$modules = PradoUnit::getProp($app, '_modules');

		try {
			$app->setModule('dbpropertiestrait.notadatasource', new TDbCache());
			$subject = new DbPropertiesTraitFixture();
			$subject->setConnectionID('dbpropertiestrait.notadatasource');

			$e = $this->connectionException($subject);

			$this->assertSame('dbproperties_connectionid_invalid', $e->getErrorCode());
		} finally {
			PradoUnit::setProp($app, '_modules', $modules);
		}
	}

	// ------- no ConnectionID: the branch that never reads a module -------

	public function testCreateDbConnectionWithoutApplicationUsesTheCustomConnection()
	{
		$subject = new DbPropertiesTraitCustomConnFixture();
		$subject->customConnection = new TDbConnection('sqlite::memory:');

		$conn = $this->withoutApplication(fn() => PradoUnit::invoke($subject, 'createDbConnection'));

		$this->assertSame($subject->customConnection, $conn);
	}

	public function testCreateDbConnectionWithoutApplicationRequiresAConnectionID()
	{
		$subject = new DbPropertiesTraitFixture();

		$e = $this->withoutApplication(fn() => $this->connectionException($subject));

		$this->assertSame('dbproperties_property_required', $e->getErrorCode());
		$this->assertStringContainsString('ConnectionID', $e->getMessage());
	}

	// ------- no ConnectionID: the sqlite database in the runtime path -------

	public function testCreateDbConnectionBuildsTheSqliteFileInTheRuntimePath()
	{
		$subject = new DbPropertiesTraitSqliteFixture();

		$conn = PradoUnit::invoke($subject, 'createDbConnection');

		$expected = 'sqlite:' . Prado::getApplication()->getRuntimePath() . DIRECTORY_SEPARATOR . 'dbpropertiestrait.db';
		$this->assertInstanceOf(TDbConnection::class, $conn);
		$this->assertSame($expected, $conn->getConnectionString());
	}

	public function testCreateDbConnectionWithoutApplicationRequiresAConnectionIDForASqliteDatabase()
	{
		$subject = new DbPropertiesTraitSqliteFixture();

		$e = $this->withoutApplication(fn() => $this->connectionException($subject));

		$this->assertSame('dbproperties_property_required', $e->getErrorCode());
		$this->assertStringContainsString('ConnectionID', $e->getMessage());
	}

	/**
	 * A sqlite database has nowhere to live without a runtime path, so no
	 * connection is handed back rooted at the file system root.
	 */
	public function testCreateDbConnectionWithoutApplicationBuildsNoSqliteConnection()
	{
		$subject = new DbPropertiesTraitSqliteFixture();
		$conn = null;

		$this->withoutApplication(function () use ($subject, &$conn) {
			try {
				$conn = PradoUnit::invoke($subject, 'createDbConnection');
			} catch (TConfigurationException $e) {
			}
		});

		$this->assertNull($conn);
	}

	/**
	 * An application whose runtime path is not resolved yet is handled the same
	 * way as no application at all.
	 */
	public function testCreateDbConnectionWithoutARuntimePathRequiresAConnectionID()
	{
		$app = Prado::getApplication();
		$runtimePath = PradoUnit::getProp($app, '_runtimePath');

		try {
			PradoUnit::setProp($app, '_runtimePath', null);
			$subject = new DbPropertiesTraitSqliteFixture();

			$e = $this->connectionException($subject);

			$this->assertSame('dbproperties_property_required', $e->getErrorCode());
		} finally {
			PradoUnit::setProp($app, '_runtimePath', $runtimePath);
		}
	}
}
