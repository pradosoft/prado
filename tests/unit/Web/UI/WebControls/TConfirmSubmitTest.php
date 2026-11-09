<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Prado;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\TAssetManager;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TForm;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TCheckBox;
use Prado\Web\UI\WebControls\TConfirmSubmit;
use Prado\Web\UI\WebControls\TDropDownList;
use Prado\Web\UI\WebControls\THtmlElement;
use Prado\Web\UI\WebControls\TPanel;
use Prado\Web\UI\WebControls\TTextBox;

/**
 * Records published paths instead of copying files.
 */
class TConfirmSubmitTestAssetManager extends TAssetManager
{
	public array $published = [];

	public function publishFilePath($path, $checkTimestamp = false)
	{
		$this->published[] = basename($path);
		return '/assets/' . basename($path);
	}
}

/**
 * Client script manager that records the script packages a control registers.
 */
class TConfirmSubmitTestClientScript extends TClientScriptManager
{
	public array $pradoScripts = [];

	public function registerPradoScript($name)
	{
		$this->pradoScripts[] = $name;
	}

	protected function registerPradoScriptInternal($name)
	{
		$this->pradoScripts[] = $name;
	}
}

class TConfirmSubmitTest extends TestCase
{
	use TWebControlRenderTrait;

	private mixed $_assetManager;

	protected function setUp(): void
	{
		$this->_assetManager = PradoUnit::getProp(Prado::getApplication(), '_assetManager');
		Prado::getApplication()->setAssetManager(new TConfirmSubmitTestAssetManager());
	}

	protected function tearDown(): void
	{
		PradoUnit::setProp(Prado::getApplication(), '_assetManager', $this->_assetManager);
	}

	/**
	 * @param ?TForm $form the page form, or null to render the dialog in place
	 * @return array{TPage, TConfirmSubmit} a wrapper "guard" rendering in a form
	 */
	private function newPageWrapper(?TForm $form = null): array
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_clientScript', new TConfirmSubmitTestClientScript($page));
		PradoUnit::setProp($page, '_inFormRender', true);
		if ($form !== null) {
			$form->setID('form1');
			$page->getControls()->add($form);
			PradoUnit::setProp($page, '_form', $form);
		}
		$wrapper = new TConfirmSubmit();
		$wrapper->setID('guard');
		$page->getControls()->add($wrapper);
		return [$page, $wrapper];
	}

	private function endScripts(TPage $page): string
	{
		return implode("\n", PradoUnit::getProp($page->getClientScript(), '_endScripts'));
	}

	public function testExtendsHtmlElementWithSpanDefault()
	{
		$wrapper = new TConfirmSubmit();
		$this->assertInstanceOf(THtmlElement::class, $wrapper);
		$this->assertSame('span', $wrapper->getTagName());
		$wrapper->setTagName('div');
		$this->assertSame('div', $wrapper->getTagName());
	}

	public function testWithoutConfirmTextRendersOnlyTheWrapper()
	{
		[$page, $wrapper] = $this->newPageWrapper();
		$wrapper->getControls()->add('Body');
		$this->assertFalse($wrapper->getHasConfirmation());
		$this->assertSame('<span id="guard">Body</span>', $this->render($wrapper));
		$this->assertSame('', $this->endScripts($page));
		$this->assertSame([], $page->getClientScript()->pradoScripts);
	}

	public function testDisabledAsksNoConfirmation()
	{
		$wrapper = new TConfirmSubmit();
		$wrapper->setConfirmText('Sure?');
		$this->assertTrue($wrapper->getHasConfirmation());
		$wrapper->setEnabled(false);
		$this->assertFalse($wrapper->getHasConfirmation());
	}

	public function testRendersWrapperThenDialogInPlace()
	{
		[$page, $wrapper] = $this->newPageWrapper();
		$wrapper->setConfirmText('Apply this change?');
		$wrapper->setConfirmButtonText('Apply');
		$wrapper->getControls()->add('Body');
		$this->assertSame(
			'<span id="guard">Body</span>'
			. '<dialog id="guard_confirm" role="alertdialog" class="prado-confirm" aria-labelledby="guard_confirm_header" aria-describedby="guard_confirm_message">'
			. '<div class="dialog-header"><h2 id="guard_confirm_header" class="dialog-title">Confirm</h2></div>'
			. '<p id="guard_confirm_message">Apply this change?</p>'
			. '<div class="dialog-buttons">'
			. '<button type="button" commandfor="guard_confirm" command="close" autofocus="autofocus">Cancel</button>'
			. '<button type="button" id="guard_confirm_ok">Apply</button>'
			. '</div></dialog>',
			$this->render($wrapper)
		);
	}

	public function testDialogRendersAfterThePageForm()
	{
		[$page, $wrapper] = $this->newPageWrapper($form = new TForm());
		$wrapper->setConfirmText('Sure?');
		$this->assertSame('<span id="guard"></span>', $this->render($wrapper));
		$this->assertArrayHasKey('guard_confirm', $form->getTeleportContents());
	}

	public function testRegistersClientControl()
	{
		[$page, $wrapper] = $this->newPageWrapper();
		$wrapper->setConfirmText('Sure?');
		$this->render($wrapper);
		$this->assertContains('confirmsubmit', $page->getClientScript()->pradoScripts);
		$this->assertStringContainsString(
			"new Prado.WebUI.TConfirmSubmit({'ID':\"guard\",'DialogID':\"guard_confirm\",'OkID':\"guard_confirm_ok\"",
			$this->endScripts($page)
		);
	}

	public function testPassesAutoPostBackDescendants()
	{
		[$page, $wrapper] = $this->newPageWrapper();
		$wrapper->setConfirmText('Sure?');
		$panel = new TPanel();
		$wrapper->getControls()->add($panel);
		foreach ([[new TCheckBox(), 'box', true], [new TDropDownList(), 'list', true], [new TTextBox(), 'text', false], [new TCheckBox(), 'gone', true]] as [$control, $id, $auto]) {
			$control->setID($id);
			$control->setAutoPostBack($auto);
			$panel->getControls()->add($control);
		}
		$panel->findControl('gone')->setVisible(false);
		$this->render($wrapper);
		$this->assertStringContainsString("'AutoPostBack':[\"box\",\"list\"]", $this->endScripts($page));
	}

	public function testRegistersDialogStyleSheetOnlyWithConfirmation()
	{
		[$page, $wrapper] = $this->newPageWrapper();
		$wrapper->onPreRender(null);
		$this->assertFalse($page->getClientScript()->isStyleSheetFileRegistered('/assets/dialog.css'));
		$wrapper->setConfirmText('Sure?');
		$wrapper->onPreRender(null);
		$this->assertTrue($page->getClientScript()->isStyleSheetFileRegistered('/assets/dialog.css'));
	}

	public function testEncodeCoversTheMessage()
	{
		[$page, $wrapper] = $this->newPageWrapper();
		$wrapper->setConfirmText('<i>Sure?</i>');
		$this->assertStringContainsString('<i>Sure?</i></p>', $this->render($wrapper));
		$wrapper->setEncode(true);
		$this->assertStringContainsString('&lt;i&gt;Sure?&lt;/i&gt;</p>', $this->render($wrapper));
	}
}
