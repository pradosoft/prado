<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TConfigurationException;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TForm;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TCustomValidator;
use Prado\Web\UI\WebControls\TTextBox;

class TCustomValidatorTest extends TestCase
{
	/**
	 * Builds a page with a form and a TCustomValidator, and a text box when $withTarget is true.
	 */
	private function createPageValidator(bool $withTarget = true): TCustomValidator
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_clientScript', new TClientScriptManager($page));
		$form = new TForm();
		$form->setID('Form');
		$page->getControls()->add($form);
		$page->setForm($form);

		if ($withTarget) {
			$box = new TTextBox();
			$box->setID('Box');
			$page->getControls()->add($box);
		}

		$validator = new TCustomValidator();
		$validator->setID('Valid');
		if ($withTarget) {
			$validator->setControlToValidate('Box');
		}
		$page->getControls()->add($validator);
		return $validator;
	}

	public function testValidationTargetIsNullWithoutControlToValidate()
	{
		$this->assertNull($this->createPageValidator(false)->getValidationTarget());
	}

	public function testValidationTargetThrowsConfigurationExceptionForMissingControl()
	{
		$validator = $this->createPageValidator(false);
		$validator->setControlToValidate('Missing');
		try {
			$validator->getValidationTarget();
			$this->fail('TConfigurationException expected.');
		} catch (TConfigurationException $e) {
			$this->assertSame('basevalidator_controltovalidate_invalid', $e->getErrorCode());
		}
	}

	public function testClientScriptOptionsWithoutTargetOmitTheControl()
	{
		$validator = $this->createPageValidator(false);
		$validator->setClientValidationFunction('check');
		$options = PradoUnit::invoke($validator, 'getClientScriptOptions');
		$this->assertSame('check', $options['ClientValidationFunction']);
		$this->assertArrayNotHasKey('ControlToValidate', $options);
		$this->assertArrayNotHasKey('ControlType', $options);
	}

	public function testClientScriptOptionsWithTargetNameTheControl()
	{
		$validator = $this->createPageValidator();
		$options = PradoUnit::invoke($validator, 'getClientScriptOptions');
		$this->assertSame('Box', $options['ControlToValidate']);
		$this->assertSame('TTextBox', $options['ControlType']);
	}

	public function testFocusElementIdWithoutTargetIsEmpty()
	{
		$validator = $this->createPageValidator(false);
		$this->assertSame('', $validator->getFocusElementID());
		$validator->setFocusOnError(true);
		$options = PradoUnit::invoke($validator, 'getClientScriptOptions');
		$this->assertSame('', $options['FocusElementID']);
	}

	public function testRegistersClientScriptWithoutTarget()
	{
		$validator = $this->createPageValidator(false);
		$validator->setClientValidationFunction('check');
		PradoUnit::invoke($validator, 'registerClientScriptValidator');
		$this->assertTrue($validator->getPage()->getClientScript()->isEndScriptRegistered('prado:Valid'));
	}

	public function testServerValidatesAnEmptyValueWithoutTarget()
	{
		$validator = $this->createPageValidator(false);
		$values = [];
		$validator->attachEventHandler('OnServerValidate', function ($sender, $param) use (&$values) {
			$values[] = $param->getValue();
			$param->setIsValid(false);
		});
		$this->assertFalse($validator->evaluateIsValid());
		$this->assertSame([''], $values);
	}
}
