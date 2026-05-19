<?php

/**
 * PradoUnitTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

/**
 * Minimal stub that satisfies the $connection parameter contract of
 * {@see PradoUnit::processException()}: provides a {@see getDriverName()} method
 * returning a caller-supplied string, without opening any real database connection.
 */
class PradoUnitMockConnection
{
	public function __construct(private string $driver)
	{
	}

	public function getDriverName(): string
	{
		return $this->driver;
	}
}

/**
 * Base class for the hierarchy used in PradoUnit reflection tests.
 * Declares private and protected fields so they exercise ancestor-level
 * property access through the full class hierarchy walk.
 */
class PradoUnitTestBase
{
	private int $_basePrivate = 10;
	protected string $_baseProtected = 'base';
}

/**
 * Child class that adds its own private and public fields.
 * Together with PradoUnitTestBase this gives a two-level hierarchy covering
 * all four visibility levels (private×2, protected, public).
 */
class PradoUnitTestChild extends PradoUnitTestBase
{
	private float $_childPrivate = 2.5;
	public bool $childPublic = true;
}

/**
 * Base class for the hierarchy used in PradoUnit *static* reflection tests.
 * Declares private and protected static fields so ancestor-level static property
 * access through the full class hierarchy walk is exercised.
 */
class PradoUnitStaticTestBase
{
	private static int $_baseStaticPrivate = 10;
	protected static string $_baseStaticProtected = 'base';
}

/**
 * Child class that adds its own private and public static fields.
 * Together with PradoUnitStaticTestBase this gives a two-level hierarchy covering
 * all four visibility levels for static properties.
 */
class PradoUnitStaticTestChild extends PradoUnitStaticTestBase
{
	private static float $_childStaticPrivate = 2.5;
	public static bool $childStaticPublic = true;
}

/**
 * Tests for {@see PradoUnit}.
 *
 * Covers: snapshot/restore across a two-level hierarchy, partial-snapshot
 * filtering, getProp/setProp for private ancestor fields, and the
 * ReflectionException path for missing properties.
 *
 * @package System
 */
class PradoUnitTest extends PHPUnit\Framework\TestCase
{
	// -----------------------------------------------------------------------
	// snapshot
	// -----------------------------------------------------------------------

	public function testSnapshotCapturesAllLevels()
	{
		$obj = new PradoUnitTestChild();

		$snap = PradoUnit::snapshot($obj);

		// All four fields must be present regardless of visibility or depth.
		$this->assertArrayHasKey('_basePrivate', $snap);
		$this->assertArrayHasKey('_baseProtected', $snap);
		$this->assertArrayHasKey('_childPrivate', $snap);
		$this->assertArrayHasKey('childPublic', $snap);
	}

	public function testSnapshotCapturesCorrectValues()
	{
		$obj = new PradoUnitTestChild();

		$snap = PradoUnit::snapshot($obj);

		$this->assertSame(10, $snap['_basePrivate']);
		$this->assertSame('base', $snap['_baseProtected']);
		$this->assertSame(2.5, $snap['_childPrivate']);
		$this->assertSame(true, $snap['childPublic']);
	}

	public function testSnapshotRespectsNameFilter()
	{
		$obj = new PradoUnitTestChild();

		$snap = PradoUnit::snapshot($obj, ['_basePrivate', 'childPublic']);

		$this->assertArrayHasKey('_basePrivate', $snap);
		$this->assertArrayHasKey('childPublic', $snap);
		$this->assertArrayNotHasKey('_baseProtected', $snap);
		$this->assertArrayNotHasKey('_childPrivate', $snap);
	}

	// -----------------------------------------------------------------------
	// restore
	// -----------------------------------------------------------------------

	public function testRestoreWritesAllLevels()
	{
		$obj = new PradoUnitTestChild();
		$snap = PradoUnit::snapshot($obj);

		// Mutate via reflection so we don't rely on the API under test.
		PradoUnit::setProp($obj, '_basePrivate', 99);
		PradoUnit::setProp($obj, '_baseProtected', 'mutated');
		PradoUnit::setProp($obj, '_childPrivate', 9.9);
		$obj->childPublic = false;

		PradoUnit::restore($obj, $snap);

		$this->assertSame(10, PradoUnit::getProp($obj, '_basePrivate'));
		$this->assertSame('base', PradoUnit::getProp($obj, '_baseProtected'));
		$this->assertSame(2.5, PradoUnit::getProp($obj, '_childPrivate'));
		$this->assertTrue($obj->childPublic);
	}

	public function testRestoreWithPartialSnapshotLeavesOtherPropsUntouched()
	{
		$obj = new PradoUnitTestChild();
		$partialSnap = PradoUnit::snapshot($obj, ['_basePrivate']);

		PradoUnit::setProp($obj, '_basePrivate', 99);
		PradoUnit::setProp($obj, '_baseProtected', 'mutated');

		PradoUnit::restore($obj, $partialSnap);

		// Restored.
		$this->assertSame(10, PradoUnit::getProp($obj, '_basePrivate'));
		// Left alone — not in the snapshot.
		$this->assertSame('mutated', PradoUnit::getProp($obj, '_baseProtected'));
	}

	// -----------------------------------------------------------------------
	// getProp
	// -----------------------------------------------------------------------

	public function testGetPropReadsPrivateAncestorField()
	{
		$obj = new PradoUnitTestChild();

		$this->assertSame(10, PradoUnit::getProp($obj, '_basePrivate'));
	}

	public function testGetPropReadsProtectedField()
	{
		$obj = new PradoUnitTestChild();

		$this->assertSame('base', PradoUnit::getProp($obj, '_baseProtected'));
	}

	public function testGetPropReadsChildPrivateField()
	{
		$obj = new PradoUnitTestChild();

		$this->assertSame(2.5, PradoUnit::getProp($obj, '_childPrivate'));
	}

	public function testGetPropThrowsForMissingProperty()
	{
		$obj = new PradoUnitTestChild();

		$this->expectException(\ReflectionException::class);
		PradoUnit::getProp($obj, 'doesNotExist');
	}

	// -----------------------------------------------------------------------
	// setProp
	// -----------------------------------------------------------------------

