<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TNotSupportedException;
use Prado\TComponent;
use Prado\TEventParameter;
use Prado\Web\UI\WebControls\TDataSourceControl;
use Prado\Web\UI\WebControls\TDataSourceSelectParameters;
use Prado\Web\UI\WebControls\TDataSourceView;

/**
 * Concrete TDataSourceView that returns the select parameters it receives.
 */
class TDataSourceViewTestView extends TDataSourceView
{
	public function select($parameters)
	{
		return $parameters;
	}
}

/**
 * Concrete TDataSourceControl serving as the owner of the view.
 */
class TDataSourceViewTestSource extends TDataSourceControl
{
}

class TDataSourceViewTest extends TestCase
{
	private TDataSourceViewTestSource $owner;
	private TDataSourceViewTestView $view;

	protected function setUp(): void
	{
		$this->owner = new TDataSourceViewTestSource();
		$this->view = new TDataSourceViewTestView($this->owner, 'myView');
	}

	public function testIsComponent(): void
	{
		$this->assertInstanceOf(TComponent::class, $this->view);
	}

	public function testConstructorRequiresDataSourceOwner(): void
	{
		$this->expectException(\TypeError::class);
		new TDataSourceViewTestView(new TComponent(), 'myView');
	}

	public function testGetName(): void
	{
		$this->assertSame('myView', $this->view->getName());
		$this->assertSame('myView', $this->view->Name);
	}

	public function testGetNameEmpty(): void
	{
		$view = new TDataSourceViewTestView($this->owner, '');
		$this->assertSame('', $view->getName());
	}

	public function testGetDataSource(): void
	{
		$this->assertSame($this->owner, $this->view->getDataSource());
		$this->assertSame($this->owner, $this->view->DataSource);
	}

	public function testCapabilityDefaultsAreFalse(): void
	{
		$this->assertFalse($this->view->getCanDelete());
		$this->assertFalse($this->view->getCanInsert());
		$this->assertFalse($this->view->getCanPage());
		$this->assertFalse($this->view->getCanGetRowCount());
		$this->assertFalse($this->view->getCanSort());
		$this->assertFalse($this->view->getCanUpdate());
	}

	public function testSelectIsImplementedBySubclass(): void
	{
		$params = new TDataSourceSelectParameters();
		$this->assertSame($params, $this->view->select($params));
	}

	public function testInsertAtThrows(): void
	{
		try {
			$this->view->insertAt(['a' => 1]);
			$this->fail('insertAt() must throw TNotSupportedException.');
		} catch (TNotSupportedException $e) {
			$this->assertSame('datasourceview_insert_unsupported', $e->getErrorCode());
		}
	}

	public function testUpdateThrows(): void
	{
		try {
			$this->view->update(['id' => 1], ['a' => 2]);
			$this->fail('update() must throw TNotSupportedException.');
		} catch (TNotSupportedException $e) {
			$this->assertSame('datasourceview_update_unsupported', $e->getErrorCode());
		}
	}

	public function testDeleteThrows(): void
	{
		try {
			$this->view->delete(['id' => 1]);
			$this->fail('delete() must throw TNotSupportedException.');
		} catch (TNotSupportedException $e) {
			$this->assertSame('datasourceview_delete_unsupported', $e->getErrorCode());
		}
	}

	public function testOnDataSourceViewChangedRaisesEvent(): void
	{
		$received = [];
		$this->view->attachEventHandler('OnDataSourceViewChanged', function ($sender, $param) use (&$received) {
			$received[] = [$sender, $param];
		});
		$param = new TEventParameter();
		$this->view->onDataSourceViewChanged($param);
		$this->assertCount(1, $received);
		$this->assertSame($this->view, $received[0][0]);
		$this->assertSame($param, $received[0][1]);
	}

	public function testOnDataSourceViewChangedWithoutHandlers(): void
	{
		$this->view->onDataSourceViewChanged(null);
		$this->assertFalse($this->view->hasEventHandler('OnDataSourceViewChanged'));
	}
}
