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

	// ================================================================================
	// ReadOnly
	// ================================================================================

	// ================================================================================
	// Rating
	// ================================================================================

	// ================================================================================
	// Caption
	// ================================================================================

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

}
