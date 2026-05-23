<?php

/**
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Exceptions\TErrorHandler;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\THttpException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Exceptions\TPhpErrorException;
use Prado\Prado;
use Prado\TApplicationMode;

/**
 * Global class whose short name matches T[A-Z]\w+ so that
 * getErrorClassNameSpace() can resolve it via ReflectionClass without a namespace.
 */
if (!class_exists('TErrorHandlerTestGlobalClass', false)) {
	class TErrorHandlerTestGlobalClass
	{
	}
}

/**
 * Exposes all protected methods of TErrorHandler for unit testing.
 * All wrappers call the protected methods directly — no reflection needed since
 * the methods were promoted to protected in 4.3.3.
 *
 * Overrides errorLog(), headersSent(), and header() to capture calls and
 * suppress side-effects (stderr noise, real header emission) during tests.
 */
class TErrorHandlerAccessor extends TErrorHandler
{
	// ── Captured / fake state for UAP-SE helper overrides ────────────────────

	/** @var string|null the last message passed to errorLog(), or null if not called */
	public ?string $capturedErrorLog = null;

	/** @var bool|null when non-null, headersSent() returns this value instead of headers_sent() */
	public ?bool $fakeHeadersSent = null;

	/** @var string[] all values passed to header() calls during the test */
	public array $capturedHeaders = [];

	/** @var bool tracks whether restoreErrorHandler() was called during the test */
	public bool $restoreErrorHandlerCalled = false;

	/** @var bool tracks whether restoreExceptionHandler() was called during the test */
	public bool $restoreExceptionHandlerCalled = false;

	/** @var string|null when non-null, phpSapiName() returns this value instead of php_sapi_name() */
	public ?string $fakePhpSapiName = null;

	protected function errorLog(string $message): void
	{
		$this->capturedErrorLog = $message;
	}

	protected function headersSent(): bool
	{
		return $this->fakeHeadersSent ?? parent::headersSent();
	}

	protected function header(string $header, bool $replace = true, int $response_code = 0): void
	{
		$this->capturedHeaders[] = $header;
	}

	protected function restoreErrorHandler(): void
	{
		$this->restoreErrorHandlerCalled = true;
	}

	protected function restoreExceptionHandler(): void
	{
		$this->restoreExceptionHandlerCalled = true;
	}

	protected function phpSapiName(): string
	{
		return $this->fakePhpSapiName ?? parent::phpSapiName();
	}

	/**
	 * Returns null so that handleRecursiveError() always takes the
	 * `$this->header()` branch rather than `$response->appendHeader()`.
	 * This prevents the live THttpResponse (which opens output buffers
	 * for compression) from being touched during recursive-error tests,
	 * and ensures capturedHeaders is populated by the overridden header().
	 */
	public function getResponse(): ?\Prado\Web\THttpResponse
	{
		return null;
	}

	// ── Public wrappers for testing the base helper implementations ──────────

	public function pubErrorLog(string $message): void
	{
		// Call the real (parent) implementation — used to verify it delegates to error_log().
		parent::errorLog($message);
	}

	public function pubHeadersSent(): bool
	{
		return parent::headersSent();
	}

	public function pubHeader(string $header, bool $replace = true, int $response_code = 0): void
	{
		// In CLI tests headers_sent() is always false, so header() would fire.
		// Call via parent to exercise the real path without the override.
		parent::header($header, $replace, $response_code);
	}

	public function pubRestoreErrorHandler(): void
	{
		parent::restoreErrorHandler();
	}

	public function pubRestoreExceptionHandler(): void
	{
		parent::restoreExceptionHandler();
	}

	public function pubPhpSapiName(): string
	{
		return parent::phpSapiName();
	}

	public function pubServerGlobal(string $key): mixed
	{
		return parent::serverGlobal($key);
	}

	// ── Compatibility shims used by template-path tests ──────────────────────

	/** Writes directly to the backing field via the new Direct setter. */
	public function setRawTemplatePath(?string $path): void
	{
		$this->setErrorTemplatePathDirect($path);
	}

	/** Reads the raw backing field via the new Direct getter. */
	public function getRawTemplatePath(): ?string
	{
		return $this->getErrorTemplatePathDirect();
	}

	// ── New Direct accessor wrappers (4.3.3) ─────────────────────────────────

	public function pubGetPrivatePathReplacementsDirect(): ?array
	{
		return $this->getPrivatePathReplacementsDirect();
	}

	public function pubSetPrivatePathReplacementsDirect(?array $value): void
	{
		$this->setPrivatePathReplacementsDirect($value);
	}

	public function pubGetPrivatePathReplacements(): array
	{
		return $this->getPrivatePathReplacements();
	}

	public function pubGetErrorTemplatePathDirect(): ?string
	{
		return $this->getErrorTemplatePathDirect();
	}

	public function pubSetErrorTemplatePathDirect(?string $value): void
	{
		$this->setErrorTemplatePathDirect($value);
	}

	public function pubSetAppErrorHandler(): void
	{
		$this->setAppErrorHandler();
	}

	// ── Protected method wrappers (direct calls, no reflection) ──────────────

	public function pubGetDefaultErrorTemplatePath(): string
	{
		return $this->getDefaultErrorTemplatePath();
	}

	public function pubGetSourceCode($lines, int $errorLine): string
	{
		return $this->getSourceCode($lines, $errorLine);
	}

	public function pubGetErrorTemplate(int $statusCode, \Exception $exception): string
	{
		return $this->getErrorTemplate($statusCode, $exception);
	}

	public function pubGetExceptionTemplate(\Exception $exception): string
	{
		return $this->getExceptionTemplate($exception);
	}

	public function pubGetErrorClassNameSpace(string $message): ?array
	{
		return $this->getErrorClassNameSpace($message);
	}

	public function pubAddLink(string $message): string
	{
		return $this->addLink($message);
	}

	public function pubGetPropertyAccessTrace(array $trace, string $pattern): ?array
	{
		return $this->getPropertyAccessTrace($trace, $pattern);
	}

	public function pubHidePrivatePathParts(string $value): string
	{
		return $this->hidePrivatePathParts($value);
	}

	public function pubHideSecurityRelated(string $value, ?\Exception $exception = null): string
	{
		return $this->hideSecurityRelated($value, $exception);
	}

	public function pubGetExactTrace(\Exception $exception): ?array
	{
		return $this->getExactTrace($exception);
	}

	public function pubGetExactTraceAsString(\Exception $exception): string
	{
		return $this->getExactTraceAsString($exception);
	}

	public function pubHandleRecursiveError(\Exception $exception): void
	{
		$this->handleRecursiveError($exception);
	}

	public function pubDisplayException(\Exception $exception): void
	{
		parent::displayException($exception);
	}

	// ── Captured dispatch state — set by no-op overrides used in handleError() tests

	public ?int $capturedExternalErrorStatusCode = null;
	public bool $displayExceptionCalled = false;
	public bool $handleExternalErrorEnabled = false; // set true to run real implementation
	public bool $displayExceptionEnabled = false;    // set true to run real implementation

	protected function handleExternalError($statusCode, $exception): void
	{
		$this->capturedExternalErrorStatusCode = $statusCode;
		if ($this->handleExternalErrorEnabled) {
			parent::handleExternalError($statusCode, $exception);
		}
	}

	protected function displayException($exception): void
	{
		$this->displayExceptionCalled = true;
		if ($this->displayExceptionEnabled) {
			parent::displayException($exception);
		}
	}

	public function pubHandleError(mixed $sender, \Throwable $param): void
	{
		$this->handleError($sender, $param);
	}

	public function pubGetIsHandled(): bool
	{
		return $this->getIsHandled();
	}

	public function pubSetIsHandled(bool $value): void
	{
		$this->setIsHandled($value);
	}

	public function resetHandling(): void
	{
		// Resets the instance handling flag so handleError() can be called again in tests.
		$this->setIsHandled(false);
	}
}

