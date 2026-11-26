<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use PHPUnit\Framework\TestCase;
use Prado\IO\TTextWriter;
use Prado\Prado;
use Prado\Test\Unit\Harness\Web\TTestRecordingResponse;
use Prado\Test\Unit\Harness\Web\UI\TTestCallbackPage;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\ActiveControls\TActiveDataGrid;
use Prado\Web\UI\ActiveControls\TActiveDataGridPager;
use Prado\Web\UI\ActiveControls\TActiveDataGridPagerEventParameter;
use Prado\Web\UI\ActiveControls\TActiveDataList;
use Prado\Web\UI\ActiveControls\TActivePageAdapter;
use Prado\Web\UI\ActiveControls\TActivePager;
use Prado\Web\UI\ActiveControls\TActiveRepeater;
use Prado\Web\UI\TControl;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\WebControls\TDataBoundControl;

/**
 * The data-bound active controls ({@see TActiveDataGrid}, {@see TActiveDataList},
 * {@see TActiveRepeater}) in and outside a callback: binding data registers the control
 * and its active pagers for the callback response, and the render wraps the control in
 * a container that a callback replaces.
 */
class TActiveDataBoundCallbackTest extends TestCase
{
	private array $snapshot = [];

	protected function setUp(): void
	{
		$this->snapshot = PradoUnit::snapshot(Prado::getApplication(), ['_response', '_errorHandler']);
	}

	protected function tearDown(): void
	{
		PradoUnit::restore(Prado::getApplication(), $this->snapshot);
	}

	public static function controlProvider(): array
	{
		return [
			'data grid' => [TActiveDataGrid::class, '<div id="List_Container">', '</div>'],
			'data list' => [TActiveDataList::class, '<span id="List_Container">', '</span>'],
			'repeater' => [TActiveRepeater::class, '<div id="List_Container">', '</div>'],
		];
	}

	/**
	 * Builds a page with the control `List`, an active pager for it, and one for another control.
	 * A callback page has an active page adapter, which writes the callback response.
	 * @return array{TTestCallbackPage, TDataBoundControl, TActivePager}
	 */
	private function createList(string $class, bool $callback, int $stage = TControl::CS_PRERENDERED): array
	{
		Prado::getApplication()->setResponse(new TTestRecordingResponse());
		$page = new TTestCallbackPage();
		$page->callback = $callback;
		if ($callback) {
			$page->setAdapter(new TActivePageAdapter($page));
		}
		$list = new $class();
		$list->setID('List');
		$page->getControls()->add($list);
		$pager = new TActivePager();
		$pager->setID('Pager');
		$pager->setControlToPaginate('List');
		$page->getControls()->add($pager);
		$other = new TActivePager();
		$other->setID('Other');
		$other->setControlToPaginate('Elsewhere');
		$page->getControls()->add($other);
		PradoUnit::invoke($list, 'setControlStage', $stage);
		return [$page, $list, $pager];
	}

	/**
	 * @return array the controls registered for the callback render, by unique ID
	 */
	private function registered(TTestCallbackPage $page): array
	{
		return array_map(fn ($entry) => $entry[0], PradoUnit::getProp($page->getAdapter(), '_controlsToRender'));
	}

	private function render(TControl $control): string
	{
		$writer = new THtmlWriter(new TTextWriter());
		$control->render($writer);
		return $writer->flush();
	}

	/**
	 * @dataProvider controlProvider
	 */
	public function testDataSourceInACallbackRegistersTheControlAndItsPagers(string $class)
	{
		[$page, $list, $pager] = $this->createList($class, true);
		$list->setDataSource(['a', 'b']);
		$this->assertSame(['Pager' => $pager, 'List' => $list], $this->registered($page));
	}

	/**
	 * @dataProvider controlProvider
	 */
	public function testDataSourceOutsideACallbackRegistersNothing(string $class)
	{
		[$page, $list] = $this->createList($class, false);
		$list->setDataSource(['a', 'b']);
		$this->assertSame(['a', 'b'], iterator_to_array($list->getDataSource()));
		$this->assertNull($page->getAdapter());
	}

