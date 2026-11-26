<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\Test\Unit\Harness\Web\TTestUrlAssetManager;
use Prado\Test\Unit\Harness\Web\UI\TTestCallbackPage;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\ActiveControls\TActiveCheckBoxList;
use Prado\Web\UI\ActiveControls\TActiveUpdateMode;
use Prado\Web\UI\ActiveControls\TBaseActiveCallbackControl;
use Prado\Web\UI\ActiveControls\TBaseActiveControl;
use Prado\Web\UI\ActiveControls\TCallback;
use Prado\Web\UI\ActiveControls\TCallbackClientSide;
use Prado\Web\UI\ActiveControls\TCallbackOptions;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TControl;
use Prado\Web\UI\WebControls\TLabel;
use Prado\Web\UI\WebControls\TRequiredFieldValidator;

/**
 * The common options of an active control ({@see TBaseActiveControl}) and of a
 * callback control ({@see TBaseActiveCallbackControl}): the client side, the callback
 * options of a {@see TCallbackOptions}, validation, and the client script.
 */
class TBaseActiveCallbackControlTest extends TestCase
{
	private array $snapshot = [];

	protected function setUp(): void
	{
		$this->snapshot = PradoUnit::snapshot(Prado::getApplication(), ['_assetManager']);
	}

	protected function tearDown(): void
	{
		PradoUnit::restore(Prado::getApplication(), $this->snapshot);
	}

	private function useUrlAssetManager(): void
	{
		Prado::getApplication()->setAssetManager(new TTestUrlAssetManager());
	}

	/**
	 * @return array{TTestCallbackPage, TCallback} a callback control with the ID `Act` in a callback page
	 */
	private function createCallback(): array
	{
		$page = new TTestCallbackPage();
		PradoUnit::setProp($page, '_clientScript', new TClientScriptManager($page));
		$callback = new TCallback();
		$callback->setID('Act');
		$page->getControls()->add($callback);
		return [$page, $callback];
	}

	/**
	 * @return TCallbackOptions callback options with the ID and a success handler, in the page
	 */
	private function addOptions(TControl $parent, string $id = 'Opts'): TCallbackOptions
	{
		$options = new TCallbackOptions();
		$options->setID($id);
		$options->getClientSide()->setRequestTimeOut(10);
		$parent->getControls()->add($options);
		return $options;
	}

	public function testUpdateOptionsHaveDefaults()
	{
		$base = new TBaseActiveControl(new TLabel());
		$this->assertTrue($base->getEnableUpdate());
		$this->assertSame(TActiveUpdateMode::Default, $base->getUpdateMode());
		$this->assertFalse($base->getViewTransition());
		$base->setEnableUpdate('false');
		$base->setUpdateMode('Morph');
		$base->setViewTransition('true');
		$this->assertFalse($base->getEnableUpdate());
		$this->assertSame(TActiveUpdateMode::Morph, $base->getUpdateMode());
		$this->assertTrue($base->getViewTransition());
	}

	public function testIsMorphUpdateWithoutAPageReadsTheControlMode()
	{
		$base = new TBaseActiveControl(new TLabel());
		$this->assertFalse($base->getIsMorphUpdate());
		$base->setUpdateMode(TActiveUpdateMode::Morph);
		$this->assertTrue($base->getIsMorphUpdate());
	}

	public function testClientSideIsCreatedOnceAndCannotBeReplaced()
	{
		[, $callback] = $this->createCallback();
		$client = $callback->getClientSide();
		$this->assertInstanceOf(TCallbackClientSide::class, $client);
		$this->assertSame($client, $callback->getActiveControl()->getClientSide());
		$this->expectException(TConfigurationException::class);
		$callback->getActiveControl()->setClientSide(new TCallbackClientSide());
	}

	public function testClientSideIsSetBeforeFirstUse()
	{
		[, $callback] = $this->createCallback();
		$client = new TCallbackClientSide();
		$callback->getActiveControl()->setClientSide($client);
		$this->assertSame($client, $callback->getClientSide());
	}

