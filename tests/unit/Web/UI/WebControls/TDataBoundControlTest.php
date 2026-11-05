<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Collections\TList;
use Prado\Collections\TMap;
use Prado\Collections\TPagedDataSource;
use Prado\Data\TDbConnection;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\INamingContainer;
use Prado\Web\UI\TControl;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TDataBoundControl;
use Prado\Web\UI\WebControls\TDataSourceControl;
use Prado\Web\UI\WebControls\TDataSourceSelectParameters;
use Prado\Web\UI\WebControls\TDataSourceView;
use Prado\Web\UI\WebControls\TReadOnlyDataSource;
use Prado\Web\UI\WebControls\TReadOnlyDataSourceView;

/**
 * Concrete TDataBoundControl that records each performDataBinding call.
 */
class TDataBoundControlTestControl extends TDataBoundControl
{
	public array $boundData = [];

	protected function performDataBinding($data)
	{
		$this->boundData[] = $data;
	}
}

/**
 * Data source view returning a configurable result and recording select parameters.
 */
class TDataBoundControlTestView extends TDataSourceView
{
	public mixed $result = null;
	public array $selectCalls = [];

	public function select($parameters)
	{
		$this->selectCalls[] = $parameters;
		return $this->result;
	}
}

/**
 * Data source control holding named views; unknown names return null.
 */
class TDataBoundControlTestSource extends TDataSourceControl
{
	public array $views = [];

	public function getView($viewName)
	{
		return $this->views[$viewName] ?? null;
	}

	public function getViewNames()
	{
		return array_keys($this->views);
	}
}

/**
 * Plain naming container used to host controls for DataSourceID lookups.
 */
class TDataBoundControlTestContainer extends TControl implements INamingContainer
{
}

class TDataBoundControlTest extends TestCase
{
	private TDataBoundControlTestControl $control;

	protected function setUp(): void
	{
		$this->control = new TDataBoundControlTestControl();
	}

	// ---------------------------------------------------------------------
	// Helpers
	// ---------------------------------------------------------------------

	/**
	 * Creates a naming container with a data source control (ID "ds") and the test control.
	 * @param mixed $result value returned by the default view's select().
	 * @param string $viewName name of the default view.
	 * @return array{0: TDataBoundControlTestContainer, 1: TDataBoundControlTestSource, 2: TDataBoundControlTestView}
	 */
	private function buildContainerWithSource(mixed $result = null, string $viewName = ''): array
	{
		$container = new TDataBoundControlTestContainer();
		$source = new TDataBoundControlTestSource();
		$source->setID('ds');
		$view = new TDataBoundControlTestView($source, $viewName);
		$view->result = $result;
		$source->views[$viewName] = $view;
		$container->getControls()->add($source);
		$container->getControls()->add($this->control);
		return [$container, $source, $view];
	}

	// ---------------------------------------------------------------------
	// DataSource / validateDataSource
	// ---------------------------------------------------------------------

	public function testDataSourceDefaultsToNull(): void
	{
		$this->assertNull($this->control->getDataSource());
	}

	public function testSetDataSourceArrayBecomesTMap(): void
	{
		$this->control->setDataSource(['a' => 1, 'b' => 2]);
		$ds = $this->control->getDataSource();
		$this->assertInstanceOf(TMap::class, $ds);
		$this->assertSame(['a' => 1, 'b' => 2], $ds->toArray());
	}

	public function testSetDataSourceTraversableIsKept(): void
	{
		$list = new TList([1, 2, 3]);
		$this->control->setDataSource($list);
		$this->assertSame($list, $this->control->getDataSource());

		$iter = new \ArrayIterator([4, 5]);
		$this->control->setDataSource($iter);
		$this->assertSame($iter, $this->control->getDataSource());
	}

	public function testSetDataSourceNullIsAccepted(): void
	{
		$this->control->setDataSource([1]);
		$this->control->setDataSource(null);
		$this->assertNull($this->control->getDataSource());
	}

	public function testSetDataSourceStringListBecomesTListOfPairs(): void
	{
		$this->control->setDataSource('["x","y"]');
		$ds = $this->control->getDataSource();
		$this->assertInstanceOf(TList::class, $ds);
		$this->assertSame([['x', 'x'], ['y', 'y']], $ds->toArray());
	}

