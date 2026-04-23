<?php

/**
 * PradoUnit class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

// No Namespace for unit tests, separate from the system.
// All shared helpers are loaded via the central requires file.  The mutual
// require_once reference is intentional and safe: PHP marks a file as included
// before executing it, so the circular reference never recurses.
require_once __DIR__ . '/PradoUnitRequires.php';

/**
 * PradoUnit class
 *
 * Provides shared helpers used across the PRADO unit-test suite:
 *
 * - **Object snapshot / restore** — {@see snapshot()}, {@see restore()},
 *   {@see getProp()}, and {@see setProp()} use reflection to read and write
 *   instance properties at every level of the class hierarchy, including
 *   `private` properties declared in ancestor classes.
 *
 * - **Static snapshot / restore** — {@see snapshotStatic()}, {@see restoreStatic()},
 *   {@see getStaticProp()}, and {@see setStaticProp()} provide the same hierarchy-aware
 *   reflection access for *static* properties, enabling full save/restore of global
 *   class state (e.g. `Prado::$_aliases`, `TComponent::$_ue`) between tests.
 *
 * - **Database connection factory** — a family of `setup*Connection()` methods
 *   open driver-specific `TDbConnection` instances used by data-layer tests.
 *   Each method checks that the required PDO extension is loaded and catches
 *   connection failures, classifying them with {@see processException()} into
 *   one of three known categories (no server, no database, no table).
 *
 * - **Error classification and deduplication** — {@see processException()} converts
 *   recognised database exceptions into human-readable strings and uses three static
 *   maps ({@see $dbConnectionException}, {@see $dbDatabaseException},
 *   {@see $dbTableException}) to suppress redundant error detail for the same driver
 *   across multiple test files.
 *
 * The environment variable `PRADO_UNITTEST_SKIP_DB=1` causes
 * {@see skipDatabaseTests()} to return `true` and appends a marker to the first
 * human-readable error string for each driver, signalling that the failure was
 * expected.
 *
 * @todo generalize Exception groups.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */

class PradoUnit {

	// =========================================================================
	// Object snapshot / restore
	// =========================================================================

	/**
	 * Captures a snapshot of property values from `$object`.
	 *
	 * Walks the full class hierarchy so that private properties declared at
	 * every ancestor level are included. When `$propNames` is non-empty only
	 * those named properties are captured; otherwise every non-static instance
	 * property is captured.
	 *
	 * The returned array is suitable for passing directly to {@see restore()}.
	 *
	 * @param object   $object    The object to snapshot.
	 * @param string[] $propNames Optional list of property names to capture.
	 * @return array<string, mixed> Map of property name to captured value.
	 */
	public static function snapshot(object $object, array $propNames = []): array
	{
		$snapshot = [];
		foreach (static::reflectionProperties($object, $propNames) as $name => $rp) {
			$snapshot[$name] = $rp->getValue($object);
		}
		return $snapshot;
	}

	/**
	 * Restores property values on `$object` from a snapshot produced by
	 * {@see snapshot()}.
	 *
	 * Only properties present in `$snapshot` are written back. The method is
	 * safe to call with a partial snapshot (e.g. one captured with an explicit
	 * `$propNames` list).
	 *
	 * @param object             $object   The object to restore.
	 * @param array<string, mixed> $snapshot Snapshot previously captured by snapshot().
	 */
	public static function restore(object $object, array $snapshot): void
	{
		foreach (static::reflectionProperties($object, array_keys($snapshot)) as $name => $rp) {
			$rp->setValue($object, $snapshot[$name]);
		}
	}

	/**
	 * Builds a map of property name to `ReflectionProperty` for `$object`.
	 *
	 * Iterates each level of the class hierarchy in order from concrete class
	 * to root so that a child-class property always shadows a same-named
	 * property declared in a parent. Only properties declared at each level
	 * are collected at that level (inherited properties are deferred to their
	 * own declaring class during iteration), ensuring private parent properties
	 * are always reached via their own `ReflectionProperty`.
	 *
	 * @param object   $object    The object to reflect.
	 * @param string[] $filter    When non-empty, only include these names.
	 * @return array<string, \ReflectionProperty>
	 */
	private static function reflectionProperties(object $object, array $filter = []): array
	{
		$props       = [];
		$checkFilter = !empty($filter);
		$class       = new \ReflectionClass($object);

		do {
			$level = $class->getName();
			foreach ($class->getProperties() as $rp) {
				if ($rp->isStatic()) {
					continue;
				}
				// Only collect properties declared at this exact level so that
				// private ancestor properties are accessed via the RP that
				// belongs to the class that declared them.
				if ($rp->getDeclaringClass()->getName() !== $level) {
					continue;
				}
				$name = $rp->getName();
				if ($checkFilter && !in_array($name, $filter, true)) {
					continue;
				}
				// Child-class property wins; never overwrite with an ancestor's.
				if (!isset($props[$name])) {
					$props[$name] = $rp;
				}
			}
		} while ($class = $class->getParentClass());

		return $props;
	}

