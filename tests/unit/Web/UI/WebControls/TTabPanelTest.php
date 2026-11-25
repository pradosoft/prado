<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Collections\TMap;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Test\Unit\Harness\Traits\TAssetManagerMockTrait;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\TEventParameter;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TLiteral;
use Prado\Web\UI\WebControls\TStyle;
use Prado\Web\UI\WebControls\TTabPanel;
use Prado\Web\UI\WebControls\TTabView;
use Prado\Web\UI\WebControls\TTabViewCollection;

class TTabPanelTest extends TestCase
{
	use TAssetManagerMockTrait;
	use TWebControlRenderTrait;

	protected function tearDown(): void
	{
		$this->restoreAssetManager();
	}

	/**
	 * Creates a tab panel on a page with a view per ID.
	 */
	private function createPanel(array $ids = ['a', 'b', 'c']): TTabPanel
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_clientScript', new TViewSwitcherTestClientScript($page));
		$panel = new TTabPanel();
		$panel->setID('tp');
		foreach ($ids as $id) {
			$view = new TTabView();
			$view->setID($id);
			$view->setCaption(strtoupper($id));
			$panel->addParsedObject($view);
		}
		$page->getControls()->add($panel);
		return $panel;
	}

	private function clientScript(TTabPanel $panel): TViewSwitcherTestClientScript
	{
		return $panel->getPage()->getClientScript();
	}

	private function activeFlags(TTabPanel $panel): array
	{
		return array_map(fn ($view) => $view->getActive(), $panel->getViews()->toArray());
	}

	public function testDefaults()
	{
		$panel = new TTabPanel();
		$this->assertSame('div', PradoUnit::invoke($panel, 'getTagName'));
		$this->assertSame(0, $panel->getActiveViewIndex());
		$this->assertSame('', $panel->getActiveViewID());
		$this->assertFalse($panel->getAutoSwitch());
		$this->assertSame('default', $panel->getCssUrl());
		$this->assertSame('tab-panel', $panel->getCssClass());
		$this->assertSame('tab-view', $panel->getViewCssClass());
		$this->assertSame('tab-normal', $panel->getTabCssClass());
		$this->assertSame('tab-active', $panel->getActiveTabCssClass());
		$this->assertFalse($panel->getDataChanged());
		$this->assertSame('Prado.WebUI.TTabPanel', PradoUnit::invoke($panel, 'getClientClassName'));
		foreach (['getViewStyle', 'getTabStyle', 'getActiveTabStyle'] as $getter) {
			$style = $panel->$getter();
			$this->assertSame(TStyle::class, $style::class, $getter);
			$this->assertSame($style, $panel->$getter(), $getter);
		}
	}

	public function testSetters()
	{
		$panel = new TTabPanel();
		$panel->setActiveViewIndex('2');
		$this->assertSame(2, $panel->getActiveViewIndex());
		$panel->setActiveViewID('b');
		$this->assertSame('b', $panel->getActiveViewID());
		$panel->setAutoSwitch('true');
		$this->assertTrue($panel->getAutoSwitch());
		$panel->setCssUrl('/my.css');
		$this->assertSame('/my.css', $panel->getCssUrl());
		$panel->setCssClass('mine');
		$this->assertSame('mine', $panel->getCssClass());
		$panel->setViewCssClass('v');
		$this->assertSame('v', $panel->getViewCssClass());
		$panel->setTabCssClass('t');
		$this->assertSame('t', $panel->getTabCssClass());
		$panel->setActiveTabCssClass('at');
		$this->assertSame('at', $panel->getActiveTabCssClass());
	}

	public function testEmptyCssUrlRegistersNoStyleSheet()
	{
		$panel = $this->createPanel();
		// a template sets attributes with tracking off, so a value equal to the default is dropped
		$panel->trackViewState(false);
		$panel->setCssUrl('');
		$this->assertSame('', $panel->getCssUrl());
		$panel->onPreRender(new TEventParameter());
		$this->assertSame([], $this->clientScript($panel)->styleSheets);

		$panel->setCssUrl('default');
		$this->assertSame('default', $panel->getCssUrl());
	}

	public function testOnPreRender()
	{
		$this->mockAssetManager();
		$panel = $this->createPanel();
		$panel->onPreRender(new TEventParameter());
		$this->assertSame(['/assets/tabpanel.css' => '/assets/tabpanel.css'], $this->clientScript($panel)->styleSheets);
		$this->assertSame([true, false, false], $this->activeFlags($panel));
		$registered = PradoUnit::getProp($panel->getPage(), '_controlsRegisteredForPostData');
		$this->assertSame(['tp' => true, 'tp_1' => true], $registered);

		$panel = $this->createPanel();
		$panel->setCssUrl('/my.css');
		$panel->onPreRender(new TEventParameter());
		$this->assertSame(['/my.css' => '/my.css'], $this->clientScript($panel)->styleSheets);
	}

	public function testActiveViewByTheActiveFlagOfASingleView()
	{
		$panel = $this->createPanel(['a']);
		$panel->setActiveViewIndex(-1);
		$panel->getViews()->itemAt(0)->setActive(true);
		$this->assertSame($panel->getViews()->itemAt(0), $panel->getActiveView());
		$this->assertSame(0, $panel->getActiveViewIndex());

		$panel = $this->createPanel([]);
		$panel->setActiveViewIndex(-1);
		$this->assertNull($panel->getActiveView());
	}

	public function testFindIndexByIdOfTheFirstViewOrWithoutViews()
	{
		$this->assertSame(0, $this->createPanel(['a'])->getViews()->findIndexByID('a'));
		$this->assertSame(-1, $this->createPanel([])->getViews()->findIndexByID('a'));
	}

	public function testAPublishedStyleSheetWithoutAUrlIsNotRegistered()
	{
		$manager = $this->getMockBuilder(\Prado\Web\TAssetManager::class)->onlyMethods(['publishFilePath'])->getMock();
		$manager->method('publishFilePath')->willReturn('');
		$this->mockAssetManager();
		\Prado\Prado::getApplication()->setAssetManager($manager);
		$panel = $this->createPanel();
		$panel->onPreRender(new TEventParameter());
		$this->assertSame([], $this->clientScript($panel)->styleSheets);
	}

	public function testAddParsedObject()
	{
		$panel = new TTabPanel();
		$view = new TTabView();
		$panel->addParsedObject($view);
		$panel->addParsedObject('text');
		$panel->addParsedObject(new TLiteral());
		$this->assertSame([$view], $panel->getViews()->toArray());
	}

	public function testViewCollection()
	{
		$panel = $this->createPanel();
		$views = $panel->getViews();
		$this->assertInstanceOf(TTabViewCollection::class, $views);
		$this->assertSame($panel->getControls(), $views);
		$this->assertSame(2, $views->findIndexByID('c'));
		$this->assertSame(-1, $views->findIndexByID('missing'));

		$this->expectException(TInvalidDataTypeException::class);
		$this->expectExceptionMessage('TTabPanel can only accept TTabView as child.');
		$views->add(new TLiteral());
	}

	public function testActiveViewByIndex()
	{
		$panel = $this->createPanel();
		$panel->setActiveViewIndex(1);
		$this->assertSame($panel->getViews()->itemAt(1), $panel->getActiveView());
		$this->assertSame([false, true, false], $this->activeFlags($panel));
		$this->assertSame('b', $panel->getActiveViewID());
	}

	public function testActiveViewIdTakesPrecedence()
	{
		$panel = $this->createPanel();
		$panel->setActiveViewID('c');
		$panel->setActiveViewIndex(1);
		$this->assertSame($panel->getViews()->itemAt(2), $panel->getActiveView());
		$this->assertSame(2, $panel->getActiveViewIndex());
	}

	public function testActiveViewIndexPastTheViewsThrows()
	{
		$panel = $this->createPanel();
		$panel->setActiveViewIndex(3);
		$this->expectException(TInvalidDataValueException::class);
		$panel->getActiveView();
	}

	public function testActiveViewByAnUnknownIdThrows()
	{
		$panel = $this->createPanel();
		$panel->setActiveViewID('missing');
		$this->expectException(TInvalidDataValueException::class);
		$panel->getActiveView();
	}

	public function testActiveViewByTheActiveFlag()
	{
		$panel = $this->createPanel();
		$panel->setActiveViewIndex(-1);
		$this->assertNull($panel->getActiveView());
		$panel->getViews()->itemAt(2)->setActive(true);
		$this->assertSame($panel->getViews()->itemAt(2), $panel->getActiveView());
		$this->assertSame([false, false, true], $this->activeFlags($panel));
		$this->assertSame(2, $panel->getActiveViewIndex());
	}

	public function testSetActiveView()
	{
		$panel = $this->createPanel();
		$panel->setActiveView($panel->getViews()->itemAt(1));
		$this->assertSame([false, true, false], $this->activeFlags($panel));
		$this->assertSame('b', $panel->getActiveViewID());

		$this->expectException(TInvalidOperationException::class);
		$panel->setActiveView(new TTabView());
	}

	public function testLoadPostData()
	{
		$panel = $this->createPanel();
		$key = $panel->getClientID() . '_1';
		$this->assertFalse($panel->loadPostData($key, new TMap()));
		$this->assertFalse($panel->loadPostData($key, new TMap([$key => '0'])));

		$panel->setActiveViewID('a');
		$this->assertTrue($panel->loadPostData($key, new TMap([$key => '1'])));
		$this->assertSame(1, $panel->getActiveViewIndex());
		$this->assertSame('', $panel->getActiveViewID());
		$this->assertTrue($panel->getDataChanged());
		$panel->raisePostDataChangedEvent();
		$this->assertSame($panel->getViews()->itemAt(1), $panel->getActiveView());
	}

	public function testLoadPostDataIgnoresAnIndexOutsideTheViews()
	{
		$panel = $this->createPanel();
		$key = $panel->getClientID() . '_1';
		$this->assertFalse($panel->loadPostData($key, new TMap([$key => '3'])));
		$this->assertFalse($panel->loadPostData($key, new TMap([$key => '-1'])));
		$this->assertFalse($panel->loadPostData($key, new TMap([$key => 'x'])));
		$this->assertSame(0, $panel->getActiveViewIndex());
		$this->assertFalse($panel->getDataChanged());
	}

	public function testRender()
	{
		$panel = $this->createPanel();
		$panel->setCssUrl('');
		$panel->setActiveViewIndex(1);
		$panel->setAutoSwitch(true);
		$panel->setToolTip('Settings');
		$panel->onPreRender(new TEventParameter());
		$output = $this->render($panel);
		$this->assertStringStartsWith('<div id="tp" title="Settings" class="tab-panel">' . "\n" . '<div role="tablist" aria-label="Settings"><div id="a_0', $output);
		$this->assertStringContainsString('<div id="a_0" role="tab" aria-selected="false" aria-controls="a" tabindex="-1" class="tab-normal">A</div>', $output);
		$this->assertStringContainsString('<div id="b_0" role="tab" aria-selected="true" aria-controls="b" tabindex="0" class="tab-active">B</div>', $output);
		$this->assertStringContainsString('<div id="a" class="tab-view" role="tabpanel" aria-labelledby="a_0" tabindex="0" style="display:none;"></div>', $output);
		$this->assertStringContainsString('<div id="b" class="tab-view" role="tabpanel" aria-labelledby="b_0" tabindex="0"></div>', $output);
		$this->assertStringEndsWith('</div>', $output);

		$cs = $this->clientScript($panel);
		$this->assertSame(['tabpanel'], $cs->pradoScripts);
		$this->assertSame(['tp_1' => 1], $cs->hiddenFields);
		$script = $cs->endScripts['prado:tp'];
		$this->assertStringStartsWith('new Prado.WebUI.TTabPanel({', $script);
		// ViewsVis lists each view's visibility; the client activates the view of the hidden field
		$this->assertSame('new Prado.WebUI.TTabPanel({\'ID\':"tp",\'ActiveCssClass\':"tab-active",\'NormalCssClass\':"tab-normal",\'Views\':["a","b","c"],\'ViewsVis\':[true,true,true],\'AutoSwitch\':true});', $script);
	}

	public function testRenderWithoutAToolTip()
	{
		$panel = $this->createPanel(['a']);
		$panel->setCssUrl('');
		$panel->onPreRender(new TEventParameter());
		$this->assertStringStartsWith('<div id="tp" class="tab-panel">' . "\n" . '<div role="tablist"><div id="a_0', $this->render($panel));
	}

	public function testRenderMakesTheFirstViewActiveWhenTheActiveViewIsHidden()
	{
		$panel = $this->createPanel();
		$panel->setCssUrl('');
		$panel->setActiveViewIndex(2);
		$panel->onPreRender(new TEventParameter());
		$panel->getViews()->itemAt(2)->setVisible(false);
		$this->render($panel);
		$this->assertSame(['tp_1' => 0], $this->clientScript($panel)->hiddenFields);
	}

	public function testRenderWithoutViews()
	{
		$panel = $this->createPanel([]);
		$panel->setCssUrl('');
		$panel->onPreRender(new TEventParameter());
		$this->assertSame('<div id="tp" class="tab-panel"></div>', $this->render($panel));
		$this->assertSame(['tp_1' => 0], $this->clientScript($panel)->hiddenFields);
	}

	public function testRenderWithoutAnActiveView()
	{
		$panel = $this->createPanel();
		$panel->setCssUrl('');
		$panel->setActiveViewIndex(-1);
		$panel->onPreRender(new TEventParameter());
		$this->render($panel);
		$this->assertSame(['tp_1' => 0], $this->clientScript($panel)->hiddenFields);
	}
}