	/**
	 * @dataProvider controlProvider
	 */
	public function testRenderOutsideACallbackWrapsTheControl(string $class, string $begin, string $end)
	{
		[$page, $list] = $this->createList($class, false);
		$output = $this->render($list);
		$this->assertStringStartsWith($begin, $output);
		$this->assertStringEndsWith($end, $output);
		$this->assertSame([], $page->getClientFunctions());
	}

	/**
	 * @dataProvider controlProvider
	 */
	public function testRenderInACallbackReplacesTheContainer(string $class)
	{
		[$page, $list] = $this->createList($class, true);
		$this->render($list);
		$functions = $page->getClientFunctions();
		$this->assertCount(1, $functions);
		$this->assertSame('List_Container', $functions[0]['Prado.Element.replace'][0]);
	}

	/**
	 * @dataProvider controlProvider
	 */
	public function testRenderBeforePreRenderRegistersTheControl(string $class)
	{
		[$page, $list] = $this->createList($class, true, TControl::CS_LOADED);
		$this->assertSame('', $this->render($list));
		$this->assertSame(['List' => $list], $this->registered($page));
	}

	public function testDataGridRenderInACallbackFocusesTheSortControl()
	{
		[$page, $grid] = $this->createList(TActiveDataGrid::class, true);
		PradoUnit::setProp($grid, '_sortFocusID', 'Sort');
		$this->render($grid);
		$this->assertSame(['Prado.Element.j' => ['Sort', 'trigger', ['focus']]], $page->getClientFunctions()[1]);
	}

	public function testDataGridSurroundingTagAndColumns()
	{
		$grid = new TActiveDataGrid();
		$grid->setID('Grid');
		$this->assertSame('div', $grid->getSurroundingTag());
		$grid->setSurroundingTag('section');
		$this->assertSame('section', $grid->getSurroundingTag());
		$this->assertSame('Grid_Container', $grid->getSurroundingTagID());
		$this->assertSame('TActiveBoundColumn', PradoUnit::invoke($grid, 'getAutoGenerateColumnName'));
	}

	public function testRepeaterSurroundingTag()
	{
		$repeater = new TActiveRepeater();
		$repeater->setID('Rep');
		$this->assertSame('div', $repeater->getSurroundingTag());
		$repeater->setSurroundingTag('ul');
		$this->assertSame('ul', $repeater->getSurroundingTag());
		$this->assertSame('Rep_Container', $repeater->getSurroundingTagID());
	}

	public function testDataGridCreatesAnActivePagerAndRaisesItsEvent()
	{
		$grid = new TActiveDataGrid();
		$created = null;
		$grid->attachEventHandler('OnPagerCreated', function ($sender, $param) use (&$created) {
			$created = $param;
		});
		$pager = PradoUnit::invoke($grid, 'createPager');
		$this->assertInstanceOf(TActiveDataGridPager::class, $pager);
		$this->assertSame($grid, $pager->getParent());
		$this->assertInstanceOf(TActiveDataGridPagerEventParameter::class, $created);
		$this->assertSame($pager, $created->getPager());
	}

	public static function singlePagerProvider(): array
	{
		$cases = [];
		foreach (self::controlProvider() as $name => [$class]) {
			$cases["$name, no pager"] = [$class, null, 1];
			$cases["$name, its pager"] = [$class, 'List', 2];
			$cases["$name, another pager"] = [$class, 'Elsewhere', 1];
		}
		return $cases;
	}

	/**
	 * @dataProvider singlePagerProvider
	 */
	public function testDataSourceInACallbackWithOneOrNoPager(string $class, ?string $target, int $registered)
	{
		Prado::getApplication()->setResponse(new TTestRecordingResponse());
		$page = new TTestCallbackPage();
		$page->setAdapter(new TActivePageAdapter($page));
		$list = new $class();
		$list->setID('List');
		$page->getControls()->add($list);
		if ($target !== null) {
			$pager = new TActivePager();
			$pager->setControlToPaginate($target);
			$page->getControls()->add($pager);
		}
		PradoUnit::invoke($list, 'setControlStage', TControl::CS_PRERENDERED);
		$list->setDataSource([]);
		$this->assertCount($registered, $this->registered($page));
	}
}
