<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use PHPUnit\Framework\TestCase;
use Prado\IO\TTextWriter;
use Prado\Prado;
use Prado\Test\Unit\Harness\Web\UI\TTestCallbackPage;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\THttpResponse;
use Prado\Web\UI\ActiveControls\TActivePageAdapter;
use Prado\Web\UI\ActiveControls\TActivePanel;
use Prado\Web\UI\ActiveControls\TActiveUpdateMode;
use Prado\Web\UI\ActiveControls\TCallbackClientScript;
use Prado\Web\UI\ActiveControls\TCallbackResponseAdapter;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TControl;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\TPage;
use Prado\Web\UI\TSkinTemplate;
use Prado\Web\UI\TTemplate;
use Prado\Web\UI\TTheme;

/**
 * TPage.CallbackViewTransition, ActiveControl.ViewTransition, TCallbackClientScript.ViewTransition,
 * and the X-PRADO-VIEWTRANSITION response part of TActivePageAdapter.
 */
class TCallbackViewTransitionTest extends TestCase
{
	public function testPageCallbackViewTransition()
	{
		$page = new TPage();
		$this->assertFalse($page->getCallbackViewTransition());
		$page->setCallbackViewTransition('true');
		$this->assertTrue($page->getCallbackViewTransition());
		$page->setCallbackViewTransition(false);
		$this->assertFalse($page->getCallbackViewTransition());
	}

	public function testCallbackClientViewTransition()
	{
		$client = new TCallbackClientScript();
		$this->assertFalse($client->getViewTransition());
		$client->setViewTransition('true');
		$this->assertTrue($client->getViewTransition());
		$client->setViewTransition(false);
		$this->assertFalse($client->getViewTransition());
	}

	public function testControlViewTransition()
	{
		$panel = new TActivePanel();
		$this->assertFalse($panel->getActiveControl()->getViewTransition());
		$panel->getActiveControl()->setViewTransition('true');
		$this->assertTrue($panel->getActiveControl()->getViewTransition());
	}

	/**
	 * Renders a prerendered panel on a callback page and returns the page.
	 */
	private function renderPanel(bool $viewTransition, string $updateMode = TActiveUpdateMode::Replace): TTestCallbackPage
	{
		$page = new TTestCallbackPage();
		$page->setCallbackUpdateMode($updateMode);
		$panel = new TActivePanel();
		$panel->setID('Results');
		$page->getControls()->add($panel);
		$panel->getActiveControl()->setViewTransition($viewTransition);
		PradoUnit::invoke($panel, 'setControlStage', TControl::CS_PRERENDERED);
		$panel->render(new THtmlWriter(new TTextWriter()));
		return $page;
	}

	public function testRenderOfAViewTransitionControlTurnsOnTheResponseTransition()
	{
		$page = $this->renderPanel(true);
		$this->assertTrue($page->getCallbackClient()->getViewTransition());
		$calls = $page->getClientFunctions();
		$this->assertCount(1, $calls);
		$this->assertSame('Results', $calls[0]['Prado.Element.replace'][0]);
		$this->assertCount(4, $calls[0]['Prado.Element.replace']);
	}

	public function testRenderOfAnOrdinaryControlLeavesTheResponseTransitionOff()
	{
		$page = $this->renderPanel(false);
		$this->assertFalse($page->getCallbackClient()->getViewTransition());
		$this->assertCount(1, $page->getClientFunctions());
	}

	public function testAViewTransitionControlStillMorphs()
	{
		$page = $this->renderPanel(true, TActiveUpdateMode::Morph);
		$this->assertTrue($page->getCallbackClient()->getViewTransition());
		$params = $page->getClientFunctions()[0]['Prado.Element.replace'];
		$this->assertCount(5, $params);
		$this->assertTrue($params[4]);
	}

	public function testOutsideACallbackTheControlSendsNothing()
	{
		$page = new TTestCallbackPage();
		$page->callback = false;
		$panel = new TActivePanel();
		$page->getControls()->add($panel);
		$panel->getActiveControl()->setViewTransition(true);
		PradoUnit::invoke($panel, 'setControlStage', TControl::CS_PRERENDERED);
		$panel->render(new THtmlWriter(new TTextWriter()));
		$this->assertFalse($page->getCallbackClient()->getViewTransition());
		$this->assertSame([], $page->getClientFunctions());
	}