	public function testSetPropWritesPrivateAncestorField()
	{
		$obj = new PradoUnitTestChild();

		PradoUnit::setProp($obj, '_basePrivate', 42);

		$this->assertSame(42, PradoUnit::getProp($obj, '_basePrivate'));
	}

	public function testSetPropWritesChildPrivateField()
	{
		$obj = new PradoUnitTestChild();

		PradoUnit::setProp($obj, '_childPrivate', 3.14);

		$this->assertSame(3.14, PradoUnit::getProp($obj, '_childPrivate'));
	}

	public function testSetPropThrowsForMissingProperty()
	{
		$obj = new PradoUnitTestChild();

		$this->expectException(\ReflectionException::class);
		PradoUnit::setProp($obj, 'doesNotExist', 'value');
	}

	// -----------------------------------------------------------------------
	// snapshot / restore round-trip on a real TApplication
	// -----------------------------------------------------------------------

	public function testSnapshotRestoreRoundTripOnRealApp()
	{
		$app = Prado::getApplication();
		$originalMode = $app->getMode();

		$snap = TTestApplication::snapshotApp($app, ['_mode']);

		$app->setMode(\Prado\TApplicationMode::Normal);
		$this->assertNotSame($originalMode, $app->getMode());

		TTestApplication::restoreApp($snap, $app);

		$this->assertSame($originalMode, $app->getMode());
	}

	// -----------------------------------------------------------------------
	// snapshotStatic
	// -----------------------------------------------------------------------

	public function testSnapshotStaticCapturesAllLevels(): void
	{
		$snap = PradoUnit::snapshotStatic(PradoUnitStaticTestChild::class);

		$this->assertArrayHasKey('_baseStaticPrivate', $snap);
		$this->assertArrayHasKey('_baseStaticProtected', $snap);
		$this->assertArrayHasKey('_childStaticPrivate', $snap);
		$this->assertArrayHasKey('childStaticPublic', $snap);
	}

	public function testSnapshotStaticCapturesCorrectValues(): void
	{
		$snap = PradoUnit::snapshotStatic(PradoUnitStaticTestChild::class);

		$this->assertSame(10, $snap['_baseStaticPrivate']);
		$this->assertSame('base', $snap['_baseStaticProtected']);
		$this->assertSame(2.5, $snap['_childStaticPrivate']);
		$this->assertSame(true, $snap['childStaticPublic']);
	}

	public function testSnapshotStaticRespectsNameFilter(): void
	{
		$snap = PradoUnit::snapshotStatic(PradoUnitStaticTestChild::class, ['_baseStaticPrivate', 'childStaticPublic']);

		$this->assertArrayHasKey('_baseStaticPrivate', $snap);
		$this->assertArrayHasKey('childStaticPublic', $snap);
		$this->assertArrayNotHasKey('_baseStaticProtected', $snap);
		$this->assertArrayNotHasKey('_childStaticPrivate', $snap);
	}

	// -----------------------------------------------------------------------
	// restoreStatic
	// -----------------------------------------------------------------------

	public function testRestoreStaticWritesAllLevels(): void
	{
		$snap = PradoUnit::snapshotStatic(PradoUnitStaticTestChild::class);

		PradoUnit::setStaticProp(PradoUnitStaticTestChild::class, '_baseStaticPrivate', 99);
		PradoUnit::setStaticProp(PradoUnitStaticTestChild::class, '_baseStaticProtected', 'mutated');
		PradoUnit::setStaticProp(PradoUnitStaticTestChild::class, '_childStaticPrivate', 9.9);
		PradoUnitStaticTestChild::$childStaticPublic = false;

		PradoUnit::restoreStatic(PradoUnitStaticTestChild::class, $snap);

		$this->assertSame(10, PradoUnit::getStaticProp(PradoUnitStaticTestChild::class, '_baseStaticPrivate'));
		$this->assertSame('base', PradoUnit::getStaticProp(PradoUnitStaticTestChild::class, '_baseStaticProtected'));
		$this->assertSame(2.5, PradoUnit::getStaticProp(PradoUnitStaticTestChild::class, '_childStaticPrivate'));
		$this->assertTrue(PradoUnitStaticTestChild::$childStaticPublic);
	}

	public function testRestoreStaticWithPartialSnapshotLeavesOtherPropsUntouched(): void
	{
		$partialSnap = PradoUnit::snapshotStatic(PradoUnitStaticTestChild::class, ['_baseStaticPrivate']);

		PradoUnit::setStaticProp(PradoUnitStaticTestChild::class, '_baseStaticPrivate', 99);
		PradoUnit::setStaticProp(PradoUnitStaticTestChild::class, '_baseStaticProtected', 'mutated');

		PradoUnit::restoreStatic(PradoUnitStaticTestChild::class, $partialSnap);

		// Restored.
		$this->assertSame(10, PradoUnit::getStaticProp(PradoUnitStaticTestChild::class, '_baseStaticPrivate'));
		// Left alone — not in the snapshot.
		$this->assertSame('mutated', PradoUnit::getStaticProp(PradoUnitStaticTestChild::class, '_baseStaticProtected'));

		// Clean up so other tests see the original values.
		PradoUnit::setStaticProp(PradoUnitStaticTestChild::class, '_baseStaticProtected', 'base');
	}

	// -----------------------------------------------------------------------
	// getStaticProp
	// -----------------------------------------------------------------------

	public function testGetStaticPropReadsPrivateAncestorField(): void
	{
		$this->assertSame(10, PradoUnit::getStaticProp(PradoUnitStaticTestChild::class, '_baseStaticPrivate'));
	}

	public function testGetStaticPropReadsProtectedField(): void
	{
		$this->assertSame('base', PradoUnit::getStaticProp(PradoUnitStaticTestChild::class, '_baseStaticProtected'));
	}

	public function testGetStaticPropReadsChildPrivateField(): void
	{
		$this->assertSame(2.5, PradoUnit::getStaticProp(PradoUnitStaticTestChild::class, '_childStaticPrivate'));
	}

	public function testGetStaticPropThrowsForMissingProperty(): void
	{
		$this->expectException(\ReflectionException::class);
		PradoUnit::getStaticProp(PradoUnitStaticTestChild::class, 'doesNotExist');
	}

