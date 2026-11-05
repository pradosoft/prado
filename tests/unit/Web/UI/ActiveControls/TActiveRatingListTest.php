<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use PHPUnit\Framework\TestCase;
use Prado\IO\TTextWriter;
use Prado\Test\Unit\Harness\Web\UI\TTestCallbackPage;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\ActiveControls\IActiveControl;
use Prado\Web\UI\ActiveControls\ICallbackEventHandler;
use Prado\Web\UI\ActiveControls\TActiveListControlAdapter;
use Prado\Web\UI\ActiveControls\TActiveRatingList;
use Prado\Web\UI\ActiveControls\TBaseActiveCallbackControl;
use Prado\Web\UI\ActiveControls\TCallbackClientSide;
use Prado\Web\UI\ActiveControls\TCallbackEventParameter;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TControl;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\WebControls\TLabel;
use Prado\Web\UI\WebControls\TListItem;
use Prado\Web\UI\WebControls\TRatingList;

/**
 * Client script manager that records script registrations without publishing assets.
 */
class TActiveRatingListTestClientScript extends TClientScriptManager
{
	public array $callbackControls = [];

	public array $postBackControls = [];

	public array $pradoScripts = [];

	public function registerCallbackControl($class, $options)
	{
		$this->callbackControls[] = [$class, $options];
	}

	public function registerPostBackControl($class, $options)
	{
		$this->postBackControls[] = [$class, $options];
	}

	public function registerPradoScript($name)
	{
		$this->pradoScripts[] = $name;
	}
}

class TActiveRatingListTest extends TestCase
{
	/**
	 * Creates a five star rating list and a caption label on a page at the loaded control stage.
	 * @param bool $callback
	 * @return array [TTestCallbackPage, TActiveRatingList, TActiveRatingListTestClientScript, TLabel]
	 */
	private function createPageList(bool $callback = true): array
	{
		$page = new TTestCallbackPage();
		$page->callback = $callback;
		$cs = new TActiveRatingListTestClientScript($page);
		PradoUnit::setProp($page, '_clientScript', $cs);
		PradoUnit::setProp($page, '_inFormRender', true);
		$caption = new TLabel();
		$caption->setID('Caption');
		$caption->setText('Rate It:');
		$page->getControls()->add($caption);
		$list = new TActiveRatingList();
		$list->setID('List');
		$list->setCaptionID('Caption');
		for ($i = 1; $i <= 5; $i++) {
			$list->getItems()->add(new TListItem((string) $i, (string) $i));
		}
		$page->getControls()->add($list);
		PradoUnit::invoke($list, 'setControlStage', TControl::CS_LOADED);
		return [$page, $list, $cs, $caption];
	}

	private function evaluate(string $code): array
	{
		return ['Prado.Element.evaluateScript' => [$code, null]];
	}

	private function renderControl(TControl $control): string
	{
		$textWriter = new TTextWriter();
		$control->renderControl(new THtmlWriter($textWriter));
		return $textWriter->flush();
	}

	// ================================================================================
	// Basics
	// ================================================================================

	public function testExtendsTRatingList()
	{
		$this->assertInstanceOf(TRatingList::class, new TActiveRatingList());
	}

	public function testImplementsActiveControlInterfaces()
	{
		$list = new TActiveRatingList();
		$this->assertInstanceOf(IActiveControl::class, $list);
		$this->assertInstanceOf(ICallbackEventHandler::class, $list);
	}

	public function testConstructorSetsAdapterAndAutoPostBack()
	{
		$list = new TActiveRatingList();
		$this->assertInstanceOf(TActiveListControlAdapter::class, $list->getAdapter());
		$this->assertTrue($list->getAutoPostBack());
	}

	public function testGetActiveControl()
	{
		$list = new TActiveRatingList();
		$this->assertInstanceOf(TBaseActiveCallbackControl::class, $list->getActiveControl());
		$this->assertSame($list->getAdapter()->getBaseActiveControl(), $list->getActiveControl());
	}

	public function testGetClientSide()
	{
		$list = new TActiveRatingList();
		$this->assertInstanceOf(TCallbackClientSide::class, $list->getClientSide());
		$this->assertSame($list->getActiveControl()->getClientSide(), $list->getClientSide());
	}

	public function testClientClassName()
	{
		$this->assertSame('Prado.WebUI.TActiveRatingList', PradoUnit::invoke(new TActiveRatingList(), 'getClientClassName'));
	}

	public function testRaiseCallbackEventRaisesOnCallback()
	{
		$list = new TActiveRatingList();
		$received = null;
		$list->attachEventHandler('OnCallback', function ($sender, $param) use (&$received, $list) {
			$this->assertSame($list, $sender);
			$received = $param;
		});
		$param = new TCallbackEventParameter(null, 'value');
		$list->raiseCallbackEvent($param);
		$this->assertSame($param, $received);
	}

	// ================================================================================
	// ReadOnly
	// ================================================================================

	public function testSetReadOnlyWithoutPage()
	{
		$list = new TActiveRatingList();
		$list->setReadOnly(true);
		$this->assertTrue($list->getReadOnly());
		$this->assertFalse($list->getAllowInput());
	}

