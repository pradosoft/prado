<?php

use Prado\Web\UI\TControl;
use Prado\Web\UI\TControlAdapter;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TLabel;

class TControlTest extends PHPUnit\Framework\TestCase
{
	public function testConstruct()
	{
		$control = new TControl();
		$this->assertEquals(TControl::CS_CONSTRUCTED, $control->getControlStage());
	}

	public function test__get()
	{
		$control = new TControl();
		$this->expectException(\Prado\Exceptions\TInvalidOperationException::class);
		$control->NonExistentProperty;
	}

	public function testGetHasAdapter()
	{
		$control = new TControl();
		$this->assertFalse($control->getHasAdapter());
		
		$control->setAdapter(new TControlAdapter($control));
		$this->assertTrue($control->getHasAdapter());
	}

	public function testSetAndGetAdapter()
	{
		$control = new TControl();
		$adapter = new TControlAdapter($control);
		$control->setAdapter($adapter);
		$this->assertSame($adapter, $control->getAdapter());
	}

	public function testGetParent()
	{
		$control = new TControl();
		$this->assertNull($control->getParent());
		
		$parent = new TControl();
		$parent->getControls()->add($control);
		$this->assertSame($parent, $control->getParent());
	}

	public function testGetNamingContainer()
	{
		$control = new TControl();
		$this->assertNull($control->getNamingContainer());
		
		$page = new TPage();
		$page->getControls()->add($control);
		$this->assertSame($page, $control->getNamingContainer());
	}

	public function testSetAndGetPage()
	{
		$control = new TControl();
		$this->assertNull($control->getPage());
		
		$page = new TPage();
		$control->setPage($page);
		$this->assertSame($page, $control->getPage());
	}

	public function testSetAndGetTemplateControl()
	{
		$control = new TControl();
		$this->assertNull($control->getTemplateControl());
		
		$templateControl = new TControl();
		$templateControl->setID('template');
		$control->setTemplateControl($templateControl);
		$this->assertSame($templateControl, $control->getTemplateControl());
	}

	public function testGetSourceTemplateControl()
	{
		$control = new TControl();
		$this->assertNull($control->getSourceTemplateControl());
	}

	public function testGetControlStage()
	{
		$control = new TControl();
		$this->assertEquals(TControl::CS_CONSTRUCTED, $control->getControlStage());
	}

	public function testSetAndGetID()
	{
		$control = new TControl();
		$this->assertEquals('', $control->getID());
		
		$control->setID('testId');
		$this->assertEquals('testId', $control->getID());
		
		$control->setID('anotherId');
		$this->assertEquals('anotherId', $control->getID());
	}

	public function testGetUniqueID()
	{
		$control = new TControl();
		$control->setID('testId');
		$control->setPage(new TPage());
		$uniqueId = $control->getUniqueID();
		$this->assertStringContainsString('testId', $uniqueId);
	}

	public function testGetClientID()
	{
		$control = new TControl();
		$control->setID('testId');
		$control->setPage(new TPage());
		$clientId = $control->getClientID();
		$this->assertEquals('testId', $clientId);
	}

	public function testSetAndGetSkinID()
	{
		$control = new TControl();
		$this->assertEquals('', $control->getSkinID());
		
		$control->setSkinID('testSkin');
		$this->assertEquals('testSkin', $control->getSkinID());
	}

	public function testSetAndGetEnableTheming()
	{
		$control = new TControl();
		$this->assertTrue($control->getEnableTheming());
		
		$control->setEnableTheming(false);
		$this->assertFalse($control->getEnableTheming());
	}

	public function testSetAndGetCustomData()
	{
		$control = new TControl();
		$this->assertNull($control->getCustomData());
		
		$control->setCustomData('testValue');
		$this->assertEquals('testValue', $control->getCustomData());
		
		$control->setCustomData(['key' => 'val']);
		$this->assertEquals(['key' => 'val'], $control->getCustomData());
	}

	public function testGetHasControls()
	{
		$control = new TControl();
		$this->assertFalse($control->getHasControls());
		
		$child = new TControl();
		$control->getControls()->add($child);
		$this->assertTrue($control->getHasControls());
	}

	public function testGetControls()
	{
		$control = new TControl();
		$this->assertCount(0, $control->getControls());
		$this->assertSame($control->getControls(), $control->getControls());
	}

	public function testSetAndGetVisible()
	{
		$control = new TControl();
		$this->assertTrue($control->getVisible());
		
		$control->setVisible(false);
		$this->assertFalse($control->getVisible());
	}

