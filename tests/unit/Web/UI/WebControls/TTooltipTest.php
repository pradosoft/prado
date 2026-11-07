<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Prado;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\TAssetManager;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TPopoverPlacement;
use Prado\Web\UI\WebControls\TTextBox;
use Prado\Web\UI\WebControls\TTooltip;

/**
 * Records published paths instead of copying files.
 */
class TTooltipTestAssetManager extends TAssetManager
{
	public array $published = [];

	public function publishFilePath($path, $checkTimestamp = false)
	{
		$this->published[] = $path;
		return '/assets/' . basename($path);
	}
}

/**
 * Client script manager that records the script packages a control registers.
 */
class TTooltipTestClientScript extends TClientScriptManager
{
	public array $pradoScripts = [];

	public function registerPradoScript($name)
	{
		$this->pradoScripts[] = $name;
	}

	protected function registerPradoScriptInternal($name)
	{
		$this->pradoScripts[] = $name;
	}
}

class TTooltipTest extends TestCase
{
	use TWebControlRenderTrait;

	private mixed $_assetManager;

	protected function setUp(): void
	{
		$this->_assetManager = PradoUnit::getProp(Prado::getApplication(), '_assetManager');
		Prado::getApplication()->setAssetManager(new TTooltipTestAssetManager());
	}

	protected function tearDown(): void
	{
		PradoUnit::setProp(Prado::getApplication(), '_assetManager', $this->_assetManager);
	}

	/**
	 * @return array{TPage, TTooltip} a tooltip "tip" for the text box "name" in a page
	 */
	private function newPageTooltip(): array
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_clientScript', new TTooltipTestClientScript($page));
		$box = new TTextBox();
		$box->setID('name');
		$page->getControls()->add($box);
		$tooltip = new TTooltip();
		$tooltip->setID('tip');
		$tooltip->setForControl('name');
		$page->getControls()->add($tooltip);
		return [$page, $tooltip];
	}

	public function testDefaultRendering()
	{
		[$page, $tooltip] = $this->newPageTooltip();
		$tooltip->setText('Your full name');
		$this->assertSame(
			'<div id="tip" popover="hint" role="tooltip" class="prado-tooltip" data-placement="top">Your full name</div>',
			$this->render($tooltip)
		);
	}

	public function testPopoverIsAlwaysHint()
	{
		$tooltip = new TTooltip();
		$tooltip->setPopover('auto');
		$this->assertSame('hint', $tooltip->getPopover());
	}

	public function testRoleIsAlwaysTooltip()
	{
		$tooltip = new TTooltip();
		$tooltip->setRole('note');
		$output = $this->render($tooltip);
		$this->assertStringContainsString('role="tooltip"', $output);
		$this->assertStringNotContainsString('role="note"', $output);
	}

	public function testBodyReplacesText()
	{
		$tooltip = new TTooltip();
		$tooltip->setText('Text');
		$tooltip->getControls()->add('<kbd>Ctrl</kbd> saves');
		$this->assertStringContainsString('><kbd>Ctrl</kbd> saves</div>', $this->render($tooltip));
	}

	public function testTextEncode()
	{
		$tooltip = new TTooltip();
		$tooltip->setText('<b>x</b>');
		$this->assertStringContainsString('><b>x</b></div>', $this->render($tooltip));
		$tooltip->setEncode(true);
		$this->assertStringContainsString('>&lt;b&gt;x&lt;/b&gt;</div>', $this->render($tooltip));
	}

	public function testCssClassFollowsTooltipClass()
	{
		$tooltip = new TTooltip();
		$tooltip->setCssClass('prado-tooltip wide');
		$this->assertStringContainsString('class="prado-tooltip wide"', $this->render($tooltip));
	}

	public function testPlacement()
	{
		$tooltip = new TTooltip();
		$this->assertSame(TPopoverPlacement::Top, $tooltip->getPlacement());
		$tooltip->setPlacement('End');
		$this->assertStringContainsString('data-placement="end"', $this->render($tooltip));
		$tooltip->setPlacement(TPopoverPlacement::None);
		$this->assertStringNotContainsString('data-placement', $this->render($tooltip));
		$this->expectException(TInvalidDataValueException::class);
		$tooltip->setPlacement('Above');
	}

	public function testDelays()
	{
		$tooltip = new TTooltip();
		$this->assertSame(300, $tooltip->getShowDelay());
		$this->assertSame(150, $tooltip->getHideDelay());
		$tooltip->setShowDelay('500');
		$tooltip->setHideDelay(-5);
		$this->assertSame(500, $tooltip->getShowDelay());
		$this->assertSame(0, $tooltip->getHideDelay());
	}

	public function testTargetClientID()
	{
		[$page, $tooltip] = $this->newPageTooltip();
		$this->assertSame('name', $tooltip->getTargetClientID());
		$tooltip->setForControl(' icon ');
		$this->assertSame('icon', $tooltip->getForControl());
		$this->assertSame('icon', $tooltip->getTargetClientID());
		$tooltip->setForControl('');
		$this->assertSame('', $tooltip->getTargetClientID());
	}

	public function testPreRenderRegistersStyleSheetAndClientControl()
	{
		[$page, $tooltip] = $this->newPageTooltip();
		$tooltip->setShowDelay(400);
		$tooltip->onPreRender(null);
		$cs = $page->getClientScript();
		$this->assertTrue($cs->isStyleSheetFileRegistered('/assets/tooltip.css'));
		$this->assertContains('tooltip', $cs->pradoScripts);
		$scripts = implode("\n", PradoUnit::getProp($cs, '_endScripts'));
		$this->assertStringContainsString('new Prado.WebUI.TTooltip(', $scripts);
		$this->assertStringContainsString("'TargetID':\"name\"", $scripts);
		$this->assertStringContainsString("'ShowDelay':400", $scripts);
		$this->assertStringContainsString("'HideDelay':150", $scripts);
	}

	public function testPreRenderWithoutTargetRegistersNoClientControl()
	{
		[$page, $tooltip] = $this->newPageTooltip();
		$tooltip->setForControl('');
		$tooltip->setCssUrl('');
		$tooltip->onPreRender(null);
		$cs = $page->getClientScript();
		$this->assertFalse($cs->isStyleSheetFileRegistered('/assets/tooltip.css'));
		$this->assertNotContains('tooltip', $cs->pradoScripts);
		$this->assertSame([], PradoUnit::getProp($cs, '_endScripts'));
	}

	public function testCustomCssUrl()
	{
		[$page, $tooltip] = $this->newPageTooltip();
		$this->assertSame('default', $tooltip->getCssUrl());
		$tooltip->setCssUrl('/theme/tip.css');
		$tooltip->onPreRender(null);
		$this->assertTrue($page->getClientScript()->isStyleSheetFileRegistered('/theme/tip.css'));
	}
}
