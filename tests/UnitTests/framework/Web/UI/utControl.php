<?php

require_once(PRADO_DIR.'/Collections/TList.php');
require_once(PRADO_DIR.'/Collections/TMap.php');
require_once(PRADO_DIR.'/Web/UI/TControl.php');

class TContext extends TComponent
{
	public static $_instance=null;
	public function __construct()
	{
		if(self::$_instance)
			throw new Exception('....');
		self::$_instance=$this;
	}

	public static function getInstance()
	{
		return self::$_instance;
	}
}

class ContainerControl extends TControl implements INamingContainer
{
}

class PageControl extends TControl implements INamingContainer
{
	public $eventRaised=false;
	private $_context;

	function __construct($context)
	{
		$this->setPage($this);
		$this->_context=$context;
	}

	public function getContext()
	{
		return $this->_context;
	}

	public function clicked($sender,$param)
	{
		$this->eventRaised=true;
	}

	public function getContainsTheme()
	{
		return false;
	}

	public function runTo($lifecycle)
	{
		switch($lifecycle)
		{
			case 'init':
				$this->initRecursive(null);
				break;
			case 'load':
				$this->initRecursive(null);
				$this->loadRecursive();
				break;
			case 'prerender':
				$this->initRecursive(null);
				$this->loadRecursive();
				$this->preRenderRecursive();
				break;
		}
	}
}

class WebControl extends TControl
{
	public function getText()
	{
		return $this->getViewState('Text','');
	}

	public function setText($value)
	{
		return $this->setViewState('Text',$value,'');
	}

	public function getData()
	{
		return $this->getControlState('Data','');
	}

	public function setData($value)
	{
		$this->setControlState('Data',$value,'');
	}

	public function onClick($param)
	{
		$this->raiseEvent('OnClick',$this,$param);
	}
}

class utControl extends UnitTestCase
{
	private $context;
	private $button1;
	private $button2;
	private $page;
	private $form;
	private $panel;

	public function setUp()
	{
		// we mock up a page consisting of a form which encloses a panel.
		// in the panel there are two buttons, button1 and button2
		// The panel is a naming container
		$this->context=new TContext;
		$this->page=new PageControl($this->context);
		$this->form=new WebControl;
		$this->panel=new ContainerControl;
		$this->button1=new WebControl;
		$this->button2=new WebControl;
		$this->form->setTemplateControl($this->page);
		$this->panel->setTemplateControl($this->page);
		$this->button1->setTemplateControl($this->page);
		$this->button2->setTemplateControl($this->page);
		$this->page->getControls()->add($this->form);
		$this->form->getControls()->add($this->panel);
		$this->panel->getControls()->add($this->button1);
		$this->panel->getControls()->add($this->button2);
		$this->button1->setID('button1');
		$this->page->declareObject('button1',$this->button1);
	}

	public function tearDown()
	{
		$this->page=null;
		$this->form=null;
		$this->panel=null;
		$this->button1=null;
		$this->button2=null;
		$this->context=null;
		TContext::$_instance=null;
	}

	public function testOverload()
	{
		$this->assertEqual($this->page->button1,$this->button1);
		try
		{
			$button=$this->page->button2;
			$this->fail('non exception raised when accessing non-declared control');
		}
		catch(TInvalidOperationException $e)
		{
			$this->pass();
		}
	}

	public function testParent()
	{
		$this->assertEqual(null,$this->page->getParent());
		$this->assertEqual($this->page,$this->form->getParent());
	}

	public function testNamingContainer()
	{
		$this->assertEqual(null,$this->page->getNamingContainer());
		$this->assertEqual($this->page,$this->panel->getNamingContainer());
		$this->assertEqual($this->panel,$this->button1->getNamingContainer());
	}

	public function testPage()
	{
		$this->assertEqual($this->page,$this->page->getPage());
		$this->assertEqual($this->page,$this->panel->getPage());
		$this->assertEqual($this->page,$this->button1->getPage());
	}