	/**
	 * Returns the value of a single named property from `$object` via reflection.
	 *
	 * The full class hierarchy is searched so private ancestor properties are
	 * accessible without knowing their declaring class.
	 *
	 * @param object $object The object to read from.
	 * @param string $name   Property name (without `$`).
	 * @return mixed The current property value.
	 * @throws \ReflectionException if the property does not exist on the object.
	 */
	public static function getProp(object $object, string $name): mixed
	{
		$props = static::reflectionProperties($object, [$name]);
		if (!isset($props[$name])) {
			throw new \ReflectionException("Property $name not found on " . get_class($object));
		}
		return $props[$name]->getValue($object);
	}

	/**
	 * Sets the value of a single named property on `$object` via reflection.
	 *
	 * The full class hierarchy is searched so private ancestor properties are
	 * writable without knowing their declaring class.
	 *
	 * @param object $object The object to write to.
	 * @param string $name   Property name (without `$`).
	 * @param mixed  $value  The value to assign.
	 * @throws \ReflectionException if the property does not exist on the object.
	 */
	public static function setProp(object $object, string $name, mixed $value): void
	{
		$props = static::reflectionProperties($object, [$name]);
		if (!isset($props[$name])) {
			throw new \ReflectionException("Property $name not found on " . get_class($object));
		}
		$props[$name]->setValue($object, $value);
	}

	// =========================================================================
	// Static property snapshot / restore
	// =========================================================================

	/**
	 * Captures a snapshot of static property values on `$class`.
	 *
	 * Walks the full class hierarchy so that private static properties declared
	 * at every ancestor level are included. When `$propNames` is non-empty only
	 * those named properties are captured; otherwise every static property is
	 * captured.
	 *
	 * The returned array is suitable for passing directly to {@see restoreStatic()}.
	 *
	 * ```php
	 * $snap = PradoUnit::snapshotStatic(Prado::class);
	 * // ... code that mutates Prado static state ...
	 * PradoUnit::restoreStatic(Prado::class, $snap);
	 * ```
	 *
	 * @param class-string $class     The class to snapshot (e.g. `Prado::class`).
	 * @param string[]     $propNames Optional list of property names to capture.
	 * @return array<string, mixed> Map of property name to captured value.
	 */
	public static function snapshotStatic(string $class, array $propNames = []): array
	{
		$snapshot = [];
		foreach (static::reflectionStaticProperties($class, $propNames) as $name => $rp) {
			$snapshot[$name] = $rp->getValue(null);
		}
		return $snapshot;
	}

	/**
	 * Restores static property values on `$class` from a snapshot produced by
	 * {@see snapshotStatic()}.
	 *
	 * Only properties present in `$snapshot` are written back. The method is
	 * safe to call with a partial snapshot (e.g. one captured with an explicit
	 * `$propNames` list).
	 *
	 * @param class-string         $class    The class to restore (e.g. `Prado::class`).
	 * @param array<string, mixed> $snapshot Snapshot previously captured by snapshotStatic().
	 */
	public static function restoreStatic(string $class, array $snapshot): void
	{
		foreach (static::reflectionStaticProperties($class, array_keys($snapshot)) as $name => $rp) {
			$rp->setValue(null, $snapshot[$name]);
		}
	}

	/**
	 * Returns the value of a single named static property from `$class` via reflection.
	 *
	 * The full class hierarchy is searched so private ancestor static properties
	 * are accessible without knowing their declaring class.
	 *
	 * @param class-string $class The class to read from.
	 * @param string       $name  Property name (without `$`).
	 * @return mixed The current property value.
	 * @throws \ReflectionException if the property does not exist on the class.
	 */
	public static function getStaticProp(string $class, string $name): mixed
	{
		$props = static::reflectionStaticProperties($class, [$name]);
		if (!isset($props[$name])) {
			throw new \ReflectionException("Static property {$name} not found on {$class}");
		}
		return $props[$name]->getValue(null);
	}

