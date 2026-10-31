<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\IO\TTextWriter;
use Prado\Prado;
use Prado\Test\Unit\Harness\Web\TTestCookieResponse;
use Prado\Test\Unit\Harness\Web\UI\TTestCallbackPage;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\Services\TPageService;
use Prado\Web\UI\ActiveControls\TCallbackResponseAdapter;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TForm;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TPanel;
use Prado\Web\UI\WebControls\TTeleport;
use Prado\Web\UI\WebControls\TWebControl;

/**
 * Client script manager that records the script packages a control registers.
 */
class TTeleportTestClientScript extends TClientScriptManager
{
	public array $pradoScripts = [];

	public function registerPradoScript($name)
	{
		$this->pradoScripts[] = $name;
	}

	protected function registerPradoScriptInternal($name)
	{
		$this->pradoScripts[] = $name;
	}
}

/**
 * TTeleport renders its content after the page form, in place, or as a callback update.
 */
class TTeleportTest extends TestCase
{
	private $_obLevel;
	private $_service;
	private $_response;

	protected function setUp(): void
	{
		$app = Prado::getApplication();
		$this->_obLevel = ob_get_level();
		$this->_service = $app->getService();
		$this->_response = $app->getResponse();
		$app->setService(new TPageService());
		// the form renders the CSRF token, which writes its cookie to the response
		$app->setResponse(new TTestCookieResponse());
	}

	protected function tearDown(): void
	{
		$app = Prado::getApplication();
		$app->setService($this->_service);
		$app->setResponse($this->_response);
		// the first getResponse() of the run bootstraps the response, which starts a buffer
		while (ob_get_level() > $this->_obLevel) {
			ob_end_clean();
		}
	}

	/**
	 * @return array{TPage, TForm, TTeleport} a page whose form holds a teleport between two texts
	 */
	private function createPage(?TPage $page = null): array
	{
		$page ??= new TPage();
		PradoUnit::setProp($page, '_clientScript', new TTeleportTestClientScript($page));
		$form = new TForm();
		$form->setID('PageForm');
		$page->getControls()->add($form);
		$form->onInit(null);
		$form->getControls()->add('<p>before</p>');
		$teleport = new TTeleport();
		$teleport->setID('Layer');
		$form->getControls()->add($teleport);
		$teleport->getControls()->add('<dialog id="dlg"><form method="dialog"><button>OK</button></form></dialog>');
		$form->getControls()->add('<p>after</p>');
		return [$page, $form, $teleport];
	}

	private function renderForm(TForm $form): string
	{
		$tw = new TTextWriter();
		$form->renderControl(new THtmlWriter($tw));
		return $tw->flush();
	}

	public function testExtendsWebControlAndRendersADiv()
	{
		$teleport = new TTeleport();
		$this->assertInstanceOf(TWebControl::class, $teleport);
		$this->assertSame('div', PradoUnit::invoke($teleport, 'getTagName'));
	}

	public function testContentRendersAfterTheFormAndBeforeTheEndScripts()
	{
		[$page, $form, $teleport] = $this->createPage();
		$output = $this->renderForm($form);

		$formEnd = strpos($output, '</form>');
		$content = strpos($output, '<div id="' . $teleport->getClientID() . '" data-prado-teleport="">');
		$dialog = strpos($output, '<dialog id="dlg"><form method="dialog">');
		$script = strpos($output, 'new Prado.WebUI.TTeleport(');
		$this->assertNotFalse($content);
		$this->assertLessThan($formEnd, strpos($output, '<p>after</p>'));
		$this->assertLessThan($content, $formEnd);
		$this->assertLessThan($dialog, $content);
		$this->assertLessThan($script, $dialog);
		$this->assertSame(1, substr_count($output, '<dialog id="dlg">'));
		$this->assertStringEndsWith('</script>', rtrim($output));
		$this->assertSame([], $form->getTeleportContents());
	}

	public function testTheClientControlCarriesTheFormIDAndLoadsTheTeleportPackage()
	{
		[$page, $form, $teleport] = $this->createPage();
		$output = $this->renderForm($form);
		$this->assertStringContainsString("'ID':\"" . $teleport->getClientID() . "\"", $output);
		$this->assertStringContainsString("'FormID':\"PageForm\"", $output);
		$this->assertContains('teleport', $page->getClientScript()->pradoScripts);
	}

