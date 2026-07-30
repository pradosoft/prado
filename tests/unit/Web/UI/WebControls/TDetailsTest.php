<?php

use Prado\Web\UI\WebControls\TDetails;
use Prado\Web\UI\WebControls\TSummary;
use PHPUnit\Framework\TestCase;

class TDetailsTest extends TestCase
{
	use TWebControlRenderTrait;

	public function testRendersDetailsTag()
	{
		$control = new TDetails();
		$output = $this->render($control);
		$this->assertStringContainsString('<details', $output);
		$this->assertStringContainsString('</details>', $output);
	}

	public function testExtendsWebControl()
	{
		$control = new TDetails();
		$this->assertInstanceOf(\Prado\Web\UI\WebControls\TWebControl::class, $control);
	}

	public function testSummaryDefaultEmpty()
	{
		$control = new TDetails();
		$this->assertEquals('', $control->getSummary());
	}

	public function testSetSummary()
	{
		$control = new TDetails();
		$control->setSummary('More info');
		$this->assertEquals('More info', $control->getSummary());
	}

	public function testSetSummaryEmpty()
	{
		$control = new TDetails();
		$control->setSummary('Something');
		$control->setSummary('');
		$this->assertEquals('', $control->getSummary());
	}

	public function testOpenDefaultFalse()
	{
		$control = new TDetails();
		$this->assertFalse($control->getOpen());
	}

	public function testSetOpenTrue()
	{
		$control = new TDetails();
		$control->setOpen(true);
		$this->assertTrue($control->getOpen());
	}

	public function testSetOpenFalse()
	{
		$control = new TDetails();
		$control->setOpen(true);
		$control->setOpen(false);
		$this->assertFalse($control->getOpen());
	}

	public function testSetOpenFromString()
	{
		$control = new TDetails();
		$control->setOpen('true');
		$this->assertTrue($control->getOpen());

		$control->setOpen('false');
		$this->assertFalse($control->getOpen());
	}

	public function testGroupDefaultEmpty()
	{
		$control = new TDetails();
		$this->assertEquals('', $control->getGroup());
	}

	public function testSetGroup()
	{
		$control = new TDetails();
		$control->setGroup('faq-section');
		$this->assertEquals('faq-section', $control->getGroup());
	}

	public function testSetGroupEmpty()
	{
		$control = new TDetails();
		$control->setGroup('something');
		$control->setGroup('');
		$this->assertEquals('', $control->getGroup());
	}

	public function testOpenAttributeRenderedWhenTrue()
	{
		$control = new TDetails();
		$control->setOpen(true);
		$output = $this->render($control);
		$this->assertStringContainsString('open="open"', $output);
	}

	public function testOpenAttributeNotRenderedWhenFalse()
	{
		$control = new TDetails();
		$output = $this->render($control);
		$this->assertStringNotContainsString('open=', $output);
	}

	public function testGroupRendersNameAttributeWhenSet()
	{
		$control = new TDetails();
		$control->setGroup('accordion');
		$output = $this->render($control);
		// The HTML exclusive accordion attribute on <details> is "name"
		$this->assertStringContainsString('name="accordion"', $output);
	}

	public function testGroupNameAttributeNotRenderedWhenEmpty()
	{
		$control = new TDetails();
		$output = $this->render($control);
		$this->assertStringNotContainsString('name=', $output);
	}

	public function testEncodeDefaultFalse()
	{
		$control = new TDetails();
		$this->assertFalse($control->getEncode());
	}

	public function testSetEncode()
	{
		$control = new TDetails();
		$control->setEncode(true);
		$this->assertTrue($control->getEncode());
		$control->setEncode('false');
		$this->assertFalse($control->getEncode());
	}

	public function testSummaryEncodedWhenEncodeTrue()
	{
		$control = new TDetails();
		$control->setSummary('More <b>info</b>');
		$control->setEncode(true);
		$output = $this->render($control);
		// THttpUtility::htmlEncode() translates <, >, and " only.
		$this->assertStringContainsString('More &lt;b&gt;info&lt;/b&gt;', $output);
		$this->assertStringNotContainsString('<b>info</b>', $output);
	}