	/**
	 * Sets the value of a single named static property on `$class` via reflection.
	 *
	 * The full class hierarchy is searched so private ancestor static properties
	 * are writable without knowing their declaring class.
	 *
	 * @param class-string $class The class to write to.
	 * @param string       $name  Property name (without `$`).
	 * @param mixed        $value The value to assign.
	 * @throws \ReflectionException if the property does not exist on the class.
	 */
	public static function setStaticProp(string $class, string $name, mixed $value): void
	{
		$props = static::reflectionStaticProperties($class, [$name]);
		if (!isset($props[$name])) {
			throw new \ReflectionException("Static property {$name} not found on {$class}");
		}
		$props[$name]->setValue(null, $value);
	}

	/**
	 * Builds a map of property name to `ReflectionProperty` for the static properties
	 * of `$class`.
	 *
	 * Mirrors {@see reflectionProperties()} but collects static properties. Iterates
	 * each level of the class hierarchy so that private static properties declared at
	 * ancestor levels are reached via the `ReflectionProperty` that belongs to the
	 * class that declared them.
	 *
	 * @param class-string $class  The class to reflect.
	 * @param string[]     $filter When non-empty, only include these names.
	 * @return array<string, \ReflectionProperty>
	 */
	private static function reflectionStaticProperties(string $class, array $filter = []): array
	{
		$props = [];
		$checkFilter = !empty($filter);
		$rc = new \ReflectionClass($class);

		do {
			$level = $rc->getName();
			foreach ($rc->getProperties(\ReflectionProperty::IS_STATIC) as $rp) {
				if ($rp->getDeclaringClass()->getName() !== $level) {
					continue;
				}
				$name = $rp->getName();
				if ($checkFilter && !in_array($name, $filter, true)) {
					continue;
				}
				if (!isset($props[$name])) {
					$props[$name] = $rp;
				}
			}
		} while ($rc = $rc->getParentClass());

		return $props;
	}

	// =========================================================================
	// CI / test-environment detection
	// =========================================================================

	/**
	 * Returns `true` when any recognised continuous-integration environment variable
	 * is set, indicating the tests are running in a CI pipeline rather than a local
	 * developer machine.
	 *
	 * Checks the generic `CI` variable (set by GitHub Actions, Travis CI, CircleCI,
	 * and many others) as well as several CI-specific sentinels.  See
	 * {@see getCIEnvironment()} for the full list.
	 *
	 * @return bool
	 */
	public static function isCI(): bool
	{
		return static::getCIEnvironment() !== null;
	}

	/**
	 * Returns `true` when tests are running inside a **GitHub Actions** workflow.
	 *
	 * GitHub Actions sets `GITHUB_ACTIONS=true` for every step in a workflow job.
	 *
	 * @return bool
	 */
	public static function isGitHubActions(): bool
	{
		return getenv('GITHUB_ACTIONS') === 'true';
	}

	/**
	 * Returns a human-readable name for the detected CI environment, or `null` when
	 * the tests appear to be running locally.
	 *
	 * Detection order (first match wins):
	 *
	 * | Returned string           | Environment variable checked          |
	 * |---------------------------|---------------------------------------|
	 * | `'GitHub Actions'`        | `GITHUB_ACTIONS === 'true'`           |
	 * | `'Travis CI'`             | `TRAVIS === 'true'`                   |
	 * | `'CircleCI'`              | `CIRCLECI === 'true'`                 |
	 * | `'GitLab CI'`             | `GITLAB_CI === 'true'`                |
	 * | `'Jenkins'`               | `JENKINS_URL` is set (any value)      |
	 * | `'Scrutinizer CI'`        | `SCRUTINIZER === 'true'`              |
	 * | `'Bitbucket Pipelines'`   | `BITBUCKET_BUILD_NUMBER` is set       |
	 * | `'Drone CI'`              | `DRONE === 'true'`                    |
	 * | `'TeamCity'`              | `TEAMCITY_VERSION` is set             |
	 * | `'AppVeyor'`              | `APPVEYOR === 'True'`                 |
	 * | `'Azure Pipelines'`       | `TF_BUILD === 'True'`                 |
	 * | `'Codeship'`              | `CI_NAME === 'codeship'`              |
	 * | `'Buildkite'`             | `BUILDKITE === 'true'`                |
	 * | `'Heroku CI'`             | `HEROKU_TEST_RUN_ID` is set           |
	 * | `'CI'`                    | `CI` is set (generic fallback)        |
	 *
	 * @return string|null  CI environment name, or `null` when running locally.
	 */
	public static function getCIEnvironment(): ?string
	{
		if (getenv('GITHUB_ACTIONS') === 'true') {
			return 'GitHub Actions';
		}
		if (getenv('TRAVIS') === 'true') {
			return 'Travis CI';
		}
		if (getenv('CIRCLECI') === 'true') {
			return 'CircleCI';
		}
		if (getenv('GITLAB_CI') === 'true') {
			return 'GitLab CI';
		}
		if (getenv('JENKINS_URL') !== false) {
			return 'Jenkins';
		}
		if (getenv('SCRUTINIZER') === 'true') {
			return 'Scrutinizer CI';
		}
		if (getenv('BITBUCKET_BUILD_NUMBER') !== false) {
			return 'Bitbucket Pipelines';
		}
		if (getenv('DRONE') === 'true') {
			return 'Drone CI';
		}
		if (getenv('TEAMCITY_VERSION') !== false) {
			return 'TeamCity';
		}
		if (getenv('APPVEYOR') === 'True') {
			return 'AppVeyor';
		}
		if (getenv('TF_BUILD') === 'True') {
			return 'Azure Pipelines';
		}
		if (getenv('CI_NAME') === 'codeship') {
			return 'Codeship';
		}
		if (getenv('BUILDKITE') === 'true') {
			return 'Buildkite';
		}
		if (getenv('HEROKU_TEST_RUN_ID') !== false) {
			return 'Heroku CI';
		}
		if (getenv('CI') !== false) {
			return 'CI';
		}
		return null;
	}

