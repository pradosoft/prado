<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Collections\TList;
use Prado\Collections\TMap;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\Harness\Web\UI\TTestDataTemplate;
use Prado\TEventParameter;
use Prado\Web\UI\INamingContainer;
use Prado\Web\UI\ITemplate;
use Prado\Web\UI\TCommandEventParameter;
use Prado\Web\UI\TControl;
use Prado\Web\UI\WebControls\IItemDataRenderer;
use Prado\Web\UI\WebControls\TButton;
use Prado\Web\UI\WebControls\TDataBoundControl;
use Prado\Web\UI\WebControls\TItemDataRenderer;
use Prado\Web\UI\WebControls\TListItemType;
use Prado\Web\UI\WebControls\TLiteral;
use Prado\Web\UI\WebControls\TRepeater;
use Prado\Web\UI\WebControls\TRepeaterCommandEventParameter;
use Prado\Web\UI\WebControls\TRepeaterItem;
use Prado\Web\UI\WebControls\TRepeaterItemCollection;
use Prado\Web\UI\WebControls\TRepeaterItemEventParameter;
use Prado\Web\UI\WebControls\TRepeaterItemRenderer;

/**
 * Item renderer without a template file; renders "R[type,index]=data".
 */
class TRepeaterTestItemRenderer extends TRepeaterItemRenderer
{
	public function getTemplate()
	{
		return null;
	}

	public function render($writer)
	{
		$data = $this->getData();
		$writer->write('R[' . $this->getItemType() . ',' . $this->getItemIndex() . ']' . ($data === null ? '' : '=' . (is_scalar($data) ? $data : json_encode($data))));
	}
}

/**
 * A plain control as renderer: neither IDataRenderer nor IItemDataRenderer.
 */
class TRepeaterTestPlainRenderer extends TControl
{
	public function render($writer)
	{
		$writer->write('P');
	}
}

/**
 * Exposes the protected TRepeater members.
 */
class TRepeaterTestRepeater extends TRepeater
{
	public function callCreateItem($index, $type)
	{
		return $this->createItem($index, $type);
	}

	public function callGetDataFieldValue($data, $field)
	{
		return $this->getDataFieldValue($data, $field);
	}

	public function callRestoreItemsFromViewState()
	{
		$this->restoreItemsFromViewState();
	}

	public function markDataBound($value)
	{
		$this->setIsDataBound($value);
	}
}

class TRepeaterTest extends TestCase
{
	use TWebControlRenderTrait;

	private function newRepeater(): TRepeaterTestRepeater
	{
		$repeater = new TRepeaterTestRepeater();
		$repeater->setItemTemplate(new TTestDataTemplate('I'));
		return $repeater;
	}

	private function bind(TRepeater $repeater, $data): TRepeater
	{
		$repeater->setDataSource($data);
		$repeater->dataBind();
		return $repeater;
	}

	private function itemTypes(TControl $control): array
	{
		$types = [];
		foreach ($control->getControls() as $child) {
			$types[] = $child instanceof TRepeaterItem || $child instanceof IItemDataRenderer ? $child->getItemType() . $child->getItemIndex() : $child::class;
		}
		return $types;
	}

	// ------------------------------------------------------------------
	// Hierarchy, renderer and template properties
	// ------------------------------------------------------------------

	public function testHierarchy()
	{
		$repeater = new TRepeater();
		$this->assertInstanceOf(TDataBoundControl::class, $repeater);
		$this->assertInstanceOf(INamingContainer::class, $repeater);
		$this->assertInstanceOf(TItemDataRenderer::class, new TRepeaterTestItemRenderer());
	}

	public static function rendererProvider(): array
	{
		return [['ItemRenderer'], ['AlternatingItemRenderer'], ['SeparatorRenderer'], ['HeaderRenderer'], ['FooterRenderer'], ['EmptyRenderer']];
	}

	/**
	 * @dataProvider rendererProvider
	 */
	public function testRendererProperty(string $property)
	{
		$repeater = new TRepeater();
		$this->assertSame('', $repeater->{'get' . $property}());
		$repeater->{'set' . $property}(TRepeaterTestItemRenderer::class);
		$this->assertSame(TRepeaterTestItemRenderer::class, $repeater->{'get' . $property}());
		$repeater->{'set' . $property}('');
		$this->assertSame('', $repeater->{'get' . $property}());
	}

