<?php

namespace Prado\Test\Unit\Web\UI;

use Prado\IO\TTextWriter;
use Prado\Prado;
use Prado\Web\UI\IAdapterControl;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Web\UI\TControl;
use Prado\Web\UI\TControlAdapter;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\TPage;
use Prado\Web\UI\TRenderFilterParameter;
use Prado\Web\UI\WebControls\TLabel;
use Prado\Test\Unit\Harness\TTestApplication;
use Prado\Test\Unit\Harness\Web\UI\TNestedPathControl;
use Prado\Test\Unit\PradoUnit;

/**
 * Exposes protected getAdapterControl() for white-box testing.
 */
class TControlExposed extends TControl
{
	public function getAdapterControlPublic(): IAdapterControl
	{
		return $this->getAdapterControl();
	}
}

/**
 * Fixture: implements IRenderable only — not TControl, not IFilterRenderable.
 * Exercises the final IRenderable branch in renderChildren.
 */
class TRenderableNonControl implements \Prado\Web\UI\IRenderable
{
	private string $_text;
	public function __construct(string $text) { $this->_text = $text; }
	public function render($writer): void { $writer->write($this->_text); }
}

/**
 * Fixture: implements IFilterRenderable (via TFilterRenderableTrait) but does not extend TControl.
 * Exercises the non-TControl IFilterRenderable branch in renderChildren.
 */
class TFilterRenderableNonControl extends \Prado\TComponent implements \Prado\Web\UI\IFilterRenderable
{
	use \Prado\Web\UI\Traits\TFilterRenderableTrait;

	private string $_text;
	public function __construct(string $text)
	{
		parent::__construct();
		$this->_text = $text;
	}

	public function render($writer): void { $writer->write($this->_text); }
}

/**
 * Adapter whose render() appends a known marker instead of delegating.
 */
class TControlAdapterSpy extends TControlAdapter
{
	public bool $renderCalled = false;

	public function render($writer): void
	{
		$this->renderCalled = true;
		$writer->write('ADAPTER_OUTPUT');
	}
}

class TControlTestPluginModule extends \Prado\Util\TPluginModule
{
	private $_path;
	public function __construct($path)
	{
		parent::__construct();
		$this->_path = $path;
	}
	public function getPluginPath()
	{
		return $this->_path;
	}
}

class TControlTestPluginControl extends TControl
{
}

/**
 * A naming container control.
 */
class TControlTestContainer extends TControl implements \Prado\Web\UI\INamingContainer
{
}

/**
 * Records the life cycle methods and the bubbled and broadcast events it receives.
 */
class TControlTestRecorder extends TControl implements \Prado\Web\UI\IBroadcastEventReceiver
{
	public array $calls = [];
	public bool $stopBubble = false;

	public function onInit($param)
	{
		$this->calls[] = 'init:' . $this->getID(false);
		parent::onInit($param);
	}

	public function onLoad($param)
	{
		$this->calls[] = 'load:' . $this->getID(false);
		parent::onLoad($param);
	}

	public function onPreRender($param)
	{
		$this->calls[] = 'prerender:' . $this->getID(false);
		parent::onPreRender($param);
	}

	public function onUnload($param)
	{
		$this->calls[] = 'unload:' . $this->getID(false);
		parent::onUnload($param);
	}

	public function bubbleEvent($sender, $param)
	{
		$this->calls[] = 'bubble:' . $this->getID(false);
		return $this->stopBubble;
	}

	public function broadcastEventReceived($sender, $param)
	{
		$this->calls[] = 'broadcast:' . $param->getName() . ':' . $param->getParameter();
	}

	public function bubble($param)
	{
		$this->raiseBubbleEvent($this, $param);
	}
}

/**
 * Throws a PHP Error while it creates its child controls.
 */
class TControlTestErrorChildren extends TControl
{
	public int $attempts = 0;

	public function createChildControls()
	{
		if (++$this->attempts === 1) {
			throw new \Error('create failed');
		}
		$this->getControls()->add(new TControl());
	}
}

/**
 * Throws an exception while it creates its child controls.
 */
class TControlTestExceptionChildren extends TControl
{
	public function createChildControls()
	{
		throw new \Prado\Exceptions\TInvalidOperationException('create failed');
	}
}

class TControlTest extends \PHPUnit\Framework\TestCase
{
	public function testGetPluginModuleFindsModuleSharingClassPath()
	{
		$app = Prado::getApplication();
		$snap = TTestApplication::snapshotApp($app);
		try {
			$module = new TControlTestPluginModule(__DIR__);
			$app->setModule('TControlTestPlugin', $module);
			$control = new TControlTestPluginControl();
			$this->assertSame($module, $control->getPluginModule());
			$this->assertSame($module, $control->getPluginModule());
		} finally {
			TTestApplication::restoreApp($snap, $app);
		}
	}

	public function testGetPluginModuleReturnsNullWhenNoPathMatches()
	{
		$app = Prado::getApplication();
		$snap = TTestApplication::snapshotApp($app);
		try {
			$app->setModule('TControlTestPlugin', new TControlTestPluginModule(__DIR__ . DIRECTORY_SEPARATOR . 'no-such-dir'));
			$control = new TControlTestPluginControl();
			$this->assertNull($control->getPluginModule());
			$this->assertNull(PradoUnit::getProp($control, '_pluginmodule'));
		} finally {
			TTestApplication::restoreApp($snap, $app);
		}
	}

