<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TListControl;
use Prado\Web\UI\WebControls\TListItem;
use Prado\Web\UI\WebControls\TSuggestionList;
use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;

class TSuggestionListTest extends TestCase
{
	use TWebControlRenderTrait;

	private function createList(array $items = []): TSuggestionList
	{
		$list = new TSuggestionList();
		$list->setID('Cities');
		foreach ($items as $item) {
			$list->getItems()->add($item);
		}
		return $list;
	}

	// --- Basics ---

	public function testExtendsListControl()
	{
		$this->assertInstanceOf(TListControl::class, new TSuggestionList());
	}

	public function testRendersDatalistTag()
	{
		$output = $this->render($this->createList());
		$this->assertStringStartsWith('<datalist', $output);
		$this->assertStringEndsWith('</datalist>', $output);
	}

	public function testEnsureIdAlwaysTrue()
	{
		$list = new TSuggestionList();
		$this->assertTrue($list->getEnsureId());
		$list->setEnsureId(false);
		$this->assertTrue($list->getEnsureId());
	}

	public function testIdRenderedWithoutExplicitId()
	{
		$page = new TPage();
		$list = new TSuggestionList();
		$page->getControls()->add($list);
		$output = $this->render($list);
		$this->assertStringContainsString('id="' . $list->getClientID() . '"', $output);
	}

	public function testRendersOutsideForm()
	{
		$page = new TPage();
		$list = $this->createList([new TListItem('Paris')]);
		$page->getControls()->add($list);
		$output = $this->render($list);
		$this->assertStringContainsString('<option value="Paris"></option>', $output);
	}

	public function testNoMultipleOrPostBackAttributes()
	{
		$list = $this->createList([new TListItem('Paris')]);
		$list->setAutoPostBack(true);
		$output = $this->render($list);
		$this->assertStringNotContainsString('multiple', $output);
		$this->assertStringNotContainsString('onchange', $output);
	}

	// --- Option rendering ---

	public function testEmptyListRendersNoOptions()
	{
		$output = $this->render($this->createList());
		$this->assertSame('<datalist id="Cities"></datalist>', $output);
	}

	public function testOptionWithTextOnlyHasNoLabel()
	{
		$output = $this->render($this->createList([new TListItem('Paris')]));
		$this->assertStringContainsString('<option value="Paris"></option>', $output);
	}

	public function testOptionWithDistinctTextRendersLabel()
	{
		$output = $this->render($this->createList([new TListItem('New York City', 'NYC')]));
		$this->assertStringContainsString('<option value="NYC">New York City</option>', $output);
	}

	public function testOptionLabelIsEncoded()
	{
		$output = $this->render($this->createList([new TListItem('<b>Paris</b>', 'P')]));
		$this->assertStringContainsString('&lt;b&gt;Paris&lt;/b&gt;', $output);
		$this->assertStringNotContainsString('<b>', $output);
	}

	public function testOptionValueIsEncoded()
	{
		$output = $this->render($this->createList([new TListItem('A "quoted" name', 'a"b')]));
		$this->assertStringContainsString('value="a&quot;b"', $output);
	}

	public function testDisabledItemNotRendered()
	{
		$item = new TListItem('London');
		$item->setEnabled(false);
		$output = $this->render($this->createList([new TListItem('Paris'), $item]));
		$this->assertStringContainsString('value="Paris"', $output);
		$this->assertStringNotContainsString('London', $output);
	}

	public function testEmptyValueItemNotRendered()
	{
		$output = $this->render($this->createList([new TListItem('', '')]));
		$this->assertStringNotContainsString('<option', $output);
	}

	public function testSelectedNotRendered()
	{
		$item = new TListItem('Paris');
		$item->setSelected(true);
		$output = $this->render($this->createList([$item]));
		$this->assertStringNotContainsString('selected', $output);
	}

