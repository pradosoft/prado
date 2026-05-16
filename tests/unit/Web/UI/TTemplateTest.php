<?php

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TTemplateException;
use Prado\TComponent;
use Prado\Web\UI\TCompositeLiteral;
use Prado\Web\UI\TTemplate;

/**
 * Helper: TComponent subclass that has only a getter — no setter — to test readonly validation.
 */
class TTemplateTestReadonlyComponent extends TComponent
{
	public function getReadonlyProp()
	{
		return 'value';
	}
}

class TTemplateTest extends PHPUnit\Framework\TestCase
{
	private $_contextPath;
	protected $obj;

	protected function getTestClass()
	{
		return TTemplate::class;
	}

	protected function setUp(): void
	{
		$this->_contextPath = sys_get_temp_dir();
		$class = $this->getTestClass();
		$this->obj = new $class('', $this->_contextPath);
	}

	protected function tearDown(): void
	{
		$this->_contextPath = null;
		$this->obj = null;
	}

	private function newTemplate($template, $contextPath = null, $tplFile = null, $startingLine = 0, $sourceTemplate = true)
	{
		return new TTemplate($template, $contextPath ?? $this->_contextPath, $tplFile, $startingLine, $sourceTemplate);
	}

	private function newTemplateUnvalidated($template)
	{
		$ref = new \ReflectionClass(TTemplate::class);
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
			$p = $ref->getProperty($name);
			$p->setAccessible(true);
			$p->setValue($tplObj, $val);
		}
		$ref->getParentClass()->getParentClass()->getConstructor()->invoke($tplObj);
		$parse = $ref->getMethod('parse');
		$parse->setAccessible(true);
		$parse->invoke($tplObj, $template);
		$c = $ref->getProperty('_content');
		$c->setAccessible(true);
		$c->setValue($tplObj, null);
		return $tplObj;
	}

	private function findComponent(array $items)
	{
		foreach ($items as $item) {
			if (isset($item[TTemplate::TPL_PROPS])) {
				return $item;
			}
		}
		return null;
	}

	// -----------------------------------------------------------------------
	// Constructor and basic accessors
	// -----------------------------------------------------------------------

	public function testConstruct()
	{
		$tpl = $this->newTemplate('hello');
		$this->assertNotNull($tpl);
		$this->assertEquals(sys_get_temp_dir(), $tpl->getContextPath());
	}

	public function testConstructWithTemplateFile()
	{
		$tpl = $this->newTemplate('text', '/tmp', '/tmp/test.page');
		$this->assertEquals('/tmp/test.page', $tpl->getTemplateFile());
	}

	public function testGetIsSourceTemplate()
	{
		$this->assertTrue($this->newTemplate('hello', null, null, 0, true)->getIsSourceTemplate());
		$this->assertFalse($this->newTemplate('hello', null, null, 0, false)->getIsSourceTemplate());
	}

	public function testGetHashCode()
	{
		$this->assertEquals(md5('hello'), $this->newTemplate('hello')->getHashCode());
	}

	public function testAttributeValidation()
	{
		$tpl = $this->newTemplate('');
		$this->assertTrue($tpl->getAttributeValidation());
		$tpl->setAttributeValidation(false);
		$this->assertFalse($tpl->getAttributeValidation());
		$tpl->setAttributeValidation(true);
		$this->assertTrue($tpl->getAttributeValidation());
	}

	public function testContextPath()
	{
		$this->assertEquals('/custom/path', $this->newTemplate('', '/custom/path')->getContextPath());
	}

	public function testREGEX_RULESConstant()
	{
		$this->assertIsString(TTemplate::REGEX_RULES);
		$this->assertGreaterThan(0, strlen(TTemplate::REGEX_RULES));
	}

	// -----------------------------------------------------------------------
	// Directive parsing
	// -----------------------------------------------------------------------

	public function testGetDirectiveEmpty()
	{
		$this->assertEquals([], $this->newTemplate('hello')->getDirective());
	}

	public function testDirectiveParsing()
	{
		$tpl = $this->newTemplate('<%@ Language="PHP" %>');
		$this->assertEquals(['Language' => 'PHP'], $tpl->getDirective());
	}

	public function testDirectiveParsingMultiple()
	{
		$tpl = $this->newTemplate('<%@ Language="PHP" Master="Site" %>');
		$dir = $tpl->getDirective();
		$this->assertCount(2, $dir);
		$this->assertEquals('PHP', $dir['Language']);
		$this->assertEquals('Site', $dir['Master']);
	}

	public function testDirectivePreservesCase()
	{
		$tpl = $this->newTemplate('<%@ ThemeColor="blue" %>');
		$dir = $tpl->getDirective();
		$this->assertArrayHasKey('ThemeColor', $dir);
		$this->assertEquals('blue', $dir['ThemeColor']);
	}

	public function testDirectiveAttributeNameNoDash()
	{
		$tpl = $this->newTemplate('<%@ MasterPage="layout" %>');
		$dir = $tpl->getDirective();
		$this->assertArrayHasKey('MasterPage', $dir);
		$this->assertEquals('layout', $dir['MasterPage']);
	}

	public function testDirectiveDuplicateThrows()
	{
		$this->expectException(TConfigurationException::class);
		$this->newTemplate('<%@ Language="PHP" %><%@ Title="Test" %>');
	}

	public function testDirectiveNotAtStartThrows()
	{
		$this->expectException(TConfigurationException::class);
		$this->newTemplate('text<%@ Language="PHP" %>');
	}

	public function testDirectiveNotFirstThrows()
	{
		$this->expectException(TConfigurationException::class);
		$this->newTemplate('<com:TLabel ID="lbl1" /><%@ Language="PHP" %>');
	}

	public function testDirectiveWithSingleQuotes()
	{
		$tpl = $this->newTemplate("<%@ Language='PHP' %>");
		$this->assertEquals(['Language' => 'PHP'], $tpl->getDirective());
	}

	public function testDirectiveWithDashAttributeName()
	{
		$tpl = $this->newTemplate('<%@ MasterPage="layout" ThemeColor="blue" %>');
		$dir = $tpl->getDirective();
		$this->assertArrayHasKey('MasterPage', $dir);
		$this->assertArrayHasKey('ThemeColor', $dir);
		$this->assertEquals('layout', $dir['MasterPage']);
		$this->assertEquals('blue', $dir['ThemeColor']);
	}

	public function testDirectiveEmptyAttributes()
	{
		// <%@ %> with no attributes produces an empty directive, not an error
		$tpl = $this->newTemplate('<%@ %>');
		$this->assertEquals([], $tpl->getDirective());
	}

	// -----------------------------------------------------------------------
	// Property tags at directive level
	// -----------------------------------------------------------------------

	public function testGetPropertyTagOnDirectiveLevel()
	{
		$tpl = $this->newTemplate('<prop:Title>My Page</prop:Title>');
		$dir = $tpl->getDirective();
		$this->assertArrayHasKey('Title', $dir);
		$this->assertEquals('My Page', $dir['Title']);
	}

	public function testGetPropertyTagOnDirectiveWithDashName()
	{
		$tpl = $this->newTemplate('<prop:My-Prop>value</prop:My-Prop>');
		$dir = $tpl->getDirective();
		$this->assertArrayHasKey('My-Prop', $dir);
		$this->assertEquals('value', $dir['My-Prop']);
	}

	public function testGetPropertyTagWithExpressionOnDirectiveLevel()
	{
		$tpl = $this->newTemplate('<prop:Title><%= $this->AppName %></prop:Title>');
		$dir = $tpl->getDirective();
		$this->assertArrayHasKey('Title', $dir);
		$this->assertIsString($dir['Title']);
	}

	// -----------------------------------------------------------------------
	// Plain text and empty templates
	// -----------------------------------------------------------------------

	public function testEmptyTemplate()
	{
		$this->assertCount(0, $this->newTemplate('')->getItems());
	}

	public function testWhitespaceOnlyTemplate()
	{
		$items = $this->newTemplate('   ')->getItems();
		$this->assertCount(1, $items);
		$this->assertEquals('   ', array_values($items)[0][TTemplate::TPL_TYPE]);
	}

	public function testPlainText()
	{
		$tpl = $this->newTemplate('Hello World');
		$items = $tpl->getItems();
		$this->assertCount(1, $items);
		$item = array_values($items)[0];
		$this->assertEquals(-1, $item[TTemplate::TPL_PARENT_INDEX]);
		$this->assertEquals('Hello World', $item[TTemplate::TPL_TYPE]);
		$this->assertArrayNotHasKey(TTemplate::TPL_PROPS, $item);
	}

	public function testItemsReturnedByReference()
	{
		$tpl = $this->newTemplate('Hello');
		$items = &$tpl->getItems();
		$items[999] = [TTemplate::TPL_PARENT_INDEX => -1, TTemplate::TPL_TYPE => 'injected'];
		$this->assertArrayHasKey(999, $tpl->getItems());
	}

	// -----------------------------------------------------------------------
	// HTML and template comments
	// -----------------------------------------------------------------------

	public function testHtmlCommentPreservedAsText()
	{
		$tpl = $this->newTemplate('before<!-- comment -->after');
		$items = $tpl->getItems();
		$this->assertCount(1, $items);
		$item = array_values($items)[0];
		$data = $item[TTemplate::TPL_TYPE];
		if ($data instanceof TCompositeLiteral) {
			$combined = '';
			$ref = new \ReflectionClass(TCompositeLiteral::class);
			$itemsProp = $ref->getProperty('_items');
			$itemsProp->setAccessible(true);
			foreach ($itemsProp->getValue($data) as $subItem) {
				$combined .= is_string($subItem) ? $subItem : (string) $subItem;
			}
			$this->assertStringContainsString('before', $combined);
			$this->assertStringContainsString('after', $combined);
			$this->assertStringContainsString('<!-- comment -->', $combined);
		} else {
			$this->assertStringContainsString('before', $data);
			$this->assertStringContainsString('after', $data);
			$this->assertStringContainsString('<!-- comment -->', $data);
		}
	}

	public function testTemplateCommentStripped()
	{
		$tpl = $this->newTemplate('a<!--- stripped --->b');
		$items = $tpl->getItems();
		$this->assertCount(1, $items);
		$item = array_values($items)[0];
		$data = $item[TTemplate::TPL_TYPE];
		if ($data instanceof TCompositeLiteral) {
			$combined = '';
			$ref = new \ReflectionClass(TCompositeLiteral::class);
			$itemsProp = $ref->getProperty('_items');
			$itemsProp->setAccessible(true);
			foreach ($itemsProp->getValue($data) as $subItem) {
				$combined .= $subItem;
			}
			$this->assertStringContainsString('a', $combined);
			$this->assertStringContainsString('b', $combined);
			$this->assertStringNotContainsString('stripped', $combined);
		} else {
			$this->assertEquals('ab', $data);
		}
	}
	
