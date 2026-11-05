<?php

//namespace Prado\Tests;

/**
 * DbInit — test database initializer.
 *
 * Initialises every supported database before the test suite runs, mimicking
 * the per-driver setup steps in .github/workflows/prado.yml.
 *
 * Can be invoked two ways:
 *  1. Automatically by PHPUnit via {@see PradoTestListener::bootstrap()} — runs
 *     once at the very start of every phpunit invocation.
 *  2. Manually via `composer dbinit` / `php tests/test_tools/initdb.php`.
 *
 * Every initialiser is non-fatal: if a driver is unavailable or credentials
 * are wrong the step is silently skipped.  SQLite uses :memory: databases and
 * self-initialises inside each test's setUp() — no external step is needed.
 *
 * Environment overrides (all optional):
 *
 *   MySQL:
 *     MYSQL_ROOT_USER    privileged user   (default: root)
 *     MYSQL_ROOT_PASS    root password     (default: tries '' then 'root')
 *     MYSQL_HOST         host              (default: 127.0.0.1)
 *
 *   PostgreSQL:
 *     PGSQL_SUPER_USER   superuser         (default: current unix user, then 'postgres')
 *     PGSQL_SUPER_PASS   password          (default: '' then user name then 'postgres')
 *     PGSQL_HOST         host              (default: 127.0.0.1)
 *     PGSQL_PORT         port              (default: 5432)
 *
 *   SQL Server:
 *     SQLSRV_HOST        host,port         (default: 127.0.0.1,1433)
 *     SQLSRV_SA_USER     privileged user   (default: sa)
 *     SQLSRV_SA_PASS     password          (default: Prado_Unitest1)
 *
 *   Firebird:
 *     FIREBIRD_HOST      host              (default: localhost)
 *     FIREBIRD_DB_PATH   .fdb file path    (default: /var/lib/firebird/data/prado_unitest.fdb)
 *     FIREBIRD_USER      user              (default: SYSDBA)
 *     FIREBIRD_PASS      password          (default: masterkey)
 *
 *   Oracle:
 *     ORACLE_HOST        host              (default: localhost)
 *     ORACLE_PORT        port              (default: 1521)
 *     ORACLE_SERVICE     service name      (default: FREEPDB1)
 *     ORACLE_USER        user              (default: prado_unitest)
 *     ORACLE_PASS        password          (default: prado_unitest)
 *
 *   IBM DB2:
 *     DB2_HOST           host              (default: localhost)
 *     DB2_PORT           port              (default: 50000)
 *     DB2_DATABASE       database name     (default: pradount)
 *     DB2_USER           user              (default: db2inst1)
 *     DB2_PASSWORD       password          (default: Prado_Unitest1)
 */
class DbInit
{
	/** Prevents running init more than once per process (e.g. when test suites nest). */
	private static bool $done = false;

	// -------------------------------------------------------------------------
	// Public API
	// -------------------------------------------------------------------------

	/**
	 * Initialises all databases whose PDO extension is loaded and whose server
	 * is reachable.  Returns true if every reachable database initialised
	 * without errors; returns false if any reachable database produced errors.
	 *
	 * @param bool $quiet When true, only errors are printed (PHPUnit context).
	 *                    When false, progress is printed to stdout (CLI context).
	 */
	public static function initAll(bool $quiet = false): bool
	{
		if (self::$done) {
			return true;
		}
		self::$done = true;

		if (!$quiet) {
			self::out('');
			self::out('=== Prado test database initialization ===');
			self::out('(SQLite uses :memory: and self-initialises in setUp — no step needed.)');
			self::out('');
		}

		$results = [
			'MySQL'      => self::initMysql($quiet),
			'PostgreSQL' => self::initPgsql($quiet),
			'SQL Server' => self::initSqlSrv($quiet),
			'Firebird'   => self::initFirebird($quiet),
			'Oracle'     => self::initOracle($quiet),
			'IBM DB2'    => self::initIbm($quiet),
		];

		if (!$quiet) {
			self::out('Summary:');
			foreach ($results as $name => $ok) {
				self::out('  ' . $name . ': ' . ($ok ? 'OK' : 'FAILED'));
			}
			self::out('');
		}

		return !in_array(false, $results, true);
	}

