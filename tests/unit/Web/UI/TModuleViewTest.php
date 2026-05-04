<?php

use Prado\Web\UI\TModuleView;
use Prado\Web\UI\TCompositeControl;
use Prado\Web\UI\TTemplate;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TLabel;
use Prado\Prado;
use Prado\TApplication;
use Prado\TModule;
use PHPUnit\Framework\TestCase;

class TTestModule extends TModule
{
}

class TModuleViewTest extends TestCase
{
	private $_app;
	private $_contextPath;
	private $_page;

	protected function setUp(): void
	{
		$this->_contextPath = sys_get_temp_dir();
		$this->_app = Prado::getApplication();
		$this->_page = new TPage();
		$this->_page->setID('TestPage');
	}

	protected function tearDown(): void
	{
		$this->_contextPath = null;
		$this->_app = null;
		$this->_page = null;
	}

	private function newTemplate($template): TTemplate
	{
		return new TTemplate($template, $this->_contextPath, null, 0, true);
	}

	private function setupModuleView(TModuleView $moduleView): void
	{
		$moduleView->setTemplateControl($this->_page);
		$moduleView->setPage($this->_page);
	}

	private function setModuleAvailable(string $moduleId, bool $available): void
	{
		$ref = new \ReflectionProperty(TApplication::class, '_modules');
		$ref->setAccessible(true);
		$modules = $ref->getValue($this->_app);
		if ($available) {
			if (!isset($modules[$moduleId]) || $modules[$moduleId] === null) {
				$module = new TTestModule();
				$module->setID($moduleId);
				$modules[$moduleId] = $module;
			}
		} else {
			unset($modules[$moduleId]);
		}
		$ref->setValue($this->_app, $modules);
	}

	public function testExtendsCompositeControl()
	{
		$control = new TModuleView();
		$this->assertInstanceOf(TCompositeControl::class, $control);
	}

	public function testModuleIdDefaultEmpty()
	{
		$control = new TModuleView();
		$this->assertEquals('', $control->getModuleId());
	}

	public function testSetModuleId()
	{
		$control = new TModuleView();
		$control->setModuleId('cache');
		$this->assertEquals('cache', $control->getModuleId());
	}

	public function testSetModuleIdEmpty()
	{
		$control = new TModuleView();
		$control->setModuleId('cache');
		$control->setModuleId('');
		$this->assertEquals('', $control->getModuleId());
	}

	public function testGetModuleReturnsNullForUnknownId()
	{
		$control = new TModuleView();
		$control->setModuleId('nonexistent-module-xyz');
		$this->assertNull($control->getModule());
	}

	public function testGetModuleAvailableReturnsFalseForUnknownId()
	{
		$control = new TModuleView();
		$control->setModuleId('nonexistent-module-xyz');
		$this->assertFalse($control->getModuleAvailable());
	}

	public function testGetModuleAvailableReturnsFalseWhenNoModuleIdSet()
	{
		$control = new TModuleView();
		$this->assertFalse($control->getModuleAvailable());
	}

	public function testGetAllowChildControlsReturnsFalseWhenModuleUnavailable()
	{
		$control = new TModuleView();
		$control->setModuleId('nonexistent-module-xyz');
		$this->assertFalse($control->getAllowChildControls());
	}

	public function testFallbackTemplateDefaultNull()
	{
		$control = new TModuleView();
		$this->assertNull($control->getFallbackTemplate());
	}

	public function testSetFallbackTemplate()
	{
		$control = new TModuleView();
		$template = $this->createMock(\Prado\Web\UI\ITemplate::class);
		$control->setFallbackTemplate($template);
		$this->assertSame($template, $control->getFallbackTemplate());
	}

	public function testSetFallbackTemplateNull()
	{
		$control = new TModuleView();
		$template = $this->createMock(\Prado\Web\UI\ITemplate::class);
		$control->setFallbackTemplate($template);
		$control->setFallbackTemplate(null);
		$this->assertNull($control->getFallbackTemplate());
	}

	public function testConditionDefaultTrue()
	{
		$control = new TModuleView();
		$this->assertEquals('true', $control->getCondition());
	}

