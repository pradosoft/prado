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
use Prado\Web\UI\WebControls\TAccordion;
use Prado\Web\UI\WebControls\TAccordionView;
use Prado\Web\UI\WebControls\TAccordionViewCollection;
use Prado\Web\UI\WebControls\TLiteral;
use Prado\Web\UI\WebControls\TStyle;

class TAccordionTest extends TestCase
{
	use TAssetManagerMockTrait;
	use TWebControlRenderTrait;

	protected function tearDown(): void
	{
		$this->restoreAssetManager();
	}

	/**
	 * Creates an accordion on a page with a view per ID.
	 */
	private function createAccordion(array $ids = ['a', 'b', 'c']): TAccordion
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_clientScript', new TViewSwitcherTestClientScript($page));
		$accordion = new TAccordion();
		$accordion->setID('acc');
		foreach ($ids as $id) {
			$view = new TAccordionView();
			$view->setID($id);
			$view->setCaption(strtoupper($id));
			$accordion->addParsedObject($view);
		}
		$page->getControls()->add($accordion);
		return $accordion;
	}

	private function clientScript(TAccordion $accordion): TViewSwitcherTestClientScript
	{
		return $accordion->getPage()->getClientScript();
	}

	private function activeFlags(TAccordion $accordion): array
	{
		return array_map(fn ($view) => $view->getActive(), $accordion->getViews()->toArray());
	}

	public function testDefaults()
	{
		$accordion = new TAccordion();
		$this->assertSame('div', PradoUnit::invoke($accordion, 'getTagName'));
		$this->assertSame(0, $accordion->getActiveViewIndex());
		$this->assertSame('', $accordion->getActiveViewID());
		$this->assertSame('default', $accordion->getCssUrl());
		$this->assertSame('accordion', $accordion->getCssClass());
		$this->assertSame('accordion-view', $accordion->getViewCssClass());
		$this->assertSame('accordion-header', $accordion->getHeaderCssClass());
		$this->assertSame('accordion-header-active', $accordion->getActiveHeaderCssClass());
		$this->assertSame('1', $accordion->getAnimationDuration());
		$this->assertSame(0, $accordion->getViewHeight());
		$this->assertFalse($accordion->getDataChanged());
		$this->assertSame('Prado.WebUI.TAccordion', PradoUnit::invoke($accordion, 'getClientClassName'));
		foreach (['getViewStyle', 'getHeaderStyle', 'getActiveHeaderStyle'] as $getter) {
			$style = $accordion->$getter();
			$this->assertSame(TStyle::class, $style::class, $getter);
			$this->assertSame($style, $accordion->$getter(), $getter);
		}
	}

	public function testSetters()
	{
		$accordion = new TAccordion();
		$accordion->setActiveViewID('b');
		$accordion->setActiveViewIndex('2');
		$this->assertSame(2, $accordion->getActiveViewIndex());
		// setting the index clears the view ID
		$this->assertSame('', $accordion->getActiveViewID());
		$accordion->setActiveViewID('b');
		$this->assertSame('b', $accordion->getActiveViewID());
		$accordion->setCssUrl('/my.css');
		$this->assertSame('/my.css', $accordion->getCssUrl());
		$accordion->setCssClass('mine');
		$this->assertSame('mine', $accordion->getCssClass());
		$accordion->setViewCssClass('v');
		$this->assertSame('v', $accordion->getViewCssClass());
		$accordion->setHeaderCssClass('h');
		$this->assertSame('h', $accordion->getHeaderCssClass());
		$accordion->setActiveHeaderCssClass('ah');
		$this->assertSame('ah', $accordion->getActiveHeaderCssClass());
		$accordion->setAnimationDuration('0.5');
		$this->assertSame('0.5', $accordion->getAnimationDuration());
		$accordion->setViewHeight('120');
		$this->assertSame(120, $accordion->getViewHeight());
	}

	public function testEmptyCssUrlRegistersNoStyleSheet()
	{
		$accordion = $this->createAccordion();
		// a template sets attributes with tracking off, so a value equal to the default is dropped
		$accordion->trackViewState(false);
		$accordion->setCssUrl('');
		$this->assertSame('', $accordion->getCssUrl());
		$accordion->onPreRender(new TEventParameter());
		$this->assertSame([], $this->clientScript($accordion)->styleSheets);

		$accordion->setCssUrl('default');
		$this->assertSame('default', $accordion->getCssUrl());
	}

	public function testOnPreRenderRegistersTheStyleSheet()
	{
		$this->mockAssetManager();
		$accordion = $this->createAccordion();
		$accordion->onPreRender(new TEventParameter());
		$this->assertSame(['/assets/accordion.css' => '/assets/accordion.css'], $this->clientScript($accordion)->styleSheets);
		$this->assertTrue($accordion->getViews()->itemAt(0)->getActive());

		$accordion = $this->createAccordion();
		$accordion->setCssUrl('/my.css');
		$accordion->onPreRender(new TEventParameter());
		$this->assertSame(['/my.css' => '/my.css'], $this->clientScript($accordion)->styleSheets);
	}

	public function testActiveViewByTheActiveFlagOfASingleView()
	{
		$accordion = $this->createAccordion(['a']);
		$accordion->setActiveViewIndex(-1);
		$accordion->getViews()->itemAt(0)->setActive(true);
		$this->assertSame($accordion->getViews()->itemAt(0), $accordion->getActiveView());
		$this->assertSame(0, $accordion->getActiveViewIndex());

		$accordion = $this->createAccordion([]);
		$accordion->setActiveViewIndex(-1);
		$this->assertNull($accordion->getActiveView());
	}

	public function testFindIndexByIdOfTheFirstViewOrWithoutViews()
	{
		$this->assertSame(0, $this->createAccordion(['a'])->getViews()->findIndexByID('a'));
		$this->assertSame(-1, $this->createAccordion([])->getViews()->findIndexByID('a'));
	}

	public function testAPublishedStyleSheetWithoutAUrlIsNotRegistered()
	{
		$manager = $this->getMockBuilder(\Prado\Web\TAssetManager::class)->onlyMethods(['publishFilePath'])->getMock();
		$manager->method('publishFilePath')->willReturn('');
		$this->mockAssetManager();
		\Prado\Prado::getApplication()->setAssetManager($manager);
		$accordion = $this->createAccordion();
		$accordion->onPreRender(new TEventParameter());
		$this->assertSame([], $this->clientScript($accordion)->styleSheets);
	}

	public function testClientOptionsOfASingleView()
	{
		foreach ([0, 50] as $height) {
			foreach ([true, false] as $visible) {
				$accordion = $this->createAccordion(['a']);
				$accordion->setViewHeight($height);
				$accordion->getViews()->itemAt(0)->setVisible($visible);
				$options = PradoUnit::invoke($accordion, 'getClientOptions');
				$this->assertSame(['a' => $visible ? '1' : '0'], $options['Views']);
				$this->assertSame($height > 0, isset($options['maxHeight']));
			}
		}
		$accordion = $this->createAccordion([]);
		$accordion->setViewHeight(50);
		$options = PradoUnit::invoke($accordion, 'getClientOptions');
		$this->assertSame([], $options['Views']);
		$this->assertSame(50, $options['maxHeight']);
	}

	public function testAddParsedObject()
	{
		$accordion = new TAccordion();
		$view = new TAccordionView();
		$accordion->addParsedObject($view);
		$accordion->addParsedObject('text');
		$accordion->addParsedObject(new TLiteral());
		$this->assertSame([$view], $accordion->getViews()->toArray());
	}

	public function testViewCollection()
	{
		$accordion = $this->createAccordion();
		$views = $accordion->getViews();
		$this->assertInstanceOf(TAccordionViewCollection::class, $views);
		$this->assertSame($accordion->getControls(), $views);
		$this->assertSame(1, $views->findIndexByID('b'));
		$this->assertSame(-1, $views->findIndexByID('missing'));

		$this->expectException(TInvalidDataTypeException::class);
		$this->expectExceptionMessage('TAccordion can only accept TAccordionView as child.');
		$views->add(new TLiteral());
	}

	public function testActiveViewByIndex()
	{
		$accordion = $this->createAccordion();
		$accordion->setActiveViewIndex(1);
		$this->assertSame($accordion->getViews()->itemAt(1), $accordion->getActiveView());
		$this->assertSame([false, true, false], $this->activeFlags($accordion));
		$this->assertSame(1, $accordion->getActiveViewIndex());
		$this->assertSame('b', $accordion->getActiveViewID());
	}

	public function testActiveViewIndexPastTheViewsThrows()
	{
		$accordion = $this->createAccordion();
		$accordion->setActiveViewIndex(3);
		$this->expectException(TInvalidDataValueException::class);
		$accordion->getActiveView();
	}

	public function testActiveViewById()
	{
		$accordion = $this->createAccordion();
		$accordion->setActiveViewID('c');
		$this->assertSame($accordion->getViews()->itemAt(2), $accordion->getActiveView());
		$this->assertSame([false, false, true], $this->activeFlags($accordion));
		$this->assertSame(2, $accordion->getActiveViewIndex());
		$this->assertSame('c', $accordion->getActiveViewID());
	}

	public function testActiveViewByAnUnknownIdThrows()
	{
		$accordion = $this->createAccordion();
		$accordion->setActiveViewID('missing');
		$this->expectException(TInvalidDataValueException::class);
		$accordion->getActiveView();
	}

	public function testActiveViewByTheActiveFlag()
	{
		$accordion = $this->createAccordion();
		$accordion->setActiveViewIndex(-1);
		$this->assertNull($accordion->getActiveView());
		$this->assertSame([false, false, false], $this->activeFlags($accordion));

		$accordion->getViews()->itemAt(1)->setActive(true);
		$accordion->getViews()->itemAt(2)->setActive(true);
		$this->assertSame($accordion->getViews()->itemAt(1), $accordion->getActiveView());
		$this->assertSame([false, true, false], $this->activeFlags($accordion));
		$this->assertSame(1, $accordion->getActiveViewIndex());
	}

	public function testSetActiveView()
	{
		$accordion = $this->createAccordion();
		$accordion->setActiveView($accordion->getViews()->itemAt(2));
		$this->assertSame([false, false, true], $this->activeFlags($accordion));
		$this->assertSame('c', $accordion->getActiveViewID());

		$this->expectException(TInvalidOperationException::class);
		$accordion->setActiveView(new TAccordionView());
	}

	public function testLoadPostData()
	{
		$accordion = $this->createAccordion();
		$key = $accordion->getClientID() . '_1';
		$this->assertFalse($accordion->loadPostData($key, new TMap()));
		$this->assertFalse($accordion->loadPostData($key, new TMap([$key => '0'])));
		$this->assertFalse($accordion->getDataChanged());

		$accordion->setActiveViewID('a');
		$this->assertTrue($accordion->loadPostData($key, new TMap([$key => '2'])));
		$this->assertSame(2, $accordion->getActiveViewIndex());
		$this->assertSame('', $accordion->getActiveViewID());
		$this->assertTrue($accordion->getDataChanged());
		$accordion->raisePostDataChangedEvent();
		$this->assertSame(2, $accordion->getActiveViewIndex());
	}

	public function testLoadPostDataIgnoresAnIndexOutsideTheViews()
	{
		$accordion = $this->createAccordion();
		$key = $accordion->getClientID() . '_1';
		$this->assertFalse($accordion->loadPostData($key, new TMap([$key => '3'])));
		$this->assertFalse($accordion->loadPostData($key, new TMap([$key => '-1'])));
		$this->assertFalse($accordion->loadPostData($key, new TMap([$key => 'x'])));
		$this->assertSame(0, $accordion->getActiveViewIndex());
		$this->assertFalse($accordion->getDataChanged());
	}

	public function testRender()
	{
		$this->mockAssetManager();
		$accordion = $this->createAccordion();
		$accordion->setActiveViewIndex(1);
		$accordion->setViewHeight(200);
		$accordion->setAnimationDuration('0.3');
		$accordion->onPreRender(new TEventParameter());
		$output = $this->render($accordion);
		$this->assertStringStartsWith('<div id="acc" class="accordion">' . "\n", $output);
		$this->assertStringContainsString('<div id="a_0" role="button" aria-expanded="false" aria-controls="a" tabindex="0" class="accordion-header">A</div>', $output);
		$this->assertStringContainsString('<div id="b_0" role="button" aria-expanded="true" aria-controls="b" tabindex="0" class="accordion-header-active">B</div>', $output);
		$this->assertStringContainsString('<div id="a" class="accordion-view" role="region" aria-labelledby="a_0" style="display:none;"></div>', $output);
		$this->assertStringContainsString('<div id="b" class="accordion-view" role="region" aria-labelledby="b_0"></div>', $output);
		$this->assertStringEndsWith('</div>', $output);

		$cs = $this->clientScript($accordion);
		$this->assertSame(['accordion'], $cs->pradoScripts);
		$this->assertSame(['acc_1' => 1], $cs->hiddenFields);
		$script = $cs->endScripts['prado:acc'];
		$this->assertStringStartsWith('new Prado.WebUI.TAccordion({', $script);
		// every visible view is listed with 1; the client opens the view of the hidden field
		$this->assertSame('new Prado.WebUI.TAccordion({\'ID\':"acc",\'ActiveHeaderCssClass\':"accordion-header-active",\'HeaderCssClass\':"accordion-header",\'Duration\':"0.3",\'maxHeight\':200,\'Views\':{\'a\':"1",\'b\':"1",\'c\':"1"}});', $script);
		$registered = PradoUnit::getProp($accordion->getPage(), '_controlsRegisteredForPostData');
		$this->assertSame(['acc' => true, 'acc_1' => true], $registered);
	}

	public function testRenderWithoutAViewHeight()
	{
		$this->mockAssetManager();
		$accordion = $this->createAccordion(['a']);
		$accordion->onPreRender(new TEventParameter());
		$this->render($accordion);
		$script = $this->clientScript($accordion)->endScripts['prado:acc'];
		$this->assertStringNotContainsString('maxHeight', $script);
	}

	public function testRenderMakesTheFirstViewActiveWhenTheActiveViewIsHidden()
	{
		$this->mockAssetManager();
		$accordion = $this->createAccordion();
		$accordion->setActiveViewIndex(2);
		$accordion->onPreRender(new TEventParameter());
		$accordion->getViews()->itemAt(2)->setVisible(false);
		$output = $this->render($accordion);
		$this->assertSame(['acc_1' => 0], $this->clientScript($accordion)->hiddenFields);
		$this->assertStringNotContainsString('id="c', $output);
	}

	public function testRenderWithoutViews()
	{
		$accordion = $this->createAccordion([]);
		$accordion->setCssUrl('');
		$accordion->onPreRender(new TEventParameter());
		$this->assertSame('<div id="acc" class="accordion"></div>', $this->render($accordion));
		$this->assertSame(['acc_1' => 0], $this->clientScript($accordion)->hiddenFields);
	}

	public function testRenderWithoutAnActiveView()
	{
		$accordion = $this->createAccordion();
		$accordion->setCssUrl('');
		$accordion->setActiveViewIndex(-1);
		$accordion->onPreRender(new TEventParameter());
		$this->render($accordion);
		$this->assertSame(['acc_1' => 0], $this->clientScript($accordion)->hiddenFields);
	}

	public function testViewContents()
	{
		$accordion = $this->createAccordion(['a']);
		$view = $accordion->getViews()->itemAt(0);
		$view->setText('Hello');
		$accordion->setCssUrl('');
		$accordion->onPreRender(new TEventParameter());
		$this->assertStringContainsString('aria-labelledby="a_0">Hello</div>', $this->render($accordion));
	}
}
