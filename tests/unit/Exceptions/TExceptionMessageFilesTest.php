<?php

/**
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Exceptions;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Prado\Data\ActiveRecord\Exceptions\TActiveRecordException;
use Prado\Data\SqlMap\DataMapper\TSqlMapException;
use Prado\Exceptions\TException;
use Prado\Prado;
use Prado\Test\Unit\PradoUnit;

/**
 * Tests the ICU MessageFormat formatting of {@see TException} file messages, back-ported from
 * 4.4, with the 4.3 message file order: the files of `addMessageFile()`, then the framework file
 * of the client language. Also lints the framework message files.
 */
class TExceptionMessageFilesTest extends \PHPUnit\Framework\TestCase
{
	private array $_static;
	private array $_tempFiles = [];

	protected function setUp(): void
	{
		$this->_static = PradoUnit::snapshotStatic(TException::class);
	}

	protected function tearDown(): void
	{
		PradoUnit::restoreStatic(TException::class, $this->_static);
		foreach ($this->_tempFiles as $file) {
			if (is_file($file)) {
				unlink($file);
			}
		}
	}

	/**
	 * @return string the framework message directory.
	 */
	protected static function messagesDir(): string
	{
		return Prado::getFrameworkPath() . '/Exceptions/messages';
	}

	/**
	 * Parses a message file as TException does.
	 * @param string $file the message file.
	 * @return array{array<string, string>, string[]} entries in file order, and the keys defined more than once.
	 */
	public static function parse(string $file): array
	{
		$entries = [];
		$duplicates = [];
		foreach (file($file) ?: [] as $line) {
			$line = trim($line);
			if ($line === '' || strncmp($line, '#', 1) === 0 || strncmp($line, ';', 1) === 0 || strpos($line, '=') === false) {
				continue;
			}
			[$key, $value] = explode('=', $line, 2);
			$key = trim($key);
			if (isset($entries[$key])) {
				$duplicates[] = $key;
			}
			$entries[$key] = trim($value);
		}
		return [$entries, $duplicates];
	}

	/**
	 * Writes a message file in the temporary directory.
	 * @param string $name the file name.
	 * @param string $content the file content.
	 * @return string the file path.
	 */
	protected function writeMessageFile(string $name, string $content): string
	{
		$file = sys_get_temp_dir() . '/' . uniqid('prado_msg_', true) . '_' . $name;
		file_put_contents($file, $content);
		$this->_tempFiles[] = $file;
		return $file;
	}

	// ── Lookup ───────────────────────────────────────────────────────────────

	public function testFrameworkMessageIsFormatted(): void
	{
		$this->assertSame("Component property 'A.B' is not defined.", (new TException('component_property_undefined', 'A', 'B'))->getMessage());
	}

	public function testErrorMessageFilesAreTheAddedFilesThenTheFrameworkFile(): void
	{
		$added = $this->writeMessageFile('order.txt', "order_key = Added\n");
		TException::addMessageFile($added);
		$e = new TException('order_key');
		$this->assertSame([$added, self::messagesDir() . '/messages.txt'], PradoUnit::invoke($e, 'getErrorMessageFiles'));
	}

	public function testFrameworkFileOverridesAnAddedFile(): void
	{
		TException::addMessageFile($this->writeMessageFile('override.txt', "component_property_undefined = Custom\n"));
		$this->assertSame("Component property 'A.B' is not defined.", (new TException('component_property_undefined', 'A', 'B'))->getMessage());
	}

	public function testLaterAddedFileOverridesEarlierAddedFile(): void
	{
		TException::addMessageFile($this->writeMessageFile('first.txt', "app_key = First\n"));
		TException::addMessageFile($this->writeMessageFile('second.txt', "app_key = Second\n"));
		$this->assertSame('Second', (new TException('app_key'))->getMessage());
	}