	// -----------------------------------------------------------------------
	// setStaticProp
	// -----------------------------------------------------------------------

	public function testSetStaticPropWritesPrivateAncestorField(): void
	{
		$original = PradoUnit::getStaticProp(PradoUnitStaticTestChild::class, '_baseStaticPrivate');

		PradoUnit::setStaticProp(PradoUnitStaticTestChild::class, '_baseStaticPrivate', 42);
		$this->assertSame(42, PradoUnit::getStaticProp(PradoUnitStaticTestChild::class, '_baseStaticPrivate'));

		PradoUnit::setStaticProp(PradoUnitStaticTestChild::class, '_baseStaticPrivate', $original);
	}

	public function testSetStaticPropWritesChildPrivateField(): void
	{
		$original = PradoUnit::getStaticProp(PradoUnitStaticTestChild::class, '_childStaticPrivate');

		PradoUnit::setStaticProp(PradoUnitStaticTestChild::class, '_childStaticPrivate', 3.14);
		$this->assertSame(3.14, PradoUnit::getStaticProp(PradoUnitStaticTestChild::class, '_childStaticPrivate'));

		PradoUnit::setStaticProp(PradoUnitStaticTestChild::class, '_childStaticPrivate', $original);
	}

	public function testSetStaticPropThrowsForMissingProperty(): void
	{
		$this->expectException(\ReflectionException::class);
		PradoUnit::setStaticProp(PradoUnitStaticTestChild::class, 'doesNotExist', 'value');
	}

	// -----------------------------------------------------------------------
	// isCI / isGitHubActions / getCIEnvironment / skipSlowTests
	// -----------------------------------------------------------------------

	/**
	 * Temporarily override an env variable, run $callback, then restore the original
	 * value (or unset it if it was not set before).
	 */
	private function withEnv(string $name, string|false $value, callable $callback): void
	{
		$original = getenv($name);
		if ($value === false) {
			putenv($name);              // unset
		} else {
			putenv("{$name}={$value}");
		}
		try {
			$callback();
		} finally {
			if ($original === false) {
				putenv($name);          // restore: unset
			} else {
				putenv("{$name}={$original}");
			}
		}
	}

	public function testIsCI_returnsFalse_whenNoCIVarSet(): void
	{
		// Clear every variable getCIEnvironment() inspects.
		$vars = [
			'GITHUB_ACTIONS', 'TRAVIS', 'CIRCLECI', 'GITLAB_CI', 'JENKINS_URL',
			'SCRUTINIZER', 'BITBUCKET_BUILD_NUMBER', 'DRONE', 'TEAMCITY_VERSION',
			'APPVEYOR', 'TF_BUILD', 'CI_NAME', 'BUILDKITE', 'HEROKU_TEST_RUN_ID', 'CI',
		];
		$saved = [];
		foreach ($vars as $v) {
			$saved[$v] = getenv($v);
			putenv($v);
		}

		try {
			$this->assertFalse(PradoUnit::isCI());
		} finally {
			foreach ($saved as $k => $val) {
				if ($val !== false) {
					putenv("{$k}={$val}");
				}
			}
		}
	}

	public function testIsCI_returnsTrue_whenCIVarSet(): void
	{
		$this->withEnv('CI', 'true', function () {
			$this->assertTrue(PradoUnit::isCI());
		});
	}

	public function testIsGitHubActions_returnsFalse_whenNotSet(): void
	{
		$this->withEnv('GITHUB_ACTIONS', false, function () {
			$this->assertFalse(PradoUnit::isGitHubActions());
		});
	}

	public function testIsGitHubActions_returnsTrue_whenSet(): void
	{
		$this->withEnv('GITHUB_ACTIONS', 'true', function () {
			$this->assertTrue(PradoUnit::isGitHubActions());
		});
	}

	public function testGetCIEnvironment_returnsNull_whenNoCIVar(): void
	{
		$vars = [
			'GITHUB_ACTIONS', 'TRAVIS', 'CIRCLECI', 'GITLAB_CI', 'JENKINS_URL',
			'SCRUTINIZER', 'BITBUCKET_BUILD_NUMBER', 'DRONE', 'TEAMCITY_VERSION',
			'APPVEYOR', 'TF_BUILD', 'CI_NAME', 'BUILDKITE', 'HEROKU_TEST_RUN_ID', 'CI',
		];
		$saved = [];
		foreach ($vars as $v) {
			$saved[$v] = getenv($v);
			putenv($v);
		}

		try {
			$this->assertNull(PradoUnit::getCIEnvironment());
		} finally {
			foreach ($saved as $k => $val) {
				if ($val !== false) {
					putenv("{$k}={$val}");
				}
			}
		}
	}

	public function testGetCIEnvironment_detectsGitHubActions(): void
	{
		$this->withEnv('GITHUB_ACTIONS', 'true', function () {
			$this->assertSame('GitHub Actions', PradoUnit::getCIEnvironment());
		});
	}

	public function testGetCIEnvironment_detectsTravisCI(): void
	{
		$this->withEnv('GITHUB_ACTIONS', false, function () {
			$this->withEnv('TRAVIS', 'true', function () {
				$this->assertSame('Travis CI', PradoUnit::getCIEnvironment());
			});
		});
	}

	public function testGetCIEnvironment_detectsCircleCI(): void
	{
		$this->withEnv('GITHUB_ACTIONS', false, function () {
			$this->withEnv('TRAVIS', false, function () {
				$this->withEnv('CIRCLECI', 'true', function () {
					$this->assertSame('CircleCI', PradoUnit::getCIEnvironment());
				});
			});
		});
	}

	public function testGetCIEnvironment_detectsGitLabCI(): void
	{
		$this->withEnv('GITHUB_ACTIONS', false, function () {
			$this->withEnv('TRAVIS', false, function () {
				$this->withEnv('CIRCLECI', false, function () {
					$this->withEnv('GITLAB_CI', 'true', function () {
						$this->assertSame('GitLab CI', PradoUnit::getCIEnvironment());
					});
				});
			});
		});
	}