	public function testSetReadOnlyUpdatesClient()
	{
		[$page, $list] = $this->createPageList();
		$list->setReadOnly(true);
		$this->assertTrue($list->getReadOnly());
		$this->assertSame([$this->evaluate("Prado.Registry['List'].setReadOnly(true)")], $page->getClientFunctions());
		$list->setReadOnly(false);
		$this->assertSame($this->evaluate("Prado.Registry['List'].setReadOnly(false)"), $page->getClientFunctions()[1]);
	}

	public function testSetReadOnlyUnchangedSendsNothing()
	{
		[$page, $list] = $this->createPageList();
		$list->setReadOnly(false);
		$this->assertSame([], $page->getClientFunctions());
	}

	public function testSetReadOnlyWithoutCallbackSendsNothing()
	{
		[$page, $list] = $this->createPageList(false);
		$list->setReadOnly(true);
		$this->assertTrue($list->getReadOnly());
		$this->assertSame([], $page->getClientFunctions());
	}

	// ================================================================================
	// Rating
	// ================================================================================

	public function testSetRatingWithoutPage()
	{
		$list = new TActiveRatingList();
		$list->setRating(2.5);
		$this->assertSame(2.5, $list->getRating());
	}

	public function testSetRatingUpdatesClient()
	{
		[$page, $list] = $this->createPageList();
		$list->setRating(3.0);
		$this->assertSame(3.0, $list->getRating());
		$this->assertSame(2, $list->getSelectedIndex());
		$this->assertSame([
			['Prado.Element.select' => ['List', 'checkClear', null, 5]],
			['Prado.Element.select' => ['List', 'checkIndex', 2, 5]],
			$this->evaluate("Prado.Registry['List'].setRating(3)"),
		], $page->getClientFunctions());
	}

	public function testSetRatingHalfValueUpdatesClient()
	{
		[$page, $list] = $this->createPageList();
		$list->setRating(3.5);
		$functions = $page->getClientFunctions();
		$this->assertSame($this->evaluate("Prado.Registry['List'].setRating(3.5)"), end($functions));
	}

	public function testSetRatingUnchangedSendsNothing()
	{
		[$page, $list] = $this->createPageList();
		$list->setRating(4.0);
		$count = count($page->getClientFunctions());
		$list->setRating(4.0);
		$this->assertCount($count, $page->getClientFunctions());
	}

	public function testSetRatingWithoutCallbackSendsNothing()
	{
		[$page, $list] = $this->createPageList(false);
		$list->setRating(2.0);
		$this->assertSame(1, $list->getSelectedIndex());
		$this->assertSame([], $page->getClientFunctions());
	}

	public function testSetSelectedIndexSetsRating()
	{
		[, $list] = $this->createPageList(false);
		$list->setSelectedIndex(3);
		$this->assertSame(4.0, $list->getRating());
		$this->assertSame(3, $list->getSelectedIndex());
	}

	// ================================================================================
	// Caption
	// ================================================================================

	public function testSetCaptionWithoutCallback()
	{
		[$page, $list, , $caption] = $this->createPageList(false);
		$this->assertSame('Rate It:', $list->getCaption());
		$list->setCaption('Your rating:');
		$this->assertSame('Your rating:', $list->getCaption());
		$this->assertSame('Your rating:', $caption->getText());
		$this->assertSame([], $page->getClientFunctions());
	}

	public function testSetCaptionUpdatesClientWithEncodedString()
	{
		[$page, $list, , $caption] = $this->createPageList();
		$list->setCaption('Great');
		$this->assertSame('Great', $caption->getText());
		$list->setCaption("x');alert(1);('</script>");
		$this->assertSame([
			$this->evaluate('Prado.Registry[\'List\'].setCaption("Great")'),
			$this->evaluate('Prado.Registry[\'List\'].setCaption("x\\u0027);alert(1);(\\u0027\\u003C\\/script\\u003E")'),
		], $page->getClientFunctions());
	}

	public function testSetCaptionUnchangedSendsNothing()
	{
		[$page, $list] = $this->createPageList();
		$list->setCaption('Rate It:');
		$this->assertSame([], $page->getClientFunctions());
	}

	// ================================================================================
	// Rendering
	// ================================================================================

	public function testRenderRegistersCallbackControl()
	{
		[, $list, $cs] = $this->createPageList(false);
		$list->setRating(2.0);
		$html = $this->renderControl($list);
		$this->assertStringContainsString('id="List"', $html);
		$this->assertStringContainsString('role="radiogroup"', $html);
		$this->assertSame(5, substr_count($html, 'type="radio"'));
		$this->assertCount(1, $cs->callbackControls);
		[$class, $options] = $cs->callbackControls[0];
		$this->assertSame('Prado.WebUI.TActiveRatingList', $class);
		$this->assertSame('List', $options['ID']);
		$this->assertSame('List', $options['ListName']);
		$this->assertSame(5, $options['ItemCount']);
		$this->assertSame(1, $options['SelectedIndex']);
		$this->assertSame(2.0, $options['Rating']);
		$this->assertSame('Caption', $options['CaptionID']);
		$this->assertTrue($options['AutoPostBack']);
		$this->assertContains('ajax', $cs->pradoScripts);
	}

	public function testRenderReadOnly()
	{
		[, $list, $cs] = $this->createPageList(false);
		$list->setReadOnly(true);
		$html = $this->renderControl($list);
		$this->assertStringContainsString('aria-readonly="true"', $html);
		$this->assertTrue($cs->callbackControls[0][1]['ReadOnly']);
	}
}