	public function testSetAndGetEnabled()
	{
		$control = new TControl();
		$this->assertTrue($control->getEnabled());
		
		$control->setEnabled(false);
		$this->assertFalse($control->getEnabled());
	}

	public function testGetHasAttributes()
	{
		$control = new TControl();
		$this->assertFalse($control->getHasAttributes());
		
		$control->setAttribute('test', 'value');
		$this->assertTrue($control->getHasAttributes());
	}

	public function testGetAttributes()
	{
		$control = new TControl();
		$attrs = $control->getAttributes();
		$this->assertSame($attrs, $control->getAttributes()); // same instance on repeated calls
		$attrs->add('class', 'my-class');
		$this->assertEquals('my-class', $control->getAttribute('class'));
	}

	public function testHasAttribute()
	{
		$control = new TControl();
		$this->assertFalse($control->hasAttribute('test'));
		
		$control->setAttribute('test', 'value');
		$this->assertTrue($control->hasAttribute('test'));
	}

	public function testSetAndGetAttribute()
	{
		$control = new TControl();
		$control->setAttribute('test', 'value');
		$this->assertEquals('value', $control->getAttribute('test'));
		
		$control->setAttribute('test', 'newValue');
		$this->assertEquals('newValue', $control->getAttribute('test'));
	}

	public function testRemoveAttribute()
	{
		$control = new TControl();
		$control->setAttribute('test', 'value');
		$this->assertEquals('value', $control->removeAttribute('test'));
		$this->assertNull($control->getAttribute('test'));
		$this->assertNull($control->removeAttribute('nonexistent'));
	}

	public function testSetAndGetEnableViewState()
	{
		$control = new TControl();
		$this->assertTrue($control->getEnableViewState());
		
		$control->setEnableViewState(false);
		$this->assertFalse($control->getEnableViewState());
	}

	public function testTrackViewState()
	{
		$control = new TControl();
		$control->trackViewState(true);
		$control->setViewState('testKey', 'testValue');
		$this->assertEquals('testValue', $control->getViewState('testKey'));
	}

	public function testSetAndGetViewState()
	{
		$control = new TControl();
		$control->setViewState('testKey', 'testValue');
		$this->assertEquals('testValue', $control->getViewState('testKey'));
		
		$this->assertNull($control->getViewState('nonexistent'));
		$this->assertEquals('defaultVal', $control->getViewState('nonexistent', 'defaultVal'));
	}

	public function testClearViewState()
	{
		$control = new TControl();
		$control->setViewState('testKey', 'testValue');
		$this->assertEquals('testValue', $control->getViewState('testKey'));
		$control->clearViewState('testKey');
		$this->assertNull($control->getViewState('testKey'));
	}

	public function testBindProperty()
	{
		$label = new TLabel();
		$label->bindProperty('Text', "'bound value'");
		$label->dataBind();
		$this->assertEquals('bound value', $label->getText());
	}

	public function testUnbindProperty()
	{
		$label = new TLabel();
		$label->bindProperty('Text', "'bound value'");
		$label->unbindProperty('Text');
		$label->dataBind();
		$this->assertEquals('', $label->getText()); // unchanged from default since binding was removed
	}

	public function testAutoBindProperty()
	{
		$label = new TLabel();
		$label->autoBindProperty('Text', "'auto value'");
		$ref = new ReflectionObject($label);
		$method = $ref->getMethod('autoDataBindProperties');
		$method->setAccessible(true);
		$method->invoke($label);
		$this->assertEquals('auto value', $label->getText());
	}

	public function testDataBind()
	{
		$control = new TControl();
		$called = false;
		$control->OnDataBinding[] = function($sender, $param) use (&$called) {
			$called = true;
		};
		$control->dataBind();
		$this->assertTrue($called);
	}

	public function testEnsureChildControls()
	{
		$control = new class extends TControl {
			public int $createChildCount = 0;
			public function createChildControls(): void
			{
				$this->createChildCount++;
			}
		};
		$this->assertEquals(0, $control->createChildCount);
		$control->ensureChildControls();
		$this->assertEquals(1, $control->createChildCount);
		// Second call must be a no-op
		$control->ensureChildControls();
		$this->assertEquals(1, $control->createChildCount);
	}

	public function testCreateChildControls()
	{
		$control = new TControl();
		$this->assertFalse($control->getHasChildInitialized());
		$control->createChildControls();
		// createChildControls is a no-op in base TControl, ensureChildControls sets the flag
		$this->assertFalse($control->getHasChildInitialized());
	}