	public function testUnreadableFileIsCachedAsEmpty(): void
	{
		$missing = sys_get_temp_dir() . '/' . uniqid('prado_missing_', true) . '.txt';
		TException::addMessageFile($missing);
		$this->assertSame('missing_key', (new TException('missing_key'))->getMessage());
		$this->assertSame([], PradoUnit::snapshotStatic(TException::class, ['_messageCache'])['_messageCache'][$missing]);
	}

	public function testUndefinedKeyIsTheMessage(): void
	{
		$this->assertSame('no_such_prado_key', (new TException('no_such_prado_key', 'A'))->getMessage());
	}

	public function testEmptyErrorCodeIsAnEmptyMessage(): void
	{
		$this->assertSame('', (new TException(''))->getMessage());
	}

	public function testFindErrorMessageFileIsTheFileWithTheHighestPrecedence(): void
	{
		$added = $this->writeMessageFile('find.txt', "component_property_undefined = Custom\nfind_key = Found\n");
		TException::addMessageFile($added);
		$e = new TException('find_key');
		$this->assertSame(self::messagesDir() . '/messages.txt', PradoUnit::invoke($e, 'findErrorMessageFile', 'component_property_undefined'));
		$this->assertSame($added, PradoUnit::invoke($e, 'findErrorMessageFile', 'find_key'));
		$this->assertNull(PradoUnit::invoke($e, 'findErrorMessageFile', 'no_such_prado_key'));
		$this->assertNull(PradoUnit::invoke($e, 'findErrorMessageFile', ''));
		$this->assertNull(PradoUnit::invoke($e, 'findErrorMessageFile', null));
	}

	// ── ICU MessageFormat ────────────────────────────────────────────────────

	public function testFileMessageIsAnIcuPattern(): void
	{
		TException::addMessageFile($this->writeMessageFile('icu.txt', "icu_key = {0, plural, one{# item} other{# items}} in ''{1}''.\n"));
		$this->assertSame("1 item in 'A'.", (new TException('icu_key', 1, 'A'))->getMessage());
		$this->assertSame("1,500 items in 'A'.", (new TException('icu_key', 1500, 'A'))->getMessage());
	}

	public function testFileMessageIsFormattedInTheLanguageOfTheFile(): void
	{
		$pattern = "lang_key = {0, plural, one{one} other{other}}\n";
		TException::addMessageFile($this->writeMessageFile('lang.txt', $pattern));
		$this->assertSame('other', (new TException('lang_key', 0))->getMessage());
		TException::addMessageFile($this->writeMessageFile('lang-fr.txt', $pattern));
		$this->assertSame('one', (new TException('lang_key', 0))->getMessage());
	}

	public function testArgumentsAreValuesOfThePattern(): void
	{
		TException::addMessageFile($this->writeMessageFile('values.txt', "values_key = Value {0}.\n"));
		$this->assertSame("Value it's {1} ''{0}''.", (new TException('values_key', "it's {1} ''{0}''"))->getMessage());
	}

	public function testUnparsablePatternReplacesThePlaceholders(): void
	{
		TException::addMessageFile($this->writeMessageFile('broken.txt', "broken_key = Broken {0} {1\n"));
		$this->assertSame('Broken A {1', (new TException('broken_key', 'A', 'B'))->getMessage());
	}

	public function testUnparsablePatternWithIntlExceptionsReplacesThePlaceholders(): void
	{
		// With intl.use_exceptions, MessageFormatter throws for a pattern it cannot parse.
		$previous = ini_set('intl.use_exceptions', '1');
		try {
			TException::addMessageFile($this->writeMessageFile('throws.txt', "throws_key = Broken {0} {1\n"));
			$this->assertSame('Broken A {1', (new TException('throws_key', 'A', 'B'))->getMessage());
		} finally {
			ini_set('intl.use_exceptions', (string) $previous);
		}
	}

	public function testUndefinedKeyReplacesThePlaceholders(): void
	{
		$this->assertSame("Raw 'A' text", (new TException("Raw '{0}' text", 'A'))->getMessage());
	}