	public function testConditionEmptyStringBecomesTrue()
	{
		$control = new TModuleView();
		$control->setCondition('');
		$this->assertEquals('true', $control->getCondition());
	}

	public function testConditionIsCaseInsensitive()
	{
		$control = new TModuleView();
		$control->setCondition('TRUE');
		$this->assertEquals('TRUE', $control->getCondition());
		$control->setCondition('FALSE');
		$this->assertEquals('FALSE', $control->getCondition());
	}

	public function testConditionWithSingleQuotes()
	{
		$control = new TModuleView();
		$control->setCondition('value&#039;s'); // HTML entity for single quote
		$this->assertEquals("value's", $control->getCondition());
	}

	public function testConditionWithDoubleQuotes()
	{
		$control = new TModuleView();
		$control->setCondition('value&quot;s'); // HTML entity for double quote
		$this->assertEquals('value"s', $control->getCondition());
	}

	public function testgetIsActiveResetsOnModuleIdChange()
	{
		$control = new TModuleView();
		$control->setModuleId('test-module-a');
		$this->_page->getControls()->add($control);
		$this->setupModuleView($control);
		$this->assertFalse($control->getIsActive());
		$this->setModuleAvailable('test-module-a', true);
		$control->setModuleId('test-module-b'); // trigger reset of module presence
		$control->setModuleId('test-module-a');
		$this->assertTrue($control->getIsActive());
	}

	public function testGetAllowChildControlsTrueWhenModuleAvailable()
	{
		$control = new TModuleView();
		$control->setModuleId('test-module-c');
		$this->setModuleAvailable('test-module-c', true);
		$this->assertTrue($control->getAllowChildControls());
	}

	public function testGetAllowChildControlsFalseWhenModuleUnavailable()
	{
		$control = new TModuleView();
		$control->setModuleId('test-module-d');
		$this->setModuleAvailable('test-module-d', false);
		$this->assertFalse($control->getAllowChildControls());
	}

	public function testGetAllowChildControlsBasedOnModuleIdNotCondition()
	{
		$control = new TModuleView();
		$control->setModuleId('test-module-e');
		$control->setCondition('false');
		$this->setModuleAvailable('test-module-e', true);
		$this->assertTrue($control->getAllowChildControls());
	}

	public function testNoModuleIdMeansModuleUnavailable()
	{
		$control = new TModuleView();
		$control->setModuleId('');
		$this->assertFalse($control->getModuleAvailable());
		$this->assertFalse($control->getIsActive());
	}

	public function testCreateChildControlsClearsControlsWhenInactive_NoFallbackTemplate()
	{
		$moduleView = new TModuleView();
		$moduleView->setID('ModuleView1');
		$moduleView->setModuleId('test-module-j');
		$moduleView->setCondition('false');
		$moduleView->getControls()->add(new TLabel());
		$this->_page->getControls()->add($moduleView);
		$this->setupModuleView($moduleView);
		$this->setModuleAvailable('test-module-j', false);
		$moduleView->createChildControls();
		$this->assertFalse($moduleView->getIsActive());
		$this->assertEquals(0, $moduleView->getControls()->count());
	}

	public function testCreateChildControlsKeepsControlsWhenActive()
	{
		$moduleView = new TModuleView();
		$moduleView->setID('ModuleView2');
		$moduleView->setModuleId('test-module-k');
		$moduleView->setCondition('true');
		$childLabel = new TLabel();
		$childLabel->setID('ChildLabel');
		$moduleView->getControls()->add($childLabel);
		$this->_page->getControls()->add($moduleView);
		$this->setupModuleView($moduleView);
		$this->setModuleAvailable('test-module-k', true);
		$moduleView->createChildControls();
		$this->assertTrue($moduleView->getIsActive());
		$this->assertTrue($moduleView->getControls()->contains($childLabel));
	}

