<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\IO\TTextWriter;
use Prado\Web\UI\ITemplate;
use Prado\Web\UI\TCommandEventParameter;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\TControl;
use Prado\Web\UI\WebControls\TDataList;
use Prado\Web\UI\WebControls\TDataListCommandEventParameter;
use Prado\Web\UI\WebControls\TDataListItem;
use Prado\Web\UI\WebControls\TDataListItemCollection;
use Prado\Web\UI\WebControls\TDataListItemEventParameter;
use Prado\Web\UI\WebControls\TDataListItemRenderer;
use Prado\Web\UI\WebControls\TButton;
use Prado\Web\UI\WebControls\THorizontalAlign;
use Prado\Web\UI\WebControls\TTableGridLines;
use Prado\Web\UI\WebControls\TTableStyle;
use Prado\Collections\TList;
use Prado\TEventParameter;
use Prado\Test\Unit\Harness\Web\UI\TTestDataTemplate;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Web\UI\WebControls\TListItemType;
use Prado\Web\UI\WebControls\TRepeatDirection;
use Prado\Web\UI\WebControls\TRepeatInfo;
use Prado\Web\UI\WebControls\TRepeatLayout;
use Prado\Web\UI\WebControls\TTableItemStyle;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidOperationException;

// ---------------------------------------------------------------------------
// Prado3 alias registration — mirrors what Prado::using() does at runtime.
// ---------------------------------------------------------------------------

if (!class_exists('Prado3Alias_TDataList', false)) {
	class_alias(\Prado\Web\UI\WebControls\TDataList::class, 'Prado3Alias_TDataList');
}

if (!class_exists('Prado3Alias_TDataListItem', false)) {
	class_alias(\Prado\Web\UI\WebControls\TDataListItem::class, 'Prado3Alias_TDataListItem');
}

// ---------------------------------------------------------------------------
// Fixtures
// ---------------------------------------------------------------------------

/**
 * Spy TDataListItem: records setTagName calls and short-circuits renderControl.
 */
class SpyDataListItem extends TDataListItem
{
	public bool $setTagNameCalled = false;
	public ?string $lastTagName = null;

	public function setTagName($value): void
	{
		$this->setTagNameCalled = true;
		$this->lastTagName = $value;
		parent::setTagName($value);
	}

	public function renderControl($writer): void
	{
		// no-op: we only care about setTagName, not actual rendering
	}
}

/**
 * A TControl that is NOT a TDataListItem.
 * TDataListItemCollection accepts any TControl descendant.
 */
class NonDataListItem extends TControl
{
	public function renderControl($writer): void
	{
		// no-op
	}
}

/**
 * Minimal ITemplate stub — does nothing, just satisfies the type check.
 */
class StubTemplate implements ITemplate
{
	public function instantiateIn($parent): void
	{
	}

	public function getIncludedFiles(): array
	{
		return [];
	}
}

/**
 * Data list item renderer without a template file.
 */
class TDataListTestRenderer extends TDataListItemRenderer
{
	public function getTemplate()
	{
		return null;
	}

	public function renderContents($writer)
	{
		$data = $this->getData();
		$writer->write('R' . $this->getItemType() . $this->getItemIndex() . ($data === null ? '' : '=' . $data));
	}
}

/**
 * Data list item renderer that renders no tag.
 */
class TDataListTestNoTagRenderer extends TDataListTestRenderer
{
	protected function getTagName()
	{
		return '';
	}
}

/**
 * A plain control as an item renderer.
 */
class TDataListTestPlainRenderer extends TControl
{
	public function render($writer)
	{
		$writer->write('P');
	}
}

/**
 * Exposes the protected TDataList members.
 */
class TDataListTestList extends TDataList
{
	public function callCreateItem($index, $type)
	{
		return $this->createItem($index, $type);
	}

	public function callApplyItemStyles()
	{
		$this->applyItemStyles();
	}

	public function callGetDataFieldValue($data, $field)
	{
		return $this->getDataFieldValue($data, $field);
	}
}

// ---------------------------------------------------------------------------

class TDataListTest extends TestCase
{
	// ========================================================================
	// Helpers
	// ========================================================================

	private function writer(): THtmlWriter
	{
		return new THtmlWriter(new TTextWriter());
	}

	private function rawRepeatInfo(): TRepeatInfo
	{
		$ri = new TRepeatInfo();
		$ri->setRepeatLayout(TRepeatLayout::Raw);
		return $ri;
	}

	private function tableRepeatInfo(): TRepeatInfo
	{
		$ri = new TRepeatInfo();
		$ri->setRepeatLayout(TRepeatLayout::Table);
		return $ri;
	}

	/**
	 * Build a TDataListCommandEventParameter from a plain command name string.
	 */
	private function makeCommandParam(string $commandName, TControl $item): TDataListCommandEventParameter
	{
		$cmdParam = new TCommandEventParameter($commandName, '');
		return new TDataListCommandEventParameter($item, new NonDataListItem(), $cmdParam);
	}

	// ========================================================================
	// renderItem — Raw layout + TDataListItem → setTagName('div') called
	// ========================================================================

	public function testRenderItemRawLayoutSetsTagNameOnDataListItem(): void
	{
		$list = new TDataList();

		$item = new SpyDataListItem();
		$item->setItemIndex(0);
		$item->setItemType(TListItemType::Item);
		$list->getItems()->add($item);

		$list->renderItem($this->writer(), $this->rawRepeatInfo(), TListItemType::Item, 0);

		$this->assertTrue($item->setTagNameCalled, 'setTagName() should be called when layout is Raw and item is TDataListItem');
		$this->assertSame('div', $item->lastTagName);
	}

	// ========================================================================
	// renderItem — Raw layout + non-TDataListItem → setTagName NOT called
	// ========================================================================

	public function testRenderItemRawLayoutDoesNotSetTagNameOnNonDataListItem(): void
	{
		$list = new TDataList();

		$item = new NonDataListItem();
		$list->getItems()->add($item);

		// NonDataListItem has no setTagName — the runtime would throw if it
		// tried to call it, so a clean return means the branch was skipped.
		$list->renderItem($this->writer(), $this->rawRepeatInfo(), TListItemType::Item, 0);

		$this->assertTrue(true);
	}

	// ========================================================================
	// renderItem — Table layout + TDataListItem → setTagName NOT called
	// ========================================================================

	public function testRenderItemTableLayoutDoesNotSetTagName(): void
	{
		$list = new TDataList();

		$item = new SpyDataListItem();
		$item->setItemIndex(0);
		$item->setItemType(TListItemType::Item);
		$list->getItems()->add($item);

		$list->renderItem($this->writer(), $this->tableRepeatInfo(), TListItemType::Item, 0);

		$this->assertFalse($item->setTagNameCalled, 'setTagName() must NOT be called when layout is Table');
	}

	// ========================================================================
	// renderItem — Flow layout + TDataListItem → setTagName NOT called
	// ========================================================================

	public function testRenderItemFlowLayoutDoesNotSetTagName(): void
	{
		$list = new TDataList();

		$item = new SpyDataListItem();
		$item->setItemIndex(0);
		$item->setItemType(TListItemType::Item);
		$list->getItems()->add($item);

		$ri = new TRepeatInfo();
		$ri->setRepeatLayout(TRepeatLayout::Flow);

		$list->renderItem($this->writer(), $ri, TListItemType::Item, 0);

		$this->assertFalse($item->setTagNameCalled, 'setTagName() must NOT be called when layout is Flow');
	}

	// ========================================================================
	// renderItem — Raw layout, AlternatingItem type → setTagName called
	// ========================================================================

	public function testRenderItemRawLayoutAlternatingItemSetsTagName(): void
	{
		$list = new TDataList();

		$item = new SpyDataListItem();
		$item->setItemIndex(0);
		$item->setItemType(TListItemType::AlternatingItem);
		$list->getItems()->add($item);

		$list->renderItem($this->writer(), $this->rawRepeatInfo(), TListItemType::AlternatingItem, 0);

		$this->assertTrue($item->setTagNameCalled);
		$this->assertSame('div', $item->lastTagName);
	}

	// ========================================================================
	// getItems / getItemCount
	// ========================================================================

	public function testGetItemsReturnsEmptyCollectionInitially(): void
	{
		$list = new TDataList();
		$this->assertSame(0, $list->getItemCount());
	}

