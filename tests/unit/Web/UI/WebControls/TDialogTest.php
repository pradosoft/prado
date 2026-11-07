<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TDialog;
use Prado\Web\UI\WebControls\TDialogClosedBy;
use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;

class TDialogTest extends TestCase
{
	use TWebControlRenderTrait;

	public function testRendersDialogTag()
	{
		$control = new TDialog();
		$output = $this->render($control);
		$this->assertStringContainsString('<dialog', $output);
		$this->assertStringContainsString('</dialog>', $output);
	}

	public function testExtendsWebControl()
	{
		$control = new TDialog();
		$this->assertInstanceOf(\Prado\Web\UI\WebControls\TWebControl::class, $control);
	}

	public function testOpenDefaultFalse()
	{
		$control = new TDialog();
		$this->assertFalse($control->getOpen());
	}

	public function testSetOpenTrue()
	{
		$control = new TDialog();
		$control->setOpen(true);
		$this->assertTrue($control->getOpen());
	}

	public function testSetOpenFalse()
	{
		$control = new TDialog();
		$control->setOpen(true);
		$control->setOpen(false);
		$this->assertFalse($control->getOpen());
	}

	public function testSetOpenFromString()
	{
		$control = new TDialog();
		$control->setOpen('true');
		$this->assertTrue($control->getOpen());

		$control->setOpen('false');
		$this->assertFalse($control->getOpen());
	}

	public function testSetOpenFromNumeric()
	{
		$control = new TDialog();
		$control->setOpen(1);
		$this->assertTrue($control->getOpen());

		$control->setOpen(0);
		$this->assertFalse($control->getOpen());
	}

	public function testOpenAttributeRenderedWhenTrue()
	{
		$control = new TDialog();
		$control->setOpen(true);
		$output = $this->render($control);
		$this->assertStringContainsString('open="open"', $output);
	}

	public function testOpenAttributeNotRenderedWhenFalse()
	{
		$control = new TDialog();
		$control->setOpen(false);
		$output = $this->render($control);
		$this->assertStringNotContainsString('open=', $output);
	}

	public function testClosedByDefaultsAuto()
	{
		$control = new TDialog();
		$this->assertSame(TDialogClosedBy::Auto, $control->getClosedBy());
		$this->assertStringNotContainsString('closedby', $this->render($control));
	}

	public function testClosedByRendersLowercase()
	{
		$control = new TDialog();
		foreach (['Any' => 'any', 'closerequest' => 'closerequest', 'None' => 'none'] as $value => $attribute) {
			$control->setClosedBy($value);
			$this->assertStringContainsString('closedby="' . $attribute . '"', $this->render($control));
		}
	}

	public function testClosedByRejectsUnknown()
	{
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		(new TDialog())->setClosedBy('outside');
	}

	public function testModalDefaultFalse()
	{
		$control = new TDialog();
		$this->assertFalse($control->getModal());
		$control->setModal('true');
		$this->assertTrue($control->getModal());
	}

	public function testModalWithoutOpenRendersNothing()
	{
		[$page, $control] = $this->createPageDialog();
		$control->setModal(true);
		$output = $this->render($control);
		$this->assertStringNotContainsString('open=', $output);
		$this->assertFalse($page->getClientScript()->isEndScriptRegistered('prado:dialog:' . $control->getClientID()));
	}

	public function testOpenModalRegistersShowModalScript()
	{
		[$page, $control] = $this->createPageDialog();
		$control->setOpen(true);
		$control->setModal(true);
		$output = $this->render($control);
		$this->assertStringNotContainsString('open=', $output);
		$this->assertStringContainsString('id="' . $control->getClientID() . '"', $output);

		$key = 'prado:dialog:' . $control->getClientID();
		$scripts = PradoUnit::getProp($page->getClientScript(), '_endScripts');
		$this->assertArrayHasKey($key, $scripts);
		$this->assertStringContainsString('showModal()', $scripts[$key]);
		$this->assertStringContainsString(TJavaScript::quoteString($control->getClientID()), $scripts[$key]);
	}

	public function testHeaderDefaultsRenderNoHeader()
	{
		$control = new TDialog();
		$this->assertSame('', $control->getHeaderText());
		$this->assertSame(2, $control->getHeaderLevel());
		$this->assertFalse($control->getShowCloseButton());
		$this->assertFalse($control->getHasHeader());
		$output = $this->render($control);
		$this->assertStringNotContainsString('dialog-header', $output);
		$this->assertStringNotContainsString('aria-labelledby', $output);
	}

