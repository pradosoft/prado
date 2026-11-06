<?php

namespace Prado\Test\Unit\Data\ActiveRecord\Scaffold;

use Prado\Data\ActiveRecord\Scaffold\TScaffoldEditView;
use Prado\Exceptions\TConfigurationException;
use Prado\Web\UI\WebControls\TButton;
use Prado\Web\UI\WebControls\TRepeater;
use Prado\Web\UI\WebControls\TRepeaterItem;

class TScaffoldEditViewTest extends ScaffoldTestCase
{
	private function newEditView(bool $visible = true, ?BubbleRecorder $container = null): TScaffoldEditView
	{
		$edit = new TScaffoldEditView();
		$edit->setID('edit');
		$edit->setRecordClass(ScaffoldItemRecord::class);
		$edit->setVisible($visible);
		if ($container !== null) {
			$container->getControls()->add($edit);
			$this->add($container);
		} else {
			$this->add($edit);
		}
		return $edit;
	}

	private function display(): void
	{
		$this->page->runInit();
		$this->page->runLoad();
	}

	private function repeater(TScaffoldEditView $edit): TRepeater
	{
		return $edit->_repeater;
	}

	private function item(TScaffoldEditView $edit, string $column): TRepeaterItem
	{
		foreach ($this->repeater($edit)->getItems() as $item) {
			if ($item->getCustomData() === $column) {
				return $item;
			}
		}
		$this->fail("No input for {$column}");
	}

	private function inputText(TScaffoldEditView $edit, string $column): string
	{
		return $this->item($edit, $column)->_input->findControl('scaffold_input')->getText();
	}

	private function setInputText(TScaffoldEditView $edit, string $column, string $text): void
	{
		$this->item($edit, $column)->_input->findControl('scaffold_input')->setText($text);
	}

	private function row(int $id): ?array
	{
		$row = ScaffoldFixture::getConnection()->createCommand('SELECT * FROM scaffold_items WHERE id = ' . $id)->queryRow();
		return $row === false ? null : $row;
	}

	public function testButtonsAndValidationGroup()
	{
		$edit = $this->newEditView();

		$this->assertInstanceOf(TButton::class, $edit->getSaveButton());
		$this->assertSame('save', $edit->getSaveButton()->getCommandName());
		$this->assertSame('clear', $edit->getClearButton()->getCommandName());
		$this->assertSame('cancel', $edit->getCancelButton()->getCommandName());
		$this->assertFalse($edit->getCancelButton()->getVisible(false));
		$this->assertFalse($edit->getClearButton()->getCausesValidation());
		$this->assertSame('group_' . $edit->getUniqueID(), $edit->getValidationGroup());
		$this->display();
		$this->page->runPreRender();
		$this->assertSame($edit->getValidationGroup(), $edit->getSaveButton()->getValidationGroup());
	}

	public function testProperties()
	{
		$edit = new TScaffoldEditView();

		$this->assertSame('', $edit->getEditRenderer());
		$this->assertNull($edit->getRecordPk());
		$edit->setEditRenderer(ScaffoldEditRendererFixture::class);
		$this->assertSame(ScaffoldEditRendererFixture::class, $edit->getEditRenderer());

		$edit->setRecordPk(4);
		$this->assertSame([4], $edit->getRecordPk());
		$edit->setRecordPk(['4']);
		$this->assertSame(['4'], $edit->getRecordPk());
		$edit->setRecordPk([]);
		$this->assertNull($edit->getRecordPk());
		$edit->setRecordPk(null);
		$this->assertNull($edit->getRecordPk());
	}

	public function testLoad_visibleViewBuildsOneInputPerColumn()
	{
		$edit = $this->newEditView();
		$this->display();

		$columns = [];
		foreach ($this->repeater($edit)->getItems() as $item) {
			$columns[] = $item->getCustomData();
		}
		$this->assertSame(['id', 'item_name', 'notes', 'price'], $columns);
		$this->assertSame('Item Name:', $this->item($edit, 'item_name')->_label->getText());
		$this->assertFalse($this->item($edit, 'id')->_input->findControl('scaffold_input')->getEnabled(), 'sequence key');
		$this->assertSame('', $this->inputText($edit, 'item_name'));
		$this->assertSame(\Prado\Web\UI\WebControls\TTextBoxMode::MultiLine, $this->item($edit, 'notes')->_input->findControl('scaffold_input')->getTextMode());
	}

	public function testLoad_hiddenViewBuildsNothing()
	{
		$edit = $this->newEditView(false);
		$this->display();

		$this->assertCount(0, $this->repeater($edit)->getItems());
	}

	public function testLoad_existingRecordFillsInputs()
	{
		$edit = $this->newEditView();
		$edit->setRecordPk(4);
		$this->display();

		$this->assertSame('4', $this->inputText($edit, 'id'));
		$this->assertSame('item 04', $this->inputText($edit, 'item_name'));
		$this->assertSame('note 4', $this->inputText($edit, 'notes'));
		$this->assertSame('4.5', $this->inputText($edit, 'price'));
	}

	public function testLoad_unknownKeyThrows()
	{
		$edit = $this->newEditView();
		$edit->setRecordPk(99);

		try {
			$this->display();
			$this->fail('TConfigurationException expected');
		} catch (TConfigurationException $e) {
			$this->assertSame('scaffold_invalid_record_pk', $e->getErrorCode());
			$this->assertStringContainsString(ScaffoldItemRecord::class, $e->getMessage());
			$this->assertStringContainsString("'99'", $e->getMessage());
		}
	}