	public static function templateProvider(): array
	{
		return [['ItemTemplate'], ['AlternatingItemTemplate'], ['HeaderTemplate'], ['FooterTemplate'], ['EmptyTemplate'], ['SeparatorTemplate']];
	}

	/**
	 * @dataProvider templateProvider
	 */
	public function testTemplateProperty(string $property)
	{
		$repeater = new TRepeater();
		$this->assertNull($repeater->{'get' . $property}());
		$template = new TTestDataTemplate('x');
		$repeater->{'set' . $property}($template);
		$this->assertSame($template, $repeater->{'get' . $property}());
		$repeater->{'set' . $property}(null);
		$this->assertNull($repeater->{'get' . $property}());
	}

	/**
	 * @dataProvider templateProvider
	 */
	public function testTemplatePropertyRejectsANonTemplate(string $property)
	{
		$repeater = new TRepeater();
		try {
			$repeater->{'set' . $property}('not a template');
			$this->fail('TInvalidDataTypeException expected');
		} catch (TInvalidDataTypeException $e) {
			$this->assertStringContainsString($property, $e->getMessage());
		}
	}

	public function testItemsAndHeaderFooterDefaults()
	{
		$repeater = new TRepeater();
		$items = $repeater->getItems();
		$this->assertInstanceOf(TRepeaterItemCollection::class, $items);
		$this->assertSame($items, $repeater->getItems());
		$this->assertNull($repeater->getHeader());
		$this->assertNull($repeater->getFooter());
	}

	public function testDataKeyFieldAndDataKeys()
	{
		$repeater = new TRepeater();
		$this->assertSame('', $repeater->getDataKeyField());
		$repeater->setDataKeyField('id');
		$this->assertSame('id', $repeater->getDataKeyField());
		$keys = $repeater->getDataKeys();
		$this->assertInstanceOf(TList::class, $keys);
		$this->assertSame($keys, $repeater->getDataKeys(), 'the keys are kept in view state');
	}

	// ------------------------------------------------------------------
	// createItem()
	// ------------------------------------------------------------------

	public static function createItemTemplateProvider(): array
	{
		return [
			[TListItemType::Item, 'setItemTemplate'],
			[TListItemType::AlternatingItem, 'setAlternatingItemTemplate'],
			[TListItemType::Header, 'setHeaderTemplate'],
			[TListItemType::Footer, 'setFooterTemplate'],
			[TListItemType::Separator, 'setSeparatorTemplate'],
		];
	}

	/**
	 * @dataProvider createItemTemplateProvider
	 */
	public function testCreateItemInstantiatesTheTemplateOfTheType(string $type, string $setter)
	{
		$repeater = new TRepeaterTestRepeater();
		$template = new TTestDataTemplate($type);
		$repeater->$setter($template);
		$item = $repeater->callCreateItem(3, $type);
		$this->assertInstanceOf(TRepeaterItem::class, $item);
		$this->assertSame(3, $item->getItemIndex());
		$this->assertSame($type, $item->getItemType());
		$this->assertSame(1, $template->getInstantiations());
		$this->assertSame($type, $item->getControls()->itemAt(0)->getText());
	}

	public static function createItemRendererProvider(): array
	{
		return [
			[TListItemType::Item, 'setItemRenderer'],
			[TListItemType::AlternatingItem, 'setAlternatingItemRenderer'],
			[TListItemType::Header, 'setHeaderRenderer'],
			[TListItemType::Footer, 'setFooterRenderer'],
			[TListItemType::Separator, 'setSeparatorRenderer'],
		];
	}

	/**
	 * @dataProvider createItemRendererProvider
	 */
	public function testCreateItemPrefersTheRendererOfTheType(string $type, string $setter)
	{
		$repeater = new TRepeaterTestRepeater();
		$repeater->setItemTemplate(new TTestDataTemplate('I'));
		$repeater->setAlternatingItemTemplate(new TTestDataTemplate('A'));
		$repeater->setHeaderTemplate(new TTestDataTemplate('H'));
		$repeater->setFooterTemplate(new TTestDataTemplate('F'));
		$repeater->setSeparatorTemplate(new TTestDataTemplate('S'));
		$repeater->$setter(TRepeaterTestItemRenderer::class);
		$item = $repeater->callCreateItem(2, $type);
		$this->assertInstanceOf(TRepeaterTestItemRenderer::class, $item);
		$this->assertSame(2, $item->getItemIndex());
		$this->assertSame($type, $item->getItemType());
	}

