<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\ActiveControls\TCallbackClientScript;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TPanel;
use Prado\Web\UI\WebControls\TRequiredFieldValidator;
use Prado\Web\UI\WebControls\TUnsavedChangesGuard;
use Prado\Web\UI\WebControls\TWebControl;

/**
 * Records whether the client script hook fired, without publishing assets.
 */
class TUnsavedChangesGuardScriptSpy extends TUnsavedChangesGuard
{
	public bool $scriptRegistered = false;

	protected function registerClientScript()
	{
		$this->scriptRegistered = true;
	}
}

/**
 * A page in a callback, with a callback client of its own.
 */
class TUnsavedChangesGuardCallbackPage extends TPage
{
	private ?TCallbackClientScript $_client = null;

	public function getIsCallback()
	{
		return true;
	}

	public function getCallbackClient()
	{
		return $this->_client ??= new TCallbackClientScript();
	}
}

class TUnsavedChangesGuardTest extends TestCase
{
	use TWebControlRenderTrait;

	/**
	 * @return array{TUnsavedChangesGuard, TPage}
	 */
	private function makeOnPage(?TPage $page = null, string $class = TUnsavedChangesGuard::class): array
	{
		$page ??= new TPage();
		$guard = new $class();
		$guard->setID('Guard');
		$page->getControls()->add($guard);
		return [$guard, $page];
	}

	private function clientOptions(TUnsavedChangesGuard $guard): array
	{
		return PradoUnit::invoke($guard, 'getClientOptions');
	}

	private function failValidation(TPage $page): void
	{
		PradoUnit::setProp($page, '_postData', []);
		$validator = new TRequiredFieldValidator();
		$page->getControls()->add($validator);
		$page->getValidators()->add($validator);
		$validator->setIsValid(false);
	}

	public function testExtendsTWebControlAndRendersSpan()
	{
		[$guard] = $this->makeOnPage();
		$this->assertInstanceOf(TWebControl::class, $guard);
		$this->assertSame('<span id="Guard"></span>', $this->render($guard));
	}

	public function testClassMapResolvesShortName()
	{
		$this->assertSame(TUnsavedChangesGuard::class, Prado::usingClass('TUnsavedChangesGuard'));
	}

	public function testPackageIsRegistered()
	{
		[, $packages, $dependencies] = require PRADO_DIR . '/Web/Javascripts/packages.php';
		$this->assertSame(['prado/controls/unsavedchanges.js'], $packages['unsavedchanges']);
		$this->assertSame(['jquery', 'prado', 'unsavedchanges'], $dependencies['unsavedchanges']);
		$this->assertFileExists(PRADO_DIR . '/Web/Javascripts/source/prado/controls/unsavedchanges.js');
	}

	public function testDefaults()
	{
		$guard = new TUnsavedChangesGuard();
		$this->assertSame('', $guard->getControlToWatch());
		$this->assertTrue($guard->getWarnOnLeave());
		$this->assertSame('', $guard->getDirtyCssClass());
		$this->assertSame('', $guard->getStatusText());
		$this->assertFalse($guard->getRendersDirty());
	}

	public function testSetters()
	{
		$guard = new TUnsavedChangesGuard();
		$guard->setControlToWatch('Panel');
		$guard->setWarnOnLeave('false');
		$guard->setDirtyCssClass('changed edited');
		$guard->setStatusText('Unsaved changes');
		$this->assertSame('Panel', $guard->getControlToWatch());
		$this->assertFalse($guard->getWarnOnLeave());
		$this->assertSame('changed edited', $guard->getDirtyCssClass());
		$this->assertSame('Unsaved changes', $guard->getStatusText());
	}

	public function testClientOptions()
	{
		[$guard] = $this->makeOnPage();
		$guard->setDirtyCssClass('changed');
		$guard->setStatusText('Unsaved');
		$guard->setWarnOnLeave(false);
		$this->assertSame([
			'ID' => 'Guard',
			'ScopeID' => '',
			'WarnOnLeave' => false,
			'DirtyCssClass' => 'changed',
			'StatusText' => 'Unsaved',
			'Dirty' => false,
		], $this->clientOptions($guard));
	}

	public function testControlToWatchResolvesToItsClientID()
	{
		[$guard, $page] = $this->makeOnPage();
		$panel = new TPanel();
		$panel->setID('EditPanel');
		$page->getControls()->add($panel);
		$guard->setControlToWatch('EditPanel');
		$this->assertSame('EditPanel', $this->clientOptions($guard)['ScopeID']);
	}

