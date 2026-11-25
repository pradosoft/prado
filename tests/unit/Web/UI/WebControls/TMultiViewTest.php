<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Collections\TMap;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\TEventParameter;
use Prado\Web\UI\TCommandEventParameter;
use Prado\Web\UI\TControl;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TLiteral;
use Prado\Web\UI\WebControls\TMultiView;
use Prado\Web\UI\WebControls\TView;
use Prado\Web\UI\WebControls\TViewCollection;

class TMultiViewTest extends TestCase
{
	use TWebControlRenderTrait;

	/** @var array<string> event log written by the handlers */
	private array $events = [];

	/**
	 * Creates a multiview holding views with the given IDs, each rendering its ID.
	 */
	private function createMultiView(array $ids = ['v0', 'v1', 'v2']): TMultiView
	{
		$multiView = new TMultiView();
		foreach ($ids as $id) {
			$view = new TView();
			$view->setID($id);
			$text = new TLiteral();
			$text->setText("[$id]");
			$view->getControls()->add($text);
			$multiView->getViews()->add($view);
		}
		return $multiView;
	}

	/**
	 * Logs OnActivate, OnDeactivate, and OnActiveViewChanged of a multiview and its views.
	 */
	private function recordEvents(TMultiView $multiView): void
	{
		$multiView->attachEventHandler('OnActiveViewChanged', function ($sender, $param) {
			$this->events[] = 'changed';
		});
		foreach ($multiView->getViews() as $view) {
			$view->attachEventHandler('OnActivate', function ($sender, $param) {
				$this->events[] = 'activate:' . $sender->getID();
			});
			$view->attachEventHandler('OnDeactivate', function ($sender, $param) {
				$this->events[] = 'deactivate:' . $sender->getID();
			});
		}
	}

	private function setStage(TControl $control, int $stage): void
	{
		PradoUnit::invoke($control, 'setControlStage', $stage);
	}

	private function command(string $name, $parameter = null): TCommandEventParameter
	{
		return new TCommandEventParameter($name, $parameter);
	}

	public function testAddParsedObject()
	{
		$multiView = new TMultiView();
		$view = new TView();
		$multiView->addParsedObject($view);
		$multiView->addParsedObject("\n  ");
		$this->assertSame(1, $multiView->getViews()->getCount());
		$this->assertSame($view, $multiView->getViews()->itemAt(0));

		$this->expectException(TConfigurationException::class);
		$multiView->addParsedObject(new TLiteral());
	}

	public function testViewsAreATViewCollection()
	{
		$multiView = new TMultiView();
		$this->assertInstanceOf(TViewCollection::class, $multiView->getViews());
		$this->assertSame($multiView->getControls(), $multiView->getViews());

		$this->expectException(TInvalidDataTypeException::class);
		$multiView->getViews()->add(new TLiteral());
	}

	public function testViewCollectionRejectsAString()
	{
		$multiView = new TMultiView();
		$this->expectException(TInvalidDataTypeException::class);
		$multiView->getViews()->add('text');
	}

	public function testActiveViewIndexDefaultsToNone()
	{
		$multiView = $this->createMultiView();
		$this->assertSame(-1, $multiView->getActiveViewIndex());
		$this->assertNull($multiView->getActiveView());
		$this->assertSame('', $this->render($multiView));
	}

	public function testSetActiveViewIndexActivatesTheView()
	{
		$multiView = $this->createMultiView();
		$multiView->setActiveViewIndex('1');
		$this->assertSame(1, $multiView->getActiveViewIndex());
		$views = $multiView->getViews();
		$this->assertFalse($views->itemAt(0)->getActive());
		$this->assertTrue($views->itemAt(1)->getActive());
		$this->assertFalse($views->itemAt(2)->getActive());
		$this->assertSame($views->itemAt(1), $multiView->getActiveView());
		$this->assertSame('[v1]', $this->render($multiView));

		$multiView->setActiveViewIndex(2);
		$this->assertFalse($views->itemAt(1)->getActive());
		$this->assertTrue($views->itemAt(2)->getActive());
		$this->assertSame('[v2]', $this->render($multiView));
	}

	public function testNegativeIndexSelectsNoView()
	{
		$multiView = $this->createMultiView();
		$multiView->setActiveViewIndex(1);
		$multiView->setActiveViewIndex(-5);
		$this->assertSame(-1, $multiView->getActiveViewIndex());
		$this->assertNull($multiView->getActiveView());
		// the views keep their flags; only the index says which one renders
		$this->assertTrue($multiView->getViews()->itemAt(1)->getActive());
		$this->assertSame('', $this->render($multiView));
	}

	public function testIndexPastTheViewsThrows()
	{
		$multiView = $this->createMultiView();
		$this->expectException(TInvalidDataValueException::class);
		$multiView->setActiveViewIndex(3);
	}