	public function testFindControl()
	{
		$page = new TPage();
		$control = new TControl();
		$control->setID('parent');
		$page->getControls()->add($control);
		$child = new TControl();
		$child->setID('child');
		$control->getControls()->add($child);
		
		$found = $control->findControl('child');
		$this->assertSame($child, $found);
	}

	public function testFindControlsByType()
	{
		$control = new TControl();
		$label = new TLabel();
		$child = new TControl();
		$control->getControls()->add($label);
		$control->getControls()->add($child);
		
		$found = $control->findControlsByType(TLabel::class);
		$this->assertCount(1, $found);
		$this->assertInstanceOf(TLabel::class, $found[0]);
	}

	public function testFindControlsByID()
	{
		$control = new TControl();
		$child = new TControl();
		$child->setID('testControl');
		$control->getControls()->add($child);
		
		$found = $control->findControlsByID('testControl');
		$this->assertCount(1, $found);
		$this->assertSame($child, $found[0]);
	}

	public function testClearNamingContainer()
	{
		// TCompositeControl implements INamingContainer so children receive auto-generated IDs.
		// Pass false to getID() to retrieve auto-generated IDs (they are hidden by default).
		$container = new \Prado\Web\UI\TCompositeControl();
		$child = new TControl();
		$container->getControls()->add($child);
		$firstAutoId = $child->getID(false); // e.g. "ctl0"
		$this->assertNotEmpty($firstAutoId);

		// clearNamingContainer resets the auto-ID counter
		$container->clearNamingContainer();
		$container->getControls()->remove($child);

		// New control added after reset gets the same first auto-ID
		$child2 = new TControl();
		$container->getControls()->add($child2);
		$this->assertEquals($firstAutoId, $child2->getID(false));
	}

	public function testRegisterObject()
	{
		$control = new TControl();
		$control->setID('test');
		$this->assertFalse($control->isObjectRegistered('testObj'));
		$control->registerObject('testObj', $control);
		$this->assertTrue($control->isObjectRegistered('testObj'));
	}

	public function testUnregisterObject()
	{
		$control = new TControl();
		$control->setID('test');
		$control->registerObject('testObj', $control);
		$this->assertTrue($control->isObjectRegistered('testObj'));
		$control->unregisterObject('testObj');
		$this->assertFalse($control->isObjectRegistered('testObj'));
	}

	public function testIsObjectRegistered()
	{
		$control = new TControl();
		$control->setID('test');
		$this->assertFalse($control->isObjectRegistered('testObj'));
		
		$control->registerObject('testObj', $control);
		$this->assertTrue($control->isObjectRegistered('testObj'));
	}

	public function testGetHasChildInitialized()
	{
		$control = new TControl();
		$this->assertFalse($control->getHasChildInitialized()); // CS_CONSTRUCTED < CS_CHILD_INITIALIZED
		// initRecursive() advances the stage to at least CS_CHILD_INITIALIZED
		$ref = new ReflectionObject($control);
		$method = $ref->getMethod('initRecursive');
		$method->setAccessible(true);
		$method->invoke($control, null);
		$this->assertTrue($control->getHasChildInitialized());
	}

	public function testGetHasInitialized()
	{
		$control = new TControl();
		$this->assertFalse($control->getHasInitialized());
	}

	public function testGetHasLoadedPostData()
	{
		$control = new TControl();
		$this->assertFalse($control->getHasLoadedPostData());
	}

	public function testGetHasLoaded()
	{
		$control = new TControl();
		$this->assertFalse($control->getHasLoaded());
	}

	public function testGetHasPreRendered()
	{
		$control = new TControl();
		$this->assertFalse($control->getHasPreRendered());
	}

	public function testGetRegisteredObject()
	{
		$control = new TControl();
		$control->setID('test');
		$obj = new \stdClass();
		$control->registerObject('testObj', $obj);
		$this->assertSame($obj, $control->getRegisteredObject('testObj'));
	}

	public function testGetAllowChildControls()
	{
		$control = new TControl();
		$this->assertTrue($control->getAllowChildControls());
	}

	public function testAddParsedObject()
	{
		$control = new TControl();
		$this->assertFalse($control->getHasControls());
		$child = new TControl();
		$control->addParsedObject($child);
		$this->assertTrue($control->getHasControls());
		$this->assertSame($child, $control->getControls()[0]);
	}

	public function testAddedControl()
	{
		$control = new TControl();
		$this->assertFalse($control->getHasControls());
		$child = new TControl();
		$control->getControls()->add($child);
		$this->assertTrue($control->getHasControls());
	}