	public function testSummaryNotEncodedByDefault()
	{
		$control = new TDetails();
		$control->setSummary('More <b>info</b>');
		$output = $this->render($control);
		$this->assertStringContainsString('More <b>info</b>', $output);
	}

	public function testRenderContentsSummaryFromProperty()
	{
		$control = new TDetails();
		$control->setSummary('Click to expand');
		$output = $this->render($control);
		$this->assertStringContainsString('<summary>', $output);
		$this->assertStringContainsString('Click to expand', $output);
		$this->assertStringContainsString('</summary>', $output);
	}

	public function testRenderContentsSummaryFromPropertyNotRenderedWhenEmpty()
	{
		$control = new TDetails();
		$output = $this->render($control);
		$this->assertStringNotContainsString('<summary>', $output);
	}

	public function testRenderContentsTSummaryChildTakesPrecedence()
	{
		$control = new TDetails();
		$control->setSummary('Property summary');

		$summaryChild = new TSummary();
		$summaryChild->setId('childSummary');
		$control->getControls()->add($summaryChild);

		$output = $this->render($control);
		// TSummary child should be rendered (produces <summary> tag)
		$this->assertStringContainsString('<summary', $output);
		// The property text should NOT appear as a raw <summary> since TSummary child took over
		$this->assertStringNotContainsString('>Property summary<', $output);
	}

	public function testRenderContentsFirstTSummaryChildUsedWhenMultiple()
	{
		$control = new TDetails();

		$first = new TSummary();
		$first->getControls()->add('First summary');
		$control->getControls()->add($first);

		$second = new TSummary();
		$second->getControls()->add('Second summary');
		$control->getControls()->add($second);

		$output = $this->render($control);
		// First TSummary should appear as <summary>
		$this->assertStringContainsString('First summary', $output);
		// Second TSummary should be rendered as regular content (not discarded)
		$this->assertStringContainsString('Second summary', $output);
		// There should be two <summary> tags total (one from first child, one from second as content)
		$this->assertEquals(2, substr_count($output, '<summary'));
		// First should appear before second in output
		$this->assertLessThan(strpos($output, 'Second summary'), strpos($output, 'First summary'));
	}

	public function testRenderContentsSkipsNestedTDetailsForSummary()
	{
		$outer = new TDetails();
		$outer->setSummary('Outer summary');

		$inner = new TDetails();
		$inner->setSummary('Inner summary');
		$outer->getControls()->add($inner);

		$output = $this->render($outer);
		// Outer summary should be rendered
		$this->assertStringContainsString('Outer summary', $output);
		// Outer summary should NOT be rendered
		$this->assertStringContainsString('Inner summary', $output);
		
		// Inner details should also appear as a child
		$this->assertStringContainsString('<details', $output);
	}

	public function testRenderContentsNonSummaryChildrenRendered()
	{
		$control = new TDetails();

		$summary = new TSummary();
		$control->getControls()->add($summary);

		$header2 = new \Prado\Web\UI\WebControls\THeader2();
		$header2->setId('innerHeader2');
		$control->getControls()->add($header2);

		$output = $this->render($control);
		$this->assertStringContainsString('<h2', $output);
	}

	public function testStringChildRenderedWithSummaryProperty()
	{
		$control = new TDetails();
		$control->setSummary('More info');
		$control->getControls()->add('plain text content');

		$output = $this->render($control);
		$this->assertStringContainsString('plain text content', $output);
		$this->assertStringContainsString('<summary>More info</summary>', $output);
		// Auto-generated summary appears before the content
		$this->assertLessThan(strpos($output, 'plain text content'), strpos($output, '<summary>'));
	}

	public function testInvisibleChildNotRenderedWithSummaryProperty()
	{
		$control = new TDetails();
		$control->setSummary('More info');

		$header2 = new \Prado\Web\UI\WebControls\THeader2();
		$header2->setVisible(false);
		$control->getControls()->add($header2);

		$output = $this->render($control);
		$this->assertStringNotContainsString('<h2', $output);
		$this->assertStringContainsString('<summary>', $output);
	}
}