	public function testIndexWithoutViewsAfterChildInitThrows()
	{
		$multiView = new TMultiView();
		$this->setStage($multiView, TControl::CS_CHILD_INITIALIZED);
		$this->expectException(TInvalidDataValueException::class);
		$multiView->setActiveViewIndex(0);
	}

	public function testNegativeIndexWithoutViewsAfterChildInit()
	{
		$multiView = new TMultiView();
		$this->setStage($multiView, TControl::CS_CHILD_INITIALIZED);
		$multiView->setActiveViewIndex(-2);
		$this->assertSame(-1, $multiView->getActiveViewIndex());
		$this->assertSame(-1, PradoUnit::getProp($multiView, '_cachedActiveViewIndex'));
	}

	public function testIndexWithoutViewsIsCachedUntilInit()
	{
		$multiView = new TMultiView();
		$multiView->setActiveViewIndex(1);
		$this->assertSame(1, $multiView->getActiveViewIndex());
		$this->assertSame(1, PradoUnit::getProp($multiView, '_cachedActiveViewIndex'));

		foreach (['a', 'b'] as $id) {
			$view = new TView();
			$view->setID($id);
			$multiView->getViews()->add($view);
		}
		$multiView->onInit(new TEventParameter());
		$this->assertSame(-1, PradoUnit::getProp($multiView, '_cachedActiveViewIndex'));
		$this->assertSame(1, $multiView->getActiveViewIndex());
		$this->assertTrue($multiView->getViews()->itemAt(1)->getActive());
	}

	public function testCachedNegativeIndexIsNotAppliedAtInit()
	{
		$multiView = new TMultiView();
		$multiView->setActiveViewIndex(-1);
		$this->assertSame(-1, PradoUnit::getProp($multiView, '_cachedActiveViewIndex'));
		$multiView->getViews()->add(new TView());
		$multiView->onInit(new TEventParameter());
		$this->assertSame(-1, $multiView->getActiveViewIndex());
		$this->assertFalse($multiView->getViews()->itemAt(0)->getActive());
	}

	public function testGetActiveViewThrowsForAStaleIndex()
	{
		$multiView = $this->createMultiView();
		$multiView->setActiveViewIndex(2);
		$multiView->getViews()->removeAt(2);
		$this->expectException(TInvalidDataValueException::class);
		$multiView->getActiveView();
	}

	public function testGetActiveViewActivatesAnInactiveIndexedView()
	{
		$multiView = $this->createMultiView();
		$this->recordEvents($multiView);
		$multiView->setActiveViewIndex(1);
		$this->events = [];
		$view = $multiView->getViews()->itemAt(1);
		$view->setActive(false);

		$this->assertSame($view, $multiView->getActiveView());
		$this->assertTrue($view->getActive());
		// the activation from getActiveView() raises no event
		$this->assertSame([], $this->events);
	}

	public function testSetActiveView()
	{
		$multiView = $this->createMultiView();
		$view = $multiView->getViews()->itemAt(2);
		$multiView->setActiveView($view);
		$this->assertSame(2, $multiView->getActiveViewIndex());
		$this->assertTrue($view->getActive());

		$this->expectException(TInvalidOperationException::class);
		$multiView->setActiveView(new TView());
	}

	public function testActivationRaisesEventsWithoutAPage()
	{
		// stage CS_CONSTRUCTED and no page: the switch raises no event
		$multiView = $this->createMultiView();
		$this->recordEvents($multiView);
		$multiView->setActiveViewIndex(0);
		$this->assertSame([], $this->events);

		$this->setStage($multiView, TControl::CS_STATE_LOADED);
		$multiView->setActiveViewIndex(1);
		$this->assertSame(['deactivate:v0', 'activate:v1', 'changed'], $this->events);
	}

	public function testActivationRaisesEventsOnAGetPage()
	{
		$page = new TPage();
		$multiView = $this->createMultiView();
		$page->getControls()->add($multiView);
		$this->recordEvents($multiView);
		$multiView->setActiveViewIndex(2);
		$this->assertSame(['activate:v2', 'changed'], $this->events);
	}

	public function testActivationOnAPostBackWaitsForStateLoaded()
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_postData', new TMap());
		$multiView = $this->createMultiView();
		$page->getControls()->add($multiView);
		$this->recordEvents($multiView);
		$multiView->setActiveViewIndex(0);
		$this->assertSame([], $this->events);