	public function testPromptNotRendered()
	{
		$list = $this->createList([new TListItem('Paris')]);
		$list->setPromptText('Choose a city');
		$list->setPromptValue('none');
		$output = $this->render($list);
		$this->assertStringNotContainsString('Choose a city', $output);
		$this->assertSame(1, substr_count($output, '<option'));
	}

	public function testItemAttributesRenderedExceptGroup()
	{
		$item = new TListItem('Paris');
		$item->getAttributes()->add('Group', 'Europe');
		$item->getAttributes()->add('data-country', 'FR');
		$output = $this->render($this->createList([$item]));
		$this->assertStringContainsString('data-country="FR"', $output);
		$this->assertStringNotContainsString('optgroup', $output);
		$this->assertStringNotContainsString('Europe', $output);
		$this->assertSame('Europe', $item->getAttributes()->itemAt('Group'));
	}

	// --- Data binding ---

	public function testBindListOfStringsUsesStringAsValue()
	{
		$list = $this->createList();
		$list->setDataSource(['Paris', 'London']);
		$list->dataBind();
		$items = $list->getItems();
		$this->assertCount(2, $items);
		$this->assertSame('Paris', $items[0]->getValue());
		$this->assertSame('Paris', $items[0]->getText());
		$this->assertSame('London', $items[1]->getValue());
	}

	public function testBindStringKeysUsesKeyAsValue()
	{
		$list = $this->createList();
		$list->setDataSource(['NYC' => 'New York City', 'LA' => 'Los Angeles']);
		$list->dataBind();
		$items = $list->getItems();
		$this->assertSame('NYC', $items[0]->getValue());
		$this->assertSame('New York City', $items[0]->getText());
	}

	public function testBindRowsDefaultFieldsAreTextThenValue()
	{
		$list = $this->createList();
		$list->setDataSource([['New York City', 'NYC']]);
		$list->dataBind();
		$item = $list->getItems()[0];
		$this->assertSame('New York City', $item->getText());
		$this->assertSame('NYC', $item->getValue());
	}

	public function testBindRowsValueFieldDefaultsToTextField()
	{
		$list = $this->createList();
		$list->setDataTextField('name');
		$list->setDataSource([['id' => 1, 'name' => 'Paris'], ['id' => 2, 'name' => 'London']]);
		$list->dataBind();
		$items = $list->getItems();
		$this->assertSame('Paris', $items[0]->getValue());
		$this->assertSame('Paris', $items[0]->getText());
	}

	public function testBindRowsWithTextAndValueFields()
	{
		$list = $this->createList();
		$list->setDataTextField('name');
		$list->setDataValueField('code');
		$list->setDataSource([['code' => 'NYC', 'name' => 'New York City']]);
		$list->dataBind();
		$item = $list->getItems()[0];
		$this->assertSame('NYC', $item->getValue());
		$this->assertSame('New York City', $item->getText());
	}

	public function testBindTextFormatAppliesToLabelOnly()
	{
		$list = $this->createList();
		$list->setDataTextFormatString('%s, France');
		$list->setDataSource(['Paris']);
		$list->dataBind();
		$item = $list->getItems()[0];
		$this->assertSame('Paris', $item->getValue());
		$this->assertSame('Paris, France', $item->getText());
		$this->assertStringContainsString('<option value="Paris">Paris, France</option>', $this->render($list));
	}

	public function testBindClearsItemsByDefault()
	{
		$list = $this->createList([new TListItem('Rome')]);
		$list->setDataSource(['Paris']);
		$list->dataBind();
		$this->assertCount(1, $list->getItems());
		$this->assertSame('Paris', $list->getItems()[0]->getValue());
	}

	public function testBindAppendsItems()
	{
		$list = $this->createList([new TListItem('Rome')]);
		$list->setAppendDataBoundItems(true);
		$list->setDataSource(['Paris']);
		$list->dataBind();
		$this->assertCount(2, $list->getItems());
		$this->assertSame('Rome', $list->getItems()[0]->getValue());
	}
}
