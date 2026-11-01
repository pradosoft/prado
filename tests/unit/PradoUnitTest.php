<?php

/**
 * PradoUnitTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit;

use Prado\Test\Unit\Harness\TTestApplication;

/**
 * Base class for the hierarchy used in PradoUnit reflection tests.
 * Declares private and protected fields so they exercise ancestor-level
 * property access through the full class hierarchy walk.
 */
class PradoUnitTestBase
{
	private int $_basePrivate = 10;
	protected string $_baseProtected = 'base';

	private function multiply(int $a, int $b): int
	{
		return $a * $b;
	}
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

	private static function add(int $a, int $b): int
	{
		return $a + $b;
	}
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
 * Repeatable attribute for the PradoUnit attribute lookup tests.
 */
#[\Attribute(\Attribute::TARGET_ALL | \Attribute::IS_REPEATABLE)]
class PradoUnitTestAttribute
{
	public function __construct(public string $value)
	{
	}
}

/**
 * Subclass attribute; matched by `instanceof` when PradoUnitTestAttribute is the filter.
 */
#[\Attribute(\Attribute::TARGET_ALL)]
class PradoUnitTestSubAttribute extends PradoUnitTestAttribute
{
}

/**
 * Unrelated attribute; never matched by the PradoUnitTestAttribute filter.
 */
#[\Attribute]
class PradoUnitTestOtherAttribute
{
}

/**
 * Parent of the attributed hierarchy: a class attribute, a public and a private
 * attributed property, and an attributed method the child inherits.
 */
#[PradoUnitTestAttribute('parent')]
class PradoUnitAttributeBase
{
	#[PradoUnitTestAttribute('parent-prop')]
	public int $parentProp = 1;

	#[PradoUnitTestAttribute('private-prop')]
	private int $_privateParentProp = 2;

	#[PradoUnitTestAttribute('inherited-method')]
	public function inheritedMethod(): void
	{
	}
}

/**
 * Child of the attributed hierarchy: two repeated class attributes and an
 * unrelated one, an attributed property, a plain property, a method carrying
 * the subclass attribute, and a plain method.
 */
#[PradoUnitTestAttribute('child-a')]
#[PradoUnitTestAttribute('child-b')]
#[PradoUnitTestOtherAttribute]
class PradoUnitAttributeChild extends PradoUnitAttributeBase
{
	#[PradoUnitTestAttribute('child-prop')]
	public int $childProp = 3;

	public int $plainProp = 4;

	#[PradoUnitTestSubAttribute('sub')]
	public function subMethod(): void
	{
	}

	public function plainMethod(): void
	{
	}
}

/**
 * Tests for {@see PradoUnit}.
 *
 * Covers: snapshot/restore across a two-level hierarchy, partial-snapshot
 * filtering, getProp/setProp for private ancestor fields, and the
 * ReflectionException path for missing properties.
 *
 */
class PradoUnitTest extends \PHPUnit\Framework\TestCase
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
	// getProp / setProp — static mode (class-string argument)
	// -----------------------------------------------------------------------

	public function testGetPropStaticReadsPrivateAncestorField()
	{
		$this->assertSame(10, PradoUnit::getProp(PradoUnitStaticTestChild::class, '_baseStaticPrivate'));
	}

	public function testGetPropStaticReadsProtectedField()
	{
		$this->assertSame('base', PradoUnit::getProp(PradoUnitStaticTestChild::class, '_baseStaticProtected'));
	}

	public function testGetPropStaticReadsChildPrivateField()
	{
		$this->assertSame(2.5, PradoUnit::getProp(PradoUnitStaticTestChild::class, '_childStaticPrivate'));
	}

	public function testGetPropStaticThrowsForMissingProperty()
	{
		$this->expectException(\ReflectionException::class);
		PradoUnit::getProp(PradoUnitStaticTestChild::class, 'doesNotExist');
	}

	public function testSetPropStaticWritesPrivateAncestorField()
	{
		$original = PradoUnit::getProp(PradoUnitStaticTestChild::class, '_baseStaticPrivate');
		try {
			PradoUnit::setProp(PradoUnitStaticTestChild::class, '_baseStaticPrivate', 99);
			$this->assertSame(99, PradoUnit::getProp(PradoUnitStaticTestChild::class, '_baseStaticPrivate'));
		} finally {
			PradoUnit::setProp(PradoUnitStaticTestChild::class, '_baseStaticPrivate', $original);
		}
	}