	public function testGetCIEnvironment_detectsJenkins(): void
	{
		$this->withEnv('GITHUB_ACTIONS', false, function () {
			$this->withEnv('TRAVIS', false, function () {
				$this->withEnv('CIRCLECI', false, function () {
					$this->withEnv('GITLAB_CI', false, function () {
						$this->withEnv('JENKINS_URL', 'http://jenkins.example.com/', function () {
							$this->assertSame('Jenkins', PradoUnit::getCIEnvironment());
						});
					});
				});
			});
		});
	}

	public function testGetCIEnvironment_genericCIFallback(): void
	{
		$vars = [
			'GITHUB_ACTIONS', 'TRAVIS', 'CIRCLECI', 'GITLAB_CI', 'JENKINS_URL',
			'SCRUTINIZER', 'BITBUCKET_BUILD_NUMBER', 'DRONE', 'TEAMCITY_VERSION',
			'APPVEYOR', 'TF_BUILD', 'CI_NAME', 'BUILDKITE', 'HEROKU_TEST_RUN_ID',
		];
		$saved = [];
		foreach ($vars as $v) {
			$saved[$v] = getenv($v);
			putenv($v);
		}

		$savedCI = getenv('CI');
		putenv('CI=true');

		try {
			$this->assertSame('CI', PradoUnit::getCIEnvironment());
		} finally {
			foreach ($saved as $k => $val) {
				if ($val !== false) {
					putenv("{$k}={$val}");
				}
			}
			if ($savedCI === false) {
				putenv('CI');
			} else {
				putenv("CI={$savedCI}");
			}
		}
	}

	public function testSkipSlowTests_returnsFalse_byDefault(): void
	{
		$this->withEnv('PRADO_UNITTEST_SKIP_SLOW', false, function () {
			$this->assertFalse(PradoUnit::skipSlowTests());
		});
	}

	public function testSkipSlowTests_returnsTrue_whenSet(): void
	{
		$this->withEnv('PRADO_UNITTEST_SKIP_SLOW', '1', function () {
			$this->assertTrue(PradoUnit::skipSlowTests());
		});
	}

	public function testSkipSlowTests_returnsFalse_whenSetToOtherValue(): void
	{
		$this->withEnv('PRADO_UNITTEST_SKIP_SLOW', 'yes', function () {
			$this->assertFalse(PradoUnit::skipSlowTests());
		});
	}

	// -----------------------------------------------------------------------
	// getCIEnvironment — remaining CI providers
	// -----------------------------------------------------------------------

	/**
	 * Clears every env var inspected by getCIEnvironment(), sets $var to $value,
	 * calls getCIEnvironment(), then restores the original environment.
	 * Returns whatever getCIEnvironment() returned.
	 */
	private function detectCIWith(string $var, string $value): ?string
	{
		$allVars = [
			'GITHUB_ACTIONS', 'TRAVIS', 'CIRCLECI', 'GITLAB_CI', 'JENKINS_URL',
			'SCRUTINIZER', 'BITBUCKET_BUILD_NUMBER', 'DRONE', 'TEAMCITY_VERSION',
			'APPVEYOR', 'TF_BUILD', 'CI_NAME', 'BUILDKITE', 'HEROKU_TEST_RUN_ID', 'CI',
		];
		$saved = [];
		foreach ($allVars as $v) {
			$saved[$v] = getenv($v);
			putenv($v);                   // unset
		}
		putenv("{$var}={$value}");
		try {
			return PradoUnit::getCIEnvironment();
		} finally {
			putenv($var);                 // unset test value
			foreach ($saved as $k => $val) {
				if ($val !== false) {
					putenv("{$k}={$val}");
				}
			}
		}
	}

	public function testGetCIEnvironment_detectsScrutinizer(): void
	{
		$this->assertSame('Scrutinizer CI', $this->detectCIWith('SCRUTINIZER', 'true'));
	}

	public function testGetCIEnvironment_detectsBitbucketPipelines(): void
	{
		$this->assertSame('Bitbucket Pipelines', $this->detectCIWith('BITBUCKET_BUILD_NUMBER', '42'));
	}

	public function testGetCIEnvironment_detectsDroneCI(): void
	{
		$this->assertSame('Drone CI', $this->detectCIWith('DRONE', 'true'));
	}

	public function testGetCIEnvironment_detectsTeamCity(): void
	{
		$this->assertSame('TeamCity', $this->detectCIWith('TEAMCITY_VERSION', '2023.1'));
	}

	public function testGetCIEnvironment_detectsAppVeyor(): void
	{
		// AppVeyor uses 'True' (capital T), not 'true'.
		$this->assertSame('AppVeyor', $this->detectCIWith('APPVEYOR', 'True'));
	}

	public function testGetCIEnvironment_doesNotDetectAppVeyor_withLowercaseTrue(): void
	{
		// Confirm case-sensitivity: 'true' must NOT match AppVeyor (which requires 'True').
		$result = $this->detectCIWith('APPVEYOR', 'true');
		$this->assertNotSame('AppVeyor', $result);
	}

	public function testGetCIEnvironment_detectsAzurePipelines(): void
	{
		// Azure Pipelines uses 'True' (capital T).
		$this->assertSame('Azure Pipelines', $this->detectCIWith('TF_BUILD', 'True'));
	}

	public function testGetCIEnvironment_detectsCodeship(): void
	{
		$this->assertSame('Codeship', $this->detectCIWith('CI_NAME', 'codeship'));
	}

	public function testGetCIEnvironment_detectsBuildkite(): void
	{
		$this->assertSame('Buildkite', $this->detectCIWith('BUILDKITE', 'true'));
	}

	public function testGetCIEnvironment_detectsHerokuCI(): void
	{
		$this->assertSame('Heroku CI', $this->detectCIWith('HEROKU_TEST_RUN_ID', 'abc123'));
	}

	// -----------------------------------------------------------------------
	// skipDatabaseTests
	// -----------------------------------------------------------------------

	public function testSkipDatabaseTests_returnsFalse_byDefault(): void
	{
		$this->withEnv('PRADO_UNITTEST_SKIP_DB', false, function () {
			$this->assertFalse(PradoUnit::skipDatabaseTests());
		});
	}