	public function testSetDataSourceStringMapUsesStringKeys(): void
	{
		$this->control->setDataSource("['k1' => 'v1', 'k2' => 'v2']");
		$this->assertSame([['v1', 'k1'], ['v2', 'k2']], $this->control->getDataSource()->toArray());
	}

	public function testSetDataSourceStringWithNestedArraysAddsArraysAsIs(): void
	{
		$this->control->setDataSource('[["a","b"],"c"]');
		$this->assertSame([['a', 'b'], ['c', 'c']], $this->control->getDataSource()->toArray());
	}

	public function testSetDataSourceCommaSeparatedString(): void
	{
		$this->control->setDataSource('a,b');
		$ds = $this->control->getDataSource();
		$this->assertInstanceOf(TList::class, $ds);
		$this->assertSame([['a', 'a'], ['b', 'b']], $ds->toArray());
	}

	public function testSetDataSourceTDbDataReaderIsReadIntoArray(): void
	{
		$conn = new TDbConnection('sqlite::memory:');
		$conn->setActive(true);
		$reader = $conn->createCommand('SELECT 1 AS a UNION ALL SELECT 2')->query();
		$this->control->setDataSource($reader);
		$ds = $this->control->getDataSource();
		$this->assertIsArray($ds);
		$this->assertEquals([['a' => 1], ['a' => 2]], $ds);
		$conn->setActive(false);
	}

	public function testSetDataSourceInvalidTypeThrows(): void
	{
		$this->expectException(TInvalidDataTypeException::class);
		$this->control->setDataSource(42);
	}

	public function testSetDataSourceInvalidObjectThrows(): void
	{
		$this->expectException(TInvalidDataTypeException::class);
		$this->control->setDataSource(new \stdClass());
	}

	// ---------------------------------------------------------------------
	// DataSourceID / DataMember
	// ---------------------------------------------------------------------

	public function testDataSourceIDDefaultAndSet(): void
	{
		$this->assertSame('', $this->control->getDataSourceID());
		$this->assertFalse(PradoUnit::invoke($this->control, 'getUsingDataSourceID'));
		$this->control->setDataSourceID('ds');
		$this->assertSame('ds', $this->control->getDataSourceID());
		$this->assertTrue(PradoUnit::invoke($this->control, 'getUsingDataSourceID'));
	}

	public function testClearingDataSourceIDRequiresBindToNull(): void
	{
		$this->assertFalse(PradoUnit::getProp($this->control, '_requiresBindToNull'));
		$this->control->setDataSourceID('');
		$this->assertFalse(PradoUnit::getProp($this->control, '_requiresBindToNull'));
		$this->control->setDataSourceID('ds');
		$this->assertFalse(PradoUnit::getProp($this->control, '_requiresBindToNull'));
		$this->control->setDataSourceID('');
		$this->assertTrue(PradoUnit::getProp($this->control, '_requiresBindToNull'));
	}

	public function testDataMemberDefaultAndSet(): void
	{
		$this->assertSame('', $this->control->getDataMember());
		$this->control->setDataMember('items');
		$this->assertSame('items', $this->control->getDataMember());
		$this->control->setDataMember('');
		$this->assertSame('', $this->control->getDataMember());
	}

	// ---------------------------------------------------------------------
	// Flags
	// ---------------------------------------------------------------------

	public function testInitializedFlag(): void
	{
		$this->assertFalse(PradoUnit::invoke($this->control, 'getInitialized'));
		PradoUnit::invoke($this->control, 'setInitialized', 'true');
		$this->assertTrue(PradoUnit::invoke($this->control, 'getInitialized'));
		PradoUnit::invoke($this->control, 'setInitialized', false);
		$this->assertFalse(PradoUnit::invoke($this->control, 'getInitialized'));
	}

	public function testIsDataBoundFlag(): void
	{
		$this->assertFalse(PradoUnit::invoke($this->control, 'getIsDataBound'));
		PradoUnit::invoke($this->control, 'setIsDataBound', true);
		$this->assertTrue(PradoUnit::invoke($this->control, 'getIsDataBound'));
	}