		$this->setStage($multiView, TControl::CS_STATE_LOADED);
		$multiView->setActiveViewIndex(1);
		$this->assertSame(['deactivate:v0', 'activate:v1', 'changed'], $this->events);
	}

	public function testActivatingTheActiveViewDoesNothing()
	{
		$multiView = $this->createMultiView();
		$this->setStage($multiView, TControl::CS_STATE_LOADED);
		$multiView->setActiveViewIndex(1);
		$this->recordEvents($multiView);
		$multiView->setActiveViewIndex(1);
		$this->assertSame([], $this->events);
	}

	public function testActivateViewWithoutTheChangedEvent()
	{
		$multiView = $this->createMultiView();
		$this->setStage($multiView, TControl::CS_STATE_LOADED);
		$this->recordEvents($multiView);
		PradoUnit::invoke($multiView, 'activateView', $multiView->getViews()->itemAt(1), false);
		$this->assertTrue($multiView->getViews()->itemAt(1)->getActive());
		$this->assertSame([], $this->events);
	}

	public function testOnInitWithoutCachedIndex()
	{
		$multiView = $this->createMultiView();
		$multiView->onInit(new TEventParameter());
		$this->assertSame(-1, $multiView->getActiveViewIndex());
	}

	public function testBubbleEventIgnoresOtherParameters()
	{
		$multiView = $this->createMultiView();
		$this->assertFalse($multiView->bubbleEvent($this, new TEventParameter()));
		$this->assertFalse($multiView->bubbleEvent($this, $this->command('Other')));
		$this->assertSame(-1, $multiView->getActiveViewIndex());
	}

	public function testIgnoreBubbleEvents()
	{
		$multiView = $this->createMultiView();
		$multiView->ignoreBubbleEvents();
		$this->assertFalse($multiView->bubbleEvent($this, $this->command(TMultiView::CMD_NEXTVIEW)));
		$this->assertSame(-1, $multiView->getActiveViewIndex());
	}

	public function testNextViewCommand()
	{
		$multiView = $this->createMultiView();
		$this->assertTrue($multiView->bubbleEvent($this, $this->command(TMultiView::CMD_NEXTVIEW)));
		$this->assertSame(0, $multiView->getActiveViewIndex());
		$multiView->bubbleEvent($this, $this->command(TMultiView::CMD_NEXTVIEW));
		$multiView->bubbleEvent($this, $this->command(TMultiView::CMD_NEXTVIEW));
		$this->assertSame(2, $multiView->getActiveViewIndex());
		// next from the last view selects no view
		$this->assertTrue($multiView->bubbleEvent($this, $this->command(TMultiView::CMD_NEXTVIEW)));
		$this->assertSame(-1, $multiView->getActiveViewIndex());
	}

	public function testPreviousViewCommand()
	{
		$multiView = $this->createMultiView();
		$multiView->setActiveViewIndex(1);
		$this->assertTrue($multiView->bubbleEvent($this, $this->command(TMultiView::CMD_PREVIOUSVIEW)));
		$this->assertSame(0, $multiView->getActiveViewIndex());
		$this->assertTrue($multiView->bubbleEvent($this, $this->command(TMultiView::CMD_PREVIOUSVIEW)));
		$this->assertSame(-1, $multiView->getActiveViewIndex());
		// previous with no view selected stays on none
		$this->assertTrue($multiView->bubbleEvent($this, $this->command(TMultiView::CMD_PREVIOUSVIEW)));
		$this->assertSame(-1, $multiView->getActiveViewIndex());
	}

	public function testSwitchViewIdCommand()
	{
		$page = new TPage();
		$multiView = $this->createMultiView();
		$multiView->setID('mv');
		$page->getControls()->add($multiView);
		$this->assertTrue($multiView->bubbleEvent($this, $this->command(TMultiView::CMD_SWITCHVIEWID, 'v2')));
		$this->assertSame(2, $multiView->getActiveViewIndex());
	}

	public function testSwitchViewIdCommandWithAnUnknownIdThrows()
	{
		$page = new TPage();
		$multiView = $this->createMultiView();
		$page->getControls()->add($multiView);
		$this->expectException(TInvalidDataValueException::class);
		$multiView->bubbleEvent($this, $this->command(TMultiView::CMD_SWITCHVIEWID, 'missing'));
	}

	public function testSwitchViewIdCommandWithANestedControlThrows()
	{
		$page = new TPage();
		$multiView = $this->createMultiView();
		$page->getControls()->add($multiView);
		$inner = new TLiteral();
		$inner->setID('inner');
		$multiView->getViews()->itemAt(0)->getControls()->add($inner);
		$this->assertSame($inner, $multiView->findControl('inner'));
		$this->expectException(TInvalidDataValueException::class);
		$multiView->bubbleEvent($this, $this->command(TMultiView::CMD_SWITCHVIEWID, 'inner'));
	}

	public function testSwitchViewIndexCommand()
	{
		$multiView = $this->createMultiView();
		$this->assertTrue($multiView->bubbleEvent($this, $this->command(TMultiView::CMD_SWITCHVIEWINDEX, '2')));
		$this->assertSame(2, $multiView->getActiveViewIndex());
	}

	public function testLoadStateActivatesTheIndexedView()
	{
		$multiView = $this->createMultiView();
		PradoUnit::invoke($multiView, 'setControlState', 'ActiveViewIndex', 1, -1);
		$this->assertFalse($multiView->getViews()->itemAt(1)->getActive());
		$multiView->loadState();
		$this->assertTrue($multiView->getViews()->itemAt(1)->getActive());
	}
}