	/**
	 * Returns `true` when the `PRADO_UNITTEST_SKIP_SLOW` environment variable is set
	 * to `'1'`, indicating that time-consuming tests should be skipped.
	 *
	 * Useful for keeping fast feedback loops during local development while still
	 * allowing full coverage runs in CI pipelines.
	 *
	 * ```php
	 * if (PradoUnit::skipSlowTests()) {
	 *     $this->markTestSkipped('PRADO_UNITTEST_SKIP_SLOW=1');
	 * }
	 * ```
	 *
	 * @return bool
	 */
	public static function skipSlowTests(): bool
	{
		return getenv('PRADO_UNITTEST_SKIP_SLOW') === '1';
	}

	// =========================================================================
	// Database helpers
	// =========================================================================

	/**
	 * @var array<string, true> Drivers for which a "no connection" error has already
	 *   been reported in full during this test run. Subsequent failures from the same
	 *   driver produce a shorter "Duplicated … Error" string to keep test output
	 *   readable. Keyed by the PDO driver name returned by
	 *   {@see \Prado\Data\TDbConnection::getDriverName()}.
	 */
	public static $dbConnectionException = [];

	/**
	 * @var array<string, true> Drivers for which a "database not found" error has
	 *   already been reported in full. Same deduplication scheme as
	 *   {@see $dbConnectionException}.
	 */
	public static $dbDatabaseException = [];

	/**
	 * @var array<string, true> Drivers for which a "table not found" error has
	 *   already been reported in full. Same deduplication scheme as
	 *   {@see $dbConnectionException}.
	 */
	public static $dbTableException = [];

	/**
	 * Returns `true` when the `PRADO_UNITTEST_SKIP_DB` environment variable is set
	 * to `'1'`.
	 *
	 * When `true`, {@see processException()} appends `(PRADO_UNITTEST_SKIP_DB=1)` to
	 * the first human-readable error string for each driver, signalling that the
	 * database failure was expected and opted-in.  Individual test classes can call
	 * this directly to skip database-dependent assertions altogether:
	 *
	 * ```php
	 * if (PradoUnit::skipDatabaseTests()) {
	 *     $this->markTestSkipped('PRADO_UNITTEST_SKIP_DB=1');
	 * }
	 * ```
	 *
	 * @return bool
	 */
	public static function skipDatabaseTests(): bool
	{
		return getenv('PRADO_UNITTEST_SKIP_DB') === '1';
	}

	/**
	 * Opens a MySQL connection and wraps it in a `TMysqlMetaData` instance.
	 *
	 * This is a convenience shortcut for metadata-specific tests that need both a
	 * live connection and the schema introspection object in one call.
	 *
	 * **Note:** If {@see setupMysqlConnection()} returns an error string or an
	 * `\Exception` (e.g. the pdo_mysql extension is absent or the server is
	 * unreachable) this method still passes that value to the `TMysqlMetaData`
	 * constructor, which will likely throw. Callers that need graceful degradation
	 * should call {@see setupMysqlConnection()} directly and inspect the return
	 * value before constructing the metadata object.
	 *
	 * @param string $database Optional database/schema name appended as `;dbname=<name>`
	 *   to the DSN. Pass an empty string (the default) to connect without selecting a
	 *   database.
	 * @return \Prado\Data\Common\Mysql\TMysqlMetaData
	 */
	public static function setupMysqlMetaData($database = '')
	{
		$conn = static::setupMysqlConnection($database);
		return new TMysqlMetaData($conn);
	}