	public function testSkipDatabaseTests_returnsTrue_whenSetToOne(): void
	{
		$this->withEnv('PRADO_UNITTEST_SKIP_DB', '1', function () {
			$this->assertTrue(PradoUnit::skipDatabaseTests());
		});
	}

	public function testSkipDatabaseTests_returnsFalse_whenSetToZero(): void
	{
		$this->withEnv('PRADO_UNITTEST_SKIP_DB', '0', function () {
			$this->assertFalse(PradoUnit::skipDatabaseTests());
		});
	}

	public function testSkipDatabaseTests_returnsFalse_whenSetToOtherValue(): void
	{
		$this->withEnv('PRADO_UNITTEST_SKIP_DB', 'yes', function () {
			$this->assertFalse(PradoUnit::skipDatabaseTests());
		});
	}

	// -----------------------------------------------------------------------
	// isNoConnection
	// -----------------------------------------------------------------------

	/**
	 * Each of the six phrases that identify "no server" errors.
	 */
	public function isNoConnectionProvider(): array
	{
		return [
			'No such file'                        => ["SQLSTATE[HY000] [2002] No such file or directory"],
			'Connection refused'                  => ["SQLSTATE[HY000] [2002] Connection refused"],
			'failed to establish'                 => ["TDbConnection failed to establish DB connection: something"],
			'Unable to complete network request'  => ["Unable to complete network request to host"],
			'ODBC Driver for SQL Server'          => ["ODBC Driver for SQL Server: TCP Provider"],
			'could not connect'                   => ["could not connect to server"],
		];
	}

	/** @dataProvider isNoConnectionProvider */
	public function testIsNoConnection_returnsTrueForKnownPhrases(string $message): void
	{
		$e = new \Exception($message);
		$this->assertTrue(PradoUnit::isNoConnection($e));
	}

	public function testIsNoConnection_returnsFalse_forUnrelatedMessage(): void
	{
		$e = new \Exception("Unknown database 'prado_unitest'");
		$this->assertFalse(PradoUnit::isNoConnection($e));
	}

	public function testIsNoConnection_isCaseInsensitive(): void
	{
		$e = new \Exception("NO SUCH FILE /var/run/mysql.sock");
		$this->assertTrue(PradoUnit::isNoConnection($e));
	}

	// -----------------------------------------------------------------------
	// isNoDatabase
	// -----------------------------------------------------------------------

	public function testIsNoDatabase_returnsTrueForUnknownDatabase(): void
	{
		$e = new \Exception("SQLSTATE[HY000] [1049] Unknown database 'prado_unitest'");
		$this->assertTrue(PradoUnit::isNoDatabase($e));
	}

	public function testIsNoDatabase_isCaseInsensitive(): void
	{
		$e = new \Exception("UNKNOWN DATABASE 'test'");
		$this->assertTrue(PradoUnit::isNoDatabase($e));
	}

	public function testIsNoDatabase_returnsFalse_forConnectionRefused(): void
	{
		$e = new \Exception("Connection refused");
		$this->assertFalse(PradoUnit::isNoDatabase($e));
	}

	public function testIsNoDatabase_returnsFalse_forUnrelatedMessage(): void
	{
		$e = new \Exception("Base table or view not found: address");
		$this->assertFalse(PradoUnit::isNoDatabase($e));
	}

	// -----------------------------------------------------------------------
	// isNoTable
	// -----------------------------------------------------------------------

	/**
	 * Each of the eight driver-specific "table not found" patterns.
	 */
	public function isNoTableProvider(): array
	{
		return [
			'MySQL — Base table or view not found' => ["SQLSTATE[42S02]: Base table or view not found: 1146 Table 'prado_unitest.foo' doesn't exist"],
			'Firebird — Table unknown'             => ["SQLSTATE[HY000]: General error: -204 Dynamic SQL Error\nSQL error code = -204\nTable unknown\nFOO"],
			'PostgreSQL — does not exist'          => ["SQLSTATE[42P01]: Undefined table: 7 ERROR:  relation \"foo\" does not exist"],
			'Oracle — ORA-00942'                   => ["SQLSTATE[HY000]: ORA-00942: table or view does not exist"],
			'SQL Server — Invalid object name'     => ["SQLSTATE[42S02]: Invalid object name 'foo'"],
			'IBM DB2 — SQL0204N'                   => ["SQLSTATE=42704 SQL0204N \"DB2INST1.FOO\" is an undefined name."],
			'IBM DB2 — SQLCODE=-204'               => ["SQLSTATE[HY000]: SQLCODE=-204, SQLSTATE=42704, SQLERRMC=FOO"],
			'SQLite — no such table'               => ["SQLSTATE[HY000]: General error: 1 no such table: foo"],
		];
	}

	/** @dataProvider isNoTableProvider */
	public function testIsNoTable_returnsTrueForEachDriver(string $message): void
	{
		$e = new \Exception($message);
		$this->assertTrue(PradoUnit::isNoTable($e));
	}

	public function testIsNoTable_returnsFalse_forConnectionError(): void
	{
		$e = new \Exception("Connection refused");
		$this->assertFalse(PradoUnit::isNoTable($e));
	}

	public function testIsNoTable_returnsFalse_forUnknownDatabase(): void
	{
		$e = new \Exception("Unknown database 'prado_unitest'");
		$this->assertFalse(PradoUnit::isNoTable($e));
	}

	public function testIsNoTable_returnsFalse_forUnrelatedMessage(): void
	{
		$e = new \Exception("Column 'foo' not found in table");
		$this->assertFalse(PradoUnit::isNoTable($e));
	}

	public function testIsNoTable_isCaseInsensitive_forFirebird(): void
	{
		// "TABLE UNKNOWN" in all caps should still match.
		$e = new \Exception("TABLE UNKNOWN: SOMETABLE");
		$this->assertTrue(PradoUnit::isNoTable($e));
	}

	// -----------------------------------------------------------------------
	// processException — setUp / tearDown for map isolation
	// -----------------------------------------------------------------------

	/** Saved copies of the three static deduplication maps. */
	private array $_savedDbConnectionException;
	private array $_savedDbDatabaseException;
	private array $_savedDbTableException;

