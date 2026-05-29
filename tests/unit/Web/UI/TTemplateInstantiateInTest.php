<?php

use Prado\TComponent;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Web\Javascripts\TJavaScriptLiteral;
use Prado\Web\UI\TCompositeLiteral;
use Prado\Web\UI\TControl;
use Prado\Web\UI\TPage;
use Prado\Web\UI\TTemplate;
use Prado\Web\UI\WebControls\TButton;
use Prado\Web\UI\WebControls\TLabel;
use Prado\Web\UI\WebControls\TOutputCache;
use Prado\Web\UI\WebControls\TPanel;

class TTemplateMagicComponent extends TComponent
{
	public $receivedProperties = [];

	public function __set($name, $value)
	{
		$this->receivedProperties[$name] = $value;
		return true;
	}

	public function hasProperty($name)
	{
		return true;
	}
}

class TTemplateMagicControl extends TControl
{
	public $receivedProperties = [];

	public function __set($name, $value)
	{
		$this->receivedProperties[$name] = $value;
		return true;
	}

	public function hasProperty($name)
	{
		return parent::hasProperty($name) || true;
	}
}

class TTemplateDashComponent extends TComponent
{
	private $_data = [];

	public function setDataToggle($value)
	{
		$this->_data['dataToggle'] = $value;
	}
	public function getDataToggle()
	{
		return $this->_data['dataToggle'] ?? null;
	}
	public function setForeColor($value)
	{
		$this->_data['foreColor'] = $value;
	}
	public function getForeColor()
	{
		return $this->_data['foreColor'] ?? null;
	}
	public function setSubProp()
	{
	}
	public function getSubProp()
	{
		return $this;
	}
	public function setText($value)
	{
		$this->_data['text'] = $value;
	}
	public function getText()
	{
		return $this->_data['text'] ?? null;
	}
	public function setId($value)
	{
		$this->_data['id'] = $value;
	}
	public function getId()
	{
		return $this->_data['id'] ?? null;
	}
}

class TTemplateDashControl extends TControl
{
	private $_data = [];

	public function setDataToggle($value)
	{
		$this->_data['dataToggle'] = $value;
	}
	public function getDataToggle()
	{
		return $this->_data['dataToggle'] ?? null;
	}
	public function setForeColor($value)
	{
		$this->_data['foreColor'] = $value;
	}
	public function getForeColor()
	{
		return $this->_data['foreColor'] ?? null;
	}
	public function setText($value)
	{
		$this->_data['text'] = $value;
	}
	public function getText()
	{
		return $this->_data['text'] ?? null;
	}
}

class TTemplateInstantiateInTestComponent extends TComponent
{
	private $_customProp;

	public function setCustomProp($v)
	{
		$this->_customProp = $v;
	}
	public function getCustomProp()
	{
		return $this->_customProp;
	}
}

class TTemplateInstantiateInJsControl extends TControl
{
	private $_jsClick;

	public function setJsClick($v)
	{
		$this->_jsClick = $v;
	}
	public function getJsClick()
	{
		return $this->_jsClick;
	}
}

class TTemplateInstantiateInNoAllowChildControl extends TControl
{
	public function getAllowChildControls()
	{
		return false;
	}
}

class TTemplateInstantiateInAcceptingControl extends TControl
{
	public array $parsedObjects = [];

	public function addParsedObject($object)
	{
		$this->parsedObjects[] = $object;
		if ($object instanceof TControl) {
			$this->getControls()->add($object);
		}
	}
}

/**
 * TControl with an ItemTemplate property that accepts a TTemplate instance.
 * Used to verify CONFIG_TEMPLATE instantiation sets a nested TTemplate on the property.
 */
class TTemplateInstantiateInTemplateControl extends TControl
{
	private $_itemTemplate;

	public function setItemTemplate($tpl)
	{
		$this->_itemTemplate = $tpl;
	}
	public function getItemTemplate()
	{
		return $this->_itemTemplate;
	}
}

// Test helper: a render-capable accepting control for end-to-end instantiateIn + render tests
class TTemplateInstantiateInRenderAcceptingControl extends TTemplateInstantiateInAcceptingControl
{
	public function renderAll(): string
	{
		// Lightweight in-test writer compatible with TCompositeLiteral::render
		$writer = new class () implements \Prado\IO\ITextWriter {
			public $buffer = '';
			public function write($str)
			{
				$this->buffer .= $str;
			}
			public function writeLine($str = '')
			{
				$this->buffer .= $str . PHP_EOL;
			}
			public function flush()
			{
				$out = $this->buffer;
				$this->buffer = '';
				return $out;
			}
		};
		foreach ($this->parsedObjects as $obj) {
			if (is_string($obj)) {
				$writer->write($obj);
			} elseif ($obj instanceof TCompositeLiteral) {
				$obj->evaluateDynamicContent();
				$obj->render($writer);
			} elseif (method_exists($obj, 'render')) {
				$obj->render($writer);
			}
		}
		return $writer->buffer;
	}
}

class TTemplateInstantiateInTest extends PHPUnit\Framework\TestCase
{
	private $_contextPath;

	protected function setUp(): void
	{
		$this->_contextPath = sys_get_temp_dir();
	}

	protected function tearDown(): void
	{
		$this->_contextPath = null;
	}

	private function newTemplate($template, $contextPath = null, $tplFile = null, $startingLine = 0, $sourceTemplate = true)
	{
		return new TTemplate($template, $contextPath ?? $this->_contextPath, $tplFile, $startingLine, $sourceTemplate);
	}

	private function newTemplateUnvalidated($template)
	{
		$ref = PradoUnit::reflectionClass(TTemplate::class);
		$tplObj = $ref->newInstanceWithoutConstructor();
		$props = [
			'_sourceTemplate' => true,
			'_contextPath' => $this->_contextPath,
			'_tplFile' => null,
			'_startingLine' => 0,
			'_content' => $template,
			'_attributevalidation' => false,
			'_hashCode' => md5($template),
		];
		foreach ($props as $name => $val) {
			PradoUnit::setProp($tplObj, $name, $val);
		}
		$ref->getParentClass()->getParentClass()->getConstructor()->invoke($tplObj);
		PradoUnit::invoke($tplObj, 'parse', $template);
		PradoUnit::setProp($tplObj, '_content', null);
		return $tplObj;
	}

	private function createPage()
	{
		$page = new TPage();
		$page->setID('TestPage');
		return $page;
	}

	private function createControlWithPage()
	{
		$page = $this->createPage();
		$control = new TControl();
		$control->setID('tplControl');
		$page->getControls()->add($control);
		return $control;
	}

	private function createAcceptingControlWithPage()
	{
		$page = $this->createPage();
		$control = new TTemplateInstantiateInAcceptingControl();
		$control->setID('tplControl');
		$page->getControls()->add($control);
		return $control;
	}

	// -----------------------------------------------------------------------
	// Plain text and basic instantiation
	// -----------------------------------------------------------------------

	public function testInstantiateInPlainText()
	{
		$tpl = $this->newTemplate('Hello World');
		$parent = $this->createControlWithPage();
		$tpl->instantiateIn($parent);
		$this->assertCount(1, $parent->getControls());
		$this->assertEquals('Hello World', $parent->getControls()[0]);
	}

	public function testInstantiateInEmptyTemplate()
	{
		$tpl = $this->newTemplate('');
		$parent = $this->createControlWithPage();
		$tpl->instantiateIn($parent);
		$this->assertCount(0, $parent->getControls());
	}