	public function testRequiresDataBindingFlag(): void
	{
		$this->assertFalse(PradoUnit::invoke($this->control, 'getRequiresDataBinding'));
		PradoUnit::invoke($this->control, 'setRequiresDataBinding', 'true');
		$this->assertTrue(PradoUnit::invoke($this->control, 'getRequiresDataBinding'));
		PradoUnit::invoke($this->control, 'setRequiresDataBinding', false);
		$this->assertFalse(PradoUnit::invoke($this->control, 'getRequiresDataBinding'));
		$this->assertSame([], $this->control->boundData);
	}

	public function testOnDataSourceChangedBeforeInitializationDoesNotRequireBinding(): void
	{
		$this->control->setDataSource([1, 2]);
		$this->assertFalse(PradoUnit::invoke($this->control, 'getRequiresDataBinding'));
	}

	public function testOnDataSourceChangedAfterInitializationRequiresBinding(): void
	{
		PradoUnit::invoke($this->control, 'setInitialized', true);
		$this->control->setDataSource([1, 2]);
		$this->assertTrue(PradoUnit::invoke($this->control, 'getRequiresDataBinding'));

		PradoUnit::invoke($this->control, 'setRequiresDataBinding', false);
		$this->control->setDataSourceID('ds');
		$this->assertTrue(PradoUnit::invoke($this->control, 'getRequiresDataBinding'));
	}

	public function testOnDataSourceChangedInvalidatesCachedView(): void
	{
		$this->control->setDataSource([1]);
		PradoUnit::invoke($this->control, 'getDataSourceView');
		$this->assertTrue(PradoUnit::getProp($this->control, '_currentViewValid'));
		$this->assertTrue(PradoUnit::getProp($this->control, '_currentDataSourceValid'));
		$this->control->onDataSourceChanged();
		$this->assertFalse(PradoUnit::getProp($this->control, '_currentViewValid'));
		$this->assertFalse(PradoUnit::getProp($this->control, '_currentDataSourceValid'));
	}

	// ---------------------------------------------------------------------
	// Paging properties
	// ---------------------------------------------------------------------

	public function testAllowPaging(): void
	{
		$this->assertFalse($this->control->getAllowPaging());
		$this->control->setAllowPaging('true');
		$this->assertTrue($this->control->getAllowPaging());
		$this->control->setAllowPaging(false);
		$this->assertFalse($this->control->getAllowPaging());
	}

	public function testAllowCustomPaging(): void
	{
		$this->assertFalse($this->control->getAllowCustomPaging());
		$this->control->setAllowCustomPaging('1');
		$this->assertTrue($this->control->getAllowCustomPaging());
		$this->control->setAllowCustomPaging(false);
		$this->assertFalse($this->control->getAllowCustomPaging());
	}

	public function testCurrentPageIndex(): void
	{
		$this->assertSame(0, $this->control->getCurrentPageIndex());
		$this->control->setCurrentPageIndex('3');
		$this->assertSame(3, $this->control->getCurrentPageIndex());
		$this->control->setCurrentPageIndex(-5);
		$this->assertSame(0, $this->control->getCurrentPageIndex());
	}

	public function testPageSize(): void
	{
		$this->assertSame(10, $this->control->getPageSize());
		$this->control->setPageSize('25');
		$this->assertSame(25, $this->control->getPageSize());
		$this->control->setPageSize(1);
		$this->assertSame(1, $this->control->getPageSize());
	}

	public function testPageSizeBelowOneThrows(): void
	{
		$this->expectException(TInvalidDataValueException::class);
		$this->control->setPageSize(0);
	}

	public function testVirtualItemCount(): void
	{
		$this->assertSame(0, $this->control->getVirtualItemCount());
		$this->control->setVirtualItemCount('42');
		$this->assertSame(42, $this->control->getVirtualItemCount());
		$this->control->setVirtualItemCount(0);
		$this->assertSame(0, $this->control->getVirtualItemCount());
	}

	public function testVirtualItemCountNegativeThrows(): void
	{
		$this->expectException(TInvalidDataValueException::class);
		$this->control->setVirtualItemCount(-1);
	}

	public function testPageCountDefault(): void
	{
		$this->assertSame(1, $this->control->getPageCount());
	}

