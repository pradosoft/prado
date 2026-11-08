<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Prado;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\TAssetManager;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TDropdownMenu;
use Prado\Web\UI\WebControls\TPopover;
use Prado\Web\UI\WebControls\TPopoverPlacement;

/**
 * Records published paths instead of copying files.
 */
class TDropdownMenuTestAssetManager extends TAssetManager
{
	public array $published = [];

	public function publishFilePath($path, $checkTimestamp = false)
	{
		$this->published[] = basename($path);
		return '/assets/' . basename($path);
	}
}

/**
 * Client script manager that records the script packages a control registers.
 */
class TDropdownMenuTestClientScript extends TClientScriptManager
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

class TDropdownMenuTest extends TestCase
{
	use TWebControlRenderTrait;

	private mixed $_assetManager;

	protected function setUp(): void
	{
		$this->_assetManager = PradoUnit::getProp(Prado::getApplication(), '_assetManager');
		Prado::getApplication()->setAssetManager(new TDropdownMenuTestAssetManager());
	}

	protected function tearDown(): void
	{
		PradoUnit::setProp(Prado::getApplication(), '_assetManager', $this->_assetManager);
	}

	/**
	 * @return array{TPage, TDropdownMenu} a menu with the ID "actions" in a page
	 */
	private function newPageMenu(): array
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_clientScript', new TDropdownMenuTestClientScript($page));
		$menu = new TDropdownMenu();
		$menu->setID('actions');
		$page->getControls()->add($menu);
		return [$page, $menu];
	}

	public function testExtendsPopover()
	{
		$menu = new TDropdownMenu();
		$this->assertInstanceOf(TPopover::class, $menu);
		$this->assertSame('auto', $menu->getPopover());
		$this->assertSame(TPopoverPlacement::Bottom, $menu->getPlacement());
	}

	public function testRendersMenuButtonAndMenu()
	{
		[$page, $menu] = $this->newPageMenu();
		$menu->setButtonText('Actions');
		$menu->getControls()->add('<button type="button">Duplicate</button>');
		$this->assertSame(
			'<button aria-haspopup="menu" type="button" id="actions_button" commandfor="actions" command="toggle-popover">Actions</button>'
			. '<div id="actions" popover="auto" class="prado-popover prado-menu" data-placement="bottom"><button type="button">Duplicate</button></div>',
			$this->render($menu)
		);
	}

	public function testCssClassFollowsMenuClasses()
	{
		$menu = new TDropdownMenu();
		$menu->setCssClass('prado-menu wide prado-popover');
		$this->assertStringContainsString('class="prado-popover prado-menu wide"', $this->render($menu));
	}

	public function testPreRenderRegistersStyleSheetsAndClientControl()
	{
		[$page, $menu] = $this->newPageMenu();
		$menu->onPreRender(null);
		$cs = $page->getClientScript();
		$this->assertSame(['popover.css', 'menu.css'], Prado::getApplication()->getAssetManager()->published);
		$this->assertTrue($cs->isStyleSheetFileRegistered('/assets/popover.css'));
		$this->assertTrue($cs->isStyleSheetFileRegistered('/assets/menu.css'));
		$this->assertContains('menu', $cs->pradoScripts);
		$scripts = implode("\n", PradoUnit::getProp($cs, '_endScripts'));
		$this->assertStringContainsString("new Prado.WebUI.TDropdownMenu({'ID':\"actions\"});", $scripts);
	}

	public function testCustomCssUrlReplacesBothStyleSheets()
	{
		[$page, $menu] = $this->newPageMenu();
		$menu->setCssUrl('/theme/menu.css');
		$menu->onPreRender(null);
		$cs = $page->getClientScript();
		$this->assertSame([], Prado::getApplication()->getAssetManager()->published);
		$this->assertTrue($cs->isStyleSheetFileRegistered('/theme/menu.css'));
		$this->assertFalse($cs->isStyleSheetFileRegistered('/assets/menu.css'));
	}

	public function testEmptyCssUrlRegistersNoStyleSheet()
	{
		[$page, $menu] = $this->newPageMenu();
		$menu->setCssUrl('');
		$menu->onPreRender(null);
		$this->assertSame([], Prado::getApplication()->getAssetManager()->published);
		$this->assertContains('menu', $page->getClientScript()->pradoScripts);
	}
}