/**
 * Comprehensive tests for {@see TErrorHandler}: constants, module init,
 * Direct accessor methods, template path lazy-init and validation, source code
 * formatting, template priority fallbacks, security-related path hiding,
 * class namespace resolution, link injection, property-access trace filtering,
 * exact-trace extraction, trace-as-string formatting, CLI display, and
 * recursive error handling.
 */
class TErrorHandlerTest extends PHPUnit\Framework\TestCase
{
	private TErrorHandlerAccessor $handler;

	protected function setUp(): void
	{
		$this->handler = new TErrorHandlerAccessor();
	}

	// ── Constants ─────────────────────────────────────────────────────────────

	public function testConstantErrorFileName(): void
	{
		$this->assertSame('error', TErrorHandler::ERROR_FILE_NAME);
	}

	public function testConstantExceptionFileName(): void
	{
		$this->assertSame('exception', TErrorHandler::EXCEPTION_FILE_NAME);
	}

	public function testConstantSourceLines(): void
	{
		$this->assertSame(12, TErrorHandler::SOURCE_LINES);
	}

	public function testConstantFatalErrorTraceDropLines(): void
	{
		$this->assertSame(5, TErrorHandler::FATAL_ERROR_TRACE_DROP_LINES);
	}

	// ── setAppErrorHandler ────────────────────────────────────────────────────

	public function testSetAppErrorHandlerRegistersHandlerWithApplication(): void
	{
		$app = Prado::getApplication();
		if ($app === null) {
			$this->markTestSkipped('No global TApplication available.');
		}
		$original = $app->getErrorHandler();
		$this->handler->pubSetAppErrorHandler();
		$this->assertSame($this->handler, $app->getErrorHandler());
		$app->setErrorHandler($original);
	}

	public function testSetAppErrorHandlerCanBeCalledRepeatedly(): void
	{
		// Must not throw regardless of application state
		$this->handler->pubSetAppErrorHandler();
		$this->handler->pubSetAppErrorHandler();
		$this->addToAssertionCount(1);
	}

	// ── init ──────────────────────────────────────────────────────────────────

	public function testInitRegistersHandlerWithApplication(): void
	{
		$app = Prado::getApplication();
		if ($app === null) {
			$this->markTestSkipped('No global TApplication available.');
		}
		$original = $app->getErrorHandler();
		$fresh = new TErrorHandlerAccessor();
		$fresh->init(null);
		$this->assertSame($fresh, $app->getErrorHandler());
		$app->setErrorHandler($original);
	}

	// ── getDefaultErrorTemplatePath ───────────────────────────────────────────

	public function testGetDefaultErrorTemplatePath(): void
	{
		$expected = Prado::getFrameworkPath() . '/Exceptions/templates';
		$this->assertSame($expected, $this->handler->pubGetDefaultErrorTemplatePath());
	}

	public function testGetDefaultErrorTemplatePathPointsToExistingDirectory(): void
	{
		$path = $this->handler->pubGetDefaultErrorTemplatePath();
		$this->assertStringEndsWith('/Exceptions/templates', $path);
		$this->assertTrue(is_dir($path), 'Default template directory must exist on disk');
	}

	// ── Direct private-path-replacement accessors (4.3.3) ─────────────────────

	public function testGetPrivatePathReplacementsDirectReturnsNullBeforeFirstBuild(): void
	{
		$fresh = new TErrorHandlerAccessor();
		$this->assertNull($fresh->pubGetPrivatePathReplacementsDirect());
	}

	public function testSetGetPrivatePathReplacementsDirectRoundTrip(): void
	{
		$map = ['/secret/' => '${hidden}/'];
		$this->handler->pubSetPrivatePathReplacementsDirect($map);
		$this->assertSame($map, $this->handler->pubGetPrivatePathReplacementsDirect());
	}

	public function testSetPrivatePathReplacementsDirectAcceptsNull(): void
	{
		$this->handler->pubSetPrivatePathReplacementsDirect(['a' => 'b']);
		$this->handler->pubSetPrivatePathReplacementsDirect(null);
		$this->assertNull($this->handler->pubGetPrivatePathReplacementsDirect());
	}

	public function testGetPrivatePathReplacementsBuildsMapOnFirstCall(): void
	{
		$fresh = new TErrorHandlerAccessor();
		$this->assertNull($fresh->pubGetPrivatePathReplacementsDirect());
		$map = $fresh->pubGetPrivatePathReplacements();
		$this->assertIsArray($map);
		$this->assertNotEmpty($map);
		// After the call the Direct getter must return the built map
		$this->assertNotNull($fresh->pubGetPrivatePathReplacementsDirect());
	}

	public function testGetPrivatePathReplacementsIsCachedAcrossCalls(): void
	{
		$first  = $this->handler->pubGetPrivatePathReplacements();
		$second = $this->handler->pubGetPrivatePathReplacements();
		$this->assertSame($first, $second);
	}

	public function testGetPrivatePathReplacementsContainsPradoFrameworkEntry(): void
	{
		$map = $this->handler->pubGetPrivatePathReplacements();
		$this->assertContains('${PradoFramework}' . DIRECTORY_SEPARATOR, $map);
	}

	public function testGetPrivatePathReplacementsContainsDocumentRootEntry(): void
	{
		$docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
		if ($docRoot === '') {
			$this->markTestSkipped('DOCUMENT_ROOT is empty in this environment.');
		}
		$map = $this->handler->pubGetPrivatePathReplacements();
		$this->assertContains('${DocumentRoot}', $map);
	}

	// ── Direct template-path accessors (4.3.3) ────────────────────────────────

	public function testGetErrorTemplatePathDirectReturnsNullBeforeAnySet(): void
	{
		$fresh = new TErrorHandlerAccessor();
		$this->assertNull($fresh->pubGetErrorTemplatePathDirect());
	}

	public function testSetGetErrorTemplatePathDirectRoundTrip(): void
	{
		$path = sys_get_temp_dir();
		$this->handler->pubSetErrorTemplatePathDirect($path);
		$this->assertSame($path, $this->handler->pubGetErrorTemplatePathDirect());
	}

	public function testSetErrorTemplatePathDirectAcceptsNull(): void
	{
		$this->handler->pubSetErrorTemplatePathDirect('/some/path');
		$this->handler->pubSetErrorTemplatePathDirect(null);
		$this->assertNull($this->handler->pubGetErrorTemplatePathDirect());
	}

	// ── getErrorTemplatePath lazy-init ────────────────────────────────────────

	public function testGetErrorTemplatePathRawFieldIsNullBeforeFirstCall(): void
	{
		$fresh = new TErrorHandlerAccessor();
		$this->assertNull($fresh->getRawTemplatePath());
	}

	public function testGetErrorTemplatePathLazyInitOnFirstCall(): void
	{
		$fresh = new TErrorHandlerAccessor();
		$path = $fresh->getErrorTemplatePath();
		$this->assertNotNull($fresh->getRawTemplatePath());
		$this->assertSame($path, $fresh->getRawTemplatePath());
	}

	public function testGetErrorTemplatePathDefaultsToFrameworkTemplates(): void
	{
		$fresh = new TErrorHandlerAccessor();
		$this->assertStringEndsWith('/Exceptions/templates', $fresh->getErrorTemplatePath());
	}

	public function testGetErrorTemplatePathReturnsSameValueOnRepeatedCalls(): void
	{
		$first  = $this->handler->getErrorTemplatePath();
		$second = $this->handler->getErrorTemplatePath();
		$this->assertSame($first, $second);
	}

	// ── setErrorTemplatePath ──────────────────────────────────────────────────

	public function testSetErrorTemplatePathValidNamespaceUpdatesGetter(): void
	{
		$this->handler->setErrorTemplatePath('Prado\\Exceptions');
		$expected = Prado::getPathOfNamespace('Prado\\Exceptions');
		$this->assertSame($expected, $this->handler->getErrorTemplatePath());
	}