	public function testGetItemCountAfterAddingItems(): void
	{
		$list = new TDataList();
		$item = new SpyDataListItem();
		$item->setItemIndex(0);
		$item->setItemType(TListItemType::Item);
		$list->getItems()->add($item);

		$this->assertSame(1, $list->getItemCount());
	}

	public function testGetItemCountZeroWhenNoItemsEverAdded(): void
	{
		// _items is null initially; getItemCount() must short-circuit to 0
		$list = new TDataList();
		$this->assertSame(0, $list->getItemCount());
	}

	// ========================================================================
	// RepeatLayout
	// ========================================================================

	public function testRepeatLayoutDefaultIsTable(): void
	{
		$list = new TDataList();
		$this->assertSame(TRepeatLayout::Table, $list->getRepeatLayout());
	}

	public function testSetRepeatLayout(): void
	{
		$list = new TDataList();
		$list->setRepeatLayout(TRepeatLayout::Raw);
		$this->assertSame(TRepeatLayout::Raw, $list->getRepeatLayout());
	}

	public function testSetRepeatLayoutFlow(): void
	{
		$list = new TDataList();
		$list->setRepeatLayout(TRepeatLayout::Flow);
		$this->assertSame(TRepeatLayout::Flow, $list->getRepeatLayout());
	}

	// ========================================================================
	// RepeatColumns / RepeatDirection / Caption
	// ========================================================================

	public function testRepeatColumnsDefaultIsZero(): void
	{
		$list = new TDataList();
		$this->assertSame(0, $list->getRepeatColumns());
	}

	public function testSetRepeatColumns(): void
	{
		$list = new TDataList();
		$list->setRepeatColumns(3);
		$this->assertSame(3, $list->getRepeatColumns());
	}

	public function testRepeatDirectionDefaultIsVertical(): void
	{
		$list = new TDataList();
		$this->assertSame(TRepeatDirection::Vertical, $list->getRepeatDirection());
	}

	public function testSetRepeatDirectionHorizontal(): void
	{
		$list = new TDataList();
		$list->setRepeatDirection(TRepeatDirection::Horizontal);
		$this->assertSame(TRepeatDirection::Horizontal, $list->getRepeatDirection());
	}

	public function testCaptionDefaultIsEmpty(): void
	{
		$list = new TDataList();
		$this->assertSame('', $list->getCaption());
	}

	public function testSetCaption(): void
	{
		$list = new TDataList();
		$list->setCaption('My Caption');
		$this->assertSame('My Caption', $list->getCaption());
	}

	// ========================================================================
	// ShowHeader / ShowFooter
	// ========================================================================

	public function testShowHeaderDefaultTrue(): void
	{
		$list = new TDataList();
		$this->assertTrue($list->getShowHeader());
	}

	public function testSetShowHeaderFalse(): void
	{
		$list = new TDataList();
		$list->setShowHeader(false);
		$this->assertFalse($list->getShowHeader());
	}

	public function testSetShowHeaderStringCoercedToBool(): void
	{
		$list = new TDataList();
		$list->setShowHeader('true');
		$this->assertTrue($list->getShowHeader());
	}

	public function testShowFooterDefaultTrue(): void
	{
		$list = new TDataList();
		$this->assertTrue($list->getShowFooter());
	}

	public function testSetShowFooterFalse(): void
	{
		$list = new TDataList();
		$list->setShowFooter(false);
		$this->assertFalse($list->getShowFooter());
	}

	// ========================================================================
	// Templates — null accepted, ITemplate accepted, non-ITemplate throws
	// ========================================================================

	public function testItemTemplateDefaultIsNull(): void
	{
		$list = new TDataList();
		$this->assertNull($list->getItemTemplate());
	}

	public function testSetItemTemplateNull(): void
	{
		$list = new TDataList();
		$list->setItemTemplate(null);
		$this->assertNull($list->getItemTemplate());
	}

	public function testSetItemTemplateITemplate(): void
	{
		$list = new TDataList();
		$tpl = new StubTemplate();
		$list->setItemTemplate($tpl);
		$this->assertSame($tpl, $list->getItemTemplate());
	}

	public function testSetItemTemplateNonITemplateThrows(): void
	{
		$this->expectException(TInvalidDataTypeException::class);
		$list = new TDataList();
		$list->setItemTemplate('not-a-template');
	}

	public function testAlternatingItemTemplateDefaultIsNull(): void
	{
		$list = new TDataList();
		$this->assertNull($list->getAlternatingItemTemplate());
	}

	public function testSetAlternatingItemTemplateNull(): void
	{
		$list = new TDataList();
		$list->setAlternatingItemTemplate(null);
		$this->assertNull($list->getAlternatingItemTemplate());
	}

	public function testSetAlternatingItemTemplateITemplate(): void
	{
		$list = new TDataList();
		$tpl = new StubTemplate();
		$list->setAlternatingItemTemplate($tpl);
		$this->assertSame($tpl, $list->getAlternatingItemTemplate());
	}

	public function testSetAlternatingItemTemplateNonITemplateThrows(): void
	{
		$this->expectException(TInvalidDataTypeException::class);
		(new TDataList())->setAlternatingItemTemplate(42);
	}

	public function testSelectedItemTemplateDefaultIsNull(): void
	{
		$list = new TDataList();
		$this->assertNull($list->getSelectedItemTemplate());
	}

	public function testSetSelectedItemTemplateITemplate(): void
	{
		$list = new TDataList();
		$tpl = new StubTemplate();
		$list->setSelectedItemTemplate($tpl);
		$this->assertSame($tpl, $list->getSelectedItemTemplate());
	}

	public function testSetSelectedItemTemplateNonITemplateThrows(): void
	{
		$this->expectException(TInvalidDataTypeException::class);
		(new TDataList())->setSelectedItemTemplate(new \stdClass());
	}

	public function testEditItemTemplateDefaultIsNull(): void
	{
		$list = new TDataList();
		$this->assertNull($list->getEditItemTemplate());
	}

	public function testSetEditItemTemplateITemplate(): void
	{
		$list = new TDataList();
		$tpl = new StubTemplate();
		$list->setEditItemTemplate($tpl);
		$this->assertSame($tpl, $list->getEditItemTemplate());
	}

	public function testSetEditItemTemplateNonITemplateThrows(): void
	{
		$this->expectException(TInvalidDataTypeException::class);
		(new TDataList())->setEditItemTemplate([]);
	}

	public function testHeaderTemplateDefaultIsNull(): void
	{
		$list = new TDataList();
		$this->assertNull($list->getHeaderTemplate());
	}

	public function testSetHeaderTemplateITemplate(): void
	{
		$list = new TDataList();
		$tpl = new StubTemplate();
		$list->setHeaderTemplate($tpl);
		$this->assertSame($tpl, $list->getHeaderTemplate());
	}

	public function testSetHeaderTemplateNonITemplateThrows(): void
	{
		$this->expectException(TInvalidDataTypeException::class);
		(new TDataList())->setHeaderTemplate('bad');
	}

	public function testFooterTemplateDefaultIsNull(): void
	{
		$list = new TDataList();
		$this->assertNull($list->getFooterTemplate());
	}

	public function testSetFooterTemplateITemplate(): void
	{
		$list = new TDataList();
		$tpl = new StubTemplate();
		$list->setFooterTemplate($tpl);
		$this->assertSame($tpl, $list->getFooterTemplate());
	}

	public function testSetFooterTemplateNonITemplateThrows(): void
	{
		$this->expectException(TInvalidDataTypeException::class);
		(new TDataList())->setFooterTemplate(0);
	}

	public function testSeparatorTemplateDefaultIsNull(): void
	{
		$list = new TDataList();
		$this->assertNull($list->getSeparatorTemplate());
	}

	public function testSetSeparatorTemplateITemplate(): void
	{
		$list = new TDataList();
		$tpl = new StubTemplate();
		$list->setSeparatorTemplate($tpl);
		$this->assertSame($tpl, $list->getSeparatorTemplate());
	}

	public function testSetSeparatorTemplateNonITemplateThrows(): void
	{
		$this->expectException(TInvalidDataTypeException::class);
		(new TDataList())->setSeparatorTemplate(false);
	}

	public function testEmptyTemplateDefaultIsNull(): void
	{
		$list = new TDataList();
		$this->assertNull($list->getEmptyTemplate());
	}

	public function testSetEmptyTemplateITemplate(): void
	{
		$list = new TDataList();
		$tpl = new StubTemplate();
		$list->setEmptyTemplate($tpl);
		$this->assertSame($tpl, $list->getEmptyTemplate());
	}