	public function testIntegerErrorCodeFormatsTheMessage(): void
	{
		$e = new TException(42, 'component_property_undefined', 'A', 'B');
		$this->assertSame(42, $e->getCode());
		$this->assertSame("Component property 'A.B' is not defined.", $e->getMessage());
	}

	// ── Patterns written before 4.3.3 ────────────────────────────────────────

	public function testPatternQuotingAPlaceholderInSingleApostrophesReplacesThePlaceholders(): void
	{
		TException::addMessageFile($this->writeMessageFile('plain.txt', "plain_key = Plain '{0}' and {1}.\n"));
		$this->assertSame("Plain 'A' and B.", (new TException('plain_key', 'A', 'B'))->getMessage());
	}

	/**
	 * @return array<string, array{string, bool}> a pattern and whether it is a plain pattern.
	 */
	public static function plainPatternProvider(): array
	{
		return [
			'quoted placeholder' => ["Plain '{0}'.", true],
			'second quoted placeholder' => ["{0} and '{12}'", true],
			'ICU quoted placeholder' => ["ICU ''{0}''.", false],
			'no quotes' => ['Plain {0}.', false],
			'apostrophe in a word' => ["Can't use {0}.", false],
			'quoted literal braces' => ["Literal '{}' and {0}.", false],
			'no placeholder' => ["Just 'text'.", false],
		];
	}

	#[DataProvider('plainPatternProvider')]
	public function testIsPlainMessagePattern(string $pattern, bool $plain): void
	{
		$this->assertSame($plain, PradoUnit::invoke(TException::class, 'isPlainMessagePattern', $pattern));
	}

	// ── Message file language ────────────────────────────────────────────────

	public function testMessageFileLanguage(): void
	{
		$e = new TException('component_property_undefined', 'A', 'B');
		$this->assertSame('en', PradoUnit::invoke($e, 'getMessageFileLanguage', self::messagesDir() . '/messages.txt'));
		$this->assertSame('fr', PradoUnit::invoke($e, 'getMessageFileLanguage', '/app/messages-fr.txt'));
		$this->assertSame('pt', PradoUnit::invoke($e, 'getMessageFileLanguage', '/app/messages-pt_BR.txt'));
		$this->assertSame('en', PradoUnit::invoke($e, 'getMessageFileLanguage', '/my-app/app-messages.txt'));
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState(false)]
	public function testFrenchClientReceivesTheFrenchFrameworkMessage(): void
	{
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr-FR,fr;q=0.9';
		$this->assertSame('fr', Prado::getPreferredLanguage());
		$e = new TException('pageservice_page_unknown', 'Home');
		$this->assertSame(self::messagesDir() . '/messages-fr.txt', PradoUnit::invoke($e, 'getErrorMessageFile'));
		$this->assertSame("La page 'Home' n'a pas été trouvée", $e->getMessage());
	}

	// ── Data exceptions ──────────────────────────────────────────────────────

	public function testActiveRecordMessageIsFormatted(): void
	{
		$e = new TActiveRecordException('ar_primary_key_is_scalar', 'users', 'id', 'x');
		$this->assertSame('ar_primary_key_is_scalar', $e->getErrorCode());
		$this->assertSame("Primary key 'id' in table 'users' is NOT a composite key, invalid value 'x' used.", $e->getMessage());
	}

	public function testSqlMapMessageIsFormattedWithItsErrorCode(): void
	{
		$e = new TSqlMapException('sqlmap_contains_no_statement', 'getUsers');
		$this->assertSame("Unable to find SQLMap statement 'getUsers'.", $e->getMessage());
		$this->assertSame('sqlmap_contains_no_statement', $e->getErrorCode());
		$this->assertSame(0, $e->getCode());
	}

	public function testSqlMapMessageExpandsAnXmlNodeArgument(): void
	{
		$node = new \SimpleXMLElement('<select id="getUsers" resultClass="User"/>');
		$e = new TSqlMapException('sqlmap_contains_no_statement', $node);
		$this->assertSame('Unable to find SQLMap statement \'<select id="getUsers" resultClass="User">\'.', $e->getMessage());
	}