	public function testSetErrorTemplatePathStoresResolvedFilesystemPath(): void
	{
		$this->handler->setErrorTemplatePath('Prado\\Exceptions');
		$path = $this->handler->getErrorTemplatePath();
		$this->assertTrue(is_dir($path), 'Stored path must be an existing directory');
		$this->assertNotSame('Prado\\Exceptions', $path, 'Path must be resolved to a filesystem path, not stored as a namespace string');
	}

	public function testSetErrorTemplatePathInvalidNamespaceThrows(): void
	{
		$this->expectException(TConfigurationException::class);
		$this->handler->setErrorTemplatePath('Prado\NonExistentNamespace\ThatDoesNotExist');
	}

	public function testSetErrorTemplatePathToClassNamespaceThrowsBecauseNotADirectory(): void
	{
		// A class-level namespace resolves to a file, not a directory → throws
		$this->expectException(TConfigurationException::class);
		$this->handler->setErrorTemplatePath('Prado\\Exceptions\\TErrorHandler');
	}

	// ── getSourceCode ─────────────────────────────────────────────────────────

	public function testGetSourceCodeNullLinesReturnsEmpty(): void
	{
		$this->assertSame('', $this->handler->pubGetSourceCode(null, 5));
	}

	public function testGetSourceCodeEmptyArrayReturnsEmpty(): void
	{
		$this->assertSame('', $this->handler->pubGetSourceCode([], 5));
	}

	public function testGetSourceCodeZeroErrorLineProducesNoHighlight(): void
	{
		// errorLine=0: the highlight condition ($i === $errorLine - 1 = -1) never fires
		$lines  = ["line1\n", "line2\n", "line3\n"];
		$result = $this->handler->pubGetSourceCode($lines, 0);
		$this->assertStringNotContainsString('<div class="error">', $result);
		$this->assertStringContainsString('line1', $result);
		$this->assertStringContainsString('line3', $result);
	}

	public function testGetSourceCodeErrorLineIsHighlighted(): void
	{
		$lines  = ["first\n", "second\n", "third\n", "fourth\n", "fifth\n"];
		// errorLine=3 (1-indexed) → $i=2 receives the error div
		$result = $this->handler->pubGetSourceCode($lines, 3);
		$this->assertStringContainsString('<div class="error">', $result);
		$this->assertMatchesRegularExpression('/<div class="error">[^<]*third[^<]*<\/div>/', $result);
	}

	public function testGetSourceCodeNonErrorLinesAreNotHighlighted(): void
	{
		$lines  = ["first\n", "second\n", "third\n"];
		$result = $this->handler->pubGetSourceCode($lines, 2);
		$this->assertMatchesRegularExpression('/<div class="error">[^<]*second[^<]*<\/div>/', $result);
		$this->assertDoesNotMatchRegularExpression('/<div class="error">[^<]*first[^<]*<\/div>/', $result);
		$this->assertDoesNotMatchRegularExpression('/<div class="error">[^<]*third[^<]*<\/div>/', $result);
	}

	public function testGetSourceCodeTabsReplacedWithFourSpaces(): void
	{
		$lines  = ["\tindented code\n"];
		$result = $this->handler->pubGetSourceCode($lines, 1);
		$this->assertStringContainsString('    indented code', $result);
		$this->assertStringNotContainsString("\t", $result);
	}

	public function testGetSourceCodeHtmlSpecialCharsAreEscaped(): void
	{
		$lines  = ["<script>alert('xss')</script>\n"];
		$result = $this->handler->pubGetSourceCode($lines, 1);
		$this->assertStringNotContainsString('<script>', $result);
		$this->assertStringContainsString('&lt;script&gt;', $result);
	}

	public function testGetSourceCodeAmpersandIsEscaped(): void
	{
		$lines  = ["foo & bar\n"];
		$result = $this->handler->pubGetSourceCode($lines, 1);
		$this->assertStringContainsString('foo &amp; bar', $result);
	}

	public function testGetSourceCodeLineNumbersAreZeroPadded(): void
	{
		$lines  = ["first line\n"];
		$result = $this->handler->pubGetSourceCode($lines, 1);
		$this->assertMatchesRegularExpression('/0001:/', $result);
	}

	public function testGetSourceCodeLargeLineNumbersZeroPadded(): void
	{
		// 25 lines; errorLine=14 puts lines 3–25 in the window
		$lines  = array_map(fn($i) => "line$i\n", range(1, 25));
		$result = $this->handler->pubGetSourceCode($lines, 14);
		$this->assertMatchesRegularExpression('/0010:/', $result);
		$this->assertMatchesRegularExpression('/0003:/', $result);
	}

	public function testGetSourceCodeWindowClampsAtFileStart(): void
	{
		// SOURCE_LINES=12; errorLine=3 → beginLine = max(0, 3-12) = 0 → line 1 included
		$lines  = array_map(fn($i) => "line$i\n", range(1, 5));
		$result = $this->handler->pubGetSourceCode($lines, 3);
		$this->assertStringContainsString('line1', $result);
	}

	public function testGetSourceCodeWindowClampsAtFileEnd(): void
	{
		// SOURCE_LINES=12; 3-line file, errorLine=2 → endLine = min(3, 14) = 3
		$lines  = array_map(fn($i) => "line$i\n", range(1, 3));
		$result = $this->handler->pubGetSourceCode($lines, 2);
		$this->assertStringContainsString('line3', $result);
	}

	public function testGetSourceCodeLinesOutsideWindowAreExcluded(): void
	{
		// SOURCE_LINES=12; errorLine=50 in a 100-line file
		// beginLine = 50-12 = 38 (0-indexed) → first shown = line 39 (1-indexed)
		// endLine   = 50+12 = 62             → last shown  = line 62
		$lines  = array_map(fn($i) => "ln$i\n", range(1, 100));
		$result = $this->handler->pubGetSourceCode($lines, 50);
		$this->assertStringNotContainsString('0001: ln1', $result);   // before window
		$this->assertStringContainsString('0039: ln39', $result);     // first in window
		$this->assertStringContainsString('0062: ln62', $result);     // last in window
		$this->assertStringNotContainsString('0063: ln63', $result);  // after window
	}

	public function testGetSourceCodeErrorLineWayBeyondFileReturnsEmpty(): void
	{
		// errorLine=99 in a 1-line file:
		// beginLine = max(0, 99-12) = 87; endLine = min(1, 111) = 1
		// 87 > 1 → loop body never executes → empty string
		$lines  = ["only one line\n"];
		$result = $this->handler->pubGetSourceCode($lines, 99);
		$this->assertSame('', $result);
	}

	public function testGetSourceCodeErrorLineBeyondFileShowsAllLinesWithoutHighlight(): void
	{
		// errorLine=10, 5-line file → beginLine=0, endLine=5, $i===9 never fires
		$lines  = array_map(fn($i) => "line$i\n", range(1, 5));
		$result = $this->handler->pubGetSourceCode($lines, 10);
		$this->assertStringContainsString('line1', $result);
		$this->assertStringContainsString('line5', $result);
		$this->assertStringNotContainsString('<div class="error">', $result);
	}

	// ── getErrorTemplate priority ─────────────────────────────────────────────

	/**
	 * @param array<string,string> $files filename → content map
	 */
	private function makeTempTemplateDir(array $files): string
	{
		$tmpDir = sys_get_temp_dir() . '/prado_errtest_' . uniqid('', true);
		mkdir($tmpDir, 0700, true);
		foreach ($files as $name => $content) {
			file_put_contents($tmpDir . '/' . $name, $content);
		}
		return $tmpDir;
	}

	private function cleanTempDir(string $dir): void
	{
		foreach (glob($dir . '/*') ?: [] as $f) {
			unlink($f);
		}
		rmdir($dir);
	}