	public function testHeaderTextRendersHeadingThatNamesTheDialog()
	{
		[$page, $control] = $this->createPageDialog();
		$control->setHeaderText('Edit name');
		$control->setHeaderLevel('3');
		$output = $this->render($control);
		$headerId = $control->getClientID() . '_header';
		$this->assertSame($headerId, $control->getHeaderClientID());
		$this->assertStringContainsString('id="' . $control->getClientID() . '"', $output);
		$this->assertStringContainsString('aria-labelledby="' . $headerId . '"', $output);
		$this->assertStringContainsString('<div class="dialog-header"><h3 id="' . $headerId . '" class="dialog-title">Edit name</h3></div>', $output);
		$this->assertStringNotContainsString('dialog-close', $output);
	}

	public function testHeaderRendersBeforeTheBody()
	{
		$control = new TDialog();
		$control->setHeaderText('Notice');
		$control->getControls()->add('<p>Body</p>');
		$output = $this->render($control);
		$this->assertLessThan(strpos($output, '<p>Body</p>'), strpos($output, 'dialog-header'));
	}

	public function testExplicitLabelledByIsKept()
	{
		$control = new TDialog();
		$control->setHeaderText('Notice');
		$control->setAttribute('aria-labelledby', 'other');
		$output = $this->render($control);
		$this->assertStringContainsString('aria-labelledby="other"', $output);
		$this->assertStringNotContainsString('_header"', substr($output, 0, strpos($output, '>')));
	}

	public function testHeaderTextEncode()
	{
		$control = new TDialog();
		$control->setHeaderText('<b>Bold</b>');
		$this->assertStringContainsString('><b>Bold</b></h2>', $this->render($control));
		$control->setEncode(true);
		$this->assertStringContainsString('>&lt;b&gt;Bold&lt;/b&gt;</h2>', $this->render($control));
	}

	public function testHeaderLevelRejectsOutOfRange()
	{
		$control = new TDialog();
		foreach ([0, 7] as $level) {
			try {
				$control->setHeaderLevel($level);
				$this->fail('HeaderLevel ' . $level . ' was accepted');
			} catch (\Prado\Exceptions\TInvalidDataValueException $e) {
				$this->assertSame('dialog_headerlevel_invalid', $e->getErrorCode());
			}
		}
		$this->assertSame(2, $control->getHeaderLevel());
	}

	public function testShowCloseButtonRendersInvoker()
	{
		[$page, $control] = $this->createPageDialog();
		$control->setShowCloseButton('true');
		$this->assertTrue($control->getHasHeader());
		$output = $this->render($control);
		$this->assertStringContainsString('id="' . $control->getClientID() . '"', $output);
		$this->assertStringContainsString('<button type="button" class="dialog-close" commandfor="' . $control->getClientID() . '" command="close" aria-label="Close">&times;</button>', $output);
		$this->assertStringNotContainsString('aria-labelledby', $output);
	}

	public function testCloseButtonText()
	{
		$control = new TDialog();
		$this->assertSame('Close', $control->getCloseButtonText());
		$control->setCloseButtonText('Dismiss <now>');
		$control->setShowCloseButton(true);
		$this->assertStringContainsString('aria-label="Dismiss &lt;now&gt;"', $this->render($control));
		$control->setCloseButtonText('');
		$this->assertSame('Close', $control->getCloseButtonText());
	}

	public function testHeaderRegistersStyleSheet()
	{
		[$page, $control] = $this->createPageDialog();
		$this->assertSame('default', $control->getCssUrl());
		$control->onPreRender(null);
		$this->assertSame([], $this->styleSheetFiles($page));

		$control->setHeaderText('Notice');
		$control->setCssUrl('/css/dialog.css');
		$control->onPreRender(null);
		$this->assertSame(['/css/dialog.css'], $this->styleSheetFiles($page));
	}

	public function testEmptyCssUrlRegistersNoStyleSheet()
	{
		[$page, $control] = $this->createPageDialog();
		$control->setShowCloseButton(true);
		$control->setCssUrl('');
		$control->onPreRender(null);
		$this->assertSame([], $this->styleSheetFiles($page));
	}

	/**
	 * @return string[] the registered stylesheet file URLs
	 */
	private function styleSheetFiles(TPage $page): array
	{
		return array_values(array_map(fn ($file) => is_array($file) ? $file[0] : $file, PradoUnit::getProp($page->getClientScript(), '_styleSheetFiles')));
	}

	/**
	 * @return array{TPage, TDialog} a dialog with an ID in a page
	 */
	private function createPageDialog(): array
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_clientScript', new TClientScriptManager($page));
		$control = new TDialog();
		$control->setID('notice');
		$page->getControls()->add($control);
		return [$page, $control];
	}
}
