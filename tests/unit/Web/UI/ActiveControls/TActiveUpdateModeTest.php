<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Test\Unit\Harness\Web\UI\TTestCallbackPage;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\ActiveControls\TActivePanel;
use Prado\Web\UI\ActiveControls\TActiveUpdateMode;
use Prado\Web\UI\TControl;
use Prado\Web\UI\TPage;
use Prado\Web\UI\TSkinTemplate;
use Prado\Web\UI\TTemplate;
use Prado\Web\UI\TTheme;

/**
 * TActiveUpdateMode, TBaseActiveControl.UpdateMode, and TPage.CallbackUpdateMode.
 */
class TActiveUpdateModeTest extends TestCase
{
	public function testEnumValues()
	{
		$this->assertSame('Default', TActiveUpdateMode::Default);
		$this->assertSame('Replace', TActiveUpdateMode::Replace);
		$this->assertSame('Morph', TActiveUpdateMode::Morph);
	}

	public function testPageCallbackUpdateMode()
	{
		$page = new TPage();
		$this->assertSame(TActiveUpdateMode::Replace, $page->getCallbackUpdateMode());
		$page->setCallbackUpdateMode('morph');
		$this->assertSame(TActiveUpdateMode::Morph, $page->getCallbackUpdateMode());
		$page->setCallbackUpdateMode(TActiveUpdateMode::Default);
		$this->assertSame(TActiveUpdateMode::Replace, $page->getCallbackUpdateMode());
	}

	public function testPageCallbackUpdateModeRejectsUnknownValue()
	{
		$this->expectException(TInvalidDataValueException::class);
		(new TPage())->setCallbackUpdateMode('Patch');
	}

	public function testControlUpdateModeDefaults()
	{
		$panel = new TActivePanel();
		$this->assertSame(TActiveUpdateMode::Default, $panel->getActiveControl()->getUpdateMode());
		$this->assertFalse($panel->getActiveControl()->getIsMorphUpdate());
	}

	public function testControlUpdateModeRejectsUnknownValue()
	{
		$this->expectException(TInvalidDataValueException::class);
		(new TActivePanel())->getActiveControl()->setUpdateMode('Patch');
	}

	/**
	 * @dataProvider morphProvider
	 */
	public function testIsMorphUpdateResolvesThePageDefault(string $control, string $page, bool $expected)
	{
		$p = new TPage();
		$p->setCallbackUpdateMode($page);
		$panel = new TActivePanel();
		$p->getControls()->add($panel);
		$panel->getActiveControl()->setUpdateMode($control);
		$this->assertSame($expected, $panel->getActiveControl()->getIsMorphUpdate());
	}

	public static function morphProvider(): array
	{
		return [
			'default on a replace page' => [TActiveUpdateMode::Default, TActiveUpdateMode::Replace, false],
			'default on a morph page' => [TActiveUpdateMode::Default, TActiveUpdateMode::Morph, true],
			'replace on a morph page' => [TActiveUpdateMode::Replace, TActiveUpdateMode::Morph, false],
			'morph on a replace page' => [TActiveUpdateMode::Morph, TActiveUpdateMode::Replace, true],
		];
	}

	public function testPanelRenderSendsTheMorphArgument()
	{
		$page = new TTestCallbackPage();
		$page->setCallbackUpdateMode(TActiveUpdateMode::Morph);
		$panel = new TActivePanel();
		$panel->setID('Results');
		$page->getControls()->add($panel);
		PradoUnit::invoke($panel, 'setControlStage', TControl::CS_PRERENDERED);
		$writer = new \Prado\Web\UI\THtmlWriter(new \Prado\IO\TTextWriter());
		$panel->render($writer);
		$calls = $page->getClientFunctions();
		$this->assertCount(1, $calls);
		$params = $calls[0]['Prado.Element.replace'];
		$this->assertSame($panel->getClientID(), $params[0]);
		$this->assertCount(5, $params);
		$this->assertTrue($params[4]);
	}

	public function testASkinSetsTheUpdateModeForEveryControlOfAType()
	{
		$template = new TSkinTemplate('<com:Prado\\Web\\UI\\ActiveControls\\TActivePanel ActiveControl.UpdateMode="Morph" />', __DIR__);
		$skins = [];
		foreach ($template->getItems() as $item) {
			if (isset($item[TTemplate::TPL_PROPS])) {
				$skins[$item[TTemplate::TPL_TYPE]][0] = $item[TTemplate::TPL_PROPS];
			}
		}
		$theme = PradoUnit::reflectionClass(TTheme::class)->newInstanceWithoutConstructor();
		PradoUnit::setProp($theme, '_skins', $skins);
		foreach ([new TActivePanel(), new TActivePanel()] as $panel) {
			$this->assertTrue($theme->applySkin($panel));
			$this->assertSame(TActiveUpdateMode::Morph, $panel->getActiveControl()->getUpdateMode());
			$this->assertTrue($panel->getActiveControl()->getIsMorphUpdate());
		}
	}
}
