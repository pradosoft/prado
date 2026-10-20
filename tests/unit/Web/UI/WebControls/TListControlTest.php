<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Collections\TListItemCollection;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TNotSupportedException;
use Prado\IDataRenderer;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\TEventParameter;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TControlAdapter;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\IListControlAdapter;
use Prado\Web\UI\WebControls\TListControl;
use Prado\Web\UI\WebControls\TListItem;
use Prado\Web\UI\WebControls\TPanel;

/**
 * Concrete TListControl used to test the abstract base class.
 */
class TListControlTestList extends TListControl
{
	private ?bool $_multiSelect = null;

	public function setMultiSelect(?bool $value): void
	{
		$this->_multiSelect = $value;
	}

	protected function getIsMultiSelect()
	{
		return $this->_multiSelect ?? parent::getIsMultiSelect();
	}
}

/**
 * List control adapter that records the selection calls forwarded to it.
 */
class TListControlTestAdapter extends TControlAdapter implements IListControlAdapter
{
	private array $_calls = [];

	public function getCalls(): array
	{
		return $this->_calls;
	}

	public function setSelectedIndex($index)
	{
		$this->_calls[] = ['setSelectedIndex', $index];
	}

	public function setSelectedIndices($indices)
	{
		$this->_calls[] = ['setSelectedIndices', $indices];
	}

	public function setSelectedValue($value)
	{
		$this->_calls[] = ['setSelectedValue', $value];
	}

	public function setSelectedValues($values)
	{
		$this->_calls[] = ['setSelectedValues', $values];
	}

	public function clearSelection()
	{
		$this->_calls[] = ['clearSelection'];
	}
}

/**
 * Client script manager that records postback control registrations.
 */
class TListControlTestClientScript extends TClientScriptManager
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

/**
 * Data object with a getter and a public property for data binding tests.
 */
class TListControlTestRecord
{
	public $label;

	private $_code;

	public function __construct($label, $code)
	{
		$this->label = $label;
		$this->_code = $code;
	}

	public function getCode()
	{
		return $this->_code;
	}
}

class TListControlTest extends TestCase
{
	use TWebControlRenderTrait;

	private function createList(bool $multi = false, array $items = []): TListControlTestList
	{
		$list = new TListControlTestList();
		$list->setID('List');
		$list->setMultiSelect($multi);
		foreach ($items as $value => $text) {
			$list->getItems()->add(new TListItem($text, (string) $value));
		}
		return $list;
	}