	// -------------------------------------------------------------------------
	// SQL parsers
	// -------------------------------------------------------------------------

	/**
	 * Splits on `;` — MySQL, PostgreSQL, Firebird, Oracle.
	 * Strips line comments (-- and #) and block comments.
	 * Respects quoted strings and backtick identifiers.
	 */
	public static function splitSemicolon(string $sql): array
	{
		$sql = preg_replace('#/\*.*?\*/#s', '', $sql);

		$statements = [];
		$current    = '';
		$inString   = false;
		$stringChar = '';
		$len        = strlen($sql);

		for ($i = 0; $i < $len; $i++) {
			$ch   = $sql[$i];
			$next = $sql[$i + 1] ?? '';

			if (!$inString && (($ch === '-' && $next === '-') || $ch === '#')) {
				while ($i < $len && $sql[$i] !== "\n") {
					$i++;
				}
				$current .= "\n";
				continue;
			}

			if ($inString) {
				$current .= $ch;
				if ($ch === '\\') {
					if (isset($sql[$i + 1])) {
						$current .= $sql[++$i];
					}
				} elseif ($ch === $stringChar) {
					if (($sql[$i + 1] ?? '') === $stringChar) {
						$current .= $sql[++$i];
					} else {
						$inString = false;
					}
				}
				continue;
			}

			if ($ch === "'" || $ch === '"' || $ch === '`') {
				$inString = true; $stringChar = $ch; $current .= $ch;
				continue;
			}

			if ($ch === ';') {
				$stmt = trim($current);
				if ($stmt !== '') {
					$statements[] = $stmt;
				}
				$current = '';
				continue;
			}

			$current .= $ch;
		}

		$stmt = trim($current);
		if ($stmt !== '') {
			$statements[] = $stmt;
		}

		return $statements;
	}

	/**
	 * Splits on `GO` batch separators — SQL Server / T-SQL.
	 * GO must appear alone on a line.
	 */
	public static function splitGo(string $sql): array
	{
		$sql     = preg_replace('#/\*.*?\*/#s', '', $sql);
		$sql     = preg_replace('/--[^\n]*/', '', $sql);
		$batches = preg_split('/^\s*GO\s*$/im', $sql);

		$statements = [];
		foreach ($batches as $batch) {
			$batch = trim($batch);
			if ($batch !== '') {
				$statements[] = $batch;
			}
		}
		return $statements;
	}

	/**
	 * Splits on `@` terminators — IBM DB2 (`db2 -td@` convention).
	 */
	public static function splitAt(string $sql): array
	{
		$sql   = preg_replace('#/\*.*?\*/#s', '', $sql);
		$sql   = preg_replace('/--[^\n]*/', '', $sql);
		$parts = explode('@', $sql);

		$statements = [];
		foreach ($parts as $part) {
			$part = trim($part);
			if ($part !== '') {
				$statements[] = $part;
			}
		}
		return $statements;
	}

	// -------------------------------------------------------------------------
	// Per-driver initialisers
	// -------------------------------------------------------------------------

