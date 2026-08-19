<?php

use Prado\Web\UI\WebControls\TShadowRootMode;
use Prado\Web\UI\WebControls\TWebTemplate;
use PHPUnit\Framework\TestCase;

class TWebTemplateTest extends TestCase
{
	use TWebControlRenderTrait;

	/**
	 * Renders without a page context, so the client script is not registered.
	 * EnableClientScript is disabled to match, keeping the id attribute out.
	 */
	private function newTemplate(): TWebTemplate
	{
		$control = new TWebTemplate();
		$control->setEnableClientScript(false);
		return $control;
	}

	public function testRendersTemplateTag()
	{
		$output = $this->render($this->newTemplate());
		$this->assertStringContainsString('<template', $output);
		$this->assertStringContainsString('</template>', $output);
	}

	public function testExtendsWebControl()
	{
		$this->assertInstanceOf(\Prado\Web\UI\WebControls\TWebControl::class, new TWebTemplate());
	}

	public function testContentRenderedInsideTag()
	{
		$control = $this->newTemplate();
		$control->getControls()->add('<tr><td>{{name}}</td></tr>');
		$output = $this->render($control);
		$this->assertStringContainsString('<template><tr><td>{{name}}</td></tr></template>', $output);
	}

	// --- ShadowRootMode ---

	public function testShadowRootModeDefaultNotSet()
	{
		$this->assertSame(TShadowRootMode::NotSet, $this->newTemplate()->getShadowRootMode());
	}

	public function testSetShadowRootMode()
	{
		$control = $this->newTemplate();
		$control->setShadowRootMode(TShadowRootMode::Open);
		$this->assertSame(TShadowRootMode::Open, $control->getShadowRootMode());
	}

	public function testSetShadowRootModeInvalidThrows()
	{
		$control = $this->newTemplate();
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		$control->setShadowRootMode('Ajar');
	}

	public function testShadowRootModeNotRenderedWhenNotSet()
	{
		$output = $this->render($this->newTemplate());
		$this->assertStringNotContainsString('shadowrootmode', $output);
	}

	public function testShadowRootModeRenderedLowercase()
	{
		$control = $this->newTemplate();
		$control->setShadowRootMode(TShadowRootMode::Open);
		$output = $this->render($control);
		$this->assertStringContainsString('shadowrootmode="open"', $output);
	}

	public function testShadowRootModeClosedRendered()
	{
		$control = $this->newTemplate();
		$control->setShadowRootMode(TShadowRootMode::Closed);
		$output = $this->render($control);
		$this->assertStringContainsString('shadowrootmode="closed"', $output);
	}

	// --- ShadowRoot boolean attributes ---

	public function testShadowRootBooleanDefaults()
	{
		$control = $this->newTemplate();
		$this->assertFalse($control->getShadowRootDelegatesFocus());
		$this->assertFalse($control->getShadowRootClonable());
		$this->assertFalse($control->getShadowRootSerializable());
	}

	public function testShadowRootBooleansRenderedWithMode()
	{
		$control = $this->newTemplate();
		$control->setShadowRootMode(TShadowRootMode::Open);
		$control->setShadowRootDelegatesFocus(true);
		$control->setShadowRootClonable(true);
		$control->setShadowRootSerializable(true);
		$output = $this->render($control);
		$this->assertStringContainsString('shadowrootdelegatesfocus="shadowrootdelegatesfocus"', $output);
		$this->assertStringContainsString('shadowrootclonable="shadowrootclonable"', $output);
		$this->assertStringContainsString('shadowrootserializable="shadowrootserializable"', $output);
	}

	public function testShadowRootBooleansNotRenderedWithoutMode()
	{
		// The HTML specification ignores these attributes without shadowrootmode
		$control = $this->newTemplate();
		$control->setShadowRootDelegatesFocus(true);
		$control->setShadowRootClonable(true);
		$control->setShadowRootSerializable(true);
		$output = $this->render($control);
		$this->assertStringNotContainsString('shadowrootdelegatesfocus', $output);
		$this->assertStringNotContainsString('shadowrootclonable', $output);
		$this->assertStringNotContainsString('shadowrootserializable', $output);
	}

	public function testShadowRootBooleansFromString()
	{
		$control = $this->newTemplate();
		$control->setShadowRootDelegatesFocus('true');
		$this->assertTrue($control->getShadowRootDelegatesFocus());
		$control->setShadowRootDelegatesFocus('false');
		$this->assertFalse($control->getShadowRootDelegatesFocus());
	}

	// --- EnableClientScript ---

	public function testEnableClientScriptDefaultTrue()
	{
		$this->assertTrue((new TWebTemplate())->getEnableClientScript());
	}

	public function testSetEnableClientScript()
	{
		$control = new TWebTemplate();
		$control->setEnableClientScript(false);
		$this->assertFalse($control->getEnableClientScript());
	}

	public function testHasClientScriptFalseWhenDisabled()
	{
		$control = new TWebTemplate();
		$control->setEnableClientScript(false);
		$this->assertFalse(PradoUnit::invoke($control, 'getHasClientScript'));
	}

	public function testHasClientScriptFalseWithShadowRootMode()
	{
		// Declarative shadow DOM removes the element from the document tree,
		// leaving nothing for the client-side wrapper to bind to.
		$control = new TWebTemplate();
		$control->setShadowRootMode(TShadowRootMode::Open);
		$this->assertFalse(PradoUnit::invoke($control, 'getHasClientScript'));
	}

	public function testHasClientScriptTrueByDefault()
	{
		$this->assertTrue(PradoUnit::invoke(new TWebTemplate(), 'getHasClientScript'));
	}

	public function testGetClientClassName()
	{
		$this->assertSame('Prado.WebUI.TWebTemplate', PradoUnit::invoke(new TWebTemplate(), 'getClientClassName'));
	}