	protected function setUp(): void
	{
		parent::setUp();
		// Save and clear the per-driver deduplication maps so processException
		// tests always start from a clean slate, regardless of test execution order.
		$this->_savedDbConnectionException = PradoUnit::$dbConnectionException;
		$this->_savedDbDatabaseException   = PradoUnit::$dbDatabaseException;
		$this->_savedDbTableException      = PradoUnit::$dbTableException;
		PradoUnit::$dbConnectionException  = [];
		PradoUnit::$dbDatabaseException    = [];
		PradoUnit::$dbTableException       = [];
	}

	protected function tearDown(): void
	{
		PradoUnit::$dbConnectionException = $this->_savedDbConnectionException;
		PradoUnit::$dbDatabaseException   = $this->_savedDbDatabaseException;
		PradoUnit::$dbTableException      = $this->_savedDbTableException;
		parent::tearDown();
	}

	// -----------------------------------------------------------------------
	// processException — isNoConnection
	// -----------------------------------------------------------------------

	public function testProcessException_isNoConnection_firstOccurrence_noSkipDb_returnsException(): void
	{
		// Without SKIP_DB the exception is returned unchanged so the caller can
		// decide whether to rethrow or markTestSkipped.
		$this->withEnv('PRADO_UNITTEST_SKIP_DB', false, function () {
			$conn = new PradoUnitMockConnection('mysql');
			$e = new \Exception("Connection refused");

			$result = PradoUnit::processException($e, $conn);

			$this->assertSame($e, $result, 'First occurrence without SKIP_DB must return the original Exception');
			$this->assertArrayHasKey('mysql', PradoUnit::$dbConnectionException);
		});
	}

	public function testProcessException_isNoConnection_firstOccurrence_withSkipDb_returnsString(): void
	{
		$this->withEnv('PRADO_UNITTEST_SKIP_DB', '1', function () {
			$conn = new PradoUnitMockConnection('mysql');
			$e = new \Exception("Connection refused");

			$result = PradoUnit::processException($e, $conn);

			$this->assertIsString($result);
			$this->assertStringContainsString('mysql', $result);
			$this->assertStringContainsString('PRADO_UNITTEST_SKIP_DB=1', $result);
			$this->assertArrayHasKey('mysql', PradoUnit::$dbConnectionException);
		});
	}

	public function testProcessException_isNoConnection_duplicateOccurrence_returnsDuplicatedString(): void
	{
		$conn = new PradoUnitMockConnection('pgsql');
		PradoUnit::$dbConnectionException['pgsql'] = true;   // simulate prior occurrence

		$e = new \Exception("Connection refused");
		$result = PradoUnit::processException($e, $conn);

		$this->assertIsString($result);
		$this->assertStringContainsString('Duplicated', $result);
		$this->assertStringContainsString('pgsql', $result);
	}

	// -----------------------------------------------------------------------
	// processException — isNoDatabase (includes the $e .= bug-fix regression test)
	// -----------------------------------------------------------------------

	/**
	 * BUG-FIX REGRESSION TEST
	 *
	 * The original code in the isNoDatabase branch read:
	 *
	 *   $e .= strtr("Database '{0}' Not Found Error ...", [...]);
	 *
	 * In PHP 8, concatenating (.=) a string onto an \Exception object throws a
	 * TypeError:  "Unsupported operand types: Exception .= string".
	 *
	 * The fix changes the assignment to:
	 *
	 *   $e = strtr("Database '{0}' Not Found Error ...", [...]);
	 *
	 * This test asserts that processException() returns a string (not an Exception
	 * and not a TypeError) when PRADO_UNITTEST_SKIP_DB=1 and the exception matches
	 * isNoDatabase.  Before the fix this test would fail with a TypeError.
	 */
	public function testProcessException_isNoDatabase_firstOccurrence_withSkipDb_returnsString_bugFix(): void
	{
		$this->withEnv('PRADO_UNITTEST_SKIP_DB', '1', function () {
			$conn   = new PradoUnitMockConnection('mysql');
			$e      = new \Exception("SQLSTATE[HY000] [1049] Unknown database 'prado_unitest'");

			// Must NOT throw TypeError — that would be the pre-fix behaviour.
			$result = PradoUnit::processException($e, $conn);

			$this->assertIsString($result, 'processException must return a string when isNoDatabase matches and SKIP_DB=1 (pre-fix threw TypeError: Exception .= string)');
			$this->assertStringContainsString('mysql', $result);
			$this->assertStringContainsString('PRADO_UNITTEST_SKIP_DB=1', $result);
			$this->assertStringNotContainsString('Object', $result);
		});
	}

	public function testProcessException_isNoDatabase_firstOccurrence_noSkipDb_returnsException(): void
	{
		$this->withEnv('PRADO_UNITTEST_SKIP_DB', false, function () {
			$conn = new PradoUnitMockConnection('mysql');
			$e    = new \Exception("SQLSTATE[HY000] [1049] Unknown database 'prado_unitest'");

			$result = PradoUnit::processException($e, $conn);

			$this->assertSame($e, $result, 'First occurrence without SKIP_DB must return original Exception unchanged');
			$this->assertArrayHasKey('mysql', PradoUnit::$dbDatabaseException);
		});
	}

	public function testProcessException_isNoDatabase_duplicateOccurrence_returnsDuplicatedString(): void
	{
		$conn = new PradoUnitMockConnection('pgsql');
		PradoUnit::$dbDatabaseException['pgsql'] = true;

		$e      = new \Exception("SQLSTATE[HY000] [1049] Unknown database 'prado_unitest'");
		$result = PradoUnit::processException($e, $conn);

		$this->assertIsString($result);
		$this->assertStringContainsString('Duplicated', $result);
		$this->assertStringContainsString('pgsql', $result);
	}

	public function testProcessException_isNoDatabase_duplicateStringDoesNotContainOriginalExceptionMessage(): void
	{
		// Duplicate strings are short summaries; they must NOT include the full
		// exception text that would bloat test output on every file.
		$conn = new PradoUnitMockConnection('mysql');
		PradoUnit::$dbDatabaseException['mysql'] = true;

		$e      = new \Exception("SQLSTATE[HY000] [1049] Unknown database 'prado_unitest'");
		$result = PradoUnit::processException($e, $conn);

		$this->assertIsString($result);
		$this->assertStringNotContainsString($e->getMessage(), $result);
	}

