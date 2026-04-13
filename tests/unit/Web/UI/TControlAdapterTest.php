<?php

use Prado\Web\UI\TControl;
use Prado\Web\UI\TControlAdapter;
use Prado\Web\UI\WebControls\TLabel;

class TControlAdapterTest extends PHPUnit\Framework\TestCase
{
	public function testConstruct()
	{
		$control = new TControl();
		$adapter = new TControlAdapter($control);
		$this->assertSame($control, $adapter->getControl());
	}

	public function testGetControl()
	{
		$control = new TControl();
		$adapter = new TControlAdapter($control);
		$this->assertSame($control, $adapter->getControl());
	}

	public function testGetPage()
	{
		$control = new TControl();
		$adapter = new TControlAdapter($control);
		$this->assertNull($adapter->getPage());
		
		$page = new \Prado\Web\UI\TPage();
		$control->setPage($page);
		$this->assertSame($page, $adapter->getPage());
	}

	public function testCreateChildControls()
	{
		$control = new TControl();
		$adapter = new TControlAdapter($control);
		$this->assertFalse($control->getHasChildInitialized());
		$adapter->createChildControls();
		$this->assertFalse($control->getHasChildInitialized());
	}

	public function testLoadState()
	{
		$control = new TControl();
		$control->setViewState('testKey', 'testValue');
		$adapter = new TControlAdapter($control);
		
		$adapter->loadState();
		// loadState in TControl is a no-op; view state preserved
		$this->assertEquals('testValue', $control->getViewState('testKey'));
	}

	public function testSaveState()
	{
		$control = new TControl();
		$control->setViewState('testKey', 'testValue');
		$adapter = new TControlAdapter($control);
		
		$adapter->saveState();
		// saveState in TControl is a no-op; view state preserved
		$this->assertEquals('testValue', $control->getViewState('testKey'));
	}

	public function testOnInit()
	{
		$control = new TControl();
		$adapter = new TControlAdapter($control);
		$called = false;
		$control->OnInit[] = function($sender, $param) use (&$called) {
			$called = true;
		};
		$adapter->onInit(null);
		$this->assertTrue($called);
	}

	public function testOnLoad()
	{
		$control = new TControl();
		$adapter = new TControlAdapter($control);
		$called = false;
		$control->OnLoad[] = function($sender, $param) use (&$called) {
			$called = true;
		};
		$adapter->onLoad(null);
		$this->assertTrue($called);
	}

	public function testOnPreRender()
	{
		$control = new TControl();
		$adapter = new TControlAdapter($control);
		$called = false;
		$control->OnPreRender[] = function($sender, $param) use (&$called) {
			$called = true;
		};
		$adapter->onPreRender(null);
		$this->assertTrue($called);
	}

	public function testOnUnload()
	{
		$control = new TControl();
		$adapter = new TControlAdapter($control);
		$called = false;
		$control->OnUnload[] = function($sender, $param) use (&$called) {
			$called = true;
		};
		$adapter->onUnload(null);
		$this->assertTrue($called);
	}

	public function testRender()
	{
		$label = new TLabel();
		$label->setID('testLabel');
		$label->setText('Adapter Render Test');
		$adapter = new TControlAdapter($label);
		$writer = new \Prado\Web\UI\THtmlWriter(new \Prado\IO\TTextWriter());
		$adapter->render($writer);
		$output = $writer->getWriter()->flush();
		$this->assertStringContainsString('Adapter Render Test', $output);
	}

	public function testRenderChildren()
	{
		$control = new TControl();
		$child = new TLabel();
		$child->setID('child');
		$child->setText('Child Render Test');
		$control->getControls()->add($child);
		$adapter = new TControlAdapter($control);
		$writer = new \Prado\Web\UI\THtmlWriter(new \Prado\IO\TTextWriter());
		$adapter->renderChildren($writer);
		$output = $writer->getWriter()->flush();
		$this->assertStringContainsString('Child Render Test', $output);
	}
}