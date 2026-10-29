<?php

/**
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Exceptions;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Prado\Exceptions\IUserException;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TErrorHandler;
use Prado\Exceptions\TException;
use Prado\Exceptions\THttpException;
use Prado\Collections\TMap;
use Prado\Exceptions\TUserException;
use Prado\I18N\TGlobalization;
use Prado\Prado;
use Prado\TApplicationMode;
use Prado\Test\Unit\PradoUnit;

/**
 * A TErrorHandler that captures the log and exposes handleError() and the message methods.
 */
class TUserMessageErrorHandler extends TErrorHandler
{
	public ?string $capturedErrorLog = null;

	protected function errorLog(string $message): void
	{
		$this->capturedErrorLog = $message;
	}

	// handleError() restores the PHP handlers; the test process keeps Prado's.
	protected function restoreErrorHandler(): void
	{
	}

	protected function restoreExceptionHandler(): void
	{
	}

	public function pubHandleError(mixed $sender, \Throwable $param): void
	{
		$this->handleError($sender, $param);
	}

	public function pubGetExternalErrorMessage(\Throwable $exception, bool $isDebug): string
	{
		return $this->getExternalErrorMessage($exception, $isDebug);
	}

	public function pubLocalizeErrorMessage(TException $exception): string
	{
		return $this->localizeErrorMessage($exception);
	}
}

/**
 * Tests the visitor-facing message rule of {@see TErrorHandler}: an {@see IUserException}
 * shows its message, any other exception shows the generic sentence outside Debug mode,
 * and the globalization module's `prado` catalogue overrides both.
 */
class TErrorHandlerUserMessageTest extends \PHPUnit\Framework\TestCase
{
	private TUserMessageErrorHandler $_handler;
	private $_mode;
	private $_globalization;

	protected function setUp(): void
	{
		$app = Prado::getApplication();
		$this->_mode = $app->getMode();
		$this->_globalization = PradoUnit::getProp($app, '_globalization');
		// Not initialized: init() would register the handler as the application error handler.
		$this->_handler = new TUserMessageErrorHandler();
	}

	protected function tearDown(): void
	{
		$app = Prado::getApplication();
		$app->setMode($this->_mode);
		PradoUnit::setProp($app, '_globalization', $this->_globalization);
	}

	/**
	 * Runs the real external-error path and returns the rendered page.
	 * @param \Throwable $exception the exception to handle.
	 * @param string $mode the application mode.
	 * @return string the rendered page.
	 */
	protected function render(\Throwable $exception, string $mode): string
	{
		Prado::getApplication()->setMode($mode);
		$level = ob_get_level();
		ob_start();
		try {
			$this->_handler->pubHandleError(null, $exception);
		} finally {
			// The response starts its own buffer on first use; drain every level opened here.
			$output = '';
			while (ob_get_level() > $level) {
				$output = ob_get_clean() . $output;
			}
		}
		return $output;
	}

	// ── Marker interface ─────────────────────────────────────────────────────

	public function testHttpExceptionAndUserExceptionAreUserExceptions(): void
	{
		$this->assertInstanceOf(IUserException::class, new THttpException(404, 'pageservice_page_unknown', 'Home'));
		$this->assertInstanceOf(IUserException::class, new TUserException('Your session has expired.'));
		$this->assertNotInstanceOf(IUserException::class, new TConfigurationException('component_property_undefined', 'A', 'B'));
		$this->assertNotInstanceOf(IUserException::class, new TException('component_property_undefined', 'A', 'B'));
	}

	public function testErrorArgumentsAreTheStringifiedPlaceholderValues(): void
	{
		$e = new TConfigurationException('component_property_undefined', 'TPage', 42, new \RuntimeException('cause'));
		$this->assertSame(['TPage', '42'], $e->getErrorArguments());
		$this->assertSame([], (new TException('component_property_undefined'))->getErrorArguments());
	}

	// ── Message rule ─────────────────────────────────────────────────────────

	public function testDebugModeShowsEveryExceptionMessage(): void
	{
		$e = new TConfigurationException('component_property_undefined', 'TPage', 'Foo');
		$this->assertSame("Component property 'TPage.Foo' is not defined.", $this->_handler->pubGetExternalErrorMessage($e, true));
		$this->assertSame('plain', $this->_handler->pubGetExternalErrorMessage(new \RuntimeException('plain'), true));
	}

	public function testProductionShowsTheGenericSentenceForADeveloperException(): void
	{
		$e = new TConfigurationException('component_property_undefined', 'TPage', 'Foo');
		$this->assertSame('An internal server error occurred.', $this->_handler->pubGetExternalErrorMessage($e, false));
		$this->assertSame('An internal server error occurred.', $this->_handler->pubGetExternalErrorMessage(new \RuntimeException('plain'), false));
	}