	private function createPageList(bool $multi = false, array $items = []): array
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_inFormRender', true);
		PradoUnit::setProp($page, '_clientScript', new TListControlTestClientScript($page));
		$list = $this->createList($multi, $items);
		$page->getControls()->add($list);
		return [$page, $list];
	}

	private function bind(TListControl $list, $data): void
	{
		$list->setDataSource($data);
		$list->dataBind();
	}

	private function itemSummary(TListControl $list): array
	{
		$result = [];
		foreach ($list->getItems() as $item) {
			$result[] = [$item->getValue(), $item->getText()];
		}
		return $result;
	}

	// ================================================================================
	// Basics
	// ================================================================================

	public function testIsDataRenderer()
	{
		$this->assertInstanceOf(IDataRenderer::class, new TListControlTestList());
	}

	public function testProtectedDefaults()
	{
		$list = new TListControlTestList();
		$this->assertSame('select', PradoUnit::invoke($list, 'getTagName'));
		$this->assertFalse(PradoUnit::invoke($list, 'getIsMultiSelect'));
		$this->assertSame('Prado.WebUI.TListControl', PradoUnit::invoke($list, 'getClientClassName'));
		$this->assertInstanceOf(TListItemCollection::class, PradoUnit::invoke($list, 'createListItemCollection'));
	}

	public function testGetPostBackOptions()
	{
		$list = $this->createList();
		$list->setCausesValidation(false);
		$list->setValidationGroup('group1');
		$options = PradoUnit::invoke($list, 'getPostBackOptions');
		$this->assertSame([
			'ID' => $list->getClientID(),
			'CausesValidation' => false,
			'ValidationGroup' => 'group1',
			'EventTarget' => $list->getUniqueID(),
		], $options);
	}

	// ================================================================================
	// Properties
	// ================================================================================

	public function testEnableClientScript()
	{
		$list = new TListControlTestList();
		$this->assertTrue($list->getEnableClientScript());
		$list->setEnableClientScript('false');
		$this->assertFalse($list->getEnableClientScript());
		$list->setEnableClientScript(true);
		$this->assertTrue($list->getEnableClientScript());
	}

	public function testAppendDataBoundItems()
	{
		$list = new TListControlTestList();
		$this->assertFalse($list->getAppendDataBoundItems());
		$list->setAppendDataBoundItems('true');
		$this->assertTrue($list->getAppendDataBoundItems());
		$list->setAppendDataBoundItems(false);
		$this->assertFalse($list->getAppendDataBoundItems());
	}

	public function testAutoPostBack()
	{
		$list = new TListControlTestList();
		$list->setAutoPostBack('true');
		$this->assertTrue($list->getAutoPostBack());
		$list->setAutoPostBack('false');
		$this->assertFalse($list->getAutoPostBack());
	}

	public function testCausesValidationFalse()
	{
		$list = new TListControlTestList();
		$list->setCausesValidation('false');
		$this->assertFalse($list->getCausesValidation());
	}

	public function testValidationGroup()
	{
		$list = new TListControlTestList();
		$this->assertSame('', $list->getValidationGroup());
		$list->setValidationGroup('group1');
		$this->assertSame('group1', $list->getValidationGroup());
	}

	public function testDataFields()
	{
		$list = new TListControlTestList();
		$this->assertSame('', $list->getDataTextField());
		$this->assertSame('', $list->getDataValueField());
		$this->assertSame('', $list->getDataGroupField());
		$list->setDataTextField('name');
		$list->setDataValueField('id');
		$list->setDataGroupField('category');
		$this->assertSame('name', $list->getDataTextField());
		$this->assertSame('id', $list->getDataValueField());
		$this->assertSame('category', $list->getDataGroupField());
	}

	public function testDataTextFormatString()
	{
		$list = new TListControlTestList();
		$this->assertSame('', $list->getDataTextFormatString());
		$list->setDataTextFormatString('[%s]');
		$this->assertSame('[%s]', $list->getDataTextFormatString());
		$this->assertSame('[%s]', $list->getViewState('DataTextFormatString'));
	}

	public function testDataTextFormatStringCallableIsNotStoredInViewState()
	{
		$list = new TListControlTestList();
		$format = fn ($value) => strtoupper($value);
		$list->setDataTextFormatString($format);
		$this->assertSame($format, $list->getDataTextFormatString());
		$this->assertSame('', $list->getViewState('DataTextFormatString', ''));

		$list->setDataTextFormatString('%s!');
		$this->assertSame('%s!', $list->getDataTextFormatString());
	}

	public function testPromptTextAndValue()
	{
		$list = new TListControlTestList();
		$this->assertSame('', $list->getPromptText());
		$this->assertSame('', $list->getPromptValue());
		$list->setPromptText('Choose');
		$list->setPromptValue(0);
		$this->assertSame('Choose', $list->getPromptText());
		$this->assertSame('0', $list->getPromptValue());
	}

	// ================================================================================
	// Items
	// ================================================================================

	public function testItemCountAndHasItemsWithoutCollection()
	{
		$list = new TListControlTestList();
		$this->assertSame(0, $list->getItemCount());
		$this->assertFalse($list->getHasItems());
		$this->assertNull(PradoUnit::getProp($list, '_items'));
	}

	public function testGetItemsCreatesCollectionOnce()
	{
		$list = new TListControlTestList();
		$items = $list->getItems();
		$this->assertInstanceOf(TListItemCollection::class, $items);
		$this->assertSame($items, $list->getItems());
		$this->assertSame(0, $list->getItemCount());
		$this->assertFalse($list->getHasItems());

		$items->add(new TListItem('A', 'a'));
		$this->assertSame(1, $list->getItemCount());
		$this->assertTrue($list->getHasItems());
	}

	public function testCloneCopiesItems()
	{
		$list = $this->createList(false, ['a' => 'A', 'b' => 'B']);
		$list->getItems()->itemAt(0)->setAttribute('class', 'first');
		$copy = clone $list;
		$this->assertNotSame($list->getItems(), $copy->getItems());
		$this->assertNotSame($list->getItems()->itemAt(0), $copy->getItems()->itemAt(0));
		$this->assertSame(['A', 'B'], [$copy->getItems()->itemAt(0)->getText(), $copy->getItems()->itemAt(1)->getText()]);
		$this->assertSame('first', $copy->getItems()->itemAt(0)->getAttribute('class'));

		$copy->setSelectedIndex(1);
		$this->assertSame(-1, $list->getSelectedIndex());
		$copy->getItems()->add(new TListItem('C', 'c'));
		$this->assertSame(2, $list->getItemCount());

		$this->assertNull(PradoUnit::getProp(clone new TListControlTestList(), '_items'));
	}

		// ================================================================================
	// Selection: single
	// ================================================================================

	public function testSelectionDefaultsWithoutItems()
	{
		$list = new TListControlTestList();
		$this->assertSame(-1, $list->getSelectedIndex());
		$this->assertSame([], $list->getSelectedIndices());
		$this->assertSame([], $list->getSelectedValues());
		$this->assertNull($list->getSelectedItem());
		$this->assertSame('', $list->getSelectedValue());
		$this->assertNull(PradoUnit::getProp($list, '_items'));
	}

	public function testSetSelectedIndexNegativeClearsSelection()
	{
		$list = $this->createList(false, ['a' => 'A', 'b' => 'B']);
		$list->setSelectedIndex('1');
		$this->assertSame(1, $list->getSelectedIndex());
		$list->setSelectedIndex(-5);
		$this->assertSame(-1, $list->getSelectedIndex());
		$this->assertSame(-1, PradoUnit::getProp($list, '_cachedSelectedIndex'));
	}

	public function testSetSelectedIndexWithoutItemsIsCached()
	{
		$list = new TListControlTestList();
		$list->setSelectedIndex(2);
		$this->assertSame(-1, $list->getSelectedIndex());
		$this->assertSame(2, PradoUnit::getProp($list, '_cachedSelectedIndex'));
		$this->assertNull(PradoUnit::getProp($list, '_items'));
	}

	public function testGetSelectedIndexReturnsLowestSelected()
	{
		$list = $this->createList(true, ['a' => 'A', 'b' => 'B', 'c' => 'C']);
		$list->getItems()->itemAt(2)->setSelected(true);
		$list->getItems()->itemAt(1)->setSelected(true);
		$this->assertSame(1, $list->getSelectedIndex());
		$this->assertSame('b', $list->getSelectedValue());
		$this->assertSame('B', $list->getSelectedItem()->getText());
	}

	public function testSetSelectedValueUnknownClearsSelection()
	{
		$list = $this->createList(false, ['a' => 'A', 'b' => 'B']);
		$list->setSelectedValue('b');
		$this->assertSame(1, $list->getSelectedIndex());
		$list->setSelectedValue('zzz');
		$this->assertSame(-1, $list->getSelectedIndex());
		$this->assertSame('zzz', PradoUnit::getProp($list, '_cachedSelectedValue'));
	}

	public function testSetSelectedValueNullClearsSelection()
	{
		$list = $this->createList(false, ['a' => 'A', 'b' => 'B']);
		$list->setSelectedValue('a');
		$list->setSelectedValue(null);
		$this->assertSame(-1, $list->getSelectedIndex());
		$this->assertNull(PradoUnit::getProp($list, '_cachedSelectedValue'));
	}

	public function testSetSelectedValueMatchesNumericValue()
	{
		$list = $this->createList(false, [1 => 'One', 2 => 'Two']);
		$list->setSelectedValue(2);
		$this->assertSame(1, $list->getSelectedIndex());
		$this->assertSame('2', $list->getSelectedValue());
	}

	public function testSelectedValueFallsBackToPromptValue()
	{
		$list = $this->createList(false, ['a' => 'A']);
		$list->setPromptValue('none');
		$this->assertSame('none', $list->getSelectedValue());
		$this->assertSame('none', $list->getText());
		$this->assertSame('none', $list->getData());
	}

	public function testTextAndDataAliasSelectedValue()
	{
		$list = $this->createList(false, ['a' => 'A', 'b' => 'B', 'c' => 'C']);
		$list->setText('b');
		$this->assertSame('b', $list->getText());
		$this->assertSame(1, $list->getSelectedIndex());
		$list->setData('c');
		$this->assertSame('c', $list->getData());
		$this->assertSame(2, $list->getSelectedIndex());
	}

	public function testSingleSelectRejectsSelectedIndices()
	{
		$list = $this->createList(false, ['a' => 'A']);
		$this->expectException(TNotSupportedException::class);
		$list->setSelectedIndices([0]);
	}

	public function testSingleSelectRejectsSelectedValues()
	{
		$list = $this->createList(false, ['a' => 'A']);
		$this->expectException(TNotSupportedException::class);
		$list->setSelectedValues(['a']);
	}

	public function testClearSelectionWithoutItems()
	{
		$list = new TListControlTestList();
		$list->clearSelection();
		$this->assertNull(PradoUnit::getProp($list, '_items'));
	}

	public function testClearSelectionClearsAllSelected()
	{
		$list = $this->createList(true, ['a' => 'A', 'b' => 'B', 'c' => 'C']);
		$list->setSelectedIndices([0, 2]);
		$list->clearSelection();
		$this->assertSame([], $list->getSelectedIndices());
	}

	// ================================================================================
	// Selection: multiple
	// ================================================================================

	public function testSetSelectedIndicesIgnoresOutOfRange()
	{
		$list = $this->createList(true, ['a' => 'A', 'b' => 'B', 'c' => 'C']);
		$list->setSelectedIndices([2, -1, 0, 7]);
		$this->assertSame([0, 2], $list->getSelectedIndices());
		$this->assertSame(['a', 'c'], $list->getSelectedValues());
		$this->assertSame(0, $list->getSelectedIndex());
	}

	public function testSetSelectedIndicesReplacesSelection()
	{
		$list = $this->createList(true, ['a' => 'A', 'b' => 'B', 'c' => 'C']);
		$list->setSelectedIndices([0, 1]);
		$list->setSelectedIndices([2]);
		$this->assertSame([2], $list->getSelectedIndices());
	}

	public function testSetSelectedIndicesWithoutItemsIsCached()
	{
		$list = $this->createList(true);
		$list->setSelectedIndices([1]);
		$this->assertSame([1], PradoUnit::getProp($list, '_cachedSelectedIndices'));
		$this->assertSame([], $list->getSelectedIndices());
	}

	public function testSetSelectedValuesIgnoresUnknown()
	{
		$list = $this->createList(true, [1 => 'One', 2 => 'Two', 'x' => 'Ex']);
		$list->setSelectedValues([2, 'x', 'missing']);
		$this->assertSame([1, 2], $list->getSelectedIndices());
		$this->assertSame(['2', 'x'], $list->getSelectedValues());
	}

	public function testSetSelectedValuesReplacesSelection()
	{
		$list = $this->createList(true, ['a' => 'A', 'b' => 'B']);
		$list->setSelectedValues(['a']);
		$list->setSelectedValues(['b']);
		$this->assertSame(['b'], $list->getSelectedValues());
	}

	public function testSetSelectedValuesWithoutItemsIsCached()
	{
		$list = $this->createList(true);
		$list->setSelectedValues(['a']);
		$this->assertSame(['a'], PradoUnit::getProp($list, '_cachedSelectedValues'));
		$this->assertSame([], $list->getSelectedValues());
	}

	// ================================================================================
	// Adapter forwarding
	// ================================================================================

	public function testSelectionCallsAreForwardedToListAdapter()
	{
		$list = $this->createList(true, ['a' => 'A', 'b' => 'B']);
		$adapter = new TListControlTestAdapter($list);
		$list->setAdapter($adapter);

		$list->setSelectedIndex(1);
		$list->setSelectedIndices([0]);
		$list->setSelectedValue('b');
		$list->setSelectedValues(['a']);
		$list->clearSelection();

		$this->assertSame([
			['clearSelection'],
			['setSelectedIndex', 1],
			['clearSelection'],
			['setSelectedIndices', [0]],
			['clearSelection'],
			['setSelectedValue', 'b'],
			['clearSelection'],
			['setSelectedValues', ['a']],
			['clearSelection'],
		], $adapter->getCalls());
	}

	public function testSelectionCallsWithoutItemsForwardOnlySetters()
	{
		$list = $this->createList(true);
		$adapter = new TListControlTestAdapter($list);
		$list->setAdapter($adapter);

		$list->setSelectedIndex(1);
		$list->setSelectedValue('b');

		$this->assertSame([
			['setSelectedIndex', 1],
			['setSelectedValue', 'b'],
		], $adapter->getCalls());
	}

	// ================================================================================
	// addParsedObject
	// ================================================================================

	public function testAddParsedObjectAddsOnlyListItems()
	{
		$list = new TListControlTestList();
		$list->addParsedObject('text');
		$list->addParsedObject(new TPanel());
		$this->assertSame(0, $list->getItemCount());
		$list->addParsedObject(new TListItem('A', 'a'));
		$this->assertSame(1, $list->getItemCount());
	}

	public function testAddParsedObjectAppliesCachedSelectedValue()
	{
		$list = new TListControlTestList();
		$list->setSelectedValue('b');
		$list->addParsedObject(new TListItem('A', 'a'));
		$list->addParsedObject(new TListItem('B', 'b'));
		$list->addParsedObject(new TListItem('B2', 'b'));
		$this->assertSame([1], $list->getSelectedIndices());
		$this->assertNull(PradoUnit::getProp($list, '_cachedSelectedValue'));
		$this->assertSame(-1, PradoUnit::getProp($list, '_cachedSelectedIndex'));
	}

	public function testAddParsedObjectAppliesCachedSelectedIndex()
	{
		$list = new TListControlTestList();
		$list->setSelectedIndex(1);
		$list->addParsedObject(new TListItem('A', 'a'));
		$list->addParsedObject(new TListItem('B', 'b'));
		$list->addParsedObject(new TListItem('C', 'c'));
		$this->assertSame(1, $list->getSelectedIndex());
		$this->assertSame(-1, PradoUnit::getProp($list, '_cachedSelectedIndex'));
	}

	public function testAddParsedObjectIgnoredAfterStateLoaded()
	{
		$list = new TListControlTestList();
		$list->loadState();
		$list->addParsedObject(new TListItem('A', 'a'));
		$this->assertSame(0, $list->getItemCount());
	}

	// ================================================================================
	// Data binding
	// ================================================================================

	public function testBindScalarDataUsesKeysAsValues()
	{
		$list = new TListControlTestList();
		$this->bind($list, ['x' => 'Ex', 'y' => 'Why', 3 => 'Three']);
		$this->assertSame([['x', 'Ex'], ['y', 'Why'], ['3', 'Three']], $this->itemSummary($list));
	}

	public function testBindArraysUsesDefaultFieldIndices()
	{
		$list = new TListControlTestList();
		$this->bind($list, [['Apple', 'a'], ['Banana', 'b']]);
		$this->assertSame([['a', 'Apple'], ['b', 'Banana']], $this->itemSummary($list));
	}

	public function testBindArraysWithNamedFields()
	{
		$list = new TListControlTestList();
		$list->setDataTextField('name');
		$list->setDataValueField('id');
		$this->bind($list, [['id' => 7, 'name' => 'Seven'], ['id' => 8, 'name' => 'Eight']]);
		$this->assertSame([['7', 'Seven'], ['8', 'Eight']], $this->itemSummary($list));
		$this->assertFalse($list->getItems()->itemAt(0)->getHasAttributes());
	}

	public function testBindObjectsUsesGettersAndProperties()
	{
		$list = new TListControlTestList();
		$list->setDataTextField('label');
		$list->setDataValueField('Code');
		$this->bind($list, [new TListControlTestRecord('First', 'f'), new TListControlTestRecord('Second', 's')]);
		$this->assertSame([['f', 'First'], ['s', 'Second']], $this->itemSummary($list));
	}

	public function testBindGroupFieldSetsGroupAttribute()
	{
		$list = new TListControlTestList();
		$list->setDataTextField('name');
		$list->setDataValueField('id');
		$list->setDataGroupField('group');
		$this->bind($list, [
			['id' => 1, 'name' => 'One', 'group' => 'Odd'],
			['id' => 2, 'name' => 'Two', 'group' => 'Even'],
		]);
		$this->assertSame('Odd', $list->getItems()->itemAt(0)->getAttribute('Group'));
		$this->assertSame('Even', $list->getItems()->itemAt(1)->getAttribute('Group'));
	}

	public function testBindGroupFieldIgnoredForScalarData()
	{
		$list = new TListControlTestList();
		$list->setDataGroupField('group');
		$this->bind($list, ['a' => 'A']);
		$this->assertFalse($list->getItems()->itemAt(0)->getHasAttributes());
	}

	public function testBindReplacesExistingItems()
	{
		$list = $this->createList(false, ['old' => 'Old']);
		$this->bind($list, ['new' => 'New']);
		$this->assertSame([['new', 'New']], $this->itemSummary($list));
	}

	public function testBindAppendsWhenAppendDataBoundItems()
	{
		$list = $this->createList(false, ['old' => 'Old']);
		$list->setAppendDataBoundItems(true);
		$this->bind($list, ['new' => 'New']);
		$this->assertSame([['old', 'Old'], ['new', 'New']], $this->itemSummary($list));
	}

	public function testBindAppliesDataTextFormatString()
	{
		$list = new TListControlTestList();
		$list->setDataTextFormatString('Item: %s');
		$this->bind($list, ['a' => 'A']);
		$this->assertSame('Item: A', $list->getItems()->itemAt(0)->getText());
		$this->assertSame('a', $list->getItems()->itemAt(0)->getValue());
	}

	public function testBindAppliesCallableDataTextFormat()
	{
		$list = new TListControlTestList();
		$list->setDataTextFormatString(fn ($value) => strtoupper($value));
		$this->bind($list, ['a' => 'apple']);
		$this->assertSame('APPLE', $list->getItems()->itemAt(0)->getText());
	}

	public function testBindInvalidExpressionFormatThrows()
	{
		$list = new TListControlTestList();
		$list->setDataTextFormatString('#$value +* (');
		try {
			$this->bind($list, ['a' => 'A']);
			$this->fail('TInvalidDataValueException expected.');
		} catch (TInvalidDataValueException $e) {
			$this->assertSame('listcontrol_expression_invalid', $e->getErrorCode());
		}
	}

	public function testBindAppliesCachedSelectedValue()
	{
		$list = new TListControlTestList();
		$list->setSelectedValue('b');
		$this->bind($list, ['a' => 'A', 'b' => 'B']);
		$this->assertSame(1, $list->getSelectedIndex());
		$this->assertNull(PradoUnit::getProp($list, '_cachedSelectedValue'));
	}

	public function testBindAppliesCachedSelectedIndex()
	{
		$list = new TListControlTestList();
		$list->setSelectedIndex(2);
		$this->bind($list, ['a' => 'A', 'b' => 'B', 'c' => 'C']);
		$this->assertSame(2, $list->getSelectedIndex());
		$this->assertSame(-1, PradoUnit::getProp($list, '_cachedSelectedIndex'));
	}

	public function testBindAppliesCachedSelectedValues()
	{
		$list = $this->createList(true);
		$list->setSelectedValues(['a', 'c']);
		$this->bind($list, ['a' => 'A', 'b' => 'B', 'c' => 'C']);
		$this->assertSame([0, 2], $list->getSelectedIndices());
		$this->assertNull(PradoUnit::getProp($list, '_cachedSelectedValues'));
	}

	public function testBindAppliesCachedSelectedIndices()
	{
		$list = $this->createList(true);
		$list->setSelectedIndices([1, 2]);
		$this->bind($list, ['a' => 'A', 'b' => 'B', 'c' => 'C']);
		$this->assertSame([1, 2], $list->getSelectedIndices());
		$this->assertNull(PradoUnit::getProp($list, '_cachedSelectedIndices'));
	}

	public function testBindWithoutCachedSelectionSelectsNothing()
	{
		$list = new TListControlTestList();
		$this->bind($list, ['a' => 'A', 'b' => 'B']);
		$this->assertSame(-1, $list->getSelectedIndex());
	}

	// ================================================================================
	// State
	// ================================================================================

	public function testSaveStateStoresItems()
	{
		$list = $this->createList(false, ['a' => 'A', 'b' => 'B']);
		$list->saveState();
		$state = $list->getViewState('Items');
		$this->assertIsArray($state);
		$this->assertCount(2, $state);
		$this->assertSame('B', $state[1]->getText());
	}

	public function testSaveStateWithoutItemsClearsViewState()
	{
		$list = new TListControlTestList();
		$list->setViewState('Items', ['stale']);
		$list->saveState();
		$this->assertNull($list->getViewState('Items'));
	}

	public function testSaveStateWithEmptyCollectionClearsViewState()
	{
		$list = new TListControlTestList();
		$list->getItems();
		$list->setViewState('Items', ['stale']);
		$list->saveState();
		$this->assertNull($list->getViewState('Items'));
	}

	public function testLoadStateRestoresItems()
	{
		$source = $this->createList(false, ['a' => 'A', 'b' => 'B']);
		$source->setSelectedIndex(1);
		$source->saveState();

		$list = new TListControlTestList();
		$list->setViewState('Items', $source->getViewState('Items'));
		$list->loadState();

		$this->assertSame([['a', 'A'], ['b', 'B']], $this->itemSummary($list));
		$this->assertSame(1, $list->getSelectedIndex());
		$this->assertNull($list->getViewState('Items'));
		$this->assertTrue(PradoUnit::getProp($list, '_stateLoaded'));
	}

	public function testLoadStateWithoutItemsCreatesEmptyCollection()
	{
		$list = new TListControlTestList();
		$list->loadState();
		$this->assertInstanceOf(TListItemCollection::class, PradoUnit::getProp($list, '_items'));
		$this->assertSame(0, $list->getItemCount());
	}

	public function testLoadStateKeepsItemsWhenDataBound()
	{
		$list = new TListControlTestList();
		$this->bind($list, ['x' => 'X']);
		$list->setViewState('Items', [new TListItem('A', 'a')]);
		$list->loadState();
		$this->assertSame([['x', 'X']], $this->itemSummary($list));
		$this->assertNull($list->getViewState('Items'));
	}

	// ================================================================================
	// Events
	// ================================================================================

	public function testOnSelectedIndexChangedRaisesBothEvents()
	{
		$list = new TListControlTestList();
		$param = new TEventParameter();
		$raised = [];
		$list->attachEventHandler('OnSelectedIndexChanged', function ($sender, $p) use (&$raised, $list, $param) {
			$this->assertSame($list, $sender);
			$this->assertSame($param, $p);
			$raised[] = 'SelectedIndexChanged';
		});
		$list->attachEventHandler('OnTextChanged', function ($sender, $p) use (&$raised, $list, $param) {
			$this->assertSame($list, $sender);
			$this->assertSame($param, $p);
			$raised[] = 'TextChanged';
		});
		$list->onSelectedIndexChanged($param);
		$this->assertSame(['SelectedIndexChanged', 'TextChanged'], $raised);
	}

	public function testOnTextChangedRaisesOnlyTextChanged()
	{
		$list = new TListControlTestList();
		$raised = [];
		$list->attachEventHandler('OnSelectedIndexChanged', function () use (&$raised) {
			$raised[] = 'SelectedIndexChanged';
		});
		$list->attachEventHandler('OnTextChanged', function () use (&$raised) {
			$raised[] = 'TextChanged';
		});
		$list->onTextChanged(new TEventParameter());
		$this->assertSame(['TextChanged'], $raised);
	}

	// ================================================================================
	// Rendering: contents
	// ================================================================================

	public function testRenderContentsEmpty()
	{
		$this->assertSame('', $this->renderContents(new TListControlTestList()));
	}

	public function testRenderContentsItems()
	{
		$list = $this->createList(false, ['a' => 'A', 'b' => 'B "<C>"']);
		$list->setSelectedIndex(1);
		$output = $this->renderContents($list);
		$this->assertSame(
			"\n" . '<option value="a">A</option>' . "\n"
			. '<option selected="selected" value="b">B &quot;&lt;C&gt;&quot;</option>' . "\n",
			$output
		);
	}

	public function testRenderContentsSkipsDisabledItems()
	{
		$list = $this->createList(false, ['a' => 'A', 'b' => 'B']);
		$list->getItems()->itemAt(0)->setEnabled(false);
		$output = $this->renderContents($list);
		$this->assertStringNotContainsString('value="a"', $output);
		$this->assertStringContainsString('<option value="b">B</option>', $output);
	}

	public function testRenderContentsPromptOnly()
	{
		$list = new TListControlTestList();
		$list->setPromptText('Pick <one>');
		$this->assertSame('<option value="">Pick &lt;one&gt;</option>' . "\n", $this->renderContents($list));
	}

	public function testRenderContentsPromptValueOnly()
	{
		$list = new TListControlTestList();
		$list->setPromptValue('none');
		$this->assertSame('<option value="none"></option>' . "\n", $this->renderContents($list));
	}

	public function testRenderContentsPromptBeforeItems()
	{
		$list = $this->createList(false, ['a' => 'A']);
		$list->setPromptText('Pick');
		$list->setPromptValue('-');
		$output = $this->renderContents($list);
		$this->assertSame(
			'<option value="-">Pick</option>' . "\n\n" . '<option value="a">A</option>' . "\n",
			$output
		);
	}

	public function testRenderContentsItemAttributes()
	{
		$list = $this->createList(false, ['a' => 'A']);
		$list->getItems()->itemAt(0)->setAttribute('class', 'hot');
		$output = $this->renderContents($list);
		$this->assertStringContainsString('<option class="hot" value="a">A</option>', $output);
	}

	public function testRenderContentsGroups()
	{
		$list = new TListControlTestList();
		$list->setDataTextField('name');
		$list->setDataValueField('id');
		$list->setDataGroupField('group');
		$this->bind($list, [
			['id' => 1, 'name' => 'One', 'group' => 'Odd'],
			['id' => 3, 'name' => 'Three', 'group' => 'Odd'],
			['id' => 2, 'name' => 'Two', 'group' => 'Even'],
		]);
		$list->getItems()->add(new TListItem('Plain', 'p'));
		$output = $this->renderContents($list);
		$this->assertSame(
			"\n"
			. '<optgroup label="Odd">' . "\n"
			. '<option value="1">One</option>' . "\n"
			. '<option value="3">Three</option>' . "\n"
			. '</optgroup>' . "\n"
			. '<optgroup label="Even">' . "\n"
			. '<option value="2">Two</option>' . "\n"
			. '</optgroup>' . "\n"
			. '<option value="p">Plain</option>' . "\n",
			$output
		);
	}

	public function testRenderContentsClosesGroupBeforeItemWithOtherAttributes()
	{
		$list = new TListControlTestList();
		$grouped = new TListItem('One', '1');
		$grouped->setAttribute('Group', 'G');
		$styled = new TListItem('Two', '2');
		$styled->setAttribute('class', 'c');
		$list->getItems()->add($grouped);
		$list->getItems()->add($styled);
		$output = $this->renderContents($list);
		$this->assertSame(
			"\n"
			. '<optgroup label="G">' . "\n"
			. '<option value="1">One</option>' . "\n"
			. '</optgroup>' . "\n"
			. '<option class="c" value="2">Two</option>' . "\n",
			$output
		);
	}

	public function testRenderContentsClosesTrailingGroup()
	{
		$list = new TListControlTestList();
		$item = new TListItem('One', '1');
		$item->setAttribute('Group', 'G');
		$list->getItems()->add(new TListItem('Zero', '0'));
		$list->getItems()->add($item);
		$output = $this->renderContents($list);
		$this->assertStringEndsWith('<option value="1">One</option>' . "\n" . '</optgroup>' . "\n", $output);
		$this->assertStringStartsWith("\n" . '<option value="0">Zero</option>' . "\n" . '<optgroup label="G">', $output);
	}

	// ================================================================================
	// Rendering: control
	// ================================================================================

	public function testRenderRequiresForm()
	{
		$page = new TPage();
		$list = $this->createList(false, ['a' => 'A']);
		$page->getControls()->add($list);
		$this->expectException(TConfigurationException::class);
		$this->render($list);
	}

	public function testRenderSingleSelect()
	{
		[, $list] = $this->createPageList(false, ['a' => 'A']);
		$output = $this->render($list);
		$this->assertStringStartsWith('<select', $output);
		$this->assertStringEndsWith('</select>', $output);
		$this->assertStringNotContainsString('multiple', $output);
		$this->assertStringNotContainsString('disabled', $output);
		$this->assertStringContainsString('<option value="a">A</option>', $output);
	}

	public function testRenderMultiSelect()
	{
		[, $list] = $this->createPageList(true, ['a' => 'A']);
		$this->assertStringContainsString('multiple="multiple"', $this->render($list));
	}

	public function testRenderDisabledControl()
	{
		[, $list] = $this->createPageList(false, ['a' => 'A']);
		$list->setEnabled(false);
		$output = $this->renderBeginTag($list);
		$this->assertSame(1, substr_count($output, 'disabled="disabled"'));
	}

	public function testRenderDisabledByParent()
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_inFormRender', true);
		$panel = new TPanel();
		$page->getControls()->add($panel);
		$list = $this->createList(false, ['a' => 'A']);
		$panel->getControls()->add($list);
		$panel->setEnabled(false);
		$output = $this->renderBeginTag($list);
		$this->assertSame(1, substr_count($output, 'disabled="disabled"'));
	}

	public function testRenderAutoPostBackRegistersClientScript()
	{
		[$page, $list] = $this->createPageList(false, ['a' => 'A']);
		$list->setAutoPostBack(true);
		$output = $this->renderBeginTag($list);
		$this->assertStringContainsString('id="' . $list->getClientID() . '"', $output);
		$this->assertSame(
			[['Prado.WebUI.TListControl', PradoUnit::invoke($list, 'getPostBackOptions')]],
			$page->getClientScript()->getPostBackControls()
		);
	}

	public function testRenderAutoPostBackWithoutClientScript()
	{
		[$page, $list] = $this->createPageList(false, ['a' => 'A']);
		$list->setAutoPostBack(true);
		$list->setEnableClientScript(false);
		$this->renderBeginTag($list);
		$this->assertSame([], $page->getClientScript()->getPostBackControls());
	}

	public function testRenderAutoPostBackWithoutJavaScriptSupport()
	{
		[$page, $list] = $this->createPageList(false, ['a' => 'A']);
		$list->setAutoPostBack(true);
		PradoUnit::setProp($page, '_enableJavaScript', false);
		$this->renderBeginTag($list);
		$this->assertSame([], $page->getClientScript()->getPostBackControls());
	}

	public function testRenderAutoPostBackDisabledRegistersNoScript()
	{
		[$page, $list] = $this->createPageList(false, ['a' => 'A']);
		$list->setAutoPostBack(true);
		$list->setEnabled(false);
		$this->renderBeginTag($list);
		$this->assertSame([], $page->getClientScript()->getPostBackControls());
	}
}
