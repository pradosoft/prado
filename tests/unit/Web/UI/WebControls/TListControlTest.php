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

	// ================================================================================
	// Properties
	// ================================================================================

	// ================================================================================
	// Items
	// ================================================================================

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

	// ================================================================================
	// Selection: multiple
	// ================================================================================

	// ================================================================================
	// Adapter forwarding
	// ================================================================================

	// ================================================================================
	// addParsedObject
	// ================================================================================

	// ================================================================================
	// Data binding
	// ================================================================================

	public function testBindAppliesLastSelectionSet()
	{
		$list = new TListControlTestList();
		$list->setSelectedValue('b');
		$list->setSelectedIndex(0);
		$this->bind($list, ['a' => 'A', 'b' => 'B']);
		$this->assertSame(0, $list->getSelectedIndex());

		$list = new TListControlTestList();
		$list->setSelectedIndex(1);
		$list->setSelectedValue('a');
		$this->bind($list, ['a' => 'A', 'b' => 'B']);
		$this->assertSame(0, $list->getSelectedIndex());

		$list = $this->createList(true);
		$list->setSelectedValues(['a']);
		$list->setSelectedIndices([1, 2]);
		$this->bind($list, ['a' => 'A', 'b' => 'B', 'c' => 'C']);
		$this->assertSame([1, 2], $list->getSelectedIndices());

		$list = new TListControlTestList();
		$list->setSelectedValue('b');
		$list->setSelectedIndex(-1);
		$this->bind($list, ['a' => 'A', 'b' => 'B']);
		$this->assertSame(-1, $list->getSelectedIndex());
	}

	public function testBindAfterClearSelectionSelectsNothing()
	{
		$list = new TListControlTestList();
		$list->setSelectedValue('b');
		$list->clearSelection();
		$this->bind($list, ['a' => 'A', 'b' => 'B']);
		$this->assertSame(-1, $list->getSelectedIndex());

		$list = $this->createList(false, ['a' => 'A', 'b' => 'B']);
		$list->setSelectedIndex(1);
		$list->clearSelection();
		$this->bind($list, ['x' => 'X', 'y' => 'Y']);
		$this->assertSame(-1, $list->getSelectedIndex());
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

	// ================================================================================
	// Events
	// ================================================================================

	// ================================================================================
	// Rendering: contents
	// ================================================================================

	// ================================================================================
	// Rendering: control
	// ================================================================================


	/**
	 * A format expression that throws an Error is reported with the error as the previous exception.
	 */
	public function testFormatDataValueChainsThrowable()
	{
		$list = new TListControlTestList();
		try {
			PradoUnit::invoke($list, 'formatDataValue', '#intdiv({0}, 0)', 1);
			$this->fail('TInvalidDataValueException not raised');
		} catch (TInvalidDataValueException $e) {
			$this->assertSame('listcontrol_expression_invalid', $e->getErrorCode());
			$this->assertInstanceOf(\DivisionByZeroError::class, $e->getPrevious());
		}
	}
}