	/**
	 * Opens a MySQL database connection for unit tests.
	 *
	 * Uses the DSN `mysql:host=localhost[;dbname=<database>]` with credentials
	 * `prado_unitest` / `prado_unitest`.
	 *
	 * Returns early with a human-readable string when the `pdo_mysql` extension is
	 * not loaded. Otherwise attempts to activate the connection; on failure delegates
	 * to {@see processException()} which classifies the error into one of three
	 * known categories and returns a string or the original exception.
	 *
	 * When `$isActiveRecord` is `true` and the connection succeeds, the shared
	 * `TActiveRecordManager` instance's `DbConnection` property is set to the new
	 * connection so that ActiveRecord tests can use it without additional setup.
	 *
	 * @param string $database       Optional database/schema name. Empty string
	 *   connects without selecting a specific database.
	 * @param bool   $isActiveRecord When `true`, sets the connection on
	 *   `TActiveRecordManager::getInstance()`.
	 * @return \Prado\Data\TDbConnection|string|\Exception
	 *   `TDbConnection` on success; a human-readable `string` for a known failure
	 *   (extension missing, server unreachable, database not found, table absent);
	 *   the original `\Exception` for any unrecognised error.
	 */
	public static function setupMysqlConnection($database = '', $isActiveRecord = false)
	{
		if (!empty($database)) {
			$database = ';dbname=' . $database;
		}
		if (!extension_loaded('pdo_mysql')) {
			return 'The pdo_mysql extension is not available.';
		}
		$conn = new TDbConnection('mysql:host=localhost'. $database, 'prado_unitest', 'prado_unitest');
		try {
			$conn->setActive(true);
			if ($isActiveRecord) {
				TActiveRecordManager::getInstance()->setDbConnection($conn);
			}
		} catch(\Exception $e) {
			return static::processException($e, $conn);
		}
		return $conn;
	}

	/**
	 * Opens a PostgreSQL database connection for unit tests.
	 *
	 * Uses the DSN `pgsql:host=localhost[;dbname=<database>]`. Credentials default
	 * to `prado_unitest` / `prado_unitest`; on Scrutinizer CI they are overridden to
	 * `scrutinizer` / `scrutinizer`.
	 *
	 * Returns early with a human-readable string when the `pdo_pgsql` extension is not
	 * loaded. On connection failure delegates to {@see processException()}.
	 *
	 * @param string $database       Optional database/schema name.
	 * @param bool   $isActiveRecord When `true`, sets the connection on
	 *   `TActiveRecordManager::getInstance()`.
	 * @return \Prado\Data\TDbConnection|string|\Exception
	 */
	public static function setupPgsqlConnection($database = '', $isActiveRecord = false)
	{
		if (!empty($database)) {
			$database = ';dbname=' . $database;
		}
		if (!extension_loaded('pdo_pgsql')) {
			return 'The pdo_pgsql extension is not available.';
		}
		$cred = getenv('SCRUTINIZER') ? 'scrutinizer' : 'prado_unitest';
		$conn = new TDbConnection('pgsql:host=localhost'. $database, $cred, $cred);
		try {
			$conn->setActive(true);
			if ($isActiveRecord) {
				TActiveRecordManager::getInstance()->setDbConnection($conn);
			}
		} catch(\Exception $e) {
			return static::processException($e, $conn);
		}
		return $conn;
	}

	/**
	 * Opens a Firebird database connection for unit tests.
	 *
	 * The database path defaults to the `FIREBIRD_DB_PATH` environment variable, or
	 * `/var/lib/firebird/data/prado_unitest.fdb` when the variable is unset.
	 * Credentials are `SYSDBA` / `masterkey`.
	 *
	 * Returns early with a human-readable string when the `pdo_firebird` extension is
	 * not loaded. On connection failure delegates to {@see processException()}.
	 *
	 * @param string $database       Optional absolute path to the Firebird database
	 *   file. Overrides the environment variable default when non-empty.
	 * @param bool   $isActiveRecord When `true`, sets the connection on
	 *   `TActiveRecordManager::getInstance()`.
	 * @return \Prado\Data\TDbConnection|string|\Exception
	 */
	public static function setupFirebirdConnection($database = '', $isActiveRecord = false)
	{
		if (!extension_loaded('pdo_firebird')) {
			return 'The pdo_firebird extension is not available.';
		}
		$dbPath = !empty($database) ? $database : (getenv('FIREBIRD_DB_PATH') ?: '/var/lib/firebird/data/prado_unitest.fdb');
		$conn = new TDbConnection(
			'firebird:dbname=localhost:' . $dbPath . ';charset=UTF8',
			'SYSDBA',
			'masterkey'
		);
		try {
			$conn->setActive(true);
			if ($isActiveRecord) {
				TActiveRecordManager::getInstance()->setDbConnection($conn);
			}
		} catch(\Exception $e) {
			return static::processException($e, $conn);
		}
		return $conn;
	}