	public function testGetErrorTemplateStatusCodeAndLangHasHighestPriority(): void
	{
		$lang   = Prado::getPreferredLanguage();
		$tmpDir = $this->makeTempTemplateDir([
			"error404-{$lang}.html" => 'priority1',
			'error404.html'         => 'priority2',
			"error-{$lang}.html"    => 'priority3',
			'error.html'            => 'priority4',
		]);
		$this->handler->setRawTemplatePath($tmpDir);

		$content = $this->handler->pubGetErrorTemplate(404, new \Exception('test'));
		$this->assertSame('priority1', $content);
		$this->cleanTempDir($tmpDir);
	}

	public function testGetErrorTemplateStatusCodeOnlyFallback(): void
	{
		$lang   = Prado::getPreferredLanguage();
		$tmpDir = $this->makeTempTemplateDir([
			'error404.html'      => 'priority2',
			"error-{$lang}.html" => 'priority3',
			'error.html'         => 'priority4',
		]);
		$this->handler->setRawTemplatePath($tmpDir);

		$content = $this->handler->pubGetErrorTemplate(404, new \Exception('test'));
		$this->assertSame('priority2', $content);
		$this->cleanTempDir($tmpDir);
	}

	public function testGetErrorTemplateLangOnlyFallback(): void
	{
		$lang   = Prado::getPreferredLanguage();
		$tmpDir = $this->makeTempTemplateDir([
			"error-{$lang}.html" => 'priority3',
			'error.html'         => 'priority4',
		]);
		$this->handler->setRawTemplatePath($tmpDir);

		$content = $this->handler->pubGetErrorTemplate(404, new \Exception('test'));
		$this->assertSame('priority3', $content);
		$this->cleanTempDir($tmpDir);
	}

	public function testGetErrorTemplateDefaultFallback(): void
	{
		$tmpDir = $this->makeTempTemplateDir(['error.html' => 'default-content']);
		$this->handler->setRawTemplatePath($tmpDir);

		$content = $this->handler->pubGetErrorTemplate(404, new \Exception('test'));
		$this->assertSame('default-content', $content);
		$this->cleanTempDir($tmpDir);
	}

	public function testGetErrorTemplateDifferentStatusCodesSelectDifferentFiles(): void
	{
		$tmpDir = $this->makeTempTemplateDir([
			'error404.html' => 'not-found',
			'error500.html' => 'server-error',
			'error.html'    => 'default',
		]);
		$this->handler->setRawTemplatePath($tmpDir);

		$this->assertSame('not-found',    $this->handler->pubGetErrorTemplate(404, new \Exception()));
		$this->assertSame('server-error', $this->handler->pubGetErrorTemplate(500, new \Exception()));
		$this->assertSame('default',      $this->handler->pubGetErrorTemplate(403, new \Exception()));
		$this->cleanTempDir($tmpDir);
	}

	// ── getExceptionTemplate ──────────────────────────────────────────────────

	public function testGetExceptionTemplateReturnsNonEmptyString(): void
	{
		$content = $this->handler->pubGetExceptionTemplate(new \Exception('test'));
		$this->assertIsString($content);
		$this->assertNotEmpty($content);
	}

	public function testGetExceptionTemplateContainsErrorTypeToken(): void
	{
		$content = $this->handler->pubGetExceptionTemplate(new \Exception('test'));
		$this->assertStringContainsString('%%ErrorType%%', $content);
	}

	public function testGetExceptionTemplateContainsErrorMessageToken(): void
	{
		$content = $this->handler->pubGetExceptionTemplate(new \Exception('test'));
		$this->assertStringContainsString('%%ErrorMessage%%', $content);
	}

	public function testGetExceptionTemplateIsHtml(): void
	{
		$content = $this->handler->pubGetExceptionTemplate(new \Exception('test'));
		$this->assertStringContainsString('<html', $content);
	}

	public function testGetExceptionTemplateRespectsCustomTemplatePath(): void
	{
		// After the getExceptionTemplate() bug fix, setErrorTemplatePath() now
		// affects the exception template as well as the error template.
		$tmpDir = $this->makeTempTemplateDir([
			'exception.html' => 'custom-exception-template',
			'error.html'     => 'custom-error-template',
		]);
		$this->handler->setRawTemplatePath($tmpDir);

		$content = $this->handler->pubGetExceptionTemplate(new \Exception('test'));
		$this->assertSame('custom-exception-template', $content);
		$this->cleanTempDir($tmpDir);
	}

	public function testGetExceptionTemplateLanguageSpecificVariantHasPriority(): void
	{
		$lang   = Prado::getPreferredLanguage();
		$tmpDir = $this->makeTempTemplateDir([
			"exception-{$lang}.html" => 'lang-specific-exception',
			'exception.html'         => 'default-exception',
			'error.html'             => 'error-placeholder',
		]);
		$this->handler->setRawTemplatePath($tmpDir);

		$content = $this->handler->pubGetExceptionTemplate(new \Exception('test'));
		$this->assertSame('lang-specific-exception', $content);
		$this->cleanTempDir($tmpDir);
	}

	public function testGetExceptionTemplateFallsBackToDefaultWhenNoLanguageVariant(): void
	{
		$tmpDir = $this->makeTempTemplateDir([
			'exception.html' => 'fallback-exception',
			'error.html'     => 'error-placeholder',
		]);
		$this->handler->setRawTemplatePath($tmpDir);

		$content = $this->handler->pubGetExceptionTemplate(new \Exception('test'));
		$this->assertSame('fallback-exception', $content);
		$this->cleanTempDir($tmpDir);
	}

	// ── hideSecurityRelated ──────────────────────────────────────────────────

	public function testHideSecurityRelatedReplacesPradoDir(): void
	{
		$value  = 'Framework at ' . PRADO_DIR . DIRECTORY_SEPARATOR . 'TApplication.php';
		$result = $this->handler->pubHideSecurityRelated($value);
		$this->assertStringNotContainsString(PRADO_DIR . DIRECTORY_SEPARATOR, $result);
		$this->assertStringContainsString('${PradoFramework}', $result);
	}

	public function testHideSecurityRelatedReplacesDocumentRoot(): void
	{
		$docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
		if ($docRoot === '') {
			$this->markTestSkipped('DOCUMENT_ROOT is empty in this environment.');
		}
		$value  = 'File: ' . $docRoot . '/index.php';
		$result = $this->handler->pubHideSecurityRelated($value);
		$this->assertStringNotContainsString($docRoot, $result);
		$this->assertStringContainsString('${DocumentRoot}', $result);
	}

	public function testHideSecurityRelatedReplacesTraceDirs(): void
	{
		$exception = new \Exception('test');
		$trace     = $exception->getTrace();
		if (empty($trace) || !isset($trace[0]['file'])) {
			$this->markTestSkipped('Exception trace has no file entries.');
		}
		$traceFile = $trace[0]['file'];
		$traceDir  = dirname($traceFile) . DIRECTORY_SEPARATOR;
		$value     = 'Occurred in ' . $traceDir . basename($traceFile);

		$result = $this->handler->pubHideSecurityRelated($value, $exception);
		$this->assertStringNotContainsString($traceDir, $result);
		$this->assertStringContainsString('<hidden>', $result);
	}

	public function testHideSecurityRelatedWithNullExceptionSkipsTraceDirs(): void
	{
		// No exception → only PRADO_DIR and DOCUMENT_ROOT are replaced
		$value  = 'plain message with no sensitive paths';
		$result = $this->handler->pubHideSecurityRelated($value, null);
		$this->assertSame($value, $result);
	}

	public function testHideSecurityRelatedWithPlainStringReturnsUnchanged(): void
	{
		$value  = 'A plain message with no paths at all';
		$result = $this->handler->pubHideSecurityRelated($value);
		$this->assertSame($value, $result);
	}

	// ── hidePrivatePathParts (instance-cached) ────────────────────────────────

