<?php

use PHPUnit\Framework\TestCase;
use Prado\IO\TTextWriter;
use Prado\Web\UI\ITemplate;
use Prado\Web\UI\TCommandEventParameter;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\TControl;
use Prado\Web\UI\WebControls\TDataList;
use Prado\Web\UI\WebControls\TDataListCommandEventParameter;
use Prado\Web\UI\WebControls\TDataListItem;
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
}