	public function testSetEmptyTemplateNonITemplateThrows(): void
	{
		$this->expectException(TInvalidDataTypeException::class);
		(new TDataList())->setEmptyTemplate('oops');
	}

	// ========================================================================
	// Renderers — default '', get/set via viewstate
	// ========================================================================

	public function testItemRendererDefaultIsEmpty(): void
	{
		$list = new TDataList();
		$this->assertSame('', $list->getItemRenderer());
	}

	public function testSetItemRenderer(): void
	{
		$list = new TDataList();
		$list->setItemRenderer('MyItemRenderer');
		$this->assertSame('MyItemRenderer', $list->getItemRenderer());
	}

	public function testAlternatingItemRendererDefaultIsEmpty(): void
	{
		$list = new TDataList();
		$this->assertSame('', $list->getAlternatingItemRenderer());
	}

	public function testSetAlternatingItemRenderer(): void
	{
		$list = new TDataList();
		$list->setAlternatingItemRenderer('AltRenderer');
		$this->assertSame('AltRenderer', $list->getAlternatingItemRenderer());
	}

	public function testEditItemRendererDefaultIsEmpty(): void
	{
		$list = new TDataList();
		$this->assertSame('', $list->getEditItemRenderer());
	}

	public function testSetEditItemRenderer(): void
	{
		$list = new TDataList();
		$list->setEditItemRenderer('EditRenderer');
		$this->assertSame('EditRenderer', $list->getEditItemRenderer());
	}

	public function testSelectedItemRendererDefaultIsEmpty(): void
	{
		$list = new TDataList();
		$this->assertSame('', $list->getSelectedItemRenderer());
	}

	public function testSetSelectedItemRenderer(): void
	{
		$list = new TDataList();
		$list->setSelectedItemRenderer('SelRenderer');
		$this->assertSame('SelRenderer', $list->getSelectedItemRenderer());
	}

	public function testSeparatorRendererDefaultIsEmpty(): void
	{
		$list = new TDataList();
		$this->assertSame('', $list->getSeparatorRenderer());
	}

	public function testSetSeparatorRenderer(): void
	{
		$list = new TDataList();
		$list->setSeparatorRenderer('SepRenderer');
		$this->assertSame('SepRenderer', $list->getSeparatorRenderer());
	}

	public function testHeaderRendererDefaultIsEmpty(): void
	{
		$list = new TDataList();
		$this->assertSame('', $list->getHeaderRenderer());
	}

	public function testSetHeaderRenderer(): void
	{
		$list = new TDataList();
		$list->setHeaderRenderer('HeaderRenderer');
		$this->assertSame('HeaderRenderer', $list->getHeaderRenderer());
	}

	public function testFooterRendererDefaultIsEmpty(): void
	{
		$list = new TDataList();
		$this->assertSame('', $list->getFooterRenderer());
	}

	public function testSetFooterRenderer(): void
	{
		$list = new TDataList();
		$list->setFooterRenderer('FooterRenderer');
		$this->assertSame('FooterRenderer', $list->getFooterRenderer());
	}

	public function testEmptyRendererDefaultIsEmpty(): void
	{
		$list = new TDataList();
		$this->assertSame('', $list->getEmptyRenderer());
	}

	public function testSetEmptyRenderer(): void
	{
		$list = new TDataList();
		$list->setEmptyRenderer('EmptyRenderer');
		$this->assertSame('EmptyRenderer', $list->getEmptyRenderer());
	}

	// ========================================================================
	// getHasHeader — ShowHeader × (template | renderer)
	// ========================================================================

	public function testGetHasHeaderFalseWithNoTemplateOrRenderer(): void
	{
		$list = new TDataList();
		// ShowHeader=true (default) but no template or renderer
		$this->assertFalse($list->getHasHeader());
	}

	public function testGetHasHeaderTrueWhenTemplateSetAndShowHeaderTrue(): void
	{
		$list = new TDataList();
		$list->setHeaderTemplate(new StubTemplate());
		$this->assertTrue($list->getHasHeader());
	}

	public function testGetHasHeaderTrueWhenRendererSetAndShowHeaderTrue(): void
	{
		$list = new TDataList();
		$list->setHeaderRenderer('SomeRenderer');
		$this->assertTrue($list->getHasHeader());
	}

	public function testGetHasHeaderFalseWhenShowHeaderFalseEvenWithTemplate(): void
	{
		$list = new TDataList();
		$list->setHeaderTemplate(new StubTemplate());
		$list->setShowHeader(false);
		$this->assertFalse($list->getHasHeader());
	}

	public function testGetHasHeaderFalseWhenShowHeaderFalseEvenWithRenderer(): void
	{
		$list = new TDataList();
		$list->setHeaderRenderer('SomeRenderer');
		$list->setShowHeader(false);
		$this->assertFalse($list->getHasHeader());
	}

	// ========================================================================
	// getHasFooter — ShowFooter × (template | renderer)
	// ========================================================================

	public function testGetHasFooterFalseWithNoTemplateOrRenderer(): void
	{
		$list = new TDataList();
		$this->assertFalse($list->getHasFooter());
	}

	public function testGetHasFooterTrueWhenTemplateSetAndShowFooterTrue(): void
	{
		$list = new TDataList();
		$list->setFooterTemplate(new StubTemplate());
		$this->assertTrue($list->getHasFooter());
	}

	public function testGetHasFooterTrueWhenRendererSetAndShowFooterTrue(): void
	{
		$list = new TDataList();
		$list->setFooterRenderer('SomeRenderer');
		$this->assertTrue($list->getHasFooter());
	}

	public function testGetHasFooterFalseWhenShowFooterFalseEvenWithTemplate(): void
	{
		$list = new TDataList();
		$list->setFooterTemplate(new StubTemplate());
		$list->setShowFooter(false);
		$this->assertFalse($list->getHasFooter());
	}

	// ========================================================================
	// getHasSeparators — no ShowSeparators guard
	// ========================================================================

	public function testGetHasSeparatorsFalseByDefault(): void
	{
		$list = new TDataList();
		$this->assertFalse($list->getHasSeparators());
	}

	public function testGetHasSeparatorsTrueWhenTemplateSet(): void
	{
		$list = new TDataList();
		$list->setSeparatorTemplate(new StubTemplate());
		$this->assertTrue($list->getHasSeparators());
	}

	public function testGetHasSeparatorsTrueWhenRendererSet(): void
	{
		$list = new TDataList();
		$list->setSeparatorRenderer('SepRenderer');
		$this->assertTrue($list->getHasSeparators());
	}

	// ========================================================================
	// getHeader / getFooter — null before databind
	// ========================================================================

	public function testGetHeaderNullInitially(): void
	{
		$list = new TDataList();
		$this->assertNull($list->getHeader());
	}

	public function testGetFooterNullInitially(): void
	{
		$list = new TDataList();
		$this->assertNull($list->getFooter());
	}

	// ========================================================================
	// SelectedItemIndex
	// ========================================================================

	public function testSelectedItemIndexDefaultIsMinusOne(): void
	{
		$list = new TDataList();
		$this->assertSame(-1, $list->getSelectedItemIndex());
	}

	public function testSetSelectedItemIndexNegativeClampedToMinusOne(): void
	{
		$list = new TDataList();
		$list->setSelectedItemIndex(-99);
		$this->assertSame(-1, $list->getSelectedItemIndex());
	}

	public function testSetSelectedItemIndex(): void
	{
		$list = new TDataList();
		$item = new SpyDataListItem();
		$item->setItemIndex(0);
		$item->setItemType(TListItemType::Item);
		$list->getItems()->add($item);

		$list->setSelectedItemIndex(0);
		$this->assertSame(0, $list->getSelectedItemIndex());
	}

	public function testSetSelectedItemIndexUpdatesItemType(): void
	{
		$list = new TDataList();
		$item = new SpyDataListItem();
		$item->setItemIndex(0);
		$item->setItemType(TListItemType::Item);
		$list->getItems()->add($item);

		$list->setSelectedItemIndex(0);
		$this->assertSame(TListItemType::SelectedItem, $item->getItemType());
	}

	public function testSetSelectedItemIndexDoesNotChangeEditItem(): void
	{
		// When item is in EditItem mode, setSelectedItemIndex must not change its type
		$list = new TDataList();
		$item = new SpyDataListItem();
		$item->setItemIndex(0);
		$item->setItemType(TListItemType::EditItem);
		$list->getItems()->add($item);

		$list->setSelectedItemIndex(0);
		// Item remains EditItem, not SelectedItem
		$this->assertSame(TListItemType::EditItem, $item->getItemType());
	}