	public function testCreateItemWithAPlainRendererSetsNoIndexOrType()
	{
		$repeater = new TRepeaterTestRepeater();
		$repeater->setItemRenderer(TRepeaterTestPlainRenderer::class);
		$this->assertInstanceOf(TRepeaterTestPlainRenderer::class, $repeater->callCreateItem(0, TListItemType::Item));
	}

	public function testCreateItemAlternatingFallsBackToTheItemTemplate()
	{
		$repeater = new TRepeaterTestRepeater();
		$template = new TTestDataTemplate('I');
		$repeater->setItemTemplate($template);
		$item = $repeater->callCreateItem(1, TListItemType::AlternatingItem);
		$this->assertInstanceOf(TRepeaterItem::class, $item);
		$this->assertSame(TListItemType::AlternatingItem, $item->getItemType());
		$this->assertSame(1, $template->getInstantiations());
	}

	public function testCreateItemAlternatingFallsBackToTheItemRenderer()
	{
		$repeater = new TRepeaterTestRepeater();
		$repeater->setItemRenderer(TRepeaterTestItemRenderer::class);
		$this->assertInstanceOf(TRepeaterTestItemRenderer::class, $repeater->callCreateItem(1, TListItemType::AlternatingItem));
	}

	public function testCreateItemAlternatingTemplateWithAnItemRendererUsesTheTemplate()
	{
		$repeater = new TRepeaterTestRepeater();
		$repeater->setItemRenderer(TRepeaterTestItemRenderer::class);
		$template = new TTestDataTemplate('A');
		$repeater->setAlternatingItemTemplate($template);
		$item = $repeater->callCreateItem(1, TListItemType::AlternatingItem);
		$this->assertInstanceOf(TRepeaterItem::class, $item);
		$this->assertSame(1, $template->getInstantiations());
	}

	public function testCreateItemWithoutTemplateOrRendererIsNull()
	{
		$repeater = new TRepeaterTestRepeater();
		foreach ([TListItemType::Item, TListItemType::AlternatingItem, TListItemType::Header, TListItemType::Footer, TListItemType::Separator] as $type) {
			$this->assertNull($repeater->callCreateItem(0, $type), $type);
		}
	}

	public function testCreateItemRejectsAnUnknownType()
	{
		$repeater = new TRepeaterTestRepeater();
		$this->expectException(TInvalidDataValueException::class);
		$repeater->callCreateItem(0, TListItemType::EditItem);
	}

	// ------------------------------------------------------------------
	// performDataBinding()
	// ------------------------------------------------------------------

	public function testDataBindCreatesItemsInOrder()
	{
		$repeater = $this->newRepeater();
		$repeater->setHeaderTemplate(new TTestDataTemplate('H'));
		$repeater->setFooterTemplate(new TTestDataTemplate('F'));
		$repeater->setSeparatorTemplate(new TTestDataTemplate('S'));
		$this->bind($repeater, ['a', 'b', 'c']);

		$this->assertSame(['Header-1', 'Item0', 'Separator0', 'AlternatingItem1', 'Separator1', 'Item2', 'Footer-1'], $this->itemTypes($repeater));
		$this->assertSame(3, $repeater->getItems()->getCount());
		$this->assertSame(TListItemType::Header, $repeater->getHeader()->getItemType());
		$this->assertSame(TListItemType::Footer, $repeater->getFooter()->getItemType());
		$this->assertSame([0, 1, 2], $repeater->getDataKeys()->toArray());
		$this->assertSame(3, $repeater->getViewState('ItemCount'));
		$this->assertSame('HI:aSI:bSI:cF', $this->render($repeater));
	}

	public function testDataBindWithoutSeparatorHeaderOrFooter()
	{
		$repeater = $this->bind($this->newRepeater(), ['x' => 'a', 'y' => 'b']);
		$this->assertSame(['Item0', 'AlternatingItem1'], $this->itemTypes($repeater));
		$this->assertNull($repeater->getHeader());
		$this->assertNull($repeater->getFooter());
		$this->assertSame(['x', 'y'], $repeater->getDataKeys()->toArray());
	}