	public function testCreatePagedDataSourceCopiesPagingSettings(): void
	{
		$this->control->setAllowPaging(true);
		$this->control->setAllowCustomPaging(true);
		$this->control->setPageSize(7);
		$this->control->setCurrentPageIndex(2);
		$this->control->setVirtualItemCount(50);
		$ds = PradoUnit::invoke($this->control, 'createPagedDataSource');
		$this->assertInstanceOf(TPagedDataSource::class, $ds);
		$this->assertTrue($ds->getAllowPaging());
		$this->assertTrue($ds->getAllowCustomPaging());
		$this->assertSame(7, $ds->getPageSize());
		$this->assertSame(2, $ds->getCurrentPageIndex());
		$this->assertSame(50, $ds->getVirtualItemCount());
		$this->assertNull($ds->getDataSource());
	}

	public function testCreatePagedDataSourceDefaults(): void
	{
		$ds = PradoUnit::invoke($this->control, 'createPagedDataSource');
		$this->assertFalse($ds->getAllowPaging());
		$this->assertFalse($ds->getAllowCustomPaging());
		$this->assertSame(10, $ds->getPageSize());
		$this->assertSame(0, $ds->getCurrentPageIndex());
		$this->assertSame(0, $ds->getVirtualItemCount());
	}

	// ---------------------------------------------------------------------
	// SelectParameters
	// ---------------------------------------------------------------------

	public function testSelectParametersIsLazySingleton(): void
	{
		$this->assertNull(PradoUnit::getProp($this->control, '_parameters'));
		$params = $this->control->getSelectParameters();
		$this->assertInstanceOf(TDataSourceSelectParameters::class, $params);
		$this->assertSame($params, $this->control->getSelectParameters());
	}

	// ---------------------------------------------------------------------
	// dataBind
	// ---------------------------------------------------------------------

	public function testDataBindWithArrayDataSource(): void
	{
		$this->control->setDataSource(['a' => 1, 'b' => 2]);
		$this->control->dataBind();
		$this->assertCount(1, $this->control->boundData);
		$data = $this->control->boundData[0];
		$this->assertInstanceOf(TMap::class, $data);
		$this->assertSame(['a' => 1, 'b' => 2], $data->toArray());
		$this->assertTrue(PradoUnit::invoke($this->control, 'getIsDataBound'));
		$this->assertFalse(PradoUnit::invoke($this->control, 'getRequiresDataBinding'));
	}

	public function testDataBindWithTraversableDataSourcePassesSameObject(): void
	{
		$list = new TList([1, 2, 3]);
		$this->control->setDataSource($list);
		$this->control->dataBind();
		$this->assertSame([$list], $this->control->boundData);
	}

	public function testDataBindWithoutDataSourceSkipsPerformDataBinding(): void
	{
		$events = [];
		$this->control->attachEventHandler('OnDataBinding', function ($sender, $param) use (&$events) {
			$events[] = 'binding';
		});
		$this->control->attachEventHandler('OnDataBound', function ($sender, $param) use (&$events) {
			$events[] = 'bound';
		});
		$this->control->dataBind();
		$this->assertSame([], $this->control->boundData);
		$this->assertSame(['binding', 'bound'], $events);
		$this->assertTrue(PradoUnit::invoke($this->control, 'getIsDataBound'));
		$this->assertNull(PradoUnit::invoke($this->control, 'getDataSourceView'));
	}

	public function testDataBindRaisesEventsInOrderAroundPerformDataBinding(): void
	{
		$events = [];
		$control = $this->control;
		$this->control->attachEventHandler('OnDataBinding', function ($sender, $param) use (&$events, $control) {
			$events[] = ['binding', $sender === $control, $param, count($control->boundData)];
		});
		$this->control->attachEventHandler('OnDataBound', function ($sender, $param) use (&$events, $control) {
			$events[] = ['bound', $sender === $control, $param, count($control->boundData)];
		});
		$this->control->setDataSource([1]);
		$this->control->dataBind();
		$this->assertSame([
			['binding', true, null, 0],
			['bound', true, null, 1],
		], $events);
	}

	public function testOnDataBoundRaisesEventWithParam(): void
	{
		$received = null;
		$this->control->attachEventHandler('OnDataBound', function ($sender, $param) use (&$received) {
			$received = $param;
		});
		$this->control->onDataBound('p');
		$this->assertSame('p', $received);
	}

