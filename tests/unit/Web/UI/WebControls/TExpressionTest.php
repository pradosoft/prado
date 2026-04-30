<?php

use Prado\Web\UI\WebControls\TExpression;
use Prado\Web\UI\TTemplate;
use Prado\Web\UI\TPage;
use Prado\Web\UI\THtmlWriter;
use Prado\IO\TTextWriter;
use PHPUnit\Framework\TestCase;

class TExpressionTest extends TestCase
{
	private $_page;
	private $_contextPath;

	protected function setUp(): void
	{
		$this->_contextPath = sys_get_temp_dir();
		$this->_page = new TPage();
		$this->_page->setID('TestPage');
	}

	protected function tearDown(): void
	{
		$this->_page = null;
		$this->_contextPath = null;
	}

	private function newTemplate($template): TTemplate
	{
		return new TTemplate($template, $this->_contextPath, null, 0, true);
	}

	private function render($control)
	{
		$tw = new TTextWriter();
		$writer = new THtmlWriter($tw);
		$control->render($writer);
		return $tw->flush();
	}

	public function testExtendsTControl()
	{
		$control = new TExpression();
		$this->assertInstanceOf(\Prado\Web\UI\TControl::class, $control);
	}

	public function testExpressionDefaultEmptyString()
	{
		$control = new TExpression();
		$this->assertEquals('', $control->getExpression());
	}

	public function testExpressionSetter()
	{
		$control = new TExpression();
		$control->setExpression('test');
		$this->assertEquals('test', $control->getExpression());
	}

	public function testExpressionWithSingleQuotes()
	{
		$control = new TExpression();
		$control->setExpression('value&#039;s');
		$this->assertEquals("value's", $control->getExpression());
	}

	public function testExpressionWithDoubleQuotes()
	{
		$control = new TExpression();
		$control->setExpression('value&quot;s');
		$this->assertEquals('value"s', $control->getExpression());
	}

	public function testExpressionWithMixedQuotes()
	{
		$control = new TExpression();
		$control->setExpression('He said &#039;Hello &quot;World&quot;&#039;');
		$this->assertEquals('He said \'Hello "World"\'', $control->getExpression());
	}

	public function testExpressionEvaluationSimpleValue()
	{
		$control = new TExpression();
		$control->setExpression('123');
		$result = $control->evaluateExpression('123');
		$this->assertEquals('123', $result);
	}

	public function testExpressionEvaluationString()
	{
		$control = new TExpression();
		$control->setExpression('"Hello World"');
		$result = $control->evaluateExpression('"Hello World"');
		$this->assertEquals('Hello World', $result);
	}

	public function testExpressionEvaluationFunction()
	{
		$control = new TExpression();
		$control->setExpression('count(["a", "b", "c"])');
		$result = $control->evaluateExpression('count(["a", "b", "c"])');
		$this->assertEquals('3', $result);
	}

	public function testExpressionEvaluationEmptyExpression()
	{
		$control = new TExpression();
		$control->setExpression('');
		$result = $control->evaluateExpression('');
		$this->assertEquals('', $result);
	}

	public function testExpressionEvaluationInvalidExpression()
	{
		$control = new TExpression();
		$control->setExpression('invalid syntax');
		
		$this->expectException(\Prado\Exceptions\TInvalidOperationException::class);
		$result = $control->evaluateExpression('invalid syntax');
	}

	public function testTemplate_ExpressionEvaluation()
	{
		$template = '<com:TExpression ID="Expr1" Expression="123" />';
		$page = new TPage();
		$page->setID('TestPage');
		$tpl = $this->newTemplate($template);
		$tpl->instantiateIn($page);
		
		$expression = $page->findControl('Expr1');
		$result = $expression->evaluateExpression('123');
		$this->assertEquals('123', $result);
	}

	public function testTemplate_EmptyExpression()
	{
		$template = '<com:TExpression ID="Expr3" Expression="" />';
		$page = new TPage();
		$page->setID('TestPage');
		$tpl = $this->newTemplate($template);
		$tpl->instantiateIn($page);
		
		$expression = $page->findControl('Expr3');
		$result = $expression->evaluateExpression('');
		$this->assertEquals('', $result);
	}

	public function testRenderSimpleValue()
	{
		$expression = new TExpression();
		$expression->setExpression('123');
		$expression->setID('Expression1');
		$this->_page->getControls()->add($expression);
		
		$output = $this->render($expression);
		$this->assertEquals('123', $output);
	}

	public function testRenderStringExpression()
	{
		$expression = new TExpression();
		$expression->setExpression('"Hello World"');
		$expression->setID('Expression2');
		$this->_page->getControls()->add($expression);
		
		$output = $this->render($expression);
		$this->assertEquals('Hello World', $output);
	}

	public function testRenderFunctionExpression()
	{
		$expression = new TExpression();
		$expression->setExpression('count(["a", "b", "c"])');
		$expression->setID('Expression3');
		$this->_page->getControls()->add($expression);
		
		$output = $this->render($expression);
		$this->assertEquals('3', $output);
	}

	public function testRenderEmptyExpression()
	{
		$expression = new TExpression();
		$expression->setExpression('');
		$expression->setID('Expression4');
		$this->_page->getControls()->add($expression);
		
		$output = $this->render($expression);
		$this->assertEquals('', $output);
	}

	public function testRenderTemplateExpression()
	{
		$template = '<com:TExpression ID="Expr5" Expression="123" />';
		$page = new TPage();
		$page->setID('TestPage');
		$tpl = $this->newTemplate($template);
		$tpl->instantiateIn($page);
		
		$expression = $page->findControl('Expr5');
		$output = $this->render($expression);
		$this->assertEquals('123', $output);
	}

