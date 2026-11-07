<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TNotSupportedException;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TCheckBoxList;
use Prado\Web\UI\WebControls\TListItem;
use Prado\Web\UI\WebControls\TRadioButtonItem;
use Prado\Web\UI\WebControls\TRadioButtonList;

/**
 * Client script manager that records postback control registrations.
 */
class TRadioButtonListTestClientScript extends TClientScriptManager
{
	private array $_postBackControls = [];

	public function getPostBackControls(): array
	{
		return $this->_postBackControls;
	}

	public function registerPostBackControl($class, $options)
	{
		$this->_postBackControls[] = [$class, $options];
	}
}

class TRadioButtonListTest extends TestCase
{
	use TWebControlRenderTrait;

	private function createPageList(array $items = ['a' => 'A', 'b' => 'B', 'c' => 'C']): array
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_inFormRender', true);
		PradoUnit::setProp($page, '_clientScript', new TRadioButtonListTestClientScript($page));
		$list = new TRadioButtonList();
		$list->setID('List');
		foreach ($items as $value => $text) {
			$list->getItems()->add(new TListItem($text, (string) $value));
		}
		$page->getControls()->add($list);
		return [$page, $list];
	}

	// ================================================================================
	// Basics
	// ================================================================================

	// ================================================================================
	// Selection
	// ================================================================================

	// ================================================================================
	// Post data
	// ================================================================================

	public function testLoadPostDataIgnoredWhenDisabled()
	{
		[$page, $list] = $this->createPageList();
		$list->setEnabled(false);
		$this->assertFalse($list->loadPostData('List', ['List' => 'b']));
		$this->assertSame(-1, $list->getSelectedIndex());
		$this->assertFalse($list->getDataChanged());

		$list->setEnabled(true);
		$page->setEnabled(false);
		$this->assertFalse($list->loadPostData('List', ['List' => 'b']));
		$this->assertSame(-1, $list->getSelectedIndex());
	}

	// ================================================================================
	// Rendering
	// ================================================================================

	// ================================================================================
	// TRadioButtonItem
	// ================================================================================

}