	// ========================================================================
	// getSelectedItem
	// ========================================================================

	public function testGetSelectedItemNullWhenNoSelection(): void
	{
		$list = new TDataList();
		$this->assertNull($list->getSelectedItem());
	}

	public function testGetSelectedItemReturnsCorrectItem(): void
	{
		$list = new TDataList();
		$item = new SpyDataListItem();
		$item->setItemIndex(0);
		$item->setItemType(TListItemType::Item);
		$list->getItems()->add($item);
		$list->setSelectedItemIndex(0);

		$this->assertSame($item, $list->getSelectedItem());
	}

	// ========================================================================
	// getSelectedDataKey — throws when DataKeyField is empty
	// ========================================================================

	public function testGetSelectedDataKeyThrowsWhenDataKeyFieldEmpty(): void
	{
		$this->expectException(TInvalidOperationException::class);
		$list = new TDataList();
		$list->getSelectedDataKey();
	}

	// ========================================================================
	// EditItemIndex
	// ========================================================================

	public function testEditItemIndexDefaultIsMinusOne(): void
	{
		$list = new TDataList();
		$this->assertSame(-1, $list->getEditItemIndex());
	}

	public function testSetEditItemIndexNegativeClampedToMinusOne(): void
	{
		$list = new TDataList();
		$list->setEditItemIndex(-5);
		$this->assertSame(-1, $list->getEditItemIndex());
	}

	public function testSetEditItemIndex(): void
	{
		$list = new TDataList();
		$item = new SpyDataListItem();
		$item->setItemIndex(0);
		$item->setItemType(TListItemType::Item);
		$list->getItems()->add($item);

		$list->setEditItemIndex(0);
		$this->assertSame(0, $list->getEditItemIndex());
	}

	public function testSetEditItemIndexUpdatesItemType(): void
	{
		$list = new TDataList();
		$item = new SpyDataListItem();
		$item->setItemIndex(0);
		$item->setItemType(TListItemType::Item);
		$list->getItems()->add($item);

		$list->setEditItemIndex(0);
		$this->assertSame(TListItemType::EditItem, $item->getItemType());
	}

	// ========================================================================
	// getEditItem
	// ========================================================================

	public function testGetEditItemNullWhenNoEdit(): void
	{
		$list = new TDataList();
		$this->assertNull($list->getEditItem());
	}

	public function testGetEditItemReturnsCorrectItem(): void
	{
		$list = new TDataList();
		$item = new SpyDataListItem();
		$item->setItemIndex(0);
		$item->setItemType(TListItemType::Item);
		$list->getItems()->add($item);
		$list->setEditItemIndex(0);

		$this->assertSame($item, $list->getEditItem());
	}

	// ========================================================================
	// Style lazy initialisation — each style getter creates a TTableItemStyle
	// ========================================================================

	public function testGetItemStyleCreatesTableItemStyle(): void
	{
		$list = new TDataList();
		$style = $list->getItemStyle();
		$this->assertInstanceOf(TTableItemStyle::class, $style);
	}

	public function testGetItemStyleReturnsSameInstanceOnRepeatedCall(): void
	{
		$list = new TDataList();
		$this->assertSame($list->getItemStyle(), $list->getItemStyle());
	}

	public function testGetAlternatingItemStyleCreatesTableItemStyle(): void
	{
		$list = new TDataList();
		$this->assertInstanceOf(TTableItemStyle::class, $list->getAlternatingItemStyle());
	}

	public function testGetSelectedItemStyleCreatesTableItemStyle(): void
	{
		$list = new TDataList();
		$this->assertInstanceOf(TTableItemStyle::class, $list->getSelectedItemStyle());
	}

	public function testGetEditItemStyleCreatesTableItemStyle(): void
	{
		$list = new TDataList();
		$this->assertInstanceOf(TTableItemStyle::class, $list->getEditItemStyle());
	}

	public function testGetHeaderStyleCreatesTableItemStyle(): void
	{
		$list = new TDataList();
		$this->assertInstanceOf(TTableItemStyle::class, $list->getHeaderStyle());
	}

	public function testGetFooterStyleCreatesTableItemStyle(): void
	{
		$list = new TDataList();
		$this->assertInstanceOf(TTableItemStyle::class, $list->getFooterStyle());
	}

	public function testGetSeparatorStyleCreatesTableItemStyle(): void
	{
		$list = new TDataList();
		$this->assertInstanceOf(TTableItemStyle::class, $list->getSeparatorStyle());
	}

	// ========================================================================
	// CMD_* constants
	// ========================================================================

	public function testCmdSelectConstant(): void
	{
		$this->assertSame('Select', TDataList::CMD_SELECT);
	}

	public function testCmdEditConstant(): void
	{
		$this->assertSame('Edit', TDataList::CMD_EDIT);
	}

	public function testCmdUpdateConstant(): void
	{
		$this->assertSame('Update', TDataList::CMD_UPDATE);
	}

	public function testCmdDeleteConstant(): void
	{
		$this->assertSame('Delete', TDataList::CMD_DELETE);
	}

	public function testCmdCancelConstant(): void
	{
		$this->assertSame('Cancel', TDataList::CMD_CANCEL);
	}

	// ========================================================================
	// bubbleEvent — non-TDataListCommandEventParameter → false
	// ========================================================================

	public function testBubbleEventReturnsFalseForNonCommandEventParameter(): void
	{
		$list = new TDataList();
		$param = new \Prado\TEventParameter();
		$this->assertFalse($list->bubbleEvent(new NonDataListItem(), $param));
	}

	// ========================================================================
	// bubbleEvent — recognised commands (case-insensitive) → true
	// ========================================================================

	public function testBubbleEventReturnsTrueForSelectCommand(): void
	{
		$list = new TDataList();
		$param = $this->makeCommandParam('select', new NonDataListItem());
		$this->assertTrue($list->bubbleEvent(new NonDataListItem(), $param));
	}

	public function testBubbleEventReturnsTrueForSelectCommandUpperCase(): void
	{
		$list = new TDataList();
		$param = $this->makeCommandParam('SELECT', new NonDataListItem());
		$this->assertTrue($list->bubbleEvent(new NonDataListItem(), $param));
	}

	public function testBubbleEventReturnsTrueForEditCommand(): void
	{
		$list = new TDataList();
		$param = $this->makeCommandParam('Edit', new NonDataListItem());
		$this->assertTrue($list->bubbleEvent(new NonDataListItem(), $param));
	}

	public function testBubbleEventReturnsTrueForEditCommandLowerCase(): void
	{
		$list = new TDataList();
		$param = $this->makeCommandParam('edit', new NonDataListItem());
		$this->assertTrue($list->bubbleEvent(new NonDataListItem(), $param));
	}

	public function testBubbleEventReturnsTrueForDeleteCommand(): void
	{
		$list = new TDataList();
		$param = $this->makeCommandParam('Delete', new NonDataListItem());
		$this->assertTrue($list->bubbleEvent(new NonDataListItem(), $param));
	}

	public function testBubbleEventReturnsTrueForDeleteCommandLowerCase(): void
	{
		$list = new TDataList();
		$param = $this->makeCommandParam('delete', new NonDataListItem());
		$this->assertTrue($list->bubbleEvent(new NonDataListItem(), $param));
	}

	public function testBubbleEventReturnsTrueForUpdateCommand(): void
	{
		$list = new TDataList();
		$param = $this->makeCommandParam('Update', new NonDataListItem());
		$this->assertTrue($list->bubbleEvent(new NonDataListItem(), $param));
	}

	public function testBubbleEventReturnsTrueForUpdateCommandUpperCase(): void
	{
		$list = new TDataList();
		$param = $this->makeCommandParam('UPDATE', new NonDataListItem());
		$this->assertTrue($list->bubbleEvent(new NonDataListItem(), $param));
	}

	public function testBubbleEventReturnsTrueForCancelCommand(): void
	{
		$list = new TDataList();
		$param = $this->makeCommandParam('Cancel', new NonDataListItem());
		$this->assertTrue($list->bubbleEvent(new NonDataListItem(), $param));
	}

	public function testBubbleEventReturnsTrueForCancelCommandLowerCase(): void
	{
		$list = new TDataList();
		$param = $this->makeCommandParam('cancel', new NonDataListItem());
		$this->assertTrue($list->bubbleEvent(new NonDataListItem(), $param));
	}