	public function testRenderTemplateEmptyExpression()
	{
		$template = '<com:TExpression ID="Expr6" Expression="" />';
		$page = new TPage();
		$page->setID('TestPage');
		$tpl = $this->newTemplate($template);
		$tpl->instantiateIn($page);
		
		$expression = $page->findControl('Expr6');
		$output = $this->render($expression);
		$this->assertEquals('', $output);
	}

	public function testRenderWithSingleQuoteString()
	{
		$expression = new TExpression();
		$expression->setExpression("'value\'s'");
		$expression->setID('Expression7');
		$this->_page->getControls()->add($expression);
		
		$output = $this->render($expression);
		$this->assertEquals("value's", $output);
	}

	public function testRenderWithDoubleQuoteString()
	{
		$expression = new TExpression();
		$expression->setExpression('"value\"s"');
		$expression->setID('Expression8');
		$this->_page->getControls()->add($expression);
		
		$output = $this->render($expression);
		$this->assertEquals('value"s', $output);
	}

	public function testPropertyHandlingWithSingleQuotes()
	{
		$control = new TExpression();
		$control->setExpression('value&#039;s');
		$this->assertEquals("value's", $control->getExpression());
	}

	public function testPropertyHandlingWithDoubleQuotes()
	{
		$control = new TExpression();
		$control->setExpression('value&quot;s');
		$this->assertEquals('value"s', $control->getExpression());
	}

	public function testPropertyHandlingWithMixedQuotes()
	{
		$control = new TExpression();
		$control->setExpression('He said &#039;Hello &quot;World&quot;&#039;');
		$this->assertEquals('He said \'Hello "World"\'', $control->getExpression());
	}

	public function testTemplatePropertyHandlingWithSingleQuotes()
	{
		$template = '<com:TExpression ID="Expr7" Expression="value&#039;s" />';
		$page = new TPage();
		$page->setID('TestPage');
		$tpl = $this->newTemplate($template);
		$tpl->instantiateIn($page);
		
		$expression = $page->findControl('Expr7');
		$this->assertEquals("value's", $expression->getExpression());
	}

	public function testTemplatePropertyHandlingWithDoubleQuotes()
	{
		$template = '<com:TExpression ID="Expr8" Expression="value&quot;s" />';
		$page = new TPage();
		$page->setID('TestPage');
		$tpl = $this->newTemplate($template);
		$tpl->instantiateIn($page);
		
		$expression = $page->findControl('Expr8');
		$this->assertEquals('value"s', $expression->getExpression());
	}

	public function testTemplatePropertyHandlingWithMixedQuotes()
	{
		$template = '<com:TExpression ID="Expr9" Expression="He said &#039;Hello &quot;World&quot;&#039;" />';
		$page = new TPage();
		$page->setID('TestPage');
		$tpl = $this->newTemplate($template);
		$tpl->instantiateIn($page);
		
		$expression = $page->findControl('Expr9');
		$this->assertEquals('He said \'Hello "World"\'', $expression->getExpression());
	}

	public function testRenderWithHtmlEntitySingleQuotes()
	{
		// Test that HTML entity encoded single quotes are properly decoded and rendered
		// Expression uses double quotes, so decoded &#039; (') is valid inside
		$expression = new TExpression();
		$expression->setExpression('"it&#039;s working"');
		$expression->setID('Expression10');
		$this->_page->getControls()->add($expression);
		
		// Verify the expression property has the decoded value
		$this->assertEquals('"it\'s working"', $expression->getExpression());
		
		// Now render - the expression should evaluate to the string "it's working"
		$output = $this->render($expression);
		$this->assertEquals("it's working", $output);
	}

	public function testRenderWithHtmlEntityDoubleQuotes()
	{
		// Test that HTML entity encoded double quotes are properly decoded and rendered
		// Expression uses single quotes, so decoded &quot; (") is valid inside
		$expression = new TExpression();
		$expression->setExpression("'she said &quot;hello&quot;'");
		$expression->setID('Expression11');
		$this->_page->getControls()->add($expression);
		
		// Verify the expression property has the decoded value
		$this->assertEquals("'she said \"hello\"'", $expression->getExpression());
		
		// Now render - the expression should evaluate to the string 'she said "hello"'
		$output = $this->render($expression);
		$this->assertEquals('she said "hello"', $output);
	}

	public function testTemplateRenderWithHtmlEntitySingleQuotes()
	{
		// Test template with HTML entity that becomes valid PHP string with single quotes
		// &#039; decodes to ' which is valid inside double-quoted PHP string
		$template = '<com:TExpression ID="Expr10" Expression="&quot;hello&#039;s&quot;" />';
		$page = new TPage();
		$page->setID('TestPage');
		$tpl = $this->newTemplate($template);
		$tpl->instantiateIn($page);
		
		$expression = $page->findControl('Expr10');
		// The expression property should have the decoded value: "hello's"
		$this->assertEquals('"hello\'s"', $expression->getExpression());
		
		$output = $this->render($expression);
		$this->assertEquals("hello's", $output);
	}

	public function testTemplateRenderWithHtmlEntityDoubleQuotes()
	{
		// Test template with HTML entity that becomes valid PHP string with double quotes
		// &quot; decodes to " which is valid inside single-quoted PHP string
		$template = '<com:TExpression ID="Expr11" Expression="&#039;say &quot;hi&quot;&#039;" />';
		$page = new TPage();
		$page->setID('TestPage');
		$tpl = $this->newTemplate($template);
		$tpl->instantiateIn($page);
		
		$expression = $page->findControl('Expr11');
		// The expression property should have the decoded value: 'say "hi"'
		$this->assertEquals("'say \"hi\"'", $expression->getExpression());
		
		$output = $this->render($expression);
		$this->assertEquals('say "hi"', $output);
	}
}
