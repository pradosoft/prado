<?php

use Prado\Web\UI\TTemplateControl;
use Prado\Web\UI\TTemplate;
use Prado\Web\UI\ITemplate;
use Prado\Web\UI\WebControls\TContent;
use Prado\Web\UI\WebControls\TContentPlaceHolder;
use Prado\Exceptions\TConfigurationException;

class TTemplateControlTest extends PHPUnit\Framework\TestCase
{
	// -----------------------------------------------------------------------
	// Helpers — read private fields via Reflection
	// -----------------------------------------------------------------------

	private function getContents(TTemplateControl $ctrl): array
	{
		$ref = new ReflectionProperty(TTemplateControl::class, '_contents');
		$ref->setAccessible(true);
		return $ref->getValue($ctrl);
	}

	private function getPlaceholders(TTemplateControl $ctrl): array
	{
		$ref = new ReflectionProperty(TTemplateControl::class, '_placeholders');
		$ref->setAccessible(true);
		return $ref->getValue($ctrl);
	}

	private function setMasterOnCtrl(TTemplateControl $ctrl, TTemplateControl $master): void
	{
		$ref = new ReflectionProperty(TTemplateControl::class, '_master');
		$ref->setAccessible(true);
		$ref->setValue($ctrl, $master);
	}

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------

	public function testExtTemplateConstant(): void
	{
		$this->assertEquals('.tpl', TTemplateControl::EXT_TEMPLATE);
	}

	// -----------------------------------------------------------------------
	// setTemplate / getTemplate
	// -----------------------------------------------------------------------

	public function testSetAndGetLocalTemplate(): void
	{
		$control = new TTemplateControl();
		// ITemplate only declares instantiateIn() and getIncludedFiles();
		// using a plain mock is fine here since no methods are called.
		$template = $this->createMock(ITemplate::class);
		$control->setTemplate($template);
		$this->assertSame($template, $control->getTemplate());
	}

	public function testSetTemplateOverridesPreviousLocalTemplate(): void
	{
		$control = new TTemplateControl();
		$t1 = $this->createMock(ITemplate::class);
		$t2 = $this->createMock(ITemplate::class);
		$control->setTemplate($t1);
		$control->setTemplate($t2);
		$this->assertSame($t2, $control->getTemplate());
	}

	// -----------------------------------------------------------------------
	// getIsSourceTemplateControl
	// ITemplate does not declare getIsSourceTemplate(); must mock TTemplate.
	// -----------------------------------------------------------------------

	public function testGetIsSourceTemplateControlFalseWhenTemplateReturnsFalse(): void
	{
		$control = new TTemplateControl();
		$template = $this->createMock(TTemplate::class);
		$template->method('getIsSourceTemplate')->willReturn(false);
		$control->setTemplate($template);
		$this->assertFalse($control->getIsSourceTemplateControl());
	}

	public function testGetIsSourceTemplateControlTrueWhenTemplateIsSource(): void
	{
		$control = new TTemplateControl();
		$template = $this->createMock(TTemplate::class);
		$template->expects($this->once())->method('getIsSourceTemplate')->willReturn(true);
		$control->setTemplate($template);
		$this->assertTrue($control->getIsSourceTemplateControl());
	}

	// -----------------------------------------------------------------------
	// getTemplateDirectory
	// ITemplate does not declare getContextPath(); must mock TTemplate.
	// -----------------------------------------------------------------------

	public function testGetTemplateDirectoryWithMockTemplate(): void
	{
		$control = new TTemplateControl();
		$template = $this->createMock(TTemplate::class);
		$template->expects($this->once())->method('getContextPath')->willReturn('/some/path');
		$control->setTemplate($template);
		$this->assertEquals('/some/path', $control->getTemplateDirectory());
	}

	public function testGetTemplateDirectoryIsEmptyWithNullTemplate(): void
	{
		// Anonymous subclass that always returns null for getTemplate()
		$control = new class extends TTemplateControl {
			public function getTemplate(): ?ITemplate
			{
				return null;
			}
		};
		$this->assertEquals('', $control->getTemplateDirectory());
	}

	// -----------------------------------------------------------------------
	// createChildControls — with local template (no service required)
	// ITemplate does not declare getDirective(); must mock TTemplate.
	// -----------------------------------------------------------------------

	public function testCreateChildControlsInstantiatesTemplateWhenSet(): void
	{
		$control = new TTemplateControl();
		$template = $this->createMock(TTemplate::class);
		$template->expects($this->once())->method('getDirective')->willReturn([]);
		$template->expects($this->once())->method('instantiateIn')->with($control);
		$control->setTemplate($template);
		$control->createChildControls();
	}

	public function testCreateChildControlsDoesNothingWhenNoTemplate(): void
	{
		$control = new class extends TTemplateControl {
			public function getTemplate(): ?ITemplate
			{
				return null;
			}
		};
		$control->createChildControls();
		$this->assertTrue(true);
	}

	public function testCreateChildControlsThrowsOnNonStringDirectiveValue(): void
	{
		$control = new TTemplateControl();
		$template = $this->createMock(TTemplate::class);
		$template->method('getDirective')->willReturn(['someKey' => ['not', 'a', 'string']]);
		$control->setTemplate($template);

		$this->expectException(TConfigurationException::class);
		$control->createChildControls();
	}

