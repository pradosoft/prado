<?php

namespace Prado\Test\Unit\Data\ActiveRecord\Scaffold;

use Prado\Data\ActiveRecord\Scaffold\TScaffoldEditView;
use Prado\Data\ActiveRecord\Scaffold\TScaffoldListView;
use Prado\Data\ActiveRecord\Scaffold\TScaffoldSearch;
use Prado\Data\ActiveRecord\Scaffold\TScaffoldView;
use Prado\Web\UI\WebControls\TButton;

class TScaffoldViewTest extends ScaffoldTestCase
{
	private function newView(): TScaffoldView
	{
		$view = new TScaffoldView();
		$view->setID('view');
		$view->setRecordClass(ScaffoldItemRecord::class);
		$this->add($view);
		return $view;
	}

	private function display(): void
	{
		$this->page->runInit();
		$this->page->runLoad();
		$this->page->runPreRender();
	}

	private function assertListShown(TScaffoldView $view): void
	{
		$this->assertTrue($view->getListView()->getVisible(false));
		$this->assertFalse($view->getEditView()->getVisible(false));
		$this->assertTrue($view->_panForNewButton->getVisible(false));
		$this->assertTrue($view->_panForSearch->getVisible(false));
	}

	private function assertEditShown(TScaffoldView $view): void
	{
		$this->assertFalse($view->getListView()->getVisible(false));
		$this->assertTrue($view->getEditView()->getVisible(false));
		$this->assertFalse($view->_panForNewButton->getVisible(false));
		$this->assertFalse($view->_panForSearch->getVisible(false));
		$this->assertTrue($view->getEditView()->getCancelButton()->getVisible(false));
		$this->assertFalse($view->getEditView()->getClearButton()->getVisible(false));
	}

	public function testChildControls()
	{
		$view = $this->newView();

		$this->assertInstanceOf(TScaffoldListView::class, $view->getListView());
		$this->assertInstanceOf(TScaffoldEditView::class, $view->getEditView());
		$this->assertInstanceOf(TScaffoldSearch::class, $view->getSearchControl());
		$this->assertInstanceOf(TButton::class, $view->getAddButton());
		$this->assertSame('new', $view->getAddButton()->getCommandName());
		$this->assertSame('_editView', $view->getListView()->getEditViewID());
		$this->assertSame('_listView', $view->getSearchControl()->getListViewID());
		$this->assertListShown($view);
	}

	public function testPreRender_copiesRecordDetailsToTheChildren()
	{
		$view = $this->newView();
		$view->setEnableDefaultStyle(false);
		$this->display();

		foreach ([$view->getListView(), $view->getEditView(), $view->getSearchControl()] as $child) {
			$this->assertSame(ScaffoldItemRecord::class, $child->getRecordClass());
			$this->assertFalse($child->getEnableDefaultStyle());
		}
		$this->assertSame([], $this->styleSheets());
		$this->assertSame(10, $view->getListView()->getList()->getItems()->getCount());
	}

	public function testEditCommand_fromAListItemShowsTheEditView()
	{
		$view = $this->newView();
		$this->display();

		$item = $view->getListView()->getList()->getItems()->itemAt(1);
		foreach ($item->findControlsByType(TButton::class) as $button) {
			if ($button->getCommandName() === 'edit') {
				$button->onCommand($this->command('edit'));
			}
		}

		$this->assertEditShown($view);
		$this->assertSame([2], $view->getEditView()->getRecordPk());
	}

	public function testNewCommand_showsAnEmptyEditView()
	{
		$view = $this->newView();
		$this->display();
		$view->getEditView()->setRecordPk(3);

		$view->getAddButton()->onCommand($this->command('new'));

		$this->assertEditShown($view);
		$this->assertNull($view->getEditView()->getRecordPk());
		$this->assertSame(4, $view->getEditView()->_repeater->getItems()->getCount());
	}

	public function testOtherCommands_showTheListView()
	{
		$view = $this->newView();
		$this->display();

		foreach (['cancel', 'save', 'search'] as $command) {
			$view->bubbleEvent($view, $this->command('edit'));
			$this->assertEditShown($view);
			$view->bubbleEvent($view, $this->command($command));
			$this->assertListShown($view);
		}
	}

	public function testSaveFromTheEditView_returnsToTheList()
	{
		$view = $this->newView();
		$this->display();
		$view->getAddButton()->onCommand($this->command('new'));
		foreach ($view->getEditView()->_repeater->getItems() as $item) {
			if ($item->getCustomData() === 'item_name') {
				$item->_input->findControl('scaffold_input')->setText('added');
			}
		}

		$this->page->validate($view->getEditView()->getValidationGroup());
		$view->getEditView()->getSaveButton()->onCommand($this->command('save'));

		$this->assertListShown($view);
		$this->assertContains('added', ScaffoldFixture::names());
	}
}
