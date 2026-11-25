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
}