	public function testHidePrivatePathPartsReplacesPradoDir(): void
	{
		$value  = 'In ' . PRADO_DIR . DIRECTORY_SEPARATOR . 'TComponent.php';
		$result = $this->handler->pubHidePrivatePathParts($value);
		$this->assertStringNotContainsString(PRADO_DIR . DIRECTORY_SEPARATOR, $result);
		$this->assertStringContainsString('${PradoFramework}', $result);
	}

	public function testHidePrivatePathPartsReplacesDocumentRoot(): void
	{
		$docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
		if ($docRoot === '') {
			$this->markTestSkipped('DOCUMENT_ROOT is empty in this environment.');
		}
		$value  = 'Path: ' . $docRoot . '/index.php';
		$result = $this->handler->pubHidePrivatePathParts($value);
		$this->assertStringNotContainsString($docRoot, $result);
		$this->assertStringContainsString('${DocumentRoot}', $result);
	}

	public function testHidePrivatePathPartsCacheProducesConsistentResults(): void
	{
		// The replacement map is built once and cached on the instance.
		$value  = PRADO_DIR . DIRECTORY_SEPARATOR . 'framework.php';
		$first  = $this->handler->pubHidePrivatePathParts($value);
		$second = $this->handler->pubHidePrivatePathParts($value);
		$this->assertSame($first, $second);
	}

	public function testHidePrivatePathPartsPlainStringIsUnchanged(): void
	{
		$value  = 'nothing sensitive here';
		$result = $this->handler->pubHidePrivatePathParts($value);
		$this->assertSame($value, $result);
	}

	public function testHidePrivatePathPartsCacheCanBeCleared(): void
	{
		// Clearing the cached map via the Direct setter causes a rebuild on next call
		$this->handler->pubHidePrivatePathParts('warm up');
		$this->assertNotNull($this->handler->pubGetPrivatePathReplacementsDirect());

		$this->handler->pubSetPrivatePathReplacementsDirect(null);
		$this->assertNull($this->handler->pubGetPrivatePathReplacementsDirect());

		// Calling hidePrivatePathParts again rebuilds the cache
		$this->handler->pubHidePrivatePathParts('trigger rebuild');
		$this->assertNotNull($this->handler->pubGetPrivatePathReplacementsDirect());
	}

	// ── getErrorClassNameSpace ────────────────────────────────────────────────

	public function testGetErrorClassNameSpaceNoTClassReturnsNull(): void
	{
		$this->assertNull($this->handler->pubGetErrorClassNameSpace('No class name here at all'));
	}

	public function testGetErrorClassNameSpaceNonExistentClassReturnsNull(): void
	{
		// TFakeClassThatWillNotExistXyz123 → ReflectionClass throws → returns null
		$this->assertNull(
			$this->handler->pubGetErrorClassNameSpace('Error involving TFakeClassThatWillNotExistXyz123')
		);
	}

	public function testGetErrorClassNameSpaceRequiresUppercaseTPrefix(): void
	{
		// 'tLowercase' does not match \b(T[A-Z]\w+)\b
		$this->assertNull(
			$this->handler->pubGetErrorClassNameSpace('problem in tLowercaseClass here')
		);
	}

	public function testGetErrorClassNameSpaceResolvesGlobalClass(): void
	{
		$result = $this->handler->pubGetErrorClassNameSpace('Error in TErrorHandlerTestGlobalClass was thrown');
		$this->assertIsArray($result);
		$this->assertArrayHasKey('url', $result);
		$this->assertArrayHasKey('name', $result);
		$this->assertSame('TErrorHandlerTestGlobalClass', $result['name']);
	}

	public function testGetErrorClassNameSpaceUrlHasCorrectFormat(): void
	{
		$result = $this->handler->pubGetErrorClassNameSpace('Error in TErrorHandlerTestGlobalClass here');
		if ($result === null) {
			$this->markTestSkipped('TErrorHandlerTestGlobalClass not reflectable as a global class.');
		}
		$this->assertStringStartsWith('https://pradosoft.github.io/docs/manual/class-', $result['url']);
		$this->assertStringEndsWith('TErrorHandlerTestGlobalClass.html', $result['url']);
	}

	public function testGetErrorClassNameSpaceUsesFirstMatchWhenMultipleClassesAppear(): void
	{
		// Two T-classes in message; the regex finds the first one; neither exists → null
		$result = $this->handler->pubGetErrorClassNameSpace(
			'Both TFakeFirstClass and TFakeSecondClass appear'
		);
		$this->assertNull($result);
	}

	// ── addLink ───────────────────────────────────────────────────────────────

	public function testAddLinkWithNoClassReturnsMessageUnchanged(): void
	{
		$message = 'No class name here to link';
		$this->assertSame($message, $this->handler->pubAddLink($message));
	}

	public function testAddLinkWithUnknownClassReturnsMessageUnchanged(): void
	{
		$message = 'Error in TFakeNonExistentClassAbc occurred';
		$this->assertSame($message, $this->handler->pubAddLink($message));
	}

	public function testAddLinkWithKnownGlobalClassInsertsAnchorTag(): void
	{
		$message = 'Error in TErrorHandlerTestGlobalClass was thrown';
		$result  = $this->handler->pubAddLink($message);
		if ($result === $message) {
			$this->markTestSkipped('TErrorHandlerTestGlobalClass not reflectable as global.');
		}
		$this->assertStringContainsString('<a href=', $result);
		$this->assertStringContainsString('target="_blank"', $result);
		$this->assertStringContainsString('TErrorHandlerTestGlobalClass', $result);
	}

	public function testAddLinkDoesNotThrowOnRepeatedCalls(): void
	{
		$message = 'See TErrorHandlerTestGlobalClass for details';
		$first   = $this->handler->pubAddLink($message);
		$second  = $this->handler->pubAddLink($first);
		$this->assertIsString($second);
	}

	public function testAddLinkWithNamespacedPradoClassInsertsAnchorTag(): void
	{
		// Prado's autoloader resolves the short name 'TErrorHandler' to
		// Prado\Exceptions\TErrorHandler, so addLink() can build a documentation
		// URL even though the class lives in a namespace.
		$message = 'Error involving TErrorHandler occurred';
		$result  = $this->handler->pubAddLink($message);
		$this->assertStringContainsString('<a href=', $result);
		$this->assertStringContainsString('TErrorHandler', $result);
		$this->assertStringContainsString('Prado.Exceptions', $result);
	}

	public function testGetErrorClassNameSpaceUsesHttpsUrl(): void
	{
		$result = $this->handler->pubGetErrorClassNameSpace('Error in TErrorHandlerTestGlobalClass here');
		if ($result === null) {
			$this->markTestSkipped('TErrorHandlerTestGlobalClass not reflectable as a global class.');
		}
		$this->assertStringStartsWith('https://', $result['url']);
	}

	// ── getPropertyAccessTrace ────────────────────────────────────────────────

	public function testGetPropertyAccessTraceEmptyTraceReturnsNull(): void
	{
		$this->assertNull($this->handler->pubGetPropertyAccessTrace([], '__get'));
	}

	public function testGetPropertyAccessTraceFrameWithNoFunctionKeyReturnsNull(): void
	{
		$trace  = [['file' => 'a.php', 'line' => 1]];
		$this->assertNull($this->handler->pubGetPropertyAccessTrace($trace, '__get'));
	}

	public function testGetPropertyAccessTraceNonMatchingFunctionReturnsNull(): void
	{
		$trace  = [['function' => '__construct', 'file' => 'a.php', 'line' => 1]];
		$this->assertNull($this->handler->pubGetPropertyAccessTrace($trace, '__get'));
	}

	public function testGetPropertyAccessTraceSingleMatchingFrameReturnsIt(): void
	{
		$frame  = ['function' => '__get', 'file' => 'a.php', 'line' => 10];
		$result = $this->handler->pubGetPropertyAccessTrace([$frame], '__get');
		$this->assertSame($frame, $result);
	}