	// ========================================================================
	// bubbleEvent — unrecognised command → false (onItemCommand still fires)
	// ========================================================================

	public function testBubbleEventReturnsFalseForUnknownCommand(): void
	{
		$list = new TDataList();
		$param = $this->makeCommandParam('unknown_command', new NonDataListItem());
		$this->assertFalse($list->bubbleEvent(new NonDataListItem(), $param));
	}

	// ========================================================================
	// Prado3 class-alias compatibility
	//
	// Prado::using() registers class_alias() entries as a side-effect of
	// autoloading.  The key invariants to verify:
	//
	// 1. The PHP `instanceof` operator used in renderItem() resolves correctly
	//    even when class aliases are present in the runtime, because instanceof
	//    works on the actual class hierarchy, not on string names.
	//
	// 2. TDataList (and its items) can be reflected by TComponentReflection
	//    via a Prado3 alias — this exercises the is_a() fix in TComponentReflection.
	//
	// 3. TDataList property values (templates, renderers, etc.) are pure string
	//    or object storage and are unaffected by class aliases.
	// ========================================================================

	public function testRenderItemRawLayoutInstanceofWorksWithPrado3AliasesPresent(): void
	{
		// With Prado3Alias_TDataListItem registered in the PHP runtime, the
		// `$item instanceof TDataListItem` check inside renderItem() must still
		// work correctly for genuine TDataListItem subclasses.
		$list = new TDataList();
		$item = new SpyDataListItem(); // extends TDataListItem
		$item->setItemIndex(0);
		$item->setItemType(TListItemType::Item);
		$list->getItems()->add($item);

		$list->renderItem($this->writer(), $this->rawRepeatInfo(), TListItemType::Item, 0);

		// Alias presence must not break the instanceof branch — setTagName must fire.
		$this->assertTrue($item->setTagNameCalled,
			'instanceof TDataListItem must work even when Prado3 class aliases are registered');
		$this->assertSame('div', $item->lastTagName);
	}

	public function testPrado3AliasTDataListCanBeReflected(): void
	{
		// Reflecting TDataList via a Prado3 alias exercises the is_a() fix in
		// TComponentReflection::reflect().  If the fix is missing, $isComponent
		// would wrongly be false and getProperties() would return [].
		$r = new \Prado\TComponentReflection('Prado3Alias_TDataList');
		$this->assertSame('Prado3Alias_TDataList', $r->getClassName());
		$this->assertNotEmpty($r->getProperties(),
			'TDataList properties must be reflected when accessed via a Prado3 alias');
	}

	public function testPrado3AliasTDataListEventsAreReflected(): void
	{
		$r = new \Prado\TComponentReflection('Prado3Alias_TDataList');
		$events = $r->getEvents();
		// TDataList defines OnItemCommand, OnEditCommand, etc.
		$this->assertNotEmpty($events,
			'TDataList events must be reflected when accessed via a Prado3 alias');
	}

	public function testPrado3AliasTDataListDeclaringClassReturnsFqn(): void
	{
		$r = new \Prado\TComponentReflection('Prado3Alias_TDataList');
		foreach ($r->getProperties() as $name => $prop) {
			$this->assertTrue(
				class_exists($prop['class'], false),
				"Property '$name' declares class '{$prop['class']}' which is not a known class"
			);
		}
	}

	public function testPrado3AliasTDataListItemCanBeReflected(): void
	{
		$r = new \Prado\TComponentReflection('Prado3Alias_TDataListItem');
		$this->assertSame('Prado3Alias_TDataListItem', $r->getClassName());
		// TDataListItem defines ItemIndex, ItemType, and Data properties
		$this->assertArrayHasKey('ItemIndex', $r->getProperties());
		$this->assertArrayHasKey('ItemType', $r->getProperties());
	}

	public function testPrado3AliasTemplateAndRendererStorageUnaffected(): void
	{
		// Template and renderer values are stored as-is (object ref / string).
		// Prado3 class aliases in the runtime must not change their storage or retrieval.
		$list = new TDataList();
		$tpl = new StubTemplate();
		$list->setItemTemplate($tpl);
		$list->setItemRenderer('MyNamespace.MyItemRenderer');

		$this->assertSame($tpl, $list->getItemTemplate());
		$this->assertSame('MyNamespace.MyItemRenderer', $list->getItemRenderer());
	}

	// ========================================================================
	// Data binding (#1497)
	// ========================================================================

	private function newBoundList(array $data, array $templates = [], ?callable $setup = null): TDataListTestList
	{
		$list = new TDataListTestList();
		$list->setItemTemplate(new TTestDataTemplate('I'));
		foreach ($templates as $setter => $label) {
			$list->$setter(new TTestDataTemplate($label));
		}
		if ($setup) {
			$setup($list);
		}
		$list->setDataSource($data);
		$list->dataBind();
		return $list;
	}

	private function controlTypes(TControl $control): array
	{
		$types = [];
		foreach ($control->getControls() as $child) {
			$types[] = method_exists($child, 'getItemType') ? $child->getItemType() . $child->getItemIndex() : $child::class;
		}
		return $types;
	}

	private function renderRaw(TDataList $list): string
	{
		$list->setRepeatLayout(TRepeatLayout::Raw);
		$writer = $this->writer();
		$list->render($writer);
		return $writer->flush();
	}

	public static function createItemTemplateProvider(): array
	{
		return [
			[TListItemType::Item, 'setItemTemplate'],
			[TListItemType::AlternatingItem, 'setAlternatingItemTemplate'],
			[TListItemType::SelectedItem, 'setSelectedItemTemplate'],
			[TListItemType::EditItem, 'setEditItemTemplate'],
			[TListItemType::Header, 'setHeaderTemplate'],
			[TListItemType::Footer, 'setFooterTemplate'],
			[TListItemType::Separator, 'setSeparatorTemplate'],
		];
	}

	/**
	 * @dataProvider createItemTemplateProvider
	 */
	public function testCreateItemInstantiatesTheTemplateOfTheType(string $type, string $setter): void
	{
		$list = new TDataListTestList();
		$list->$setter($template = new TTestDataTemplate($type));
		$item = $list->callCreateItem(4, $type);
		$this->assertInstanceOf(TDataListItem::class, $item);
		$this->assertSame(4, $item->getItemIndex());
		$this->assertSame($type, $item->getItemType());
		$this->assertSame(1, $template->getInstantiations());
	}

	public static function createItemRendererProvider(): array
	{
		return [
			[TListItemType::Item, 'setItemRenderer'],
			[TListItemType::AlternatingItem, 'setAlternatingItemRenderer'],
			[TListItemType::SelectedItem, 'setSelectedItemRenderer'],
			[TListItemType::EditItem, 'setEditItemRenderer'],
			[TListItemType::Header, 'setHeaderRenderer'],
			[TListItemType::Footer, 'setFooterRenderer'],
			[TListItemType::Separator, 'setSeparatorRenderer'],
		];
	}

	/**
	 * @dataProvider createItemRendererProvider
	 */
	public function testCreateItemPrefersTheRendererOfTheType(string $type, string $setter): void
	{
		$list = new TDataListTestList();
		foreach (self::createItemTemplateProvider() as [$t, $templateSetter]) {
			$list->$templateSetter(new TTestDataTemplate($t));
		}
		$list->$setter(TDataListTestRenderer::class);
		$item = $list->callCreateItem(2, $type);
		$this->assertInstanceOf(TDataListTestRenderer::class, $item);
		$this->assertSame(2, $item->getItemIndex());
		$this->assertSame($type, $item->getItemType());
	}

	public function testCreateItemWithAPlainRendererSetsNoIndexOrType(): void
	{
		$list = new TDataListTestList();
		$list->setItemRenderer(TDataListTestPlainRenderer::class);
		$this->assertInstanceOf(TDataListTestPlainRenderer::class, $list->callCreateItem(0, TListItemType::Item));
	}