/*	public function testTemplateCommentStripped_shortEnd()
	{
		$tpl = $this->newTemplate('a<!--- stripped -->b');
		$items = $tpl->getItems();
		$this->assertCount(1, $items);
		$item = array_values($items)[0];
		$data = $item[TTemplate::TPL_TYPE];
		if ($data instanceof TCompositeLiteral) {
			$combined = '';
			$ref = new \ReflectionClass(TCompositeLiteral::class);
			$itemsProp = $ref->getProperty('_items');
			$itemsProp->setAccessible(true);
			foreach ($itemsProp->getValue($data) as $subItem) {
				$combined .= $subItem;
			}
			$this->assertStringContainsString('a', $combined);
			$this->assertStringContainsString('b', $combined);
			$this->assertStringNotContainsString('stripped', $combined);
		} else {
			$this->assertEquals('ab', $data);
		}
	}
*/

	public function testHtmlEdgeCommentParseVariants()
	{
		$tpl = $this->newTemplateUnvalidated('before<!-- --!>after');
		$items = $tpl->getItems();
		$this->assertIsArray($items);
		$this->assertNotEmpty($items);
	}

	public function testTemplateEdgeCommentParseVariants()
	{
		$tpl = $this->newTemplateUnvalidated('before<!---    --->after');
		$rendered = '';
		foreach ($tpl->getItems() as $item) {
			$data = $item[TTemplate::TPL_TYPE] ?? '';
			if (is_string($data)) {
				$rendered .= $data;
			} elseif ($data instanceof TCompositeLiteral) {
				$data->evaluateDynamicContent();
				$writer = new class () implements \Prado\IO\ITextWriter {
					public $buf = '';
					public function write($s)
					{
						$this->buf .= $s;
					}
					public function writeLine($s = '')
					{
						$this->buf .= $s;
					}
					public function flush()
					{
						return $this->buf;
					}
				};
				$data->render($writer);
				$rendered .= $writer->buf;
			}
		}
		$this->assertEquals('beforeafter', $rendered);
	}

/*
	public function testTemplateEdgeCommentParseVariants_shortEnd()
	{
		$tpl = $this->newTemplateUnvalidated('before<!---    -->after');
		$rendered = '';
		foreach ($tpl->getItems() as $item) {
			$data = $item[TTemplate::TPL_TYPE] ?? '';
			if (is_string($data)) {
				$rendered .= $data;
			} elseif ($data instanceof TCompositeLiteral) {
				$data->evaluateDynamicContent();
				$writer = new class () implements \Prado\IO\ITextWriter {
					public $buf = '';
					public function write($s)
					{
						$this->buf .= $s;
					}
					public function writeLine($s = '')
					{
						$this->buf .= $s;
					}
					public function flush()
					{
						return $this->buf;
					}
				};
				$data->render($writer);
				$rendered .= $writer->buf;
			}
		}
		$this->assertEquals('beforeafter', $rendered);
	}
*/

	public function testHtmlCommentExclamationPreserved()
	{
		$tpl = $this->newTemplate('a<!-- stripped --!>b');
		$items = $tpl->getItems();
		$this->assertCount(1, $items);
		$item = array_values($items)[0];
		$data = $item[TTemplate::TPL_TYPE];
		if ($data instanceof TCompositeLiteral) {
			$combined = '';
			$ref = new \ReflectionClass(TCompositeLiteral::class);
			$itemsProp = $ref->getProperty('_items');
			$itemsProp->setAccessible(true);
			foreach ($itemsProp->getValue($data) as $subItem) {
				$combined .= $subItem;
			}
			$this->assertStringContainsString('a', $combined);
			$this->assertStringContainsString('b', $combined);
			$this->assertStringContainsString('<!-- stripped --!>', $combined);
		} else {
			$this->assertStringContainsString('<!-- stripped --!>', $data);
		}
	}

	public function testHtmlCommentInsidePropertyTagPreservedAsText()
	{
		// HTML comments are NOT matched by REGEX_RULES, so inside <prop:Text> bodies
		// they become part of the property text value — not a separate template item.
		$tpl = $this->newTemplate('<com:TLabel><prop:Text><!-- comment --!>hello</prop:Text></com:TLabel>');
		$items = $tpl->getItems();
		$this->assertCount(1, $items);
		$item = array_values($items)[0];
		$this->assertArrayHasKey(TTemplate::TPL_PROPS, $item);
		$text = $item[TTemplate::TPL_PROPS]['text'];
		$this->assertStringContainsString('<!-- comment --!>', $text[TTemplate::PROP_VALUE]);
		$this->assertStringContainsString('hello', $text[TTemplate::PROP_VALUE]);
	}

	public function testHtmlCommentInComponentAttributeValuePreserved()
	{
		$tpl = $this->newTemplate('<com:TLabel Text="<!-- comment -->hello" />');
		$items = $tpl->getItems();
		$this->assertCount(1, $items);
		$item = array_values($items)[0];
		$text = $item[TTemplate::TPL_PROPS]['text'];
		$this->assertEquals('<!-- comment -->hello', $text[TTemplate::PROP_VALUE]);
	}

	public function testHtmlCommentExclamationInComponentAttributeValuePreserved()
	{
		$tpl = $this->newTemplate('<com:TLabel Text="<!-- comment --!>hello" />');
		$items = $tpl->getItems();
		$this->assertCount(1, $items);
		$item = array_values($items)[0];
		$text = $item[TTemplate::TPL_PROPS]['text'];
		$this->assertEquals('<!-- comment --!>hello', $text[TTemplate::PROP_VALUE]);
	}

	public function testPradoTemplateCommentInPropertyTagBodyStripped()
	{
		$tpl = $this->newTemplate('<com:TLabel><prop:Text><!--- comment --->hello</prop:Text></com:TLabel>');
		$items = $tpl->getItems();
		$item = array_values($items)[0];
		$text = $item[TTemplate::TPL_PROPS]['text'];
		$this->assertStringNotContainsString('<!---', $text[TTemplate::PROP_VALUE]);
		$this->assertStringContainsString('hello', $text[TTemplate::PROP_VALUE]);
	}

	public function testPradoTemplateCommentEndBangInPropertyTagBodyStripped()
	{
		$tpl = $this->newTemplate('<com:TLabel><prop:Text><!--- comment ---!>hello</prop:Text></com:TLabel>');
		$items = $tpl->getItems();
		$item = array_values($items)[0];
		$text = $item[TTemplate::TPL_PROPS]['text'];
		$this->assertStringNotContainsString('<!---', $text[TTemplate::PROP_VALUE]);
		$this->assertStringContainsString('hello', $text[TTemplate::PROP_VALUE]);
	}

/*
	public function testPradoTemplateCommentInPropertyTagBodyStripped_shortEnd()
	{
		$tpl = $this->newTemplate('<com:TLabel><prop:Text><!--- comment -->hello</prop:Text></com:TLabel>');
		$items = $tpl->getItems();
		$item = array_values($items)[0];
		$text = $item[TTemplate::TPL_PROPS]['text'];
		$this->assertStringNotContainsString('<!---', $text[TTemplate::PROP_VALUE]);
		$this->assertStringContainsString('hello', $text[TTemplate::PROP_VALUE]);
	}
	
	public function testPradoTemplateCommentEndBangInPropertyTagBodyStripped_shortEnd()
	{
		$tpl = $this->newTemplate('<com:TLabel><prop:Text><!--- comment --!>hello</prop:Text></com:TLabel>');
		$items = $tpl->getItems();
		$item = array_values($items)[0];
		$text = $item[TTemplate::TPL_PROPS]['text'];
		$this->assertStringNotContainsString('<!---', $text[TTemplate::PROP_VALUE]);
		$this->assertStringContainsString('hello', $text[TTemplate::PROP_VALUE]);
	}
*/

	public function testHtmlCommentInPropertyTagBodyPreserved()
	{
		// HTML comments are not matched by REGEX_RULES; inside a <prop:Text> body
		// the entire content (including the comment) becomes the property value string.
		$tpl = $this->newTemplate('<com:TLabel><prop:Text><!-- comment -->hello</prop:Text></com:TLabel>');
		$items = $tpl->getItems();
		$this->assertCount(1, $items);
		$item = array_values($items)[0];
$this->assertArrayHasKey(TTemplate::TPL_PROPS, $item);
		$text = $item[TTemplate::TPL_PROPS]['text'];
		$this->assertStringContainsString('<!-- comment -->', $text[TTemplate::PROP_VALUE]);
		$this->assertStringContainsString('hello', $text[TTemplate::PROP_VALUE]);
	}
	
	public function testHtmlCommentEndBangInPropertyTagBodyPreserved()
	{
		// HTML comments (including --!> variant) are not matched by REGEX_RULES;
		// inside a <prop:Text> body the full content becomes the property value string.
		$tpl = $this->newTemplate('<com:TLabel><prop:Text><!-- comment --!>hello</prop:Text></com:TLabel>');
		$items = $tpl->getItems();
		$this->assertCount(1, $items);
		$item = array_values($items)[0];
		$this->assertArrayHasKey(TTemplate::TPL_PROPS, $item);
		$text = $item[TTemplate::TPL_PROPS]['text'];
		$this->assertStringContainsString('<!-- comment --!>', $text[TTemplate::PROP_VALUE]);
		$this->assertStringContainsString('hello', $text[TTemplate::PROP_VALUE]);
	}
	
	public function testPradoTemplateCommentInComponentAttributePreserved()
	{
		$tpl = $this->newTemplate('<com:TLabel Text="<!--- comment --->hello" />');
		$items = $tpl->getItems();
		$item = array_values($items)[0];
		$text = $item[TTemplate::TPL_PROPS]['text'];
		$this->assertStringContainsString('<!--- comment --->', $text[TTemplate::PROP_VALUE]);
		$this->assertStringContainsString('hello', $text[TTemplate::PROP_VALUE]);
	}
	
	public function testPradoTemplateCommentEndBangInComponentAttributePreserved()
	{
		$tpl = $this->newTemplate('<com:TLabel Text="<!--- comment ---!>hello" />');
		$items = $tpl->getItems();
		$item = array_values($items)[0];
		$text = $item[TTemplate::TPL_PROPS]['text'];
		$this->assertStringContainsString('<!--- comment ---!>', $text[TTemplate::PROP_VALUE]);
		$this->assertStringContainsString('hello', $text[TTemplate::PROP_VALUE]);
	}
	