	public function testDataBindNonTraversableViewResultSkipsPerformDataBinding(): void
	{
		[, , $view] = $this->buildContainerWithSource(['not', 'traversable']);
		$this->control->setDataSourceID('ds');
		$this->control->dataBind();
		$this->assertSame([], $this->control->boundData);
		$this->assertCount(1, $view->selectCalls);
		$this->assertTrue(PradoUnit::invoke($this->control, 'getIsDataBound'));
	}

	public function testDataBindWithoutPagingClearsPageCount(): void
	{
		$this->control->setViewState('PageCount', 5);
		$this->control->setDataSource([1, 2, 3]);
		$this->control->dataBind();
		$this->assertSame(1, $this->control->getPageCount());
	}

	public function testDataBindWithPagingPassesPagedDataSource(): void
	{
		$this->control->setAllowPaging(true);
		$this->control->setPageSize(2);
		$this->control->setCurrentPageIndex(1);
		$this->control->setDataSource(['a', 'b', 'c', 'd', 'e']);
		$this->control->dataBind();

		$this->assertCount(1, $this->control->boundData);
		$ds = $this->control->boundData[0];
		$this->assertInstanceOf(TPagedDataSource::class, $ds);
		$this->assertSame(3, $this->control->getPageCount());
		$this->assertSame(1, $ds->getCurrentPageIndex());
		$this->assertSame(1, $this->control->getCurrentPageIndex());
		$this->assertSame(['c', 'd'], iterator_to_array($ds->getIterator(), false));
	}

	public function testDataBindWithPagingClampsCurrentPageIndex(): void
	{
		$this->control->setAllowPaging(true);
		$this->control->setPageSize(2);
		$this->control->setCurrentPageIndex(9);
		$this->control->setDataSource(['a', 'b', 'c']);
		$this->control->dataBind();

		$ds = $this->control->boundData[0];
		$this->assertSame(2, $this->control->getPageCount());
		$this->assertSame(1, $ds->getCurrentPageIndex());
		$this->assertSame(1, $this->control->getCurrentPageIndex());
		$this->assertSame(['c'], iterator_to_array($ds->getIterator(), false));
	}

	public function testDataBindWithPagingEmptyDataHasOnePage(): void
	{
		$this->control->setAllowPaging(true);
		$this->control->setCurrentPageIndex(3);
		$this->control->setDataSource([]);
		$this->control->dataBind();
		$this->assertSame(1, $this->control->getPageCount());
		$this->assertSame(0, $this->control->getCurrentPageIndex());
	}

	public function testDataBindWithCustomPagingUsesVirtualItemCount(): void
	{
		$this->control->setAllowPaging(true);
		$this->control->setAllowCustomPaging(true);
		$this->control->setPageSize(2);
		$this->control->setVirtualItemCount(10);
		$this->control->setCurrentPageIndex(3);
		$this->control->setDataSource(['g', 'h']);
		$this->control->dataBind();

		$ds = $this->control->boundData[0];
		$this->assertSame(5, $this->control->getPageCount());
		$this->assertSame(3, $this->control->getCurrentPageIndex());
		$this->assertSame(['g', 'h'], iterator_to_array($ds->getIterator(), false));
	}

	// ---------------------------------------------------------------------
	// Data source views
	// ---------------------------------------------------------------------

	public function testDetermineDataSourceWrapsDataSourceInReadOnlyDataSource(): void
	{
		$this->control->setDataSource([1, 2]);
		$this->control->setDataMember('m');
		$ds = PradoUnit::invoke($this->control, 'determineDataSource');
		$this->assertInstanceOf(TReadOnlyDataSource::class, $ds);
		$this->assertSame($ds, PradoUnit::invoke($this->control, 'determineDataSource'));

		$view = PradoUnit::invoke($this->control, 'getDataSourceView');
		$this->assertInstanceOf(TReadOnlyDataSourceView::class, $view);
		$this->assertSame('m', $view->getName());
		$this->assertSame($view, PradoUnit::invoke($this->control, 'getDataSourceView'));
		$this->assertFalse(PradoUnit::getProp($this->control, '_currentViewIsFromDataSourceID'));
	}

