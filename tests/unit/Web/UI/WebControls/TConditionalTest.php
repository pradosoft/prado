<?php

use Prado\Web\UI\WebControls\TConditional;
use Prado\Web\UI\TTemplate;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TLabel;
use Prado\Prado;
use PHPUnit\Framework\TestCase;

class TConditionalTest extends TestCase
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

	private function setupConditional(TConditional $conditional): void
	{
		$conditional->setTemplateControl($this->_page);
		$conditional->setPage($this->_page);
	}

	public function testExtendsTControl()
	{
		$control = new TConditional();
		$this->assertInstanceOf(\Prado\Web\UI\TControl::class, $control);
	}

	public function testConditionDefaultTrue()
	{
		$control = new TConditional();
		$this->assertEquals('true', $control->getCondition());
	}

	public function testConditionEmptyStringBecomesTrue()
	{
		$control = new TConditional();
		$control->setCondition('');
		$this->assertEquals('true', $control->getCondition());
	}

	public function testConditionIsCasePreserved()
	{
		$control = new TConditional();
		$control->setCondition('TRUE');
		$this->assertEquals('TRUE', $control->getCondition());
		$control->setCondition('FALSE');
		$this->assertEquals('FALSE', $control->getCondition());
	}

	public function testConditionWithSingleQuotes()
	{
		$control = new TConditional();
		$control->setCondition('value&#039;s');
		$this->assertEquals("value's", $control->getCondition());
	}

	public function testConditionWithDoubleQuotes()
	{
		$control = new TConditional();
		$control->setCondition('value&quot;s');
		$this->assertEquals('value"s', $control->getCondition());
	}

	public function testConditionWithMixedQuotes()
	{
		$control = new TConditional();
		$control->setCondition('He said &#039;Hello &quot;World&quot;&#039;');
		$this->assertEquals('He said \'Hello "World"\'', $control->getCondition());
	}

	public function testTrueTemplateDefaultNull()
	{
		$control = new TConditional();
		$this->assertNull($control->getTrueTemplate());
	}

	public function testFalseTemplateDefaultNull()
	{
		$control = new TConditional();
		$this->assertNull($control->getFalseTemplate());
	}

	public function testCreateChildControls_ConditionFalseExpression()
	{
		$conditional = new TConditional();
		$conditional->setID('Conditional1');
		$conditional->setCondition('1 == 0');
		$falseTemplate = $this->newTemplate('<com:TLabel ID="FalseLabel" Text="False Branch" />');
		$conditional->setFalseTemplate($falseTemplate);
		$conditional->getControls()->add(new TLabel());
		$this->_page->getControls()->add($conditional);
		$this->setupConditional($conditional);
		$conditional->createChildControls();
		$this->assertNotNull($conditional->findControl('FalseLabel'));
		$this->assertEquals(1, $conditional->getControls()->count());
		$this->assertEquals('False Branch', $conditional->findControl('FalseLabel')->getText());
	}

	public function testCreateChildControls_ConditionTrueExpression()
	{
		$conditional = new TConditional();
		$conditional->setID('Conditional2');
		$conditional->setCondition('1 == 1');
		$trueTemplate = $this->newTemplate('<com:TLabel ID="TrueLabel" Text="True Branch" />');
		$conditional->setTrueTemplate($trueTemplate);
		$conditional->getControls()->add(new TLabel());
		$this->_page->getControls()->add($conditional);
		$this->setupConditional($conditional);
		$conditional->createChildControls();
		$this->assertNotNull($conditional->findControl('TrueLabel'));
		$this->assertEquals(1, $conditional->getControls()->count());
		$this->assertEquals('True Branch', $conditional->findControl('TrueLabel')->getText());
	}
	
	public function testCreateChildControls_NoTemplateControl()
	{
		$conditional = new TConditional();
		$conditional->setID('Conditional2');
		$conditional->setCondition('1 == 1');
		$trueTemplate = $this->newTemplate('<com:TLabel ID="TrueLabel" Text="True Branch" />');
		$falseTemplate = $this->newTemplate('<com:TLabel ID="FalseLabel" Text="False Branch" />');
		$conditional->setTrueTemplate($trueTemplate);
		$conditional->setFalseTemplate($falseTemplate);
		$conditional->getControls()->add(new TLabel());
		$this->_page->getControls()->add($conditional);
		{	// $this->setupConditional($conditional);
			//$conditional->setTemplateControl($this->_page);
			$conditional->setPage($this->_page);
		}
		$conditional->createChildControls();
		$this->assertNull($conditional->findControl('TrueLabel'));
		$this->assertNull($conditional->findControl('FalseLabel'));
		$this->assertEquals(0, $conditional->getControls()->count());
	}
	
	public function testCreateChildControls_NoPage()
	{
		$conditional = new TConditional();
		$conditional->setID('Conditional2');
		$conditional->setCondition('1 == 1');
		$trueTemplate = $this->newTemplate('<com:TLabel ID="TrueLabel" Text="True Branch" />');
		$falseTemplate = $this->newTemplate('<com:TLabel ID="FalseLabel" Text="False Branch" />');
		$conditional->setTrueTemplate($trueTemplate);
		$conditional->setFalseTemplate($falseTemplate);
		$conditional->getControls()->add(new TLabel());
		$this->_page->getControls()->add($conditional);
		{	// $this->setupConditional($conditional);
			$conditional->setTemplateControl($this->_page);
			//$conditional->setPage($this->_page);
		}
		$conditional->createChildControls();
		$this->assertNotNull($conditional->findControl('TrueLabel'));
		$this->assertNull($conditional->findControl('FalseLabel'));
		$this->assertEquals(1, $conditional->getControls()->count());
	}
	
	public function testCreateChildControls_NoPageNoTemplateControl()
	{
		$conditional = new TConditional();
		$conditional->setID('Conditional2');
		$conditional->setCondition('1 == 1');
		$trueTemplate = $this->newTemplate('<com:TLabel ID="TrueLabel" Text="True Branch" />');
		$falseTemplate = $this->newTemplate('<com:TLabel ID="FalseLabel" Text="False Branch" />');
		$conditional->setTrueTemplate($trueTemplate);
		$conditional->setFalseTemplate($falseTemplate);
		$conditional->getControls()->add(new TLabel());
		$this->_page->getControls()->add($conditional);
		{	// $this->setupConditional($conditional);
			//$conditional->setTemplateControl($this->_page);
			//$conditional->setPage($this->_page);
		}
		$conditional->createChildControls();
		$this->assertNull($conditional->findControl('TrueLabel'));
		$this->assertNull($conditional->findControl('FalseLabel'));
		$this->assertEquals(0, $conditional->getControls()->count());
	}

	public function testCreateChildControls_BothTemplatesTrueCondition()
	{
		$conditional = new TConditional();
		$conditional->setID('Conditional3');
		$conditional->setCondition('true');
		$trueTemplate = $this->newTemplate('<com:TLabel ID="TrueLabel" Text="True Branch" />');
		$falseTemplate = $this->newTemplate('<com:TLabel ID="FalseLabel" Text="False Branch" />');
		$conditional->setTrueTemplate($trueTemplate);
		$conditional->setFalseTemplate($falseTemplate);
		$conditional->getControls()->add(new TLabel());
		$this->_page->getControls()->add($conditional);
		$this->setupConditional($conditional);
		$conditional->createChildControls();
		$this->assertNotNull($conditional->findControl('TrueLabel'));
		$this->assertNull($conditional->findControl('FalseLabel'));
		$this->assertEquals(1, $conditional->getControls()->count());
	}

	public function testCreateChildControls_BothTemplatesFalseCondition()
	{
		$conditional = new TConditional();
		$conditional->setID('Conditional4');
		$conditional->setCondition('false');
		$trueTemplate = $this->newTemplate('<com:TLabel ID="TrueLabel" Text="True Branch" />');
		$falseTemplate = $this->newTemplate('<com:TLabel ID="FalseLabel" Text="False Branch" />');
		$conditional->setTrueTemplate($trueTemplate);
		$conditional->setFalseTemplate($falseTemplate);
		$conditional->getControls()->add(new TLabel());
		$this->_page->getControls()->add($conditional);
		$this->setupConditional($conditional);
		$conditional->createChildControls();
		$this->assertNull($conditional->findControl('TrueLabel'));
		$this->assertNotNull($conditional->findControl('FalseLabel'));
		$this->assertEquals(1, $conditional->getControls()->count());
	}

	public function testCreateChildControls_NoTemplates()
	{
		$conditional = new TConditional();
		$conditional->setID('Conditional5');
		$conditional->setCondition('true');
		$conditional->getControls()->add(new TLabel());
		$this->_page->getControls()->add($conditional);
		$this->setupConditional($conditional);
		$conditional->createChildControls();
		if ($conditional->getControls()->count()) {
			$this->assertNull($conditional->getControls()[0], 'Control should have no children after createChildrenControls');
		}
		$this->assertEquals(0, $conditional->getControls()->count(), 'There should be no controls');
	}

	private function createPageWithTConditional(string $template): array
	{
		$page = new TPage();
		$page->setID('TestPage');
		$tpl = $this->newTemplate($template);
		$tpl->instantiateIn($page);
		return [$page, $tpl];
	}

	public function testTemplate_ConditionTrue_ShowsTrueTemplate()
	{
		$template = '<com:TConditional ID="Cond1" Condition="true">
			<prop:TrueTemplate>
				<com:TLabel ID="TrueLabel" Text="True Content" />
			</prop:TrueTemplate>
			<prop:FalseTemplate>
				<com:TLabel ID="FalseLabel" Text="False Content" />
			</prop:FalseTemplate>
		</com:TConditional>';
		[$page, $tpl] = $this->createPageWithTConditional($template);
		$conditional = $page->findControl('Cond1');
		$this->setupConditional($conditional);
		$conditional->createChildControls();
		$this->assertNotNull($conditional->findControl('TrueLabel'));
		$this->assertNull($conditional->findControl('FalseLabel'));
		$this->assertEquals('True Content', $conditional->findControl('TrueLabel')->getText());
	}

	public function testTemplate_ConditionFalse_ShowsFalseTemplate()
	{
		$template = '<com:TConditional ID="Cond2" Condition="false">
			<prop:TrueTemplate>
				<com:TLabel ID="TrueLabel" Text="True Content" />
			</prop:TrueTemplate>
			<prop:FalseTemplate>
				<com:TLabel ID="FalseLabel" Text="False Content" />
			</prop:FalseTemplate>
		</com:TConditional>';
		[$page, $tpl] = $this->createPageWithTConditional($template);
		$conditional = $page->findControl('Cond2');
		$this->setupConditional($conditional);
		$conditional->createChildControls();
		$this->assertNotNull($conditional->findControl('FalseLabel'));
		$this->assertNull($conditional->findControl('TrueLabel'));
		$this->assertEquals('False Content', $conditional->findControl('FalseLabel')->getText());
	}

	public function testTemplate_ConditionExpression_True()
	{
		$template = '<com:TConditional ID="Cond3" Condition="1 == 1">
			<prop:TrueTemplate>
				<com:TLabel ID="TrueLabel" Text="True Content" />
			</prop:TrueTemplate>
			<prop:FalseTemplate>
				<com:TLabel ID="FalseLabel" Text="False Content" />
			</prop:FalseTemplate>
		</com:TConditional>';
		[$page, $tpl] = $this->createPageWithTConditional($template);
		$conditional = $page->findControl('Cond3');
		$this->setupConditional($conditional);
		$conditional->createChildControls();
		$this->assertNotNull($conditional->findControl('TrueLabel'));
		$this->assertNull($conditional->findControl('FalseLabel'));
	}

	public function testTemplate_ConditionExpression_False()
	{
		$template = '<com:TConditional ID="Cond4" Condition="1 == 0">
			<prop:TrueTemplate>
				<com:TLabel ID="TrueLabel" Text="True Content" />
			</prop:TrueTemplate>
			<prop:FalseTemplate>
				<com:TLabel ID="FalseLabel" Text="False Content" />
			</prop:FalseTemplate>
		</com:TConditional>';
		[$page, $tpl] = $this->createPageWithTConditional($template);
		$conditional = $page->findControl('Cond4');
		$this->setupConditional($conditional);
		$conditional->createChildControls();
		$this->assertNotNull($conditional->findControl('FalseLabel'));
		$this->assertNull($conditional->findControl('TrueLabel'));
	}

	public function testTemplate_ConditionWithSingleQuotes()
	{
		$template = "<com:TConditional ID=\"Cond5\" Condition='&#039;value&#039; === &#039;value&#039;'>
			<prop:TrueTemplate>
				<com:TLabel ID=\"TrueLabel\" Text=\"Single Quote Content\" />
			</prop:TrueTemplate>
			<prop:FalseTemplate>
				<com:TLabel ID=\"FalseLabel\" Text=\"False Content\" />
			</prop:FalseTemplate>
		</com:TConditional>";
		[$page, $tpl] = $this->createPageWithTConditional($template);
		$conditional = $page->findControl('Cond5');
		$this->assertEquals("'value' === 'value'", $conditional->getCondition());
		$this->setupConditional($conditional);
		$conditional->createChildControls();
		$this->assertNotNull($conditional->findControl('TrueLabel'));
		$this->assertNull($conditional->findControl('FalseLabel'));
	}
	
	public function testTemplate_ConditionWithSingleQuotes_DoubleWithin()
	{
		$template = "<com:TConditional ID=\"Cond5\" Condition='\"value\" === \"value\"'>
			<prop:TrueTemplate>
				<com:TLabel ID=\"TrueLabel\" Text=\"Single Quote Content\" />
			</prop:TrueTemplate>
			<prop:FalseTemplate>
				<com:TLabel ID=\"FalseLabel\" Text=\"False Content\" />
			</prop:FalseTemplate>
		</com:TConditional>";
		[$page, $tpl] = $this->createPageWithTConditional($template);
		$conditional = $page->findControl('Cond5');
		$this->assertEquals('"value" === "value"', $conditional->getCondition());
		$this->setupConditional($conditional);
		$conditional->createChildControls();
		$this->assertNotNull($conditional->findControl('TrueLabel'));
		$this->assertNull($conditional->findControl('FalseLabel'));
	}

	public function testTemplate_ConditionWithDoubleQuotes()
	{
		$template = '<com:TConditional ID="Cond6" Condition="&quot;value&quot; === &quot;value&quot;">
			<prop:TrueTemplate>
				<com:TLabel ID="TrueLabel" Text="Double Quote Content" />
			</prop:TrueTemplate>
			<prop:FalseTemplate>
				<com:TLabel ID="FalseLabel" Text="False Content" />
			</prop:FalseTemplate>
		</com:TConditional>';
		[$page, $tpl] = $this->createPageWithTConditional($template);
		$conditional = $page->findControl('Cond6');
		$this->assertEquals('"value" === "value"', $conditional->getCondition());
		$this->setupConditional($conditional);
		$conditional->createChildControls();
		$this->assertNotNull($conditional->findControl('TrueLabel'));
		$this->assertNull($conditional->findControl('FalseLabel'));
	}
	
	public function testTemplate_ConditionWithDoubleQuotes_SingleWithin()
	{
		$template = '<com:TConditional ID="Cond6" Condition="\'value\' === \'value\'">
			<prop:TrueTemplate>
				<com:TLabel ID="TrueLabel" Text="Double Quote Content" />
			</prop:TrueTemplate>
			<prop:FalseTemplate>
				<com:TLabel ID="FalseLabel" Text="False Content" />
			</prop:FalseTemplate>
		</com:TConditional>';
		[$page, $tpl] = $this->createPageWithTConditional($template);
		$conditional = $page->findControl('Cond6');
		$this->assertEquals("'value' === 'value'", $conditional->getCondition());
		$this->setupConditional($conditional);
		$conditional->createChildControls();
		$this->assertNotNull($conditional->findControl('TrueLabel'));
		$this->assertNull($conditional->findControl('FalseLabel'));
	}

	public function testTemplate_ConditionWithMixedQuotes()
	{
		$template = '<com:TConditional ID="Cond7" Condition="He said &#039;Hello &quot;World&quot;&#039;">
			<prop:TrueTemplate>
				<com:TLabel ID="TrueLabel" Text="Mixed Quote Content" />
			</prop:TrueTemplate>
			<prop:FalseTemplate>
				<com:TLabel ID="FalseLabel" Text="False Content" />
			</prop:FalseTemplate>
		</com:TConditional>';
		[$page, $tpl] = $this->createPageWithTConditional($template);
		$conditional = $page->findControl('Cond7');
		$this->assertEquals('He said \'Hello "World"\'', $conditional->getCondition());
		$this->setupConditional($conditional);
	}

	public function testTemplate_EmptyConditionDefaultsToTrue()
	{
		$template = '<com:TConditional ID="Cond8" Condition="">
			<prop:TrueTemplate>
				<com:TLabel ID="TrueLabel" Text="Empty Condition Content" />
			</prop:TrueTemplate>
			<prop:FalseTemplate>
				<com:TLabel ID="FalseLabel" Text="False Content" />
			</prop:FalseTemplate>
		</com:TConditional>';
		[$page, $tpl] = $this->createPageWithTConditional($template);
		$conditional = $page->findControl('Cond8');
		$this->assertEquals('true', $conditional->getCondition());
		$this->setupConditional($conditional);
		$conditional->createChildControls();
		$this->assertNotNull($conditional->findControl('TrueLabel'));
		$this->assertNull($conditional->findControl('FalseLabel'));
	}

	public function testCreateChildControls_EmptyConditionTrue()
	{
		$conditional = new TConditional();
		$conditional->setID('Conditional6');
		$conditional->setCondition('');
		$trueTemplate = $this->newTemplate('<com:TLabel ID="TrueLabel" Text="True Branch" />');
		$conditional->setTrueTemplate($trueTemplate);
		$conditional->getControls()->add(new TLabel());
		$this->_page->getControls()->add($conditional);
		$this->setupConditional($conditional);
		$conditional->createChildControls();
		$this->assertEquals('true', $conditional->getCondition());
		$this->assertNotNull($conditional->findControl('TrueLabel'));
		$this->assertEquals(1, $conditional->getControls()->count());
	}

	public function testCreateChildControls_NoTrueTemplateWhenConditionFalse()
	{
		$conditional = new TConditional();
		$conditional->setID('Conditional7');
		$conditional->setCondition('false');
		$falseTemplate = $this->newTemplate('<com:TLabel ID="FalseLabel" Text="False Branch" />');
		$conditional->setFalseTemplate($falseTemplate);
		$conditional->getControls()->add(new TLabel());
		$this->_page->getControls()->add($conditional);
		$this->setupConditional($conditional);
		$conditional->createChildControls();
		$this->assertNotNull($conditional->findControl('FalseLabel'));
		$this->assertEquals(1, $conditional->getControls()->count());
	}
}