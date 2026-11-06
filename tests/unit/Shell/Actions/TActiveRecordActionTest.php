<?php

namespace Prado\Test\Unit\Shell\Actions;

use Prado\Data\ActiveRecord\TActiveRecord;
use Prado\Data\ActiveRecord\TActiveRecordConfig;
use Prado\Data\ActiveRecord\TActiveRecordManager;
use Prado\Data\TDbConnection;
use Prado\Data\TDbDriver;
use Prado\IO\TTextWriter;
use Prado\Prado;
use Prado\Shell\Actions\TActiveRecordAction;
use Prado\Shell\TShellWriter;
use Prado\Test\Unit\PradoUnit;

/**
 * Covers `activerecord/generate` and `activerecord/generate-all`.
 *
 * Each test runs with its own {@see TActiveRecordManager}, so the gateway's table
 * information cache never carries a schema from one test into another. Tests that write
 * class files work in a unique directory under the system temporary directory: its `app`
 * subdirectory is the application base path and the `ArActionApp` path alias.
 */
class TActiveRecordActionTest extends \PHPUnit\Framework\TestCase
{
	private const OUTPUT = 'Application.nowhere.UserRecord';

	private const ALIAS = 'ArActionApp';

	private TTextWriter $_out;

	private ?TActiveRecordManager $_previousManager = null;

	private array $_aliases = [];

	private ?string $_basePath = null;

	private array $_modules = [];

	/** @var ?string root of the temporary directory tree, null until {@see appDir()} */
	private ?string $_root = null;

	/** @var string[] files this test created or expects the generator to create */
	private array $_files = [];

	/** @var string[] directories this test created, in creation order */
	private array $_dirs = [];

	protected function setUp(): void
	{
		$this->_previousManager = TActiveRecordManager::getInstance();
		TActiveRecordManager::getInstance(new TActiveRecordManager());
		$app = Prado::getApplication();
		$this->_aliases = PradoUnit::getStaticProp(Prado::class, '_aliases');
		$this->_basePath = $app->getBasePath();
		$this->_modules = PradoUnit::getProp($app, '_modules');
	}

	protected function tearDown(): void
	{
		$app = Prado::getApplication();
		PradoUnit::setProp($app, '_modules', $this->_modules);
		$app->setBasePath($this->_basePath);
		PradoUnit::setStaticProp(Prado::class, '_aliases', $this->_aliases);
		TActiveRecordManager::getInstance($this->_previousManager);
		foreach ($this->_files as $file) {
			if (is_file($file)) {
				unlink($file);
			}
		}
		foreach (array_reverse($this->_dirs) as $dir) {
			if (is_dir($dir)) {
				rmdir($dir);
			}
		}
	}

	private function newAction(): TActiveRecordAction
	{
		$this->_out = new TTextWriter();
		$writer = new TShellWriter($this->_out);
		$writer->setColorSupported(false);
		$action = new TActiveRecordAction();
		$action->setWriter($writer);
		return $action;
	}

	private function assertFlags(TActiveRecordAction $action, bool $soap, bool $overwrite): void
	{
		$this->assertSame($soap, PradoUnit::getProp($action, '_soap'), 'soap flag');
		$this->assertSame($overwrite, PradoUnit::getProp($action, '_overwrite'), 'overwrite flag');
	}

	/**
	 * @return string the text of the error boxes in the captured output, one space between wrapped lines
	 */
	private function errorText(string $output): string
	{
		$text = [];
		foreach (explode("\n", $output) as $line) {
			if (preg_match('/^\*  (.*)  \*$/', $line, $match)) {
				$text[] = trim($match[1]);
			}
		}
		return implode(' ', $text);
	}

	private function mkdir(string $dir): string
	{
		mkdir($dir);
		$this->_dirs[] = $dir;
		return realpath($dir);
	}