	private static function initMysql(bool $quiet): bool
	{
		$sqlFile = __DIR__ . '/../initdb_mysql.sql';
		if (!file_exists($sqlFile) || !extension_loaded('pdo_mysql')) {
			return true;
		}

		$host      = (string) (getenv('MYSQL_HOST') ?: '127.0.0.1');
		$rootUser  = (string) (getenv('MYSQL_ROOT_USER') ?: 'root');
		$envPass   = getenv('MYSQL_ROOT_PASS');
		$passwords = $envPass !== false ? [$envPass] : ['', 'root'];

		// Try both TCP (127.0.0.1) and socket/localhost.  On macOS Homebrew the
		// root account is only accessible via Unix socket (host=localhost), while
		// CI uses TCP.  We probe both so either environment works automatically.
		$rootDsns = ["mysql:host=$host;charset=utf8mb4"];
		if ($host === '127.0.0.1') {
			$rootDsns[] = 'mysql:host=localhost;charset=utf8mb4';
		}

		$pdo = null;
		$connDesc = '';
		foreach ($rootDsns as $dsn) {
			foreach ($passwords as $pass) {
				$pdo = self::tryConnect($dsn, $rootUser, $pass);
				if ($pdo !== null) {
					$connDesc = "$rootUser via $dsn";
					break 2;
				}
			}
		}

		if ($pdo !== null) {
			// Full privileged init: DROP/CREATE DB, users, grants, schema.
			if (!$quiet) {
				self::out("MySQL: initialising as $connDesc …");
			}
			$sql = (string) file_get_contents($sqlFile);
			$sql = preg_replace('/\bCREATE USER(?!\s+IF\s+NOT\s+EXISTS)/i', 'CREATE USER IF NOT EXISTS', $sql);
			[$ok, , $errors] = self::runStatements($pdo, self::splitSemicolon($sql));

			if (!$quiet) {
				self::out("MySQL: $ok statements OK" . ($errors ? ", $errors ERRORS" : '.'));
			} elseif ($errors) {
				self::out("[dbinit] MySQL: $errors error(s) during initialisation.");
			}
			return $errors === 0;
		}

		// Root is unavailable (common on macOS when only socket auth is configured
		// without a password, or when MySQL is not running at all).
		// Fall back to the prado_unitest application user — it has GRANT ALL on
		// the schema, so it can create/replace tables even without DROP DATABASE.
		$appUser = 'prado_unitest';
		$appPass = 'prado_unitest';
		$appDsns = [
			'mysql:host=localhost;dbname=prado_unitest;charset=utf8mb4',
			"mysql:host=$host;dbname=prado_unitest;charset=utf8mb4",
		];

		$pdo2 = null;
		foreach ($appDsns as $dsn) {
			$pdo2 = self::tryConnect($dsn, $appUser, $appPass);
			if ($pdo2 !== null) {
				break;
			}
		}

		if ($pdo2 === null) {
			return true; // MySQL not available at all — skip silently
		}

		if (!$quiet) {
			self::out("MySQL: root unavailable; initialising schema as '$appUser' …");
		}

		// Skip DDL that requires root/superuser privileges: DROP/CREATE DATABASE,
		// CREATE USER, GRANT, FLUSH, and USE (connection already targets the DB).
		$allStmts   = self::splitSemicolon((string) file_get_contents($sqlFile));
		$schemaStmts = array_values(array_filter($allStmts, static function (string $s): bool {
			return !preg_match('/^\s*(DROP|CREATE)\s+DATABASE\b/i', $s)
				&& !preg_match('/^\s*CREATE\s+USER\b/i', $s)
				&& !preg_match('/^\s*GRANT\b/i', $s)
				&& !preg_match('/^\s*FLUSH\b/i', $s)
				&& !preg_match('/^\s*USE\b/i', $s);
		}));

		[$ok, , $errors] = self::runStatements($pdo2, $schemaStmts);

		if (!$quiet) {
			self::out("MySQL: $ok statements OK" . ($errors ? ", $errors ERRORS" : '.'));
		} elseif ($errors) {
			self::out("[dbinit] MySQL: $errors error(s) during initialisation.");
		}
		return $errors === 0;
	}

