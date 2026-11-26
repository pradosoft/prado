<?php

namespace Prado\Test\Unit\Web\UI\JuiControls;

use PHPUnit\Framework\TestCase;
use Prado\Prado;
use Prado\Test\Unit\Harness\Traits\TPageLifecycleTrait;
use Prado\Test\Unit\Harness\Web\UI\TTestLifecyclePage;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\Services\TPageService;
use Prado\Web\UI\JuiControls\TJuiDraggable;
use Prado\Web\UI\JuiControls\TJuiDroppable;
use Prado\Web\UI\JuiControls\TJuiEventParameter;
use Prado\Web\UI\TForm;
use Prado\Web\UI\WebControls\THead;

/**
 * Unit tests for {@see TJuiEventParameter}.
 */
class TJuiEventParameterTest extends TestCase
{
	use TPageLifecycleTrait;

	protected function setUp(): void
	{
		$this->resetPageState();
	}

	protected function tearDown(): void
	{
		$this->resetPageState();
	}

	/**
	 * @param callable $onDrop runs in the OnDrop handler, receives the page and the event parameter
	 * @return TTestLifecyclePage a page with the draggable `Drag` and the droppable `Drop`
	 */
	private function newPage(callable $onDrop): TTestLifecyclePage
	{
		$page = new TTestLifecyclePage();
		$page->build = function (TTestLifecyclePage $page, TForm $form) use ($onDrop) {
			$page->getControls()->insertAt(0, new THead());
			$drag = new TJuiDraggable();
			$drag->setID('Drag');
			$form->getControls()->add($drag);
			$drop = new TJuiDroppable();
			$drop->setID('Drop');
			$drop->attachEventHandler('OnDrop', fn ($sender, $param) => $onDrop($page, $param));
			$form->getControls()->add($drop);
		};
		return $page;
	}

	/**
	 * Runs the page for a GET, then a new page as the requested page of a service for a drop callback.
	 * @param mixed $ui
	 * @param callable $onDrop
	 */
	private function runDrop(mixed $ui, callable $onDrop): void
	{
		$this->runPage($this->newPage($onDrop));
		$page = $this->newPage($onDrop);
		$service = new TPageService();
		PradoUnit::setProp($service, '_page', $page);
		$this->runCallback($page, 'Drop', ['event' => 'drop', 'ui' => $ui], [], $service);
	}

	public function testControlPropertyFindsTheControlByClientID(): void
	{
		$found = [];
		$this->runDrop(['draggable' => 'Drag', 'helper' => ''], function ($page, $param) use (&$found) {
			$found = [
				$param->draggablecontrol,
				$param->getControl('Drag'),
				$param->HelperControl,
				$param->MissingControl,
				$param->Control,
				$param->getControl('Nothing'),
				$page->findControl('Drag'),
			];
		});
		$this->assertInstanceOf(TJuiDraggable::class, $found[0]);
		$this->assertSame($found[6], $found[0]);
		$this->assertSame($found[6], $found[1]);
		$this->assertNull($found[2]);
		$this->assertNull($found[3]);
		$this->assertNull($found[4]);
		$this->assertNull($found[5]);
	}

	public function testGetControlWithoutPageServiceReturnsNull(): void
	{
		$app = Prado::getApplication();
		$saved = $app->getService();
		$app->setService(null);
		try {
			$param = new TJuiEventParameter(null, (object) ['draggable' => 'Drag']);
			$this->assertNull($param->getControl('Drag'));
			$this->assertNull($param->DraggableControl);
		} finally {
			$app->setService($saved);
		}
	}

	/**
	 * A property name without the `Control` suffix reads the component property.
	 */
	public function testPropertyWithoutControlSuffixReadsTheComponentProperty(): void
	{
		$data = (object) ['draggable' => 'Drag'];
		$param = new TJuiEventParameter(null, $data);
		$this->assertSame($data, $param->CallbackParameter);
	}
}