	public function testSetPropStaticWritesChildPrivateField()
	{
		$original = PradoUnit::getProp(PradoUnitStaticTestChild::class, '_childStaticPrivate');
		try {
			PradoUnit::setProp(PradoUnitStaticTestChild::class, '_childStaticPrivate', 6.28);
			$this->assertSame(6.28, PradoUnit::getProp(PradoUnitStaticTestChild::class, '_childStaticPrivate'));
		} finally {
			PradoUnit::setProp(PradoUnitStaticTestChild::class, '_childStaticPrivate', $original);
		}
	}

	public function testSetPropStaticThrowsForMissingProperty()
	{
		$this->expectException(\ReflectionException::class);
		PradoUnit::setProp(PradoUnitStaticTestChild::class, 'doesNotExist', 'value');
	}

	// -----------------------------------------------------------------------
	// snapshot / restore round-trip on a real TApplication
	// -----------------------------------------------------------------------

	public function testSnapshotRestoreRoundTripOnRealApp()
	{
		$app = \Prado::getApplication();
		$originalMode = $app->getMode();

		$snap = TTestApplication::snapshotApp($app, ['_mode']);

		$app->setMode($originalMode === \Prado\TApplicationMode::Performance ? \Prado\TApplicationMode::Normal : \Prado\TApplicationMode::Performance);
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
	 * @param string $name
	 * @param false|string $value
	 * @param callable $callback
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
	// reflectionClass / reflectionMethod / reflectionProperty (cache)
	// -----------------------------------------------------------------------

	public function testReflectionClass_returnsReflectionClass_forClassName(): void
	{
		$rc = PradoUnit::reflectionClass(PradoUnitTestChild::class);
		$this->assertInstanceOf(\ReflectionClass::class, $rc);
		$this->assertSame(PradoUnitTestChild::class, $rc->getName());
	}

	public function testReflectionClass_returnsReflectionClass_forInstance(): void
	{
		$rc = PradoUnit::reflectionClass(new PradoUnitTestChild());
		$this->assertInstanceOf(\ReflectionClass::class, $rc);
		$this->assertSame(PradoUnitTestChild::class, $rc->getName());
	}

	public function testReflectionClass_cachesSameInstance(): void
	{
		$first = PradoUnit::reflectionClass(PradoUnitTestChild::class);
		$second = PradoUnit::reflectionClass(PradoUnitTestChild::class);
		$this->assertSame($first, $second);
	}

	public function testReflectionClass_isCaseInsensitive(): void
	{
		$lower = PradoUnit::reflectionClass(strtolower(PradoUnitTestChild::class));
		$exact = PradoUnit::reflectionClass(PradoUnitTestChild::class);
		$this->assertSame($exact, $lower);
	}

	public function testReflectionClass_returnsNull_forUnknownClass(): void
	{
		$this->assertNull(PradoUnit::reflectionClass('NoSuchClassXyz123'));
	}

	public function testReflectionClass_cachesNullForUnknownClass(): void
	{
		// Second lookup of an unknown class returns the same cached null result
		// without re-attempting the ReflectionException path.
		$this->assertNull(PradoUnit::reflectionClass('NoSuchClassXyz456'));
		$this->assertNull(PradoUnit::reflectionClass('NoSuchClassXyz456'));
	}

	public function testReflectionMethod_returnsMethod_byClassName(): void
	{
		$rm = PradoUnit::reflectionMethod(PradoUnit::class, 'reflectionClass');
		$this->assertInstanceOf(\ReflectionMethod::class, $rm);
		$this->assertSame('reflectionClass', $rm->getName());
	}

	public function testReflectionMethod_cachesSameInstance(): void
	{
		$first = PradoUnit::reflectionMethod(PradoUnit::class, 'reflectionClass');
		$second = PradoUnit::reflectionMethod(PradoUnit::class, 'reflectionClass');
		$this->assertSame($first, $second);
	}

	public function testReflectionMethod_returnsNull_forUnknownMethod(): void
	{
		$this->assertNull(PradoUnit::reflectionMethod(PradoUnit::class, 'noSuchMethodXyz'));
	}

	public function testReflectionMethod_returnsNull_forUnknownClass(): void
	{
		$this->assertNull(PradoUnit::reflectionMethod('NoSuchClassXyz789', 'anything'));
	}

	// -----------------------------------------------------------------------
	// invoke — instance and static modes
	// -----------------------------------------------------------------------

	public function testInvokeCallsPrivateAncestorMethod(): void
	{
		$obj = new PradoUnitTestChild();

		$this->assertSame(12, PradoUnit::invoke($obj, 'multiply', 3, 4));
	}

	public function testInvokeCallsStaticMethodViaClassString(): void
	{
		$this->assertSame(7, PradoUnit::invoke(PradoUnitStaticTestChild::class, 'add', 3, 4));
	}

	public function testInvokePassesMultipleArgs(): void
	{
		$obj = new PradoUnitTestChild();

		$this->assertSame(0, PradoUnit::invoke($obj, 'multiply', 0, 99));
	}

	public function testInvokeThrowsForMissingMethod(): void
	{
		$obj = new PradoUnitTestChild();

		$this->expectException(\ReflectionException::class);
		PradoUnit::invoke($obj, 'noSuchMethodXyz');
	}

	public function testInvokeThrowsForMissingMethodOnClass(): void
	{
		$this->expectException(\ReflectionException::class);
		PradoUnit::invoke(PradoUnitStaticTestChild::class, 'noSuchMethodXyz');
	}

	public function testReflectionProperty_findsPropertyOnConcreteClass(): void
	{
		$rp = PradoUnit::reflectionProperty(PradoUnitTestChild::class, 'childPublic');
		$this->assertInstanceOf(\ReflectionProperty::class, $rp);
		$this->assertSame('childPublic', $rp->getName());
		$this->assertSame(PradoUnitTestChild::class, $rp->getDeclaringClass()->getName());
	}

	public function testReflectionProperty_findsPrivateAncestorProperty(): void
	{
		// _basePrivate is declared on the parent — the standard
		// ReflectionClass::getProperty() called on the child would miss it.
		$rp = PradoUnit::reflectionProperty(PradoUnitTestChild::class, '_basePrivate');
		$this->assertInstanceOf(\ReflectionProperty::class, $rp);
		$this->assertSame(PradoUnitTestBase::class, $rp->getDeclaringClass()->getName());
	}

	public function testReflectionProperty_cachesSameInstance(): void
	{
		$first = PradoUnit::reflectionProperty(PradoUnitTestChild::class, '_basePrivate');
		$second = PradoUnit::reflectionProperty(PradoUnitTestChild::class, '_basePrivate');
		$this->assertSame($first, $second);
	}

	public function testReflectionProperty_returnsNull_forUnknownProperty(): void
	{
		$this->assertNull(PradoUnit::reflectionProperty(PradoUnitTestChild::class, 'noSuchProp'));
	}

	public function testReflectionProperty_returnsNull_forUnknownClass(): void
	{
		$this->assertNull(PradoUnit::reflectionProperty('NoSuchClassAbc', 'anything'));
	}

	// -----------------------------------------------------------------------
	// classAttributes / methodAttributes / propertyAttributes / *WithAttribute
	// -----------------------------------------------------------------------

	/**
	 * Maps attribute instances to their `value` strings.
	 * @param array<int,object> $attributes
	 * @return string[]
	 */
	private static function attributeValues(array $attributes): array
	{
		return array_map(fn ($a) => $a->value, $attributes);
	}

	public function testClassAttributes_ownThenParent_inDeclarationOrder(): void
	{
		$attributes = PradoUnit::classAttributes(PradoUnitAttributeChild::class, PradoUnitTestAttribute::class);
		$this->assertSame(['child-a', 'child-b', 'parent'], self::attributeValues($attributes));
		$this->assertContainsOnlyInstancesOf(PradoUnitTestAttribute::class, $attributes);
	}

	public function testClassAttributes_withoutInherit_returnsOwnOnly(): void
	{
		$attributes = PradoUnit::classAttributes(PradoUnitAttributeChild::class, PradoUnitTestAttribute::class, false);
		$this->assertSame(['child-a', 'child-b'], self::attributeValues($attributes));
	}

	public function testClassAttributes_filtersByAttributeClass(): void
	{
		$attributes = PradoUnit::classAttributes(PradoUnitAttributeChild::class, PradoUnitTestOtherAttribute::class);
		$this->assertCount(1, $attributes);
		$this->assertInstanceOf(PradoUnitTestOtherAttribute::class, $attributes[0]);
	}

	public function testClassAttributes_acceptsInstance(): void
	{
		$byName = PradoUnit::classAttributes(PradoUnitAttributeChild::class, PradoUnitTestAttribute::class);
		$byInstance = PradoUnit::classAttributes(new PradoUnitAttributeChild(), PradoUnitTestAttribute::class);
		$this->assertSame($byName, $byInstance);
	}

	public function testClassAttributes_cachesSameInstances_caseInsensitively(): void
	{
		$first = PradoUnit::classAttributes(PradoUnitAttributeChild::class, PradoUnitTestAttribute::class);
		$second = PradoUnit::classAttributes(strtoupper(PradoUnitAttributeChild::class), PradoUnitTestAttribute::class);
		$this->assertSame($first[0], $second[0]);
	}

	public function testClassAttributes_returnsEmpty_forClassWithoutAttribute(): void
	{
		$this->assertSame([], PradoUnit::classAttributes(PradoUnitTestChild::class, PradoUnitTestAttribute::class));
	}

	public function testClassAttributes_returnsEmpty_forUnknownClass(): void
	{
		$this->assertSame([], PradoUnit::classAttributes('NoSuchClassAttr123', PradoUnitTestAttribute::class));
	}

	public function testClassAttributes_throwsError_forUnknownAttributeClass(): void
	{
		$this->expectException(\Error::class);
		PradoUnit::classAttributes(PradoUnitAttributeChild::class, 'NoSuchAttributeXyz');
	}

	public function testMethodAttributes_findsInheritedMethod(): void
	{
		$attributes = PradoUnit::methodAttributes(PradoUnitAttributeChild::class, 'inheritedMethod', PradoUnitTestAttribute::class);
		$this->assertSame(['inherited-method'], self::attributeValues($attributes));
	}

	public function testMethodAttributes_matchesSubclassAttribute(): void
	{
		$attributes = PradoUnit::methodAttributes(PradoUnitAttributeChild::class, 'subMethod', PradoUnitTestAttribute::class);
		$this->assertCount(1, $attributes);
		$this->assertInstanceOf(PradoUnitTestSubAttribute::class, $attributes[0]);
	}

	public function testMethodAttributes_sharesInstances_acrossSubclasses(): void
	{
		$parent = PradoUnit::methodAttributes(PradoUnitAttributeBase::class, 'inheritedMethod', PradoUnitTestAttribute::class);
		$child = PradoUnit::methodAttributes(PradoUnitAttributeChild::class, 'inheritedMethod', PradoUnitTestAttribute::class);
		$this->assertSame($parent[0], $child[0]);
	}

	public function testMethodAttributes_returnsEmpty_forPlainOrUnknownMethod(): void
	{
		$this->assertSame([], PradoUnit::methodAttributes(PradoUnitAttributeChild::class, 'plainMethod', PradoUnitTestAttribute::class));
		$this->assertSame([], PradoUnit::methodAttributes(PradoUnitAttributeChild::class, 'noSuchMethod', PradoUnitTestAttribute::class));
	}

	public function testPropertyAttributes_findsPrivateAncestorProperty(): void
	{
		$attributes = PradoUnit::propertyAttributes(PradoUnitAttributeChild::class, '_privateParentProp', PradoUnitTestAttribute::class);
		$this->assertSame(['private-prop'], self::attributeValues($attributes));
	}

	public function testPropertyAttributes_returnsEmpty_forPlainOrUnknownProperty(): void
	{
		$this->assertSame([], PradoUnit::propertyAttributes(PradoUnitAttributeChild::class, 'plainProp', PradoUnitTestAttribute::class));
		$this->assertSame([], PradoUnit::propertyAttributes(PradoUnitAttributeChild::class, 'noSuchProp', PradoUnitTestAttribute::class));
	}

	public function testMethodsWithAttribute_mapsOwnAndInheritedMethods(): void
	{
		$map = PradoUnit::methodsWithAttribute(PradoUnitAttributeChild::class, PradoUnitTestAttribute::class);
		$this->assertSame(['subMethod', 'inheritedMethod'], array_keys($map));
		$this->assertSame(['sub'], self::attributeValues($map['subMethod']));
		$this->assertSame(['inherited-method'], self::attributeValues($map['inheritedMethod']));
	}

	public function testMethodsWithAttribute_sharesInstances_withSingleLookup(): void
	{
		$map = PradoUnit::methodsWithAttribute(PradoUnitAttributeChild::class, PradoUnitTestAttribute::class);
		$single = PradoUnit::methodAttributes(PradoUnitAttributeChild::class, 'subMethod', PradoUnitTestAttribute::class);
		$this->assertSame($single[0], $map['subMethod'][0]);
	}

	public function testPropertiesWithAttribute_mapsOwnAndInheritedPublicProperties(): void
	{
		$map = PradoUnit::propertiesWithAttribute(PradoUnitAttributeChild::class, PradoUnitTestAttribute::class);
		$this->assertSame(['childProp', 'parentProp'], array_keys($map));
		$this->assertSame(['child-prop'], self::attributeValues($map['childProp']));
		$this->assertArrayNotHasKey('_privateParentProp', $map);
	}

	public function testPropertiesWithAttribute_returnsEmpty_forUnknownClass(): void
	{
		$this->assertSame([], PradoUnit::propertiesWithAttribute('NoSuchClassAttr456', PradoUnitTestAttribute::class));
		$this->assertSame([], PradoUnit::methodsWithAttribute('NoSuchClassAttr456', PradoUnitTestAttribute::class));
	}
}