	/**
	 * Creates `<root>/app/records`, makes `<root>/app` the application base path, and
	 * points the `ArActionApp` alias at it.
	 * @return string the canonical application directory
	 */
	private function appDir(): string
	{
		if ($this->_root === null) {
			$this->_root = $this->mkdir(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado-ar-action-' . bin2hex(random_bytes(6)));
			$app = $this->mkdir($this->_root . DIRECTORY_SEPARATOR . 'app');
			$this->mkdir($app . DIRECTORY_SEPARATOR . 'records');
			Prado::getApplication()->setBasePath($app);
			Prado::setPathOfAlias(self::ALIAS, $app);
		}
		return $this->_root . DIRECTORY_SEPARATOR . 'app';
	}

	/**
	 * @return string path of `<class>.php` in the records directory, removed in tearDown
	 */
	private function recordFile(string $class): string
	{
		return $this->_files[] = $this->appDir() . DIRECTORY_SEPARATOR . 'records' . DIRECTORY_SEPARATOR . $class . '.php';
	}

	private function writeFile(string $file, string $content): void
	{
		$this->_files[] = $file;
		file_put_contents($file, $content);
	}

	private function useConnection(TDbConnection $conn): TDbConnection
	{
		TActiveRecordManager::getInstance()->setDbConnection($conn);
		return $conn;
	}

	private function memoryConnection(string ...$ddl): TDbConnection
	{
		$conn = $this->useConnection(new TDbConnection('sqlite::memory:'));
		$conn->setActive(true);
		foreach ($ddl as $sql) {
			$conn->createCommand($sql)->execute();
		}
		return $conn;
	}

	private function registerConfig(string $connectionID): TActiveRecordConfig
	{
		$config = new TActiveRecordConfig();
		$config->setConnectionID($connectionID);
		Prado::getApplication()->setModule('ar-action-test', $config);
		return $config;
	}

	/**
	 * @return string the generated source with the generation timestamp replaced by `DATE`
	 */
	private function readGenerated(string $file): string
	{
		$this->assertFileExists($file);
		return preg_replace('/^ \* Auto generated by prado-cli\.php on .*\.$/m', ' * Auto generated by prado-cli.php on DATE.', file_get_contents($file));
	}

	private function expectedClass(string $class, string $table, string $props): string
	{
		return "<?php\n"
			. "/**\n"
			. " * Auto generated by prado-cli.php on DATE.\n"
			. " */\n"
			. "class $class extends TActiveRecord\n"
			. "{\n"
			. "\tconst TABLE='$table';\n"
			. "\n"
			. $props . "\n"
			. "\n"
			. "\tpublic static function finder(\$className=__CLASS__)\n"
			. "\t{\n"
			. "\t\treturn parent::finder(\$className);\n"
			. "\t}\n"
			. "}\n";
	}

	// ---------------------------------------------------------------------
	// Argument handling
	// ---------------------------------------------------------------------

	public function testActionGenerate_flagsDefaultToFalse()
	{
		$action = $this->newAction();
		$this->assertTrue($action->actionGenerate(['generate', 'users', self::OUTPUT]));
		$this->assertFlags($action, false, false);
		$this->assertStringContainsString('must be within directory', $this->_out->flush());
	}

	public function testActionGenerate_readsSoapAndOverwrite()
	{
		$action = $this->newAction();
		$action->actionGenerate(['generate', 'users', self::OUTPUT, 'soap', 'overwrite']);
		$this->assertFlags($action, true, true);
	}

	public function testActionGenerate_acceptsTrueForEitherFlag()
	{
		$action = $this->newAction();
		$action->actionGenerate(['generate', 'users', self::OUTPUT, 'true', 'true']);
		$this->assertFlags($action, true, true);
	}

	public function testActionGenerate_overwriteWithoutSoap()
	{
		$action = $this->newAction();
		$action->actionGenerate(['generate', 'users', self::OUTPUT, 'false', 'overwrite']);
		$this->assertFlags($action, false, true);
	}

	public function testGenerate_passesCommandLineTokensThrough()
	{
		$action = $this->newAction();
		$action->generate('generate users ' . self::OUTPUT . ' false overwrite');
		$this->assertFlags($action, false, true);

		$action = $this->newAction();
		$action->generate('generate users ' . self::OUTPUT . ' soap');
		$this->assertFlags($action, true, false);
	}

	public function testGenerate_printsUsageWithoutEnoughArguments()
	{
		$action = $this->newAction();
		$action->generate('generate users');
		$this->assertStringContainsString('Usage: generate', $this->_out->flush());
	}

	// ---------------------------------------------------------------------
	// generate: class file contents
	// ---------------------------------------------------------------------

	public function testActionGenerate_writesRecordClassForTable()
	{
		$file = $this->recordFile('ArActionUserAccountRecord');
		$this->memoryConnection('CREATE TABLE user_accounts (id INTEGER PRIMARY KEY, username VARCHAR(50), score REAL)');

		$action = $this->newAction();
		$this->assertTrue($action->actionGenerate(['generate', 'user_accounts', self::ALIAS . '.records.ArActionUserAccountRecord']));

		$this->assertSame("  Writing class ArActionUserAccountRecord to file $file\n", $this->_out->flush());
		$this->assertSame(
			$this->expectedClass('ArActionUserAccountRecord', 'user_accounts', "\tpublic \$id;\n\tpublic \$username;\n\tpublic \$score;"),
			$this->readGenerated($file)
		);

		require $file;
		$class = new \ReflectionClass('ArActionUserAccountRecord');
		$this->assertTrue($class->isSubclassOf(TActiveRecord::class));
		$this->assertSame('user_accounts', $class->getConstant('TABLE'));
		$this->assertSame(['id', 'username', 'score'], array_values(array_map(fn ($p) => $p->getName(), array_filter($class->getProperties(\ReflectionProperty::IS_PUBLIC), fn ($p) => !$p->isStatic()))));
	}

	public function testActionGenerate_stampsGenerationTimeOnTwentyFourHourClock()
	{
		$file = $this->recordFile('StampRecord');
		$this->memoryConnection('CREATE TABLE stamps (id INTEGER PRIMARY KEY)');

		$before = time();
		$this->newAction()->actionGenerate(['generate', 'stamps', self::ALIAS . '.records.StampRecord']);
		$after = time();

		$this->assertMatchesRegularExpression('/ \* Auto generated by prado-cli\.php on (\d{4}-\d\d-\d\d \d\d:\d\d:\d\d)\.\n/', file_get_contents($file));
		preg_match('/on (\d{4}-\d\d-\d\d \d\d:\d\d:\d\d)\./', file_get_contents($file), $match);
		$stamp = \DateTime::createFromFormat('Y-m-d H:i:s', $match[1])->getTimestamp();
		$this->assertGreaterThanOrEqual($before, $stamp);
		$this->assertLessThanOrEqual($after, $stamp);
	}

	public function testActionGenerate_soapAddsDocblockToEachProperty()
	{
		$file = $this->recordFile('SoapUserRecord');
		$this->memoryConnection('CREATE TABLE soap_users (id INTEGER PRIMARY KEY, username VARCHAR(50))');

		$this->newAction()->actionGenerate(['generate', 'soap_users', self::ALIAS . '.records.SoapUserRecord', 'soap']);

		$props = "\n\t/**\n\t * @var integer \$id\n\t * @soapproperty\n\t */\n\tpublic \$id;\n"
			. "\n\t/**\n\t * @var string \$username\n\t * @soapproperty\n\t */\n\tpublic \$username;";
		$this->assertSame($this->expectedClass('SoapUserRecord', 'soap_users', $props), $this->readGenerated($file));
	}

	// ---------------------------------------------------------------------
	// generate: existing files
	// ---------------------------------------------------------------------

	public function testActionGenerate_skipsExistingFileWithoutOverwrite()
	{
		$file = $this->recordFile('KeptRecord');
		$this->writeFile($file, 'original');
		$this->memoryConnection('CREATE TABLE kept (id INTEGER PRIMARY KEY)');

		$this->newAction()->actionGenerate(['generate', 'kept', self::ALIAS . '.records.KeptRecord']);

		$this->assertSame("File $file already exists, skipping.", $this->errorText($this->_out->flush()));
		$this->assertSame('original', file_get_contents($file));
	}

	public function testActionGenerate_overwriteReplacesExistingFile()
	{
		$file = $this->recordFile('ReplacedRecord');
		$this->writeFile($file, 'original');
		$this->memoryConnection('CREATE TABLE replaced (id INTEGER PRIMARY KEY)');

		$this->newAction()->actionGenerate(['generate', 'replaced', self::ALIAS . '.records.ReplacedRecord', 'false', 'overwrite']);

		$this->assertSame($this->expectedClass('ReplacedRecord', 'replaced', "\tpublic \$id;"), $this->readGenerated($file));
	}

	public function testActionGenerate_acceptsExistingFilePathInsideApplication()
	{
		$file = $this->recordFile('PathRecord');
		$this->writeFile($file, 'original');
		$this->memoryConnection('CREATE TABLE by_path (id INTEGER PRIMARY KEY)');

		$this->newAction()->actionGenerate(['generate', 'by_path', $file, 'false', 'overwrite']);

		$this->assertSame("  Writing class PathRecord to file $file\n", $this->_out->flush());
		$this->assertSame($this->expectedClass('PathRecord', 'by_path', "\tpublic \$id;"), $this->readGenerated($file));
	}

	// ---------------------------------------------------------------------
	// generate: output location
	// ---------------------------------------------------------------------

	public function testActionGenerate_rejectsFilePathThatClimbsOutOfApplication()
	{
		$app = $this->appDir();
		$outside = $this->_root . DIRECTORY_SEPARATOR . 'Outside.php';
		$this->writeFile($outside, 'original');
		$this->memoryConnection('CREATE TABLE outside (id INTEGER PRIMARY KEY)');

		$climbing = $app . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Outside.php';
		$this->newAction()->actionGenerate(['generate', 'outside', $climbing, 'false', 'overwrite']);

		$this->assertStringContainsString('must be within directory ' . $app, $this->errorText($this->_out->flush()));
		$this->assertSame('original', file_get_contents($outside));
	}

	public function testActionGenerate_rejectsSiblingDirectorySharingTheApplicationPrefix()
	{
		$app = $this->appDir();
		$sibling = $this->mkdir($app . '2');
		Prado::setPathOfAlias('ArActionSibling', $sibling);
		$this->_files[] = $sibling . DIRECTORY_SEPARATOR . 'SiblingRecord.php';
		$this->memoryConnection('CREATE TABLE sibling (id INTEGER PRIMARY KEY)');

		$this->newAction()->actionGenerate(['generate', 'sibling', 'ArActionSibling.SiblingRecord']);

		$this->assertSame(
			'Output file ' . $sibling . DIRECTORY_SEPARATOR . 'SiblingRecord.php must be within directory ' . $app,
			$this->errorText($this->_out->flush())
		);
		$this->assertFileDoesNotExist($sibling . DIRECTORY_SEPARATOR . 'SiblingRecord.php');
	}

	public function testActionGenerate_acceptsNonCanonicalApplicationBasePath()
	{
		$app = $this->appDir();
		$file = $this->recordFile('CanonicalRecord');
		Prado::getApplication()->setBasePath($app . DIRECTORY_SEPARATOR . 'records' . DIRECTORY_SEPARATOR . '..');
		$this->memoryConnection('CREATE TABLE canonical (id INTEGER PRIMARY KEY)');

		$this->newAction()->actionGenerate(['generate', 'canonical', self::ALIAS . '.records.CanonicalRecord']);

		$this->assertSame($this->expectedClass('CanonicalRecord', 'canonical', "\tpublic \$id;"), $this->readGenerated($file));
	}

	public function testActionGenerate_rejectsOutputWhenApplicationDirectoryIsMissing()
	{
		$missing = $this->appDir() . DIRECTORY_SEPARATOR . 'missing';
		Prado::getApplication()->setBasePath($missing);
		$file = $this->recordFile('OrphanRecord');
		$this->memoryConnection('CREATE TABLE orphans (id INTEGER PRIMARY KEY)');

		$this->newAction()->actionGenerate(['generate', 'orphans', self::ALIAS . '.records.OrphanRecord']);

		$this->assertSame("Output file $file must be within directory $missing", $this->errorText($this->_out->flush()));
		$this->assertFileDoesNotExist($file);
	}

	public function testActionGenerate_unknownAliasNamesTheNamespace()
	{
		$app = $this->appDir();
		$this->newAction()->actionGenerate(['generate', 'users', 'NoSuchAlias.records.UserRecord']);

		$this->assertSame(
			'Output file NoSuchAlias.records.UserRecord must be within directory ' . $app,
			$this->errorText($this->_out->flush())
		);
	}

	// ---------------------------------------------------------------------
	// generate: database errors
	// ---------------------------------------------------------------------

	public function testActionGenerate_missingTableWritesError()
	{
		$file = $this->recordFile('MissingRecord');
		$this->memoryConnection();

		$this->assertTrue($this->newAction()->actionGenerate(['generate', 'missing', self::ALIAS . '.records.MissingRecord']));

		$this->assertSame('Unable to find table or view "missing" in "sqlite::memory:".', $this->errorText($this->_out->flush()));
		$this->assertFileDoesNotExist($file);
	}

	public function testActionGenerate_missingConnectionWithoutConfigWritesError()
	{
		$file = $this->recordFile('NoConnRecord');

		$this->assertTrue($this->newAction()->actionGenerate(['generate', 'users', self::ALIAS . '.records.NoConnRecord']));

		$this->assertStringStartsWith('Unable to connect to database with ConnectionID=\'\'.', $this->errorText($this->_out->flush()));
		$this->assertFileDoesNotExist($file);
	}

	public function testActionGenerate_missingConnectionNamesConfiguredConnectionID()
	{
		$file = $this->recordFile('NoConnRecord');
		$this->registerConfig('db1');

		$this->newAction()->actionGenerate(['generate', 'users', self::ALIAS . '.records.NoConnRecord']);

		$this->assertSame(
			'Unable to connect to database with ConnectionID=\'db1\'. Please check your settings in application.xml and ensure your database connection is set up first.',
			$this->errorText($this->_out->flush())
		);
		$this->assertFileDoesNotExist($file);
	}

	public function testGetActiveRecordConfig_findsApplicationModule()
	{
		$action = $this->newAction();
		$this->assertNull(PradoUnit::invoke($action, 'getActiveRecordConfig'));
		$config = $this->registerConfig('db1');
		$this->assertSame($config, PradoUnit::invoke($action, 'getActiveRecordConfig'));
	}

	// ---------------------------------------------------------------------
	// generate-all
	// ---------------------------------------------------------------------

	public function testActionGenerateAll_forwardsFlagsPrefixAndSuffixToEachTable()
	{
		$this->memoryConnection('CREATE TABLE widgets (id INTEGER PRIMARY KEY, name TEXT)');
		$action = $this->newAction();
		$this->assertTrue($action->actionGenerateAll(['generate-all', 'Application.nowhere', 'soap', 'overwrite', 'Pre', 'Suffix']));
		$this->assertFlags($action, true, true);
		$this->assertTrue(PradoUnit::getProp($action, '_soapall'));
		$this->assertTrue(PradoUnit::getProp($action, '_overwriteall'));
		$this->assertSame('Pre', PradoUnit::getProp($action, '_prefix'));
		$this->assertSame('Suffix', PradoUnit::getProp($action, '_postfix'));
		$this->assertStringContainsString('PreWidgetsSuffix', $this->_out->flush());

		$action = $this->newAction();
		$action->actionGenerateAll(['generate-all', 'Application.nowhere', 'false', 'true']);
		$this->assertFlags($action, false, true);
		$this->assertSame('', PradoUnit::getProp($action, '_prefix'));
		$this->assertSame('', PradoUnit::getProp($action, '_postfix'));
		$this->assertStringContainsString('Widgets.php', $this->_out->flush());

		$action = $this->newAction();
		$action->actionGenerateAll(['generate-all', 'Application.nowhere']);
		$this->assertFlags($action, false, false);
	}

	public function testActionGenerateAll_writesClassForEveryTableOfInMemoryDatabase()
	{
		$widgets = $this->recordFile('PreWidgetsRecord');
		$gadgets = $this->recordFile('PreGadgetsRecord');
		$this->recordFile('PreSqlite_sequenceRecord');
		$conn = $this->memoryConnection(
			'CREATE TABLE widgets (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)',
			'CREATE TABLE gadgets (gadget_id INTEGER PRIMARY KEY, label TEXT, price REAL)',
			'CREATE INDEX gadgets_label ON gadgets (label)'
		);

		$action = $this->newAction();
		$this->assertTrue($action->actionGenerateAll(['generate-all', self::ALIAS . '.records', 'false', 'false', 'Pre', 'Record']));

		$out = $this->_out->flush();
		$this->assertStringContainsString("Writing class PreWidgetsRecord to file $widgets", $out);
		$this->assertStringContainsString("Writing class PreGadgetsRecord to file $gadgets", $out);
		$this->assertSame('', $this->errorText($out));
		$this->assertSame($this->expectedClass('PreWidgetsRecord', 'widgets', "\tpublic \$id;\n\tpublic \$name;"), $this->readGenerated($widgets));
		$this->assertSame($this->expectedClass('PreGadgetsRecord', 'gadgets', "\tpublic \$gadget_id;\n\tpublic \$label;\n\tpublic \$price;"), $this->readGenerated($gadgets));
		$this->assertCount(2, glob(dirname($widgets) . DIRECTORY_SEPARATOR . '*.php'));
		$this->assertTrue($conn->getActive(), 'an open connection stays open');
	}

	public function testActionGenerateAll_turnsTableNameIntoValidClassName()
	{
		$file = $this->recordFile('Line_itemsRecord');
		$this->memoryConnection('CREATE TABLE "line-items" (id INTEGER PRIMARY KEY)');

		$this->newAction()->actionGenerateAll(['generate-all', self::ALIAS . '.records', 'false', 'false', '', 'Record']);

		$this->assertSame('', $this->errorText($this->_out->flush()));
		$this->assertSame($this->expectedClass('Line_itemsRecord', 'line-items', "\tpublic \$id;"), $this->readGenerated($file));
	}

	public function testActionGenerateAll_skipsExistingFilesUnlessOverwrite()
	{
		$file = $this->recordFile('Things');
		$this->writeFile($file, 'original');
		$this->memoryConnection('CREATE TABLE things (id INTEGER PRIMARY KEY)');

		$this->newAction()->actionGenerateAll(['generate-all', self::ALIAS . '.records', 'soap']);
		$this->assertSame("File $file already exists, skipping.", $this->errorText($this->_out->flush()));
		$this->assertSame('original', file_get_contents($file));

		$this->newAction()->actionGenerateAll(['generate-all', self::ALIAS . '.records', 'soap', 'overwrite']);
		$this->assertStringContainsString(' * @soapproperty', file_get_contents($file));
		$this->assertStringContainsString('class Things extends TActiveRecord', file_get_contents($file));
	}

	public static function provideTableListSql(): array
	{
		$mysql = 'SHOW TABLES';
		$sqlite = "SELECT DISTINCT tbl_name FROM sqlite_master WHERE tbl_name<>'sqlite_sequence'";
		$mssql = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'";
		$firebird = 'SELECT TRIM(RDB$RELATION_NAME) AS tbl_name FROM RDB$RELATIONS WHERE RDB$SYSTEM_FLAG = 0 AND RDB$VIEW_BLR IS NULL ORDER BY RDB$RELATION_NAME';
		return [
			'mysqli' => [TDbDriver::EXTENSION_MYSQLI, $mysql],
			'mysql' => [TDbDriver::DRIVER_MYSQL, $mysql],
			'sqlite' => [TDbDriver::DRIVER_SQLITE, $sqlite],
			'sqlite2' => [TDbDriver::DRIVER_SQLITE2, $sqlite],
			'pgsql' => [TDbDriver::DRIVER_PGSQL, "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE'"],
			'mssql' => [TDbDriver::EXTENSION_MSSQL, $mssql],
			'sqlsrv' => [TDbDriver::DRIVER_SQLSRV, $mssql],
			'dblib' => [TDbDriver::DRIVER_DBLIB, $mssql],
			'oci' => [TDbDriver::DRIVER_OCI, 'SELECT table_name FROM user_tables'],
			'ibm' => [TDbDriver::DRIVER_IBM, "SELECT TABNAME FROM SYSCAT.TABLES WHERE TABSCHEMA = CURRENT SCHEMA AND TYPE = 'T' ORDER BY TABNAME"],
			'firebird' => [TDbDriver::DRIVER_FIREBIRD, $firebird],
			'interbase' => [TDbDriver::DRIVER_INTERBASE, $firebird],
		];
	}

	/**
	 * @dataProvider provideTableListSql
	 */
	public function testActionGenerateAll_listsTablesWithDriverQuery(string $driver, string $sql)
	{
		$conn = $this->useConnection(new TActiveRecordActionTestConnection($driver));

		$this->assertTrue($this->newAction()->actionGenerateAll(['generate-all', self::OUTPUT]));

		$this->assertSame([$sql], $conn->commands);
		$this->assertSame('', $this->_out->flush());
		$this->assertFalse($conn->getActive(), 'a closed connection is closed again');
	}

	public function testActionGenerateAll_unsupportedDriverWritesError()
	{
		$conn = $this->useConnection(new TActiveRecordActionTestConnection(TDbDriver::DRIVER_ODBC));

		$this->assertTrue($this->newAction()->actionGenerateAll(['generate-all', self::OUTPUT]));

		$this->assertSame('Sorry, generateAll is not implemented for odbc.', $this->errorText($this->_out->flush()));
		$this->assertSame([], $conn->commands);
		$this->assertFalse($conn->getActive());
	}

	public function testActionGenerateAll_missingConnectionWritesError()
	{
		$this->registerConfig('db1');

		$this->assertTrue($this->newAction()->actionGenerateAll(['generate-all', self::OUTPUT]));

		$this->assertSame(
			'Unable to connect to database with ConnectionID=\'db1\'. Please check your settings in application.xml and ensure your database connection is set up first.',
			$this->errorText($this->_out->flush())
		);
	}
}