	/**
	 * @dataProvider adapterProvider
	 */
	public function testAdapterResolvesThePageAndTheResponse(bool $page, bool $client, bool $expected)
	{
		$p = new TPage();
		$p->setCallbackViewTransition($page);
		$adapter = $this->adapter($p);
		$adapter->getCallbackClientHandler()->setViewTransition($client);
		$this->assertSame($expected, PradoUnit::invoke($adapter, 'getIsViewTransition'));
	}

	public static function adapterProvider(): array
	{
		return [
			'neither' => [false, false, false],
			'page' => [true, false, true],
			'response' => [false, true, true],
			'both' => [true, true, true],
		];
	}

	/**
	 * @dataProvider responseProvider
	 */
	public function testResponseCarriesTheViewTransitionPart(bool $page, bool $actions, bool $expected)
	{
		$p = new TPage();
		$p->setEnableCsrfValidation(false);
		PradoUnit::setProp($p, '_clientScript', new TCallbackViewTransitionClientScript($p));
		$p->setCallbackViewTransition($page);
		$adapter = $this->adapter($p);
		if ($actions) {
			$adapter->getCallbackClientHandler()->show('Target');
		}
		$output = $this->renderResponse($adapter);
		$this->assertStringContainsString('<!--' . TActivePageAdapter::CALLBACK_ACTION_HEADER . '-->', $output);
		$part = '<!--' . TActivePageAdapter::CALLBACK_VIEWTRANSITION_HEADER . '-->1<!--//' . TActivePageAdapter::CALLBACK_VIEWTRANSITION_HEADER . '-->';
		$this->assertSame($expected, str_contains($output, $part));
	}

	public static function responseProvider(): array
	{
		return [
			'transition with actions' => [true, true, true],
			'transition without actions' => [true, false, false],
			'no transition' => [false, true, false],
		];
	}

	public function testASkinSetsTheViewTransitionForEveryControlOfAType()
	{
		$template = new TSkinTemplate('<com:Prado\\Web\\UI\\ActiveControls\\TActivePanel ActiveControl.ViewTransition="true" />', __DIR__);
		$skins = [];
		foreach ($template->getItems() as $item) {
			if (isset($item[TTemplate::TPL_PROPS])) {
				$skins[$item[TTemplate::TPL_TYPE]][0] = $item[TTemplate::TPL_PROPS];
			}
		}
		$theme = PradoUnit::reflectionClass(TTheme::class)->newInstanceWithoutConstructor();
		PradoUnit::setProp($theme, '_skins', $skins);
		$panel = new TActivePanel();
		$this->assertTrue($theme->applySkin($panel));
		$this->assertTrue($panel->getActiveControl()->getViewTransition());
	}

	/**
	 * Returns a page adapter for the page, without the constructor's error handler and response adapter.
	 */
	private function adapter(TPage $page): TActivePageAdapter
	{
		$adapter = PradoUnit::reflectionClass(TActivePageAdapter::class)->newInstanceWithoutConstructor();
		PradoUnit::setProp($adapter, '_control', $page);
		return $adapter;
	}

	/**
	 * Renders the callback response through a callback response adapter of a fresh
	 * response, the application response meanwhile, and returns its parts.
	 */
	private function renderResponse(TActivePageAdapter $adapter): string
	{
		$app = Prado::getApplication();
		$saved = PradoUnit::invoke($app, 'getResponseDirect');
		$response = new THttpResponse();
		$callbackAdapter = new TCallbackResponseAdapter($response);
		$response->setAdapter($callbackAdapter);
		$app->setResponse($response);
		try {
			PradoUnit::invoke($adapter, 'renderResponse', $response->createHtmlWriter());
		} finally {
			PradoUnit::setProp($app, '_response', $saved);
		}
		$output = '';
		foreach (PradoUnit::getProp($callbackAdapter, '_writers') as $writer) {
			$output .= $writer->flush();
		}
		return $output;
	}
}

/**
 * A client script manager without published assets, so a response renders without an asset manager.
 */
class TCallbackViewTransitionClientScript extends TClientScriptManager
{
	public function getStyleSheetUrls()
	{
		return [];
	}
}