	public static function createItemFallbackProvider(): array
	{
		// [type, index, templates set, label expected]
		return [
			'alternating to item' => [TListItemType::AlternatingItem, 1, ['setItemTemplate' => 'I'], 'I'],
			'selected even to item' => [TListItemType::SelectedItem, 2, ['setItemTemplate' => 'I', 'setAlternatingItemTemplate' => 'A'], 'I'],
			'selected odd to alternating' => [TListItemType::SelectedItem, 3, ['setItemTemplate' => 'I', 'setAlternatingItemTemplate' => 'A'], 'A'],
			'selected odd to item' => [TListItemType::SelectedItem, 3, ['setItemTemplate' => 'I'], 'I'],
			'edit to selected' => [TListItemType::EditItem, 0, ['setItemTemplate' => 'I', 'setSelectedItemTemplate' => 'S'], 'S'],
			'edit odd to alternating' => [TListItemType::EditItem, 1, ['setItemTemplate' => 'I', 'setAlternatingItemTemplate' => 'A'], 'A'],
		];
	}

	/**
	 * @dataProvider createItemFallbackProvider
	 */
	public function testCreateItemFallsBackToALowerTemplate(string $type, int $index, array $templates, string $expected): void
	{
		$list = new TDataListTestList();
		foreach ($templates as $setter => $label) {
			$list->$setter(new TTestDataTemplate($label));
		}
		$item = $list->callCreateItem($index, $type);
		$this->assertSame($type, $item->getItemType());
		$this->assertSame($expected, $item->getControls()->itemAt(0)->getText());
	}

	public function testCreateItemFallsBackToTheItemRenderer(): void
	{
		$list = new TDataListTestList();
		$list->setItemRenderer(TDataListTestRenderer::class);
		foreach ([TListItemType::AlternatingItem, TListItemType::SelectedItem, TListItemType::EditItem] as $type) {
			$this->assertInstanceOf(TDataListTestRenderer::class, $list->callCreateItem(1, $type), $type);
		}
	}

	public function testCreateItemWithoutTemplateOrRendererIsNull(): void
	{
		$list = new TDataListTestList();
		foreach (self::createItemTemplateProvider() as [$type]) {
			$this->assertNull($list->callCreateItem(0, $type), $type);
		}
	}

	public function testCreateItemRejectsAnUnknownType(): void
	{
		$list = new TDataListTestList();
		$this->expectException(TInvalidDataValueException::class);
		$list->callCreateItem(0, TListItemType::Pager);
	}

	public function testDataBindCreatesItemsInOrder(): void
	{
		$list = $this->newBoundList(['a', 'b', 'c'], ['setHeaderTemplate' => 'H', 'setFooterTemplate' => 'F', 'setSeparatorTemplate' => 'S']);
		$this->assertSame(['Header-1', 'Item0', 'Separator0', 'AlternatingItem1', 'Separator1', 'Item2', 'Footer-1'], $this->controlTypes($list));
		$this->assertSame(3, $list->getItemCount());
		$this->assertSame(TListItemType::Header, $list->getHeader()->getItemType());
		$this->assertSame(TListItemType::Footer, $list->getFooter()->getItemType());
		$this->assertSame([0, 1, 2], $list->getDataKeys()->toArray());
		$this->assertSame(3, $list->getViewState('ItemCount'));
		$this->assertSame('b', $list->getItems()->itemAt(1)->getData());
		$this->assertSame('I:b', $list->getItems()->itemAt(1)->getControls()->itemAt(0)->getText());
	}

	public function testDataBindMarksTheSelectedAndEditItems(): void
	{
		$list = $this->newBoundList(['a', 'b', 'c', 'd'], [], function ($list) {
			$list->setSelectedItemIndex(1);
			$list->setEditItemIndex(2);
		});
		$this->assertSame(['Item0', 'SelectedItem1', 'EditItem2', 'AlternatingItem3'], $this->controlTypes($list));
		$this->assertSame($list->getItems()->itemAt(1), $list->getSelectedItem());
		$this->assertSame($list->getItems()->itemAt(2), $list->getEditItem());
	}

	public function testDataBindReadsTheKeyField(): void
	{
		$list = $this->newBoundList([['id' => 'k1'], ['id' => 'k2']], [], function ($list) {
			$list->setDataKeyField('id');
			$list->setSelectedItemIndex(1);
		});
		$this->assertSame(['k1', 'k2'], $list->getDataKeys()->toArray());
		$this->assertSame('k2', $list->getSelectedDataKey());
		$list->setSelectedItemIndex(5);
		$this->assertNull($list->getSelectedDataKey());
		$this->assertSame('v', $list->callGetDataFieldValue(['f' => 'v'], 'f'));
	}

	public function testDataBindSetsTheDataOfRenderers(): void
	{
		$list = new TDataList();
		$list->setItemRenderer(TDataListTestRenderer::class);
		$list->setSeparatorRenderer(TDataListTestPlainRenderer::class);
		$list->setDataSource(['a', 'b']);
		$list->dataBind();
		$this->assertSame('<span>RItem0=a</span>P<span>RAlternatingItem1=b</span>', $this->renderRaw($list));
	}

	public function testDataBindRaisesItemEvents(): void
	{
		$created = $bound = [];
		$this->newBoundList(['a', 'b'], [], function ($list) use (&$created, &$bound) {
			$list->attachEventHandler('OnItemCreated', function ($sender, $param) use (&$created) {
				$this->assertInstanceOf(TDataListItemEventParameter::class, $param);
				$created[] = [$param->getItem()->getItemIndex(), $param->getItem()->getParent()];
			});
			$list->attachEventHandler('OnItemDataBound', function ($sender, $param) use (&$bound) {
				$bound[] = $param->getItem()->getControls()->itemAt(0)->getText();
			});
		});
		$this->assertSame([[0, null], [1, null]], $created);
		$this->assertSame(['I:a', 'I:b'], $bound);
	}

	public function testDataBindEmptyUsesTheEmptyTemplate(): void
	{
		$list = $this->newBoundList([], ['setHeaderTemplate' => 'H', 'setEmptyTemplate' => 'E']);
		$this->assertSame(0, $list->getItemCount());
		$this->assertNull($list->getHeader());
		$this->assertSame([\Prado\Web\UI\WebControls\TLiteral::class], $this->controlTypes($list));
		$this->assertSame('<span>E</span>', $this->renderRaw($list));
	}

	public function testDataBindEmptyPrefersTheEmptyRenderer(): void
	{
		$list = $this->newBoundList([], ['setEmptyTemplate' => 'E'], function ($list) {
			$list->setEmptyRenderer(TDataListTestPlainRenderer::class);
		});
		$this->assertSame('<span>P</span>', $this->renderRaw($list));
	}

	public function testRenderWithoutItemsOrEmptyContentRendersNothing(): void
	{
		$list = $this->newBoundList([]);
		$this->assertSame('', $this->renderRaw($list), 'no controls');
		$list->getControls()->add(new TDataListTestPlainRenderer());
		$this->assertSame('', $this->renderRaw($list), 'controls but no items and no empty content');
	}

	public function testRenderRawLayoutRendersHeaderItemsSeparatorsAndFooter(): void
	{
		$list = $this->newBoundList(['a', 'b'], ['setHeaderTemplate' => 'H', 'setFooterTemplate' => 'F', 'setSeparatorTemplate' => 'S']);
		$this->assertSame('<div>H</div><div>I:a</div><div>S</div><div>I:b</div><div>F</div>', $this->renderRaw($list));
	}

	public function testRenderTableLayoutRendersTheItemsInCells(): void
	{
		$list = $this->newBoundList(['a', 'b']);
		$list->setID('dl');
		$writer = $this->writer();
		$list->render($writer);
		$html = $writer->flush();
		$this->assertStringContainsString('<table id="dl">', $html);
		$this->assertStringContainsString('<span>I:a</span>', $html);
		$this->assertStringContainsString('<span>I:b</span>', $html);
	}

	// ------------------------------------------------------------------
	// Styles
	// ------------------------------------------------------------------