/*
	public function testPradoTemplateCommentInComponentAttributePreserved_shortEnd()
	{
		$tpl = $this->newTemplate('<com:TLabel Text="<!--- comment -->hello" />');
		$items = $tpl->getItems();
		$item = array_values($items)[0];
		$text = $item[TTemplate::TPL_PROPS]['text'];
		$this->assertStringContainsString('<!--- comment -->', $text[TTemplate::PROP_VALUE]);
		$this->assertStringContainsString('hello', $text[TTemplate::PROP_VALUE]);
	}
	
	public function testPradoTemplateCommentEndBangInComponentAttributePreserved_shortEnd()
	{
		$tpl = $this->newTemplate('<com:TLabel Text="<!--- comment --!>hello" />');
		$items = $tpl->getItems();
		$item = array_values($items)[0];
		$text = $item[TTemplate::TPL_PROPS]['text'];
		$this->assertStringContainsString('<!--- comment --!>', $text[TTemplate::PROP_VALUE]);
		$this->assertStringContainsString('hello', $text[TTemplate::PROP_VALUE]);
	}
*/

	// -----------------------------------------------------------------------
	// Component tag parsing
	// -----------------------------------------------------------------------

	public function testComponentTagParsing()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="label1" Text="Hello" />');
		$items = $tpl->getItems();
		$this->assertCount(1, $items);
		$item = array_values($items)[0];
		$this->assertStringContainsString('TLabel', $item[TTemplate::TPL_TYPE]);
		$this->assertArrayHasKey('id', $item[TTemplate::TPL_PROPS]);
		$this->assertEquals('Hello', $item[TTemplate::TPL_PROPS]['text'][TTemplate::PROP_VALUE]);
	}

	public function testComponentTagReturnsFQCN()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1" />');
		$item = array_values($tpl->getItems())[0];
		$this->assertEquals('Prado\\Web\\UI\\WebControls\\TLabel', $item[TTemplate::TPL_TYPE]);
	}

	public function testAttributeKeyIsLowered()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1" Text="Hello" />');
		$item = array_values($tpl->getItems())[0];
		$this->assertArrayHasKey('id', $item[TTemplate::TPL_PROPS]);
		$this->assertArrayHasKey('text', $item[TTemplate::TPL_PROPS]);
	}

	public function testComponentTagWithSubProperties()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="label1" Font.Size="12" />');
		$item = array_values($tpl->getItems())[0];
		$this->assertArrayHasKey('font.size', $item[TTemplate::TPL_PROPS]);
		$this->assertEquals(TTemplate::CONFIG_VALUE, $item[TTemplate::TPL_PROPS]['font.size'][TTemplate::PROP_TYPE]);
		$this->assertEquals('Font.Size', $item[TTemplate::TPL_PROPS]['font.size'][TTemplate::PROP_NAME]);
	}

	public function testSubpropertyFontDashAttribute()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1" Font.Size="12px" />');
		$item = array_values($tpl->getItems())[0];
		$prop = $item[TTemplate::TPL_PROPS]['font.size'];
		$this->assertEquals('Font.Size', $prop[TTemplate::PROP_NAME]);
		$this->assertEquals('12px', $prop[TTemplate::PROP_VALUE]);
	}

	public function testComponentTagSelfClosingWithSubProperty()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1" Font.Size="12" Font.Bold="true" />');
		$component = $this->findComponent($tpl->getItems());
		$this->assertArrayHasKey('font.size', $component[TTemplate::TPL_PROPS]);
		$this->assertArrayHasKey('font.bold', $component[TTemplate::TPL_PROPS]);
		$this->assertEquals('12', $component[TTemplate::TPL_PROPS]['font.size'][TTemplate::PROP_VALUE]);
		$this->assertEquals('true', $component[TTemplate::TPL_PROPS]['font.bold'][TTemplate::PROP_VALUE]);
	}

	public function testComponentTagWithClosingTag()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1">Hello</com:TLabel>');
		$this->assertCount(2, $tpl->getItems());
	}

	public function testComponentTagWithBodyText()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1">Hello</com:TLabel>');
		$items = $tpl->getItems();
		$this->assertCount(2, $items);
		$itemValues = array_values($items);
		$component = null;
		$textItem = null;
		foreach ($itemValues as $item) {
			if (isset($item[TTemplate::TPL_PROPS])) {
				$component = $item;
			} else {
				$textItem = $item;
			}
		}
		$this->assertNotNull($component);
		$this->assertNotNull($textItem);
		$this->assertEquals('Hello', $textItem[TTemplate::TPL_TYPE]);
		$this->assertEquals(0, $textItem[TTemplate::TPL_PARENT_INDEX]);
	}

	public function testMixedContentAndComponents()
	{
		$tpl = $this->newTemplate('before<com:TLabel ID="lbl1" />after');
		$this->assertCount(3, $tpl->getItems());
	}

	public function testMultipleComponentsInSequence()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="a" /><com:TLabel ID="b" /><com:TLabel ID="c" />');
		$this->assertCount(3, $tpl->getItems());
	}

	public function testNestedComponents()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="outer"><com:TLabel ID="inner" /></com:TLabel>');
		$this->assertCount(2, $tpl->getItems());
	}

	public function testNestedComponentParentIndex()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="outer"><com:TLabel ID="inner" Text="Hello" /></com:TLabel>');
		$items = $tpl->getItems();
		$itemValues = array_values($items);
		$outerIndex = null;
		$innerParentIndex = null;
		foreach ($itemValues as $idx => $item) {
			if (isset($item[TTemplate::TPL_PROPS]) && isset($item[TTemplate::TPL_PROPS]['id'])) {
				if ($item[TTemplate::TPL_PROPS]['id'][TTemplate::PROP_VALUE] === 'outer') {
					$outerIndex = $idx;
				}
				if ($item[TTemplate::TPL_PROPS]['id'][TTemplate::PROP_VALUE] === 'inner') {
					$innerParentIndex = $item[TTemplate::TPL_PARENT_INDEX];
				}
			}
		}
		$this->assertNotNull($outerIndex);
		$this->assertEquals($outerIndex, $innerParentIndex);
	}

	public function testOpenComponentTagContainerStacking()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="l1"><com:TLabel ID="l2"><com:TLabel ID="l3" /></com:TLabel></com:TLabel>');
		$items = $tpl->getItems();
		$this->assertCount(3, $items);
		$itemValues = array_values($items);
		$indices = [];
		foreach ($itemValues as $idx => $item) {
			if (isset($item[TTemplate::TPL_PROPS]) && isset($item[TTemplate::TPL_PROPS]['id'])) {
				$indices[$item[TTemplate::TPL_PROPS]['id'][TTemplate::PROP_VALUE]] = ['index' => $idx, 'parent' => $item[TTemplate::TPL_PARENT_INDEX]];
			}
		}
		$this->assertEquals(-1, $indices['l1']['parent']);
		$this->assertEquals($indices['l1']['index'], $indices['l2']['parent']);
		$this->assertEquals($indices['l2']['index'], $indices['l3']['parent']);
	}

	public function testComponentWithTextBeforeAndAfterNestedComponent()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="outer">before<com:TLabel ID="inner" />after</com:TLabel>');
		$items = $tpl->getItems();
		$this->assertCount(4, $items);
		$outer = null;
		$inner = null;
		$texts = [];
		foreach ($items as $item) {
			if (isset($item[TTemplate::TPL_PROPS])) {
				if ($item[TTemplate::TPL_PROPS]['id'][TTemplate::PROP_VALUE] === 'outer') {
					$outer = $item;
				} elseif ($item[TTemplate::TPL_PROPS]['id'][TTemplate::PROP_VALUE] === 'inner') {
					$inner = $item;
				}
			} else {
				$texts[] = $item;
			}
		}
		$this->assertNotNull($outer);
		$this->assertNotNull($inner);
		$this->assertCount(2, $texts);
		$this->assertEquals('before', $texts[0][TTemplate::TPL_TYPE]);
		$this->assertEquals('after', $texts[1][TTemplate::TPL_TYPE]);
	}

	public function testComponentWithNoAttributes()
	{
		$tpl = $this->newTemplate('<com:TLabel />');
		$items = $tpl->getItems();
		$this->assertCount(1, $items);
		$this->assertCount(0, array_values($items)[0][TTemplate::TPL_PROPS]);
	}

	public function testComponentNamespaceHandling()
	{
		$tpl = $this->newTemplate('<com:Prado\\Web\\UI\\WebControls\\TLabel ID="lbl1" />');
		$item = array_values($tpl->getItems())[0];
		$this->assertEquals('Prado\\Web\\UI\\WebControls\\TLabel', $item[TTemplate::TPL_TYPE]);
	}

	public function testComponentTagWithPrado3SystemDotNotation()
	{
		// Prado3-style System.* dot-notation is converted to PHP FQN via usingClass()
		$tpl = $this->newTemplate('<com:System.Web.UI.WebControls.TLabel ID="lbl1" />');
		$item = array_values($tpl->getItems())[0];
		$this->assertEquals('Prado\\Web\\UI\\WebControls\\TLabel', $item[TTemplate::TPL_TYPE]);
	}

	public function testComponentTagWithPrado3PradoDotNotation()
	{
		// Prado3-style Prado.* dot-notation (dots-to-backslashes) is resolved to PHP FQN
		$tpl = $this->newTemplate('<com:Prado.Web.UI.WebControls.TLabel ID="lbl1" />');
		$item = array_values($tpl->getItems())[0];
		$this->assertEquals('Prado\\Web\\UI\\WebControls\\TLabel', $item[TTemplate::TPL_TYPE]);
	}

	public function testComponentTagWithAttributeExpressionValue()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1" Text=<%= $this->Name %> />');
		$items = $tpl->getItems();
		$this->assertCount(1, $items);
		$component = $this->findComponent($items);
		$this->assertArrayHasKey('text', $component[TTemplate::TPL_PROPS]);
		$this->assertEquals(TTemplate::CONFIG_EXPRESSION, $component[TTemplate::TPL_PROPS]['text'][TTemplate::PROP_TYPE]);
	}

	public function testStartingLine()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1" />', null, null, 5);
		$this->assertCount(1, $tpl->getItems());
	}

	// -----------------------------------------------------------------------
	// Property tags
	// -----------------------------------------------------------------------

	public function testPropertyTag()
	{
		$tpl = $this->newTemplate('<com:TLabel><prop:Text>Hello World</prop:Text></com:TLabel>');
		$component = $this->findComponent($tpl->getItems());
		$this->assertArrayHasKey('text', $component[TTemplate::TPL_PROPS]);
		$textProp = $component[TTemplate::TPL_PROPS]['text'];
		$this->assertEquals(TTemplate::CONFIG_VALUE, $textProp[TTemplate::PROP_TYPE]);
		$this->assertEquals('Hello World', $textProp[TTemplate::PROP_VALUE]);
		$this->assertEquals('Text', $textProp[TTemplate::PROP_NAME]);
	}

	public function testPropertyTagCasePreserved()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1"><prop:Text>Hello</prop:Text></com:TLabel>');
		$component = $this->findComponent($tpl->getItems());
		$prop = $component[TTemplate::TPL_PROPS]['text'];
		$this->assertEquals('Text', $prop[TTemplate::PROP_NAME]);
	}

	public function testGroupSubPropertyTag()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1"><prop:Font Size="12" Bold="true" /></com:TLabel>');
		$component = $this->findComponent($tpl->getItems());
		$this->assertArrayHasKey('font.size', $component[TTemplate::TPL_PROPS]);
		$this->assertArrayHasKey('font.bold', $component[TTemplate::TPL_PROPS]);
		$this->assertEquals('Font.Size', $component[TTemplate::TPL_PROPS]['font.size'][TTemplate::PROP_NAME]);
		$this->assertEquals('Font.Bold', $component[TTemplate::TPL_PROPS]['font.bold'][TTemplate::PROP_NAME]);
	}

	public function testGroupSubPropertyTagValues()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1"><prop:Font Size="14" Bold="true" /></com:TLabel>');
		$component = $this->findComponent($tpl->getItems());
		$sizeProp = $component[TTemplate::TPL_PROPS]['font.size'];
		$boldProp = $component[TTemplate::TPL_PROPS]['font.bold'];
		$this->assertEquals('14', $sizeProp[TTemplate::PROP_VALUE]);
		$this->assertEquals('true', $boldProp[TTemplate::PROP_VALUE]);
		$this->assertEquals(TTemplate::CONFIG_VALUE, $sizeProp[TTemplate::PROP_TYPE]);
		$this->assertEquals(TTemplate::CONFIG_VALUE, $boldProp[TTemplate::PROP_TYPE]);
	}

	public function testTemplatePropertyViaTag()
	{
		$tpl = $this->newTemplateUnvalidated('<com:TComponent ID="c1"><prop:Template>inner content</prop:Template></com:TComponent>');
		$component = $this->findComponent($tpl->getItems());
		$this->assertArrayHasKey('template', $component[TTemplate::TPL_PROPS]);
		$tplProp = $component[TTemplate::TPL_PROPS]['template'];
		$this->assertEquals(TTemplate::CONFIG_TEMPLATE, $tplProp[TTemplate::PROP_TYPE]);
		$this->assertInstanceOf(TTemplate::class, $tplProp[TTemplate::PROP_VALUE]);
	}

	public function testTemplatePropertyViaAttribute()
	{
		$tpl = $this->newTemplateUnvalidated('<com:TComponent ID="c1" Template="text content" />');
		$component = $this->findComponent($tpl->getItems());
		$this->assertArrayHasKey('template', $component[TTemplate::TPL_PROPS]);
		$tplProp = $component[TTemplate::TPL_PROPS]['template'];
		$this->assertEquals(TTemplate::CONFIG_TEMPLATE, $tplProp[TTemplate::PROP_TYPE]);
		$this->assertInstanceOf(TTemplate::class, $tplProp[TTemplate::PROP_VALUE]);
	}

	public function testTemplatePropertyViaTagCreatesTTemplate()
	{
		$tpl = $this->newTemplateUnvalidated('<com:TComponent ID="c1" Template="inner content" />');
		$component = $this->findComponent($tpl->getItems());
		$tplProp = $component[TTemplate::TPL_PROPS]['template'];
		$this->assertEquals(TTemplate::CONFIG_TEMPLATE, $tplProp[TTemplate::PROP_TYPE]);
		$this->assertInstanceOf(TTemplate::class, $tplProp[TTemplate::PROP_VALUE]);
	}

	public function testTemplatePropertyTagEndingInTemplate()
	{
		$tpl = $this->newTemplateUnvalidated('<com:TComponent ID="c1"><prop:Itemtemplate>content</prop:Itemtemplate></com:TComponent>');
		$component = $this->findComponent($tpl->getItems());
		$this->assertArrayHasKey('itemtemplate', $component[TTemplate::TPL_PROPS]);
		$tplProp = $component[TTemplate::TPL_PROPS]['itemtemplate'];
		$this->assertEquals(TTemplate::CONFIG_TEMPLATE, $tplProp[TTemplate::PROP_TYPE]);
		$this->assertInstanceOf(TTemplate::class, $tplProp[TTemplate::PROP_VALUE]);
	}

	public function testTemplatePropertyTagEndingInTemplateCaseInsensitive()
	{
		// 'ItemTemplate' ends in 'template' only when lowercased — the check is `substr($propName, -8, 8) === 'template'`
		// which is case-sensitive, so 'ItemTemplate' (capital T) does NOT match and is treated as CONFIG_VALUE
		$tpl = $this->newTemplateUnvalidated('<com:TComponent ID="c1"><prop:ItemTemplate>content</prop:ItemTemplate></com:TComponent>');
		$component = $this->findComponent($tpl->getItems());
		$this->assertArrayHasKey('itemtemplate', $component[TTemplate::TPL_PROPS]);
		$tplProp = $component[TTemplate::TPL_PROPS]['itemtemplate'];
		$this->assertEquals(TTemplate::CONFIG_TEMPLATE, $tplProp[TTemplate::PROP_TYPE]);
		$this->assertInstanceOf(TTemplate::class, $tplProp[TTemplate::PROP_VALUE]);
	}

	public function testNestedPropertyTagInsideComponent()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1"><prop:Text><prop:SubTag>ignored</prop:SubTag></prop:Text></com:TLabel>');
		$component = $this->findComponent($tpl->getItems());
		$this->assertArrayHasKey('text', $component[TTemplate::TPL_PROPS]);
	}

	// -----------------------------------------------------------------------
	// Attribute name transformations (dash, case, subproperty)
	// -----------------------------------------------------------------------

	public function testAttributeNameDashToUnderscorePropagation()
	{
		$tpl = $this->newTemplateUnvalidated('<com:TComponent ID="c1" data-toggle="modal" />');
		$component = $this->findComponent($tpl->getItems());
		$this->assertArrayHasKey('data-toggle', $component[TTemplate::TPL_PROPS]);
		$attr = $component[TTemplate::TPL_PROPS]['data-toggle'];
		$this->assertEquals('data-toggle', $attr[TTemplate::PROP_NAME]);
		$this->assertEquals('modal', $attr[TTemplate::PROP_VALUE]);
		$this->assertEquals(TTemplate::CONFIG_VALUE, $attr[TTemplate::PROP_TYPE]);
	}

	public function testAttributeNameCaseIsPreservedInPropName()
	{
		$tpl = $this->newTemplateUnvalidated('<com:TComponent ID="c1" ThemeColor="blue" />');
		$component = $this->findComponent($tpl->getItems());
		$this->assertArrayHasKey('themecolor', $component[TTemplate::TPL_PROPS]);
		$attr = $component[TTemplate::TPL_PROPS]['themecolor'];
		$this->assertEquals('ThemeColor', $attr[TTemplate::PROP_NAME]);
	}

	public function testSubpropertyDashInAttributeName()
	{
		$tpl = $this->newTemplateUnvalidated('<com:TComponent ID="c1" Style.Font-Size="12px" />');
		$component = $this->findComponent($tpl->getItems());
		$this->assertArrayHasKey('style.font-size', $component[TTemplate::TPL_PROPS]);
		$prop = $component[TTemplate::TPL_PROPS]['style.font-size'];
		$this->assertEquals('Style.Font-Size', $prop[TTemplate::PROP_NAME]);
	}

	public function testGroupNameSubPropertyDashPreservedInPropName()
	{
		$tpl = $this->newTemplateUnvalidated('<com:TComponent ID="c1"><prop:Font Data-Toggle="dropdown" /></com:TComponent>');
		$component = $this->findComponent($tpl->getItems());
		$this->assertArrayHasKey('font.data-toggle', $component[TTemplate::TPL_PROPS]);
		$attr = $component[TTemplate::TPL_PROPS]['font.data-toggle'];
		$this->assertEquals('Font.Data-Toggle', $attr[TTemplate::PROP_NAME]);
	}

	public function testPropertyNameDashInPropertyTag()
	{
		$tpl = $this->newTemplateUnvalidated('<com:TComponent ID="c1"><prop:data-value>123</prop:data-value></com:TComponent>');
		$component = $this->findComponent($tpl->getItems());
		$this->assertArrayHasKey('data-value', $component[TTemplate::TPL_PROPS]);
		$prop = $component[TTemplate::TPL_PROPS]['data-value'];
		$this->assertEquals('data-value', $prop[TTemplate::PROP_NAME]);
		$this->assertEquals('123', $prop[TTemplate::PROP_VALUE]);
	}

	public function testPropertyTagWithDashPreservesCaseUnvalidated()
	{
		$tpl = $this->newTemplateUnvalidated('<com:TComponent ID="c1"><prop:Custom-Prop>value</prop:Custom-Prop></com:TComponent>');
		$component = $this->findComponent($tpl->getItems());
		$this->assertArrayHasKey('custom-prop', $component[TTemplate::TPL_PROPS]);
		$prop = $component[TTemplate::TPL_PROPS]['custom-prop'];
		$this->assertEquals('Custom-Prop', $prop[TTemplate::PROP_NAME]);
		$this->assertEquals('value', $prop[TTemplate::PROP_VALUE]);
	}

	public function testAttributeToMethodNameConvertsDashToUnderscore()
	{
		$tpl = $this->newTemplate('');
		$tplClass = new \ReflectionClass(TTemplate::class);
		$method = $tplClass->getMethod('attributeToMethodName');
		$method->setAccessible(true);
		$this->assertEquals('data_toggle', $method->invoke($tpl, 'data-toggle'));
		$this->assertEquals('Font_Size', $method->invoke($tpl, 'Font-Size'));
		$this->assertEquals('nocase', $method->invoke($tpl, 'nocase'));
		$this->assertEquals('multi_dash_name', $method->invoke($tpl, 'multi-dash-name'));
	}

	public function testAttributeWithSingleQuotes()
	{
		$tpl = $this->newTemplate("<com:TLabel ID='lbl1' Text='Hello' />");
		$component = $this->findComponent($tpl->getItems());
		$this->assertEquals('Hello', $component[TTemplate::TPL_PROPS]['text'][TTemplate::PROP_VALUE]);
	}

	public function testComponentWithOnlySingleQuotedAttributes()
	{
		$tpl = $this->newTemplate("<com:TLabel ID='lbl1' Text='Hello' Font.Size='12' />");
		$component = $this->findComponent($tpl->getItems());
		$this->assertEquals('lbl1', $component[TTemplate::TPL_PROPS]['id'][TTemplate::PROP_VALUE]);
		$this->assertEquals('Hello', $component[TTemplate::TPL_PROPS]['text'][TTemplate::PROP_VALUE]);
		$this->assertEquals('12', $component[TTemplate::TPL_PROPS]['font.size'][TTemplate::PROP_VALUE]);
	}

	public function testAttributeWithSpacesAroundEqual()
	{
		$tpl = $this->newTemplate('<com:TLabel ID = "lbl1" />');
		$this->assertCount(1, $tpl->getItems());
		$this->assertEquals('lbl1', array_values($tpl->getItems())[0][TTemplate::TPL_PROPS]['id'][TTemplate::PROP_VALUE]);
	}

	public function testAttributeWithEqualSignInValue()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1" Text="a=b" />');
		$this->assertEquals('a=b', $this->findComponent($tpl->getItems())[TTemplate::TPL_PROPS]['text'][TTemplate::PROP_VALUE]);
	}

	public function testEmptyAttributeValue()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1" Text="" />');
		$component = $this->findComponent($tpl->getItems());
		$this->assertEquals('', $component[TTemplate::TPL_PROPS]['text'][TTemplate::PROP_VALUE]);
	}

	public function testAttributeWithExpressionInSingleQuotes()
	{
		$tpl = $this->newTemplate("<com:TLabel ID='lbl1' Text='<%= \$this->Name %>' />");
		$component = $this->findComponent($tpl->getItems());
		$this->assertEquals(TTemplate::CONFIG_EXPRESSION, $component[TTemplate::TPL_PROPS]['text'][TTemplate::PROP_TYPE]);
	}

	public function testAttributeWithExpressionInQuotes()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1" Text="<%= $this->Title %>" />');
		$component = $this->findComponent($tpl->getItems());
		$this->assertEquals(TTemplate::CONFIG_EXPRESSION, $component[TTemplate::TPL_PROPS]['text'][TTemplate::PROP_TYPE]);
	}

	// -----------------------------------------------------------------------
	// Expression types in component attributes
	// -----------------------------------------------------------------------

	public function testDatabindExpression()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1" Text="<%# $this->Data %>" />');
		$component = $this->findComponent($tpl->getItems());
		$textProp = $component[TTemplate::TPL_PROPS]['text'];
		$this->assertEquals(TTemplate::CONFIG_DATABIND, $textProp[TTemplate::PROP_TYPE]);
		$this->assertStringContainsString('$this->Data', $textProp[TTemplate::PROP_VALUE]);
	}

	public function testParameterExpressionValueContent()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1" Text="<%$ MyParam %>" />');
		$component = $this->findComponent($tpl->getItems());
		$textProp = $component[TTemplate::TPL_PROPS]['text'];
		$this->assertEquals(TTemplate::CONFIG_PARAMETER, $textProp[TTemplate::PROP_TYPE]);
		$this->assertEquals('MyParam', $textProp[TTemplate::PROP_VALUE]);
	}

	public function testAssetExpressionValueContent()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1" Text="<%~ images/logo.png %>" />');
		$component = $this->findComponent($tpl->getItems());
		$textProp = $component[TTemplate::TPL_PROPS]['text'];
		$this->assertEquals(TTemplate::CONFIG_ASSET, $textProp[TTemplate::PROP_TYPE]);
		$this->assertEquals('images/logo.png', $textProp[TTemplate::PROP_VALUE]);
	}

	public function testLocalizationExpressionValueContent()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1" Text="<%[ Hello World ]%>" />');
		$component = $this->findComponent($tpl->getItems());
		$textProp = $component[TTemplate::TPL_PROPS]['text'];
		$this->assertEquals(TTemplate::CONFIG_LOCALIZATION, $textProp[TTemplate::PROP_TYPE]);
		$this->assertEquals('Hello World', $textProp[TTemplate::PROP_VALUE]);
	}

	public function testUrlExpressionValueContent()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1" Text="<%/ path/to/resource %>" />');
		$component = $this->findComponent($tpl->getItems());
		$textProp = $component[TTemplate::TPL_PROPS]['text'];
		$this->assertEquals(TTemplate::CONFIG_EXPRESSION, $textProp[TTemplate::PROP_TYPE]);
		$this->assertStringContainsString('path/to/resource', $textProp[TTemplate::PROP_VALUE]);
	}

	public function testExpressionAttributeValueContent()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1" Text="<%= $this->Title %>" />');
		$component = $this->findComponent($tpl->getItems());
		$textProp = $component[TTemplate::TPL_PROPS]['text'];
		$this->assertEquals(TTemplate::CONFIG_EXPRESSION, $textProp[TTemplate::PROP_TYPE]);
		$this->assertStringContainsString('$this->Title', $textProp[TTemplate::PROP_VALUE]);
	}

	public function testMixedExpressionAndStaticText()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1" Text="prefix<%= $this->Name %>suffix" />');
		$component = $this->findComponent($tpl->getItems());
		$this->assertEquals(TTemplate::CONFIG_EXPRESSION, $component[TTemplate::TPL_PROPS]['text'][TTemplate::PROP_TYPE]);
	}

	public function testMixedDatabindAndStaticText()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1" Text="a<%# $this->Data %>b" />');
		$component = $this->findComponent($tpl->getItems());
		$this->assertEquals(TTemplate::CONFIG_DATABIND, $component[TTemplate::TPL_PROPS]['text'][TTemplate::PROP_TYPE]);
	}

	public function testMixedExpressionAndDatabindInAttribute()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1" Text="a<%# $this->Data %>b<%= $this->Name %>c" />');
		$component = $this->findComponent($tpl->getItems());
		$textProp = $component[TTemplate::TPL_PROPS]['text'];
		$this->assertEquals(TTemplate::CONFIG_DATABIND, $textProp[TTemplate::PROP_TYPE]);
	}

	public function testMixedDatabindAndExpressionInAttribute()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1" Text="<%# $data %><%= $expr %>" />');
		$component = $this->findComponent($tpl->getItems());
		$textProp = $component[TTemplate::TPL_PROPS]['text'];
		$this->assertEquals(TTemplate::CONFIG_DATABIND, $textProp[TTemplate::PROP_TYPE]);
	}

	// -----------------------------------------------------------------------
	// Inline expressions (top-level template items)
	// -----------------------------------------------------------------------

	public function testExpressionTag()
	{
		$tpl = $this->newTemplate('<%= $this->Property %>');
		$items = $tpl->getItems();
		$this->assertCount(1, $items);
		$item = array_values($items)[0];
		$this->assertInstanceOf(TCompositeLiteral::class, $item[TTemplate::TPL_TYPE]);
		$this->assertEquals(-1, $item[TTemplate::TPL_PARENT_INDEX]);
		$ref = new \ReflectionClass(TCompositeLiteral::class);
		$exprProp = $ref->getProperty('_expressions');
		$exprProp->setAccessible(true);
		$expressions = $exprProp->getValue($item[TTemplate::TPL_TYPE]);
		$this->assertCount(1, $expressions);
		$this->assertStringContainsString('$this->Property', array_values($expressions)[0]);
	}

	public function testStatementsTag()
	{
		$tpl = $this->newTemplate('<%% echo "hello"; %>');
		$items = $tpl->getItems();
		$this->assertCount(1, $items);
		$item = array_values($items)[0];
		$this->assertInstanceOf(TCompositeLiteral::class, $item[TTemplate::TPL_TYPE]);
		$ref = new \ReflectionClass(TCompositeLiteral::class);
		$stmtProp = $ref->getProperty('_statements');
		$stmtProp->setAccessible(true);
		$statements = $stmtProp->getValue($item[TTemplate::TPL_TYPE]);
		$this->assertCount(1, $statements);
		$this->assertStringContainsString('echo "hello";', array_values($statements)[0]);
	}

	public function testInlineExpression()
	{
		$tpl = $this->newTemplate('Hello <%= $this->Name %>!');
		$items = $tpl->getItems();
		$this->assertCount(1, $items);
		$item = array_values($items)[0];
		$this->assertInstanceOf(TCompositeLiteral::class, $item[TTemplate::TPL_TYPE]);
		$ref = new \ReflectionClass(TCompositeLiteral::class);
		$exprProp = $ref->getProperty('_expressions');
		$exprProp->setAccessible(true);
		$itemsProp = $ref->getProperty('_items');
		$itemsProp->setAccessible(true);
		$expressions = $exprProp->getValue($item[TTemplate::TPL_TYPE]);
		$literalItems = $itemsProp->getValue($item[TTemplate::TPL_TYPE]);
		$this->assertCount(1, $expressions);
		$this->assertStringContainsString('$this->Name', array_values($expressions)[0]);
		$this->assertCount(3, $literalItems);
	}

	public function testInlineDatabindExpression()
	{
		$tpl = $this->newTemplate('<%# $this->Data %>');
		$items = $tpl->getItems();
		$this->assertCount(1, $items);
		$item = array_values($items)[0];
		$this->assertInstanceOf(TCompositeLiteral::class, $item[TTemplate::TPL_TYPE]);
		$ref = new \ReflectionClass(TCompositeLiteral::class);
		$bindProp = $ref->getProperty('_bindings');
		$bindProp->setAccessible(true);
		$bindings = $bindProp->getValue($item[TTemplate::TPL_TYPE]);
		$this->assertCount(1, $bindings);
		$this->assertStringContainsString('$this->Data', array_values($bindings)[0]);
	}

	public function testInlineParameterExpression()
	{
		$tpl = $this->newTemplate('<%$ ParamName %>');
		$items = $tpl->getItems();
		$this->assertCount(1, $items);
		$item = array_values($items)[0];
		$this->assertInstanceOf(TCompositeLiteral::class, $item[TTemplate::TPL_TYPE]);
		$ref = new \ReflectionClass(TCompositeLiteral::class);
		$exprProp = $ref->getProperty('_expressions');
		$exprProp->setAccessible(true);
		$expressions = $exprProp->getValue($item[TTemplate::TPL_TYPE]);
		$this->assertCount(1, $expressions);
		$this->assertStringContainsString('getParameters', array_values($expressions)[0]);
		$this->assertStringContainsString('ParamName', array_values($expressions)[0]);
	}

	public function testInlineAssetExpression()
	{
		$items = $this->newTemplate('<%~ assets/logo.png %>')->getItems();
		$this->assertCount(1, $items);
		$item = array_values($items)[0];
		$this->assertInstanceOf(TCompositeLiteral::class, $item[TTemplate::TPL_TYPE]);
		$ref = new \ReflectionClass(TCompositeLiteral::class);
		$exprProp = $ref->getProperty('_expressions');
		$exprProp->setAccessible(true);
		$expressions = $exprProp->getValue($item[TTemplate::TPL_TYPE]);
		$this->assertCount(1, $expressions);
		$this->assertStringContainsString('publishFilePath', array_values($expressions)[0]);
		$this->assertStringContainsString('assets/logo.png', array_values($expressions)[0]);
	}

	public function testInlineUrlExpression()
	{
		$items = $this->newTemplate('<%/ path/to/page %>')->getItems();
		$this->assertCount(1, $items);
		$item = array_values($items)[0];
		$this->assertInstanceOf(TCompositeLiteral::class, $item[TTemplate::TPL_TYPE]);
		$ref = new \ReflectionClass(TCompositeLiteral::class);
		$exprProp = $ref->getProperty('_expressions');
		$exprProp->setAccessible(true);
		$expressions = $exprProp->getValue($item[TTemplate::TPL_TYPE]);
		$this->assertCount(1, $expressions);
		$this->assertStringContainsString('path/to/page', array_values($expressions)[0]);
	}

	public function testInlineLocalizationExpression()
	{
		$items = $this->newTemplate('<%[ Hello ]%>')->getItems();
		$this->assertCount(1, $items);
		$item = array_values($items)[0];
		$this->assertInstanceOf(TCompositeLiteral::class, $item[TTemplate::TPL_TYPE]);
		$ref = new \ReflectionClass(TCompositeLiteral::class);
		$exprProp = $ref->getProperty('_expressions');
		$exprProp->setAccessible(true);
		$expressions = $exprProp->getValue($item[TTemplate::TPL_TYPE]);
		$this->assertCount(1, $expressions);
		$this->assertStringContainsString('localize', array_values($expressions)[0]);
		$this->assertStringContainsString('Hello', array_values($expressions)[0]);
	}

	public function testMultipleInlineExpressions()
	{
		$tpl = $this->newTemplate('<%= $a %><%= $b %>');
		$items = $tpl->getItems();
		$this->assertCount(1, $items);
		$item = array_values($items)[0];
		$this->assertInstanceOf(TCompositeLiteral::class, $item[TTemplate::TPL_TYPE]);
		$ref = new \ReflectionClass(TCompositeLiteral::class);
		$exprProp = $ref->getProperty('_expressions');
		$exprProp->setAccessible(true);
		$expressions = $exprProp->getValue($item[TTemplate::TPL_TYPE]);
		$this->assertCount(2, $expressions);
	}

	// -----------------------------------------------------------------------
	// Property tag expressions
	// -----------------------------------------------------------------------

	public function testPropertyTagWithExpression()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1"><prop:Text><%= $this->Name %></prop:Text></com:TLabel>');
		$component = $this->findComponent($tpl->getItems());
		$prop = $component[TTemplate::TPL_PROPS]['text'];
		$this->assertEquals(TTemplate::CONFIG_EXPRESSION, $prop[TTemplate::PROP_TYPE]);
	}

	public function testPropertyTagWithParameterExpression()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1"><prop:Text><%$ AppParam %></prop:Text></com:TLabel>');
		$component = $this->findComponent($tpl->getItems());
		$prop = $component[TTemplate::TPL_PROPS]['text'];
		$this->assertEquals(TTemplate::CONFIG_PARAMETER, $prop[TTemplate::PROP_TYPE]);
	}

	public function testPropertyTagWithAssetExpression()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1"><prop:Text><%~ assets/img.png %></prop:Text></com:TLabel>');
		$component = $this->findComponent($tpl->getItems());
		$prop = $component[TTemplate::TPL_PROPS]['text'];
		$this->assertEquals(TTemplate::CONFIG_ASSET, $prop[TTemplate::PROP_TYPE]);
	}

	public function testPropertyTagWithLocalization()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1"><prop:Text><%[ Hello ]%></prop:Text></com:TLabel>');
		$component = $this->findComponent($tpl->getItems());
		$prop = $component[TTemplate::TPL_PROPS]['text'];
		$this->assertEquals(TTemplate::CONFIG_LOCALIZATION, $prop[TTemplate::PROP_TYPE]);
	}

	public function testPropertyTagWithDatabind()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1"><prop:Text><%# $this->Data %></prop:Text></com:TLabel>');
		$component = $this->findComponent($tpl->getItems());
		$prop = $component[TTemplate::TPL_PROPS]['text'];
		$this->assertEquals(TTemplate::CONFIG_DATABIND, $prop[TTemplate::PROP_TYPE]);
	}

	public function testPropertyTagWithMixedExpression()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1"><prop:Text>Hello <%= $this->Name %>!</prop:Text></com:TLabel>');
		$component = $this->findComponent($tpl->getItems());
		$prop = $component[TTemplate::TPL_PROPS]['text'];
		$this->assertEquals(TTemplate::CONFIG_EXPRESSION, $prop[TTemplate::PROP_TYPE]);
	}

	public function testPropertyTagWithUrlExpression()
	{
		$tpl = $this->newTemplate('<com:TLabel ID="lbl1"><prop:Text><%/ path/to/page %></prop:Text></com:TLabel>');
		$component = $this->findComponent($tpl->getItems());
		$prop = $component[TTemplate::TPL_PROPS]['text'];
		$this->assertEquals(TTemplate::CONFIG_EXPRESSION, $prop[TTemplate::PROP_TYPE]);
	}

	public function testPropertyTagWithUrlExpressionValue()
	{
		$tpl = $this->newTemplateUnvalidated('<com:TComponent ID="c1"><prop:Text><%/ some/path %></prop:Text></com:TComponent>');
		$component = $this->findComponent($tpl->getItems());
		$prop = $component[TTemplate::TPL_PROPS]['text'];
		$this->assertEquals(TTemplate::CONFIG_EXPRESSION, $prop[TTemplate::PROP_TYPE]);
		$this->assertStringContainsString('some/path', $prop[TTemplate::PROP_VALUE]);
	}

	// -----------------------------------------------------------------------
	// Error conditions
	// -----------------------------------------------------------------------

	public function testNonComponentClassThrows()
	{
		$this->expectException(TConfigurationException::class);
		$this->expectExceptionMessageMatches('/not a component/i');
		$this->newTemplate('<com:stdClass />');
	}

	public function testComponentTag_withUnresolvableClass_throwsComponentRequired()
	{
		// usingClass() returns null for a class that cannot be found anywhere;
		// validateAttributes() must throw template_component_required rather than
		// crashing with a ReflectionException or a silent failure
		$this->expectException(TConfigurationException::class);
		$this->expectExceptionMessageMatches('/not a component/i');
		$this->newTemplate('<com:TFakeClassXYZ99999 />');
	}

	public function testClosingTagMismatch()
	{
		$this->expectException(TConfigurationException::class);
		$this->newTemplate('<com:TLabel></com:TButton>');
	}

	public function testClosingComponentTagMustMatch()
	{
		$this->expectException(TConfigurationException::class);
		$this->newTemplate('<com:TControl>text</com:TLabel>');
	}

	public function testUnclosedComponentTag()
	{
		$this->expectException(TConfigurationException::class);
		$this->newTemplate('<com:TLabel>');
	}

	public function testUnclosedNestedComponentTagsThrows()
	{
		$this->expectException(TConfigurationException::class);
		$this->newTemplate('<com:TLabel ID="lbl1"><com:TLabel ID="lbl2">');
	}

	public function testUnclosedPropertyTag()
	{
		$this->expectException(TConfigurationException::class);
		$this->newTemplate('<com:TLabel><prop:Text>hello</com:TLabel>');
	}

	public function testUnexpectedClosingTag()
	{
		$this->expectException(TConfigurationException::class);
		$this->newTemplate('</com:TLabel>');
	}

	public function testUnexpectedClosingPropertyTag()
	{
		$this->expectException(TConfigurationException::class);
		$this->newTemplate('</prop:Text>');
	}

	public function testClosingPropertyTagMismatch()
	{
		$this->expectException(TConfigurationException::class);
		$this->newTemplate('<com:TLabel ID="lbl1"><prop:Text>hello</prop:Title></com:TLabel>');
	}

	public function testClosingPropertyTagMismatchDifferentName()
	{
		$this->expectException(TConfigurationException::class);
		$this->newTemplate('<com:TLabel ID="lbl1"><prop:Title>Hello</prop:Text></com:TLabel>');
	}

	public function testDuplicatePropertyThrows()
	{
		$this->expectException(TConfigurationException::class);
		$this->expectExceptionMessageMatches('/configured twice|duplicated/i');
		$this->newTemplate('<com:TLabel ID="lbl1" ID="lbl2" />');
	}

	public function testDuplicateSubPropertyGroupThrows()
	{
		$this->expectException(TConfigurationException::class);
		$this->expectExceptionMessageMatches('/configured twice|duplicated/i');
		$this->newTemplate('<com:TLabel><prop:Font Size="12" /><prop:Font Size="14" /></com:TLabel>');
	}

	public function testDuplicatePropertyTagThrows()
	{
		$this->expectException(TConfigurationException::class);
		$this->expectExceptionMessageMatches('/configured twice|duplicated/i');
		$this->newTemplate('<com:TLabel><prop:Text>Hello</prop:Text><prop:Text>World</prop:Text></com:TLabel>');
	}

	public function testDuplicateAttributeThrows()
	{
		$this->expectException(TConfigurationException::class);
		$this->expectExceptionMessageMatches('/configured twice|duplicated/i');
		$this->newTemplate('<com:TLabel ID="lbl1" Text="a" Text="b" />');
	}

	public function testDatabindOnTComponentNotAllowed()
	{
		$this->expectException(TConfigurationException::class);
		$this->newTemplate('<com:TComponent Text="<%# $this->Data %>" />');
	}

	public function testEventOnTComponentNotAllowed()
	{
		$this->expectException(TConfigurationException::class);
		$this->newTemplate('<com:TComponent onClick="handler" />');
	}

	public function testUnknownPropertyOnTControlThrows()
	{
		$this->expectException(TConfigurationException::class);
		$this->newTemplate('<com:TControl NonExistentProp="value" />');
	}

	public function testUnknownEventOnTControlThrows()
	{
		$this->expectException(TConfigurationException::class);
		$this->newTemplate('<com:TControl onNonExistentEvent="handler" />');
	}

	public function testReadonlyPropertyOnTControlThrows()
	{
		$this->expectException(TConfigurationException::class);
		$this->newTemplate('<com:TControl Parent="something" />');
	}

	public function testControlSubpropertyFirstSegmentUnknownThrows()
	{
		// First segment of a subproperty (NonExistent.Prop) must be a readable property
		$this->expectException(TConfigurationException::class);
		$this->newTemplate('<com:TLabel NonExistent.Prop="val" />');
	}

	public function testControlSkinIdDatabindForbidden()
	{
		// SkinID may only be CONFIG_VALUE, CONFIG_EXPRESSION, or CONFIG_PARAMETER — databind must throw
		$this->expectException(TConfigurationException::class);
		$this->newTemplate('<com:TLabel ID="lbl1" SkinID="<%# $this->Data %>" />');
	}

	public function testControlIdDatabindForbidden()
	{
		// ID may only be CONFIG_VALUE, CONFIG_EXPRESSION, or CONFIG_PARAMETER — databind must throw
		$this->expectException(TConfigurationException::class);
		$this->newTemplate('<com:TLabel ID="<%# $this->Data %>" />');
	}

	public function testValidateAttributesTComponentReadonlyPropertyThrows()
	{
		Prado::using('TTemplateTestReadonlyComponent');
		$this->expectException(TConfigurationException::class);
		// ReadonlyProp has a getter but no setter on TTemplateTestReadonlyComponent
		$this->newTemplate('<com:TTemplateTestReadonlyComponent ReadonlyProp="val" />');
	}

	public function testAttributeValidationOffSkipsChecks()
	{
		$tpl = $this->newTemplateUnvalidated('<com:TComponent NonExistentProp="value" />');
		$component = $this->findComponent($tpl->getItems());
		$this->assertArrayHasKey('nonexistentprop', $component[TTemplate::TPL_PROPS]);
	}

	public function testIncludeDirectiveInvalidThrows()
	{
		$this->expectException(TConfigurationException::class);
		$this->newTemplate('<%include NonExistentNamespace %>');
	}

	public function testGetIncludedFilesEmpty()
	{
		$this->assertEquals([], $this->newTemplate('Hello')->getIncludedFiles());
	}

	// -----------------------------------------------------------------------
	// Events and JS
	// -----------------------------------------------------------------------

	public function testConfigureEventWithoutDotHandler()
	{
		$tpl = $this->newTemplateUnvalidated('<com:TComponent ID="c1" onClick="handleClick" />');
		$component = $this->findComponent($tpl->getItems());
		$handler = $component[TTemplate::TPL_PROPS]['onclick'];
		$this->assertEquals(TTemplate::CONFIG_VALUE, $handler[TTemplate::PROP_TYPE]);
		$this->assertEquals('handleClick', $handler[TTemplate::PROP_VALUE]);
	}

	public function testConfigureEventWithDotHandler()
	{
		$tpl = $this->newTemplateUnvalidated('<com:TComponent ID="c1" onClick="MyObj.handleClick" />');
		$component = $this->findComponent($tpl->getItems());
		$handler = $component[TTemplate::TPL_PROPS]['onclick'];
		$this->assertEquals('MyObj.handleClick', $handler[TTemplate::PROP_VALUE]);
	}

	public function testJsPropertyPrefix()
	{
		$tpl = $this->newTemplateUnvalidated('<com:TComponent ID="c1" jsClick="alert(1)" />');
		$component = $this->findComponent($tpl->getItems());
		$this->assertArrayHasKey('jsclick', $component[TTemplate::TPL_PROPS]);
		$this->assertEquals('jsClick', $component[TTemplate::TPL_PROPS]['jsclick'][TTemplate::PROP_NAME]);
	}

	// -----------------------------------------------------------------------
	// Template optimization (merging consecutive literals)
	// -----------------------------------------------------------------------

	public function testOptimizeTemplateSingleStringNotMerged()
	{
		$tpl = $this->newTemplate('just text');
		$items = $tpl->getItems();
		$this->assertCount(1, $items);
		$item = array_values($items)[0];
		$this->assertIsString($item[TTemplate::TPL_TYPE]);
		$this->assertEquals('just text', $item[TTemplate::TPL_TYPE]);
	}

	public function testOptimizeTemplateExpressionAndStrings()
	{
		// 'Hello ', expression, ' World' are merged into one TCompositeLiteral
		$tpl = $this->newTemplate('Hello <%= $this->Name %> World');
		$items = $tpl->getItems();
		$this->assertCount(1, $items);
		$item = array_values($items)[0];
		$this->assertInstanceOf(TCompositeLiteral::class, $item[TTemplate::TPL_TYPE]);
		// No raw strings should remain at the top level
		$stringCount = 0;
		foreach ($items as $i) {
			if (is_string($i[TTemplate::TPL_TYPE])) {
				$stringCount++;
			}
		}
		$this->assertEquals(0, $stringCount);
		// The composite literal has 3 sub-items: 'Hello ', expression, ' World'
		$ref = new \ReflectionClass(TCompositeLiteral::class);
		$itemsProp = $ref->getProperty('_items');
		$itemsProp->setAccessible(true);
		$literalItems = $itemsProp->getValue($item[TTemplate::TPL_TYPE]);
		$this->assertCount(3, $literalItems);
	}

	// -----------------------------------------------------------------------
	// Private method unit tests (via reflection)
	// -----------------------------------------------------------------------

	public function testPackTemplateWithAttributes()
	{
		$ref = new \ReflectionClass(TTemplate::class);
		$tplObj = $ref->newInstanceWithoutConstructor();
		$method = $ref->getMethod('packTemplate');
		$method->setAccessible(true);
		$result = $method->invoke($tplObj, -1, 'SomeClass', ['id' => [TTemplate::PROP_TYPE => TTemplate::CONFIG_VALUE, TTemplate::PROP_NAME => 'ID', TTemplate::PROP_VALUE => 'test']]);
		$this->assertEquals(-1, $result[TTemplate::TPL_PARENT_INDEX]);
		$this->assertEquals('SomeClass', $result[TTemplate::TPL_TYPE]);
		$this->assertArrayHasKey(TTemplate::TPL_PROPS, $result);
		$this->assertCount(1, $result[TTemplate::TPL_PROPS]);
	}

	public function testPackTemplateWithoutAttributes()
	{
		$ref = new \ReflectionClass(TTemplate::class);
		$tplObj = $ref->newInstanceWithoutConstructor();
		$method = $ref->getMethod('packTemplate');
		$method->setAccessible(true);
		$result = $method->invoke($tplObj, -1, 'Hello text');
		$this->assertEquals(-1, $result[TTemplate::TPL_PARENT_INDEX]);
		$this->assertEquals('Hello text', $result[TTemplate::TPL_TYPE]);
		$this->assertArrayNotHasKey(TTemplate::TPL_PROPS, $result);
	}

	public function testPackProperty()
	{
		$ref = new \ReflectionClass(TTemplate::class);
		$tplObj = $ref->newInstanceWithoutConstructor();
		$method = $ref->getMethod('packProperty');
		$method->setAccessible(true);
		$result = $method->invoke($tplObj, TTemplate::CONFIG_VALUE, 'Text', 'Hello');
		$this->assertEquals(TTemplate::CONFIG_VALUE, $result[TTemplate::PROP_TYPE]);
		$this->assertEquals('Text', $result[TTemplate::PROP_NAME]);
		$this->assertEquals('Hello', $result[TTemplate::PROP_VALUE]);
	}

	public function testPropertyExpressionCharToTypeMapping()
	{
		$ref = new \ReflectionClass(TTemplate::class);
		$tplObj = $ref->newInstanceWithoutConstructor();
		$method = $ref->getMethod('propertyExpressionCharToType');
		$method->setAccessible(true);
		$this->assertEquals(TTemplate::CONFIG_EXPRESSION, $method->invoke($tplObj, '<%= expr %>', 'prop'));
		$this->assertEquals(TTemplate::CONFIG_DATABIND, $method->invoke($tplObj, '<%# expr %>', 'prop'));
		$this->assertEquals(TTemplate::CONFIG_ASSET, $method->invoke($tplObj, '<%~ expr %>', 'prop'));
		$this->assertEquals(TTemplate::CONFIG_LOCALIZATION, $method->invoke($tplObj, '<%[ expr ]%>', 'prop'));
		$this->assertEquals(TTemplate::CONFIG_PARAMETER, $method->invoke($tplObj, '<%$ expr %>', 'prop'));
		$this->assertEquals(TTemplate::CONFIG_EXPRESSION, $method->invoke($tplObj, '<%/ expr %>', 'prop'));
	}

	public function testPropertyExpressionCharToTypeInvalid()
	{
		$ref = new \ReflectionClass(TTemplate::class);
		$tplObj = $ref->newInstanceWithoutConstructor();
		$method = $ref->getMethod('propertyExpressionCharToType');
		$method->setAccessible(true);
		$this->expectException(TConfigurationException::class);
		$method->invoke($tplObj, '<%X value%>', 'prop');
	}

	public function testParseExpressionAllTypes()
	{
		$ref = new \ReflectionClass(TTemplate::class);
		$tplObj = $ref->newInstanceWithoutConstructor();
		$ctxProp = $ref->getProperty('_contextPath');
		$ctxProp->setAccessible(true);
		$ctxProp->setValue($tplObj, sys_get_temp_dir());
		$method = $ref->getMethod('parseExpression');
		$method->setAccessible(true);

		$result = $method->invoke($tplObj, '=', '$this->Prop');
		$this->assertEquals([TCompositeLiteral::TYPE_EXPRESSION, '$this->Prop'], $result);

		$result = $method->invoke($tplObj, '%', 'echo "hi";');
		$this->assertEquals([TCompositeLiteral::TYPE_STATEMENTS, 'echo "hi";'], $result);

		$result = $method->invoke($tplObj, '#', '$this->Data');
		$this->assertEquals([TCompositeLiteral::TYPE_DATABINDING, '$this->Data'], $result);

		$result = $method->invoke($tplObj, '$', 'ParamName');
		$this->assertEquals(TCompositeLiteral::TYPE_EXPRESSION, $result[0]);
		$this->assertStringContainsString('getParameters', $result[1]);

		$result = $method->invoke($tplObj, '~', 'assets/img.png');
		$this->assertEquals(TCompositeLiteral::TYPE_EXPRESSION, $result[0]);
		$this->assertStringContainsString('publishFilePath', $result[1]);

		$result = $method->invoke($tplObj, '/', 'path/to/page');
		$this->assertEquals(TCompositeLiteral::TYPE_EXPRESSION, $result[0]);
		$this->assertStringContainsString('path/to/page', $result[1]);

		$result = $method->invoke($tplObj, '[', 'Hello World ]');
		$this->assertEquals(TCompositeLiteral::TYPE_EXPRESSION, $result[0]);
		$this->assertStringContainsString('localize', $result[1]);
	}

	public function testParseExpressionInvalidType()
	{
		$ref = new \ReflectionClass(TTemplate::class);
		$tplObj = $ref->newInstanceWithoutConstructor();
		$method = $ref->getMethod('parseExpression');
		$method->setAccessible(true);
		$this->expectException(TConfigurationException::class);
		$method->invoke($tplObj, 'X', 'someExpression');
	}

	public function testParseAttributeStringValue()
	{
		$ref = new \ReflectionClass(TTemplate::class);
		$tplObj = $ref->newInstanceWithoutConstructor();
		$method = $ref->getMethod('parseAttribute');
		$method->setAccessible(true);
		$result = $method->invoke($tplObj, 'Text', 'Hello World');
		$this->assertEquals(TTemplate::CONFIG_VALUE, $result[TTemplate::PROP_TYPE]);
		$this->assertEquals('Text', $result[TTemplate::PROP_NAME]);
		$this->assertEquals('Hello World', $result[TTemplate::PROP_VALUE]);
	}

	public function testParseAttributeExpressionValue()
	{
		$ref = new \ReflectionClass(TTemplate::class);
		$tplObj = $ref->newInstanceWithoutConstructor();
		$method = $ref->getMethod('parseAttribute');
		$method->setAccessible(true);
		$result = $method->invoke($tplObj, 'Text', '<%= $this->Name %>');
		$this->assertEquals(TTemplate::CONFIG_EXPRESSION, $result[TTemplate::PROP_TYPE]);
		$this->assertStringContainsString('$this->Name', $result[TTemplate::PROP_VALUE]);
	}

	public function testParseAttributeDatabindValue()
	{
		$ref = new \ReflectionClass(TTemplate::class);
		$tplObj = $ref->newInstanceWithoutConstructor();
		$method = $ref->getMethod('parseAttribute');
		$method->setAccessible(true);
		$result = $method->invoke($tplObj, 'Text', '<%# $this->Data %>');
		$this->assertEquals(TTemplate::CONFIG_DATABIND, $result[TTemplate::PROP_TYPE]);
	}

	public function testParseAttributeAssetValue()
	{
		$ref = new \ReflectionClass(TTemplate::class);
		$tplObj = $ref->newInstanceWithoutConstructor();
		$method = $ref->getMethod('parseAttribute');
		$method->setAccessible(true);
		$result = $method->invoke($tplObj, 'ImageUrl', '<%~ images/logo.png %>');
		$this->assertEquals(TTemplate::CONFIG_ASSET, $result[TTemplate::PROP_TYPE]);
		$this->assertEquals('images/logo.png', $result[TTemplate::PROP_VALUE]);
	}

	public function testParseAttributeParameterValue()
	{
		$ref = new \ReflectionClass(TTemplate::class);
		$tplObj = $ref->newInstanceWithoutConstructor();
		$method = $ref->getMethod('parseAttribute');
		$method->setAccessible(true);
		$result = $method->invoke($tplObj, 'Text', '<%$ AppParam %>');
		$this->assertEquals(TTemplate::CONFIG_PARAMETER, $result[TTemplate::PROP_TYPE]);
		$this->assertEquals('AppParam', $result[TTemplate::PROP_VALUE]);
	}

	public function testParseAttributeLocalizationValue()
	{
		$ref = new \ReflectionClass(TTemplate::class);
		$tplObj = $ref->newInstanceWithoutConstructor();
		$method = $ref->getMethod('parseAttribute');
		$method->setAccessible(true);
		$result = $method->invoke($tplObj, 'Text', '<%[ Hello World ]%>');
		$this->assertEquals(TTemplate::CONFIG_LOCALIZATION, $result[TTemplate::PROP_TYPE]);
		$this->assertEquals('Hello World', $result[TTemplate::PROP_VALUE]);
	}

	public function testParseAttributeUrlValue()
	{
		$ref = new \ReflectionClass(TTemplate::class);
		$tplObj = $ref->newInstanceWithoutConstructor();
		$method = $ref->getMethod('parseAttribute');
		$method->setAccessible(true);
		$result = $method->invoke($tplObj, 'NavigateUrl', '<%/ path/to/page %>');
		$this->assertEquals(TTemplate::CONFIG_EXPRESSION, $result[TTemplate::PROP_TYPE]);
		$this->assertStringContainsString('path/to/page', $result[TTemplate::PROP_VALUE]);
	}

	public function testParseAttributesEmpty()
	{
		$ref = new \ReflectionClass(TTemplate::class);
		$tplObj = $ref->newInstanceWithoutConstructor();
		$method = $ref->getMethod('parseAttributes');
		$method->setAccessible(true);
		$result = $method->invoke($tplObj, '', 0, false);
		$this->assertEquals([], $result);
	}

	public function testParseAttributesDirective()
	{
		$ref = new \ReflectionClass(TTemplate::class);
		$tplObj = $ref->newInstanceWithoutConstructor();
		$method = $ref->getMethod('parseAttributes');
		$method->setAccessible(true);
		$result = $method->invoke($tplObj, 'Language="PHP" Master="Site"', 0, true);
		$this->assertCount(2, $result);
		$this->assertArrayHasKey('Language', $result);
		$this->assertArrayHasKey('Master', $result);
		$this->assertEquals('PHP', $result['Language']);
		$this->assertEquals('Site', $result['Master']);
	}

	public function testParseAttributesDirectiveWithDash()
	{
		$ref = new \ReflectionClass(TTemplate::class);
		$tplObj = $ref->newInstanceWithoutConstructor();
		$method = $ref->getMethod('parseAttributes');
		$method->setAccessible(true);
		$result = $method->invoke($tplObj, 'Master-Page="layout" Theme-Color="blue"', 0, true);
		$this->assertCount(2, $result);

		$this->assertEquals(['Master_Page' => 'layout', 'Theme_Color' => 'blue'], $result);
	}

	public function testTemplatePropertyTagEndingInTemplateCaseSensitive()
	{
		$tpl = $this->newTemplateUnvalidated('<com:TConditional ID="c1"><prop:TrueTemplate>content</prop:TrueTemplate></com:TConditional>');
		$component = $this->findComponent($tpl->getItems());
		$this->assertArrayHasKey('truetemplate', $component[TTemplate::TPL_PROPS]);
		$tplProp = $component[TTemplate::TPL_PROPS]['truetemplate'];
		$this->assertEquals(TTemplate::CONFIG_TEMPLATE, $tplProp[TTemplate::PROP_TYPE]);
		$this->assertInstanceOf(TTemplate::class, $tplProp[TTemplate::PROP_VALUE]);
	}

	public function testTemplatePropertyAttributeEndingInTemplateCaseSensitive()
	{
		$tpl = $this->newTemplateUnvalidated('<com:TConditional ID="c1" FalseTemplate="content" />');
		$component = $this->findComponent($tpl->getItems());
		$this->assertArrayHasKey('falsetemplate', $component[TTemplate::TPL_PROPS]);
		$tplProp = $component[TTemplate::TPL_PROPS]['falsetemplate'];
		$this->assertEquals(TTemplate::CONFIG_TEMPLATE, $tplProp[TTemplate::PROP_TYPE]);
		$this->assertInstanceOf(TTemplate::class, $tplProp[TTemplate::PROP_VALUE]);
	}
}