	private static function initPgsql(bool $quiet): bool
	{
		$sqlFile = __DIR__ . '/../initdb_pgsql.sql';
		if (!file_exists($sqlFile) || !extension_loaded('pdo_pgsql')) {
			return true;
		}

		$host      = (string) (getenv('PGSQL_HOST') ?: '127.0.0.1');
		$port      = (string) (getenv('PGSQL_PORT') ?: '5432');
		$envUser   = getenv('PGSQL_SUPER_USER');
		$envPass   = getenv('PGSQL_SUPER_PASS');
		$unix      = self::unixUser();
		$users     = $envUser !== false ? [$envUser] : array_unique(array_filter([$unix, 'postgres']));
		$passwords = $envPass !== false ? [$envPass] : ['', $unix, 'postgres'];

		// Try both the configured host (TCP) and localhost (socket) so that
		// macOS Homebrew peer-auth works alongside CI TCP connections.
		$superHosts = [$host];
		if ($host === '127.0.0.1') {
			$superHosts[] = 'localhost';
		}

		$pdo = null; $connUser = ''; $connHost = '';
		foreach ($superHosts as $h) {
			foreach ($users as $u) {
				foreach ($passwords as $p) {
					$pdo = self::tryConnect("pgsql:host=$h;port=$port;dbname=postgres", $u, $p);
					if ($pdo !== null) {
						$connUser = $u; $connHost = $h;
						break 3;
					}
				}
			}
		}

		if ($pdo !== null) {
			// Full privileged init: drop/recreate DB, role DDL, schema DDL.
			if (!$quiet) {
				self::out("PostgreSQL: initialising as '$connUser'@'$connHost:$port' …");
			}

			// Terminate connections then drop/recreate DB
			try {
				$pdo->exec("SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname='prado_unitest'");
			} catch (\PDOException $e) {
			}
			foreach (['DROP DATABASE IF EXISTS prado_unitest', 'CREATE DATABASE prado_unitest'] as $ddl) {
				try {
					$pdo->exec($ddl);
				} catch (\PDOException $e) {
					self::out("[dbinit] PostgreSQL: $ddl failed: " . $e->getMessage());
					return false;
				}
			}

			$statements = self::splitSemicolon((string) file_get_contents($sqlFile));
			[$roleStmts, $tableStmts] = self::partitionPgsqlStatements($statements);

			[$ok1, , $er1] = self::runStatements($pdo, $roleStmts);

			// Reconnect to prado_unitest for table DDL
			$pdo2 = null;
			foreach ($superHosts as $h) {
				foreach ($users as $u) {
					foreach ($passwords as $p) {
						$pdo2 = self::tryConnect("pgsql:host=$h;port=$port;dbname=prado_unitest", $u, $p);
						if ($pdo2 !== null) {
							break 3;
						}
					}
				}
			}
			if ($pdo2 === null) {
				self::out("[dbinit] PostgreSQL: cannot reconnect to 'prado_unitest'.");
				return false;
			}

			[$ok2, , $er2] = self::runStatements($pdo2, $tableStmts);
			$ok = $ok1 + $ok2; $er = $er1 + $er2;

			if (!$quiet) {
				self::out("PostgreSQL: $ok statements OK" . ($er ? ", $er ERRORS" : '.'));
			} elseif ($er) {
				self::out("[dbinit] PostgreSQL: $er error(s) during initialisation.");
			}
			return $er === 0;
		}

		// Superuser unavailable — fall back to the prado_unitest app user.
		// It has GRANT ALL on the schema, so it can create/replace tables.
		$appUser = 'prado_unitest';
		$appPass = 'prado_unitest';
		$appHosts = ['localhost', $host];

		$pdo3 = null;
		foreach ($appHosts as $h) {
			$pdo3 = self::tryConnect("pgsql:host=$h;port=$port;dbname=prado_unitest", $appUser, $appPass);
			if ($pdo3 !== null) {
				break;
			}
		}

		if ($pdo3 === null) {
			return true; // PostgreSQL not available at all — skip silently
		}

		if (!$quiet) {
			self::out("PostgreSQL: superuser unavailable; initialising schema as '$appUser' …");
		}

		// Skip role-level DDL that requires superuser privileges.
		$allStmts    = self::splitSemicolon((string) file_get_contents($sqlFile));
		[, $tableStmts] = self::partitionPgsqlStatements($allStmts);
		[$ok, , $er] = self::runStatements($pdo3, $tableStmts);

		if (!$quiet) {
			self::out("PostgreSQL: $ok statements OK" . ($er ? ", $er ERRORS" : '.'));
		} elseif ($er) {
			self::out("[dbinit] PostgreSQL: $er error(s) during initialisation.");
		}
		return $er === 0;
	}