	public function testGetPropertyAccessTraceReturnsLastOfConsecutiveMatches(): void
	{
		$frame1 = ['function' => '__get', 'file' => 'a.php', 'line' => 1];
		$frame2 = ['function' => '__get', 'file' => 'b.php', 'line' => 2];
		$result = $this->handler->pubGetPropertyAccessTrace([$frame1, $frame2], '__get');
		$this->assertSame($frame2, $result);
	}

	public function testGetPropertyAccessTraceBreaksAtFirstNonMatch(): void
	{
		$frame1 = ['function' => '__get',           'file' => 'a.php', 'line' => 1];
		$frame2 = ['function' => 'someOtherMethod', 'file' => 'b.php', 'line' => 2];
		$frame3 = ['function' => '__get',           'file' => 'c.php', 'line' => 3];
		$result = $this->handler->pubGetPropertyAccessTrace([$frame1, $frame2, $frame3], '__get');
		// Stops at frame2; frame3 (after the break) is never considered
		$this->assertSame($frame1, $result);
	}

	public function testGetPropertyAccessTraceThreeConsecutiveMatchesReturnsLast(): void
	{
		$frames = [
			['function' => '__set', 'file' => 'a.php', 'line' => 1],
			['function' => '__set', 'file' => 'b.php', 'line' => 2],
			['function' => '__set', 'file' => 'c.php', 'line' => 3],
		];
		$this->assertSame($frames[2], $this->handler->pubGetPropertyAccessTrace($frames, '__set'));
	}

	public function testGetPropertyAccessTracePatternIsStrictlyMatched(): void
	{
		// __set frames do not match when the pattern is __get
		$trace  = [['function' => '__set', 'file' => 'a.php', 'line' => 1]];
		$this->assertNull($this->handler->pubGetPropertyAccessTrace($trace, '__get'));
	}

	public function testGetPropertyAccessTraceMatchFollowedByFrameWithoutFunctionBreaks(): void
	{
		// A frame with no 'function' key triggers the else-break, so only the
		// matching frames before it count.
		$frame1 = ['function' => '__get', 'file' => 'a.php', 'line' => 1];
		$frameNoFunc = ['file' => 'b.php', 'line' => 2]; // no 'function' key
		$frame3 = ['function' => '__get', 'file' => 'c.php', 'line' => 3];
		$result = $this->handler->pubGetPropertyAccessTrace([$frame1, $frameNoFunc, $frame3], '__get');
		$this->assertSame($frame1, $result);
	}

	// ── getExactTrace ─────────────────────────────────────────────────────────

	public function testGetExactTraceGenericExceptionReturnsNull(): void
	{
		// A plain RuntimeException is neither TPhpErrorException nor
		// TInvalidOperationException → the method returns null.
		$result = $this->handler->pubGetExactTrace(new \RuntimeException('test'));
		$this->assertNull($result);
	}

	public function testGetExactTraceTPhpErrorExceptionReturnsFirstFrameWithFile(): void
	{
		$ex    = new TPhpErrorException(E_WARNING, 'test warning', __FILE__, __LINE__);
		$trace = $ex->getTrace();
		if (empty($trace) || !isset($trace[0]['file'])) {
			$this->markTestSkipped('Exception trace frame 0 has no file key in this environment.');
		}
		$result = $this->handler->pubGetExactTrace($ex);
		$this->assertIsArray($result);
		$this->assertArrayHasKey('file', $result);
		$this->assertSame($trace[0], $result);
	}

	public function testGetExactTraceTPhpErrorExceptionEvalFrameReturnsNull(): void
	{
		// When the exception is constructed inside eval(), trace[0]['file'] contains
		// ": eval()'d code" which the method explicitly filters out.
		$ex = eval('return new \Prado\Exceptions\TPhpErrorException(\E_WARNING, "eval test", "eval.php", 1);');
		$this->assertNull($this->handler->pubGetExactTrace($ex));
	}

	public function testGetExactTraceTInvalidOperationExceptionWithNoPropertyFramesReturnsNull(): void
	{
		// A directly constructed TInvalidOperationException has no __get or __set
		// frames in its trace → getPropertyAccessTrace returns null for both →
		// getExactTrace returns null.
		$ex     = new TInvalidOperationException('prado_application_singleton_required');
		$result = $this->handler->pubGetExactTrace($ex);
		$this->assertNull($result);
	}

	public function testGetExactTraceNonMatchingExceptionTypeReturnsNull(): void
	{
		// TConfigurationException is neither TPhpErrorException nor
		// TInvalidOperationException → returns null.
		$ex     = new TConfigurationException('errorhandler_errortemplatepath_invalid', 'x');
		$result = $this->handler->pubGetExactTrace($ex);
		$this->assertNull($result);
	}

	// ── getExactTraceAsString ─────────────────────────────────────────────────

	public function testGetExactTraceAsStringReturnsString(): void
	{
		$ex     = new \Exception('trace test');
		$result = $this->handler->pubGetExactTraceAsString($ex);
		$this->assertIsString($result);
	}

	public function testGetExactTraceAsStringContainsHashFrameMarkers(): void
	{
		$ex     = new \Exception('trace test');
		$result = $this->handler->pubGetExactTraceAsString($ex);
		// Exception::getTraceAsString() always starts with '#0'
		$this->assertMatchesRegularExpression('/#\d/', $result);
	}

	public function testGetExactTraceAsStringSanitizesPradoFrameworkPaths(): void
	{
		// The string representation of the exception's trace will contain paths
		// to framework files; hidePrivatePathParts must have replaced PRADO_DIR.
		$ex     = new \Exception('trace test');
		$result = $this->handler->pubGetExactTraceAsString($ex);
		// PRADO_DIR itself must not appear verbatim; if the trace touches framework
		// files the token replaces it.
		if (str_contains($ex->getTraceAsString(), PRADO_DIR . DIRECTORY_SEPARATOR)) {
			$this->assertStringNotContainsString(PRADO_DIR . DIRECTORY_SEPARATOR, $result);
			$this->assertStringContainsString('${PradoFramework}', $result);
		} else {
			// No framework paths in the trace; result is unchanged (no assertion needed)
			$this->assertIsString($result);
		}
	}

	// ── displayException (CLI mode) ───────────────────────────────────────────

	public function testDisplayExceptionCliModeOutputsExceptionMessage(): void
	{
		// PHPUnit runs via CLI, so php_sapi_name() === 'cli' is guaranteed.
		$exception = new \Exception('cli display test message');
		ob_start();
		$this->handler->pubDisplayException($exception);
		$output = ob_get_clean();
		$this->assertStringContainsString('cli display test message', $output);
	}

	public function testDisplayExceptionCliModeOutputsTraceString(): void
	{
		$exception = new \Exception('trace display test');
		ob_start();
		$this->handler->pubDisplayException($exception);
		$output = ob_get_clean();
		// Trace output contains '#0' frame marker
		$this->assertMatchesRegularExpression('/#\d/', $output);
	}

	// ── displayException (web mode) ──────────────────────────────────────────────

	public function testDisplayExceptionWebModeOutputsExceptionTokens(): void
	{
		// Run displayException() in web mode by faking a non-CLI SAPI.
		$this->handler->fakePhpSapiName = 'apache2handler';
		$exception = new \Exception('web display test message');
		ob_start();
		try {
			$this->handler->pubDisplayException($exception);
		} finally {
			$output = ob_get_clean();
			$this->handler->fakePhpSapiName = null;
		}
		// Build token strings dynamically so they do not appear as literals in the
		// source-code window rendered by the exception template itself.
		$tokenErrorType    = '%%' . 'ErrorType'    . '%%';
		$tokenErrorMessage = '%%' . 'ErrorMessage' . '%%';
		// The exception template is rendered with token substitution.
		$this->assertStringNotContainsString($tokenErrorType, $output);
		$this->assertStringNotContainsString($tokenErrorMessage, $output);
		$this->assertStringContainsString('Exception', $output);
		$this->assertStringContainsString('web display test message', $output);
	}