	public function testTemplateControl()
	{
		$this->assertEqual(null,$this->page->getTemplateControl());
		$this->assertEqual($this->page,$this->panel->getTemplateControl());
		$this->assertEqual($this->page,$this->button1->getTemplateControl());
	}

	public function testContext()
	{
		$this->assertEqual($this->context,$this->button1->getContext());
	}

	public function testSkinID()
	{
		$this->assertEqual('',$this->button1->getSkinID());
		$this->button1->setSkinID('buttonSkin');
		$this->assertEqual('buttonSkin',$this->button1->getSkinID());
		$this->page->runTo('init');
		try
		{
			$this->button1->setSkinID('buttonSkin2');
			$this->fail('no exception raised when SkinID is set after PreInit');
		}
		catch(TInvalidOperationException $e)
		{
			$this->pass();
		}
	}

	public function testID()
	{
		$this->assertEqual('button1',$this->button1->getID());
		$this->assertEqual('',$this->button2->getID());
		$this->assertEqual('ctl1',$this->button2->getID(false));
		$this->button2->setID('button2');
		$this->assertEqual('button2',$this->button2->getID());
		try
		{
			$this->button2->setID('123');
			$this->fail('exception not raised when control is set with an invalid ID');
		}
		catch(TInvalidDataValueException $e)
		{
			$this->pass();
		}
	}

	public function testUniqueID()
	{
		$sep=TControl::ID_SEPARATOR;
		$this->assertEqual('ctl0',$this->form->getUniqueID());
		$this->assertEqual('ctl1',$this->panel->getUniqueID());
		$this->assertEqual('ctl1'.$sep.'button1',$this->button1->getUniqueID());
		$this->assertEqual('ctl1'.$sep.'ctl1',$this->button2->getUniqueID());
		$this->button2->setID('button2');
		$this->assertEqual('ctl1'.$sep.'button2',$this->button2->getUniqueID());
		$this->panel->setID('panel');
		$this->assertEqual('panel'.$sep.'button2',$this->button2->getUniqueID());
	}

	public function testEnableTheming()
	{
		$this->assertEqual(true,$this->button1->getEnableTheming());
		$this->page->setEnableTheming(false);
		$this->assertEqual(false,$this->button1->getEnableTheming());
		$this->page->setEnableTheming(true);
		$this->assertEqual(true,$this->button1->getEnableTheming());
		$this->button1->setEnableTheming(false);
		$this->assertEqual(false,$this->button1->getEnableTheming());

		$this->page->runTo('init');
		try
		{
			$this->button1->setEnableTheming(true);
			$this->fail('no exception raised when EnableTheming is set after PreInit');
		}
		catch(TInvalidOperationException $e)
		{
			$this->pass();
		}
	}

	public function testHasControls()
	{
		$this->assertEqual(true,$this->page->getHasControls());
		$this->assertEqual(false,$this->button1->getHasControls());
	}

	public function testControls()
	{
		$this->assertEqual(1,$this->page->getControls()->getCount());
		$control=new WebControl;
		$this->panel->getControls()->add($control);
		$this->assertEqual(3,$this->panel->getControls()->getCount());
		$this->panel->getControls()->remove($this->button1);
		$this->assertEqual(2,$this->panel->getControls()->getCount());
	}

	public function testVisible()
	{
		$this->assertEqual(true,$this->button1->getVisible());
		$this->page->setVisible(false);
		$this->assertEqual(false,$this->button1->getVisible());
		$this->page->setVisible(true);
		$this->assertEqual(true,$this->button1->getVisible());
		$this->button1->setVisible(false);
		$this->assertEqual(false,$this->button1->getVisible());
	}

	public function testEnabled()
	{
		$this->assertEqual(true,$this->button1->getEnabled());
		$this->page->setEnabled(false);
		$this->assertEqual(true,$this->button1->getEnabled());
		$this->assertEqual(false,$this->button1->getEnabled(true));
		$this->page->setEnabled(true);
		$this->assertEqual(true,$this->button1->getEnabled(true));
		$this->button1->setEnabled(false);
		$this->assertEqual(false,$this->button1->getEnabled(true));
		$this->assertEqual(false,$this->button1->getEnabled());
	}