	private static function initSqlSrv(bool $quiet): bool
	{
		$sqlFile = __DIR__ . '/../initdb_sqlsrv.sql';
		if (!file_exists($sqlFile) || !extension_loaded('pdo_sqlsrv')) {
			return true;
		}

		$host = (string) (getenv('SQLSRV_HOST') ?: '127.0.0.1,1433');
		$user = (string) (getenv('SQLSRV_SA_USER') ?: 'sa');
		$pass = (string) (getenv('SQLSRV_SA_PASS') ?: 'Prado_Unitest1');

		$pdo = self::tryConnect("sqlsrv:Server=$host;Database=master;TrustServerCertificate=1", $user, $pass);
		if ($pdo === null) {
			return true;
		}

		if (!$quiet) {
			self::out("SQL Server: initialising as '$user'@'$host' …");
		}

		[$ok, , $errors] = self::runStatements($pdo, self::splitGo((string) file_get_contents($sqlFile)));

		if (!$quiet) {
			self::out("SQL Server: $ok batches OK" . ($errors ? ", $errors ERRORS" : '.'));
		} elseif ($errors) {
			self::out("[dbinit] SQL Server: $errors error(s) during initialisation.");
		}

		return $errors === 0;
	}

	private static function initFirebird(bool $quiet): bool
	{
		$sqlFile = __DIR__ . '/../initdb_firebird.sql';
		if (!file_exists($sqlFile) || !extension_loaded('pdo_firebird')) {
			return true;
		}

		$host   = (string) (getenv('FIREBIRD_HOST') ?: 'localhost');
		$dbPath = (string) (getenv('FIREBIRD_DB_PATH') ?: '/var/lib/firebird/data/prado_unitest.fdb');
		$user   = (string) (getenv('FIREBIRD_USER') ?: 'SYSDBA');
		$pass   = (string) (getenv('FIREBIRD_PASS') ?: 'masterkey');

		$pdo = self::tryConnect("firebird:dbname=$host:$dbPath;charset=UTF8", $user, $pass);
		if ($pdo === null) {
			return true;
		}

		if (!$quiet) {
			self::out("Firebird: initialising '$host:$dbPath' …");
		}

		[$ok, , $errors] = self::runStatements($pdo, self::splitSemicolon((string) file_get_contents($sqlFile)));

		if (!$quiet) {
			self::out("Firebird: $ok statements OK" . ($errors ? ", $errors ERRORS" : '.'));
		} elseif ($errors) {
			self::out("[dbinit] Firebird: $errors error(s) during initialisation.");
		}

		return $errors === 0;
	}

	private static function initOracle(bool $quiet): bool
	{
		$sqlFile = __DIR__ . '/../initdb_oracle.sql';
		if (!file_exists($sqlFile) || !extension_loaded('pdo_oci')) {
			return true;
		}

		$host    = (string) (getenv('ORACLE_HOST') ?: 'localhost');
		$port    = (string) (getenv('ORACLE_PORT') ?: '1521');
		$service = (string) (getenv('ORACLE_SERVICE') ?: getenv('ORACLE_SERVICE_NAME') ?: 'FREEPDB1');
		$user    = (string) (getenv('ORACLE_USER') ?: 'prado_unitest');
		$pass    = (string) (getenv('ORACLE_PASS') ?: 'prado_unitest');

		$pdo = self::tryConnect("oci:dbname=//$host:$port/$service;charset=AL32UTF8", $user, $pass);
		if ($pdo === null) {
			return true;
		}

		if (!$quiet) {
			self::out("Oracle: initialising '$host:$port/$service' as '$user' …");
		}

		[$ok, , $errors] = self::runStatements($pdo, self::splitSemicolon((string) file_get_contents($sqlFile)));

		if (!$quiet) {
			self::out("Oracle: $ok statements OK" . ($errors ? ", $errors ERRORS" : '.'));
		} elseif ($errors) {
			self::out("[dbinit] Oracle: $errors error(s) during initialisation.");
		}

		return $errors === 0;
	}