	// -----------------------------------------------------------------------
	// processException — isNoTable
	// -----------------------------------------------------------------------

	public function testProcessException_isNoTable_firstOccurrence_noSkipDb_returnsException(): void
	{
		$this->withEnv('PRADO_UNITTEST_SKIP_DB', false, function () {
			$conn = new PradoUnitMockConnection('mysql');
			$e    = new \Exception("Base table or view not found: 1146 Table 'prado_unitest.foo' doesn't exist");

			$result = PradoUnit::processException($e, $conn);

			$this->assertSame($e, $result);
			$this->assertArrayHasKey('mysql', PradoUnit::$dbTableException);
		});
	}

	public function testProcessException_isNoTable_firstOccurrence_withSkipDb_returnsString(): void
	{
		$this->withEnv('PRADO_UNITTEST_SKIP_DB', '1', function () {
			$conn = new PradoUnitMockConnection('mysql');
			$e    = new \Exception("Base table or view not found: foo");

			$result = PradoUnit::processException($e, $conn);

			$this->assertIsString($result);
			$this->assertStringContainsString('mysql', $result);
			$this->assertStringContainsString('PRADO_UNITTEST_SKIP_DB=1', $result);
		});
	}

	public function testProcessException_isNoTable_duplicateOccurrence_returnsDuplicatedString(): void
	{
		$conn = new PradoUnitMockConnection('sqlite');
		PradoUnit::$dbTableException['sqlite'] = true;

		$e      = new \Exception("does not exist");
		$result = PradoUnit::processException($e, $conn);

		$this->assertIsString($result);
		$this->assertStringContainsString('Duplicated', $result);
	}

	// -----------------------------------------------------------------------
	// processException — unrecognised exception passthrough
	// -----------------------------------------------------------------------

	public function testProcessException_unrecognisedExceptionIsReturnedUnchanged(): void
	{
		$conn   = new PradoUnitMockConnection('mysql');
		$e      = new \RuntimeException("Unexpected PDO error: something weird");

		$result = PradoUnit::processException($e, $conn);

		// None of the three maps must be populated — this was not a known category.
		$this->assertSame($e, $result);
		$this->assertArrayNotHasKey('mysql', PradoUnit::$dbConnectionException);
		$this->assertArrayNotHasKey('mysql', PradoUnit::$dbDatabaseException);
		$this->assertArrayNotHasKey('mysql', PradoUnit::$dbTableException);
	}

	// -----------------------------------------------------------------------
	// processException — each category populates the right map only
	// -----------------------------------------------------------------------

	public function testProcessException_isNoConnection_populatesOnlyConnectionMap(): void
	{
		$conn = new PradoUnitMockConnection('firebird');
		$e    = new \Exception("Connection refused");
		PradoUnit::processException($e, $conn);

		$this->assertArrayHasKey('firebird', PradoUnit::$dbConnectionException);
		$this->assertArrayNotHasKey('firebird', PradoUnit::$dbDatabaseException);
		$this->assertArrayNotHasKey('firebird', PradoUnit::$dbTableException);
	}

	public function testProcessException_isNoDatabase_populatesOnlyDatabaseMap(): void
	{
		$conn = new PradoUnitMockConnection('firebird');
		$e    = new \Exception("Unknown database 'test'");
		PradoUnit::processException($e, $conn);

		$this->assertArrayNotHasKey('firebird', PradoUnit::$dbConnectionException);
		$this->assertArrayHasKey('firebird', PradoUnit::$dbDatabaseException);
		$this->assertArrayNotHasKey('firebird', PradoUnit::$dbTableException);
	}

	public function testProcessException_isNoTable_populatesOnlyTableMap(): void
	{
		$conn = new PradoUnitMockConnection('firebird');
		$e    = new \Exception("Table unknown: FOO");
		PradoUnit::processException($e, $conn);

		$this->assertArrayNotHasKey('firebird', PradoUnit::$dbConnectionException);
		$this->assertArrayNotHasKey('firebird', PradoUnit::$dbDatabaseException);
		$this->assertArrayHasKey('firebird', PradoUnit::$dbTableException);
	}

	// -----------------------------------------------------------------------
	// processException — per-driver isolation (different drivers tracked independently)
	// -----------------------------------------------------------------------

	public function testProcessException_dedupMapsAreKeyedByDriver(): void
	{
		$connA = new PradoUnitMockConnection('mysql');
		$connB = new PradoUnitMockConnection('pgsql');
		$e     = new \Exception("Connection refused");

		// First occurrence for mysql — returns Exception.
		$r1 = PradoUnit::processException($e, $connA);
		// First occurrence for pgsql — also returns Exception (not "Duplicated").
		$e2 = new \Exception("Connection refused");
		$r2 = PradoUnit::processException($e2, $connB);

		$this->assertInstanceOf(\Exception::class, $r1);
		$this->assertInstanceOf(\Exception::class, $r2);

		// Second occurrence for mysql — returns "Duplicated".
		$e3 = new \Exception("Connection refused");
		$r3 = PradoUnit::processException($e3, $connA);
		$this->assertIsString($r3);
		$this->assertStringContainsString('Duplicated', $r3);
	}

	// -----------------------------------------------------------------------
	// setupSqliteConnection
	// -----------------------------------------------------------------------

	public function testSetupSqliteConnection_returnsStringWhenExtensionMissing(): void
	{
		if (extension_loaded('pdo_sqlite')) {
			// Can't unload an extension at runtime; skip if it is present.
			$this->markTestSkipped('pdo_sqlite is loaded; cannot test the missing-extension branch.');
		}
		$result = PradoUnit::setupSqliteConnection();
		$this->assertIsString($result);
		$this->assertStringContainsString('pdo_sqlite', $result);
	}

	public function testSetupSqliteConnection_inMemory_returnsTDbConnection(): void
	{
		if (!extension_loaded('pdo_sqlite')) {
			$this->markTestSkipped('pdo_sqlite extension not available.');
		}
		$result = PradoUnit::setupSqliteConnection();
		$this->assertInstanceOf(\Prado\Data\TDbConnection::class, $result);
		$this->assertTrue($result->getActive());
		$result->setActive(false);
	}