	public function testFallbackTemplateInstantiatedWhenInactive()
	{
		$moduleView = new TModuleView();
		$moduleView->setID('ModuleView3');
		$moduleView->setModuleId('test-module-l');
		$moduleView->setCondition('false');
		$notFoundTpl = $this->newTemplate('<com:TLabel ID="NotFoundLabel" />');
		$moduleView->setFallbackTemplate($notFoundTpl);
		$childLabel = new TLabel();
		$childLabel->setID('ChildLabel');
		$moduleView->getControls()->add($childLabel);
		$this->_page->getControls()->add($moduleView);
		$this->setupModuleView($moduleView);
		$this->setModuleAvailable('test-module-l', false);
		$moduleView->createChildControls();
		$this->assertFalse($moduleView->getIsActive());
		$this->assertNotNull($moduleView->findControl('NotFoundLabel'));
		$this->assertNull($moduleView->findControl('ChildLabel'));
	}

	public function testFallbackTemplateNotUsedWhenActive()
	{
		$moduleView = new TModuleView();
		$moduleView->setID('ModuleView4');
		$moduleView->setModuleId('test-module-m');
		$moduleView->setCondition('true');
		$notFoundTpl = $this->newTemplate('<com:TLabel ID="NotFoundLabel" />');
		$moduleView->setFallbackTemplate($notFoundTpl);
		$childLabel = new TLabel();
		$childLabel->setID('ChildLabel');
		$moduleView->getControls()->add($childLabel);
		$this->_page->getControls()->add($moduleView);
		$this->setupModuleView($moduleView);
		$this->setModuleAvailable('test-module-m', true);
		$moduleView->createChildControls();
		$this->assertTrue($moduleView->getIsActive());
		$this->assertTrue($moduleView->getControls()->contains($childLabel));
		$this->assertNull($moduleView->findControl('NotFoundLabel'));
	}

	public function testModulePresentConditionFalse_FallbackTemplate()
	{
		$moduleView = new TModuleView();
		$moduleView->setID('ModuleView5');
		$moduleView->setModuleId('test-module-n');
		$moduleView->setCondition('false');
		$notFoundTpl = $this->newTemplate('<com:TLabel ID="NotFoundLabel" />');
		$moduleView->setFallbackTemplate($notFoundTpl);
		$this->_page->getControls()->add($moduleView);
		$this->setupModuleView($moduleView);
		$this->setModuleAvailable('test-module-n', true);
		$moduleView->createChildControls();
		$this->assertFalse($moduleView->getIsActive());
		$this->assertNotNull($moduleView->findControl('NotFoundLabel'));
		$this->assertEquals(1, $moduleView->getControls()->count());
	}

	public function testModuleNotPresentConditionTrue_FallbackTemplate()
	{
		$moduleView = new TModuleView();
		$moduleView->setID('ModuleView6');
		$moduleView->setModuleId('test-module-o');
		$moduleView->setCondition('true');
		$notFoundTpl = $this->newTemplate('<com:TLabel ID="NotFoundLabel" />');
		$moduleView->setFallbackTemplate($notFoundTpl);
		$moduleView->getControls()->add(new TLabel());
		$this->_page->getControls()->add($moduleView);
		$this->setupModuleView($moduleView);
		$this->setModuleAvailable('test-module-o', false);
		$moduleView->createChildControls();
		$this->assertFalse($moduleView->getIsActive());
		$this->assertNotNull($moduleView->findControl('NotFoundLabel'));
		$this->assertNull($moduleView->findControl('ChildLabel'));
	}

	public function testModuleNotPresentConditionFalse_FallbackTemplate()
	{
		$moduleView = new TModuleView();
		$moduleView->setID('ModuleView7');
		$moduleView->setModuleId('test-module-p');
		$moduleView->setCondition('false');
		$notFoundTpl = $this->newTemplate('<com:TLabel ID="NotFoundLabel" />');
		$moduleView->setFallbackTemplate($notFoundTpl);
		$moduleView->getControls()->add(new TLabel());
		$this->_page->getControls()->add($moduleView);
		$this->setupModuleView($moduleView);
		$this->setModuleAvailable('test-module-p', false);
		$moduleView->createChildControls();
		$this->assertFalse($moduleView->getIsActive());
		$this->assertNotNull($moduleView->findControl('NotFoundLabel'));
		$this->assertNull($moduleView->findControl('ChildLabel'));
	}