	public function testDetermineDataSourceNullWithoutSource(): void
	{
		$this->assertNull(PradoUnit::invoke($this->control, 'determineDataSource'));
	}

	public function testDataSourceIDResolvesDataSourceControl(): void
	{
		[, $source, $view] = $this->buildContainerWithSource(new TList(['x', 'y']));
		$this->control->setDataSourceID('ds');

		$this->assertSame($source, PradoUnit::invoke($this->control, 'determineDataSource'));
		$this->assertSame($view, PradoUnit::invoke($this->control, 'getDataSourceView'));
		$this->assertTrue(PradoUnit::getProp($this->control, '_currentViewIsFromDataSourceID'));
		$this->assertTrue($view->hasEventHandler('OnDataSourceViewChanged'));
		$this->assertCount(1, $view->getEventHandlers('OnDataSourceViewChanged'));
	}

	public function testDataBindWithDataSourceIDSelectsWithSelectParameters(): void
	{
		$data = new TList(['x', 'y']);
		[, , $view] = $this->buildContainerWithSource($data);
		$this->control->setDataSourceID('ds');
		$this->control->dataBind();

		$this->assertSame([$data], $this->control->boundData);
		$this->assertCount(1, $view->selectCalls);
		$this->assertSame($this->control->getSelectParameters(), $view->selectCalls[0]);
	}

	public function testDataBindWithDataSourceIDAndDataMember(): void
	{
		$data = new TList(['m']);
		[, $source] = $this->buildContainerWithSource(new TList(['default']));
		$memberView = new TDataBoundControlTestView($source, 'members');
		$memberView->result = $data;
		$source->views['members'] = $memberView;

		$this->control->setDataSourceID('ds');
		$this->control->setDataMember('members');
		$this->control->dataBind();
		$this->assertSame([$data], $this->control->boundData);
	}

	public function testDataSourceIDTakesPrecedenceOverDataSource(): void
	{
		$data = new TList(['fromID']);
		[, $source] = $this->buildContainerWithSource($data);
		$this->control->setDataSource(['fromDataSource']);
		$this->control->setDataSourceID('ds');
		$this->assertSame($source, PradoUnit::invoke($this->control, 'determineDataSource'));
	}

	public function testDataSourceIDInexistentThrows(): void
	{
		$this->buildContainerWithSource();
		$this->control->setDataSourceID('missing');
		$this->expectException(TInvalidDataValueException::class);
		$this->expectExceptionMessage("DataSourceID 'missing' does not refer to an existing control.");
		PradoUnit::invoke($this->control, 'determineDataSource');
	}

	public function testDataSourceIDNotADataSourceThrows(): void
	{
		[$container] = $this->buildContainerWithSource();
		$plain = new TControl();
		$plain->setID('plain');
		$container->getControls()->add($plain);
		$this->control->setDataSourceID('plain');
		$this->expectException(TInvalidDataValueException::class);
		$this->expectExceptionMessage("DataSourceID 'plain' must refer to a control implementing IDataSource.");
		PradoUnit::invoke($this->control, 'determineDataSource');
	}

	public function testInvalidDataMemberThrows(): void
	{
		$this->buildContainerWithSource();
		$this->control->setDataSourceID('ds');
		$this->control->setDataMember('nope');
		$this->expectException(TInvalidDataValueException::class);
		$this->expectExceptionMessage("DataMember 'nope' does not name a view of the data source.");
		PradoUnit::invoke($this->control, 'getDataSourceView');
	}

	public function testDataSourceViewChangedRequiresDataBinding(): void
	{
		[, , $view] = $this->buildContainerWithSource(new TList([1]));
		$this->control->setDataSourceID('ds');
		PradoUnit::invoke($this->control, 'getDataSourceView');
		$this->assertFalse(PradoUnit::invoke($this->control, 'getRequiresDataBinding'));
		$view->onDataSourceViewChanged(null);
		$this->assertTrue(PradoUnit::invoke($this->control, 'getRequiresDataBinding'));
	}

	public function testDataSourceViewChangedMethodSetsRequiresDataBinding(): void
	{
		$this->control->dataSourceViewChanged(null, null);
		$this->assertTrue(PradoUnit::invoke($this->control, 'getRequiresDataBinding'));
	}