	public function testDisplayExceptionWebModeSourceFileIsSanitized(): void
	{
		$this->handler->fakePhpSapiName = 'apache2handler';
		$exception = new \Exception('path sanitation test');
		ob_start();
		try {
			$this->handler->pubDisplayException($exception);
		} finally {
			$output = ob_get_clean();
			$this->handler->fakePhpSapiName = null;
		}
		// The %%SourceFile%% token must have been replaced; PRADO_DIR must not appear raw.
		$this->assertStringNotContainsString(PRADO_DIR . DIRECTORY_SEPARATOR, $output);
	}

	public function testDisplayExceptionWebModeDoesNotOutputRawStackTrace(): void
	{
		$this->handler->fakePhpSapiName = 'apache2handler';
		$exception = new \Exception('stack trace test');
		ob_start();
		try {
			$this->handler->pubDisplayException($exception);
		} finally {
			$output = ob_get_clean();
			$this->handler->fakePhpSapiName = null;
		}
		// Build token string dynamically so it does not appear as a literal in
		// the source-code window rendered by the exception template itself.
		$tokenStackTrace = '%%' . 'StackTrace' . '%%';
		$this->assertStringNotContainsString($tokenStackTrace, $output);
	}

	// ── handleRecursiveError ──────────────────────────────────────────────────

	public function testHandleRecursiveErrorInDebugModeRendersMinimalHtml(): void
	{
		$app = Prado::getApplication();
		if ($app === null) {
			$this->markTestSkipped('No global TApplication available.');
		}
		$originalMode = $app->getMode();
		$app->setMode(TApplicationMode::Debug);
		$exception = new \Exception('Test recursive error message');
		ob_start();
		try {
			$this->handler->pubHandleRecursiveError($exception);
		} finally {
			$output = ob_get_clean();
			$app->setMode($originalMode);
		}
		$this->assertStringContainsString('<html>', $output);
		$this->assertStringContainsString('Recursive Error', $output);
		$this->assertStringContainsString('Test recursive error message', $output);
	}

	public function testHandleRecursiveErrorInNonDebugModeProducesNoHtmlOutput(): void
	{
		$app = Prado::getApplication();
		if ($app === null) {
			$this->markTestSkipped('No global TApplication available.');
		}
		$originalMode = $app->getMode();
		$app->setMode(TApplicationMode::Normal);
		$exception = new \Exception('Test recursive error');
		ob_start();
		try {
			$this->handler->pubHandleRecursiveError($exception);
		} finally {
			$output = ob_get_clean();
			$app->setMode($originalMode);
		}
		$this->assertSame('', $output);
	}

	public function testHandleRecursiveErrorInNonDebugModeLogsMessage(): void
	{
		$app = Prado::getApplication();
		if ($app === null) {
			$this->markTestSkipped('No global TApplication available.');
		}
		$originalMode = $app->getMode();
		$app->setMode(TApplicationMode::Normal);
		$this->handler->capturedErrorLog = null;
		try {
			$this->handler->pubHandleRecursiveError(new \Exception('logged error'));
		} finally {
			$app->setMode($originalMode);
		}
		$this->assertNotNull($this->handler->capturedErrorLog);
		$this->assertStringContainsString('logged error', $this->handler->capturedErrorLog);
		$this->assertStringContainsString('Error happened while processing an existing error', $this->handler->capturedErrorLog);
	}

	public function testHandleRecursiveErrorInOffModeProducesNoHtmlOutput(): void
	{
		$app = Prado::getApplication();
		if ($app === null) {
			$this->markTestSkipped('No global TApplication available.');
		}
		$originalMode = $app->getMode();
		$app->setMode(TApplicationMode::Off);
		$exception = new \Exception('Test recursive off error');
		ob_start();
		try {
			$this->handler->pubHandleRecursiveError($exception);
		} finally {
			$output = ob_get_clean();
			$app->setMode($originalMode);
		}
		$this->assertSame('', $output);
	}

	public function testHandleRecursiveErrorInPerformanceModeProducesNoHtmlOutput(): void
	{
		$app = Prado::getApplication();
		if ($app === null) {
			$this->markTestSkipped('No global TApplication available.');
		}
		$originalMode = $app->getMode();
		$app->setMode(TApplicationMode::Performance);
		$exception = new \Exception('Test recursive performance error');
		ob_start();
		try {
			$this->handler->pubHandleRecursiveError($exception);
		} finally {
			$output = ob_get_clean();
			$app->setMode($originalMode);
		}
		$this->assertSame('', $output);
	}

	public function testHandleRecursiveErrorSendsHeaderWhenHeadersNotSent(): void
	{
		$app = Prado::getApplication();
		if ($app === null) {
			$this->markTestSkipped('No global TApplication available.');
		}
		$originalMode = $app->getMode();
		$app->setMode(TApplicationMode::Normal);
		// Simulate headers not yet sent.
		$this->handler->fakeHeadersSent = false;
		$this->handler->capturedHeaders = [];
		ob_start();
		try {
			$this->handler->pubHandleRecursiveError(new \Exception('header test'));
		} finally {
			ob_end_clean();
			$this->handler->fakeHeadersSent = null;
			$app->setMode($originalMode);
		}
		$this->assertCount(1, $this->handler->capturedHeaders);
		$this->assertStringContainsString('500', $this->handler->capturedHeaders[0]);
	}

	public function testHandleRecursiveErrorSkipsHeaderWhenHeadersAlreadySent(): void
	{
		$app = Prado::getApplication();
		if ($app === null) {
			$this->markTestSkipped('No global TApplication available.');
		}
		$originalMode = $app->getMode();
		$app->setMode(TApplicationMode::Normal);
		// Simulate headers already sent.
		$this->handler->fakeHeadersSent = true;
		$this->handler->capturedHeaders = [];
		try {
			$this->handler->pubHandleRecursiveError(new \Exception('header skip test'));
		} finally {
			$this->handler->fakeHeadersSent = null;
			$app->setMode($originalMode);
		}
		$this->assertCount(0, $this->handler->capturedHeaders);
	}

	public function testHandleRecursiveErrorDoesNotThrow(): void
	{
		// Must not throw regardless of application mode.
		$exception = new \Exception('Error inside error handler');
		ob_start();
		try {
			$this->handler->pubHandleRecursiveError($exception);
			$threw = false;
		} catch (\Throwable $t) {
			$threw = true;
		} finally {
			ob_get_clean();
		}
		$this->assertFalse($threw);
	}

	// ── getIsHandled / setIsHandled ───────────────────────────────────────────────

	public function testGetIsHandledDefaultsToFalse(): void
	{
		$this->assertFalse($this->handler->pubGetIsHandled());
	}

	public function testSetIsHandledToTrueIsReflectedByGetter(): void
	{
		$this->handler->pubSetIsHandled(true);
		$this->assertTrue($this->handler->pubGetIsHandled());
	}

	public function testSetIsHandledToFalseResetsFlag(): void
	{
		$this->handler->pubSetIsHandled(true);
		$this->handler->pubSetIsHandled(false);
		$this->assertFalse($this->handler->pubGetIsHandled());
	}

	public function testResetHandlingClearsFlag(): void
	{
		$this->handler->pubSetIsHandled(true);
		$this->handler->resetHandling();
		$this->assertFalse($this->handler->pubGetIsHandled());
	}

	// ── handleError ───────────────────────────────────────────────────────────────

	public function testHandleErrorCallsRestoreErrorHandler(): void
	{
		$app = Prado::getApplication();
		if ($app === null) {
			$this->markTestSkipped('No global TApplication available.');
		}
		$this->handler->resetHandling();
		$this->handler->restoreErrorHandlerCalled = false;
		$exception = new \Prado\Exceptions\THttpException(404, 'pageservice_page_unknown', 'test');
		$this->handler->pubHandleError(null, $exception);
		$this->assertTrue($this->handler->restoreErrorHandlerCalled);
	}