	public function testControlToWatchInvalidThrows()
	{
		[$guard] = $this->makeOnPage();
		$guard->setControlToWatch('Missing');
		$this->expectException(TConfigurationException::class);
		$this->clientOptions($guard);
	}

	public function testStatusTextMakesStatusRegion()
	{
		[$guard] = $this->makeOnPage();
		$guard->setStatusText('Unsaved');
		$this->assertSame('<span id="Guard" role="status"></span>', $this->render($guard));
	}

	public function testRoleOverridesStatusRole()
	{
		[$guard] = $this->makeOnPage();
		$guard->setStatusText('Unsaved');
		$guard->setRole('alert');
		$this->assertSame('<span id="Guard" role="alert"></span>', $this->render($guard));
	}

	public function testRendersEncodedStatusTextWhenDirty()
	{
		[$guard] = $this->makeOnPage();
		$guard->setStatusText('Unsaved <changes>');
		$guard->markDirty();
		$this->assertSame('<span id="Guard" role="status">Unsaved &lt;changes&gt;</span>', $this->render($guard));
		$this->assertTrue($this->clientOptions($guard)['Dirty']);
	}

	public function testMarkCleanAndMarkDirtySetTheRenderedState()
	{
		[$guard] = $this->makeOnPage();
		$guard->markDirty();
		$this->assertTrue($guard->getRendersDirty());
		$guard->markClean();
		$this->assertFalse($guard->getRendersDirty());
	}

	public function testFailedPostBackRendersDirty()
	{
		[$guard, $page] = $this->makeOnPage();
		$this->failValidation($page);
		$this->assertTrue($guard->getRendersDirty());
		$guard->markClean();
		$this->assertFalse($guard->getRendersDirty());
	}

	public function testValidPostBackRendersClean()
	{
		[$guard, $page] = $this->makeOnPage();
		$this->failValidation($page);
		$page->getValidators()->itemAt(0)->setIsValid(true);
		$this->assertFalse($guard->getRendersDirty());
	}

	public function testFailedValidatorWithoutPostBackRendersClean()
	{
		[$guard, $page] = $this->makeOnPage();
		$validator = new TRequiredFieldValidator();
		$page->getControls()->add($validator);
		$page->getValidators()->add($validator);
		$validator->setIsValid(false);
		$this->assertFalse($guard->getRendersDirty());
	}

	public function testMarkCleanInCallbackCallsTheClient()
	{
		[$guard, $page] = $this->makeOnPage(new TUnsavedChangesGuardCallbackPage());
		$guard->markClean();
		$guard->markDirty();
		$this->assertSame([
			['Prado.WebUI.TUnsavedChangesGuard.markClean' => ['Guard']],
			['Prado.WebUI.TUnsavedChangesGuard.markDirty' => ['Guard']],
		], $page->getCallbackClient()->getClientFunctionsToExecute());
	}

	public function testMarkCleanOutsideCallbackCallsNoClient()
	{
		[$guard, $page] = $this->makeOnPage();
		$guard->markClean();
		$this->assertSame([], $page->getCallbackClient()->getClientFunctionsToExecute());
	}

	public function testMarkCleanWithoutPage()
	{
		$guard = new TUnsavedChangesGuard();
		$guard->markDirty();
		$this->assertTrue($guard->getRendersDirty());
	}

	public function testOnPreRenderRegistersScript()
	{
		[$guard] = $this->makeOnPage(null, TUnsavedChangesGuardScriptSpy::class);
		$guard->onPreRender(null);
		$this->assertTrue($guard->scriptRegistered);
	}

	public function testOnPreRenderSkipsScriptWhenDisabled()
	{
		[$guard] = $this->makeOnPage(null, TUnsavedChangesGuardScriptSpy::class);
		$guard->setEnabled(false);
		$guard->onPreRender(null);
		$this->assertFalse($guard->scriptRegistered);
	}

	public function testOnPreRenderSkipsScriptWithoutJavaScript()
	{
		[$guard, $page] = $this->makeOnPage(null, TUnsavedChangesGuardScriptSpy::class);
		$page->setClientSupportsJavaScript(false);
		$guard->onPreRender(null);
		$this->assertFalse($guard->scriptRegistered);
	}

	public function testClientClassName()
	{
		$this->assertSame('Prado.WebUI.TUnsavedChangesGuard', PradoUnit::invoke(new TUnsavedChangesGuard(), 'getClientClassName'));
	}
}