	public function testLoad_missingRecordClassThrows()
	{
		$edit = $this->newEditView();
		$edit->setRecordClass(null);

		try {
			$this->display();
			$this->fail('TConfigurationException expected');
		} catch (TConfigurationException $e) {
			$this->assertSame('scaffold_invalid_record_class', $e->getErrorCode());
			$this->assertStringContainsString("'edit'", $e->getMessage());
		}
	}

	public function testRestoredItemsWithoutDataGetNoInputs()
	{
		$edit = $this->newEditView(false);
		$this->display();
		$repeater = $this->repeater($edit);
		$repeater->setViewState('ItemCount', 2);
		$repeater->loadState();

		$this->assertCount(2, $repeater->getItems());
		foreach ($repeater->getItems() as $item) {
			$this->assertNull($item->getCustomData());
			$this->assertNull($item->_input->findControl('scaffold_input'));
		}
	}

	public function testSave_insertsNewRecord()
	{
		$container = new BubbleRecorder();
		$edit = $this->newEditView(true, $container);
		$this->display();
		$this->setInputText($edit, 'item_name', 'new item');
		$this->setInputText($edit, 'notes', 'new note');
		$this->setInputText($edit, 'price', '7.25');

		$this->page->validate($edit->getValidationGroup());
		$this->assertFalse($edit->bubbleEvent($edit->getSaveButton(), $this->command('save')), 'a saved record lets the command bubble');

		$row = $this->row(ScaffoldFixture::ROWS + 1);
		$this->assertSame('new item', $row['item_name']);
		$this->assertSame('new note', $row['notes']);
		$this->assertSame('7.25', (string) $row['price']);
	}

	public function testSave_updatesExistingRecord()
	{
		$edit = $this->newEditView();
		$edit->setRecordPk(5);
		$this->display();
		$this->setInputText($edit, 'item_name', 'renamed');

		$this->page->validate($edit->getValidationGroup());
		$edit->getSaveButton()->onCommand($this->command('save'));

		$this->assertSame('renamed', $this->row(5)['item_name']);
		$this->assertSame('note 4', $this->row(4)['notes']);
		$this->assertCount(ScaffoldFixture::ROWS, ScaffoldFixture::names());
	}

	public function testSave_invalidInputStopsTheCommand()
	{
		$edit = $this->newEditView();
		$edit->setRecordPk(5);
		$this->display();
		$this->setInputText($edit, 'item_name', 'renamed');
		$this->setInputText($edit, 'price', 'not a number');

		$this->page->validate($edit->getValidationGroup());
		$this->assertTrue($edit->bubbleEvent($edit->getSaveButton(), $this->command('save')));

		$this->assertSame('item 05', $this->row(5)['item_name']);
	}

	public function testClear_startsANewRecord()
	{
		$edit = $this->newEditView();
		$edit->setRecordPk(5);
		$this->display();
		$this->assertSame('item 05', $this->inputText($edit, 'item_name'));

		$this->assertFalse($edit->bubbleEvent($edit->getClearButton(), $this->command('Clear')));

		$this->assertNull($edit->getRecordPk());
		$this->assertSame('', $this->inputText($edit, 'item_name'));
	}

	public function testOtherCommand_bubbles()
	{
		$edit = $this->newEditView();
		$this->display();

		$this->assertFalse($edit->bubbleEvent($edit->getCancelButton(), $this->command('cancel')));
	}

	public function testEditRenderer_replacesTheDefaultInputs()
	{
		$edit = $this->newEditView();
		$edit->setEditRenderer(ScaffoldEditRendererFixture::class);
		$edit->setRecordPk(6);
		$this->display();

		$renderers = $edit->findControlsByType(ScaffoldEditRendererFixture::class);
		$this->assertCount(1, $renderers);
		$renderer = $renderers[0];
		$this->assertNull($edit->findControl('_repeater'));
		$this->assertSame('item 06', $renderer->getData()->item_name);

		$edit->setRecordPk(7);
		$edit->initializeEditForm();
		$this->assertCount(1, $edit->findControlsByType(ScaffoldEditRendererFixture::class));
		$this->assertSame(2, $renderer->dataSets);
		$this->assertSame('item 07', $renderer->getData()->item_name);

		$this->page->validate($edit->getValidationGroup());
		$this->assertFalse($edit->bubbleEvent($edit->getSaveButton(), $this->command('save')));
		$this->assertSame(1, $renderer->updates);
		$this->assertSame('from renderer', $this->row(7)['item_name']);
	}

	public function testEditRenderer_invalidPageKeepsTheInput()
	{
		$edit = $this->newEditView();
		$edit->setEditRenderer(ScaffoldEditRendererFixture::class);
		$edit->setRecordPk(6);
		$this->display();
		$renderer = $edit->findControlsByType(ScaffoldEditRendererFixture::class)[0];
		$validator = new \Prado\Web\UI\WebControls\TCustomValidator();
		$validator->attachEventHandler('OnServerValidate', fn ($sender, $param) => $param->setIsValid(false));
		$this->add($validator);

		$this->page->validate();
		$this->assertTrue($edit->bubbleEvent($edit->getSaveButton(), $this->command('save')));

		$this->assertSame(1, $renderer->updates);
		$this->assertSame('from renderer', $renderer->getData()->item_name);
		$this->assertSame('item 06', $this->row(6)['item_name']);
	}

	public function testEditRenderer_mustImplementTheInterface()
	{
		$edit = $this->newEditView();
		$edit->setEditRenderer(\Prado\Web\UI\WebControls\TLabel::class);

		try {
			$this->display();
			$this->fail('TConfigurationException expected');
		} catch (TConfigurationException $e) {
			$this->assertSame('scaffold_invalid_edit_renderer', $e->getErrorCode());
			$this->assertStringContainsString("'edit'", $e->getMessage());
			$this->assertStringContainsString(ScaffoldItemRecord::class, $e->getMessage());
		}
	}
}
