<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TNotSupportedException;
use Prado\IO\TTextWriter;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\ActiveControls\TActiveCustomValidator;
use Prado\Web\UI\ActiveControls\TActiveCustomValidatorClientSide;
use Prado\Web\UI\ActiveControls\TCallbackEventParameter;
use Prado\Web\UI\THtmlWriter;

class TActiveCustomValidatorTest extends TestCase
{
	private function createValidator(bool $callback = true): array
	{
		$page = new TActiveTestPage($callback);
		$validator = $page->addControl(new TActiveCustomValidator(), 'Check');
		return [$page, $validator];
	}

	/**
	 * @return TCallbackEventParameter a callback parameter whose response records the response data
	 */
	private function callbackParameter($parameter): TCallbackEventParameter
	{
		$response = new class () {
			public $adapter;

			public function __construct()
			{
				$this->adapter = new class () {
					public $data = 'unset';

					public function setResponseData($data)
					{
						$this->data = $data;
					}

					public function getResponseData()
					{
						return $this->data;
					}
				};
			}

			public function getAdapter()
			{
				return $this->adapter;
			}
		};
		return new TCallbackEventParameter($response, $parameter);
	}

	public function testClientSideIsTheActiveCustomValidatorClientSide()
	{
		$validator = new TActiveCustomValidator();
		$client = $validator->getClientSide();
		$this->assertInstanceOf(TActiveCustomValidatorClientSide::class, $client);
		$this->assertSame($validator->getActiveControl()->getClientSide(), $client);
		$this->assertFalse($validator->getIsCallback());
		$this->assertTrue($validator->getEnableClientScript());
	}

	public function testClientSideOptions()
	{
		$client = new TActiveCustomValidatorClientSide();
		$this->assertNull($client->getOnValidate());
		$this->assertNull($client->getOnValidationSuccess());
		$this->assertNull($client->getOnValidationError());
		$this->assertTrue($client->getObserveChanges());
		$client->setOnValidate('a()');
		$client->setOnValidationSuccess('b()');
		$client->setOnValidationError('c()');
		$client->setObserveChanges('false');
		$this->assertStringContainsString('a()', (string) $client->getOnValidate());
		$this->assertStringContainsString('b()', (string) $client->getOnValidationSuccess());
		$this->assertStringContainsString('c()', (string) $client->getOnValidationError());
		$this->assertFalse($client->getObserveChanges());
	}

	public function testSetClientValidationFunctionIsNotSupported()
	{
		$this->expectException(TNotSupportedException::class);
		(new TActiveCustomValidator())->setClientValidationFunction('f');
	}

	public function testRaiseCallbackEventValidatesAndReturnsTheResult()
	{
		[$page, $validator] = $this->createValidator();
		$events = [];
		$validator->attachEventHandler('OnServerValidate', function ($sender, $param) use (&$events) {
			$events[] = 'validate:' . $param->getValue();
			$param->setIsValid($param->getValue() === 'ok');
		});
		$validator->attachEventHandler('OnCallback', function ($sender, $param) use (&$events, $validator) {
			$events[] = 'callback:' . json_encode($validator->getIsCallback());
		});
		$param = $this->callbackParameter('bad');
		$validator->raiseCallbackEvent($param);
		$this->assertSame(['validate:bad', 'callback:true'], $events);
		$this->assertFalse($param->getResponseData());

		$param = $this->callbackParameter('ok');
		$validator->raiseCallbackEvent($param);
		$this->assertTrue($param->getResponseData());
	}

	public function testSetIsValidUpdatesTheClientInACallback()
	{
		[$page, $validator] = $this->createValidator();
		$validator->setIsValid(false);
		$this->assertFalse($validator->getIsValid());
		$this->assertSame([['Prado.Validation.updateActiveCustomValidator' => ['Check', false]]], $page->getClientFunctions());

		[$page, $validator] = $this->createValidator(false);
		$validator->setIsValid(false);
		$this->assertSame([], $page->getClientFunctions());
	}

	public function testSetErrorMessageUpdatesTheClientOnce()
	{
		[$page, $validator] = $this->createValidator();
		$validator->setErrorMessage('Wrong');
		$validator->setErrorMessage('Wrong');
		$this->assertSame('Wrong', $validator->getErrorMessage());
		$this->assertSame([['Prado.Validation.setErrorMessage' => ['Check', 'Wrong']]], $page->getClientFunctions());

		[$page, $validator] = $this->createValidator(false);
		$validator->setErrorMessage('Wrong');
		$this->assertSame([], $page->getClientFunctions());
	}

	public function testAddAttributesToRenderRegistersTheValidatorScript()
	{
		[$page, $validator] = $this->createValidator();
		$validator->setErrorMessage('Wrong');
		PradoUnit::invoke($validator, 'addAttributesToRender', new THtmlWriter(new TTextWriter()));
		$script = PradoUnit::getProp($page->cs, '_endScripts')['prado:Check'];
		$this->assertStringStartsWith('new Prado.WebUI.TActiveCustomValidator({', $script);
		$this->assertStringContainsString('\'EventTarget\':"Check"', $script);
		$this->assertStringContainsString('\'ErrorMessage\':"Wrong"', $script);
	}

	public function testGetClientScriptOptionsCarryTheEventTarget()
	{
		[, $validator] = $this->createValidator();
		$options = PradoUnit::invoke($validator, 'getClientScriptOptions');
		$this->assertSame('Check', $options['EventTarget']);
		$this->assertSame('Check', $options['ID']);
	}
}
