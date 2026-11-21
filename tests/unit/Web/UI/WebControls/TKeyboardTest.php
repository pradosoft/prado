<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TConfigurationException;
use Prado\Test\Unit\Harness\Traits\TAssetManagerMockTrait;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TKeyboard;
use Prado\Web\UI\WebControls\TTextBox;

class TKeyboardTest extends TestCase
{
	use TAssetManagerMockTrait;
	use TWebControlRenderTrait;

	protected function tearDown(): void
	{
		$this->restoreAssetManager();
		parent::tearDown();
	}

	private function createKeyboard(): array
	{
		$page = new TPage();
		$cs = $this->getMockBuilder(TClientScriptManager::class)->setConstructorArgs([$page])
			->onlyMethods(['registerStyleSheetFile', 'registerPradoScript', 'registerEndScript'])->getMock();
		PradoUnit::setProp($page, '_clientScript', $cs);
		$text = new TTextBox();
		$text->setID('pin');
		$page->getControls()->add($text);
		$keyboard = new TKeyboard();
		$keyboard->setID('kb');
		$page->getControls()->add($keyboard);
		return [$keyboard, $cs, $page];
	}

	public function testPropertyDefaultsAndSetters()
	{
		$keyboard = new TKeyboard();
		$this->assertSame('', $keyboard->getForControl());
		$this->assertTrue($keyboard->getAutoHide());
		$this->assertSame('Keyboard', $keyboard->getKeyboardCssClass());
		$this->assertSame('', $keyboard->getCssUrl());
		$this->assertSame('Prado.WebUI.TKeyboard', PradoUnit::invoke($keyboard, 'getClientClassName'));
		$keyboard->setForControl('pin');
		$keyboard->setAutoHide('false');
		$keyboard->setKeyboardCssClass('Keys');
		$keyboard->setCssUrl('/keys.css');
		$this->assertSame('pin', $keyboard->getForControl());
		$this->assertFalse($keyboard->getAutoHide());
		$this->assertSame('Keys', $keyboard->getKeyboardCssClass());
		$this->assertSame('/keys.css', $keyboard->getCssUrl());
	}

	public function testRenderHasTheClientIdOnlyWithJavaScript()
	{
		$page = new TPage();
		$keyboard = new TKeyboard();
		$page->getControls()->add($keyboard);
		$this->assertMatchesRegularExpression('/^<span id="ctl\d+"><\/span>$/', $this->render($keyboard));
		$page->setClientSupportsJavaScript(false);
		$this->assertSame('<span></span>', $this->render($keyboard));
	}

	public function testOnPreRenderPublishesTheStyleSheetAndRegistersTheScript()
	{
		$this->mockAssetManager();
		[$keyboard, $cs] = $this->createKeyboard();
		$keyboard->setForControl('pin');
		$keyboard->setToolTip('PIN pad');
		$cs->expects($this->once())->method('registerStyleSheetFile')->with('/assets/keyboard.css', '/assets/keyboard.css');
		$cs->expects($this->once())->method('registerPradoScript')->with('keyboard');
		$cs->expects($this->once())->method('registerEndScript')->with('prado:kb', 'new Prado.WebUI.TKeyboard({\'ID\':"kb",\'ForControl\':"pin",\'AutoHide\':true,\'CssClass\':"Keyboard",\'Label\':"PIN pad"});');
		$keyboard->onPreRender(null);
	}

	public function testOnPreRenderUsesTheCssUrlAndTheDefaultLabel()
	{
		[$keyboard, $cs] = $this->createKeyboard();
		$keyboard->setForControl('pin');
		$keyboard->setCssUrl('/keys.css');
		$cs->expects($this->once())->method('registerStyleSheetFile')->with('/keys.css', '/keys.css');
		$cs->expects($this->once())->method('registerEndScript')->with('prado:kb', $this->stringContains('\'Label\':"On-screen keyboard"'));
		$keyboard->onPreRender(null);
	}

	public function testOnPreRenderWithoutJavaScriptRegistersNothing()
	{
		[$keyboard, $cs, $page] = $this->createKeyboard();
		$page->setClientSupportsJavaScript(false);
		$cs->expects($this->never())->method('registerStyleSheetFile');
		$cs->expects($this->never())->method('registerEndScript');
		$keyboard->onPreRender(null);
	}

	public function testClientOptionsNeedForControl()
	{
		[$keyboard] = $this->createKeyboard();
		try {
			PradoUnit::invoke($keyboard, 'getClientOptions');
			$this->fail('Expected TConfigurationException');
		} catch (TConfigurationException $e) {
			$this->assertSame('keyboard_forcontrol_required', $e->getErrorCode());
		}
	}

	public function testClientOptionsNeedAnExistingForControl()
	{
		[$keyboard] = $this->createKeyboard();
		$keyboard->setForControl('missing');
		try {
			PradoUnit::invoke($keyboard, 'getClientOptions');
			$this->fail('Expected TConfigurationException');
		} catch (TConfigurationException $e) {
			$this->assertSame('keyboard_forcontrol_invalid', $e->getErrorCode());
		}
	}
}