	public function testValidationAndParameterOptionsHaveDefaults()
	{
		[, $callback] = $this->createCallback();
		$active = $callback->getActiveControl();
		$this->assertTrue($active->getCausesValidation());
		$this->assertSame('', $active->getValidationGroup());
		$this->assertSame('', $active->getCallbackParameter());
		$this->assertSame('', $active->getCallbackOptions());
		$active->setCausesValidation('false');
		$active->setValidationGroup('G');
		$active->setCallbackParameter('p');
		$active->setCallbackOptions('Opts');
		$this->assertFalse($active->getCausesValidation());
		$this->assertSame('G', $active->getValidationGroup());
		$this->assertSame('p', $active->getCallbackParameter());
		$this->assertSame('Opts', $active->getCallbackOptions());
	}

	/**
	 * Without a validation group, every validator of the page counts.
	 */
	public function testCanCauseValidationNeedsAValidatorOfTheGroup()
	{
		[$page, $callback] = $this->createCallback();
		$active = $callback->getActiveControl();
		$this->assertFalse($active->canCauseValidation());
		$validator = new TRequiredFieldValidator();
		$validator->setValidationGroup('G');
		$page->getValidators()->add($validator);
		$this->assertTrue($active->canCauseValidation());
		$active->setValidationGroup('Other');
		$this->assertFalse($active->canCauseValidation());
		$active->setValidationGroup('G');
		$this->assertTrue($active->canCauseValidation());
		$active->setCausesValidation(false);
		$this->assertFalse($active->canCauseValidation());
	}

	public function testClientSideOptionsMergeTheCallbackOptionsOfAControlByID()
	{
		[$page, $callback] = $this->createCallback();
		$this->addOptions($page);
		$active = $callback->getActiveControl();
		$active->setCallbackOptions('Opts');
		$active->setCausesValidation(false);
		$callback->getClientSide()->setRetryLimit(3);
		$this->assertSame(
			['RequestTimeOut' => 10, 'RetryLimit' => 3, 'CausesValidation' => false, 'ValidationGroup' => '', 'CallbackParameter' => '', 'EventTarget' => 'Act'],
			PradoUnit::invoke($active, 'getClientSideOptions')
		);
	}

	public function testClientSideOptionsKeepThePostBackTarget()
	{
		[, $callback] = $this->createCallback();
		$callback->getClientSide()->setPostBackTarget('Other');
		$options = PradoUnit::invoke($callback->getActiveControl(), 'getClientSideOptions');
		$this->assertSame('Other', $options['EventTarget']);
		$this->assertSame('', $options['CausesValidation']);
	}

	public function testCallbackOptionsByPathReadANamedObject()
	{
		[$page, $callback] = $this->createCallback();
		$options = $this->addOptions($page, 'Named');
		$page->registerObject('Named', $options);
		$active = $callback->getActiveControl();
		$active->setCallbackOptions('Page.Named');
		$this->assertSame(10, PradoUnit::invoke($active, 'getDefaultClientSideOptions')['RequestTimeOut']);
	}

	/**
	 * A check box list is a naming container whose findControl() returns itself, so it
	 * finds the callback options by ID among its own child controls.
	 */
	public function testCallbackOptionsOfACheckBoxListFindTheControl()
	{
		$page = new TTestCallbackPage();
		$list = new TActiveCheckBoxList();
		$list->setID('List');
		$page->getControls()->add($list);
		$this->addOptions($list);
		$active = $list->getActiveControl();
		$active->setCallbackOptions('Opts');
		$this->assertSame(['RequestTimeOut' => 10], PradoUnit::invoke($active, 'getDefaultClientSideOptions'));
	}

	public static function invalidOptionsProvider(): array
	{
		return [
			'missing ID' => ['Missing'],
			'not callback options' => ['Act'],
			'path to no object' => ['Page.Form.Missing'],
		];
	}