	public function testGetPluginModuleSearchesOnlyOnce()
	{
		$app = Prado::getApplication();
		$snap = TTestApplication::snapshotApp($app);
		try {
			$control = new TControlTestPluginControl();
			$this->assertFalse(PradoUnit::getProp($control, '_pluginmodule'));
			$this->assertNull($control->getPluginModule());
			$app->setModule('TControlTestPlugin', new TControlTestPluginModule(__DIR__));
			$this->assertNull($control->getPluginModule());
		} finally {
			TTestApplication::restoreApp($snap, $app);
		}
	}

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
		PradoUnit::invoke($label, 'autoDataBindProperties');
		$this->assertEquals('auto value', $label->getText());
	}

	/**
	 * All three binding slots write a parent path before any path nested beneath
	 * it, so a nested binding survives whatever order the bindings were declared
	 * in. Each expression still evaluates immediately before its own write.
	 */
	public function testBindingsApplyParentPathBeforeNestedPath()
	{
		$orders = [
			['Cfg.Size' => "'child'", 'Cfg' => "'value'"],
			['Cfg' => "'value'", 'Cfg.Size' => "'child'"],
		];

		// <%# %> — RF_DATA_BINDINGS, applied by dataBind()
		foreach ($orders as $bindings) {
			$control = new TNestedPathControl();
			foreach ($bindings as $path => $expression) {
				$control->bindProperty($path, $expression);
			}
			$control->dataBind();
			$this->assertEquals('child', $control->getCfg()->getSize(), 'dataBind, declared: ' . implode(', ', array_keys($bindings)));
		}

		// <%! %> — RF_INIT_BINDINGS, applied once at the start of initRecursive()
		foreach ($orders as $bindings) {
			$control = new TNestedPathControl();
			foreach ($bindings as $path => $expression) {
				$control->initBindProperty($path, $expression);
			}
			PradoUnit::invoke($control, 'initDataBindProperties');
			$this->assertEquals('child', $control->getCfg()->getSize(), 'initBind, declared: ' . implode(', ', array_keys($bindings)));
		}

		// <%= %> — RF_AUTO_BINDINGS, applied by preRenderRecursive()
		foreach ($orders as $bindings) {
			$control = new TNestedPathControl();
			foreach ($bindings as $path => $expression) {
				$control->autoBindProperty($path, $expression);
			}
			PradoUnit::invoke($control, 'autoDataBindProperties');
			$this->assertEquals('child', $control->getCfg()->getSize(), 'autoBind, declared: ' . implode(', ', array_keys($bindings)));
		}
	}

	/**
	 * An expression is evaluated immediately before its own write, so a later
	 * binding reads what an earlier binding wrote rather than the prior state.
	 */
	public function testBindingExpressionsEvaluateAgainstEarlierWrites()
	{
		$control = new TNestedPathControl();
		$control->setID('probe');
		$control->bindProperty('Cfg', "'value'");
		$control->bindProperty('Cfg.Size', "'saw:' . \$this->getCfg()->getSize()");
		$control->dataBind();
		$this->assertEquals('saw:parent:value', $control->getCfg()->getSize());
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
		PradoUnit::invoke($control, 'initRecursive', null);
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

	/**
	 * Test renderChildren method with onRenderFilter event
	 */
	public function testRenderChildrenWithOnRenderFilter()
	{
		$control = new TControl();
		$child = new TLabel();
		$child->setID('child');
		$child->setText('Child Content');
		$control->getControls()->add($child);

		// Add an onRenderFilter event handler that modifies the output
		$control->onRenderFilter[] = function($sender, $param) {
			$param[TRenderFilterParameter::RENDER_FILTER_TEXT] = strtoupper($param[TRenderFilterParameter::RENDER_FILTER_TEXT]);
		};

		$writer = new \Prado\Web\UI\THtmlWriter(new \Prado\IO\TTextWriter());
		$control->renderControl($writer);
		$output = $writer->getWriter()->flush();
		$this->assertStringContainsString('CHILD CONTENT', $output);
	}

	/**
	 * Test onRenderFilter method directly
	 */
	public function testOnRenderFilter()
	{
		$control = new TControl();
		$output = 'test output';

		// Add an onRenderFilter event handler that modifies the output
		$control->onRenderFilter[] = function($sender, $param) {
			$param[TRenderFilterParameter::RENDER_FILTER_TEXT] = 'modified: ' . $param[TRenderFilterParameter::RENDER_FILTER_TEXT];
		};

		$result = $control->onRenderFilter($output);
		$this->assertEquals('modified: test output', $result);
	}

	/**
	 * Test onRenderFilter with no event handlers
	 */
	public function testOnRenderFilterWithoutHandlers()
	{
		$control = new TControl();
		$output = 'test output';
		$result = $control->onRenderFilter($output);
		$this->assertEquals('test output', $result);
	}

	/**
	 * Test onRenderFilter using getFilterText / setFilterText getter-setter API
	 */
	public function testOnRenderFilterGetterSetterApi()
	{
		$control = new TControl();
		$control->onRenderFilter[] = function ($sender, $param) {
			$html = $param->getFilterText();
			$param->setFilterText(str_replace('world', 'PRADO', $html));
		};

		$result = $control->onRenderFilter('<p>hello world</p>');
		$this->assertStringContainsString('PRADO', $result);
		$this->assertStringNotContainsString('world', $result);
	}

	/**
	 * Test onRenderFilter using the DOM API via walkElements
	 */
	public function testOnRenderFilterDomWalkElements()
	{
		$control = new TControl();
		$control->onRenderFilter[] = function ($sender, $param) {
			$param->walkElements(function (\DOMElement $el, $p) {
				if ($el->tagName === 'img' && !$el->hasAttribute('alt')) {
					$el->setAttribute('alt', 'auto');
				}
			});
		};

		$result = $control->onRenderFilter('<img src="test.png"><p>text</p>');
		$this->assertStringContainsString('alt="auto"', $result);
		$this->assertStringContainsString('<p>text</p>', $result);
	}

	/**
	 * Test that postRaiseEvent converts DOM back to HTML automatically
	 */
	public function testOnRenderFilterDomAutoSyncOnPost()
	{
		$control = new TControl();
		$control->onRenderFilter[] = function ($sender, $param) {
			// Only access DOM — never call getFilterText; postRaiseEvent must sync
			$dom = $param->getFilterDOM();
			$items = $dom->getElementsByTagName('span');
			if ($items->length > 0) {
				$items->item(0)->setAttribute('class', 'highlight');
			}
		};

		$result = $control->onRenderFilter('<span>hello</span>');
		$this->assertStringContainsString('class="highlight"', $result);
	}

	// =========================================================================
	// renderChildren — dispatch branches
	// =========================================================================

	public function testRenderChildrenStringChildWrittenDirectly()
	{
		// String children are written to the writer unchanged.
		$control = new TControl();
		$control->getControls()->add('raw string');

		$tw = new TTextWriter();
		$writer = new THtmlWriter($tw);
		$control->render($writer);
		$this->assertSame('raw string', $tw->flush());
	}

	public function testRenderChildrenIRenderableChildRendered()
	{
		// Plain IRenderable (not TControl, not IFilterRenderable) is rendered with
		// no filter wrapping — output goes directly to the writer.
		$control = new TControl();
		$child = new TRenderableNonControl('renderable content');
		$control->getControls()->add($child);

		$tw = new TTextWriter();
		$writer = new THtmlWriter($tw);
		$control->render($writer);
		$this->assertSame('renderable content', $tw->flush());
	}

	public function testRenderChildrenIFilterRenderableNonTControlNoHandler()
	{
		// IFilterRenderable (non-TControl) with no handler: output goes directly
		// to the writer because preRenderFilter returns null.
		$control = new TControl();
		$child = new TFilterRenderableNonControl('filterable child');
		$control->getControls()->add($child);

		$tw = new TTextWriter();
		$writer = new THtmlWriter($tw);
		$control->render($writer);
		$this->assertSame('filterable child', $tw->flush());
	}

	public function testRenderChildrenIFilterRenderableNonTControlWithHandler()
	{
		// IFilterRenderable (non-TControl) with an onRenderFilter handler on the child.
		// The parent dispatches the capture-and-restore lifecycle using the child's handler.
		$control = new TControl();
		$child = new TFilterRenderableNonControl('filterable');
		$child->onRenderFilter[] = function ($sender, $param) {
			$param[TRenderFilterParameter::RENDER_FILTER_TEXT] =
				strtoupper($param[TRenderFilterParameter::RENDER_FILTER_TEXT]);
		};
		$control->getControls()->add($child);

		$tw = new TTextWriter();
		$writer = new THtmlWriter($tw);
		$control->render($writer);
		$this->assertSame('FILTERABLE', $tw->flush());
	}

	public function testRenderChildrenNoChildren()
	{
		// No children — renderChildren must write nothing.
		$control = new TControl();
		$tw = new TTextWriter();
		$writer = new THtmlWriter($tw);
		$control->render($writer);
		$this->assertSame('', $tw->flush());
	}

	public function testProcessRenderFilterEmptyCaptureRestoresWriter()
	{
		// Handler registered, but the control renders nothing (no children).
		// processRenderFilter must restore the inner writer even when capture is empty,
		// so subsequent writes to $writer still reach the original TTextWriter.
		$control = new TControl(); // no children → render() captures nothing
		$control->onRenderFilter[] = function ($sender, $param) { /* no-op */ };

		$tw = new TTextWriter();
		$writer = new THtmlWriter($tw);
		$control->renderControl($writer);
		$this->assertSame('', $tw->flush(), 'Empty capture must produce no output');

		// Writer must be fully restored: a subsequent write reaches the original TTextWriter.
		$writer->write('after');
		$this->assertSame('after', $tw->flush());
	}

	// =========================================================================
	// renderControl — visibility and no-handler path
	// =========================================================================

	public function testRenderControlSkipsWhenInvisible()
	{
		$control = new TControl();
		$control->setVisible(false);
		$tw = new TTextWriter();
		$writer = new THtmlWriter($tw);
		$control->renderControl($writer);
		$this->assertSame('', $tw->flush(), 'Invisible control must produce no output');
	}

	public function testRenderControlRendersNormallyWithoutHandlers()
	{
		// No onRenderFilter handlers — output goes directly to writer unchanged.
		$control = new TControl();
		$child = new TLabel();
		$child->setText('hello');
		$control->getControls()->add($child);

		$tw = new TTextWriter();
		$writer = new THtmlWriter($tw);
		$control->renderControl($writer);
		$this->assertStringContainsString('hello', $tw->flush());
	}

	public function testRenderControlWithHandlerProducesFilteredOutput()
	{
		$control = new TControl();
		$child = new TLabel();
		$child->setText('hello');
		$control->getControls()->add($child);

		$control->onRenderFilter[] = function ($sender, $param) {
			$param[TRenderFilterParameter::RENDER_FILTER_TEXT] =
				strtoupper($param[TRenderFilterParameter::RENDER_FILTER_TEXT]);
		};

		$tw = new TTextWriter();
		$writer = new THtmlWriter($tw);
		$control->renderControl($writer);
		$output = $tw->flush();
		$this->assertStringContainsString('HELLO', $output);
		$this->assertStringNotContainsString('hello', $output);
	}

	public function testProcessRenderFilterDoesNotWriteWhenOutputIsEmpty()
	{
		// A handler that clears the HTML to '' must result in nothing written to the writer.
		$control = new TControl();
		$child = new TLabel();
		$child->setText('content');
		$control->getControls()->add($child);

		$control->onRenderFilter[] = function ($sender, $param) {
			$param[TRenderFilterParameter::RENDER_FILTER_TEXT] = '';
		};

		$tw = new TTextWriter();
		$writer = new THtmlWriter($tw);
		$control->renderControl($writer);
		$this->assertSame('', $tw->flush(), 'Handler clearing output must produce no bytes in the writer');
	}

	public function testMultipleRenderFilterHandlersChainingModifications()
	{
		$control = new TControl();
		$child = new TLabel();
		$child->setText('base');
		$control->getControls()->add($child);

		$control->onRenderFilter[] = function ($sender, $param) {
			$param[TRenderFilterParameter::RENDER_FILTER_TEXT] .= '-first';
		};
		$control->onRenderFilter[] = function ($sender, $param) {
			$param[TRenderFilterParameter::RENDER_FILTER_TEXT] .= '-second';
		};

		$tw = new TTextWriter();
		$writer = new THtmlWriter($tw);
		$control->renderControl($writer);
		$output = $tw->flush();
		$this->assertStringContainsString('-first', $output);
		$this->assertStringContainsString('-second', $output);
	}

	// =========================================================================
	// getAdapterControl
	// =========================================================================

	public function testGetAdapterControlReturnsSelfWithoutAdapter()
	{
		$control = new TControlExposed();
		$this->assertSame($control, $control->getAdapterControlPublic());
	}

	public function testGetAdapterControlReturnsAdapterWhenSet()
	{
		$control = new TControlExposed();
		$adapter = new TControlAdapter($control);
		$control->setAdapter($adapter);
		$this->assertSame($adapter, $control->getAdapterControlPublic());
	}

	public function testRenderControlDelegatesToAdapterRender()
	{
		$control = new TControl();
		$spy = new TControlAdapterSpy($control);
		$control->setAdapter($spy);

		$tw = new TTextWriter();
		$writer = new THtmlWriter($tw);
		$control->renderControl($writer);

		$this->assertTrue($spy->renderCalled, 'renderControl must call adapter render()');
		$this->assertStringContainsString('ADAPTER_OUTPUT', $tw->flush());
	}

	public function testRenderControlAdapterOutputPassesThroughFilter()
	{
		$control = new TControl();
		$spy = new TControlAdapterSpy($control);
		$control->setAdapter($spy);

		$control->onRenderFilter[] = function ($sender, $param) {
			$param[TRenderFilterParameter::RENDER_FILTER_TEXT] =
				str_replace('ADAPTER', 'FILTERED', $param[TRenderFilterParameter::RENDER_FILTER_TEXT]);
		};

		$tw = new TTextWriter();
		$writer = new THtmlWriter($tw);
		$control->renderControl($writer);
		$output = $tw->flush();

		$this->assertStringContainsString('FILTERED_OUTPUT', $output);
		$this->assertStringNotContainsString('ADAPTER_OUTPUT', $output);
	}

	// =========================================================================
	// preRenderFilter / processRenderFilter — unit-level
	// =========================================================================

	public function testPreRenderFilterReturnsNullWithNoHandlers()
	{
		// Indirectly: if preRenderFilter returned non-null when no handler,
		// processRenderFilter would try to flush an empty inner writer and
		// write '' — meaning any child output would be lost.  Verify children
		// still render (writer was NOT swapped out).
		$control = new TControl();
		$child = new TLabel();
		$child->setText('direct');
		$control->getControls()->add($child);

		$tw = new TTextWriter();
		$writer = new THtmlWriter($tw);
		$control->renderControl($writer);
		// Output must appear in the original writer, not a swapped buffer
		$this->assertStringContainsString('direct', $tw->flush());
	}

	public function testOnRenderFilterReturnsInputUnmodifiedWithNoHandlers()
	{
		$control = new TControl();
		$result = $control->onRenderFilter('<p>unchanged</p>');
		$this->assertSame('<p>unchanged</p>', $result);
	}

	public function testOnRenderFilterHandlerCanUsePassByReferenceOnParam()
	{
		// Verifies the param is the same object received by the handler.
		$control = new TControl();
		$receivedParam = null;
		$control->onRenderFilter[] = function ($sender, $param) use (&$receivedParam) {
			$receivedParam = $param;
		};
		$control->onRenderFilter('<p>test</p>');
		$this->assertInstanceOf(TRenderFilterParameter::class, $receivedParam);
	}

	// -----------------------------------------------------------------------
	// Control tree: naming containers, page, template control
	// -----------------------------------------------------------------------

	public function testGetNamingContainerAsksTheParentAddedBeforeItsContainer()
	{
		$page = new TPage();
		$panel = new TControl();
		$child = new TControl();
		$panel->getControls()->add($child);
		$this->assertNull(PradoUnit::getProp($child, '_namingContainer'));
		$page->getControls()->add($panel);
		$this->assertSame($page, $child->getNamingContainer());
		$this->assertSame($page, $child->getNamingContainer());
	}

	public function testGetNamingContainerResolvesANamingContainerParent()
	{
		$container = new TControlTestContainer();
		$child = new TControl();
		$container->getControls()->add($child);
		// adding sets the naming container; clearing it exercises the lazy lookup
		PradoUnit::setProp($child, '_namingContainer', null);
		$this->assertSame($container, $child->getNamingContainer());
	}

	public function testGetPageAndTemplateControlFollowTheTemplateControlOrTheParent()
	{
		$page = new TPage();
		$template = new TControl();
		$template->setPage($page);
		$control = new TControl();
		$control->setTemplateControl($template);
		$this->assertSame($page, $control->getPage());

		$parent = new TControl();
		$parent->setTemplateControl($template);
		$child = new TControl();
		$parent->getControls()->add($child);
		$this->assertSame($template, $child->getTemplateControl());
		$this->assertNull((new TControl())->getPage());
	}

	public function testGetSourceTemplateControlReturnsTheSourceTemplateControl()
	{
		$source = $this->createMock(\Prado\Web\UI\TTemplateControl::class);
		$source->method('getIsSourceTemplateControl')->willReturn(true);
		$control = new TControl();
		$control->setTemplateControl($source);
		$this->assertSame($source, $control->getSourceTemplateControl());

		$plain = $this->createMock(\Prado\Web\UI\TTemplateControl::class);
		$plain->method('getIsSourceTemplateControl')->willReturn(false);
		$plain->method('getTemplateControl')->willReturn(null);
		$page = new TPage();
		$control = new TControl();
		$control->setTemplateControl($plain);
		$control->setPage($page);
		$this->assertSame($page, $control->getSourceTemplateControl());
	}

	public function testGetPluginModuleSkipsALazyModule()
	{
		$app = Prado::getApplication();
		$snap = TTestApplication::snapshotApp($app);
		try {
			PradoUnit::invoke($app, 'setLazyModule', 'TControlTestLazyPlugin', [TControlTestPluginModule::class, [], null]);
			PradoUnit::setProp($app, '_modules', PradoUnit::getProp($app, '_modules') + ['TControlTestLazyPlugin' => null]);
			$this->assertArrayHasKey('TControlTestLazyPlugin', $app->getModulesByType(\Prado\Util\IPluginModule::class));
			$this->assertNull((new TControlTestPluginControl())->getPluginModule());
		} finally {
			TTestApplication::restoreApp($snap, $app);
		}
	}

	public function testSetControlStage()
	{
		$control = new TControl();
		PradoUnit::invoke($control, 'setControlStage', TControl::CS_LOADED);
		$this->assertSame(TControl::CS_LOADED, PradoUnit::invoke($control, 'getControlStage'));
	}

	// -----------------------------------------------------------------------
	// IDs
	// -----------------------------------------------------------------------

	public function testSetIDRejectsAnInvalidID()
	{
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		(new TControl())->setID('1bad id');
	}

	public function testSetIDClearsTheNamingContainerNameTable()
	{
		$page = new TPage();
		$child = new TControl();
		$child->setID('Old');
		$page->getControls()->add($child);
		$this->assertSame($child, $page->findControl('Old'));
		$child->setID('New');
		$this->assertNull($page->findControl('Old'));
		$this->assertSame($child, $page->findControl('New'));
	}

	public function testGetUniqueIDBelowANamingContainerWithAndWithoutAnID()
	{
		$container = new TControlTestContainer();
		$child = new TControl();
		$container->getControls()->add($child);
		$this->assertSame('ctl0', $child->getUniqueID());
		$this->assertSame('ctl0', $child->getUniqueID());

		$container->setID('Box');
		$this->assertSame('Box:ctl0', $child->getUniqueID());
		$this->assertSame('Box:ctl0', $child->getUniqueID());
	}

	public function testClearCachedUniqueIDReachesTheChildrenOfANamingContainer()
	{
		$page = new TPage();
		$container = new TControlTestContainer();
		$container->setID('A');
		$page->getControls()->add($container);
		$child = new TControl();
		$child->setID('C');
		$container->getControls()->add($child);
		$this->assertSame('A:C', $child->getUniqueID());
		$container->setID('B');
		$this->assertSame('B:C', $child->getUniqueID());
	}

	public function testFocusSetsThePageFocus()
	{
		$page = new TPage();
		$control = new TControl();
		$page->getControls()->add($control);
		$control->focus();
		$this->assertSame($control, PradoUnit::getProp($page, '_focus'));
	}

	// -----------------------------------------------------------------------
	// Skins, theming, enabled, attributes, view state flags
	// -----------------------------------------------------------------------

	public function testSkinIDIsUnchangeableOnceTheSkinIsApplied()
	{
		$control = new TControl();
		$this->assertFalse($control->getIsSkinApplied());
		$control->setSkinID('Big');
		$this->assertSame('Big', $control->getSkinID());
		PradoUnit::setProp($control, '_flags', PradoUnit::getProp($control, '_flags') | TControl::IS_SKIN_APPLIED);
		$this->assertTrue($control->getIsSkinApplied());
		$this->expectException(TInvalidOperationException::class);
		$control->setSkinID('Small');
	}

	public function testSkinIDAndThemingAreUnchangeableAfterChildInitialization()
	{
		$control = new TControl();
		$control->setEnableTheming(false);
		$this->assertFalse($control->getEnableTheming());
		$control->setEnableTheming(true);
		$this->assertTrue($control->getEnableTheming());
		PradoUnit::invoke($control, 'setControlStage', TControl::CS_CHILD_INITIALIZED);
		foreach (['setSkinID' => 'Big', 'setEnableTheming' => false] as $method => $value) {
			try {
				$control->$method($value);
				$this->fail($method . ' was accepted');
			} catch (TInvalidOperationException $e) {
				$this->assertSame($method === 'setSkinID' ? 'control_skinid_unchangeable' : 'control_enabletheming_unchangeable', $e->getErrorCode());
			}
		}
	}

	public function testGetEnabledChecksTheParents()
	{
		$parent = new TControl();
		$child = new TControl();
		$parent->getControls()->add($child);
		$this->assertTrue($child->getEnabled(true));
		$parent->setEnabled(false);
		$this->assertTrue($child->getEnabled());
		$this->assertFalse($child->getEnabled(true));
	}

	public function testGetAndRemoveAttributeWithoutAttributes()
	{
		$control = new TControl();
		$this->assertNull($control->getAttribute('title'));
		$this->assertNull($control->removeAttribute('title'));
		$this->assertFalse($control->getHasAttributes());
	}

	public function testGetEnableViewStateChecksTheParents()
	{
		$parent = new TControl();
		$child = new TControl();
		$parent->getControls()->add($child);
		$this->assertTrue($child->getEnableViewState(true));
		$parent->setEnableViewState(false);
		$this->assertTrue($child->getEnableViewState());
		$this->assertFalse($child->getEnableViewState(true));
		$parent->setEnableViewState(true);
		$this->assertTrue($child->getEnableViewState(true));
	}

	public function testControlStateDefaultValueAndClear()
	{
		$control = new TControl();
		PradoUnit::invoke($control, 'setControlState', 'Key', 'value', 'default');
		$this->assertSame('value', PradoUnit::invoke($control, 'getControlState', 'Key', 'default'));
		PradoUnit::invoke($control, 'setControlState', 'Key', 'default', 'default');
		$this->assertSame('default', PradoUnit::invoke($control, 'getControlState', 'Key', 'default'));
		PradoUnit::invoke($control, 'setControlState', 'Key', 'value', 'default');
		PradoUnit::invoke($control, 'clearControlState', 'Key');
		$this->assertNull(PradoUnit::invoke($control, 'getControlState', 'Key'));
	}

	// -----------------------------------------------------------------------
	// Child controls
	// -----------------------------------------------------------------------

	public function testDataBindReachesTheBindableChildren()
	{
		$parent = new TControl();
		$child = new TControl();
		$child->bindProperty('ID', '"Bound"');
		$parent->getControls()->add($child);
		$parent->getControls()->add('text');
		$parent->dataBind();
		$this->assertSame('Bound', $child->getID());
	}

	public function testChildControlsCreatedFlag()
	{
		$control = new TControl();
		$this->assertFalse(PradoUnit::invoke($control, 'getChildControlsCreated'));
		PradoUnit::invoke($control, 'setChildControlsCreated', false);
		PradoUnit::invoke($control, 'setChildControlsCreated', true);
		$this->assertTrue(PradoUnit::invoke($control, 'getChildControlsCreated'));
		PradoUnit::invoke($control, 'setChildControlsCreated', false);
		$this->assertFalse(PradoUnit::invoke($control, 'getChildControlsCreated'));

		$control->getControls()->add(new TControl());
		PradoUnit::invoke($control, 'setChildControlsCreated', false);
		$this->assertSame(1, $control->getControls()->getCount());
		PradoUnit::invoke($control, 'setChildControlsCreated', true);
		PradoUnit::invoke($control, 'setChildControlsCreated', false);
		$this->assertSame(0, $control->getControls()->getCount());
	}

	public function testEnsureChildControlsMarksTheChildrenCreatedWhenCreationThrows()
	{
		$control = new TControlTestExceptionChildren();
		try {
			$control->ensureChildControls();
			$this->fail('createChildControls() did not throw');
		} catch (TInvalidOperationException $e) {
			$this->assertSame('create failed', $e->getMessage());
		}
		$this->assertTrue(PradoUnit::invoke($control, 'getChildControlsCreated'));
		$control->ensureChildControls();
	}

	public function testEnsureChildControlsRecoversFromAPhpErrorDuringCreation()
	{
		$control = new TControlTestErrorChildren();
		try {
			$control->ensureChildControls();
			$this->fail('createChildControls() did not throw');
		} catch (\Error $e) {
			$this->assertSame('create failed', $e->getMessage());
		}
		$this->assertTrue(PradoUnit::invoke($control, 'getChildControlsCreated'));
		PradoUnit::invoke($control, 'setChildControlsCreated', false);
		$control->ensureChildControls();
		$this->assertSame(2, $control->attempts);
		$this->assertSame(1, $control->getControls()->getCount());
	}

	public function testFindControlByAPathThroughANamingContainer()
	{
		$page = new TPage();
		$container = new TControlTestContainer();
		$container->setID('Box');
		$page->getControls()->add($container);
		$child = new TControl();
		$child->setID('Item');
		$container->getControls()->add($child);
		$this->assertSame($child, $page->findControl('Box.Item'));
		$this->assertSame($child, $page->findControl('Box:Item'));
		$this->assertNull($page->findControl('None.Item'));
	}

	public function testIdSeparatorDefaultsToAColon()
	{
		$this->assertSame(':', TControl::DEFAULT_ID_SEPARATOR);
		$this->assertSame(':', TControl::getIdSeparator());
		$this->assertSame('A_B_C', TControl::convertUniqueIdToClientId('A:B:C'));
	}

	public function testSetIdSeparatorSeparatesUniqueIDsAndControlPaths()
	{
		try {
			TControl::setIdSeparator('$');
			$this->assertSame('$', TControl::getIdSeparator());
			$page = new TPage();
			$container = new TControlTestContainer();
			$container->setID('Box');
			$page->getControls()->add($container);
			$child = new TControl();
			$child->setID('Item');
			$container->getControls()->add($child);
			$this->assertSame('Box$Item', $child->getUniqueID());
			$this->assertSame('Box_Item', $child->getClientID());
			$this->assertSame('A_B_C', TControl::convertUniqueIdToClientId('A$B$C'));
			$this->assertSame($child, $page->findControl('Box$Item'));
			$this->assertSame($child, $page->findControl('Box.Item'));
			$this->assertNull($page->findControl('Box:Item'));
		} finally {
			TControl::setIdSeparator(TControl::DEFAULT_ID_SEPARATOR);
		}
	}

	public static function validIdSeparators(): array
	{
		return [[':'], ['$'], ['|'], ['-'], ['~'], ['!']];
	}

	/**
	 * @dataProvider validIdSeparators
	 */
	public function testSetIdSeparatorTakesOnePunctuationCharacter(string $value)
	{
		try {
			TControl::setIdSeparator($value);
			$this->assertSame($value, TControl::getIdSeparator());
		} finally {
			TControl::setIdSeparator(TControl::DEFAULT_ID_SEPARATOR);
		}
	}

	public static function invalidIdSeparators(): array
	{
		return [
			'empty' => [''],
			'two characters' => ['::'],
			'letter' => ['a'],
			'digit' => ['1'],
			'underscore, an ID character' => ['_'],
			'period, rewritten by PHP' => ['.'],
			'bracket, rewritten by PHP' => ['['],
			'space, rewritten by PHP' => [' '],
			'control character' => ["\n"],
			'multibyte character' => ["\u{00A7}"],
		];
	}

	/**
	 * @dataProvider invalidIdSeparators
	 */
	public function testSetIdSeparatorRefusesAnInvalidCharacter(string $value)
	{
		try {
			TControl::setIdSeparator($value);
			$this->fail('TInvalidDataValueException expected');
		} catch (\Prado\Exceptions\TInvalidDataValueException $e) {
			$this->assertSame('control_idseparator_invalid', $e->getErrorCode());
		}
		$this->assertSame(':', TControl::getIdSeparator());
	}

	public function testIdSeparatorConstantKeepsTheSeparatorOfPrado43()
	{
		$this->assertSame('$', TControl::ID_SEPARATOR);
	}

	public function testFindControlRejectsADuplicateID()
	{
		$page = new TPage();
		foreach ([1, 2] as $n) {
			$control = new TControl();
			$control->setID('Twin');
			$page->getControls()->add($control);
		}
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		$page->findControl('Twin');
	}

	public function testFindControlsByTypeSearchesTheDescendants()
	{
		$root = new TControl();
		$panel = new TControl();
		$label = new TLabel();
		$root->getControls()->add($panel);
		$panel->getControls()->add($label);
		$this->assertSame([$label], $root->findControlsByType(TLabel::class));
		$this->assertSame([$panel, $label], $root->findControlsByType(TControl::class, false));
	}

	public function testRegisterObjectRejectsAName()
	{
		$control = new TControl();
		$control->registerObject('Name', new \stdClass());
		$this->expectException(TInvalidOperationException::class);
		$control->registerObject('Name', new \stdClass());
	}

	public function testClearChildStateAndIsDescendentOf()
	{
		$root = new TControl();
		$panel = new TControl();
		$leaf = new TControl();
		$root->getControls()->add($panel);
		$panel->getControls()->add($leaf);
		$this->assertTrue(PradoUnit::invoke($leaf, 'isDescendentOf', $root));
		$this->assertTrue(PradoUnit::invoke($leaf, 'isDescendentOf', $leaf));
		$this->assertFalse(PradoUnit::invoke($root, 'isDescendentOf', $leaf));

		$state = ['Gone' => [0 => ['Text' => 'x']]];
		$this->loadState($root, $state);
		$this->assertNotEmpty(PradoUnit::getProp($root, '_rf')[TControl::RF_CHILD_STATE] ?? null);
		PradoUnit::invoke($root, 'clearChildState');
		$this->assertArrayNotHasKey(TControl::RF_CHILD_STATE, PradoUnit::getProp($root, '_rf'));
	}

	public function testAddedControlLeavesItsOldParent()
	{
		$old = new TControl();
		$new = new TControl();
		$child = new TControl();
		$old->getControls()->add($child);
		$new->getControls()->add($child);
		$this->assertSame(0, $old->getControls()->getCount());
		$this->assertSame($new, $child->getParent());
	}

	public function testAddedControlCatchesUpWithTheParentStage()
	{
		$page = new TPage();
		$parent = new TControlTestRecorder();
		$parent->setID('P');
		$page->getControls()->add($parent);
		$parent->getControls()->add(new TControl());

		$stages = [
			TControl::CS_CHILD_INITIALIZED => ['init:A'],
			TControl::CS_STATE_LOADED => ['init:B'],
			TControl::CS_LOADED => ['init:C', 'load:C'],
			TControl::CS_PRERENDERED => ['init:D', 'load:D', 'prerender:D'],
		];
		$childState = ['B' => [0 => ['Visible' => false]]];
		PradoUnit::setProp($parent, '_rf', PradoUnit::getProp($parent, '_rf') + [TControl::RF_CHILD_STATE => $childState]);
		foreach ($stages as $stage => $expected) {
			PradoUnit::invoke($parent, 'setControlStage', $stage);
			$child = new TControlTestRecorder();
			$child->setID([TControl::CS_CHILD_INITIALIZED => 'A', TControl::CS_STATE_LOADED => 'B', TControl::CS_LOADED => 'C', TControl::CS_PRERENDERED => 'D'][$stage]);
			$parent->getControls()->add($child);
			$this->assertSame($expected, $child->calls);
			if ($stage === TControl::CS_STATE_LOADED) {
				$this->assertFalse($child->getVisible(false));
				$this->assertArrayNotHasKey('B', PradoUnit::getProp($parent, '_rf')[TControl::RF_CHILD_STATE]);
			}
		}
	}

	public function testInitRecursiveNamesTheChildrenOfANamingContainer()
	{
		$container = new TControlTestContainer();
		$child = new TControl();
		$container->getControls()->add($child);
		$this->assertSame('ctl0', $child->getID(false));
		PradoUnit::setProp($child, '_id', '');
		PradoUnit::invoke($container, 'initRecursive');
		$this->assertSame($container, $child->getNamingContainer());
		$this->assertSame('ctl1', $child->getID(false));
	}

	public function testRecursiveLifeCycleVisitsTheTree()
	{
		$root = new TControlTestRecorder();
		$root->setID('Root');
		$hidden = new TControlTestRecorder();
		$hidden->setID('Hidden');
		$hidden->setVisible(false);
		$leaf = new TControlTestRecorder();
		$leaf->setID('Leaf');
		$root->getControls()->add($hidden);
		$root->getControls()->add('literal');
		$root->getControls()->add($literal = new \Prado\Web\UI\TCompositeLiteral(['plain', [\Prado\Web\UI\TCompositeLiteral::TYPE_EXPRESSION, '"dynamic"']]));
		$hidden->getControls()->add($leaf);
		$auto = new TControlTestRecorder();
		$root->getControls()->add($auto);

		PradoUnit::invoke($root, 'loadRecursive');
		PradoUnit::invoke($root, 'loadRecursive');
		PradoUnit::invoke($root, 'preRenderRecursive');
		PradoUnit::invoke($root, 'unloadRecursive');
		$this->assertSame(['load:Root', 'prerender:Root', 'unload:Root'], $root->calls);
		$this->assertSame(['load:Hidden', 'unload:Hidden'], $hidden->calls);
		$this->assertSame(['load:Leaf', 'unload:Leaf'], $leaf->calls);
		$this->assertSame(['load:', 'prerender:', 'unload:'], $auto->calls);
		$this->assertSame(TControl::CS_PRERENDERED, PradoUnit::invoke($hidden, 'getControlStage'));
		$writer = new THtmlWriter(new TTextWriter());
		$literal->render($writer);
		$this->assertSame('plaindynamic', $writer->flush());
	}

	public function testRaiseBubbleEventStopsAtTheFirstHandlerThatAccepts()
	{
		$top = new TControlTestRecorder();
		$top->setID('Top');
		$middle = new TControlTestRecorder();
		$middle->setID('Middle');
		$leaf = new TControlTestRecorder();
		$top->getControls()->add($middle);
		$middle->getControls()->add($leaf);

		$leaf->bubble(null);
		$this->assertSame(['bubble:Middle'], $middle->calls);
		$this->assertSame(['bubble:Top'], $top->calls);

		$middle->calls = $top->calls = [];
		$middle->stopBubble = true;
		$leaf->bubble(null);
		$this->assertSame(['bubble:Middle'], $middle->calls);
		$this->assertSame([], $top->calls);
	}

	public function testBroadcastEventWithoutAPageStartsAtTheControl()
	{
		$root = new TControlTestRecorder();
		$child = new TControlTestRecorder();
		$root->getControls()->add($child);
		$root->getControls()->add('text');
		$child->broadcastEvent('OnPing', $root, 'data');
		$this->assertSame(['broadcast:OnPing:data'], $child->calls);
		$this->assertSame([], $root->calls);
	}

	public function testTraverseChildControlsCallsThePreAndPostCallbacks()
	{
		$root = new TControl();
		$root->setID('Root');
		$child = new TControl();
		$child->setID('Child');
		$root->getControls()->add($child);
		$root->getControls()->add('text');
		$visits = [];
		PradoUnit::invoke(
			$root,
			'traverseChildControls',
			'p',
			function ($control, $param) use (&$visits) {
				$visits[] = 'pre:' . $control->getID() . ':' . $param;
			},
			function ($control, $param) use (&$visits) {
				$visits[] = 'post:' . $control->getID();
			}
		);
		$this->assertSame(['pre:Root:p', 'pre:Child:p', 'post:Child', 'post:Root'], $visits);
		$visits = [];
		PradoUnit::invoke($root, 'traverseChildControls', 'p');
		$this->assertSame([], $visits);
	}

	// -----------------------------------------------------------------------
	// State
	// -----------------------------------------------------------------------

	public function testSaveAndLoadStateRecursiveRoundTrip()
	{
		[$root, $child, $quiet] = $this->newStateTree();
		$root->setViewState('Title', 'root');
		PradoUnit::invoke($root, 'setControlState', 'Mode', 'edit');
		$child->setViewState('Title', 'child');
		$quiet->setViewState('Title', 'quiet');
		$state = PradoUnit::invoke($root, 'saveStateRecursive');
		$this->assertSame([
			'Child' => [0 => ['Title' => 'child']],
			0 => ['Title' => 'root'],
			1 => ['Mode' => 'edit'],
		], $state);

		[$root, $child, $quiet] = $this->newStateTree();
		$state['Gone'] = [0 => ['Title' => 'gone']];
		$this->loadState($root, $state);
		$this->assertSame('root', $root->getViewState('Title'));
		$this->assertSame('edit', PradoUnit::invoke($root, 'getControlState', 'Mode'));
		$this->assertSame('child', $child->getViewState('Title'));
		$this->assertSame(['Gone' => [0 => ['Title' => 'gone']]], PradoUnit::getProp($root, '_rf')[TControl::RF_CHILD_STATE]);
		$this->assertSame(TControl::CS_STATE_LOADED, PradoUnit::invoke($child, 'getControlStage'));
	}

	public function testSaveStateRecursiveWithoutViewState()
	{
		[$root, $child] = $this->newStateTree();
		$root->setViewState('Title', 'root');
		$child->setViewState('Title', 'child');
		$this->assertSame([], PradoUnit::invoke($root, 'saveStateRecursive', false));
	}

	public function testLoadStateRecursiveWithoutViewStateOrControlState()
	{
		[$root, $child] = $this->newStateTree();
		$root->setViewState('Title', 'kept');
		PradoUnit::invoke($root, 'setControlState', 'Mode', 'edit');
		$state = [0 => ['Title' => 'loaded'], 'Child' => []];
		$this->loadState($root, $state, false);
		$this->assertSame('kept', $root->getViewState('Title'));
		$this->assertNull(PradoUnit::invoke($root, 'getControlState', 'Mode'));

		$state = [1 => ['Mode' => 'view']];
		$this->loadState($root, $state);
		$this->assertNull($root->getViewState('Title'));
		$this->assertSame('view', PradoUnit::invoke($root, 'getControlState', 'Mode'));

		$state = null;
		PradoUnit::invoke($root, 'setControlStage', TControl::CS_CONSTRUCTED);
		$this->loadState($root, $state);
		$this->assertSame('view', PradoUnit::invoke($root, 'getControlState', 'Mode'));
		$this->assertSame(TControl::CS_STATE_LOADED, PradoUnit::invoke($root, 'getControlStage'));
	}

	/**
	 * Calls the protected loadStateRecursive(), which takes the state by reference.
	 * @param TControl $control the control loading the state
	 * @param mixed $state the state to load
	 * @param bool $needViewState whether to load the view state
	 */
	private function loadState(TControl $control, &$state, bool $needViewState = true): void
	{
		\Closure::bind(function () use (&$state, $needViewState) {
			$this->loadStateRecursive($state, $needViewState);
		}, $control, TControl::class)();
	}

	/**
	 * @return array{TControl, TControl, TControl} a root with a child and a child whose view state is disabled
	 */
	private function newStateTree(): array
	{
		$root = new TControl();
		$child = new TControl();
		$child->setID('Child');
		$quiet = new TControl();
		$quiet->setID('Quiet');
		$quiet->setEnableViewState(false);
		$root->getControls()->add($child);
		$root->getControls()->add($quiet);
		$root->getControls()->add('text');
		$root->trackViewState(true);
		$child->trackViewState(true);
		$quiet->trackViewState(true);
		return [$root, $child, $quiet];
	}

	public function testApplyStyleSheetSkinOnlyOnce()
	{
		$page = $this->createMock(TPage::class);
		$page->expects($this->once())->method('applyControlStyleSheet');
		$control = new TControl();
		$control->applyStyleSheetSkin(null);
		$control->applyStyleSheetSkin($page);
		$this->expectException(TInvalidOperationException::class);
		$control->applyStyleSheetSkin($page);
	}

	// -----------------------------------------------------------------------
	// Single-element twins of the multi-element trees above, for path coverage
	// -----------------------------------------------------------------------

	public function testFindControlRejectsADuplicateIDInANestedControl()
	{
		$page = new TPage();
		$twin = new TControl();
		$twin->setID('Twin');
		$panel = new TControl();
		$page->getControls()->add($twin);
		$page->getControls()->add($panel);
		$nested = new TControl();
		$nested->setID('Twin');
		$panel->getControls()->add($nested);
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		$page->findControl('Twin');
	}

	public function testFindControlSkipsTheUnnamedAndTextChildrenOfANestedControl()
	{
		foreach (['plain', 'parent', 'container', 'text'] as $kind) {
			$page = new TPage();
			$panel = new TControl();
			if ($kind === 'text') {
				$panel->getControls()->add('text');
			} else {
				$child = $kind === 'container' ? new TControlTestContainer() : new TControl();
				$panel->getControls()->add($child);
				if ($kind === 'parent') {
					$leaf = new TControl();
					$leaf->setID('Leaf');
					$child->getControls()->add($leaf);
				}
			}
			$panel->setID('Panel');
			$page->getControls()->add($panel);
			$this->assertSame($panel, $page->findControl('Panel'), $kind);
			$this->assertSame($kind === 'parent' ? $leaf : null, $page->findControl('Leaf'), $kind);
		}
	}

	public function testFindControlsByIDOfASingleChild()
	{
		$root = new TControl();
		$other = new TControl();
		$other->setID('Other');
		$root->getControls()->add($other);
		$this->assertSame([], $root->findControlsByID('Item'));

		$root = new TControl();
		$root->getControls()->add('text');
		$this->assertSame([], $root->findControlsByID('Item'));
	}

	public function testRecursiveMethodsOfASingleTextChild()
	{
		foreach ([true, false] as $withID) {
			$recorder = new TControlTestRecorder();
			if ($withID) {
				$recorder->setID('Rec');
			}
			$recorder->getControls()->add('text');
			PradoUnit::invoke($recorder, 'dataBindChildren');
			PradoUnit::invoke($recorder, 'preRenderRecursive');
			PradoUnit::invoke($recorder, 'unloadRecursive');
			$this->assertSame($withID ? ['prerender:Rec', 'unload:Rec'] : ['prerender:', 'unload:'], $recorder->calls);
		}
	}

	public function testRecursiveMethodsOfASingleControlChild()
	{
		$root = new TControl();
		$child = new TControl();
		$child->bindProperty('ID', '"Bound"');
		$root->getControls()->add($child);
		PradoUnit::invoke($root, 'dataBindChildren');
		$this->assertSame('Bound', $child->getID());

		$root = new TControl();
		$root->getControls()->add($literal = new \Prado\Web\UI\TCompositeLiteral([[\Prado\Web\UI\TCompositeLiteral::TYPE_EXPRESSION, '"x"']]));
		PradoUnit::invoke($root, 'preRenderRecursive');
		$this->assertSame(['x'], PradoUnit::getProp($literal, '_items'));

		$root = new TControl();
		$root->getControls();
		PradoUnit::invoke($root, 'dataBindChildren');
		$this->assertFalse($root->getHasControls());
	}

	public function testRaiseBubbleEventWithoutAParent()
	{
		$leaf = new TControlTestRecorder();
		$leaf->bubble(null);
		$this->assertSame([], $leaf->calls);
	}

	public function testRemovedControlOfAnOwnerWithoutANamingContainer()
	{
		$owner = new TControl();
		$child = new TControl();
		$child->setID('Kept');
		$owner->getControls()->add($child);
		$owner->registerObject('Kept', $child);
		$owner->getControls()->remove($child);
		$this->assertSame('Kept', $child->getID());
		$this->assertFalse($owner->isObjectRegistered('Kept'));
	}

	public function testGetUniqueIDAfterTheNamingContainerBecomesThePage()
	{
		$container = new TControlTestContainer();
		$child = new TControl();
		$child->setID('Item');
		$container->getControls()->add($child);
		$this->assertSame('Item', $child->getUniqueID());
		$child->setPage($container);
		$this->assertSame('Item', $child->getUniqueID());
	}

	public function testGetEnabledAndEnableViewStateOfTheControlItself()
	{
		$control = new TControl();
		$control->setEnabled(false);
		$this->assertFalse($control->getEnabled(true));
		$control->setEnableViewState(false);
		$this->assertFalse($control->getEnableViewState(true));
	}

	public function testApplyStyleSheetSkinWithoutAPageAfterItIsApplied()
	{
		$control = new TControl();
		$control->applyStyleSheetSkin($this->createMock(TPage::class));
		$this->expectException(TInvalidOperationException::class);
		$control->applyStyleSheetSkin(null);
	}

	public function testRenderFilterThroughAWriterThatIsNoHtmlWriter()
	{
		$control = new TControl();
		$control->getControls()->add('<b>text</b>');
		$control->onRenderFilter[] = fn ($sender, $param) => $param->setFilterText(strtoupper($param->getFilterText()));
		$writer = new TControlTestPlainWriter();
		$control->renderControl($writer);
		$this->assertSame('<B>TEXT</B>', $writer->getWriter()->flush());

		$empty = new TControl();
		$empty->onRenderFilter[] = fn ($sender, $param) => $param->setFilterText('never');
		$writer = new TControlTestPlainWriter();
		$empty->renderControl($writer);
		$this->assertSame('', $writer->getWriter()->flush());
	}

	public function testDataBindAfterTheLastBindingIsRemoved()
	{
		foreach ([false, true] as $withTemplateControl) {
			$control = new TControl();
			if ($withTemplateControl) {
				$control->setTemplateControl(new TControl());
			}
			$control->setID('Kept');
			$control->bindProperty('ID', '"Bound"');
			$control->unbindProperty('ID');
			$control->dataBind();
			$this->assertSame('Kept', $control->getID());
		}
	}

	public function testRenderFilterOfANonControlChildInAnExistingContext()
	{
		$parent = new TControl();
		$child = new TFilterRenderableNonControl('<i>x</i>');
		$child->onRenderFilter[] = fn ($sender, $param) => $param->setFilterText(strtoupper($param->getFilterText()));
		$parent->getControls()->add($child);
		$writer = new THtmlWriter(new TTextWriter());
		$writer->setRenderFilterContext(new \Prado\Web\UI\TRenderFilterContext());
		$parent->renderChildren($writer);
		$this->assertSame('<I>X</I>', $writer->flush());
	}

	public function testEnsureChildControlsDuringCreationDoesNotRecurse()
	{
		$control = new TControlTestReentrantChildren();
		$control->ensureChildControls();
		$this->assertSame(1, $control->created);
		$this->assertTrue(PradoUnit::invoke($control, 'getChildControlsCreated'));
	}
}

/**
 * Asks for its child controls again while it creates them.
 */
class TControlTestReentrantChildren extends TControl
{
	public int $created = 0;

	public function createChildControls()
	{
		$this->created++;
		$this->ensureChildControls();
	}
}

/**
 * A writer that swaps its inner writer like THtmlWriter but has no render filter context.
 */
class TControlTestPlainWriter implements \Prado\IO\ITextWriter
{
	private \Prado\IO\ITextWriter $_writer;

	public function __construct()
	{
		$this->_writer = new TTextWriter();
	}

	public function getWriter()
	{
		return $this->_writer;
	}

	public function setWriter($writer)
	{
		$this->_writer = $writer;
	}

	public function flush()
	{
		return $this->_writer->flush();
	}

	public function write($str)
	{
		$this->_writer->write($str);
	}

	public function writeLine($str = '')
	{
		$this->_writer->writeLine($str);
	}
}