	public function testDataBindReadsTheKeyField()
	{
		$repeater = $this->newRepeater();
		$repeater->setDataKeyField('id');
		$this->bind($repeater, [['id' => 7, 'n' => 'a'], ['id' => 9, 'n' => 'b']]);
		$this->assertSame([7, 9], $repeater->getDataKeys()->toArray());
		$this->assertSame(['id' => 9, 'n' => 'b'], $repeater->getItems()->itemAt(1)->getData());
	}

	public function testDataBindSetsTheDataOfRenderers()
	{
		$repeater = new TRepeater();
		$repeater->setItemRenderer(TRepeaterTestItemRenderer::class);
		$repeater->setHeaderRenderer(TRepeaterTestItemRenderer::class);
		$repeater->setSeparatorRenderer(TRepeaterTestPlainRenderer::class);
		$this->bind($repeater, ['a', 'b']);
		$this->assertSame('R[Header,-1]R[Item,0]=aPR[AlternatingItem,1]=b', $this->render($repeater));
	}

	public function testDataBindRaisesItemEvents()
	{
		$repeater = $this->newRepeater();
		$created = $bound = [];
		$repeater->attachEventHandler('OnItemCreated', function ($sender, $param) use (&$created) {
			$this->assertInstanceOf(TRepeaterItemEventParameter::class, $param);
			$created[] = [$param->getItem()->getItemIndex(), $param->getItem()->getData(), $param->getItem()->getParent()];
		});
		$repeater->attachEventHandler('OnItemDataBound', function ($sender, $param) use (&$bound) {
			$bound[] = $param->getItem()->getControls()->itemAt(0)->getText();
		});
		$this->bind($repeater, ['a', 'b']);
		$this->assertSame([[0, 'a', null], [1, 'b', null]], $created, 'OnItemCreated is raised before the item joins the repeater');
		$this->assertSame(['I:a', 'I:b'], $bound);
	}

	public function testDataBindAgainReplacesTheItems()
	{
		$repeater = $this->newRepeater();
		$repeater->setHeaderTemplate(new TTestDataTemplate('H'));
		$this->bind($repeater, ['a', 'b', 'c']);
		$this->bind($repeater, ['z']);
		$this->assertSame(['Header-1', 'Item0'], $this->itemTypes($repeater));
		$this->assertSame(1, $repeater->getItems()->getCount());
		$this->assertSame([0], $repeater->getDataKeys()->toArray());
	}

	public function testDataBindEmptyUsesTheEmptyTemplate()
	{
		$repeater = $this->newRepeater();
		$repeater->setHeaderTemplate(new TTestDataTemplate('H'));
		$repeater->setFooterTemplate(new TTestDataTemplate('F'));
		$repeater->setEmptyTemplate(new TTestDataTemplate('E'));
		$this->bind($repeater, []);
		$this->assertSame(0, $repeater->getItems()->getCount());
		$this->assertNull($repeater->getHeader());
		$this->assertNull($repeater->getFooter());
		$this->assertSame(0, $repeater->getViewState('ItemCount', 0));
		$this->assertSame('E', $this->render($repeater), 'the empty content is data bound');
	}

	public function testDataBindEmptyPrefersTheEmptyRenderer()
	{
		$repeater = $this->newRepeater();
		$repeater->setEmptyTemplate(new TTestDataTemplate('E'));
		$repeater->setEmptyRenderer(TRepeaterTestPlainRenderer::class);
		$this->bind($repeater, []);
		$this->assertSame('P', $this->render($repeater));
	}

	public function testDataBindEmptyWithoutEmptyContentRendersNothing()
	{
		$repeater = $this->bind($this->newRepeater(), []);
		$this->assertSame(0, $repeater->getControls()->getCount());
		$this->assertSame('', $this->render($repeater));
	}

	public function testRenderWithoutDataBindingRendersNothing()
	{
		$repeater = $this->newRepeater();
		$repeater->getControls()->add(new TLiteral());
		$repeater->getControls()->itemAt(0)->setText('loose');
		$this->assertSame('', $this->render($repeater), 'no items and no empty content');
	}

	// ------------------------------------------------------------------
	// View state
	// ------------------------------------------------------------------