	public function testHasAttributes()
	{
		$this->assertEqual(false,$this->button1->getHasAttributes());
		$this->button1->getAttributes()->add('name','value');
		$this->assertEqual(true,$this->button1->getHasAttributes());
		$this->button1->getAttributes()->clear();
		$this->assertEqual(false,$this->button1->getHasAttributes());
	}

	public function testAttributes()
	{
		$this->assertEqual(0,$this->button1->getAttributes()->getCount());
		$this->button1->getAttributes()->add('name1','value1');
		$this->button1->getAttributes()->add('name2','value2');
		$this->assertEqual(2,$this->button1->getAttributes()->getCount());
		$this->button1->getAttributes()->remove('name1');
		$this->assertEqual(1,$this->button1->getAttributes()->getCount());
	}

	public function testEnableViewState()
	{
		$this->assertEqual(true,$this->button1->getEnableViewState());
		$this->button1->setEnableViewState(false);
		$this->assertEqual(false,$this->button1->getEnableViewState());

	}

	public function testViewState()
	{
		$this->assertEqual('',$this->button1->getText());
		$this->button1->setText('abc');
		$this->assertEqual('abc',$this->button1->getText());
	}

	public function testControlState()
	{
		$this->assertEqual('',$this->button1->getData());
		$this->button1->setData('abc');
		$this->assertEqual('abc',$this->button1->getData());
	}

	public function testEventScheme()
	{
		$this->assertEqual(true,$this->button1->hasEvent('OnClick'));
		$this->assertEqual(false,$this->button1->hasEvent('Click'));
		$this->button1->attachEventHandler('OnClick','Page.clicked');
		$this->assertEqual(false,$this->page->eventRaised);
		$this->button1->raiseEvent('OnClick',$this,null);
		$this->assertEqual(true,$this->page->eventRaised);
		$this->button1->getEventHandlers('OnClick')->clear();
		try
		{
			$this->button1->attachEventHandler('Click','clicked');
			$this->fail('no exception raised when undefined event is raised');
		}
		catch(TInvalidOperationException $e)
		{
			$this->pass();
		}
		$this->assertEqual(0,$this->button1->getEventHandlers('OnClick')->getCount());
		$this->button1->attachEventHandler('OnClick','Pages.clicked');
		try
		{
			$this->button1->raiseEvent('OnClick',$this,null);
			$this->fail('no exception raised when undefined event handler is invoked');
		}
		catch(TInvalidOperationException $e)
		{
			$this->pass();
		}
	}

	public function testDataBindingScheme()
	{
		$this->button1->bindProperty('Text','"abc"."def"');
		$this->button1->dataBind();
		$this->assertEqual('abcdef',$this->button1->getText());
		$this->button2->bindProperty('Text','"abc"."def"');
		$this->button2->unbindProperty('Text');
		$this->button2->dataBind();
		$this->assertEqual('',$this->button2->getText());
		$this->button1->bindProperty('Texts','"abc"."def"');
		try
		{
			$this->button1->dataBind();
			$this->fail('no exception raised for invalid databinding');
		}
		catch(TInvalidOperationException $e)
		{
			$this->pass();
		}
	}

	public function testFindControl()
	{
		$this->assertEqual($this->button1,$this->panel->findControl('button1'));
		$this->assertEqual(null,$this->panel->findControl('button2'));
		$this->assertEqual($this->button1,$this->page->findControl($this->panel->getID(false).TControl::ID_SEPARATOR.'button1'));
		$this->button1->setID('button3');
		$this->assertEqual($this->button1,$this->panel->findControl('button3'));
	}

	public function testAddRemoveControl()
	{

	}
}


if(!defined('RUN_ALL_TESTS'))
{
	$className=basename(__FILE__,'.php');
	$test = new $className;
	$test->run(new HtmlReporter());
}

?>