	public function testApplyItemStylesMergesTheStyleOfEachItemType(): void
	{
		$list = $this->newBoundList(['a', 'b', 'c', 'd', 'e'], ['setHeaderTemplate' => 'H', 'setFooterTemplate' => 'F', 'setSeparatorTemplate' => 'S'], function ($list) {
			$list->setSelectedItemIndex(2);
			$list->setEditItemIndex(3);
		});
		$list->getItemStyle()->setCssClass('item');
		$list->getItemStyle()->setForeColor('red');
		$list->getAlternatingItemStyle()->setCssClass('alt');
		$list->getSelectedItemStyle()->setCssClass('sel');
		$list->getSelectedItemStyle()->setBackColor('blue');
		$list->getEditItemStyle()->setCssClass('edit');
		$list->getHeaderStyle()->setCssClass('head');
		$list->getFooterStyle()->setCssClass('foot');
		$list->getSeparatorStyle()->setCssClass('sep');
		$list->callApplyItemStyles();

		$items = $list->getItems();
		$this->assertSame('item', $items->itemAt(0)->getStyle()->getCssClass());
		$this->assertSame('alt', $items->itemAt(1)->getStyle()->getCssClass());
		$this->assertSame('red', $items->itemAt(1)->getStyle()->getForeColor(), 'AlternatingItemStyle inherits ItemStyle');
		$this->assertSame('sel', $items->itemAt(2)->getStyle()->getCssClass());
		$this->assertSame('edit', $items->itemAt(3)->getStyle()->getCssClass());
		$this->assertSame('blue', $items->itemAt(3)->getStyle()->getBackColor(), 'EditItemStyle inherits SelectedItemStyle');
		$this->assertSame('item', $items->itemAt(4)->getStyle()->getCssClass());
		$this->assertSame('head', $list->getHeader()->getStyle()->getCssClass());
		$this->assertSame('foot', $list->getFooter()->getStyle()->getCssClass());
		$controls = $list->getControls();
		foreach ([2, 4, 6, 8] as $i) {
			$this->assertSame(TListItemType::Separator, $controls->itemAt($i)->getItemType());
			$this->assertSame('sep', $controls->itemAt($i)->getStyle()->getCssClass(), "separator at $i");
		}
	}

	public function testApplyItemStylesUsesTheItemStyleForAlternatingItemsWithoutTheirOwn(): void
	{
		$list = $this->newBoundList(['a', 'b'], [], function ($list) {
			$list->setSelectedItemIndex(0);
			$list->setEditItemIndex(1);
		});
		$list->getItemStyle()->setCssClass('item');
		$list->getSelectedItemStyle()->setCssClass('sel');
		$list->callApplyItemStyles();
		$this->assertSame('sel', $list->getItems()->itemAt(0)->getStyle()->getCssClass());
		$this->assertSame('sel', $list->getItems()->itemAt(1)->getStyle()->getCssClass(), 'an edit item without EditItemStyle uses SelectedItemStyle');

		$list = $this->newBoundList(['a', 'b']);
		$list->getItemStyle()->setCssClass('item');
		$list->callApplyItemStyles();
		$this->assertSame('item', $list->getItems()->itemAt(1)->getStyle()->getCssClass());
	}

	public function testApplyItemStylesWithoutStylesLeavesItemsUnstyled(): void
	{
		$list = $this->newBoundList(['a', 'b', 'c'], ['setSeparatorTemplate' => 'S', 'setHeaderTemplate' => 'H', 'setFooterTemplate' => 'F']);
		$list->callApplyItemStyles();
		foreach ($list->getControls() as $control) {
			$this->assertFalse($control->getHasStyle());
		}
	}

	public function testApplyItemStylesSkipsControlsThatAreNotStyleable(): void
	{
		$list = new TDataListTestList();
		foreach (['setItemRenderer', 'setHeaderRenderer', 'setFooterRenderer', 'setSeparatorRenderer'] as $setter) {
			$list->$setter(TDataListTestPlainRenderer::class);
		}
		$list->setDataSource(['a', 'b']);
		$list->dataBind();
		foreach (['getItemStyle', 'getHeaderStyle', 'getFooterStyle', 'getSeparatorStyle'] as $getter) {
			$list->$getter()->setCssClass('x');
		}
		$list->callApplyItemStyles();
		$this->assertSame(5, $list->getControls()->getCount());
	}

	public function testSeparatorStyleWithoutAHeaderStartsAtTheFirstSeparator(): void
	{
		$list = $this->newBoundList(['a', 'b', 'c'], ['setSeparatorTemplate' => 'S']);
		$list->getSeparatorStyle()->setCssClass('sep');
		$list->callApplyItemStyles();
		$controls = $list->getControls();
		$this->assertFalse($controls->itemAt(0)->getHasStyle());
		$this->assertSame('sep', $controls->itemAt(1)->getStyle()->getCssClass());
		$this->assertSame('sep', $controls->itemAt(3)->getStyle()->getCssClass());
	}

	public function testGenerateItemStyleMovesTheItemStyleToTheRepeater(): void
	{
		$list = $this->newBoundList(['a', 'b'], ['setHeaderTemplate' => 'H', 'setFooterTemplate' => 'F', 'setSeparatorTemplate' => 'S']);
		$list->getHeader()->getStyle()->setCssClass('h');
		$list->getFooter()->getStyle()->setCssClass('f');
		$list->getControls()->itemAt(2)->getStyle()->setCssClass('s');
		$list->getItems()->itemAt(1)->getStyle()->setCssClass('i');

		$this->assertSame('h', $list->generateItemStyle(TListItemType::Header, -1)->getCssClass());
		$this->assertFalse($list->getHeader()->getHasStyle(), 'the item style is cleared');
		$this->assertSame('f', $list->generateItemStyle(TListItemType::Footer, -1)->getCssClass());
		$this->assertSame('s', $list->generateItemStyle(TListItemType::Separator, 0)->getCssClass());
		$this->assertSame('i', $list->generateItemStyle(TListItemType::AlternatingItem, 1)->getCssClass());
		$this->assertNull($list->generateItemStyle(TListItemType::Item, 0), 'an item without a style');
		$this->assertNull($list->generateItemStyle(TListItemType::Pager, 0), 'no item of the type');
	}

	public function testGenerateItemStyleOfASeparatorWithoutAHeader(): void
	{
		$list = $this->newBoundList(['a', 'b'], ['setSeparatorTemplate' => 'S']);
		$list->getControls()->itemAt(1)->getStyle()->setCssClass('s');
		$this->assertSame('s', $list->generateItemStyle(TListItemType::Separator, 0)->getCssClass());
	}

	public function testGenerateItemStyleOfAPlainRendererIsNull(): void
	{
		$list = new TDataList();
		$list->setItemRenderer(TDataListTestPlainRenderer::class);
		$list->setDataSource(['a']);
		$list->dataBind();
		$this->assertNull($list->generateItemStyle(TListItemType::Item, 0));
	}

	// ------------------------------------------------------------------
	// View state
	// ------------------------------------------------------------------

	public function testSaveStateKeepsTheItemCount(): void
	{
		$list = $this->newBoundList(['a', 'b']);
		$list->clearViewState('ItemCount');
		$list->saveState();
		$this->assertSame(2, $list->getViewState('ItemCount'));

		$list = new TDataList();
		$list->setViewState('ItemCount', 3, 0);
		$list->saveState();
		$this->assertSame(0, $list->getViewState('ItemCount', 0), 'without items');
	}

	public function testLoadStateRestoresTheItems(): void
	{
		$list = new TDataListTestList();
		foreach (['setItemTemplate' => 'I', 'setHeaderTemplate' => 'H', 'setFooterTemplate' => 'F', 'setSeparatorTemplate' => 'S'] as $setter => $label) {
			$list->$setter(new TTestDataTemplate($label));
		}
		$created = 0;
		$list->attachEventHandler('OnItemCreated', function () use (&$created) {
			$created++;
		});
		$list->setSelectedItemIndex(1);
		$list->setEditItemIndex(2);
		$list->setViewState('ItemCount', 4, 0);
		$list->loadState();

		$this->assertSame(['Header-1', 'Item0', 'Separator0', 'SelectedItem1', 'Separator1', 'EditItem2', 'Separator2', 'AlternatingItem3', 'Footer-1'], $this->controlTypes($list));
		$this->assertSame(9, $created);
		$this->assertSame(4, $list->getItemCount());
		$this->assertSame(0, $list->getViewState('ItemCount', 0));
	}

	public function testLoadStateWithoutItemsCreatesTheEmptyContent(): void
	{
		$list = new TDataList();
		$list->setItemTemplate(new TTestDataTemplate('I'));
		$list->setEmptyTemplate(new TTestDataTemplate('E'));
		$list->loadState();
		$this->assertSame([\Prado\Web\UI\WebControls\TLiteral::class], $this->controlTypes($list));

		$list = new TDataList();
		$list->setEmptyRenderer(TDataListTestPlainRenderer::class);
		$list->loadState();
		$this->assertSame([TDataListTestPlainRenderer::class], $this->controlTypes($list));

		$list = new TDataList();
		$list->loadState();
		$this->assertSame(0, $list->getControls()->getCount());
	}

