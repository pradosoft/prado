<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\IO\TTextWriter;
use Prado\Test\Unit\Harness\Web\UI\TTestCallbackPage;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\ActiveControls\ICallbackEventHandler;
use Prado\Web\UI\ActiveControls\TActivePanel;
use Prado\Web\UI\ActiveControls\TBaseActiveCallbackControl;
use Prado\Web\UI\ActiveControls\TCallbackEventParameter;
use Prado\Web\UI\ActiveControls\TLazyPanel;
use Prado\Web\UI\ActiveControls\TLazyPanelTrigger;
use Prado\Web\UI\INamingContainer;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TControl;
use Prado\Web\UI\TPage;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\TTemplate;

/**
 * A client script manager that records callback controls and publishes no assets.
 */
class TLazyPanelTestClientScript extends TClientScriptManager
{
	public array $callbackControls = [];

	public function registerPradoScript($name)
	{
	}

	public function registerCallbackControl($class, $options)
	{
		$this->callbackControls[] = [$class, $options];
	}
}

/**
 * TLazyPanel and TLazyPanelTrigger (#1408).
 */
class TLazyPanelTest extends TestCase
{
	private function preparePage(TPage $page): TLazyPanelTestClientScript
	{
		$cs = new TLazyPanelTestClientScript($page);
		PradoUnit::setProp($page, '_clientScript', $cs);
		return $cs;
	}

	private const CONTENT = '<com:Prado\\Web\\UI\\WebControls\\TLabel ID="Value" Text="content" />';
	private const PLACEHOLDER = '<p class="ph">Loading</p><com:Prado\\Web\\UI\\WebControls\\TLabel ID="Wait" Text="wait" />';

	/**
	 * @return array{TTestCallbackPage, TLazyPanel}
	 */
	private function createPanel(bool $callback = false, int $stage = TControl::CS_PRERENDERED): array
	{
		$page = new TTestCallbackPage();
		$page->callback = $callback;
		$this->preparePage($page);
		$panel = new TLazyPanel();
		$panel->setID('Lazy');
		$panel->setTemplateControl($page);
		$panel->setContentTemplate(new TTemplate(self::CONTENT, __DIR__));
		$panel->setPlaceholderTemplate(new TTemplate(self::PLACEHOLDER, __DIR__));
		$page->getControls()->add($panel);
		PradoUnit::invoke($panel, 'setControlStage', $stage);
		return [$page, $panel];
	}

	/**
	 * @return TCallbackEventParameter a callback parameter whose writers write to memory
	 */
	private function callbackParameter(): TCallbackEventParameter
	{
		$response = new class () {
			public function createHtmlWriter($type = null)
			{
				return new THtmlWriter(new TTextWriter());
			}
		};
		return new TCallbackEventParameter($response, null);
	}

	private function render(TControl $control): string
	{
		$writer = new THtmlWriter(new TTextWriter());
		$control->render($writer);
		return $writer->flush();
	}

	public function testTriggerValues()
	{
		$this->assertSame('Load', TLazyPanelTrigger::Load);
		$this->assertSame('Visible', TLazyPanelTrigger::Visible);
		$this->assertSame('Manual', TLazyPanelTrigger::Manual);
	}

	public function testTypes()
	{
		$panel = new TLazyPanel();
		$this->assertInstanceOf(TActivePanel::class, $panel);
		$this->assertInstanceOf(ICallbackEventHandler::class, $panel);
		$this->assertInstanceOf(INamingContainer::class, $panel);
		$this->assertInstanceOf(TBaseActiveCallbackControl::class, $panel->getActiveControl());
		$this->assertSame($panel->getActiveControl()->getClientSide(), $panel->getClientSide());
	}

	public function testDefaults()
	{
		$panel = new TLazyPanel();
		$this->assertSame(TLazyPanelTrigger::Visible, $panel->getTrigger());
		$this->assertSame('0px', $panel->getRootMargin());
		$this->assertFalse($panel->getIsContentLoaded());
		$this->assertNull($panel->getContentTemplate());
		$this->assertNull($panel->getPlaceholderTemplate());
		$this->assertFalse($panel->getActiveControl()->getCausesValidation());
	}

	public function testTriggerAndRootMargin()
	{
		$panel = new TLazyPanel();
		$panel->setTrigger('load');
		$this->assertSame(TLazyPanelTrigger::Load, $panel->getTrigger());
		$panel->setRootMargin('200px 0px');
		$this->assertSame('200px 0px', $panel->getRootMargin());
	}

	public function testTriggerRejectsUnknownValue()
	{
		$this->expectException(TInvalidDataValueException::class);
		(new TLazyPanel())->setTrigger('Hover');
	}

	public function testUnloadedRenderShowsThePlaceholderBusyAndRegistersTheClient()
	{
		[$page, $panel] = $this->createPanel();
		$panel->setTrigger(TLazyPanelTrigger::Load);
		$panel->setRootMargin('50px');
		$html = $this->render($panel);
		$this->assertStringContainsString('aria-busy="true"', $html);
		$this->assertStringContainsString('<p class="ph">Loading</p>', $html);
		$this->assertStringContainsString('wait', $html);
		$this->assertStringNotContainsString('content', $html);
		$this->assertNull($panel->findControl('Value'));

		$controls = $page->getClientScript()->callbackControls;
		$this->assertCount(1, $controls);
		[$class, $options] = $controls[0];
		$this->assertSame('Prado.WebUI.TLazyPanel', $class);
		$this->assertSame('Lazy', $options['ID']);
		$this->assertSame('Lazy', $options['EventTarget']);
		$this->assertSame(TLazyPanelTrigger::Load, $options['Trigger']);
		$this->assertSame('50px', $options['RootMargin']);
		$this->assertFalse($options['CausesValidation']);
	}