	public function testInstantiateInWhitespaceOnly()
	{
		$tpl = $this->newTemplate('   ');
		$parent = $this->createControlWithPage();
		$tpl->instantiateIn($parent);
		$this->assertCount(1, $parent->getControls());
		$this->assertEquals('   ', $parent->getControls()[0]);
	}

	// -----------------------------------------------------------------------
	// Separate parent control
	// -----------------------------------------------------------------------

	public function testInstantiateInSeparateParentControl()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1" Text="Hello" />');
		$tplControl = $this->createControlWithPage();
		$parentControl = new TControl();
		$parentControl->setID('parent');
		$tplControl->getPage()->getControls()->add($parentControl);
		$tpl->instantiateIn($tplControl, $parentControl);
		$this->assertCount(1, $parentControl->getControls());
		$this->assertCount(0, $tplControl->getControls());
		$label = $parentControl->getControls()[0];
		$this->assertInstanceOf(TLabel::class, $label);
		$this->assertEquals('Hello', $label->getText());
		$this->assertSame($tplControl, $label->getTemplateControl());
	}

	public function testInstantiateInSeparateParentControlWithText()
	{
		$tpl = $this->newTemplate('Hello');
		$tplControl = $this->createControlWithPage();
		$parentControl = new TControl();
		$parentControl->setID('parent');
		$tplControl->getPage()->getControls()->add($parentControl);
		$tpl->instantiateIn($tplControl, $parentControl);
		$this->assertCount(1, $parentControl->getControls());
		$this->assertEquals('Hello', $parentControl->getControls()[0]);
		$this->assertCount(0, $tplControl->getControls());
	}

	public function testInstantiateInSeparateParentCompositeLiteral()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1">a<%= $this->X %>b</com:TLabel>');
		$tplControl = $this->createControlWithPage();
		$parentControl = new TControl();
		$parentControl->setID('parent');
		$tplControl->getPage()->getControls()->add($parentControl);
		$tpl->instantiateIn($tplControl, $parentControl);
		$this->assertCount(1, $parentControl->getControls());
		$label = $parentControl->getControls()[0];
		$this->assertCount(1, $label->getControls());
		$this->assertInstanceOf(TCompositeLiteral::class, $label->getControls()[0]);
	}

	// -----------------------------------------------------------------------
	// TControl component instantiation
	// -----------------------------------------------------------------------

	public function testInstantiateInTControlComponent()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1" Text="Hello" />');
		$parent = $this->createControlWithPage();
		$tpl->instantiateIn($parent);
		$this->assertCount(1, $parent->getControls());
		$label = $parent->getControls()[0];
		$this->assertInstanceOf(TLabel::class, $label);
		$this->assertInstanceOf(TControl::class, $label);
		$this->assertEquals('Hello', $label->getText());
		$this->assertEquals('lbl1', $label->getID());
		$this->assertCount(0, $label->getControls());
		$this->assertSame($parent, $label->getParent());
	}

	// -----------------------------------------------------------------------
	// TComponent (non-control) instantiation
	// -----------------------------------------------------------------------

	public function testInstantiateInTComponentNonControlViaAcceptingParent()
	{
		$tpl = $this->newTemplateUnvalidated('<com:TTemplateInstantiateInTestComponent ID="c1" />');
		$tplControl = $this->createAcceptingControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->parsedObjects);
		$comp = $tplControl->parsedObjects[0];
		$this->assertInstanceOf(TTemplateInstantiateInTestComponent::class, $comp);
		$this->assertNotInstanceOf(TControl::class, $comp);
	}

	public function testInstantiateInTComponentDirectChildFailsInTControlCollection()
	{
		$tpl = $this->newTemplateUnvalidated('<com:TTemplateInstantiateInTestComponent ID="c1" />');
		$tplControl = $this->createControlWithPage();
		$this->expectException(TInvalidDataTypeException::class);
		$tpl->instantiateIn($tplControl);
	}

	// -----------------------------------------------------------------------
	// Comment rendering (valid and invalid comment forms)
	// -----------------------------------------------------------------------

	// Valid PRADO HTML comments - testing "<!-- --!>"

	public function testInstantiateIn_Valid_PradoHtmlComment()
	{
		$tpl = $this->newTemplateUnvalidated('before<!-- --!>after');
		$tplCtl = new TTemplateInstantiateInRenderAcceptingControl();
		$page = $this->createPage();
		$tplCtl->setID('tplRender');
		$page->getControls()->add($tplCtl);
		$tpl->instantiateIn($tplCtl);
		$rendered = $tplCtl->renderAll();
		$this->assertEquals('before<!-- --!>after', $rendered);
	}

	public function testInstantiateIn_Valid_PradoHtmlCommentText()
	{
		$tpl = $this->newTemplateUnvalidated('before<!--PRADO HTML Comment--!>after');
		$tplCtl = new TTemplateInstantiateInRenderAcceptingControl();
		$page = $this->createPage();
		$tplCtl->setID('tplRender');
		$page->getControls()->add($tplCtl);
		$tpl->instantiateIn($tplCtl);
		$rendered = $tplCtl->renderAll();
		$this->assertEquals('before<!--PRADO HTML Comment--!>after', $rendered);
	}

	// Valid HTML comments - testing "<!-- -->"

	public function testInstantiateIn_Valid_HtmlComment()
	{
		$tpl = $this->newTemplateUnvalidated('before<!-- -->after');
		$tplCtl = new TTemplateInstantiateInRenderAcceptingControl();
		$page = $this->createPage();
		$tplCtl->setID('tplRender');
		$page->getControls()->add($tplCtl);
		$tpl->instantiateIn($tplCtl);
		$rendered = $tplCtl->renderAll();
		$this->assertEquals('before<!-- -->after', $rendered);
	}

	public function testInstantiateIn_Valid_HtmlCommentText()
	{
		$tpl = $this->newTemplateUnvalidated('before<!--HTML Comments-->after');
		$tplCtl = new TTemplateInstantiateInRenderAcceptingControl();
		$page = $this->createPage();
		$tplCtl->setID('tplRender');
		$page->getControls()->add($tplCtl);
		$tpl->instantiateIn($tplCtl);
		$rendered = $tplCtl->renderAll();
		$this->assertEquals('before<!--HTML Comments-->after', $rendered);
	}

	// Valid PRADO Template comments - testing "<!--- ---!>"

	public function testInstantiateIn_Valid_PradoTemplateComment()
	{
		$tpl = $this->newTemplateUnvalidated('before<!--- ---!>after');
		$tplCtl = new TTemplateInstantiateInRenderAcceptingControl();
		$page = $this->createPage();
		$tplCtl->setID('tplRender');
		$page->getControls()->add($tplCtl);
		$tpl->instantiateIn($tplCtl);
		$rendered = $tplCtl->renderAll();
		$this->assertEquals('beforeafter', $rendered);
		$this->assertStringNotContainsString('<!---', $rendered);
		$this->assertStringNotContainsString('---!>', $rendered);
	}

	public function testInstantiateIn_Valid_PradoTemplateCommentText()
	{
		$tpl = $this->newTemplateUnvalidated('before<!---Template Comments---!>after');
		$tplCtl = new TTemplateInstantiateInRenderAcceptingControl();
		$page = $this->createPage();
		$tplCtl->setID('tplRender');
		$page->getControls()->add($tplCtl);
		$tpl->instantiateIn($tplCtl);
		$rendered = $tplCtl->renderAll();
		$this->assertEquals('beforeafter', $rendered);
		$this->assertStringNotContainsString('<!---', $rendered);
		$this->assertStringNotContainsString('---!>', $rendered);
	}

	// Valid PRADO Template comments like HTML Comments - testing "<!--- --->"

	public function testInstantiateIn_Valid_TemplateComment()
	{
		$tpl = $this->newTemplateUnvalidated('before<!--- --->after');
		$tplCtl = new TTemplateInstantiateInRenderAcceptingControl();
		$page = $this->createPage();
		$tplCtl->setID('tplRender2');
		$page->getControls()->add($tplCtl);
		$tpl->instantiateIn($tplCtl);
		$rendered = $tplCtl->renderAll();
		$this->assertEquals('beforeafter', $rendered);
		$this->assertStringNotContainsString('<!---', $rendered);
		$this->assertStringNotContainsString('--->', $rendered);
	}

	public function testInstantiateIn_Valid_TemplateCommentText()
	{
		$tpl = $this->newTemplateUnvalidated('before<!---Template Comments--->after');
		$tplCtl = new TTemplateInstantiateInRenderAcceptingControl();
		$page = $this->createPage();
		$tplCtl->setID('tplRender2');
		$page->getControls()->add($tplCtl);
		$tpl->instantiateIn($tplCtl);
		$rendered = $tplCtl->renderAll();
		$this->assertEquals('beforeafter', $rendered);
		$this->assertStringNotContainsString('<!---', $rendered);
		$this->assertStringNotContainsString('--->', $rendered);
	}

	// Invalid mismatch comments must pass through unmodified

	public function testInstantiateIn_Invalid_PradoHtmlComment()
	{
		$tpl = $this->newTemplateUnvalidated('before<!-- ---!>after');
		$tplCtl = new TTemplateInstantiateInRenderAcceptingControl();
		$page = $this->createPage();
		$tplCtl->setID('tplRender');
		$page->getControls()->add($tplCtl);
		$tpl->instantiateIn($tplCtl);
		$rendered = $tplCtl->renderAll();
		$this->assertEquals('before<!-- ---!>after', $rendered);
	}

	public function testInstantiateIn_Invalid_PradoHtmlCommentText()
	{
		$tpl = $this->newTemplateUnvalidated('before<!--PRADO HTML Comment---!>after');
		$tplCtl = new TTemplateInstantiateInRenderAcceptingControl();
		$page = $this->createPage();
		$tplCtl->setID('tplRender');
		$page->getControls()->add($tplCtl);
		$tpl->instantiateIn($tplCtl);
		$rendered = $tplCtl->renderAll();
		$this->assertEquals('before<!--PRADO HTML Comment---!>after', $rendered);
	}

	public function testInstantiateIn_Invalid_HtmlComment()
	{
		$tpl = $this->newTemplateUnvalidated('before<!-- --->after');
		$tplCtl = new TTemplateInstantiateInRenderAcceptingControl();
		$page = $this->createPage();
		$tplCtl->setID('tplRender');
		$page->getControls()->add($tplCtl);
		$tpl->instantiateIn($tplCtl);
		$rendered = $tplCtl->renderAll();
		$this->assertEquals('before<!-- --->after', $rendered);
	}

	public function testInstantiateIn_Invalid_HtmlCommentText()
	{
		$tpl = $this->newTemplateUnvalidated('before<!--HTML Comments--->after');
		$tplCtl = new TTemplateInstantiateInRenderAcceptingControl();
		$page = $this->createPage();
		$tplCtl->setID('tplRender');
		$page->getControls()->add($tplCtl);
		$tpl->instantiateIn($tplCtl);
		$rendered = $tplCtl->renderAll();
		$this->assertEquals('before<!--HTML Comments--->after', $rendered);
	}

	public function testInstantiateIn_Invalid_PradoTemplateComment()
	{
		$tpl = $this->newTemplateUnvalidated('before<!--- --!>after');
		$tplCtl = new TTemplateInstantiateInRenderAcceptingControl();
		$page = $this->createPage();
		$tplCtl->setID('tplRender');
		$page->getControls()->add($tplCtl);
		$tpl->instantiateIn($tplCtl);
		$rendered = $tplCtl->renderAll();
		$this->assertEquals('before<!--- --!>after', $rendered);
	}

	public function testInstantiateIn_Invalid_PradoTemplateCommentText()
	{
		$tpl = $this->newTemplateUnvalidated('before<!---Template Comments--!>after');
		$tplCtl = new TTemplateInstantiateInRenderAcceptingControl();
		$page = $this->createPage();
		$tplCtl->setID('tplRender');
		$page->getControls()->add($tplCtl);
		$tpl->instantiateIn($tplCtl);
		$rendered = $tplCtl->renderAll();
		$this->assertEquals('before<!---Template Comments--!>after', $rendered);
	}

	public function testInstantiateIn_Invalid_TemplateComment()
	{
		$tpl = $this->newTemplateUnvalidated('before<!--- -->after');
		$tplCtl = new TTemplateInstantiateInRenderAcceptingControl();
		$page = $this->createPage();
		$tplCtl->setID('tplRender2');
		$page->getControls()->add($tplCtl);
		$tpl->instantiateIn($tplCtl);
		$rendered = $tplCtl->renderAll();
		$this->assertEquals('before<!--- -->after', $rendered);
	}

	public function testInstantiateIn_Invalid_TemplateCommentText()
	{
		$tpl = $this->newTemplateUnvalidated('before<!---Template Comments-->after');
		$tplCtl = new TTemplateInstantiateInRenderAcceptingControl();
		$page = $this->createPage();
		$tplCtl->setID('tplRender2');
		$page->getControls()->add($tplCtl);
		$tpl->instantiateIn($tplCtl);
		$rendered = $tplCtl->renderAll();
		$this->assertEquals('before<!---Template Comments-->after', $rendered);
	}

	// -----------------------------------------------------------------------
	// Template-control relationship, ID registration, and view state
	// -----------------------------------------------------------------------

	public function testInstantiateInSetsTemplateControlOnTControl()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1" Text="Hello" />');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->getControls());
		$label = $tplControl->getControls()[0];
		$this->assertSame($tplControl, $label->getTemplateControl());
		$this->assertSame($tplControl, $label->getParent());
	}

	public function testInstantiateInRegistersIdOnTControl()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="myLabel" Text="test" />');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertTrue($tplControl->isObjectRegistered('myLabel'));
		$registered = $tplControl->getRegisteredObject('myLabel');
		$this->assertInstanceOf(TLabel::class, $registered);
		$this->assertEquals('myLabel', $registered->getID());
	}

	public function testInstantiateInRegistersIdOnTComponentWithIdProperty()
	{
		Prado::using('TTemplateDashComponent');
		$tpl = $this->newTemplateUnvalidated('<com:TTemplateDashComponent ID="c1" ForeColor="red" />');
		$tplControl = $this->createAcceptingControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->parsedObjects);
		$comp = $tplControl->parsedObjects[0];
		$this->assertInstanceOf(TTemplateDashComponent::class, $comp);
		$this->assertEquals('c1', $comp->getID());
		$this->assertTrue($tplControl->isObjectRegistered('c1'));
		$this->assertEquals('red', $comp->getForeColor());
	}

	public function testInstantiateInTComponentNoIdPropertyUnsetsId()
	{
		$tpl = $this->newTemplateUnvalidated('<com:TTemplateInstantiateInTestComponent ID="c1" />');
		$tplControl = $this->createAcceptingControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->parsedObjects);
		$comp = $tplControl->parsedObjects[0];
		$this->assertInstanceOf(TTemplateInstantiateInTestComponent::class, $comp);
		$this->assertFalse($comp->hasProperty('id'));
		$this->assertFalse($tplControl->isObjectRegistered('c1'));
	}

	public function testInstantiateInTComponentIdNoIdPropertyWithCustomProp()
	{
		$tpl = $this->newTemplateUnvalidated('<com:TTemplateInstantiateInTestComponent ID="c1" CustomProp="val" />');
		$tplControl = $this->createAcceptingControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->parsedObjects);
		$comp = $tplControl->parsedObjects[0];
		$this->assertInstanceOf(TTemplateInstantiateInTestComponent::class, $comp);
		$this->assertEquals('val', $comp->getCustomProp());
		$this->assertFalse($tplControl->isObjectRegistered('c1'));
	}

	public function testInstantiateInTComponentNestedInAcceptingControl()
	{
		Prado::using('TTemplateInstantiateInAcceptingControl');
		$tpl = $this->newTemplateUnvalidated('<com:TTemplateInstantiateInAcceptingControl ID="pnl1"><com:TTemplateInstantiateInTestComponent ID="c1" CustomProp="val" /></com:TTemplateInstantiateInAcceptingControl>');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$panel = $tplControl->getControls()[0];
		$this->assertInstanceOf(TTemplateInstantiateInAcceptingControl::class, $panel);
		$this->assertCount(1, $panel->parsedObjects);
		$comp = $panel->parsedObjects[0];
		$this->assertInstanceOf(TTemplateInstantiateInTestComponent::class, $comp);
		$this->assertEquals('val', $comp->getCustomProp());
	}

	// -----------------------------------------------------------------------
	// SkinID handling
	// -----------------------------------------------------------------------

	public function testInstantiateInSkinIdConfigValue()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1" SkinID="default" Text="Hello" />');
		$parent = $this->createControlWithPage();
		$tpl->instantiateIn($parent);
		$this->assertCount(1, $parent->getControls());
		$label = $parent->getControls()[0];
		$this->assertEquals('default', $label->getSkinID());
		$this->assertEquals('Hello', $label->getText());
	}

	public function testInstantiateInSkinidRemovedFromPropertiesAfterSet()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1" SkinID="default" Text="Hello" Font.Size="12" />');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$label = $tplControl->getControls()[0];
		$this->assertEquals('default', $label->getSkinID());
		$this->assertEquals('Hello', $label->getText());
		$this->assertEquals('12', $label->getFont()->getSize());
	}

	public function testInstantiateInSkinidExpressionEvaluates()
	{
		$tpl = $this->newTemplateUnvalidated('<com:TLabel ID="lbl1" SkinID="<%= "custom" %>" Text="Hello" />');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->getControls());
		$label = $tplControl->getControls()[0];
		$this->assertInstanceOf(TLabel::class, $label);
	}

	public function testInstantiateInSkinidParameterEvaluates()
	{
		$tpl = $this->newTemplateUnvalidated('<com:TLabel ID="lbl1" SkinID="<%$ SkinParam %>" Text="Hello" />');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->getControls());
		$label = $tplControl->getControls()[0];
		$this->assertInstanceOf(TLabel::class, $label);
	}

	// -----------------------------------------------------------------------
	// Orphaned child items (parent index not in controls map)
	// -----------------------------------------------------------------------

	public function testInstantiateInOrphanedChildIndexSkipped()
	{
		$tplObj = $this->newTemplateUnvalidated('<com:TLabel ID="lbl1" Text="Hello" />');
		$items = PradoUnit::getProp($tplObj, '_tpl');
		$items[99] = [TTemplate::TPL_PARENT_INDEX => 50, TTemplate::TPL_TYPE => 'Prado\\Web\\UI\\WebControls\\TLabel', TTemplate::TPL_PROPS => ['id' => [TTemplate::PROP_TYPE => TTemplate::CONFIG_VALUE, TTemplate::PROP_NAME => 'ID', TTemplate::PROP_VALUE => 'orphan']]];
		PradoUnit::setProp($tplObj, '_tpl', $items);
		$tplControl = $this->createControlWithPage();
		$tplObj->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->getControls());
		$this->assertEquals('Hello', $tplControl->getControls()[0]->getText());
	}

	// -----------------------------------------------------------------------
	// TCompositeLiteral cloning and container
	// -----------------------------------------------------------------------

	public function testInstantiateInCompositeLiteralCloned()
	{
		$tpl = $this->newTemplate('before<%= $this->Name %>after');
		$parent1 = $this->createControlWithPage();
		$parent2 = $this->createControlWithPage();
		$tpl->instantiateIn($parent1);
		$tpl->instantiateIn($parent2);
		$this->assertCount(1, $parent1->getControls());
		$this->assertCount(1, $parent2->getControls());
		$this->assertNotSame($parent1->getControls()[0], $parent2->getControls()[0]);
	}

	public function testInstantiateInCompositeLiteralSetsContainer()
	{
		$tpl = $this->newTemplate('<%= $this->Name %>');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->getControls());
		$literal = $tplControl->getControls()[0];
		$this->assertInstanceOf(TCompositeLiteral::class, $literal);
		$this->assertSame($tplControl, $literal->getContainer());
	}

	public function testInstantiateInCompositeLiteralDirectChild()
	{
		$tpl = $this->newTemplate('text<%= $this->Name %>more');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->getControls());
		$literal = $tplControl->getControls()[0];
		$this->assertInstanceOf(TCompositeLiteral::class, $literal);
		$items = PradoUnit::getProp($literal, '_items');
		$this->assertCount(3, $items);
	}

	public function testInstantiateInCompositeLiteralNestedChild()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1"><%= $this->Name %></com:TLabel>');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$label = $tplControl->getControls()[0];
		$this->assertCount(1, $label->getControls());
		$literal = $label->getControls()[0];
		$this->assertInstanceOf(TCompositeLiteral::class, $literal);
		$this->assertSame($tplControl, $literal->getContainer());
	}

	public function testInstantiateInCompositeLiteralNestedViaAddParsedObject()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1">a<%= $this->X %>b</com:TLabel>');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$label = $tplControl->getControls()[0];
		$this->assertCount(1, $label->getControls());
		$this->assertInstanceOf(TCompositeLiteral::class, $label->getControls()[0]);
	}

	public function testInstantiateInCompositeLiteralClonePreservesExpressions()
	{
		$tpl = $this->newTemplate('a<%= $this->X %>b');
		$parent1 = $this->createControlWithPage();
		$tpl->instantiateIn($parent1);
		$lit1 = $parent1->getControls()[0];
		$this->assertInstanceOf(TCompositeLiteral::class, $lit1);
		$exprs1 = PradoUnit::getProp($lit1, '_expressions');
		$this->assertCount(1, $exprs1);
	}

	// -----------------------------------------------------------------------
	// Property configuration: CONFIG_VALUE
	// -----------------------------------------------------------------------

	public function testInstantiateInConfigureControlValue()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1" Text="Hello" />');
		$parent = $this->createControlWithPage();
		$tpl->instantiateIn($parent);
		$label = $parent->getControls()[0];
		$this->assertEquals('Hello', $label->getText());
	}

	public function testInstantiateInConfigureComponentValueViaAcceptingParent()
	{
		$tpl = $this->newTemplateUnvalidated('<com:TTemplateInstantiateInTestComponent ID="c1" CustomProp="val" />');
		$tplControl = $this->createAcceptingControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->parsedObjects);
		$comp = $tplControl->parsedObjects[0];
		$this->assertInstanceOf(TTemplateInstantiateInTestComponent::class, $comp);
		$this->assertEquals('val', $comp->getCustomProp());
	}

	// -----------------------------------------------------------------------
	// Property configuration: CONFIG_EXPRESSION
	// -----------------------------------------------------------------------

	public function testInstantiateInExpressionOnTControlAutoBinds()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1" Text="<%= $this->Title %>" />');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->getControls());
		$label = $tplControl->getControls()[0];
		$this->assertInstanceOf(TLabel::class, $label);
	}

	public function testInstantiateInConfigureComponentExpressionViaAcceptingParent()
	{
		$tpl = $this->newTemplateUnvalidated('<com:TTemplateInstantiateInTestComponent ID="c1" CustomProp="<%= "hello" %>" />');
		$tplControl = $this->createAcceptingControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->parsedObjects);
		$comp = $tplControl->parsedObjects[0];
		$this->assertInstanceOf(TTemplateInstantiateInTestComponent::class, $comp);
	}

	public function testInstantiateInExpressionOnTComponentEvaluatesImmediately()
	{
		// Use single-quoted string inside the expression to avoid breaking the attribute parser's
		// double-quote boundary detection ("..." stops at the first inner double quote).
		$tpl = $this->newTemplateUnvalidated('<com:TTemplateInstantiateInTestComponent ID="c1" CustomProp="<%= \'hello\' %>" />');
		$tplControl = $this->createAcceptingControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->parsedObjects);
		$comp = $tplControl->parsedObjects[0];
		// For TComponent (non-control), CONFIG_EXPRESSION is evaluated immediately via
		// $this->_tplControl->evaluateExpression($propInfo[PROP_VALUE]).
		$this->assertInstanceOf(TTemplateInstantiateInTestComponent::class, $comp);
		$this->assertEquals('hello', $comp->getCustomProp());
	}

	// -----------------------------------------------------------------------
	// Property configuration: CONFIG_DATABIND
	// -----------------------------------------------------------------------

	public function testInstantiateInDatabindOnTControlBindsProperty()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1" Text="<%# $this->Data %>" />');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->getControls());
		$label = $tplControl->getControls()[0];
		$this->assertInstanceOf(TLabel::class, $label);
	}

	public function testInstantiateInDatabindOnTComponentNotAllowed()
	{
		$this->expectException(TConfigurationException::class);
		$tpl = $this->newTemplate('<com:TComponent Text="<%# $this->Data %>" />');
		$parent = $this->createControlWithPage();
		$tpl->instantiateIn($parent);
	}

	// -----------------------------------------------------------------------
	// Property configuration: CONFIG_PARAMETER, CONFIG_LOCALIZATION, CONFIG_ASSET
	// -----------------------------------------------------------------------

	public function testInstantiateInParameterExpressionRetrievesParam()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1" Text="<%$ SomeParam %>" />');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->getControls());
		$label = $tplControl->getControls()[0];
		$this->assertInstanceOf(TLabel::class, $label);
	}

	public function testInstantiateInConfigureComponentParameterViaAcceptingParent()
	{
		$tpl = $this->newTemplateUnvalidated('<com:TTemplateInstantiateInTestComponent ID="c1" CustomProp="<%$ SomeParam %>" />');
		$tplControl = $this->createAcceptingControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->parsedObjects);
	}

	public function testInstantiateInLocalizationExpressionLocalizes()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1" Text="<%[ Hello World ]%>" />');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->getControls());
		$label = $tplControl->getControls()[0];
		$this->assertInstanceOf(TLabel::class, $label);
	}

	public function testInstantiateInConfigureComponentLocalizationViaAcceptingParent()
	{
		$tpl = $this->newTemplateUnvalidated('<com:TTemplateInstantiateInTestComponent ID="c1" CustomProp="<%[ Hello ]%>" />');
		$tplControl = $this->createAcceptingControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->parsedObjects);
	}

	public function testInstantiateInConfigureComponentAssetParsedCorrectly()
	{
		$tpl = $this->newTemplateUnvalidated('<com:TTemplateInstantiateInTestComponent ID="c1" CustomProp="<%~ assets/img.png %>" />');
		$items = $tpl->getItems();
		$component = null;
		foreach ($items as $item) {
			if (isset($item[TTemplate::TPL_PROPS])) {
				$component = $item;
			}
		}
		$this->assertNotNull($component);
		$this->assertCount(2, $component[TTemplate::TPL_PROPS]);
		$this->assertEquals(TTemplate::CONFIG_ASSET, $component[TTemplate::TPL_PROPS]['customprop'][TTemplate::PROP_TYPE]);
		$this->assertEquals('assets/img.png', $component[TTemplate::TPL_PROPS]['customprop'][TTemplate::PROP_VALUE]);
	}

	// -----------------------------------------------------------------------
	// Property configuration: CONFIG_TEMPLATE
	// -----------------------------------------------------------------------

	public function testInstantiateInConfigureComponentTemplateParsedCorrectly()
	{
		$tpl = $this->newTemplateUnvalidated('<com:TTemplateInstantiateInTestComponent ID="c1" Template="content" />');
		$items = $tpl->getItems();
		$component = null;
		foreach ($items as $item) {
			if (isset($item[TTemplate::TPL_PROPS])) {
				$component = $item;
			}
		}
		$this->assertNotNull($component);
		$this->assertEquals(TTemplate::CONFIG_TEMPLATE, $component[TTemplate::TPL_PROPS]['template'][TTemplate::PROP_TYPE]);
		$this->assertInstanceOf(TTemplate::class, $component[TTemplate::TPL_PROPS]['template'][TTemplate::PROP_VALUE]);
	}

	public function testInstantiateInConfigureControlTemplateSetsNestedTemplate()
	{
		Prado::using('TTemplateInstantiateInTemplateControl');
		// 'itemtemplate' (all lowercase) triggers CONFIG_TEMPLATE on instantiation
		$tpl = $this->newTemplateUnvalidated('<com:TTemplateInstantiateInTemplateControl ID="c1" Itemtemplate="inner content" />');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->getControls());
		$control = $tplControl->getControls()[0];
		$this->assertInstanceOf(TTemplateInstantiateInTemplateControl::class, $control);
		$this->assertInstanceOf(TTemplate::class, $control->getItemTemplate());
	}

	// -----------------------------------------------------------------------
	// Unexpected property type triggers exception
	// -----------------------------------------------------------------------

	public function testInstantiateInConfigurePropertyUnexpectedTypeThrows()
	{
		$tplObj = $this->newTemplateUnvalidated('<com:TLabel ID="lbl1" Text="Hello" />');
		$items = PradoUnit::getProp($tplObj, '_tpl');
		foreach ($items as &$item) {
			if (isset($item[TTemplate::TPL_PROPS])) {
				$item[TTemplate::TPL_PROPS]['badprop'] = [TTemplate::PROP_TYPE => 99, TTemplate::PROP_NAME => 'BadProp', TTemplate::PROP_VALUE => 'x'];
			}
		}
		PradoUnit::setProp($tplObj, '_tpl', $items);
		$parent = $this->createControlWithPage();
		$this->expectException(TConfigurationException::class);
		$tplObj->instantiateIn($parent);
	}

	// -----------------------------------------------------------------------
	// Events
	// -----------------------------------------------------------------------

	public function testInstantiateInConfigureEventWithoutDot()
	{
		$tpl = $this->newTemplateUnvalidated('<com:TButton ID="btn1" OnClick="handleClick" Text="Click" />');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->getControls());
		$button = $tplControl->getControls()[0];
		$this->assertInstanceOf(TButton::class, $button);
		$this->assertEquals('Click', $button->getText());
		// Verify the event handler was actually attached to the OnClick event
		$this->assertTrue($button->hasEventHandler('onClick'));
	}

	public function testInstantiateInConfigureEventWithDot()
	{
		$tpl = $this->newTemplateUnvalidated('<com:TButton ID="btn1" OnClick="MyObj.handleClick" Text="Click" />');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->getControls());
		$button = $tplControl->getControls()[0];
		$this->assertInstanceOf(TButton::class, $button);
		// Dot-handler passes the literal handler string directly
		$this->assertTrue($button->hasEventHandler('onClick'));
	}

	public function testInstantiateInEventOnTControlNotAllowed()
	{
		$this->expectException(TConfigurationException::class);
		$tpl = $this->newTemplate('<com:TControl onClick="handler" />');
		$parent = $this->createControlWithPage();
		$tpl->instantiateIn($parent);
	}

	// -----------------------------------------------------------------------
	// Subproperty configuration
	// -----------------------------------------------------------------------

	public function testInstantiateInSubPropertyConfigValue()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1" Font.Size="12" />');
		$parent = $this->createControlWithPage();
		$tpl->instantiateIn($parent);
		$this->assertCount(1, $parent->getControls());
		$label = $parent->getControls()[0];
		$this->assertEquals('12', $label->getFont()->getSize());
	}

	public function testInstantiateInSubPropertySetsNestedProperty()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1" Font.Size="12" Font.Bold="true" />');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->getControls());
		$label = $tplControl->getControls()[0];
		$this->assertEquals('12', $label->getFont()->getSize());
		$this->assertTrue($label->getFont()->getBold());
	}

	public function testInstantiateInSubPropertyWithExpression()
	{
		$tpl = $this->newTemplateUnvalidated('<com:TLabel ID="lbl1" Font.Size="<%= 12 %>" />');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->getControls());
		$label = $tplControl->getControls()[0];
		$this->assertInstanceOf(TLabel::class, $label);
	}

	public function testInstantiateInSubPropertyWithDatabind()
	{
		$tpl = $this->newTemplateUnvalidated('<com:TLabel ID="lbl1" Font.Size="<%# 12 %>" />');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->getControls());
		$label = $tplControl->getControls()[0];
		$this->assertInstanceOf(TLabel::class, $label);
	}

	public function testInstantiateInSubPropertyWithParameter()
	{
		$tpl = $this->newTemplateUnvalidated('<com:TLabel ID="lbl1" Font.Size="<%$ FontSizeParam %>" />');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->getControls());
		$label = $tplControl->getControls()[0];
		$this->assertInstanceOf(TLabel::class, $label);
	}

	public function testInstantiateInSubPropertyWithLocalization()
	{
		$tpl = $this->newTemplateUnvalidated('<com:TLabel ID="lbl1" Font.Size="<%[ size ]%>" />');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->getControls());
		$label = $tplControl->getControls()[0];
		$this->assertInstanceOf(TLabel::class, $label);
	}

	// -----------------------------------------------------------------------
	// Property tag (block-style) configuration
	// -----------------------------------------------------------------------

	public function testInstantiateInGroupSubPropertyTag()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1"><prop:Font Size="14" Bold="true" /></com:TLabel>');
		$parent = $this->createControlWithPage();
		$tpl->instantiateIn($parent);
		$this->assertCount(1, $parent->getControls());
		$label = $parent->getControls()[0];
		$this->assertEquals('14', $label->getFont()->getSize());
		$this->assertTrue($label->getFont()->getBold());
	}

	public function testInstantiateInGroupSubPropertyWithDash()
	{
		Prado::using('TTemplateDashComponent');
		$tpl = $this->newTemplateUnvalidated('<com:TTemplateDashComponent ID="c1"><prop:SubProp ForeColor="green" /></com:TTemplateDashComponent>');
		$tplControl = $this->createAcceptingControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->parsedObjects);
		$comp = $tplControl->parsedObjects[0];
		$this->assertInstanceOf(TTemplateDashComponent::class, $comp);
		$this->assertEquals('green', $comp->getForeColor());
	}

	public function testInstantiateInPropertyTag()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1"><prop:Text>Hello World</prop:Text></com:TLabel>');
		$parent = $this->createControlWithPage();
		$tpl->instantiateIn($parent);
		$this->assertCount(1, $parent->getControls());
		$label = $parent->getControls()[0];
		$this->assertEquals('Hello World', $label->getText());
	}

	public function testInstantiateInPropertyTagWithExpression()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1"><prop:Text><%= $this->Name %></prop:Text></com:TLabel>');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->getControls());
		$label = $tplControl->getControls()[0];
		$this->assertInstanceOf(TLabel::class, $label);
	}

	public function testInstantiateInPropertyTagWithDatabind()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1"><prop:Text><%# $this->Data %></prop:Text></com:TLabel>');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->getControls());
		$label = $tplControl->getControls()[0];
		$this->assertInstanceOf(TLabel::class, $label);
	}

	public function testInstantiateInPropertyTagWithParameter()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1"><prop:Text><%$ AppParam %></prop:Text></com:TLabel>');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->getControls());
		$label = $tplControl->getControls()[0];
		$this->assertInstanceOf(TLabel::class, $label);
	}

	public function testInstantiateInPropertyTagWithLocalization()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1"><prop:Text><%[ Hello ]%></prop:Text></com:TLabel>');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->getControls());
		$label = $tplControl->getControls()[0];
		$this->assertInstanceOf(TLabel::class, $label);
	}

	// -----------------------------------------------------------------------
	// Dash-to-underscore attribute name conversion
	// -----------------------------------------------------------------------

	public function testInstantiateInPropertyTagWithDashInMagicComponent()
	{
		Prado::using('TTemplateMagicComponent');
		$tpl = $this->newTemplateUnvalidated('<com:TTemplateMagicComponent ID="c1"><prop:Custom-Prop>value from tag</prop:Custom-Prop></com:TTemplateMagicComponent>');
		$tplControl = $this->createAcceptingControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->parsedObjects);
		$comp = $tplControl->parsedObjects[0];
		$this->assertInstanceOf(TTemplateMagicComponent::class, $comp);
		$this->assertArrayHasKey('Custom_Prop', $comp->receivedProperties);
		$this->assertEquals('value from tag', $comp->receivedProperties['Custom_Prop']);
	}

	public function testInstantiateInPropertyTagWithDashInMagicControl()
	{
		Prado::using('TTemplateMagicControl');
		$tpl = $this->newTemplateUnvalidated('<com:TTemplateMagicControl ID="c1"><prop:data-value>123</prop:data-value></com:TTemplateMagicControl>');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->getControls());
		$control = $tplControl->getControls()[0];
		$this->assertInstanceOf(TTemplateMagicControl::class, $control);
		$this->assertArrayHasKey('data_value', $control->receivedProperties);
		$this->assertEquals('123', $control->receivedProperties['data_value']);
	}

	public function testInstantiateInAttributeDashToUnderscoreOnMagicControl()
	{
		Prado::using('TTemplateMagicControl');
		$tpl = $this->newTemplateUnvalidated('<com:TTemplateMagicControl ID="c1" data-toggle="dropdown" />');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->getControls());
		$control = $tplControl->getControls()[0];
		$this->assertInstanceOf(TTemplateMagicControl::class, $control);
		$this->assertArrayHasKey('data_toggle', $control->receivedProperties);
		$this->assertEquals('dropdown', $control->receivedProperties['data_toggle']);
	}

	public function testInstantiateInCasePreservedPropertyOnDashControl()
	{
		Prado::using('TTemplateDashControl');
		$tpl = $this->newTemplateUnvalidated('<com:TTemplateDashControl ID="c1" ForeColor="blue" />');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->getControls());
		$control = $tplControl->getControls()[0];
		$this->assertInstanceOf(TTemplateDashControl::class, $control);
		$this->assertEquals('blue', $control->getForeColor());
	}

	public function testInstantiateInPropertyWithDashSetViaMagic()
	{
		Prado::using('TTemplateMagicControl');
		$tpl = $this->newTemplateUnvalidated('<com:TTemplateMagicControl ID="c1" Custom-Prop="value" />');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->getControls());
		$control = $tplControl->getControls()[0];
		$this->assertInstanceOf(TTemplateMagicControl::class, $control);
		$this->assertArrayHasKey('Custom_Prop', $control->receivedProperties);
		$this->assertEquals('value', $control->receivedProperties['Custom_Prop']);
	}

	public function testInstantiateInMagicComponentReceivesDashProperty()
	{
		Prado::using('TTemplateMagicComponent');
		$tpl = $this->newTemplateUnvalidated('<com:TTemplateMagicComponent ID="c1" data-toggle="dropdown" />');
		$tplControl = $this->createAcceptingControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->parsedObjects);
		$comp = $tplControl->parsedObjects[0];
		$this->assertInstanceOf(TTemplateMagicComponent::class, $comp);
		$this->assertArrayHasKey('data_toggle', $comp->receivedProperties);
		$this->assertEquals('dropdown', $comp->receivedProperties['data_toggle']);
	}

	public function testInstantiateInMagicComponentReceivesCasePreservedProperty()
	{
		Prado::using('TTemplateMagicComponent');
		$tpl = $this->newTemplateUnvalidated('<com:TTemplateMagicComponent ID="c1" ThemeColor="blue" />');
		$tplControl = $this->createAcceptingControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->parsedObjects);
		$comp = $tplControl->parsedObjects[0];
		$this->assertInstanceOf(TTemplateMagicComponent::class, $comp);
		$this->assertArrayHasKey('ThemeColor', $comp->receivedProperties);
		$this->assertEquals('blue', $comp->receivedProperties['ThemeColor']);
	}

	public function testInstantiateInMagicControlReceivesDashProperty()
	{
		Prado::using('TTemplateMagicControl');
		$tpl = $this->newTemplateUnvalidated('<com:TTemplateMagicControl ID="c1" data-toggle="modal" />');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->getControls());
		$control = $tplControl->getControls()[0];
		$this->assertInstanceOf(TTemplateMagicControl::class, $control);
		$this->assertArrayHasKey('data_toggle', $control->receivedProperties);
		$this->assertEquals('modal', $control->receivedProperties['data_toggle']);
	}

	public function testInstantiateInMagicControlReceivesCasePreservedProperty()
	{
		Prado::using('TTemplateMagicControl');
		$tpl = $this->newTemplateUnvalidated('<com:TTemplateMagicControl ID="c1" ThemeColor="purple" />');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->getControls());
		$control = $tplControl->getControls()[0];
		$this->assertInstanceOf(TTemplateMagicControl::class, $control);
		$this->assertArrayHasKey('ThemeColor', $control->receivedProperties);
		$this->assertEquals('purple', $control->receivedProperties['ThemeColor']);
	}

	// -----------------------------------------------------------------------
	// JS property prefix
	// -----------------------------------------------------------------------

	public function testInstantiateInJsPropertyPrefixOnControl()
	{
		Prado::using('TTemplateInstantiateInJsControl');
		$tpl = $this->newTemplateUnvalidated('<com:TTemplateInstantiateInJsControl ID="c1" JsClick="alert(1)" />');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->getControls());
		$control = $tplControl->getControls()[0];
		$this->assertInstanceOf(TTemplateInstantiateInJsControl::class, $control);
		$this->assertInstanceOf(TJavaScriptLiteral::class, $control->getJsClick());
	}

	public function testInstantiateInJsPropertyPrefixEmptyValue()
	{
		Prado::using('TTemplateInstantiateInJsControl');
		$tpl = $this->newTemplateUnvalidated('<com:TTemplateInstantiateInJsControl ID="c1" JsClick="" />');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->getControls());
		$control = $tplControl->getControls()[0];
		$this->assertInstanceOf(TTemplateInstantiateInJsControl::class, $control);
		$this->assertEquals('', $control->getJsClick());
	}

	// -----------------------------------------------------------------------
	// Multiple components, nesting, mixed content
	// -----------------------------------------------------------------------

	public function testInstantiateInMultipleComponents()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="a" Text="A" /><com:TLabel ID="b" Text="B" />');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(2, $tplControl->getControls());
		$this->assertEquals('A', $tplControl->getControls()[0]->getText());
		$this->assertEquals('B', $tplControl->getControls()[1]->getText());
		$this->assertSame($tplControl, $tplControl->getControls()[0]->getParent());
		$this->assertSame($tplControl, $tplControl->getControls()[1]->getParent());
	}

	public function testInstantiateInNestedComponents()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="outer"><com:TLabel ID="inner" Text="World" /></com:TLabel>');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->getControls());
		$outer = $tplControl->getControls()[0];
		$this->assertEquals('outer', $outer->getID());
		$this->assertCount(1, $outer->getControls());
		$inner = $outer->getControls()[0];
		$this->assertInstanceOf(TLabel::class, $inner);
		$this->assertEquals('World', $inner->getText());
		$this->assertSame($outer, $inner->getParent());
	}

	public function testInstantiateInNestedTextUsesAddParsedObject()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="outer">inner text</com:TLabel>');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$outer = $tplControl->getControls()[0];
		$this->assertCount(1, $outer->getControls());
		$this->assertEquals('inner text', $outer->getControls()[0]);
	}

	public function testInstantiateInDeeplyNestedComponents()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="l1"><com:TLabel ID="l2"><com:TLabel ID="l3" Text="Deep" /></com:TLabel></com:TLabel>');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->getControls());
		$l1 = $tplControl->getControls()[0];
		$this->assertEquals('l1', $l1->getID());
		$this->assertCount(1, $l1->getControls());
		$l2 = $l1->getControls()[0];
		$this->assertEquals('l2', $l2->getID());
		$this->assertCount(1, $l2->getControls());
		$l3 = $l2->getControls()[0];
		$this->assertEquals('l3', $l3->getID());
		$this->assertEquals('Deep', $l3->getText());
	}

	public function testInstantiateInMultipleTextsAndComponents()
	{
		$tpl = $this->newTemplate('before<com:TLabel ID="lbl1" Text="Hello" />after');
		$parent = $this->createControlWithPage();
		$tpl->instantiateIn($parent);
		$this->assertCount(3, $parent->getControls());
		$this->assertEquals('before', $parent->getControls()[0]);
		$this->assertInstanceOf(TLabel::class, $parent->getControls()[1]);
		$this->assertEquals('Hello', $parent->getControls()[1]->getText());
		$this->assertEquals('after', $parent->getControls()[2]);
	}

	public function testInstantiateInMultipleNestedLevelsSameParent()
	{
		$tpl = $this->newTemplate('<com:TPanel ID="p1"><com:TLabel ID="l1" Text="A" /><com:TLabel ID="l2" Text="B" /></com:TPanel>');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->getControls());
		$panel = $tplControl->getControls()[0];
		$this->assertInstanceOf(TPanel::class, $panel);
		$this->assertCount(2, $panel->getControls());
		$this->assertEquals('A', $panel->getControls()[0]->getText());
		$this->assertEquals('B', $panel->getControls()[1]->getText());
	}

	public function testInstantiateInComponentWithNoAttributes()
	{
		$tpl = $this->newTemplate('<com:TLabel />');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->getControls());
		$label = $tplControl->getControls()[0];
		$this->assertInstanceOf(TLabel::class, $label);
		$this->assertCount(0, $label->getControls());
	}

	public function testInstantiateInAllowChildControlsFalse()
	{
		Prado::using('TTemplateInstantiateInNoAllowChildControl');
		$tpl = $this->newTemplateUnvalidated('<com:TTemplateInstantiateInNoAllowChildControl ID="outer"><com:TLabel ID="inner" Text="Hello" /></com:TTemplateInstantiateInNoAllowChildControl>');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->getControls());
		$outer = $tplControl->getControls()[0];
		$this->assertInstanceOf(TTemplateInstantiateInNoAllowChildControl::class, $outer);
		$this->assertCount(0, $outer->getControls());
	}

	public function testInstantiateInTComponentNestedAddsToControlsArray()
	{
		$tpl = $this->newTemplateUnvalidated('<com:TTemplateInstantiateInAcceptingControl ID="pnl1"><com:TTemplateDashComponent ID="c1" ForeColor="red" /></com:TTemplateInstantiateInAcceptingControl>');
		$tplControl = $this->createAcceptingControlWithPage();
		$tpl->instantiateIn($tplControl);
		$outer = $tplControl->parsedObjects[0];
		$this->assertInstanceOf(TTemplateInstantiateInAcceptingControl::class, $outer);
		$this->assertCount(1, $outer->parsedObjects);
		$comp = $outer->parsedObjects[0];
		$this->assertInstanceOf(TTemplateDashComponent::class, $comp);
		$this->assertEquals('red', $comp->getForeColor());
	}

	public function testInstantiateInPanelWithChildren()
	{
		$tpl = $this->newTemplate('<com:TPanel ID="pnl1"><com:TLabel ID="lbl1" Text="Inside" /></com:TPanel>');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->getControls());
		$panel = $tplControl->getControls()[0];
		$this->assertInstanceOf(TPanel::class, $panel);
		$this->assertCount(1, $panel->getControls());
		$label = $panel->getControls()[0];
		$this->assertInstanceOf(TLabel::class, $label);
		$this->assertEquals('Inside', $label->getText());
	}

	// -----------------------------------------------------------------------
	// Reusing a template across multiple controls
	// -----------------------------------------------------------------------

	public function testInstantiateInTemplateReusedMultipleTimes()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1" Text="Hello" />');
		$parent1 = $this->createControlWithPage();
		$parent2 = $this->createControlWithPage();
		$tpl->instantiateIn($parent1);
		$tpl->instantiateIn($parent2);
		$label1 = $parent1->getControls()[0];
		$label2 = $parent2->getControls()[0];
		$this->assertNotSame($label1, $label2);
		$this->assertEquals('lbl1', $label1->getID());
		$this->assertEquals('lbl1', $label2->getID());
		$this->assertEquals('Hello', $label1->getText());
		$this->assertEquals('Hello', $label2->getText());
	}

	public function testInstantiateInMultipleTemplatesSequentially()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1" Text="Hello" />');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->getControls());
		$tpl2 = $this->newTemplate('<com:TLabel ID="lbl2" Text="World" />');
		$tpl2->instantiateIn($tplControl);
		$this->assertCount(2, $tplControl->getControls());
		$this->assertEquals('Hello', $tplControl->getControls()[0]->getText());
		$this->assertEquals('World', $tplControl->getControls()[1]->getText());
	}

	// -----------------------------------------------------------------------
	// TOutputCache cache key prefix
	// -----------------------------------------------------------------------

	public function testInstantiateInOutputCacheSetsCacheKeyPrefix()
	{
		$templateStr = '<com:TOutputCache ID="cache1" Duration="60"><com:TLabel ID="lbl1" Text="Cached" /></com:TOutputCache>';
		$tpl = $this->newTemplateUnvalidated($templateStr);
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->getControls());
		$cache = $tplControl->getControls()[0];
		$this->assertInstanceOf(TOutputCache::class, $cache);
		$this->assertCount(1, $cache->getControls());
		$label = $cache->getControls()[0];
		$this->assertInstanceOf(TLabel::class, $label);
		$this->assertEquals('Cached', $label->getText());
		// TOutputCache has setCacheKeyPrefix() but no getter; read via reflection.
		$prefix = PradoUnit::getProp($cache, '_keyPrefix');
		$this->assertNotEmpty($prefix);
		// TTemplate sets the prefix to $this->_hashCode . $tplKey; hashCode = md5 of the template string.
		$this->assertStringStartsWith(md5($templateStr), $prefix);
	}

	// -----------------------------------------------------------------------
	// Component namespace, inline expressions, and directive-level tags
	// -----------------------------------------------------------------------

	public function testInstantiateInComponentNamespaceHandling()
	{
		$tpl = $this->newTemplate('<com:Prado\\Web\\UI\\WebControls\\TLabel ID="lbl1" Text="Hello" />');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->getControls());
		$label = $tplControl->getControls()[0];
		$this->assertInstanceOf(TLabel::class, $label);
		$this->assertEquals('Hello', $label->getText());
	}

	public function testInstantiateInMixedExpressionAndStaticText()
	{
		$tpl = $this->newTemplate('prefix<%= $this->Name %>suffix');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->getControls());
		$literal = $tplControl->getControls()[0];
		$this->assertInstanceOf(TCompositeLiteral::class, $literal);
	}

	public function testInstantiateInStatementExpression()
	{
		$tpl = $this->newTemplate('<%% echo "hello"; %>');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->getControls());
		$literal = $tplControl->getControls()[0];
		$this->assertInstanceOf(TCompositeLiteral::class, $literal);
	}

	public function testInstantiateInlineDatabindExpression()
	{
		$tpl = $this->newTemplate('<%# $this->Data %>');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(1, $tplControl->getControls());
		$literal = $tplControl->getControls()[0];
		$this->assertInstanceOf(TCompositeLiteral::class, $literal);
	}

	public function testInstantiateInDirectiveLevelPropertyTagNotInstantiated()
	{
		$tpl = $this->newTemplate('<prop:Title>My Page</prop:Title>');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(0, $tplControl->getControls());
	}

	public function testInstantiateInMultipleCompositeLiteralsNotMerged()
	{
		$tpl = $this->newTemplate('<com:TPanel ID="p1"><%= $a %></com:TPanel><%= $b %>');
		$tplControl = $this->createControlWithPage();
		$tpl->instantiateIn($tplControl);
		$this->assertCount(2, $tplControl->getControls());
		$panel = $tplControl->getControls()[0];
		$this->assertInstanceOf(TPanel::class, $panel);
		$this->assertCount(1, $panel->getControls());
		$this->assertInstanceOf(TCompositeLiteral::class, $panel->getControls()[0]);
		$outer = $tplControl->getControls()[1];
		$this->assertInstanceOf(TCompositeLiteral::class, $outer);
	}

	public function testInstantiateInIdParameterExpressionParsedCorrectly()
	{
		$tpl = $this->newTemplateUnvalidated('<com:TLabel ID="<%$ LabelIdParam %>" Text="Hello" />');
		$items = $tpl->getItems();
		$component = null;
		foreach ($items as $item) {
			if (isset($item[TTemplate::TPL_PROPS])) {
				$component = $item;
			}
		}
		$this->assertNotNull($component);
		$this->assertCount(2, $component[TTemplate::TPL_PROPS]);
		$this->assertEquals(TTemplate::CONFIG_PARAMETER, $component[TTemplate::TPL_PROPS]['id'][TTemplate::PROP_TYPE]);
		$this->assertEquals('LabelIdParam', $component[TTemplate::TPL_PROPS]['id'][TTemplate::PROP_VALUE]);
	}
}