	public function testClearControlsHappensBeforeFallbackTemplate()
	{
		$moduleView = new TModuleView();
		$moduleView->setID('ModuleView8');
		$moduleView->setModuleId('test-module-q');
		$moduleView->setCondition('false');
		$notFoundTpl = $this->newTemplate('<com:TLabel ID="NotFoundLabel1" /><com:TLabel ID="NotFoundLabel2" />');
		$moduleView->setFallbackTemplate($notFoundTpl);
		$moduleView->getControls()->add(new TLabel());
		$this->_page->getControls()->add($moduleView);
		$this->setupModuleView($moduleView);
		$this->setModuleAvailable('test-module-q', false);
		$moduleView->createChildControls();
		$this->assertFalse($moduleView->getIsActive());
		$this->assertNull($moduleView->findControl('ChildLabel'));
		$this->assertNotNull($moduleView->findControl('NotFoundLabel1'));
		$this->assertNotNull($moduleView->findControl('NotFoundLabel2'));
		$this->assertEquals(2, $moduleView->getControls()->count());
	}

	public function testNoModuleId_NoChildrenWhenInactive()
	{
		$moduleView = new TModuleView();
		$moduleView->setID('ModuleView9');
		$moduleView->setModuleId('');
		$moduleView->setCondition('false');
		$moduleView->getControls()->add(new TLabel());
		$this->_page->getControls()->add($moduleView);
		$this->setupModuleView($moduleView);
		$moduleView->createChildControls();
		$this->assertFalse($moduleView->getIsActive());
		$this->assertEquals(0, $moduleView->getControls()->count());
	}

	public function testCreateChildControls_ConditionFalseExpression()
	{
		$moduleView = new TModuleView();
		$moduleView->setID('ModuleView10');
		$moduleView->setModuleId('test-module-r');
		$moduleView->setCondition('1 == 0');
		$notFoundTpl = $this->newTemplate('<com:TLabel ID="NotFoundLabel" />');
		$moduleView->setFallbackTemplate($notFoundTpl);
		$childLabel = new TLabel();
		$childLabel->setID('ChildLabel');
		$moduleView->getControls()->add($childLabel);
		$this->_page->getControls()->add($moduleView);
		$this->setupModuleView($moduleView);
		$this->setModuleAvailable('test-module-r', true);
		$moduleView->createChildControls();
		$this->assertFalse($moduleView->getIsActive());
		$this->assertNull($moduleView->findControl('ChildLabel'));
		$this->assertNotNull($moduleView->findControl('NotFoundLabel'));
	}

	public function testCreateChildControls_ConditionTrueExpression()
	{
		$moduleView = new TModuleView();
		$moduleView->setID('ModuleView11');
		$moduleView->setModuleId('test-module-s');
		$moduleView->setCondition('1 == 1');
		$notFoundTpl = $this->newTemplate('<com:TLabel ID="NotFoundLabel" />');
		$moduleView->setFallbackTemplate($notFoundTpl);
		$childLabel = new TLabel();
		$childLabel->setID('ChildLabel');
		$moduleView->getControls()->add($childLabel);
		$this->_page->getControls()->add($moduleView);
		$this->setupModuleView($moduleView);
		$this->setModuleAvailable('test-module-s', true);
		$moduleView->createChildControls();
		$this->assertTrue($moduleView->getIsActive());
		$this->assertTrue($moduleView->getControls()->contains($childLabel));
		$this->assertNull($moduleView->findControl('NotFoundLabel'));
	}

	public function testCreateControlCollectionAlways()
	{
		$moduleView = new TModuleView();
		$collection = $moduleView->getControls();
		$this->assertInstanceOf(\Prado\Web\UI\TControlCollection::class, $collection);
	}

	public function testGetModuleAvailableReturnsTrueWhenModulePresent()
	{
		$control = new TModuleView();
		$control->setModuleId('test-module-t');
		$this->setModuleAvailable('test-module-t', true);
		$this->assertTrue($control->getModuleAvailable());
	}

	public function testGetModuleAvailableReturnsFalseWhenModuleNotPresent()
	{
		$control = new TModuleView();
		$control->setModuleId('test-module-u');
		$this->setModuleAvailable('test-module-u', false);
		$this->assertFalse($control->getModuleAvailable());
	}