	public function testPlaceholderIsInstantiatedOncePerRequest()
	{
		[, $panel] = $this->createPanel();
		$this->render($panel);
		$count = $panel->getControls()->getCount();
		$this->render($panel);
		$this->assertSame($count, $panel->getControls()->getCount());
	}

	public function testLoadContentInstantiatesTheContentAndRaisesOnLoadContent()
	{
		[$page, $panel] = $this->createPanel();
		$raised = [];
		$panel->attachEventHandler('OnLoadContent', function ($sender, $param) use (&$raised, $panel) {
			$raised[] = [$sender, $param, $panel->findControl('Value')];
		});
		$panel->loadContent('param');
		$this->assertTrue($panel->getIsContentLoaded());
		$this->assertCount(1, $raised);
		$this->assertSame($panel, $raised[0][0]);
		$this->assertSame('param', $raised[0][1]);
		$this->assertNotNull($raised[0][2], 'the content exists when OnLoadContent is raised');
		$this->assertSame($raised[0][2], $page->Value, 'content controls register with the template control');
		$this->assertSame('Lazy$Value', $raised[0][2]->getUniqueID());

		$panel->loadContent();
		$this->assertCount(1, $raised, 'loaded content is left as it is');
		$this->assertSame($page->getClientFunctions(), [], 'no client update outside a callback');

		$html = $this->render($panel);
		$this->assertStringNotContainsString('aria-busy', $html);
		$this->assertStringNotContainsString('Loading', $html);
		$this->assertStringContainsString('content', $html);
		$this->assertSame([], $page->getClientScript()->callbackControls);
	}

	public function testLoadContentWithoutAContentTemplate()
	{
		$page = new TTestCallbackPage();
		$page->callback = false;
		$this->preparePage($page);
		$panel = new TLazyPanel();
		$page->getControls()->add($panel);
		PradoUnit::invoke($panel, 'setControlStage', TControl::CS_PRERENDERED);
		$panel->attachEventHandler('OnLoadContent', function ($sender) {
			$sender->getControls()->add('added in the handler');
		});
		$panel->loadContent();
		$this->assertTrue($panel->getIsContentLoaded());
		$this->assertStringContainsString('added in the handler', $this->render($panel));
	}

	public function testLoadStateRecreatesLoadedContent()
	{
		[, $panel] = $this->createPanel(false, TControl::CS_CHILD_INITIALIZED);
		$panel->loadState();
		$this->assertNull($panel->findControl('Value'), 'unloaded content is not created');

		[$page, $panel] = $this->createPanel(false, TControl::CS_CHILD_INITIALIZED);
		$raised = 0;
		$panel->attachEventHandler('OnLoadContent', function () use (&$raised) {
			$raised++;
		});
		PradoUnit::invoke($panel, 'setControlState', 'ContentLoaded', true, false);
		$panel->loadState();
		$panel->loadState();
		$this->assertNotNull($panel->findControl('Value'));
		$this->assertCount(1, $panel->getControls());
		$this->assertSame(0, $raised, 'restored content raises no OnLoadContent');
		PradoUnit::invoke($panel, 'setControlStage', TControl::CS_PRERENDERED);
		$this->assertStringNotContainsString('aria-busy', $this->render($panel));
	}

	public function testLoadedFlagIsKeptInControlState()
	{
		[, $panel] = $this->createPanel();
		$panel->setEnableViewState(false);
		$panel->loadContent();
		$state = PradoUnit::invoke($panel, 'saveStateRecursive', false);
		$this->assertTrue($state[1]['ContentLoaded']);
	}

	public function testLoadCallbackLoadsAndReplacesThePlaceholder()
	{
		[$page, $panel] = $this->createPanel(true);
		$raised = 0;
		$panel->attachEventHandler('OnLoadContent', function ($sender, $param) use (&$raised) {
			$this->assertInstanceOf(TCallbackEventParameter::class, $param);
			$raised++;
		});
		$param = $this->callbackParameter();
		$panel->raiseCallbackEvent($param);
		$this->assertSame(1, $raised);
		$this->assertTrue($panel->getIsContentLoaded());
		$calls = $page->getClientFunctions();
		$this->assertCount(1, $calls);
		$this->assertSame($panel->getClientID(), $calls[0]['Prado.Element.replace'][0]);

		$panel->raiseCallbackEvent($param);
		$this->assertSame(1, $raised, 'a repeated load callback renders the loaded content again');
		$this->assertCount(2, $page->getClientFunctions());
	}

	public function testLoadContentInAnotherCallbackUpdatesTheClient()
	{
		[$page, $panel] = $this->createPanel(true);
		$panel->loadContent($this->callbackParameter());
		$calls = $page->getClientFunctions();
		$this->assertCount(1, $calls);
		$this->assertArrayHasKey('Prado.Element.replace', $calls[0]);
	}
}