	public function testGetClientOptionsContainsId()
	{
		$control = new TWebTemplate();
		$control->setID('tpl');
		$options = PradoUnit::invoke($control, 'getClientOptions');
		$this->assertArrayHasKey('ID', $options);
		$this->assertSame($control->getClientID(), $options['ID']);
	}

	// --- ValidateContent ---

	public function testValidateContentDefaultTrue()
	{
		$this->assertTrue((new TWebTemplate())->getValidateContent());
	}

	public function testPostBackDataChildThrows()
	{
		$control = $this->newTemplate();
		$control->getControls()->add(new \Prado\Web\UI\WebControls\TTextBox());
		$this->expectException(\Prado\Exceptions\TConfigurationException::class);
		$control->onPreRender(null);
	}

	public function testPostBackEventChildThrows()
	{
		$control = $this->newTemplate();
		$control->getControls()->add(new \Prado\Web\UI\WebControls\TButton());
		$this->expectException(\Prado\Exceptions\TConfigurationException::class);
		$control->onPreRender(null);
	}

	public function testActiveControlChildThrows()
	{
		$control = $this->newTemplate();
		$control->getControls()->add(new \Prado\Web\UI\ActiveControls\TActiveLabel());
		$this->expectException(\Prado\Exceptions\TConfigurationException::class);
		$control->onPreRender(null);
	}

	public function testValidatorChildThrows()
	{
		$control = $this->newTemplate();
		$control->getControls()->add(new \Prado\Web\UI\WebControls\TRequiredFieldValidator());
		$this->expectException(\Prado\Exceptions\TConfigurationException::class);
		$control->onPreRender(null);
	}

	public function testNestedUnsupportedChildThrows()
	{
		// The check recurses: an unsupported control inside a wrapping panel is found
		$control = $this->newTemplate();
		$panel = new \Prado\Web\UI\WebControls\TPanel();
		$panel->getControls()->add(new \Prado\Web\UI\WebControls\TTextBox());
		$control->getControls()->add($panel);
		$this->expectException(\Prado\Exceptions\TConfigurationException::class);
		$control->onPreRender(null);
	}

	public function testMarkupChildrenPassValidation()
	{
		$control = $this->newTemplate();
		$label = new \Prado\Web\UI\WebControls\TLabel();
		$label->setText('{{name}}');
		$control->getControls()->add($label);
		$image = new \Prado\Web\UI\WebControls\TImage();
		$image->setImageUrl('{{avatar}}');
		$control->getControls()->add($image);
		$control->getControls()->add('plain text {{value}}');
		$control->onPreRender(null);
		$this->assertTrue(true);
	}

	public function testValidateContentDisabledSkipsCheck()
	{
		$control = $this->newTemplate();
		$control->setValidateContent(false);
		$control->getControls()->add(new \Prado\Web\UI\WebControls\TTextBox());
		$control->onPreRender(null);
		$this->assertTrue(true);
	}

	// --- PersistInstances ---

	public function testPersistInstancesDefaultFalse()
	{
		$this->assertFalse((new TWebTemplate())->getPersistInstances());
	}

	public function testSetPersistInstances()
	{
		$control = new TWebTemplate();
		$control->setPersistInstances(true);
		$this->assertTrue($control->getPersistInstances());
	}

	public function testClientOptionsCarryPersistInstances()
	{
		$control = new TWebTemplate();
		$control->setPersistInstances(true);
		$options = PradoUnit::invoke($control, 'getClientOptions');
		$this->assertTrue($options['PersistInstances']);
	}

	public function testImplementsIPostBackDataHandler()
	{
		$this->assertInstanceOf(\Prado\Web\UI\IPostBackDataHandler::class, new TWebTemplate());
	}

	public function testHasPersistenceRequiresBothFlags()
	{
		$control = new TWebTemplate();
		// off by default
		$this->assertFalse(PradoUnit::invoke($control, 'getHasPersistence'));
		// PersistInstances alone is not enough — tracking carries the instance data
		$control->setPersistInstances(true);
		$control->setTrackInstances(false);
		$this->assertFalse(PradoUnit::invoke($control, 'getHasPersistence'));
		// both together
		$control->setTrackInstances(true);
		$this->assertTrue(PradoUnit::invoke($control, 'getHasPersistence'));
	}

	public function testLoadPostDataStoresState()
	{
		$control = new TWebTemplate();
		$control->setID('tpl');
		$state = '[{"uid":"wt1","target":"listBody","data":{"name":"Ada"}}]';

		$changed = $control->loadPostData('tpl', [$control->getClientID() . '_instances' => $state]);

		$this->assertTrue($changed);
		$this->assertTrue($control->getDataChanged());
		$this->assertSame($state, $control->getPersistedInstances());
	}

	public function testLoadPostDataUnchangedStateReturnsFalse()
	{
		$control = new TWebTemplate();
		$control->setID('tpl');
		$this->assertFalse($control->loadPostData('tpl', []));
		$this->assertFalse($control->getDataChanged());
	}

	// --- Inherited rendering ---

	public function testCssClassAndAttributesRendered()
	{
		$control = $this->newTemplate();
		$control->setCssClass('row-template');
		$control->getAttributes()->add('data-kind', 'row');
		$output = $this->render($control);
		$this->assertStringContainsString('class="row-template"', $output);
		$this->assertStringContainsString('data-kind="row"', $output);
	}

	public function testNotVisibleRendersNothing()
	{
		$control = $this->newTemplate();
		$control->setVisible(false);
		$textWriter = new \Prado\IO\TTextWriter();
		$control->renderControl(new \Prado\Web\UI\THtmlWriter($textWriter));
		$this->assertSame('', $textWriter->flush());
	}
}
