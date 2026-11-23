<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TConfigurationException;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TReCaptcha2;
use Prado\Web\UI\WebControls\TReCaptcha2Validator;
use Prado\Web\UI\WebControls\TTextBox;

class TReCaptcha2ValidatorTestCaptcha extends TReCaptcha2
{
	public int $validations = 0;
	public bool $result = true;

	public function validate()
	{
		$this->validations++;
		return $this->result;
	}
}

class TReCaptcha2ValidatorTest extends TestCase
{
	private function create(string $target = 'Captcha', bool $callback = false): array
	{
		$page = $this->getMockBuilder(TPage::class)->onlyMethods(['getIsCallback', 'getForm'])->getMock();
		$page->method('getIsCallback')->willReturn($callback);
		$form = new \Prado\Web\UI\TForm();
		$form->setID('form1');
		$page->method('getForm')->willReturn($form);
		$cs = $this->getMockBuilder(TClientScriptManager::class)->setConstructorArgs([$page])
			->onlyMethods(['registerPradoScript', 'registerHiddenField', 'registerEndScript'])->getMock();
		PradoUnit::setProp($page, '_clientScript', $cs);
		$captcha = new TReCaptcha2ValidatorTestCaptcha();
		$captcha->setID('Captcha');
		$page->getControls()->add($captcha);
		$text = new TTextBox();
		$text->setID('Text');
		$page->getControls()->add($text);
		$validator = new TReCaptcha2Validator();
		$validator->setID('Val');
		$validator->setControlToValidate($target);
		$page->getControls()->add($validator);
		return [$validator, $captcha, $cs];
	}

	public function testClientClassAndScript()
	{
		$validator = new TReCaptcha2Validator();
		$this->assertSame('Prado.WebUI.TReCaptcha2Validator', PradoUnit::invoke($validator, 'getClientClassName'));
		$this->assertTrue($validator->getEnableClientScript());
	}

	public function testTheTargetMustBeATReCaptcha2()
	{
		[$validator] = $this->create('Text');
		try {
			PradoUnit::invoke($validator, 'getCaptchaControl');
			$this->fail('Expected TConfigurationException');
		} catch (TConfigurationException $e) {
			$this->assertSame('recaptcha2validator_control_invalid', $e->getErrorCode());
			$this->assertStringContainsString('Text', $e->getMessage());
		}
	}

	public function testAMissingTargetThrows()
	{
		[$validator] = $this->create('Missing');
		try {
			PradoUnit::invoke($validator, 'getCaptchaControl');
			$this->fail('Expected TConfigurationException');
		} catch (TConfigurationException $e) {
			$this->assertSame('basevalidator_controltovalidate_invalid', $e->getErrorCode());
		}
	}

	public function testClientScriptOptionsNameTheResponseField()
	{
		[$validator, $captcha] = $this->create();
		$this->assertSame($captcha->getResponseFieldName(), $validator->getClientScriptOptions()['ResponseFieldName']);
	}

	public function testEvaluateIsValidAsksTheCaptchaOnce()
	{
		[$validator, $captcha] = $this->create();
		$captcha->result = false;
		$this->assertFalse(PradoUnit::invoke($validator, 'evaluateIsValid'));
		$this->assertFalse(PradoUnit::invoke($validator, 'evaluateIsValid'));
		$this->assertSame(1, $captcha->validations);
	}

	public static function preRenderProvider(): array
	{
		// evaluated result, callback => hidden value, callback call
		return [
			'not evaluated' => [null, false, '1', false],
			'failed' => [false, false, '0', false],
			'passed in a callback' => [true, true, '1', true],
			'failed in a callback' => [false, true, '0', true],
		];
	}

	/**
	 * @dataProvider preRenderProvider
	 */
	public function testOnPreRenderSendsTheStatus(?bool $result, bool $callback, string $value, bool $callsBack)
	{
		[$validator, $captcha, $cs] = $this->create('Captcha', $callback);
		if ($result !== null) {
			$captcha->result = $result;
			PradoUnit::invoke($validator, 'evaluateIsValid');
		}
		$cs->expects($this->once())->method('registerHiddenField')->with('Val_1', $value);
		$cs->expects($this->atLeastOnce())->method('registerEndScript')->willReturnCallback(function ($key, $script) use ($callsBack, $value) {
			if ($key === 'Val::validate') {
				$this->assertStringContainsString('function captchaUpdateValidatorStatus_Val(valid)', $script);
				$this->assertSame($callsBack, str_contains($script, 'captchaUpdateValidatorStatus_Val(' . $value . ');'));
				$this->assertStringContainsString('jQuery("#Captcha").on("change"', $script);
			}
		});
		$validator->onPreRender(null);
	}
}