	/**
	 * Opens an SQLite database connection for unit tests.
	 *
	 * When `$database` is empty the connection uses an in-memory database
	 * (`sqlite::memory:`). Otherwise `$database` is used as the file path for the DSN.
	 *
	 * Returns early with a human-readable string when the `pdo_sqlite` extension is not
	 * loaded. On connection failure delegates to {@see processException()}.
	 *
	 * @param string $database       Optional absolute path to an SQLite database file.
	 *   Pass an empty string (the default) for an in-memory database.
	 * @param bool   $isActiveRecord When `true`, sets the connection on
	 *   `TActiveRecordManager::getInstance()`.
	 * @return \Prado\Data\TDbConnection|string|\Exception
	 */
	public static function setupSqliteConnection($database = '', $isActiveRecord = false)
	{
		if (!extension_loaded('pdo_sqlite')) {
			return 'The pdo_sqlite extension is not available.';
		}
		$dsn = !empty($database) ? 'sqlite:' . $database : 'sqlite::memory:';
		$conn = new TDbConnection($dsn, '', '');
		try {
			$conn->setActive(true);
			if ($isActiveRecord) {
				TActiveRecordManager::getInstance()->setDbConnection($conn);
			}
		} catch(\Exception $e) {
			return static::processException($e, $conn);
		}
		return $conn;
	}

	/**
	 * Opens a Microsoft SQL Server database connection for unit tests.
	 *
	 * Uses the DSN `sqlsrv:Server=localhost,1433[;Database=<database>];TrustServerCertificate=yes`
	 * with credentials `prado_unitest` / `prado_unitest`.
	 *
	 * Returns early with a human-readable string when the `pdo_sqlsrv` extension is not
	 * loaded. On connection failure delegates to {@see processException()}.
	 *
	 * @param string $database       Optional database name appended as `;Database=<name>`.
	 * @param bool   $isActiveRecord When `true`, sets the connection on
	 *   `TActiveRecordManager::getInstance()`.
	 * @return \Prado\Data\TDbConnection|string|\Exception
	 */
	public static function setupMssqlConnection($database = '', $isActiveRecord = false)
	{
		if (!extension_loaded('pdo_sqlsrv')) {
			return 'The pdo_sqlsrv extension is not available.';
		}
		$dbParam = !empty($database) ? ';Database=' . $database : '';
		$conn = new TDbConnection(
			'sqlsrv:Server=localhost,1433' . $dbParam . ';TrustServerCertificate=yes',
			'prado_unitest',
			'prado_unitest'
		);
		try {
			$conn->setActive(true);
			if ($isActiveRecord) {
				TActiveRecordManager::getInstance()->setDbConnection($conn);
			}
		} catch(\Exception $e) {
			return static::processException($e, $conn);
		}
		return $conn;
	}

	/**
	 * Opens an Oracle database connection for unit tests.
	 *
	 * The service name defaults to the `ORACLE_SERVICE_NAME` environment variable, or
	 * `FREEPDB1` when the variable is unset. Credentials are `prado_unitest` /
	 * `prado_unitest`.
	 *
	 * Returns early with a human-readable string when the `pdo_oci` extension is not
	 * loaded. On connection failure delegates to {@see processException()}.
	 *
	 * @param string $database       Optional Oracle service name. Overrides the
	 *   environment variable default when non-empty.
	 * @param bool   $isActiveRecord When `true`, sets the connection on
	 *   `TActiveRecordManager::getInstance()`.
	 * @return \Prado\Data\TDbConnection|string|\Exception
	 */
	public static function setupOciConnection($database = '', $isActiveRecord = false)
	{
		if (!extension_loaded('pdo_oci')) {
			return 'The pdo_oci extension is not available.';
		}
		$serviceName = !empty($database) ? $database : (getenv('ORACLE_SERVICE_NAME') ?: 'FREEPDB1');
		$conn = new TDbConnection(
			'oci:dbname=//localhost:1521/' . $serviceName,
			'prado_unitest',
			'prado_unitest'
		);
		try {
			$conn->setActive(true);
			if ($isActiveRecord) {
				TActiveRecordManager::getInstance()->setDbConnection($conn);
			}
		} catch(\Exception $e) {
			return static::processException($e, $conn);
		}
		return $conn;
	}