	public function testSetupSqliteConnection_withFilePath_returnsTDbConnection(): void
	{
		if (!extension_loaded('pdo_sqlite')) {
			$this->markTestSkipped('pdo_sqlite extension not available.');
		}
		$file   = sys_get_temp_dir() . '/prado_unit_test_' . getmypid() . '.db';
		$result = PradoUnit::setupSqliteConnection($file);
		$this->assertInstanceOf(\Prado\Data\TDbConnection::class, $result);
		$result->setActive(false);
		if (file_exists($file)) {
			unlink($file);
		}
	}

	public function testSetupSqliteConnection_emptyDatabase_usesInMemoryDsn(): void
	{
		if (!extension_loaded('pdo_sqlite')) {
			$this->markTestSkipped('pdo_sqlite extension not available.');
		}
		// Two separate in-memory connections must be independent databases.
		$a = PradoUnit::setupSqliteConnection();
		$b = PradoUnit::setupSqliteConnection();
		$this->assertInstanceOf(\Prado\Data\TDbConnection::class, $a);
		$this->assertInstanceOf(\Prado\Data\TDbConnection::class, $b);
		// Create a table in $a; it must not appear in $b.
		$a->createCommand('CREATE TABLE ping (id INTEGER PRIMARY KEY)')->execute();
		$count = $b->createCommand("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='ping'")->queryScalar();
		$this->assertSame('0', (string) $count, 'In-memory databases must be isolated from each other');
		$a->setActive(false);
		$b->setActive(false);
	}

	// -----------------------------------------------------------------------
	// checkForTable — SQLite in-memory
	// -----------------------------------------------------------------------

	public function testCheckForTable_returnsNull_whenTableExists(): void
	{
		if (!extension_loaded('pdo_sqlite')) {
			$this->markTestSkipped('pdo_sqlite extension not available.');
		}
		$conn = PradoUnit::setupSqliteConnection();
		$conn->createCommand('CREATE TABLE test_probe (id INTEGER PRIMARY KEY)')->execute();

		$result = PradoUnit::checkForTable($conn, 'test_probe');

		$this->assertNull($result, 'checkForTable must return null when the table exists');
		$conn->setActive(false);
	}

	public function testCheckForTable_returnsStringOrException_whenTableMissing(): void
	{
		if (!extension_loaded('pdo_sqlite')) {
			$this->markTestSkipped('pdo_sqlite extension not available.');
		}
		$conn = PradoUnit::setupSqliteConnection();

		$result = PradoUnit::checkForTable($conn, 'nonexistent_table_xyz');

		// On first occurrence with SKIP_DB unset, processException returns the Exception.
		// On any occurrence with SKIP_DB=1, it returns a string.
		// Either way it must NOT be null.
		$this->assertNotNull($result, 'checkForTable must return non-null when the table is missing');
		$conn->setActive(false);
	}

	public function testCheckForTable_withSkipDb_returnsString_whenTableMissing(): void
	{
		if (!extension_loaded('pdo_sqlite')) {
			$this->markTestSkipped('pdo_sqlite extension not available.');
		}
		$this->withEnv('PRADO_UNITTEST_SKIP_DB', '1', function () {
			$conn   = PradoUnit::setupSqliteConnection();
			$result = PradoUnit::checkForTable($conn, 'nonexistent_table_xyz');
			$this->assertIsString($result);
			$this->assertStringContainsString('PRADO_UNITTEST_SKIP_DB=1', $result);
			$conn->setActive(false);
		});
	}

	public function testCheckForTable_returnsNull_repeatCallOnSameTable(): void
	{
		if (!extension_loaded('pdo_sqlite')) {
			$this->markTestSkipped('pdo_sqlite extension not available.');
		}
		$conn = PradoUnit::setupSqliteConnection();
		$conn->createCommand('CREATE TABLE test_repeat (id INTEGER PRIMARY KEY)')->execute();

		// Calling checkForTable twice on the same existing table must both return null.
		$this->assertNull(PradoUnit::checkForTable($conn, 'test_repeat'));
		$this->assertNull(PradoUnit::checkForTable($conn, 'test_repeat'));
		$conn->setActive(false);
	}

	// -----------------------------------------------------------------------
	// snapshot / restore — additional edge cases
	// -----------------------------------------------------------------------

	public function testSnapshot_withEmptyFilterReturnsAllProperties(): void
	{
		$obj  = new PradoUnitTestChild();
		$snap = PradoUnit::snapshot($obj);
		$this->assertCount(4, $snap, 'Full snapshot must contain all four properties across both hierarchy levels');
	}

	public function testSnapshot_filterWithNonExistentNameIsIgnored(): void
	{
		$obj  = new PradoUnitTestChild();
		// 'doesNotExist' is not a real property; it should just be absent from the result.
		$snap = PradoUnit::snapshot($obj, ['_basePrivate', 'doesNotExist']);
		$this->assertArrayHasKey('_basePrivate', $snap);
		$this->assertArrayNotHasKey('doesNotExist', $snap);
	}

	public function testRestore_withEmptySnapshotIsNoOp(): void
	{
		$obj = new PradoUnitTestChild();
		PradoUnit::setProp($obj, '_basePrivate', 99);

		PradoUnit::restore($obj, []);

		// Property must be unchanged — empty snapshot = no writes.
		$this->assertSame(99, PradoUnit::getProp($obj, '_basePrivate'));
	}

	public function testSnapshot_childPropertyShadowsParentSameName(): void
	{
		/**
		 * Fixture where child re-declares a property with the same name as the parent.
		 * PradoUnit must return the child-level value (child wins).
		 */
		$class = new class extends PradoUnitTestBase {
			// Re-declare _baseProtected at the child level so there are *two*
			// ReflectionProperty objects for that name in the hierarchy.
			// @phpstan-ignore-next-line
			protected string $_baseProtected = 'child-override';
		};
		$snap = PradoUnit::snapshot($class);
		$this->assertSame('child-override', $snap['_baseProtected'],
			'Child-level property must shadow the parent-level declaration of the same name');
	}
}
