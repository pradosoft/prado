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
use Prado\Web\UI\WebControls\TPopover;
use Prado\Web\UI\WebControls\TPopoverPlacement;
use Prado\Web\UI\WebControls\TWebControl;

/**
 * Records published paths instead of copying files.
 */
class TPopoverTestAssetManager extends TAssetManager
{
	public array $published = [];

	public function publishFilePath($path, $checkTimestamp = false)
	{
		$this->published[] = $path;
		return '/assets/popover.css';
	}
}

class TPopoverTest extends TestCase
{
	use TWebControlRenderTrait;

	private mixed $_assetManager;

	protected function setUp(): void
	{
		$this->_assetManager = PradoUnit::getProp(Prado::getApplication(), '_assetManager');
	}

	protected function tearDown(): void
	{
		PradoUnit::setProp(Prado::getApplication(), '_assetManager', $this->_assetManager);
	}

	/**
	 * @return array{TPage, TPopover} a popover with the ID "help" in a page
	 */
	private function newPagePopover(): array
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_clientScript', new TClientScriptManager($page));
		$popover = new TPopover();
		$popover->setID('help');
		$page->getControls()->add($popover);
		return [$page, $popover];
	}

	public function testExtendsWebControl()
	{
		$this->assertInstanceOf(TWebControl::class, new TPopover());
	}

	public function testDefaultRendering()
	{
		[$page, $popover] = $this->newPagePopover();
		$popover->getControls()->add('Body');
		$this->assertSame(
			'<div id="help" popover="auto" class="prado-popover" data-placement="bottom">Body</div>',
			$this->render($popover)
		);
	}

	public function testPopoverDefaultsAutoAndAcceptsStates()
	{
		$popover = new TPopover();
		$this->assertSame('auto', $popover->getPopover());
		$popover->setPopover('manual');
		$this->assertSame('manual', $popover->getPopover());
		$popover->setPopover('hint');
		$this->assertStringContainsString('popover="hint"', $this->render($popover));
		$popover->setPopover(false);
		$this->assertSame('auto', $popover->getPopover());
		$popover->setPopover('');
		$this->assertSame('auto', $popover->getPopover());
	}

	public function testPopoverRestoresAutoWhileTrackingViewState()
	{
		$popover = new TPopover();
		$popover->trackViewState(true);
		$popover->setPopover('manual');
		$popover->setPopover(false);
		$this->assertSame('auto', $popover->getPopover());
	}

	public function testPopoverRejectsUnknownState()
	{
		$this->expectException(TInvalidDataValueException::class);
		(new TPopover())->setPopover('sticky');
	}

	public function testRendersIdWithoutExplicitID()
	{
		$page = new TPage();
		$popover = new TPopover();
		$page->getControls()->add($popover);
		$this->assertStringContainsString('id="' . $popover->getClientID() . '"', $this->render($popover));
	}

	public function testPlacement()
	{
		$popover = new TPopover();
		$this->assertSame(TPopoverPlacement::Bottom, $popover->getPlacement());
		foreach (['Top' => 'top', 'start' => 'start', 'End' => 'end', 'Bottom' => 'bottom'] as $value => $attribute) {
			$popover->setPlacement($value);
			$this->assertStringContainsString('data-placement="' . $attribute . '"', $this->render($popover));
		}
		$popover->setPlacement(TPopoverPlacement::None);
		$this->assertStringNotContainsString('data-placement', $this->render($popover));
	}

	public function testPlacementRejectsUnknown()
	{
		$this->expectException(TInvalidDataValueException::class);
		(new TPopover())->setPlacement('Left');
	}

	public function testCssClassFollowsPopoverClass()
	{
		$popover = new TPopover();
		$popover->setCssClass('wide prado-popover tips');
		$this->assertStringContainsString('class="prado-popover wide tips"', $this->render($popover));
	}

	public function testNoButtonByDefault()
	{
		[$page, $popover] = $this->newPagePopover();
		$this->assertSame('', $popover->getButtonText());
		$this->assertStringNotContainsString('<button', $this->render($popover));
	}

	public function testButtonTextRendersInvokerBeforePopover()
	{
		[$page, $popover] = $this->newPagePopover();
		$popover->setButtonText('Help');
		$popover->setButtonCssClass('btn');
		$this->assertSame('help_button', $popover->getButtonClientID());
		$output = $this->render($popover);
		$this->assertStringStartsWith(
			'<button type="button" id="help_button" class="btn" commandfor="help" command="toggle-popover">Help</button><div id="help"',
			$output
		);
	}

	public function testButtonTextEncode()
	{
		[$page, $popover] = $this->newPagePopover();
		$popover->setButtonText('<b>Help</b>');
		$this->assertStringContainsString('><b>Help</b></button>', $this->render($popover));
		$popover->setEncode(true);
		$this->assertStringContainsString('>&lt;b&gt;Help&lt;/b&gt;</button>', $this->render($popover));
	}

	public function testCssUrlDefaultAndSet()
	{
		$popover = new TPopover();
		$this->assertSame('default', $popover->getCssUrl());
		$popover->setCssUrl('/theme/popover.css');
		$this->assertSame('/theme/popover.css', $popover->getCssUrl());
		$popover->setCssUrl('');
		$this->assertSame('', $popover->getCssUrl());
	}

	public function testPreRenderPublishesBundledStyleSheet()
	{
		Prado::getApplication()->setAssetManager($manager = new TPopoverTestAssetManager());
		[$page, $popover] = $this->newPagePopover();
		$popover->onPreRender(null);
		$this->assertSame(
			[dirname((new \ReflectionClass(TPopover::class))->getFileName()) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'popover.css'],
			$manager->published
		);
		$this->assertTrue($page->getClientScript()->isStyleSheetFileRegistered('/assets/popover.css'));
	}

	public function testPreRenderRegistersCustomStyleSheet()
	{
		Prado::getApplication()->setAssetManager($manager = new TPopoverTestAssetManager());
		[$page, $popover] = $this->newPagePopover();
		$popover->setCssUrl('/theme/popover.css');
		$popover->onPreRender(null);
		$this->assertSame([], $manager->published);
		$this->assertTrue($page->getClientScript()->isStyleSheetFileRegistered('/theme/popover.css'));
	}

	public function testPreRenderWithoutStyleSheet()
	{
		Prado::getApplication()->setAssetManager($manager = new TPopoverTestAssetManager());
		[$page, $popover] = $this->newPagePopover();
		$popover->setCssUrl('');
		$popover->onPreRender(null);
		$this->assertSame([], $manager->published);
		$this->assertFalse($page->getClientScript()->isStyleSheetFileRegistered('/assets/popover.css'));
	}
}