	/**
	 * Opens an IBM DB2 database connection for unit tests.
	 *
	 * Credentials and database name may be overridden via the `DB2_USER`,
	 * `DB2_PASSWORD`, and `DB2_DATABASE` environment variables; the defaults are
	 * `db2inst1`, `Prado_Unitest1`, and `pradount` respectively.
	 *
	 * Returns early with a human-readable string when the `pdo_ibm` extension is not
	 * loaded. On connection failure delegates to {@see processException()}.
	 *
	 * @param string $database       Optional database name. Overrides the `DB2_DATABASE`
	 *   environment variable when non-empty.
	 * @param bool   $isActiveRecord When `true`, sets the connection on
	 *   `TActiveRecordManager::getInstance()`.
	 * @return \Prado\Data\TDbConnection|string|\Exception
	 */
	public static function setupIbmConnection($database = '', $isActiveRecord = false)
	{
		if (!extension_loaded('pdo_ibm')) {
			return 'The pdo_ibm extension is not available.';
		}
		$user     = getenv('DB2_USER')     ?: 'db2inst1';
		$password = getenv('DB2_PASSWORD') ?: 'Prado_Unitest1';
		$dbname   = !empty($database) ? $database : (getenv('DB2_DATABASE') ?: 'pradount');
		$conn = new TDbConnection(
			'ibm:DRIVER={IBM DB2 ODBC DRIVER};DATABASE=' . $dbname . ';HOSTNAME=localhost;PORT=50000;PROTOCOL=TCPIP',
			$user,
			$password
		);
		try {
			$conn->setActive(true);
			if ($isActiveRecord) {
				TActiveRecordManager::getInstance()->setDbConnection($conn);
			}
		} catch(\Exception $e) {
			return static::processException($e, $conn);
		}
		return $conn;
	}

	/**
	 * Verifies that `$tableName` is accessible via `$conn` by executing a no-op
	 * SELECT and discarding the result.
	 *
	 * Returns `null` when the table exists and is readable. On failure, delegates to
	 * {@see processException()} which classifies the error:
	 *
	 * - If it matches the "table not found" pattern, returns a human-readable string
	 *   (possibly a short "Duplicated …" string on repeated failures for the same
	 *   driver).
	 * - If the exception is unrecognised, returns the original `\Exception`.
	 *
	 * Callers from {@see PradoUnitDataConnectionTrait::setUpConnection()} use the
	 * return value to `markTestSkipped()` (string) or rethrow (exception), keeping
	 * table-missing failures non-fatal and annotated.
	 *
	 * @param \Prado\Data\TDbConnection $conn      An active database connection.
	 * @param string                    $tableName Unquoted table name to probe.
	 * @return string|\Exception|null `null` on success; a human-readable `string` or
	 *   `\Exception` on failure.
	 */
	public static function checkForTable($conn, $tableName): mixed
	{
		$sql = 'SELECT * FROM ' . $tableName . ' WHERE 0=1';
		try {
			$conn->createCommand($sql)->query()->close();
		} catch (Exception $e) {
			return static::processException($e, $conn);
		}
		return null;
	}