	public function testRemovedControl()
	{
		$control = new TControl();
		$child = new TControl();
		$control->getControls()->add($child);
		$this->assertTrue($control->getHasControls());
		$control->getControls()->remove($child);
		$this->assertFalse($control->getHasControls());
	}

	public function testOnInit()
	{
		$control = new TControl();
		$called = false;
		$control->OnInit[] = function($sender, $param) use (&$called) {
			$called = true;
		};
		$control->onInit(null);
		$this->assertTrue($called);
	}

	public function testOnLoad()
	{
		$control = new TControl();
		$called = false;
		$control->OnLoad[] = function($sender, $param) use (&$called) {
			$called = true;
		};
		$control->onLoad(null);
		$this->assertTrue($called);
	}

	public function testOnDataBinding()
	{
		$control = new TControl();
		$called = false;
		$control->OnDataBinding[] = function($sender, $param) use (&$called) {
			$called = true;
		};
		$control->onDataBinding(null);
		$this->assertTrue($called);
	}

	public function testOnUnload()
	{
		$control = new TControl();
		$called = false;
		$control->OnUnload[] = function($sender, $param) use (&$called) {
			$called = true;
		};
		$control->onUnload(null);
		$this->assertTrue($called);
	}

	public function testOnPreRender()
	{
		$control = new TControl();
		$called = false;
		$control->OnPreRender[] = function($sender, $param) use (&$called) {
			$called = true;
		};
		$control->onPreRender(null);
		$this->assertTrue($called);
	}

	public function testBubbleEvent()
	{
		$control = new TControl();
		$this->assertFalse($control->bubbleEvent($control, null));
	}

	public function testBroadcastEvent()
	{
		$page = new TPage();
		$control = new TControl();
		$control->setID('broadcaster');
		$page->getControls()->add($control);

		$received = false;
		// broadcastEvent delivers to controls that define the named event method
		$receiver = new class extends TControl {
			public function onBroadcastTest($param)
			{
				$this->raiseEvent('OnBroadcastTest', $this, $param);
			}
		};
		$receiver->setID('receiver');
		$receiver->OnBroadcastTest[] = function($sender, $param) use (&$received) {
			$received = true;
		};
		$page->getControls()->add($receiver);

		$control->broadcastEvent('OnBroadcastTest', $control, null);
		$this->assertTrue($received);
	}

	public function testRenderControl()
	{
		$control = new TControl();
		$control->setVisible(true);
		$writer = new \Prado\Web\UI\THtmlWriter(new \Prado\IO\TTextWriter());
		$control->renderControl($writer);
		$this->assertEquals('', $writer->getWriter()->flush());
	}

	public function testRender()
	{
		$label = new TLabel();
		$label->setID('testLabel');
		$label->setText('Hello World');
		$writer = new \Prado\Web\UI\THtmlWriter(new \Prado\IO\TTextWriter());
		$label->render($writer);
		$output = $writer->getWriter()->flush();
		$this->assertStringContainsString('Hello World', $output);
	}

	public function testRenderChildren()
	{
		$control = new TControl();
		$child = new TLabel();
		$child->setID('child');
		$child->setText('Child Content');
		$control->getControls()->add($child);
		
		$writer = new \Prado\Web\UI\THtmlWriter(new \Prado\IO\TTextWriter());
		$control->renderChildren($writer);
		$output = $writer->getWriter()->flush();
		$this->assertStringContainsString('Child Content', $output);
	}

	public function testSaveState()
	{
		$control = new TControl();
		$control->setViewState('testKey', 'testValue');
		$this->assertEquals('testValue', $control->getViewState('testKey'));
		$control->saveState();
		// saveState is a no-op in base TControl; view state is still accessible
		$this->assertEquals('testValue', $control->getViewState('testKey'));
	}

	public function testLoadState()
	{
		$control = new TControl();
		$control->setViewState('testKey', 'testValue');
		$this->assertEquals('testValue', $control->getViewState('testKey'));
		$control->loadState();
		// loadState is a no-op in base TControl; view state is still accessible
		$this->assertEquals('testValue', $control->getViewState('testKey'));
	}

	public function testApplyStyleSheetSkin()
	{
		$control = new TControl();
		$control->setID('testControl');
		$this->assertEquals('', $control->getSkinID());
		$page = new TPage();
		$control->applyStyleSheetSkin($page);
		// applyStyleSheetSkin with no theme should not change skin ID
		$this->assertEquals('', $control->getSkinID());
	}
}