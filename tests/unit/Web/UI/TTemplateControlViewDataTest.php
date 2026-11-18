<?php

namespace Prado\Test\Unit\Web\UI;

use Prado\Web\UI\IPostBackDataHandler;
use Prado\Web\UI\IViewDataModel;
use Prado\Web\UI\TControl;
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

/**
 * A model that rejects the values it receives.
 */
class ViewDataRejectingModel implements IViewDataModel
{
	public function getViewData(): array
	{
		return [];
	}

	public function setViewData(array $data): void
	{
		throw new \RuntimeException('model rejected');
	}
}

/**
 * An input control that gives no data value.
 */
class ViewDataPostBackOnlyControl extends TControl implements IPostBackDataHandler
{
	public function loadPostData($key, $values)
	{
		return false;
	}

	public function raisePostDataChangedEvent()
	{
	}

	public function getDataChanged()
	{
		return false;
	}
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

	public function testTryToUpdateModelSwallowsAModelExceptionByDefault(): void
	{
		$this->view->tryToUpdateModel(new ViewDataRejectingModel());
		$this->assertTrue(true);
	}

	public function testTryToUpdateModelRethrowsAModelExceptionWhenAsked(): void
	{
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('model rejected');
		$this->view->tryToUpdateModel(new ViewDataRejectingModel(), true);
	}

	public function testTryToUpdateModelWithoutRegisteredObjects(): void
	{
		$model = new ViewDataModelStub();
		(new TTemplateControl())->tryToUpdateModel($model);
		$this->assertSame([], $model->received);
	}

	public function testTryToUpdateModelWithOneRegisteredObjectOfEachKind(): void
	{
		$this->name->setText('Ada');
		$this->born->setDate('2001-02-03');
		foreach (['name' => 'Ada', 'born' => '2001-02-03', 'caption' => null, 'broken' => null] as $id => $expected) {
			$view = new TTemplateControl();
			$view->registerObject($id, $id === 'broken' ? new ViewDataThrowingTextBox() : $this->{$id});
			$model = new ViewDataModelStub();
			$view->tryToUpdateModel($model);
			$this->assertSame($expected === null ? [] : [$id => $expected], $model->received);
		}
	}

	public function testTryToUpdateModelReadsTheControlAfterASwallowedException(): void
	{
		$this->name->setText('Ada');
		$this->born->setDate('2001-02-03');
		$cases = [
			'name' => ['name' => 'Ada'],
			'born' => ['born' => '2001-02-03'],
			'caption' => [],
			'input' => [],
		];
		foreach ($cases as $id => $expected) {
			$view = new TTemplateControl();
			$view->registerObject('broken', new ViewDataThrowingTextBox());
			$view->registerObject($id, $id === 'input' ? new ViewDataPostBackOnlyControl() : $this->{$id});
			$model = new ViewDataModelStub();
			$view->tryToUpdateModel($model);
			$this->assertSame($expected, $model->received, $id);
		}
	}

	public function testTryToUpdateModelSkipsAnInputControlWithoutData(): void
	{
		$view = new TTemplateControl();
		$view->registerObject('input', new ViewDataPostBackOnlyControl());
		$model = new ViewDataModelStub();
		$view->tryToUpdateModel($model);
		$this->assertSame([], $model->received);
	}

	public function testTryToUpdateModelLeavesAPlainObjectWithoutMatchingProperties(): void
	{
		$this->name->setText('Ada');
		$model = new \stdClass();
		$this->view->tryToUpdateModel($model);
		$this->assertSame([], get_object_vars($model));
	}
}
