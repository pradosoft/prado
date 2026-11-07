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
use Prado\Web\UI\WebControls\TButton;
use Prado\Web\UI\WebControls\TConfirmButton;

/**
 * Records published paths instead of copying files.
 */
class TConfirmButtonTestAssetManager extends TAssetManager
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
class TConfirmButtonTestClientScript extends TClientScriptManager
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

class TConfirmButtonTest extends TestCase
{
	use TWebControlRenderTrait;

	private mixed $_assetManager;

	protected function setUp(): void
	{
		$this->_assetManager = PradoUnit::getProp(Prado::getApplication(), '_assetManager');
		Prado::getApplication()->setAssetManager(new TConfirmButtonTestAssetManager());
	}

	protected function tearDown(): void
	{
		PradoUnit::setProp(Prado::getApplication(), '_assetManager', $this->_assetManager);
	}

	/**
	 * @param ?TForm $form the page form, or null to render in place
	 * @return array{TPage, TConfirmButton} a button "delete" rendering in a form
	 */
	private function newPageButton(?TForm $form = null): array
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_clientScript', new TConfirmButtonTestClientScript($page));
		PradoUnit::setProp($page, '_inFormRender', true);
		if ($form !== null) {
			$form->setID('form1');
			$page->getControls()->add($form);
			PradoUnit::setProp($page, '_form', $form);
		}
		$button = new TConfirmButton();
		$button->setID('delete');
		$button->setText('Delete');
		$page->getControls()->add($button);
		return [$page, $button];
	}

	public function testExtendsButton()
	{
		$this->assertInstanceOf(TButton::class, new TConfirmButton());
	}

	public function testDefaults()
	{
		$button = new TConfirmButton();
		$this->assertSame('', $button->getConfirmText());
		$this->assertSame('Confirm', $button->getConfirmHeaderText());
		$this->assertSame('OK', $button->getConfirmButtonText());
		$this->assertSame('Cancel', $button->getCancelButtonText());
		$this->assertSame('default', $button->getCssUrl());
		$this->assertFalse($button->getHasConfirmation());
	}

	public function testTextsRestoreDefaultsWhenEmpty()
	{
		$button = new TConfirmButton();
		$button->setConfirmHeaderText('Really?');
		$button->setConfirmButtonText('Delete');
		$button->setCancelButtonText('Keep');
		$this->assertSame(['Really?', 'Delete', 'Keep'], [$button->getConfirmHeaderText(), $button->getConfirmButtonText(), $button->getCancelButtonText()]);
		$button->setConfirmHeaderText('');
		$button->setConfirmButtonText('');
		$button->setCancelButtonText('');
		$this->assertSame(['Confirm', 'OK', 'Cancel'], [$button->getConfirmHeaderText(), $button->getConfirmButtonText(), $button->getCancelButtonText()]);
	}

	public function testWithoutConfirmTextRendersTButton()
	{
		[$page, $button] = $this->newPageButton();
		$output = $this->render($button);
		$this->assertStringStartsWith('<input type="submit" name="delete" value="Delete"', $output);
		$this->assertStringNotContainsString('<dialog', $output);
	}

	public function testDisabledRendersTButton()
	{
		[$page, $button] = $this->newPageButton();
		$button->setConfirmText('Delete this record?');
		$button->setEnabled(false);
		$this->assertFalse($button->getHasConfirmation());
		$output = $this->render($button);
		$this->assertStringContainsString('disabled="disabled"', $output);
		$this->assertStringNotContainsString('<dialog', $output);
	}

	public function testRendersInvokerAndDialogInPlace()
	{
		[$page, $button] = $this->newPageButton();
		$button->setConfirmText('Delete this record?');
		$button->setCssClass('danger');
		$this->assertSame('delete_confirm', $button->getDialogClientID());
		$this->assertSame('delete_confirm_ok', $button->getConfirmButtonClientID());
		$this->assertSame(
			'<button id="delete" class="danger" type="button" aria-haspopup="dialog" commandfor="delete_confirm" command="show-modal">Delete</button>'
			. '<dialog id="delete_confirm" role="alertdialog" class="prado-confirm" aria-labelledby="delete_confirm_header" aria-describedby="delete_confirm_message">'
			. '<div class="dialog-header"><h2 id="delete_confirm_header" class="dialog-title">Confirm</h2></div>'
			. '<p id="delete_confirm_message">Delete this record?</p>'
			. '<div class="dialog-buttons">'
			. '<button type="button" commandfor="delete_confirm" command="close" autofocus="autofocus">Cancel</button>'
			. '<button type="submit" id="delete_confirm_ok" name="delete" value="Delete">OK</button>'
			. '</div></dialog>',
			$this->render($button)
		);
	}

	public function testDialogRendersAfterThePageForm()
	{
		[$page, $button] = $this->newPageButton($form = new TForm());
		$button->setConfirmText('Delete this record?');
		$output = $this->render($button);
		$this->assertStringStartsWith('<button id="delete"', $output);
		$this->assertStringNotContainsString('<dialog', $output);
		$teleported = $form->getTeleportContents();
		$this->assertArrayHasKey('delete_confirm', $teleported);
		$this->assertStringStartsWith('<dialog id="delete_confirm"', $teleported['delete_confirm']);
		$this->assertStringContainsString('<button type="submit" id="delete_confirm_ok" name="delete" value="Delete" form="form1">OK</button>', $teleported['delete_confirm']);
	}

	public function testTextsAreEncoded()
	{
		[$page, $button] = $this->newPageButton();
		$button->setText('<b>Del</b>');
		$button->setConfirmText('<i>Sure?</i>');
		$button->setConfirmButtonText('<u>Yes</u>');
		$button->setCancelButtonText('<s>No</s>');
		$output = $this->render($button);
		$this->assertStringContainsString('>&lt;b&gt;Del&lt;/b&gt;</button>', $output);
		$this->assertStringContainsString('>&lt;u&gt;Yes&lt;/u&gt;</button>', $output);
		$this->assertStringContainsString('>&lt;s&gt;No&lt;/s&gt;</button>', $output);
		$this->assertStringContainsString('<p id="delete_confirm_message"><i>Sure?</i></p>', $output);
		$button->setEncode(true);
		$this->assertStringContainsString('<p id="delete_confirm_message">&lt;i&gt;Sure?&lt;/i&gt;</p>', $this->render($button));
	}

	public function testNoValidationRendersFormNoValidate()
	{
		[$page, $button] = $this->newPageButton();
		$button->setConfirmText('Sure?');
		$button->setCausesValidation(false);
		$this->assertStringContainsString('name="delete" value="Delete" formnovalidate="formnovalidate">OK', $this->render($button));
	}

	public function testClientControlTargetsTheOkButton()
	{
		[$page, $button] = $this->newPageButton();
		$button->setConfirmText('Sure?');
		$button->setIsDefaultButton(true);
		$this->render($button);
		$scripts = implode("\n", PradoUnit::getProp($page->getClientScript(), '_endScripts'));
		$this->assertStringContainsString('new Prado.WebUI.TConfirmButton(', $scripts);
		$this->assertStringContainsString("'ID':\"delete_confirm_ok\"", $scripts);
		$this->assertStringContainsString("'EventTarget':\"delete\"", $scripts);
	}

	public function testClientControlWithoutConfirmationIsTButton()
	{
		[$page, $button] = $this->newPageButton();
		$button->setIsDefaultButton(true);
		$this->render($button);
		$scripts = implode("\n", PradoUnit::getProp($page->getClientScript(), '_endScripts'));
		$this->assertStringContainsString('new Prado.WebUI.TButton(', $scripts);
		$this->assertStringContainsString("'ID':\"delete\"", $scripts);
	}

	public function testPreRenderRegistersDialogStyleSheet()
	{
		[$page, $button] = $this->newPageButton();
		$button->onPreRender(null);
		$this->assertSame([], Prado::getApplication()->getAssetManager()->published);

		$button->setConfirmText('Sure?');
		$button->onPreRender(null);
		$this->assertSame(['dialog.css'], Prado::getApplication()->getAssetManager()->published);
		$this->assertTrue($page->getClientScript()->isStyleSheetFileRegistered('/assets/dialog.css'));
	}

	public function testPreRenderCustomAndEmptyCssUrl()
	{
		[$page, $button] = $this->newPageButton();
		$button->setConfirmText('Sure?');
		$button->setCssUrl('');
		$button->onPreRender(null);
		$this->assertFalse($page->getClientScript()->isStyleSheetFileRegistered('/assets/dialog.css'));
		$button->setCssUrl('/theme/confirm.css');
		$button->onPreRender(null);
		$this->assertTrue($page->getClientScript()->isStyleSheetFileRegistered('/theme/confirm.css'));
		$this->assertSame([], Prado::getApplication()->getAssetManager()->published);
	}
}