	public function testHandleErrorCallsRestoreExceptionHandler(): void
	{
		$app = Prado::getApplication();
		if ($app === null) {
			$this->markTestSkipped('No global TApplication available.');
		}
		$this->handler->resetHandling();
		$this->handler->restoreExceptionHandlerCalled = false;
		$exception = new \Prado\Exceptions\THttpException(404, 'pageservice_page_unknown', 'test');
		$this->handler->pubHandleError(null, $exception);
		$this->assertTrue($this->handler->restoreExceptionHandlerCalled);
	}

	public function testHandleErrorSetsContentTypeHeaderWhenHeadersNotSent(): void
	{
		$app = Prado::getApplication();
		if ($app === null) {
			$this->markTestSkipped('No global TApplication available.');
		}
		$this->handler->resetHandling();
		$this->handler->fakeHeadersSent = false;
		$this->handler->capturedHeaders = [];
		$exception = new \Prado\Exceptions\THttpException(404, 'pageservice_page_unknown', 'test');
		$this->handler->pubHandleError(null, $exception);
		$this->handler->fakeHeadersSent = null;
		$found = false;
		foreach ($this->handler->capturedHeaders as $h) {
			if (stripos($h, 'Content-Type') !== false && stripos($h, 'text/html') !== false) {
				$found = true;
				break;
			}
		}
		$this->assertTrue($found, 'handleError() must set a Content-Type: text/html header when headers have not been sent.');
	}

	public function testHandleErrorDispatchesToHandleExternalErrorForHttpException(): void
	{
		$app = Prado::getApplication();
		if ($app === null) {
			$this->markTestSkipped('No global TApplication available.');
		}
		$this->handler->resetHandling();
		$this->handler->capturedExternalErrorStatusCode = null;
		$exception = new \Prado\Exceptions\THttpException(403, 'pageservice_page_unknown', 'test');
		$this->handler->pubHandleError(null, $exception);
		$this->assertSame(403, $this->handler->capturedExternalErrorStatusCode);
	}

	public function testHandleErrorDispatchesToDisplayExceptionInDebugMode(): void
	{
		$app = Prado::getApplication();
		if ($app === null) {
			$this->markTestSkipped('No global TApplication available.');
		}
		$originalMode = $app->getMode();
		$app->setMode(TApplicationMode::Debug);
		$this->handler->resetHandling();
		$this->handler->displayExceptionCalled = false;
		try {
			$this->handler->pubHandleError(null, new \RuntimeException('dispatch test'));
		} finally {
			$app->setMode($originalMode);
		}
		$this->assertTrue($this->handler->displayExceptionCalled);
	}

	public function testHandleErrorDispatchesToHandleExternalError500ForNonHttpExceptionInNonDebugMode(): void
	{
		$app = Prado::getApplication();
		if ($app === null) {
			$this->markTestSkipped('No global TApplication available.');
		}
		$originalMode = $app->getMode();
		$app->setMode(TApplicationMode::Normal);
		$this->handler->resetHandling();
		$this->handler->capturedExternalErrorStatusCode = null;
		try {
			$this->handler->pubHandleError(null, new \RuntimeException('non-http normal mode dispatch'));
		} finally {
			$app->setMode($originalMode);
		}
		$this->assertSame(500, $this->handler->capturedExternalErrorStatusCode);
	}

	public function testHandleErrorRecursiveCallGoesToHandleRecursiveError(): void
	{
		$app = Prado::getApplication();
		if ($app === null) {
			$this->markTestSkipped('No global TApplication available.');
		}
		// Force _handling = true so the next call is treated as recursive.
		$this->handler->resetHandling();
		$exception = new \Prado\Exceptions\THttpException(404, 'pageservice_page_unknown', 'test');
		$this->handler->pubHandleError(null, $exception); // sets _handling = true
		// Second call — must take the recursive path (handleRecursiveError in non-debug = no output, just log).
		$originalMode = $app->getMode();
		$app->setMode(TApplicationMode::Normal);
		$this->handler->capturedErrorLog = null;
		ob_start();
		try {
			$this->handler->pubHandleError(null, new \RuntimeException('recursive'));
		} finally {
			ob_get_clean();
			$app->setMode($originalMode);
		}
		$this->assertNotNull($this->handler->capturedErrorLog,
			'Second handleError() call must route through handleRecursiveError() and log.');
	}

	// ── PHP global-function wrappers (self-encapsulation) ──────────────────────

	public function testHeadersSentDelegatesTo_headers_sent(): void
	{
		// headers_sent() may or may not be false in the CLI PHPUnit environment
		// (it returns true once any output has been flushed). The protected
		// helper must return exactly the same value as the underlying PHP call.
		$this->assertSame(headers_sent(), $this->handler->pubHeadersSent());
	}

	public function testErrorLogDelegatesToPhpErrorLog(): void
	{
		// Redirect error_log to a temp file to capture the output without
		// polluting stderr, then verify the message was written.
		$logFile = tempnam(sys_get_temp_dir(), 'prado_test_errlog_');
		$originalErrorLog = ini_set('error_log', $logFile);
		try {
			$this->handler->pubErrorLog('test error log message');
		} finally {
			ini_set('error_log', $originalErrorLog);
		}
		$contents = @file_get_contents($logFile) ?: '';
		@unlink($logFile);
		$this->assertStringContainsString('test error log message', $contents);
	}

	public function testPhpSapiNameDelegatesToPhpFunction(): void
	{
		$this->assertSame(php_sapi_name(), $this->handler->pubPhpSapiName());
	}

	public function testRestoreErrorHandlerRestoresPreviousHandler(): void
	{
		// Push a sentinel handler onto the PHP error-handler stack.
		$sentinel = static function (int $no, string $str): bool { return true; };
		set_error_handler($sentinel);
		// pubRestoreErrorHandler() wraps restore_error_handler(); the sentinel must be popped.
		$this->handler->pubRestoreErrorHandler();
		// Install a probe to read what the current "previous" handler is.
		$previous = set_error_handler(static function (int $no, string $str): bool { return false; });
		restore_error_handler(); // remove the probe
		$this->assertNotSame($sentinel, $previous,
			'restoreErrorHandler() must pop the sentinel off the error-handler stack.');
	}

	public function testRestoreExceptionHandlerRestoresPreviousHandler(): void
	{
		// Push a sentinel exception handler.
		$sentinel = static function (\Throwable $e): void {};
		set_exception_handler($sentinel);
		// pubRestoreExceptionHandler() wraps restore_exception_handler().
		$this->handler->pubRestoreExceptionHandler();
		// Install a probe to read the current "previous" exception handler.
		$previous = set_exception_handler(static function (\Throwable $e): void {});
		restore_exception_handler(); // remove the probe
		$this->assertNotSame($sentinel, $previous,
			'restoreExceptionHandler() must pop the sentinel off the exception-handler stack.');
	}

	public function testServerGlobalDelegatesToServerSuperglobal(): void
	{
		// serverGlobal() must return the same value as $_SERVER[$key].
		$key   = 'PATH';
		$this->assertSame($_SERVER[$key] ?? null, $this->handler->pubServerGlobal($key));
	}

	public function testServerGlobalReturnsNullForAbsentKey(): void
	{
		$this->assertNull($this->handler->pubServerGlobal('__PRADO_TEST_KEY_THAT_DOES_NOT_EXIST__'));
	}

	public function testServerGlobalReturnsInjectedValue(): void
	{
		// serverGlobal() reads from $_SERVER, so injecting a value there is immediately visible.
		$key   = '__PRADO_TEST_INJECT__';
		$_SERVER[$key] = 'injected-value';
		try {
			$this->assertSame('injected-value', $this->handler->pubServerGlobal($key));
		} finally {
			unset($_SERVER[$key]);
		}
	}
}