	public function testgetIsActive_WhenConditionFalseModulePresent()
	{
		$moduleView = new TModuleView();
		$moduleView->setID('ModuleView12');
		$moduleView->setModuleId('test-module-v');
		$moduleView->setCondition('false');
		$notFoundTpl = $this->newTemplate('<com:TLabel ID="NotFoundLabel" />');
		$moduleView->setFallbackTemplate($notFoundTpl);
		$this->_page->getControls()->add($moduleView);
		$this->setupModuleView($moduleView);
		$this->setModuleAvailable('test-module-v', true);
		$moduleView->createChildControls();
		$this->assertFalse($moduleView->getIsActive());
		$this->assertNotNull($moduleView->findControl('NotFoundLabel'));
	}

	public function testgetIsActive_WhenConditionTrueModuleNotPresent()
	{
		$moduleView = new TModuleView();
		$moduleView->setID('ModuleView13');
		$moduleView->setModuleId('test-module-w');
		$moduleView->setCondition('true');
		$notFoundTpl = $this->newTemplate('<com:TLabel ID="NotFoundLabel" />');
		$moduleView->setFallbackTemplate($notFoundTpl);
		$childLabel = new TLabel();
		$childLabel->setID('ChildLabel');
		$moduleView->getControls()->add($childLabel);
		$this->_page->getControls()->add($moduleView);
		$this->setupModuleView($moduleView);
		$this->setModuleAvailable('test-module-w', false);
		$moduleView->createChildControls();
		$this->assertFalse($moduleView->getIsActive());
		$this->assertNull($moduleView->findControl('ChildLabel'));
		$this->assertNotNull($moduleView->findControl('NotFoundLabel'));
	}

	// Template-based integration tests using <com:TModuleView ... /> syntax
	// These test the actual template parsing and instantiation in real-world use

	private function createPageWithTModuleView(string $template): array
	{
		$page = new TPage();
		$page->setID('TestPage');
		$tpl = $this->newTemplate($template);
		$tpl->instantiateIn($page);
		return [$page, $tpl];
	}

	public function testTemplate_ModulePresentConditionTrue_ShowsChildren()
	{
		$this->setModuleAvailable('test-tpl-module-1', true);
		$template = '<com:TModuleView ID="MV1" ModuleId="test-tpl-module-1" Condition="true">
			<com:TLabel ID="ChildLabel" Text="Module Content" />
		</com:TModuleView>';
		[$page, $tpl] = $this->createPageWithTModuleView($template);
		$moduleView = $page->findControl('MV1');
		$this->assertTrue($moduleView->getIsActive());
		$this->assertNotNull($moduleView->findControl('ChildLabel'));
	}

	public function testTemplate_ModulePresentConditionFalse_FallbackTemplate()
	{
		$this->setModuleAvailable('test-tpl-module-2', true);
		$template = '<com:TModuleView ID="MV2" ModuleId="test-tpl-module-2" Condition="false">
			<com:TLabel ID="ChildLabel" Text="Module Content" />
		</com:TModuleView>
		<com:TLabel ID="NotFoundLabel" Text="Not Found" />';
		[$page, $tpl] = $this->createPageWithTModuleView($template);
		$moduleView = $page->findControl('MV2');
		$this->setupModuleView($moduleView);
		$moduleView->createChildControls();
		$this->assertFalse($moduleView->getIsActive());
		$this->assertNull($moduleView->findControl('ChildLabel'));
		$this->assertNotNull($page->findControl('NotFoundLabel'));
	}

	public function testTemplate_ModuleNotPresentConditionTrue_FallbackTemplate()
	{
		$this->setModuleAvailable('test-tpl-module-3', false);
		$template = '<com:TModuleView ID="MV3" ModuleId="test-tpl-module-3" Condition="true">
			<com:TLabel ID="ChildLabel" Text="Module Content" />
		</com:TModuleView>
		<com:TLabel ID="NotFoundLabel" Text="Not Found" />';
		[$page, $tpl] = $this->createPageWithTModuleView($template);
		$moduleView = $page->findControl('MV3');
		$this->setupModuleView($moduleView);
		$moduleView->createChildControls();
		$this->assertFalse($moduleView->getIsActive());
		$this->assertNull($moduleView->findControl('ChildLabel'));
		$this->assertNotNull($page->findControl('NotFoundLabel'));
	}

