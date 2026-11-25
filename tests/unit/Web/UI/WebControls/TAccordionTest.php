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
}
