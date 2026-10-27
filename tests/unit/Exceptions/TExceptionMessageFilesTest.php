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
use Prado\Exceptions\TException;
use Prado\Exceptions\THttpException;
use Prado\Prado;
use Prado\Test\Unit\PradoUnit;

/**
 * Tests the message file lookup of {@see TException} and {@see THttpException}: the English
 * master, the precedence of files added with `addMessageFile()`, the `-<lang>` variant
 * resolution, and the client-language files of the visitor-facing `THttpException` keys.
 * Also lints `messages.txt` and `messages-<lang>.txt`.
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

	// ── TException lookup ────────────────────────────────────────────────────

	public function testErrorMessageFileIsTheEnglishMaster(): void
	{
		$e = new TException('component_property_undefined', 'A', 'B');
		$this->assertSame(self::messagesDir() . '/messages.txt', PradoUnit::invoke($e, 'getErrorMessageFile'));
		$this->assertSame("Component property 'A.B' is not defined.", $e->getMessage());
	}

	public function testErrorMessageFilesAreTheMasterThenTheAddedFiles(): void
	{
		$added = $this->writeMessageFile('added.txt', "some_key = Some value\n");
		TException::addMessageFile($added);
		$e = new TException('some_key');
		$this->assertSame([self::messagesDir() . '/messages.txt', $added], PradoUnit::invoke($e, 'getErrorMessageFiles'));
		$this->assertSame('Some value', $e->getMessage());
	}

	public function testAddedFileOverridesTheFrameworkMessage(): void
	{
		TException::addMessageFile($this->writeMessageFile('override.txt', "component_property_undefined = Custom {1} of {0}\n"));
		$this->assertSame('Custom B of A', (new TException('component_property_undefined', 'A', 'B'))->getMessage());
	}

	public function testLaterAddedFileOverridesEarlierAddedFile(): void
	{
		TException::addMessageFile($this->writeMessageFile('first.txt', "shared_key = first\n"));
		TException::addMessageFile($this->writeMessageFile('second.txt', "shared_key = second\n"));
		$this->assertSame('second', (new TException('shared_key'))->getMessage());
	}

	public function testKeyMissingFromAddedFileKeepsTheFrameworkMessage(): void
	{
		TException::addMessageFile($this->writeMessageFile('other.txt', "other_key = other\n"));
		$this->assertSame("Component property 'A.B' is not defined.", (new TException('component_property_undefined', 'A', 'B'))->getMessage());
	}

	public function testUnreadableFileIsCachedAsEmpty(): void
	{
		$missing = sys_get_temp_dir() . '/' . uniqid('prado_missing_', true) . '.txt';
		TException::addMessageFile($missing);
		$this->assertSame("Component property 'A.B' is not defined.", (new TException('component_property_undefined', 'A', 'B'))->getMessage());
		$this->assertSame([], PradoUnit::getStaticProp(TException::class, '_messageCache')[$missing]);
	}

	public function testUndefinedKeyIsTheMessage(): void
	{
		$this->assertSame('no_such_prado_key', (new TException('no_such_prado_key', 'A'))->getMessage());
	}

	// ── Client language ──────────────────────────────────────────────────────

	#[RunInSeparateProcess]
	#[PreserveGlobalState(false)]
	public function testFrenchClientReceivesEnglishDeveloperMessage(): void
	{
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr-FR,fr;q=0.9';
		$this->assertSame('fr', Prado::getPreferredLanguage());
		$e = new TException('component_property_undefined', 'A', 'B');
		$this->assertSame("Component property 'A.B' is not defined.", $e->getMessage());
		$this->assertSame([self::messagesDir() . '/messages.txt'], PradoUnit::invoke($e, 'getErrorMessageFiles'));
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState(false)]
	public function testFrenchClientReceivesFrenchHttpExceptionMessage(): void
	{
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr-FR,fr;q=0.9';
		$e = new THttpException(404, 'pageservice_page_unknown', 'Home');
		$this->assertSame("La page 'Home' n'a pas été trouvée.", $e->getMessage());
		$this->assertSame(
			[self::messagesDir() . '/messages.txt', self::messagesDir() . '/messages-fr.txt'],
			PradoUnit::invoke($e, 'getErrorMessageFiles')
		);
		// A developer key through THttpException falls back to English.
		$this->assertSame("Component property 'A.B' is not defined.", (new THttpException(500, 'component_property_undefined', 'A', 'B'))->getMessage());
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState(false)]
	public function testUnsupportedClientLanguageReceivesEnglishHttpExceptionMessage(): void
	{
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'xx';
		$e = new THttpException(404, 'pageservice_page_unknown', 'Home');
		$this->assertSame("Page 'Home' Not Found", $e->getMessage());
		$this->assertSame([self::messagesDir() . '/messages.txt'], PradoUnit::invoke($e, 'getErrorMessageFiles'));
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState(false)]
	public function testAddedFileResolvesToTheClientLanguageVariant(): void
	{
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr';
		$dir = sys_get_temp_dir() . '/' . uniqid('prado_variant_', true);
		mkdir($dir);
		file_put_contents($dir . '/app.txt', "app_key = English\n");
		file_put_contents($dir . '/app-fr.txt', "app_key = Français\n");
		try {
			TException::addMessageFile($dir . '/app.txt');
			$this->assertSame('Français', (new TException('app_key'))->getMessage());
		} finally {
			unlink($dir . '/app.txt');
			unlink($dir . '/app-fr.txt');
			rmdir($dir);
		}
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState(false)]
	public function testAddedFileWithoutVariantIsUsedAsIs(): void
	{
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr';
		$file = $this->writeMessageFile('plain.txt', "app_key = English only\n");
		TException::addMessageFile($file);
		$this->assertSame('English only', (new TException('app_key'))->getMessage());
	}

	// ── Message file lint ────────────────────────────────────────────────────

	public function testEnglishMasterHasNoDuplicateKeys(): void
	{
		[, $duplicates] = self::parse(self::messagesDir() . '/messages.txt');
		$this->assertSame([], $duplicates);
	}

	/**
	 * @return array<string, array{string}> the language codes of the framework message files.
	 */
	public static function languageProvider(): array
	{
		$sets = [];
		foreach (glob(self::messagesDir() . '/messages-*.txt') ?: [] as $file) {
			$lang = substr(basename($file, '.txt'), strlen('messages-'));
			$sets[$lang] = [$lang];
		}
		return $sets;
	}

	/**
	 * @return string[] the message keys thrown as THttpException in the framework source, sorted.
	 */
	public static function httpExceptionKeys(): array
	{
		$keys = [];
		$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(Prado::getFrameworkPath(), \FilesystemIterator::SKIP_DOTS));
		foreach ($iterator as $file) {
			if ($file->getExtension() === 'php' && preg_match_all("/new THttpException\(\s*\d+\s*,\s*'([a-z0-9_]+)'/", file_get_contents($file->getPathname()), $matches)) {
				$keys = array_merge($keys, $matches[1]);
			}
		}
		$keys = array_unique($keys);
		sort($keys);
		return $keys;
	}

	public function testFrameworkThrowsHttpExceptionKeys(): void
	{
		$this->assertContains('pageservice_page_unknown', self::httpExceptionKeys());
		$this->assertContains('application_unavailable', self::httpExceptionKeys());
	}

	#[DataProvider('languageProvider')]
	public function testLanguageFileHasExactlyTheHttpExceptionKeys(string $lang): void
	{
		[$entries, $duplicates] = self::parse(self::messagesDir() . "/messages-$lang.txt");
		$this->assertSame([], $duplicates, "messages-$lang.txt defines keys more than once");
		$keys = array_keys($entries);
		sort($keys);
		$this->assertSame(self::httpExceptionKeys(), $keys, "messages-$lang.txt must define every key thrown as THttpException and nothing else");
	}

	#[DataProvider('languageProvider')]
	public function testLanguageFileEntriesAreTranslatedWithTheEnglishPlaceholders(string $lang): void
	{
		[$english] = self::parse(self::messagesDir() . '/messages.txt');
		[$entries] = self::parse(self::messagesDir() . "/messages-$lang.txt");
		foreach ($entries as $key => $value) {
			$this->assertArrayHasKey($key, $english, "'$key' of messages-$lang.txt is not in messages.txt");
			$this->assertNotSame($english[$key], $value, "'$key' of messages-$lang.txt is untranslated");
			preg_match_all('/\{\d+\}/', $english[$key], $expected);
			preg_match_all('/\{\d+\}/', $value, $actual);
			sort($expected[0]);
			sort($actual[0]);
			$this->assertSame(array_unique($expected[0]), array_unique($actual[0]), "placeholders of '$key' differ in messages-$lang.txt");
		}
	}
}