	public function testTemplate_ModuleNotPresentConditionFalse_FallbackTemplate()
	{
		$this->setModuleAvailable('test-tpl-module-4', false);
		$template = '<com:TModuleView ID="MV4" ModuleId="test-tpl-module-4" Condition="false">
			<com:TLabel ID="ChildLabel" Text="Module Content" />
		</com:TModuleView>
		<com:TLabel ID="NotFoundLabel" Text="Not Found" />';
		[$page, $tpl] = $this->createPageWithTModuleView($template);
		$moduleView = $page->findControl('MV4');
		$this->setupModuleView($moduleView);
		$moduleView->createChildControls();
		$this->assertFalse($moduleView->getIsActive());
		$this->assertNull($moduleView->findControl('ChildLabel'));
		$this->assertNotNull($page->findControl('NotFoundLabel'));
	}

	public function testTemplate_ModulePresentConditionTrue_NoChildren()
	{
		$this->setModuleAvailable('test-tpl-module-5', true);
		$template = '<com:TModuleView ID="MV5" ModuleId="test-tpl-module-5" Condition="true" />';
		[$page, $tpl] = $this->createPageWithTModuleView($template);
		$moduleView = $page->findControl('MV5');
		$this->assertTrue($moduleView->getIsActive());
		$this->assertEquals(0, $moduleView->getControls()->count());
	}

	public function testTemplate_ModuleNotPresentConditionTrue_NoChildren_NoFallbackTemplate()
	{
		$this->setModuleAvailable('test-tpl-module-6', false);
		$template = '<com:TModuleView ID="MV6" ModuleId="test-tpl-module-6" Condition="true">
			<com:TLabel ID="ChildLabel" Text="Module Content" />
		</com:TModuleView>';
		[$page, $tpl] = $this->createPageWithTModuleView($template);
		$moduleView = $page->findControl('MV6');
		$this->assertFalse($moduleView->getIsActive());
		$this->assertNull($moduleView->findControl('ChildLabel'));
	}

	public function testTemplate_ModulePresentConditionFalse_NoFallbackTemplate()
	{
		$this->setModuleAvailable('test-tpl-module-7', true);
		$template = '<com:TModuleView ID="MV7" ModuleId="test-tpl-module-7" Condition="false">
			<com:TLabel ID="ChildLabel" Text="Module Content" />
		</com:TModuleView>';
		[$page, $tpl] = $this->createPageWithTModuleView($template);
		$moduleView = $page->findControl('MV7');
		$this->setupModuleView($moduleView);
		$moduleView->createChildControls();
		$this->assertFalse($moduleView->getIsActive());
		$this->assertNull($moduleView->findControl('ChildLabel'));
		$this->assertEquals(0, $moduleView->getControls()->count());
	}

	public function testTemplate_ModuleNotPresentConditionFalse_NoFallbackTemplate()
	{
		$this->setModuleAvailable('test-tpl-module-8', false);
		$template = '<com:TModuleView ID="MV8" ModuleId="test-tpl-module-8" Condition="false">
			<com:TLabel ID="ChildLabel" Text="Module Content" />
		</com:TModuleView>';
		[$page, $tpl] = $this->createPageWithTModuleView($template);
		$moduleView = $page->findControl('MV8');
		$this->assertFalse($moduleView->getIsActive());
		$this->assertNull($moduleView->findControl('ChildLabel'));
		$this->assertEquals(0, $moduleView->getControls()->count());
	}

	public function testTemplate_ConditionExpression_True()
	{
		$this->setModuleAvailable('test-tpl-module-9', true);
		$template = '<com:TModuleView ID="MV9" ModuleId="test-tpl-module-9" Condition="1 == 1">
			<com:TLabel ID="ChildLabel" Text="Module Content" />
		</com:TModuleView>';
		[$page, $tpl] = $this->createPageWithTModuleView($template);
		$moduleView = $page->findControl('MV9');
		$this->assertTrue($moduleView->getIsActive());
		$this->assertNotNull($moduleView->findControl('ChildLabel'));
	}

