<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Collections\TList;
use Prado\Collections\TMap;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Web\UI\WebControls\TDataSourceControl;
use Prado\Web\UI\WebControls\TDataSourceSelectParameters;
use Prado\Web\UI\WebControls\TDataSourceView;
use Prado\Web\UI\WebControls\TReadOnlyDataSource;
use Prado\Web\UI\WebControls\TReadOnlyDataSourceView;

/**
 * Data source view returning a fixed result.
 */
class TReadOnlyDataSourceTestView extends TDataSourceView
{
	public mixed $result = null;

	public function select($parameters)
	{
		return $this->result;
	}
}

/**
 * Data source control holding named views and recording requested view names.
 */
class TReadOnlyDataSourceTestSource extends TDataSourceControl
{
	public array $views = [];
	public array $requested = [];

	public function getView($viewName)
	{
		$this->requested[] = $viewName;
		return $this->views[$viewName] ?? null;
	}

	public function getViewNames()
	{
		return array_keys($this->views);
	}
}

class TReadOnlyDataSourceTest extends TestCase
{
	// TReadOnlyDataSource

	public function testIsDataSourceControl(): void
	{
		$source = new TReadOnlyDataSource([], '');
		$this->assertInstanceOf(TDataSourceControl::class, $source);
	}

	public function testArrayDataSourceViewSelectsTMap(): void
	{
		$source = new TReadOnlyDataSource(['a' => 1, 'b' => 2], 'member');
		$view = $source->getView('ignored');

		$this->assertInstanceOf(TReadOnlyDataSourceView::class, $view);
		$this->assertSame('member', $view->getName());
		$this->assertSame($source, $view->getDataSource());
		$data = $view->select(new TDataSourceSelectParameters());
		$this->assertInstanceOf(TMap::class, $data);
		$this->assertSame(['a' => 1, 'b' => 2], $data->toArray());
	}

	public function testTraversableDataSourceViewSelectsSameObject(): void
	{
		$list = new TList([1, 2, 3]);
		$source = new TReadOnlyDataSource($list, '');
		$view = $source->getView('');

		$this->assertInstanceOf(TReadOnlyDataSourceView::class, $view);
		$this->assertSame($list, $view->select(null));
	}

	public function testGeneratorDataSourceIsAccepted(): void
	{
		$generator = (function () {
			yield 'x';
		})();
		$source = new TReadOnlyDataSource($generator, '');
		$this->assertSame($generator, $source->getView('')->select(null));
	}

	public function testGetViewCreatesNewViewEachCall(): void
	{
		$source = new TReadOnlyDataSource([1], 'm');
		$this->assertNotSame($source->getView(''), $source->getView(''));
	}

	public function testDataMemberIsViewName(): void
	{
		$source = new TReadOnlyDataSource([], 'Orders');
		$this->assertSame('Orders', $source->getView('Customers')->getName());
	}

	public function testNullDataMember(): void
	{
		$source = new TReadOnlyDataSource([], null);
		$this->assertNull($source->getView('')->getName());
	}

	public function testIDataSourceDataSourceDelegatesGetView(): void
	{
		$inner = new TReadOnlyDataSourceTestSource();
		$innerView = new TReadOnlyDataSourceTestView($inner, 'v');
		$inner->views['v'] = $innerView;

		$source = new TReadOnlyDataSource($inner, 'member');
		$this->assertSame($innerView, $source->getView('v'));
		$this->assertNull($source->getView('missing'));
		$this->assertSame(['v', 'missing'], $inner->requested);
	}

	public function testGetViewNamesIsEmpty(): void
	{
		$source = new TReadOnlyDataSource([1, 2], 'm');
		$this->assertSame([], $source->getViewNames());
	}

	public function testStringDataSourceThrows(): void
	{
		try {
			new TReadOnlyDataSource('abc', '');
			$this->fail('A string data source must throw TInvalidDataTypeException.');
		} catch (TInvalidDataTypeException $e) {
			$this->assertSame('readonlydatasource_datasource_invalid', $e->getErrorCode());
			$this->assertSame('TReadOnlyDataSource requires an array, an IDataSource, or a Traversable data source.', $e->getMessage());
		}
	}

	public function testNullDataSourceThrows(): void
	{
		$this->expectException(TInvalidDataTypeException::class);
		new TReadOnlyDataSource(null, '');
	}

	public function testPlainObjectDataSourceThrows(): void
	{
		$this->expectException(TInvalidDataTypeException::class);
		new TReadOnlyDataSource(new \stdClass(), '');
	}

	public function testIntegerDataSourceThrows(): void
	{
		$this->expectException(TInvalidDataTypeException::class);
		new TReadOnlyDataSource(5, '');
	}

	// TReadOnlyDataSourceView

	public function testViewNullDataSourceBecomesEmptyTMap(): void
	{
		$owner = new TReadOnlyDataSourceTestSource();
		$view = new TReadOnlyDataSourceView($owner, 'v', null);

		$data = $view->select(null);
		$this->assertInstanceOf(TMap::class, $data);
		$this->assertSame(0, $data->getCount());
		$this->assertSame('v', $view->getName());
		$this->assertSame($owner, $view->getDataSource());
	}

	public function testViewArrayDataSourceBecomesTMap(): void
	{
		$view = new TReadOnlyDataSourceView(new TReadOnlyDataSourceTestSource(), '', [10, 20]);
		$data = $view->select(null);
		$this->assertInstanceOf(TMap::class, $data);
		$this->assertSame([10, 20], $data->toArray());
	}

	public function testViewSelectReturnsSameObjectEachCall(): void
	{
		$view = new TReadOnlyDataSourceView(new TReadOnlyDataSourceTestSource(), '', ['a']);
		$this->assertSame($view->select(null), $view->select(new TDataSourceSelectParameters()));
	}

	public function testViewTraversableDataSourceIsKept(): void
	{
		$iterator = new \ArrayIterator(['k' => 'v']);
		$view = new TReadOnlyDataSourceView(new TReadOnlyDataSourceTestSource(), '', $iterator);
		$this->assertSame($iterator, $view->select(null));
	}

	public function testViewInvalidDataSourceThrows(): void
	{
		try {
			new TReadOnlyDataSourceView(new TReadOnlyDataSourceTestSource(), '', 'abc');
			$this->fail('A string data source must throw TInvalidDataTypeException.');
		} catch (TInvalidDataTypeException $e) {
			$this->assertSame('readonlydatasourceview_datasource_invalid', $e->getErrorCode());
			$this->assertSame('TReadOnlyDataSourceView requires null, an array, or a Traversable data source.', $e->getMessage());
		}
	}

	public function testViewObjectDataSourceThrows(): void
	{
		$this->expectException(TInvalidDataTypeException::class);
		new TReadOnlyDataSourceView(new TReadOnlyDataSourceTestSource(), '', new \stdClass());
	}

	public function testViewCapabilitiesAreReadOnly(): void
	{
		$view = new TReadOnlyDataSourceView(new TReadOnlyDataSourceTestSource(), '', []);
		$this->assertFalse($view->getCanInsert());
		$this->assertFalse($view->getCanUpdate());
		$this->assertFalse($view->getCanDelete());
	}
}