	private static function initIbm(bool $quiet): bool
	{
		$sqlFile = __DIR__ . '/../initdb_ibm.sql';
		if (!file_exists($sqlFile) || !extension_loaded('pdo_ibm')) {
			return true;
		}

		$host = (string) (getenv('DB2_HOST') ?: 'localhost');
		$port = (string) (getenv('DB2_PORT') ?: '50000');
		$db   = (string) (getenv('DB2_DATABASE') ?: 'pradount');
		$user = (string) (getenv('DB2_USER') ?: 'db2inst1');
		$pass = (string) (getenv('DB2_PASSWORD') ?: 'Prado_Unitest1');

		$dsn = "ibm:DRIVER={IBM DB2 ODBC DRIVER};DATABASE=$db;HOSTNAME=$host;PORT=$port;PROTOCOL=TCPIP;UID=$user;PWD=$pass";
		$pdo = self::tryConnect($dsn, $user, $pass);
		if ($pdo === null) {
			return true;
		}

		if (!$quiet) {
			self::out("IBM DB2: initialising '$host:$port/$db' as '$user' …");
		}

		[$ok, , $errors] = self::runStatements($pdo, self::splitAt((string) file_get_contents($sqlFile)));

		if (!$quiet) {
			self::out("IBM DB2: $ok statements OK" . ($errors ? ", $errors ERRORS" : '.'));
		} elseif ($errors) {
			self::out("[dbinit] IBM DB2: $errors error(s) during initialisation.");
		}

		return $errors === 0;
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	private static function tryConnect(string $dsn, string $user, string $pass): ?\PDO
	{
		try {
			return new \PDO($dsn, $user, $pass, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
		} catch (\PDOException $e) {
			return null;
		}
	}

	/**
	 * Executes statements, counting OK / skipped (duplicate object) / errors.
	 * @return array{int,int,int}
	 */
	private static function runStatements(\PDO $pdo, array $statements): array
	{
		$ok = $skipped = $errors = 0;
		foreach ($statements as $sql) {
			try {
				$pdo->exec($sql);
				$ok++;
			} catch (\PDOException $e) {
				$code = (string) $e->getCode();
				$msg  = $e->getMessage();
				$ignorable = [
					'already exists', 'Operation CREATE USER',
					'23000', '42710', '42P04', 'S0001',
					'335544351', 'ORA-01920', 'ORA-00955', 'SQL0601N',
				];
				$skip = false;
				foreach ($ignorable as $p) {
					if (str_contains($code, $p) || str_contains($msg, $p)) {
						$skip = true;
						break;
					}
				}
				if ($skip) {
					$skipped++;
				} else {
					self::out("[dbinit] SQL error ($code): " . substr($msg, 0, 200));
					$errors++;
				}
			}
		}
		return [$ok, $skipped, $errors];
	}

	/** Splits PostgreSQL statements into role-level (runs in postgres DB) and table-level. */
	private static function partitionPgsqlStatements(array $statements): array
	{
		$role = $table = [];
		foreach ($statements as $stmt) {
			if (preg_match('/^\s*(DROP|CREATE|ALTER)\s+(ROLE|USER)\b/i', $stmt)
				|| preg_match('/^\s*GRANT\b/i', $stmt)
			) {
				$role[] = $stmt;
			} else {
				$table[] = $stmt;
			}
		}
		return [$role, $table];
	}

	/** Returns the current unix login name, or '' on non-POSIX systems. */
	private static function unixUser(): string
	{
		if (function_exists('posix_getuid') && function_exists('posix_getpwuid')) {
			return (string) (posix_getpwuid(posix_getuid())['name'] ?? '');
		}
		return (string) (getenv('USER') ?: getenv('USERNAME') ?: '');
	}

	private static function out(string $msg): void
	{
		echo $msg . PHP_EOL;
	}
}