	public function testProductionShowsTheMessageOfAUserException(): void
	{
		$this->assertSame("Page 'Home' Not Found", $this->_handler->pubGetExternalErrorMessage(new THttpException(404, 'pageservice_page_unknown', 'Home'), false));
		$this->assertSame('Your session has expired.', $this->_handler->pubGetExternalErrorMessage(new TUserException('Your session has expired.'), false));
	}

	public function testRenderedProductionPageHidesTheDeveloperMessageAndLogsIt(): void
	{
		$output = $this->render(new TConfigurationException('component_property_undefined', 'TPage', 'Foo'), TApplicationMode::Normal);
		$this->assertStringContainsString('An internal server error occurred.', $output);
		$this->assertStringNotContainsString('TPage.Foo', $output);
		$this->assertStringContainsString('TPage.Foo', $this->_handler->capturedErrorLog);
	}

	public function testRenderedProductionPageShowsTheUserMessage(): void
	{
		$output = $this->render(new TUserException('Your session has expired.'), TApplicationMode::Normal);
		$this->assertStringContainsString('Your session has expired.', $output);
		$this->assertStringNotContainsString('An internal server error occurred.', $output);
	}

	public function testRenderedDebugPageShowsTheDeveloperMessage(): void
	{
		$output = $this->render(new TConfigurationException('component_property_undefined', 'TPage', 'Foo'), TApplicationMode::Debug);
		$this->assertStringContainsString('TPage.Foo', $output);
	}

	// ── Language and translation ─────────────────────────────────────────────

	public function testApplicationLanguageFollowsTheGlobalizationCulture(): void
	{
		$app = Prado::getApplication();
		$globalization = new TGlobalization();
		$globalization->setCulture('de-DE');
		$app->setGlobalization($globalization);
		$this->assertSame('de', Prado::getApplicationLanguage());
		$globalization->setCulture('zh_TW');
		$this->assertSame('zh', Prado::getApplicationLanguage());
		PradoUnit::setProp($app, '_globalization', null);
		$this->assertSame(Prado::getPreferredLanguage(), Prado::getApplicationLanguage());
	}

	public function testLocalizeWithoutTranslationConfigurationReturnsTheMessage(): void
	{
		PradoUnit::setProp(Prado::getApplication(), '_globalization', null);
		$e = new THttpException(404, 'pageservice_page_unknown', 'Home');
		$this->assertSame("Page 'Home' Not Found", $this->_handler->pubLocalizeErrorMessage($e));
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState(false)]
	public function testGlobalizationCultureSelectsTheVisitorLanguageFile(): void
	{
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en';
		$globalization = new TGlobalization();
		$globalization->setCulture('fr_FR');
		Prado::getApplication()->setGlobalization($globalization);
		$this->assertSame("La page 'Home' n'a pas été trouvée.", (new THttpException(404, 'pageservice_page_unknown', 'Home'))->getMessage());
		$this->assertSame("Une erreur interne du serveur s'est produite.", $this->_handler->pubGetExternalErrorMessage(new \RuntimeException('plain'), false));
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState(false)]
	public function testTranslationCatalogueOverridesTheVisitorMessage(): void
	{
		$dir = sys_get_temp_dir() . '/prado_i18n_' . uniqid();
		mkdir($dir);
		file_put_contents($dir . '/prado.fr.php', '<?php return ["trans-unit" => ['
			. '["source" => "errorhandler_internal_error", "target" => "Erreur interne (catalogue)."],'
			. '["source" => "pageservice_page_unknown", "target" => "Page {0} introuvable (catalogue)."]]];');
		try {
			$globalization = new TGlobalization();
			$globalization->setCulture('fr');
			// A TMap, as the XML configuration produces: Translation::init() reads optional keys without isset().
			PradoUnit::setProp($globalization, '_translation', new TMap(['type' => 'PHP', 'source' => $dir, 'catalogue' => 'prado', 'marker' => '@@']));
			Prado::getApplication()->setGlobalization($globalization);

			$this->assertSame('Page Home introuvable (catalogue).', $this->_handler->pubGetExternalErrorMessage(new THttpException(404, 'pageservice_page_unknown', 'Home'), false));
			$this->assertSame('Erreur interne (catalogue).', $this->_handler->pubGetExternalErrorMessage(new \RuntimeException('plain'), false));
			// A key the catalogue does not define keeps the message file text, without the marker.
			$this->assertSame("Le nom de la page est requis.", $this->_handler->pubGetExternalErrorMessage(new THttpException(404, 'pageservice_page_required'), false));
		} finally {
			unlink($dir . '/prado.fr.php');
			rmdir($dir);
		}
	}
}