	/**
	 * Classifies a database exception into one of three known failure categories
	 * (no connection, no database, no table) and returns a human-readable string
	 * describing the failure, or the original exception if it is unrecognised.
	 *
	 * **Parameter mutation — intentional.**
	 * `$e` is explicitly overwritten from the incoming `\Exception` object to a
	 * `string` when the exception matches a known category. The caller receives
	 * whichever form `$e` holds at the point of `return`. This is the designed
	 * contract: callers can `return static::processException($e, $conn)` and
	 * propagate either a descriptive string (recognised failure) or the raw
	 * exception (unrecognised failure) without a separate branch.
	 *
	 * **Reference parameter — intentional.**
	 * `$connection` is passed by reference so that a subclass or future override
	 * can null or replace the connection handle in the caller's scope (e.g. to
	 * release resources immediately on error). The current implementation only
	 * reads `$connection->getDriverName()` and does not modify the variable, but
	 * the reference is preserved by design to keep that door open without a
	 * signature change.
	 *
	 * **Per-driver deduplication across the test run.**
	 * The three static arrays {@see $dbConnectionException}, {@see $dbDatabaseException},
	 * and {@see $dbTableException} are indexed by driver name. The first time a
	 * given driver produces a known error the full message (including the raw
	 * exception text) is returned and the driver is recorded. Subsequent failures
	 * from the same driver produce a shorter "Duplicated … Error" string instead,
	 * keeping test output readable when dozens of test files all try the same
	 * unavailable server.
	 *
	 * **`PRADO_UNITTEST_SKIP_DB=1` annotation.**
	 * When {@see skipDatabaseTests()} returns `true` the string `(PRADO_UNITTEST_SKIP_DB=1)`
	 * is appended to the first-occurrence message so the reader knows the failure
	 * was expected and opted-in.
	 *
	 * @param \Exception $e          The caught exception. Overwritten in-place with a
	 *   descriptive string when the exception matches a known DB-failure pattern;
	 *   left as the original exception object when it does not match any category.
	 * @param \Prado\Data\TDbConnection &$connection Connection whose
	 *   {@see \Prado\Data\TDbConnection::getDriverName()} is used to key the
	 *   per-driver deduplication tables. Passed by reference so callers or
	 *   subclasses may null it on error without a signature change.
	 * @return \Exception|string The (possibly overwritten) `$e`: a descriptive string
	 *   for a recognised connection / database / table failure, or the original
	 *   `\Exception` for any unrecognised error.
	 */
	public static function processException($e, &$connection)
	{
		$driver = $connection->getDriverName();
		if (static::isNoConnection($e)) {
			if (isset(static::$dbConnectionException[$driver])) {
				$e = strtr("Duplicated Database Driver '{0}' Unavailable Error", ['{0}' => $driver]);
			} else {
				if (static::skipDatabaseTests()) {
					$e = strtr("Database Driver '{0}' Unavailable Error [PRADO_UNITTEST_SKIP_DB=1]:\n{1}", ['{0}' => $driver, '{1}' => $e->getMessage()]);
				}
				static::$dbConnectionException[$driver] = true;
			}
		} elseif (static::isNoDatabase($e)) {
			//TDbConnection failed to establish DB connection: SQLSTATE[HY000] [1049] Unknown database 'prado_unitest'
			if (isset(static::$dbDatabaseException[$driver])) {
				$e = strtr("Duplicated Database '{0}' Not Found Error (Connection OK)", ['{0}' => $driver]);
			} else {
				if (static::skipDatabaseTests()) {
					$e .= strtr("Database '{0}' Not Found Error (Connection OK) [PRADO_UNITTEST_SKIP_DB=1]:\n{1}", ['{0}' => $driver, '{1}' => $e->getMessage()]);;
				}
				static::$dbDatabaseException[$driver] = true;
			}
		} elseif (static::isNoTable($e)) {
			if (isset(static::$dbTableException[$driver])) {
				$e = strtr("Duplicated Table Not Found Error (driver: '{0}')", ['{0}' => $driver]);
			} else {
				if (static::skipDatabaseTests()) {
					$e = strtr("Table Not Found Error (driver: '{0}') [PRADO_UNITTEST_SKIP_DB=1]:\n{1}", ['{0}' => $driver, '{1}' => $e->getMessage()]);
				}
				static::$dbTableException[$driver] = true;
			}
		}
		return $e;
	}

	/**
	 * Returns `true` when `$e` looks like a "server not reachable" exception.
	 *
	 * Matches exception message text containing any of the following
	 * (case-insensitive):
	 * - `"No such file"` — Unix socket path does not exist (MySQL / PostgreSQL).
	 * - `"Connection refused"` — TCP port not listening.
	 * - `"failed to establish"` — PRADO's own TDbConnection preamble for a PDO
	 *   connection failure.
	 *
	 * @param \Exception|string $e Exception object or stringified exception text.
	 * @return bool
	 */
	public static function isNoConnection($e): bool
	{
		return is_int(stripos((string) $e->getMessage(), 'No such file')) || 
			   is_int(stripos((string) $e->getMessage(), 'Connection refused')) || 
			   is_int(stripos((string) $e->getMessage(), 'failed to establish'));
	}

	/**
	 * Returns `true` when `$e` looks like a "database / schema not found" exception.
	 *
	 * Matches exception message text containing `"Unknown database"` (case-insensitive),
	 * which is the phrasing used by MySQL when the requested schema does not exist.
	 * Extend or override this method to add equivalent phrases for other drivers.
	 *
	 * @param \Exception|string $e Exception object or stringified exception text.
	 * @return bool
	 */
	public static function isNoDatabase($e): bool
	{
		return is_int(stripos((string) $e->getMessage(), 'Unknown database'));
	}

	/**
	 * Returns `true` when `$e` looks like a "table or view not found" exception.
	 *
	 * Matches exception message text containing `"Base table or view not found"`
	 * (case-insensitive), which is the standard MySQL/MariaDB error text for SQLSTATE
	 * 42S02. Extend or override this method to add equivalent phrases for other
	 * drivers.
	 *
	 * @param \Exception|string $e Exception object or stringified exception text.
	 * @return bool
	 */
	public static function isNoTable($e): bool
	{
		return is_int(stripos((string) $e->getMessage(), 'Base table or view not found'));
	}
}
