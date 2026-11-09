<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Prado;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\TAssetManager;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TContextMenu;
use Prado\Web\UI\WebControls\TDropdownMenu;
use Prado\Web\UI\WebControls\TPanel;

/**
 * Records published paths instead of copying files.
 */
class TContextMenuTestAssetManager extends TAssetManager
{
	public function publishFilePath($path, $checkTimestamp = false)
	{
		return '/assets/' . basename($path);
	}
}

/**
 * Client script manager that records the script packages a control registers.
 */
class TContextMenuTestClientScript extends TClientScriptManager
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

class TContextMenuTest extends TestCase
{
	use TWebControlRenderTrait;

	private mixed $_assetManager;

	protected function setUp(): void
	{
		$this->_assetManager = PradoUnit::getProp(Prado::getApplication(), '_assetManager');
		Prado::getApplication()->setAssetManager(new TContextMenuTestAssetManager());
	}

	protected function tearDown(): void
	{
		PradoUnit::setProp(Prado::getApplication(), '_assetManager', $this->_assetManager);
	}

	/**
	 * @return array{TPage, TContextMenu} a context menu "menu" for the panel "photo" in a page
	 */
	private function newPageMenu(): array
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_clientScript', new TContextMenuTestClientScript($page));
		$panel = new TPanel();
		$panel->setID('photo');
		$page->getControls()->add($panel);
		$menu = new TContextMenu();
		$menu->setID('menu');
		$menu->setForControl('photo');
		$page->getControls()->add($menu);
		return [$page, $menu];
	}

	public function testExtendsDropdownMenu()
	{
		$this->assertInstanceOf(TDropdownMenu::class, new TContextMenu());
	}

	public function testHasNoButtonAndNoPlacement()
	{
		[$page, $menu] = $this->newPageMenu();
		$menu->setButtonText('Actions');
		$menu->setPlacement('Top');
		$this->assertSame('', $menu->getButtonText());
		$menu->getControls()->add('<button type="button">Rotate</button>');
		$this->assertSame(
			'<div id="menu" popover="auto" class="prado-popover prado-menu"><button type="button">Rotate</button></div>',
			$this->render($menu)
		);
	}

	public function testTargetClientID()
	{
		[$page, $menu] = $this->newPageMenu();
		$this->assertSame('photo', $menu->getTargetClientID());
		$menu->setForControl(' gallery ');
		$this->assertSame('gallery', $menu->getForControl());
		$this->assertSame('gallery', $menu->getTargetClientID());
		$menu->setForControl('');
		$this->assertSame('', $menu->getTargetClientID());
	}

	public function testPreRenderRegistersClientControlWithTarget()
	{
		[$page, $menu] = $this->newPageMenu();
		$menu->onPreRender(null);
		$cs = $page->getClientScript();
		$this->assertContains('menu', $cs->pradoScripts);
		$this->assertTrue($cs->isStyleSheetFileRegistered('/assets/menu.css'));
		$scripts = implode("\n", PradoUnit::getProp($cs, '_endScripts'));
		$this->assertStringContainsString("new Prado.WebUI.TContextMenu({'ID':\"menu\",'TargetID':\"photo\"", $scripts);
	}

	public function testPreRenderWithoutTargetRegistersOnlyStyleSheets()
	{
		[$page, $menu] = $this->newPageMenu();
		$menu->setForControl('');
		$menu->onPreRender(null);
		$cs = $page->getClientScript();
		$this->assertNotContains('menu', $cs->pradoScripts);
		$this->assertSame([], PradoUnit::getProp($cs, '_endScripts'));
		$this->assertTrue($cs->isStyleSheetFileRegistered('/assets/popover.css'));
	}
}
