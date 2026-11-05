<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TBulletedList;
use Prado\Web\UI\WebControls\TBulletedListDisplayMode;
use Prado\Web\UI\WebControls\TBulletStyle;
use Prado\Web\UI\WebControls\TListItem;
use Prado\Web\UI\WebControls\TMenuList;

/**
 * Client script manager that records postback control registrations.
 */
class TMenuListTestClientScript extends TClientScriptManager
{
	public array $postBackControls = [];

	public function registerPostBackControl($class, $options)
	{
		$this->postBackControls[] = $class;
	}
}

class TMenuListTest extends TestCase
{
	use TWebControlRenderTrait;

	private function menu(array $items = ['cut' => 'Cut', 'copy' => 'Copy']): TMenuList
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_inFormRender', true);
		PradoUnit::setProp($page, '_clientScript', new TMenuListTestClientScript($page));
		$menu = new TMenuList();
		$menu->setID('Menu');
		foreach ($items as $value => $text) {
			$menu->getItems()->add(new TListItem($text, $value));
		}
		$page->getControls()->add($menu);
		return $menu;
	}

	public function testExtendsTBulletedList()
	{
		$this->assertInstanceOf(TBulletedList::class, new TMenuList());
	}

	public function testRendersMenuWithItems()
	{
		$this->assertSame("<menu id=\"Menu\">\n<li>Cut</li>\n<li>Copy</li>\n</menu>", $this->render($this->menu()));
	}

	public function testNumberedStyleKeepsMenuTag()
	{
		$menu = $this->menu();
		$menu->setBulletStyle(TBulletStyle::Numbered);
		$this->assertStringStartsWith('<menu id="Menu" style="list-style-type:decimal;">', $this->render($menu));
	}

	public function testLinkButtonItemsPostBack()
	{
		$menu = $this->menu();
		$menu->setDisplayMode(TBulletedListDisplayMode::LinkButton);
		$output = $this->render($menu);
		$this->assertStringStartsWith('<menu id="Menu">', $output);
		$this->assertStringContainsString('<a id="Menu0" href="javascript:;//Menu0">Cut</a>', $output);
		$this->assertSame(['Prado.WebUI.TBulletedList', 'Prado.WebUI.TBulletedList'], $menu->getPage()->getClientScript()->postBackControls);
	}

	public function testNoItemsRendersNothing()
	{
		$this->assertSame('', $this->render($this->menu([])));
	}
}