	/**
	 * @dataProvider invalidOptionsProvider
	 */
	public function testCallbackOptionsThatNameNoCallbackOptionsThrow(string $id)
	{
		[, $callback] = $this->createCallback();
		$active = $callback->getActiveControl();
		$active->setCallbackOptions($id);
		$this->expectException(TConfigurationException::class);
		PradoUnit::invoke($active, 'getDefaultClientSideOptions');
	}

	/**
	 * A CausesValidation of true becomes the empty string, which the encoder leaves out with the other empty options.
	 */
	public function testRegisterCallbackClientScriptMergesTheOptions()
	{
		$this->useUrlAssetManager();
		[$page, $callback] = $this->createCallback();
		$active = $callback->getActiveControl();
		$active->registerCallbackClientScript('Prado.WebUI.Test', ['Extra' => 1]);
		$active->registerCallbackClientScript('Prado.WebUI.True', ['CausesValidation' => true]);
		$active->registerCallbackClientScript('Prado.WebUI.Plain');
		$scripts = array_values(PradoUnit::getProp($page->getClientScript(), '_endScripts'));
		$this->assertSame([
			"new Prado.WebUI.Test({'EventTarget':\"Act\",'Extra':1});",
			"new Prado.WebUI.True({'EventTarget':\"Act\"});",
			"new Prado.WebUI.Plain({'EventTarget':\"Act\"});",
		], $scripts);
	}

	public function testJavascriptAndJsCallbackOptions()
	{
		$this->useUrlAssetManager();
		[, $callback] = $this->createCallback();
		$active = $callback->getActiveControl();
		$active->setCallbackParameter('p');
		$expected = "{'CallbackParameter':\"p\",'EventTarget':\"Act\"}";
		$this->assertSame($expected, $active->getJsCallbackOptions());
		$this->assertSame("new Prado.CallbackRequest('Act'," . $expected . ')', $active->getJavascript());
	}

	public function testCallbackOptionsByPathToAnotherObjectThrow()
	{
		[$page, $callback] = $this->createCallback();
		$page->registerObject('Named', $callback);
		$active = $callback->getActiveControl();
		$active->setCallbackOptions('Page.Named');
		$this->expectException(TConfigurationException::class);
		PradoUnit::invoke($active, 'getDefaultClientSideOptions');
	}

	public function testCallbackOptionsOfACheckBoxListThatNameNoChildThrow()
	{
		$page = new TTestCallbackPage();
		$list = new TActiveCheckBoxList();
		$list->setID('List');
		$page->getControls()->add($list);
		$this->addOptions($page);
		$active = $list->getActiveControl();
		$active->setCallbackOptions('Opts');
		$this->expectException(TConfigurationException::class);
		PradoUnit::invoke($active, 'getDefaultClientSideOptions');
	}

	public function testClientSideOptionsWithoutValidationKeepThePostBackTarget()
	{
		[, $callback] = $this->createCallback();
		$callback->getActiveControl()->setCausesValidation(false);
		$callback->getClientSide()->setPostBackTarget('Other');
		$options = PradoUnit::invoke($callback->getActiveControl(), 'getClientSideOptions');
		$this->assertFalse($options['CausesValidation']);
		$this->assertSame('Other', $options['EventTarget']);
	}

	public function testSetOptionOfNullStoresTheDefault()
	{
		[, $callback] = $this->createCallback();
		$active = $callback->getActiveControl();
		$active->setValidationGroup(null);
		PradoUnit::invoke($active, 'setOption', 'Unset', null);
		$options = PradoUnit::invoke($active, 'getOptions');
		$this->assertSame('', $options->itemAt('ValidationGroup'));
		$this->assertFalse($options->contains('Unset'));
	}

	public function testIsMorphUpdateByDefaultReadsThePageMode()
	{
		[$page, $callback] = $this->createCallback();
		$this->assertFalse($callback->getActiveControl()->getIsMorphUpdate());
		$page->setCallbackUpdateMode(TActiveUpdateMode::Morph);
		$this->assertTrue($callback->getActiveControl()->getIsMorphUpdate());
	}
}
