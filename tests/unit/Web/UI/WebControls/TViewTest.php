<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidOperationException;
use Prado\TEventParameter;
use Prado\Web\UI\TControl;
use Prado\Web\UI\WebControls\TMultiView;
use Prado\Web\UI\WebControls\TView;

class TViewTest extends TestCase
{
	public function testActiveDefaultsToFalse()
	{
		$view = new TView();
		$this->assertFalse($view->getActive());
		$this->assertFalse($view->getVisible());
	}

	public function testSetActiveSetsTheControlVisibility()
	{
		$view = new TView();
		$view->setActive('true');
		$this->assertTrue($view->getActive());
		$this->assertTrue($view->getVisible());

		$view->setActive(false);
		$this->assertFalse($view->getActive());
		$this->assertFalse($view->getVisible());
	}

	public function testVisibleFollowsTheParent()
	{
		$parent = new TControl();
		$view = new TView();
		$parent->getControls()->add($view);
		$this->assertFalse($view->getVisible());

		$view->setActive(true);
		$this->assertTrue($view->getVisible());
		$this->assertTrue($view->getVisible(false));

		$parent->setVisible(false);
		$this->assertFalse($view->getVisible());

		$view->setActive(false);
		$this->assertFalse($view->getVisible(false));
	}

	public function testVisibleWithoutParentsIsTheOwnActiveState()
	{
		$parent = new TControl();
		$view = new TView();
		$parent->getControls()->add($view);
		$view->setActive(true);
		$parent->setVisible(false);

		$this->assertTrue($view->getVisible(false));
		$this->assertFalse($view->getVisible());
	}

	public function testSetVisibleThrows()
	{
		$view = new TView();
		$this->expectException(TInvalidOperationException::class);
		$view->setVisible(true);
	}

	public function testActivateAndDeactivateEvents()
	{
		$view = new TView();
		$log = [];
		$view->attachEventHandler('OnActivate', function ($sender, $param) use (&$log) {
			$log[] = ['activate', $sender, $param];
		});
		$view->attachEventHandler('OnDeactivate', function ($sender, $param) use (&$log) {
			$log[] = ['deactivate', $sender, $param];
		});
		$param = new TEventParameter();
		$view->onActivate($param);
		$view->onDeactivate(null);
		$this->assertSame([['activate', $view, $param], ['deactivate', $view, null]], $log);
	}

	public function testInsideAMultiView()
	{
		$multiView = new TMultiView();
		$view = new TView();
		$multiView->getViews()->add($view);
		$multiView->setActiveViewIndex(0);
		$this->assertTrue($view->getVisible());
	}
}