	public function testSqlMapIntegerErrorCodeAndPreviousException(): void
	{
		$previous = new \RuntimeException('cause');
		$e = new TSqlMapException(7, 'sqlmap_contains_no_statement', 'getUsers', $previous);
		$this->assertSame(7, $e->getCode());
		$this->assertSame($previous, $e->getPrevious());
		$this->assertSame("Unable to find SQLMap statement 'getUsers'.", $e->getMessage());
	}

	// ── Message file lint ────────────────────────────────────────────────────

	/**
	 * @return array<string, array{string}> the framework message files.
	 */
	public static function messageFileProvider(): array
	{
		$framework = dirname(__DIR__, 3) . '/framework';
		$sets = [];
		foreach ([...glob($framework . '/Exceptions/messages/messages*.txt') ?: [], $framework . '/Data/ActiveRecord/Exceptions/messages.txt', $framework . '/Data/SqlMap/DataMapper/messages.txt'] as $file) {
			$sets[substr($file, strlen($framework) + 1)] = [$file];
		}
		return $sets;
	}

	/**
	 * @param string $message the message pattern.
	 * @return int[] the argument numbers the pattern refers to, sorted.
	 */
	protected static function argumentNumbers(string $message): array
	{
		preg_match_all('/\{\s*(\d+)\s*[,}]/', $message, $matches);
		$numbers = array_map('intval', array_unique($matches[1]));
		sort($numbers);
		return $numbers;
	}

	#[DataProvider('messageFileProvider')]
	public function testMessageFileEntriesAreIcuPatternsThatRenderEveryArgument(string $file): void
	{
		[$entries] = self::parse($file);
		$this->assertNotEmpty($entries);
		$name = basename($file);
		$lang = PradoUnit::invoke(new TException('lint'), 'getMessageFileLanguage', $file);
		foreach ($entries as $key => $value) {
			$this->assertFalse(PradoUnit::invoke(TException::class, 'isPlainMessagePattern', $value), "'$key' of $name quotes a placeholder in single apostrophes; double them: ''{0}''");
			$numbers = self::argumentNumbers($value);
			$args = $numbers === [] ? [] : array_map(fn ($i) => "\u{AB}$i\u{BB}", range(0, max($numbers)));
			$formatted = \MessageFormatter::formatMessage($lang, $value, $args);
			$this->assertNotFalse($formatted, "'$key' of $name is not an ICU MessageFormat pattern");
			$this->assertDoesNotMatchRegularExpression('/\{\d/', $formatted, "'$key' of $name quotes a placeholder; double the apostrophe: ''{0}''");
			foreach ($numbers as $i) {
				$this->assertStringContainsString("\u{AB}$i\u{BB}", $formatted, "'$key' of $name does not render argument $i");
			}
		}
	}

	/**
	 * @return array<string, array{string}> the language codes of the framework message files.
	 */
	public static function languageProvider(): array
	{
		$sets = [];
		foreach (glob(dirname(__DIR__, 3) . '/framework/Exceptions/messages/messages-*.txt') ?: [] as $file) {
			$lang = substr(basename($file, '.txt'), strlen('messages-'));
			$sets[$lang] = [$lang];
		}
		return $sets;
	}

	#[DataProvider('languageProvider')]
	public function testLanguageFileEntriesUseOnlyTheEnglishArguments(string $lang): void
	{
		[$english] = self::parse(self::messagesDir() . '/messages.txt');
		[$entries] = self::parse(self::messagesDir() . "/messages-$lang.txt");
		foreach ($entries as $key => $value) {
			if (isset($english[$key])) {
				$this->assertSame([], array_values(array_diff(self::argumentNumbers($value), self::argumentNumbers($english[$key]))), "'$key' of messages-$lang.txt uses an argument the English message does not have");
			}
		}
	}
}