	public function testDataSourceViewChangedAfterPreRenderRebinds(): void
	{
		$data = new TList([1, 2]);
		[, , $view] = $this->buildContainerWithSource($data);
		$this->control->setDataSourceID('ds');
		$this->control->onPreRender(null);
		$this->assertSame([], $this->control->boundData);

		PradoUnit::invoke($this->control, 'getDataSourceView');
		$view->onDataSourceViewChanged(null);
		$this->assertSame([$data], $this->control->boundData);
		$this->assertFalse(PradoUnit::invoke($this->control, 'getRequiresDataBinding'));
	}

	public function testRecomputingViewFromDataSourceIDRevalidates(): void
	{
		[, $source, $view] = $this->buildContainerWithSource(new TList([1]));
		$second = new TDataBoundControlTestView($source, 'second');
		$source->views['second'] = $second;
		$this->control->setDataSourceID('ds');
		$this->assertSame($view, PradoUnit::invoke($this->control, 'getDataSourceView'));

		$this->control->setDataMember('second');
		$this->control->onDataSourceChanged();
		$this->assertSame($second, PradoUnit::invoke($this->control, 'getDataSourceView'));
		$this->assertTrue($second->hasEventHandler('OnDataSourceViewChanged'));
		$this->assertFalse($view->hasEventHandler('OnDataSourceViewChanged'));
	}

	// ---------------------------------------------------------------------
	// ensureDataBound / setRequiresDataBinding
	// ---------------------------------------------------------------------

	public function testEnsureDataBoundDoesNothingWhenNotRequired(): void
	{
		$this->buildContainerWithSource(new TList([1]));
		$this->control->setDataSourceID('ds');
		PradoUnit::invoke($this->control, 'ensureDataBound');
		$this->assertSame([], $this->control->boundData);
		$this->assertFalse(PradoUnit::invoke($this->control, 'getIsDataBound'));
	}

	public function testEnsureDataBoundDoesNothingWithDataSourceObjectOnly(): void
	{
		$this->control->setDataSource([1]);
		PradoUnit::invoke($this->control, 'setRequiresDataBinding', true);
		PradoUnit::invoke($this->control, 'ensureDataBound');
		$this->assertSame([], $this->control->boundData);
		$this->assertTrue(PradoUnit::invoke($this->control, 'getRequiresDataBinding'));
	}

	public function testEnsureDataBoundBindsWithDataSourceID(): void
	{
		$data = new TList([1]);
		$this->buildContainerWithSource($data);
		$this->control->setDataSourceID('ds');
		PradoUnit::invoke($this->control, 'setRequiresDataBinding', true);
		PradoUnit::invoke($this->control, 'ensureDataBound');
		$this->assertSame([$data], $this->control->boundData);
		$this->assertFalse(PradoUnit::invoke($this->control, 'getRequiresDataBinding'));
	}

	public function testEnsureDataBoundBindsToNullAfterClearingDataSourceID(): void
	{
		$events = 0;
		$this->control->attachEventHandler('OnDataBound', function () use (&$events) {
			$events++;
		});
		$this->control->setDataSourceID('ds');
		$this->control->setDataSourceID('');
		PradoUnit::invoke($this->control, 'setRequiresDataBinding', true);
		PradoUnit::invoke($this->control, 'ensureDataBound');
		$this->assertSame(1, $events);
		$this->assertSame([], $this->control->boundData);
		$this->assertFalse(PradoUnit::getProp($this->control, '_requiresBindToNull'));
	}

	public function testSetRequiresDataBindingAfterPreRenderBindsImmediately(): void
	{
		$data = new TList([1]);
		$this->buildContainerWithSource($data);
		$this->control->setDataSourceID('ds');
		$this->control->onPreRender(null);
		$this->assertTrue(PradoUnit::getProp($this->control, '_prerendered'));
		$this->assertSame([], $this->control->boundData);

		PradoUnit::invoke($this->control, 'setRequiresDataBinding', true);
		$this->assertSame([$data], $this->control->boundData);
		$this->assertFalse(PradoUnit::invoke($this->control, 'getRequiresDataBinding'));
	}