	public function testTemplate_ConditionExpression_False()
	{
		$this->setModuleAvailable('test-tpl-module-10', true);
		$template = '<com:TModuleView ID="MV10" ModuleId="test-tpl-module-10" Condition="1 == 0">
			<com:TLabel ID="ChildLabel" Text="Module Content" />
		</com:TModuleView>';
		[$page, $tpl] = $this->createPageWithTModuleView($template);
		$moduleView = $page->findControl('MV10');
		$this->setupModuleView($moduleView);
		$moduleView->createChildControls();
		$this->assertFalse($moduleView->getIsActive());
		$this->assertNull($moduleView->findControl('ChildLabel'));
	}

	public function testTemplate_NoModuleId_ConditionFalse()
	{
		$template = '<com:TModuleView ID="MV11" Condition="false">
			<com:TLabel ID="ChildLabel" Text="Module Content" />
		</com:TModuleView>';
		[$page, $tpl] = $this->createPageWithTModuleView($template);
		$moduleView = $page->findControl('MV11');
		$this->setupModuleView($moduleView);
		$moduleView->createChildControls();
		$this->assertFalse($moduleView->getIsActive());
		$this->assertNull($moduleView->findControl('ChildLabel'));
		$this->assertEquals(0, $moduleView->getControls()->count());
	}

	public function testTemplate_NoModuleId_ConditionTrue()
	{
		$template = '<com:TModuleView ID="MV12" Condition="true">
			<com:TLabel ID="ChildLabel" Text="Module Content" />
		</com:TModuleView>';
		[$page, $tpl] = $this->createPageWithTModuleView($template);
		$moduleView = $page->findControl('MV12');
		$this->setupModuleView($moduleView);
		$moduleView->createChildControls();
		$this->assertFalse($moduleView->getIsActive());
		$this->assertNull($moduleView->findControl('ChildLabel'));
	}

	public function testTemplate_NestedChildren_PresentAndActive()
	{
		$this->setModuleAvailable('test-tpl-module-16', true);
		$template = '<com:TModuleView ID="MV16" ModuleId="test-tpl-module-16" Condition="true">
			<com:TLabel ID="Label1" Text="Label 1" />
			<com:TLabel ID="Label2" Text="Label 2" />
			<com:TLabel ID="Label3" Text="Label 3" />
		</com:TModuleView>';
		[$page, $tpl] = $this->createPageWithTModuleView($template);
		$moduleView = $page->findControl('MV16');
		$this->setupModuleView($moduleView);
		$moduleView->createChildControls();
		$this->assertTrue($moduleView->getIsActive());
		$this->assertNotNull($moduleView->findControl('Label1'));
		$this->assertNotNull($moduleView->findControl('Label2'));
		$this->assertNotNull($moduleView->findControl('Label3'));
		$labelCount = 0;
		foreach ($moduleView->getControls() as $control) {
			if ($control instanceof TLabel) {
				$labelCount++;
			}
		}
		$this->assertEquals(3, $labelCount);
	}

	public function testTemplate_NestedChildren_NotPresent()
	{
		$this->setModuleAvailable('test-tpl-module-17', false);
		$template = '<com:TModuleView ID="MV17" ModuleId="test-tpl-module-17" Condition="true">
			<com:TLabel ID="Label1" Text="Label 1" />
			<com:TLabel ID="Label2" Text="Label 2" />
			<com:TLabel ID="Label3" Text="Label 3" />
		</com:TModuleView>
		<com:TLabel ID="OuterLabel" Text="Outer" />';
		[$page, $tpl] = $this->createPageWithTModuleView($template);
		$moduleView = $page->findControl('MV17');
		$this->setupModuleView($moduleView);
		$moduleView->createChildControls();
		$this->assertFalse($moduleView->getIsActive());
		$this->assertNull($moduleView->findControl('Label1'));
		$this->assertNull($moduleView->findControl('Label2'));
		$this->assertNull($moduleView->findControl('Label3'));
		$this->assertEquals(0, $moduleView->getControls()->count());
		$this->assertNotNull($page->findControl('OuterLabel'));
	}
}
