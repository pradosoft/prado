<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Web\Javascripts\TJavaScriptLiteral;
use Prado\Web\UI\WebControls\TClientSideValidationSummaryOptions;

class TClientSideValidationSummaryOptionsTest extends TestCase
{
	public function testEventsAreNullUntilSet()
	{
		$options = new TClientSideValidationSummaryOptions();
		$this->assertNull($options->getOnHideSummary());
		$this->assertNull($options->getOnShowSummary());
	}

	public function testEventsWrapTheScriptInASummaryFunction()
	{
		$options = new TClientSideValidationSummaryOptions();
		$options->setOnHideSummary('hide();');
		$options->setOnShowSummary('show();');
		$this->assertInstanceOf(TJavaScriptLiteral::class, $options->getOnHideSummary());
		$this->assertSame('function(summary, validators){ hide(); }', (string) $options->getOnHideSummary());
		$this->assertSame('function(summary, validators){ show(); }', (string) $options->getOnShowSummary());
		$this->assertSame(['OnHideSummary', 'OnShowSummary'], $options->getOptions()->getKeys());
	}
}