	public function testSaveStateKeepsTheItemCount()
	{
		$repeater = $this->bind($this->newRepeater(), ['a', 'b']);
		$repeater->clearViewState('ItemCount');
		$repeater->saveState();
		$this->assertSame(2, $repeater->getViewState('ItemCount'));
	}

	public function testSaveStateWithoutItemsClearsTheItemCount()
	{
		$repeater = new TRepeater();
		$repeater->setViewState('ItemCount', 4, 0);
		$repeater->saveState();
		$this->assertSame(0, $repeater->getViewState('ItemCount', 0));
	}

	public function testLoadStateRestoresTheItems()
	{
		$repeater = $this->newRepeater();
		$repeater->setHeaderTemplate(new TTestDataTemplate('H'));
		$repeater->setFooterTemplate(new TTestDataTemplate('F'));
		$repeater->setSeparatorTemplate(new TTestDataTemplate('S'));
		$created = 0;
		$repeater->attachEventHandler('OnItemCreated', function () use (&$created) {
			$created++;
		});
		$repeater->setViewState('ItemCount', 3, 0);
		$repeater->loadState();

		$this->assertSame(['Header-1', 'Item0', 'Separator0', 'AlternatingItem1', 'Separator1', 'Item2', 'Footer-1'], $this->itemTypes($repeater));
		$this->assertSame(3, $repeater->getItems()->getCount());
		$this->assertSame(7, $created);
		$this->assertSame(0, $repeater->getViewState('ItemCount', 0), 'loadState() clears the item count');
		$this->assertNull($repeater->getItems()->itemAt(0)->getData(), 'restored items are not data bound');
	}

	public function testLoadStateWithoutItemsCreatesTheEmptyContent()
	{
		$repeater = $this->newRepeater();
		$repeater->setEmptyTemplate(new TTestDataTemplate('E'));
		$repeater->loadState();
		$this->assertSame([TLiteral::class], $this->itemTypes($repeater));
		$this->assertSame('E', $this->render($repeater));
	}

	public function testLoadStateWithoutItemsUsesTheEmptyRenderer()
	{
		$repeater = $this->newRepeater();
		$repeater->setEmptyRenderer(TRepeaterTestPlainRenderer::class);
		$repeater->callRestoreItemsFromViewState();
		$this->assertSame([TRepeaterTestPlainRenderer::class], $this->itemTypes($repeater));
	}

	public function testLoadStateWithoutItemsOrEmptyContentAddsNothing()
	{
		$repeater = $this->newRepeater();
		$repeater->loadState();
		$this->assertSame(0, $repeater->getControls()->getCount());
	}

	public function testLoadStateAfterDataBindingKeepsTheItems()
	{
		$repeater = $this->bind($this->newRepeater(), ['a', 'b']);
		$first = $repeater->getItems()->itemAt(0);
		$repeater->loadState();
		$this->assertSame($first, $repeater->getItems()->itemAt(0));
		$this->assertSame('a', $first->getData());
		$this->assertSame(0, $repeater->getViewState('ItemCount', 0));
	}

	public function testResetClearsItemsHeaderAndFooter()
	{
		$repeater = $this->newRepeater();
		$repeater->setHeaderTemplate(new TTestDataTemplate('H'));
		$repeater->setFooterTemplate(new TTestDataTemplate('F'));
		$this->bind($repeater, ['a']);
		$repeater->reset();
		$this->assertSame(0, $repeater->getControls()->getCount());
		$this->assertSame(0, $repeater->getItems()->getCount());
		$this->assertNull($repeater->getHeader());
		$this->assertNull($repeater->getFooter());
	}

	// ------------------------------------------------------------------
	// Commands
	// ------------------------------------------------------------------

	public function testAButtonCommandBubblesToOnItemCommand()
	{
		$repeater = new TRepeater();
		$repeater->setItemTemplate(new class () implements ITemplate {
			public function instantiateIn($parent)
			{
				$button = new TButton();
				$button->setCommandName('Pick');
				$button->setCommandParameter('p');
				$parent->getControls()->add($button);
			}

			public function getIncludedFiles()
			{
				return [];
			}
		});
		$this->bind($repeater, ['a', 'b']);
		$received = null;
		$repeater->attachEventHandler('OnItemCommand', function ($sender, $param) use (&$received) {
			$received = $param;
		});
		$item = $repeater->getItems()->itemAt(1);
		$button = $item->getControls()->itemAt(0);
		$button->onCommand(new TCommandEventParameter('Pick', 'p'));

		$this->assertInstanceOf(TRepeaterCommandEventParameter::class, $received);
		$this->assertSame($item, $received->getItem());
		$this->assertSame($button, $received->getCommandSource());
		$this->assertSame('Pick', $received->getCommandName());
		$this->assertSame('p', $received->getCommandParameter());
	}

