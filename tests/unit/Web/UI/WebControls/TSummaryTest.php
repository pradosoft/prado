<?php

use Prado\Web\UI\WebControls\TSummary;
use Prado\Web\UI\WebControls\TDetails;
use PHPUnit\Framework\TestCase;

class TSummaryTest extends TestCase
{
	use TWebControlRenderTrait;

	public function testRendersSummaryTag()
	{
		$control = new TSummary();
		$output = $this->render($control);
		$this->assertStringContainsString('<summary', $output);
		$this->assertStringContainsString('</summary>', $output);
	}

	public function testExtendsWebControl()
	{
		$control = new TSummary();
		$this->assertInstanceOf(\Prado\Web\UI\WebControls\TWebControl::class, $control);
	}

	public function testRendersWithAttributes()
	{
		$control = new TSummary();
		$control->setCssClass('details-summary');
		$output = $this->render($control);
		$this->assertStringContainsString('class="details-summary"', $output);
		$this->assertStringContainsString('<summary', $output);
	}

	public function testOnInitThrowsWhenParentIsNotTDetails()
	{
		$summary = new TSummary();
		// Parent is null — not a TDetails
		$this->expectException(\Prado\Exceptions\TInvalidOperationException::class);
		$summary->onInit(null);
	}

	public function testOnInitThrowsWhenParentIsWrongType()
	{
		$outer = new \Prado\Web\UI\WebControls\TPanel();
		$summary = new TSummary();
		$outer->getControls()->add($summary);
		// Parent is TPanel — not the required TDetails
		$this->expectException(\Prado\Exceptions\TInvalidOperationException::class);
		$summary->onInit(null);
	}

	public function testOnInitDoesNotThrowWhenParentIsTDetails()
	{
		$details = new TDetails();
		$summary = new TSummary();
		$details->getControls()->add($summary);
		// Should not throw
		$summary->onInit(null);
		$this->assertInstanceOf(TDetails::class, $summary->getParent());
	}
}
