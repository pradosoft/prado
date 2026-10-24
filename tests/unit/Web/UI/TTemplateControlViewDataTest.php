<?php

namespace Prado\Test\Unit\Web\UI;

use Prado\Web\UI\IViewDataModel;
use Prado\Web\UI\TTemplateControl;
use Prado\Web\UI\WebControls\TCheckBox;
use Prado\Web\UI\WebControls\TDatePicker;
use Prado\Web\UI\WebControls\TDropDownList;
use Prado\Web\UI\WebControls\TLabel;
use Prado\Web\UI\WebControls\TPanel;
use Prado\Web\UI\WebControls\TTextBox;

/**
 * An IViewDataModel whose data is set by the test and which records what it receives.
 */
class ViewDataModelStub implements IViewDataModel
{
	public array $data = [];
	public ?array $received = null;

	public function getViewData(): array
	{
		return $this->data;
	}

	public function setViewData(array $data): void
	{
		$this->received = $data;
	}
}

/**
 * The same contract by method name only, as a record on PRADO 4.3 provides it.
 */
class ViewDataDuckModel
{
	public array $data = [];
	public ?array $received = null;

	public function getViewData(): array
	{
		return $this->data;
	}

	public function setViewData(array $data): void
	{
		$this->received = $data;
	}
}

class ViewDataPlainModel
{
	public $name = 'Ada';
	public $active = true;
	public $born = '2001-02-03';
	public $ID = 'not a control';
	protected $hidden = 'hidden';
}

class ViewDataThrowingTextBox extends TTextBox
{
	public function getData()
	{
		throw new \RuntimeException('read failed');
	}

	public function setData($value)
	{
		throw new \RuntimeException('write failed');
	}
}

/**
 * Tests {@see TTemplateControl::tryToUpdateView()}, {@see TTemplateControl::tryToUpdateModel()},
 * {@see TTemplateControl::tryToUpdateAR()}, and {@see \Prado\Web\UI\TControl::getRegisteredObjects()}.
 */
class TTemplateControlViewDataTest extends \PHPUnit\Framework\TestCase
{
	private TTemplateControl $view;
	private TTextBox $name;
	private TCheckBox $active;
	private TDatePicker $born;
	private TDropDownList $items;
	private TDropDownList $choice;
	private TLabel $caption;
	private TPanel $panel;

	protected function setUp(): void
	{
		$this->view = new TTemplateControl();
		$this->name = new TTextBox();
		$this->active = new TCheckBox();
		$this->born = new TDatePicker();
		$this->items = new TDropDownList();
		$this->choice = new TDropDownList();
		$this->choice->setDataSource(['a' => 'A', 'b' => 'B']);
		$this->choice->dataBind();
		$this->caption = new TLabel();
		$this->panel = new TPanel();
		foreach (['name', 'active', 'born', 'items', 'choice', 'caption', 'panel'] as $id) {
			$this->view->registerObject($id, $this->{$id});
		}
	}

	// -----------------------------------------------------------------------
	// TControl::getRegisteredObjects
	// -----------------------------------------------------------------------

	public function testGetRegisteredObjectsReturnsNamedObjects(): void
	{
		$objects = $this->view->getRegisteredObjects();

		$this->assertSame(['name', 'active', 'born', 'items', 'choice', 'caption', 'panel'], array_keys($objects));
		$this->assertSame($this->name, $objects['name']);
	}

	public function testGetRegisteredObjectsIsEmptyWithoutRegistrations(): void
	{
		$this->assertSame([], (new TTemplateControl())->getRegisteredObjects());
	}

	// -----------------------------------------------------------------------
	// tryToUpdateView
	// -----------------------------------------------------------------------

	public function testTryToUpdateViewBindsPlainObjectProperties(): void
	{
		$this->view->tryToUpdateView(new ViewDataPlainModel());

		$this->assertSame('Ada', $this->name->getText());
		$this->assertTrue($this->active->getChecked());
		$this->assertSame('2001-02-03', $this->born->getDate());
	}

	public function testTryToUpdateViewBindsIterableToDataBoundControl(): void
	{
		$model = new ViewDataModelStub();
		$model->data = ['items' => ['x' => 'X', 'y' => 'Y', 'z' => 'Z'], 'choice' => 'b'];

		$this->view->tryToUpdateView($model);

		$this->assertSame(3, $this->items->getItems()->getCount());
		$this->assertSame('Y', $this->items->getItems()->itemAt(1)->getText());
		$this->assertSame('b', $this->choice->getSelectedValue());
	}

