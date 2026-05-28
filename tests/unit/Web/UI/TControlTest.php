<?php

require_once __DIR__ . '/../../PradoUnitRequires.php';

use Prado\IO\TTextWriter;
use Prado\Web\UI\IAdapterControl;
use Prado\Web\UI\TControl;
use Prado\Web\UI\TControlAdapter;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\TPage;
use Prado\Web\UI\TRenderFilterParameter;
use Prado\Web\UI\WebControls\TLabel;

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
		PradoUnit::invoke($label, 'autoDataBindProperties');
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
}