	public function testAFormWithoutTeleportKeepsItsEndScriptsInside()
	{
		$page = new TPage();
		$form = new TForm();
		$form->setID('PageForm');
		$page->getControls()->add($form);
		$form->onInit(null);
		$page->getClientScript()->registerEndScript('probe', 'window.probe = 1;');
		$output = $this->renderForm($form);
		$this->assertLessThan(strpos($output, '</form>'), strpos($output, 'window.probe = 1;'));
		$this->assertStringEndsWith('</form>', rtrim($output));
	}

	public function testANestedTeleportRendersInPlaceInTheOuterContent()
	{
		[$page, $form, $teleport] = $this->createPage();
		$inner = new TTeleport();
		$inner->setID('Inner');
		$teleport->getControls()->add($inner);
		$inner->getControls()->add('<span>inner</span>');
		$this->assertTrue($inner->getIsInsideTeleport());
		$this->assertFalse($teleport->getIsInsideTeleport());

		$output = $this->renderForm($form);
		$outer = strpos($output, 'id="' . $teleport->getClientID() . '"');
		$this->assertLessThan(strpos($output, 'id="' . $inner->getClientID() . '"'), $outer);
		$this->assertLessThan($outer, strpos($output, '</form>'));
		$this->assertSame(1, substr_count($output, '<span>inner</span>'));
		$this->assertSame(1, substr_count($output, 'new Prado.WebUI.TTeleport('));
	}

	public function testATeleportOutsideTheFormRendersInPlace()
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_clientScript', new TTeleportTestClientScript($page));
		$panel = new TPanel();
		$page->getControls()->add($panel);
		$teleport = new TTeleport();
		$teleport->setID('Layer');
		$panel->getControls()->add($teleport);
		$teleport->getControls()->add('<b>here</b>');

		$tw = new TTextWriter();
		$panel->renderControl(new THtmlWriter($tw));
		$this->assertSame('<div><div id="' . $teleport->getClientID() . '" data-prado-teleport=""><b>here</b></div></div>', $tw->flush());
	}

	public function testTheContentKeepsTheWriterOutput()
	{
		[$page, $form, $teleport] = $this->createPage();
		$tw = new TTextWriter();
		$writer = new THtmlWriter($tw);
		$writer->write('<x>');
		$html = PradoUnit::invoke($teleport, 'renderContent', $writer);
		$this->assertSame($tw, $writer->getWriter());
		$this->assertSame('<x>', $tw->flush());
		$this->assertStringStartsWith('<div id="' . $teleport->getClientID() . '"', $html);
	}

	public function testACallbackSendsTheContentToTheClientReplace()
	{
		$page = new TTestCallbackPage();
		[$page, $form, $teleport] = $this->createPage($page);
		$tw = new TTextWriter();
		$teleport->renderControl(new THtmlWriter($tw));

		$this->assertSame('', $tw->flush());
		$functions = $page->getClientFunctions();
		$this->assertCount(1, $functions);
		$params = $functions[0]['Prado.WebUI.TTeleport.replace'];
		$this->assertSame($teleport->getClientID(), $params[0]);
		$this->assertSame('PageForm', $params[1]);
		$this->assertStringStartsWith('<div id="' . $teleport->getClientID() . '" data-prado-teleport="">', $params[2]);
		$this->assertNull($params[3]);
	}

	public function testACallbackSendsTheContentInAResponseBoundary()
	{
		$response = Prado::getApplication()->getResponse();
		$response->setAdapter(new TCallbackResponseAdapter($response));
		$page = new TTestCallbackPage();
		[$page, $form, $teleport] = $this->createPage($page);
		$teleport->renderControl(new THtmlWriter(new TTextWriter()));

		$params = $page->getClientFunctions()[0]['Prado.WebUI.TTeleport.replace'];
		$this->assertNull($params[2]);
		$this->assertIsString($params[3]);
		$this->assertNotSame('', $params[3]);
	}

	public function testFormTeleportContentRegistry()
	{
		$form = new TForm();
		$this->assertFalse($form->getHasTeleportContent());
		$form->registerTeleportContent('a', '<i>1</i>');
		$form->registerTeleportContent('b', '<i>2</i>');
		$form->registerTeleportContent('a', '<i>3</i>');
		$this->assertTrue($form->getHasTeleportContent());
		$this->assertSame(['a' => '<i>3</i>', 'b' => '<i>2</i>'], $form->getTeleportContents());
	}
}