	public function testCreateChildControlsAppliesStringDirectiveAsSubProperty(): void
	{
		$control = new class extends TTemplateControl {
			public string $applied = '';

			public function setSomeKey(string $v): void
			{
				$this->applied = $v;
			}
		};
		$template = $this->createMock(TTemplate::class);
		$template->method('getDirective')->willReturn(['somekey' => 'directiveValue']);
		$template->method('instantiateIn');
		$control->setTemplate($template);
		$control->createChildControls();
		$this->assertEquals('directiveValue', $control->applied);
	}

	// -----------------------------------------------------------------------
	// registerContent
	// -----------------------------------------------------------------------

	public function testRegisterContentStoresContent(): void
	{
		$control = new TTemplateControl();
		$content = $this->createMock(TContent::class);
		$control->registerContent('myContent', $content);

		$contents = $this->getContents($control);
		$this->assertArrayHasKey('myContent', $contents);
		$this->assertSame($content, $contents['myContent']);
	}

	public function testRegisterContentDuplicateIdThrowsTConfigurationException(): void
	{
		$control = new TTemplateControl();
		$content = $this->createMock(TContent::class);
		$control->registerContent('dup', $content);

		$this->expectException(TConfigurationException::class);
		$control->registerContent('dup', $this->createMock(TContent::class));
	}

	public function testRegisterContentAllowsDifferentIds(): void
	{
		$control = new TTemplateControl();
		$control->registerContent('id1', $this->createMock(TContent::class));
		$control->registerContent('id2', $this->createMock(TContent::class));

		$contents = $this->getContents($control);
		$this->assertArrayHasKey('id1', $contents);
		$this->assertArrayHasKey('id2', $contents);
	}

	// -----------------------------------------------------------------------
	// registerContentPlaceHolder
	// -----------------------------------------------------------------------

	public function testRegisterContentPlaceHolderStoresPlaceholder(): void
	{
		$control = new TTemplateControl();
		$ph = $this->createMock(TContentPlaceHolder::class);
		$control->registerContentPlaceHolder('myPH', $ph);

		$phs = $this->getPlaceholders($control);
		$this->assertArrayHasKey('myPH', $phs);
		$this->assertSame($ph, $phs['myPH']);
	}

	public function testRegisterContentPlaceHolderDuplicateIdThrows(): void
	{
		$control = new TTemplateControl();
		$ph = $this->createMock(TContentPlaceHolder::class);
		$control->registerContentPlaceHolder('dupPH', $ph);

		$this->expectException(TConfigurationException::class);
		$control->registerContentPlaceHolder('dupPH', $this->createMock(TContentPlaceHolder::class));
	}

	public function testRegisterContentPlaceHolderAllowsDifferentIds(): void
	{
		$control = new TTemplateControl();
		$control->registerContentPlaceHolder('ph1', $this->createMock(TContentPlaceHolder::class));
		$control->registerContentPlaceHolder('ph2', $this->createMock(TContentPlaceHolder::class));

		$phs = $this->getPlaceholders($control);
		$this->assertArrayHasKey('ph1', $phs);
		$this->assertArrayHasKey('ph2', $phs);
	}

	// -----------------------------------------------------------------------
	// MasterClass
	// -----------------------------------------------------------------------

	public function testGetMasterClassIsEmptyStringByDefault(): void
	{
		$control = new TTemplateControl();
		$this->assertEquals('', $control->getMasterClass());
	}

	public function testSetAndGetMasterClass(): void
	{
		$control = new TTemplateControl();
		$control->setMasterClass('Some\\Namespace\\MasterPage');
		$this->assertEquals('Some\\Namespace\\MasterPage', $control->getMasterClass());
	}

	public function testSetMasterClassToEmptyString(): void
	{
		$control = new TTemplateControl();
		$control->setMasterClass('SomeClass');
		$control->setMasterClass('');
		$this->assertEquals('', $control->getMasterClass());
	}

	// -----------------------------------------------------------------------
	// getMaster (private _master)
	// -----------------------------------------------------------------------

	public function testGetMasterIsNullByDefault(): void
	{
		$control = new TTemplateControl();
		$this->assertNull($control->getMaster());
	}

	public function testGetMasterReturnsInjectedMasterViaReflection(): void
	{
		$control = new TTemplateControl();
		$master = new TTemplateControl();
		$this->setMasterOnCtrl($control, $master);
		$this->assertSame($master, $control->getMaster());
	}

	// -----------------------------------------------------------------------
	// injectContent
	// -----------------------------------------------------------------------

	public function testInjectContentWithNoPlaceholderAndNoMasterClassThrows(): void
	{
		$control = new TTemplateControl();
		$content = $this->createMock(TContent::class);

		$this->expectException(TConfigurationException::class);
		$control->injectContent('nonExistentPlaceholder', $content);
	}

	public function testInjectContentWithMasterClassAndNoPlaceholderStoresContent(): void
	{
		$control = new TTemplateControl();
		$control->setMasterClass(TTemplateControl::class);
		$content = $this->createMock(TContent::class);

		// With a masterClass set, missing placeholder stores content in _contents
		$control->injectContent('somePlaceholder', $content);

		$contents = $this->getContents($control);
		$this->assertArrayHasKey('somePlaceholder', $contents);
		$this->assertSame($content, $contents['somePlaceholder']);
	}
}
