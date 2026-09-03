<?php


use Prado\Web\UI\WebControls\TValidationSummary;
use PHPUnit\Framework\TestCase;

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
}