	public function testSetRequiresDataBindingAfterPreRenderWithoutDataSourceIDOnlyFlags(): void
	{
		$this->control->setDataSource([1]);
		$this->control->onPreRender(null);
		PradoUnit::invoke($this->control, 'setRequiresDataBinding', true);
		$this->assertSame([], $this->control->boundData);
		$this->assertTrue(PradoUnit::invoke($this->control, 'getRequiresDataBinding'));
	}

	// ---------------------------------------------------------------------
	// onPreRender
	// ---------------------------------------------------------------------

	public function testOnPreRenderBindsPendingDataSourceID(): void
	{
		$data = new TList([1, 2]);
		$this->buildContainerWithSource($data);
		$this->control->setDataSourceID('ds');
		PradoUnit::invoke($this->control, 'setRequiresDataBinding', true);

		$preRendered = false;
		$this->control->attachEventHandler('OnPreRender', function () use (&$preRendered) {
			$preRendered = true;
		});
		$this->control->onPreRender(null);
		$this->assertSame([$data], $this->control->boundData);
		$this->assertTrue($preRendered);
		$this->assertTrue(PradoUnit::invoke($this->control, 'getIsDataBound'));
	}

	public function testOnPreRenderWithoutPendingBindingDoesNotBind(): void
	{
		$this->control->setDataSource([1]);
		$this->control->onPreRender(null);
		$this->assertSame([], $this->control->boundData);
		$this->assertFalse(PradoUnit::invoke($this->control, 'getIsDataBound'));
	}

	// ---------------------------------------------------------------------
	// onInit / pagePreLoad
	// ---------------------------------------------------------------------

	public function testOnInitAttachesPagePreLoadHandler(): void
	{
		$page = new TPage();
		$page->getControls()->add($this->control);
		$this->control->onInit(null);
		$this->assertTrue($page->hasEventHandler('OnPreLoad'));

		$page->onPreLoad(null);
		$this->assertTrue(PradoUnit::invoke($this->control, 'getInitialized'));
		$this->assertTrue(PradoUnit::invoke($this->control, 'getRequiresDataBinding'));
	}

	public function testPagePreLoadOnGetRequiresDataBinding(): void
	{
		$page = new TPage();
		$page->getControls()->add($this->control);
		PradoUnit::invoke($this->control, 'setIsDataBound', true);
		$this->control->pagePreLoad($page, null);
		$this->assertTrue(PradoUnit::invoke($this->control, 'getInitialized'));
		$this->assertTrue(PradoUnit::invoke($this->control, 'getRequiresDataBinding'));
	}

	public function testPagePreLoadOnPostBackWithBoundViewStateDoesNotRequireBinding(): void
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_postData', new TMap());
		$this->assertTrue($page->getIsPostBack());
		$page->getControls()->add($this->control);
		PradoUnit::invoke($this->control, 'setIsDataBound', true);
		$this->control->pagePreLoad($page, null);
		$this->assertTrue(PradoUnit::invoke($this->control, 'getInitialized'));
		$this->assertFalse(PradoUnit::invoke($this->control, 'getRequiresDataBinding'));
	}

	public function testPagePreLoadOnPostBackNotYetBoundRequiresBinding(): void
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_postData', new TMap());
		$page->getControls()->add($this->control);
		$this->control->pagePreLoad($page, null);
		$this->assertTrue(PradoUnit::invoke($this->control, 'getRequiresDataBinding'));
	}

	public function testPagePreLoadOnPostBackWithViewStateDisabledRequiresBinding(): void
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_postData', new TMap());
		$page->getControls()->add($this->control);
		PradoUnit::invoke($this->control, 'setIsDataBound', true);
		$this->control->setEnableViewState(false);
		$this->control->pagePreLoad($page, null);
		$this->assertTrue(PradoUnit::invoke($this->control, 'getRequiresDataBinding'));
	}

	public function testDataSourceChangesAfterPagePreLoadRequireBinding(): void
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_postData', new TMap());
		$page->getControls()->add($this->control);
		PradoUnit::invoke($this->control, 'setIsDataBound', true);
		$this->control->pagePreLoad($page, null);
		$this->assertFalse(PradoUnit::invoke($this->control, 'getRequiresDataBinding'));
		$this->control->setDataSource([1]);
		$this->assertTrue(PradoUnit::invoke($this->control, 'getRequiresDataBinding'));
	}
}