	public function testLoadStateAfterDataBindingKeepsTheItems(): void
	{
		$list = $this->newBoundList(['a']);
		$item = $list->getItems()->itemAt(0);
		$list->loadState();
		$this->assertSame($item, $list->getItems()->itemAt(0));
	}

	public function testResetClearsItemsHeaderAndFooter(): void
	{
		$list = $this->newBoundList(['a'], ['setHeaderTemplate' => 'H', 'setFooterTemplate' => 'F']);
		$list->reset();
		$this->assertSame(0, $list->getControls()->getCount());
		$this->assertSame(0, $list->getItemCount());
		$this->assertNull($list->getHeader());
		$this->assertNull($list->getFooter());
	}

	// ------------------------------------------------------------------
	// Commands
	// ------------------------------------------------------------------

	public function testAButtonCommandBubblesThroughTheItem(): void
	{
		$list = new TDataList();
		$list->setItemTemplate(new class () implements ITemplate {
			public function instantiateIn($parent)
			{
				$button = new TButton();
				$button->setCommandName('Select');
				$parent->getControls()->add($button);
			}

			public function getIncludedFiles()
			{
				return [];
			}
		});
		$list->setDataSource(['a', 'b']);
		$list->dataBind();
		$events = [];
		foreach (['OnItemCommand', 'OnSelectedIndexChanged'] as $event) {
			$list->attachEventHandler($event, function ($sender, $param) use (&$events, $event) {
				$events[] = [$event, $param->getItem()->getItemIndex(), $param->getCommandSource()::class];
			});
		}
		$list->getItems()->itemAt(1)->getControls()->itemAt(0)->onCommand(new TCommandEventParameter('Select', ''));
		$this->assertSame([['OnItemCommand', 1, TButton::class], ['OnSelectedIndexChanged', 1, TButton::class]], $events);
		$this->assertSame(1, $list->getSelectedItemIndex());
		$this->assertSame(TListItemType::SelectedItem, $list->getItems()->itemAt(1)->getItemType());
	}

	public static function commandEventProvider(): array
	{
		return [['Edit', 'OnEditCommand'], ['Delete', 'OnDeleteCommand'], ['Update', 'OnUpdateCommand'], ['Cancel', 'OnCancelCommand']];
	}

	/**
	 * @dataProvider commandEventProvider
	 */
	public function testBubbleEventRaisesTheCommandEvent(string $command, string $event): void
	{
		$list = new TDataList();
		$raised = [];
		foreach (['OnItemCommand', $event] as $name) {
			$list->attachEventHandler($name, function () use (&$raised, $name) {
				$raised[] = $name;
			});
		}
		$this->assertTrue($list->bubbleEvent(new TControl(), $this->makeCommandParam(strtolower($command), new TDataListItem())));
		$this->assertSame(['OnItemCommand', $event], $raised);
	}

	public function testSelectCommandFromAPlainItemKeepsTheSelection(): void
	{
		$list = new TDataList();
		$list->setSelectedItemIndex(3);
		$raised = 0;
		$list->attachEventHandler('OnSelectedIndexChanged', function () use (&$raised) {
			$raised++;
		});
		$this->assertTrue($list->bubbleEvent(new TControl(), $this->makeCommandParam('Select', new NonDataListItem())));
		$this->assertSame(3, $list->getSelectedItemIndex());
		$this->assertSame(1, $raised);
	}

	public function testItemsBubbleCommands(): void
	{
		$source = new TControl();
		foreach ([new TDataListItem(), new TDataListTestRenderer()] as $item) {
			$parent = new class () extends TControl {
				public $received;

				public function bubbleEvent($sender, $param)
				{
					$this->received = $param;
					return true;
				}
			};
			$parent->getControls()->add($item);
			$this->assertTrue($item->bubbleEvent($source, new TCommandEventParameter('Go', 'p')));
			$this->assertInstanceOf(TDataListCommandEventParameter::class, $parent->received);
			$this->assertSame($item, $parent->received->getItem());
			$this->assertSame($source, $parent->received->getCommandSource());
			$this->assertSame('Go', $parent->received->getCommandName());
			$this->assertSame('p', $parent->received->getCommandParameter());
			$this->assertFalse($item->bubbleEvent($source, new TEventParameter()));
		}
	}

	// ------------------------------------------------------------------
	// TDataListItem, TDataListItemRenderer, TDataListItemCollection
	// ------------------------------------------------------------------

	public function testDataListItemProperties(): void
	{
		$item = new TDataListItem();
		$this->assertInstanceOf(TTableItemStyle::class, $item->getStyle());
		$item->setItemIndex('3');
		$item->setItemType('edititem');
		$item->setData('d');
		$this->assertSame(3, $item->getItemIndex());
		$this->assertSame(TListItemType::EditItem, $item->getItemType());
		$this->assertSame('d', $item->getData());
		$writer = $this->writer();
		$item->render($writer);
		$this->assertSame('<span></span>', $writer->flush());
	}

	public function testDataListItemRendererStyle(): void
	{
		$renderer = new TDataListTestRenderer();
		$this->assertFalse($renderer->getHasStyle());
		$style = $renderer->getStyle();
		$this->assertInstanceOf(TTableItemStyle::class, $style);
		$this->assertTrue($renderer->getHasStyle());
		$this->assertSame($style, $renderer->getStyle());
		$style->setCssClass('c');
		$renderer->setItemType(TListItemType::Item);
		$renderer->setItemIndex(0);
		$writer = $this->writer();
		$renderer->render($writer);
		$this->assertSame('<span class="c">RItem0</span>', $writer->flush());
		$renderer->clearStyle();
		$this->assertFalse($renderer->getHasStyle());
		$writer = $this->writer();
		$renderer->render($writer);
		$this->assertSame('<span>RItem0</span>', $writer->flush());
	}

	public function testDataListItemRendererWithoutATagRendersItsContents(): void
	{
		$renderer = new TDataListTestNoTagRenderer();
		$renderer->setItemType(TListItemType::Footer);
		$renderer->setItemIndex(-1);
		$writer = $this->writer();
		$renderer->render($writer);
		$this->assertSame('RFooter-1', $writer->flush());
	}

	public function testDataListItemRendererRendersItsChildren(): void
	{
		$renderer = new class () extends TDataListItemRenderer {
			public function getTemplate()
			{
				return null;
			}
		};
		$literal = new \Prado\Web\UI\WebControls\TLiteral();
		$literal->setText('child');
		$renderer->getControls()->add($literal);
		$writer = $this->writer();
		$renderer->render($writer);
		$this->assertSame('<span>child</span>', $writer->flush());
	}

	public function testItemCollectionAcceptsOnlyControls(): void
	{
		$items = new TDataListItemCollection();
		$items->add($item = new TDataListItem());
		$this->assertSame($item, $items->itemAt(0));
		$this->expectException(TInvalidDataTypeException::class);
		$items->add('item');
	}

	// ------------------------------------------------------------------
	// TBaseDataList
	// ------------------------------------------------------------------

	public function testBaseDataListTableProperties(): void
	{
		$list = new TDataList();
		$this->assertSame(-1, $list->getCellSpacing());
		$this->assertSame(-1, $list->getCellPadding());
		$this->assertSame(THorizontalAlign::NotSet, $list->getHorizontalAlign());
		$this->assertSame(TTableGridLines::None, $list->getGridLines());
		$this->assertFalse($list->getHasStyle(), 'the getters create no style');

		$list->setCellSpacing(2);
		$list->setCellPadding(3);
		$list->setHorizontalAlign(THorizontalAlign::Center);
		$list->setGridLines(TTableGridLines::Both);
		$this->assertInstanceOf(TTableStyle::class, $list->getStyle());
		$this->assertSame(2, $list->getCellSpacing());
		$this->assertSame(3, $list->getCellPadding());
		$this->assertSame(THorizontalAlign::Center, $list->getHorizontalAlign());
		$this->assertSame(TTableGridLines::Both, $list->getGridLines());
	}

	public function testBaseDataListKeysAndSelectedIndexChanged(): void
	{
		$list = new TDataList();
		$this->assertSame('', $list->getDataKeyField());
		$keys = $list->getDataKeys();
		$this->assertInstanceOf(TList::class, $keys);
		$this->assertSame($keys, $list->getDataKeys());
		$raised = null;
		$list->attachEventHandler('OnSelectedIndexChanged', function ($sender, $param) use (&$raised) {
			$raised = $param;
		});
		$list->onSelectedIndexChanged($param = new TEventParameter());
		$this->assertSame($param, $raised);
	}
}
