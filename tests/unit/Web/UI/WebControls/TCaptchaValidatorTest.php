<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TConfigurationException;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TCaptcha;
use Prado\Web\UI\WebControls\TCaptchaValidator;
use Prado\Web\UI\WebControls\TLabel;
use Prado\Web\UI\WebControls\TTextBox;

/**
 * A CAPTCHA whose token is `ABCD` and that records the inputs it validates.
 */
class TCaptchaValidatorTestCaptcha extends TCaptcha
{
	/** @var string[] the inputs validated. */
	public array $inputs = [];

	public function validate($input)
	{
		$this->inputs[] = $input;
		return $input === 'ABCD';
	}
}

class TCaptchaValidatorTest extends TestCase
{
	private TTextBox $box;
	private TCaptchaValidatorTestCaptcha $captcha;

	/**
	 * Builds a page with a text box `Box`, a CAPTCHA `Captcha`, a label `Label`,
	 * and a validator of the text box.
	 * @param string $captchaControl the validator's CaptchaControl.
	 * @return TCaptchaValidator the validator.
	 */
	private function createValidator(string $captchaControl = 'Captcha'): TCaptchaValidator
	{
		$page = new TPage();
		$this->box = new TTextBox();
		$this->box->setID('Box');
		$this->captcha = new TCaptchaValidatorTestCaptcha();
		$this->captcha->setID('Captcha');
		$label = new TLabel();
		$label->setID('Label');
		$validator = new TCaptchaValidator();
		$validator->setID('Valid');
		$validator->setControlToValidate('Box');
		$validator->setCaptchaControl($captchaControl);
		foreach ([$this->box, $this->captcha, $label, $validator] as $control) {
			$page->getControls()->add($control);
		}
		return $validator;
	}

	public function testCaptchaControl()
	{
		$validator = new TCaptchaValidator();
		$this->assertSame('', $validator->getCaptchaControl());
		$validator->setCaptchaControl('Captcha');
		$this->assertSame('Captcha', $validator->getCaptchaControl());
		$this->assertSame('Prado.WebUI.TCaptchaValidator', PradoUnit::invoke($validator, 'getClientClassName'));
	}

	public function testValidatesTheTrimmedInputWithTheCaptcha()
	{
		$validator = $this->createValidator();
		$this->box->setText(' ABCD ');
		$this->assertTrue(PradoUnit::invoke($validator, 'evaluateIsValid'));
		$this->box->setText('WXYZ');
		$this->assertFalse(PradoUnit::invoke($validator, 'evaluateIsValid'));
		$this->box->setText('');
		$this->assertFalse(PradoUnit::invoke($validator, 'evaluateIsValid'), 'The CAPTCHA requires an input.');
		$this->assertSame(['ABCD', 'WXYZ', ''], $this->captcha->inputs);
	}

	public static function provideInvalidCaptchaControl(): array
	{
		return [
			'not set' => ['', 'captchavalidator_captchacontrol_required'],
			'missing' => ['Missing', 'captchavalidator_captchacontrol_inexistent'],
			'not a CAPTCHA' => ['Label', 'captchavalidator_captchacontrol_invalid'],
		];
	}

	/**
	 * @param string $captchaControl the CaptchaControl ID.
	 * @param string $errorCode the expected error code.
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('provideInvalidCaptchaControl')]
	public function testInvalidCaptchaControlThrows(string $captchaControl, string $errorCode)
	{
		$validator = $this->createValidator($captchaControl);
		try {
			PradoUnit::invoke($validator, 'evaluateIsValid');
			$this->fail('TConfigurationException expected.');
		} catch (TConfigurationException $e) {
			$this->assertSame($errorCode, $e->getErrorCode());
		}
	}
}