	public function testTryToUpdateViewSetsLabelThroughIDataRenderer(): void
	{
		$this->view->tryToUpdateView(['caption' => 'Hello']);

		$this->assertSame('Hello', $this->caption->getText());
	}

	public function testTryToUpdateViewSkipsUnknownKeysAndOtherControls(): void
	{
		$this->view->tryToUpdateView(['missing' => 1, 'panel' => 'ignored', 'ID' => 'ignored', 'name' => 'Grace']);

		$this->assertSame('Grace', $this->name->getText());
		$this->assertSame('', $this->view->getID());
	}

	public function testTryToUpdateViewUsesGetViewDataWithoutTheInterface(): void
	{
		$model = new ViewDataDuckModel();
		$model->data = ['name' => 'Duck', 'active' => false];
		$this->active->setChecked(true);

		$this->view->tryToUpdateView($model);

		$this->assertSame('Duck', $this->name->getText());
		$this->assertFalse($this->active->getChecked());
	}

	public function testTryToUpdateViewSwallowsControlExceptionsByDefault(): void
	{
		$this->view->registerObject('broken', new ViewDataThrowingTextBox());

		$this->view->tryToUpdateView(['broken' => 'x', 'name' => 'after']);

		$this->assertSame('after', $this->name->getText());
	}

	public function testTryToUpdateViewRethrowsWhenAsked(): void
	{
		$this->view->registerObject('broken', new ViewDataThrowingTextBox());

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('write failed');
		$this->view->tryToUpdateView(['broken' => 'x'], true);
	}

	// -----------------------------------------------------------------------
	// tryToUpdateModel / tryToUpdateAR
	// -----------------------------------------------------------------------

	private function fillInputs(): void
	{
		$this->name->setText('Ada');
		$this->active->setChecked(true);
		$this->born->setDate('2001-02-03');
		$this->choice->setSelectedValue('b');
		$this->caption->setText('not an input');
	}

	public function testTryToUpdateModelPassesInputValuesToIViewDataModel(): void
	{
		$this->fillInputs();
		$model = new ViewDataModelStub();

		$this->view->tryToUpdateModel($model);

		$this->assertSame(
			['name' => 'Ada', 'active' => true, 'born' => '2001-02-03', 'items' => '', 'choice' => 'b'],
			$model->received
		);
		$this->assertArrayNotHasKey('caption', $model->received, 'TLabel is output only');
		$this->assertArrayNotHasKey('panel', $model->received, 'TPanel holds no data');
	}

	public function testTryToUpdateModelUsesSetViewDataWithoutTheInterface(): void
	{
		$this->fillInputs();
		$model = new ViewDataDuckModel();

		$this->view->tryToUpdateModel($model);

		$this->assertSame('Ada', $model->received['name']);
		$this->assertSame('b', $model->received['choice']);
	}

	public function testTryToUpdateModelWritesOnlyPublicPropertiesOfPlainObject(): void
	{
		$this->fillInputs();
		$this->name->setText('Grace');
		$this->active->setChecked(false);
		$model = new ViewDataPlainModel();

		$this->view->tryToUpdateModel($model);

		$this->assertSame('Grace', $model->name);
		$this->assertFalse($model->active);
		$this->assertSame('2001-02-03', $model->born);
		$this->assertSame('not a control', $model->ID);
		$this->assertObjectNotHasProperty('choice', $model);
		$this->assertObjectNotHasProperty('items', $model);
	}

	public function testTryToUpdateModelSwallowsExceptionsByDefault(): void
	{
		$this->fillInputs();
		$this->view->registerObject('broken', new ViewDataThrowingTextBox());
		$model = new ViewDataModelStub();

		$this->view->tryToUpdateModel($model);

		$this->assertSame('Ada', $model->received['name']);
		$this->assertArrayNotHasKey('broken', $model->received);
	}

	public function testTryToUpdateModelRethrowsWhenAsked(): void
	{
		$this->view->registerObject('broken', new ViewDataThrowingTextBox());

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('read failed');
		$this->view->tryToUpdateModel(new ViewDataModelStub(), true);
	}

	public function testTryToUpdateARDelegatesToTryToUpdateModel(): void
	{
		$this->fillInputs();
		$model = new ViewDataModelStub();

		$this->view->tryToUpdateAR($model);

		$this->assertSame('Ada', $model->received['name']);
		$this->assertTrue($model->received['active']);
	}
}