	public function testARendererCommandBubblesToOnItemCommand()
	{
		$repeater = new TRepeater();
		$repeater->setItemRenderer(TRepeaterTestItemRenderer::class);
		$this->bind($repeater, ['a']);
		$received = null;
		$repeater->attachEventHandler('OnItemCommand', function ($sender, $param) use (&$received) {
			$received = $param;
		});
		$renderer = $repeater->getItems()->itemAt(0);
		$source = new TControl();
		$this->assertTrue($renderer->bubbleEvent($source, new TCommandEventParameter('Go', 1)));
		$this->assertSame($renderer, $received->getItem());
		$this->assertSame($source, $received->getCommandSource());
		$this->assertSame('Go', $received->getCommandName());
	}

	public function testItemsIgnoreOtherBubbledEvents()
	{
		$this->assertFalse((new TRepeaterItem())->bubbleEvent(new TControl(), new TEventParameter()));
		$this->assertFalse((new TRepeaterTestItemRenderer())->bubbleEvent(new TControl(), new TEventParameter()));
		$this->assertFalse((new TRepeater())->bubbleEvent(new TControl(), new TCommandEventParameter('Go', 1)));
	}

	public function testRepeaterHandlesItsCommandParameter()
	{
		$repeater = new TRepeater();
		$param = new TRepeaterCommandEventParameter(new TRepeaterItem(), new TControl(), new TCommandEventParameter('Go', 1));
		$raised = 0;
		$repeater->attachEventHandler('OnItemCommand', function ($sender, $p) use (&$raised, $param) {
			$this->assertSame($param, $p);
			$raised++;
		});
		$this->assertTrue($repeater->bubbleEvent(new TControl(), $param));
		$this->assertSame(1, $raised);
	}

	// ------------------------------------------------------------------
	// TRepeaterItem and getDataFieldValue()
	// ------------------------------------------------------------------

	public function testRepeaterItemProperties()
	{
		$item = new TRepeaterItem();
		$this->assertInstanceOf(INamingContainer::class, $item);
		$this->assertInstanceOf(IItemDataRenderer::class, $item);
		$this->assertNull($item->getItemIndex());
		$this->assertNull($item->getItemType());
		$this->assertNull($item->getData());
		$item->setItemIndex('5');
		$item->setItemType('separator');
		$item->setData(['k' => 'v']);
		$this->assertSame(5, $item->getItemIndex());
		$this->assertSame(TListItemType::Separator, $item->getItemType());
		$this->assertSame(['k' => 'v'], $item->getData());
	}

	public function testRepeaterItemRejectsAnUnknownType()
	{
		$this->expectException(TInvalidDataValueException::class);
		(new TRepeaterItem())->setItemType('Unknown');
	}

	public function testItemCollectionAcceptsOnlyControls()
	{
		$items = new TRepeaterItemCollection();
		$items->add($item = new TRepeaterItem());
		$this->assertSame($item, $items->itemAt(0));
		$this->expectException(TInvalidDataTypeException::class);
		$items->add('item');
	}

	public function testGetDataFieldValue()
	{
		$repeater = new TRepeaterTestRepeater();
		$this->assertSame('v', $repeater->callGetDataFieldValue(['f' => 'v'], 'f'));
		$this->assertSame('m', $repeater->callGetDataFieldValue(new TMap(['f' => 'm']), 'f'));
		$this->expectException(TInvalidDataValueException::class);
		$repeater->callGetDataFieldValue(['f' => 'v'], 'missing');
	}

	public function testLoadStateWithoutHeaderOrFooterRestoresOnlyTheItems()
	{
		$repeater = $this->newRepeater();
		$repeater->setViewState('ItemCount', 2, 0);
		$repeater->loadState();
		$this->assertSame(['Item0', 'AlternatingItem1'], $this->itemTypes($repeater));
		$this->assertNull($repeater->getHeader());
		$this->assertNull($repeater->getFooter());
	}
}
