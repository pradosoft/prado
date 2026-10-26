<?php


namespace Prado\Test\Unit\Web\UI\WebControls;

use Prado\Web\UI\WebControls\TRequiredFieldValidator;
use Prado\Web\UI\WebControls\TTextBox;
use Prado\Web\UI\WebControls\TValidationSummary;
use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;

class TValidationSummaryTest extends TestCase
{
	use TWebControlRenderTrait;

	private function createSummary($id = 'summary1')
	{
		$page = new \Prado\Web\UI\TPage();
		$summary = new TValidationSummary();
		$summary->setID($id);
		$page->getControls()->add($summary);
		return $summary;
	}

	// ================================================================================
	// Accessibility Rendering Tests
	// ================================================================================

	public function testRenderAddsAlertRole()
	{
		$summary = $this->createSummary();
		$html = $this->renderBeginTag($summary);
		$this->assertStringContainsString('role="alert"', $html);
	}

	public function testRenderIncludesClientId()
	{
		$summary = $this->createSummary('mySummary');
		$html = $this->renderBeginTag($summary);
		$this->assertStringContainsString('id="' . $summary->getClientID() . '"', $html);
	}

	/**
	 * Adds a failed validator with the message to the summary's page.
	 */
	private function addFailedValidator(TValidationSummary $summary, string $id, string $message, bool $encode): TRequiredFieldValidator
	{
		$page = $summary->getPage();
		$textbox = new TTextBox();
		$textbox->setID($id . 'Input');
		$page->getControls()->add($textbox);
		$validator = new TRequiredFieldValidator();
		$validator->setID($id);
		$validator->setControlToValidate($textbox->getID());
		$validator->setErrorMessage($message);
		$validator->setEncode($encode);
		$page->getControls()->add($validator);
		$validator->onInit(null);
		$validator->setIsValid(false);
		return $validator;
	}

	/**
	 * A message is HTML unless its validator's Encode is true.
	 */
	public function testErrorMessagesEncodedPerValidator()
	{
		$summary = $this->createSummary();
		$this->addFailedValidator($summary, 'raw', '<b>raw</b>', false);
		$this->addFailedValidator($summary, 'enc', '<b>enc</b>', true);

		$this->assertSame(['<b>raw</b>', '&lt;b&gt;enc&lt;/b&gt;'], PradoUnit::invoke($summary, 'getErrorMessages'));

		$tw = new \Prado\IO\TTextWriter();
		PradoUnit::invoke($summary, 'renderBulletList', new \Prado\Web\UI\THtmlWriter($tw));
		$html = $tw->flush();
		$this->assertStringContainsString('<li><b>raw</b></li>', $html);
		$this->assertStringContainsString('<li>&lt;b&gt;enc&lt;/b&gt;</li>', $html);
	}
}
