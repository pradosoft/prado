<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TNotSupportedException;
use Prado\Test\Unit\Harness\Traits\TAssetManagerMockTrait;
use Prado\Test\Unit\Harness\Traits\TFormPageTrait;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\UI\TControl;
use Prado\Web\UI\WebControls\TBaseValidator;
use Prado\Web\UI\WebControls\TCheckBox;
use Prado\Web\UI\WebControls\TCheckBoxList;
use Prado\Web\UI\WebControls\TDatePicker;
use Prado\Web\UI\WebControls\THiddenField;
use Prado\Web\UI\WebControls\TLiteral;
use Prado\Web\UI\WebControls\TRadioButtonList;
use Prado\Web\UI\WebControls\TTextBox;
use Prado\Web\UI\WebControls\TValidatorClientSide;
use Prado\Web\UI\WebControls\TValidatorDisplayStyle;

/**
 * A validator whose result the test sets.
 */
class TBaseValidatorTestValidator extends TBaseValidator
{
	public bool $result = true;
	public int $evaluations = 0;

	protected function getClientClassName()
	{
		return 'Prado.WebUI.TTestValidator';
	}

	protected function evaluateIsValid()
	{
		$this->evaluations++;
		return $this->result;
	}
}

class TBaseValidatorTest extends TestCase
{
	use TAssetManagerMockTrait;
	use TFormPageTrait;
	use TWebControlRenderTrait;

	private string $_mode;

	protected function setUp(): void
	{
		$this->mockAssetManager(['getBaseUrl'])->method('getBaseUrl')->willReturn('');
		// Outside Debug mode, the client script manager writes minified scripts to the published path.
		$this->_mode = \Prado\Prado::getApplication()->getMode();
		\Prado\Prado::getApplication()->setMode(\Prado\TApplicationMode::Debug);
	}

	protected function tearDown(): void
	{
		\Prado\Prado::getApplication()->setMode($this->_mode);
		$this->restoreAssetManager();
	}

	private TTextBox $box;

	/**
	 * Builds a form page with a text box `Box` and a validator `Valid` of it.
	 * @param ?TControl $target the control `Target` the validator validates instead of the text box.
	 * @return TBaseValidatorTestValidator the validator.
	 */
	private function createValidator(?TControl $target = null): TBaseValidatorTestValidator
	{
		$this->box = new TTextBox();
		$this->box->setID('Box');
		$validator = new TBaseValidatorTestValidator();
		$validator->setID('Valid');
		$validator->setControlToValidate('Box');
		$controls = [$this->box, $validator];
		if ($target !== null) {
			$target->setID('Target');
			$validator->setControlToValidate('Target');
			array_unshift($controls, $target);
		}
		$this->createFormPage(...$controls);
		return $validator;
	}

	public function testDefaults()
	{
		$validator = new TBaseValidatorTestValidator();
		$this->assertSame('red', $validator->getForeColor());
		$this->assertSame(TValidatorDisplayStyle::Fixed, $validator->getDisplay());
		$this->assertTrue($validator->getEnableClientScript());
		$this->assertSame('', $validator->getErrorMessage());
		$this->assertSame('', $validator->getControlToValidate());
		$this->assertFalse($validator->getFocusOnError());
		$this->assertSame('', $validator->getValidationGroup());
		$this->assertSame('', $validator->getControlCssClass());
		$this->assertTrue($validator->getIsValid());
	}

	public function testProperties()
	{
		$validator = new TBaseValidatorTestValidator();
		$validator->setDisplay('Dynamic');
		$this->assertSame(TValidatorDisplayStyle::Dynamic, $validator->getDisplay());
		$validator->setEnableClientScript('false');
		$this->assertFalse($validator->getEnableClientScript());
		$validator->setErrorMessage('Required');
		$this->assertSame('Required', $validator->getErrorMessage());
		$validator->setFocusOnError('true');
		$this->assertTrue($validator->getFocusOnError());
		$validator->setFocusElementID('Elsewhere');
		$this->assertSame('Elsewhere', $validator->getFocusElementID());
		$validator->setValidationGroup('Group');
		$this->assertSame('Group', $validator->getValidationGroup());
		$validator->setControlCssClass('error');
		$this->assertSame('error', $validator->getControlCssClass());
		$validator->setIsValid('false');
		$this->assertFalse($validator->getIsValid());
	}

	public function testForControlIsNotSupported()
	{
		$this->expectException(TNotSupportedException::class);
		(new TBaseValidatorTestValidator())->setForControl('Box');
	}

	public function testDisablingResetsIsValid()
	{
		$validator = new TBaseValidatorTestValidator();
		$validator->setIsValid(false);
		$validator->setEnabled(true);
		$this->assertFalse($validator->getIsValid());
		$validator->setEnabled('false');
		$this->assertTrue($validator->getIsValid());
		$this->assertFalse($validator->getEnabled());
	}

	public function testInitRegistersAndUnloadRemovesTheValidator()
	{
		$validator = $this->createValidator();
		$page = $validator->getPage();
		$validator->onInit(null);
		$this->assertSame([$validator], $page->getValidators()->toArray());

		$validator->onUnload(null);
		$this->assertSame([], $page->getValidators()->toArray());
		$validator->onUnload(null);
		$this->assertSame([], $page->getValidators()->toArray(), 'An unregistered validator removes nothing.');
	}

	public function testUnloadWithoutPage()
	{
		$validator = new TBaseValidatorTestValidator();
		PradoUnit::setProp($validator, '_registered', true);
		$validator->onUnload(null);
		$this->assertNull($validator->getPage());
		$this->assertFalse(PradoUnit::getProp($validator, '_registered'));
	}

	public function testValidationTargetRequiresAControl()
	{
		$validator = $this->createValidator();
		$this->assertSame($this->box, $validator->getValidationTarget());

		foreach (['', 'Missing'] as $id) {
			$validator->setControlToValidate($id);
			try {
				$validator->getValidationTarget();
				$this->fail('TConfigurationException expected.');
			} catch (TConfigurationException $e) {
				$this->assertSame('basevalidator_controltovalidate_invalid', $e->getErrorCode());
			}
		}
	}

	public function testValidationValueRequiresAValidatableControl()
	{
		$validator = $this->createValidator(new TLiteral());
		try {
			PradoUnit::invoke($validator, 'getValidationValue', $validator->getValidationTarget());
			$this->fail('TInvalidDataTypeException expected.');
		} catch (TInvalidDataTypeException $e) {
			$this->assertSame('basevalidator_validatable_required', $e->getErrorCode());
		}
		$this->box->setText('value');
		$this->assertSame('value', PradoUnit::invoke($validator, 'getValidationValue', $this->box));
	}

	public function testValidateRaisesTheEventsAndMarksTheTarget()
	{
		$validator = $this->createValidator();
		$events = [];
		foreach (['OnValidate', 'OnValidationSuccess', 'OnValidationError'] as $event) {
			$validator->attachEventHandler($event, function () use (&$events, $event) {
				$events[] = $event;
			});
		}

		$this->assertTrue($validator->validate());
		$this->assertSame(['OnValidate', 'OnValidationSuccess'], $events);
		$this->assertTrue($this->box->getIsValid());

		$events = [];
		$validator->result = false;
		$this->assertFalse($validator->validate());
		$this->assertSame(['OnValidate', 'OnValidationError'], $events);
		$this->assertFalse($validator->getIsValid());
		$this->assertFalse($this->box->getIsValid());
	}

	public function testValidateFailsWithATargetThatIsNotValidatable()
	{
		$validator = $this->createValidator(new TLiteral());
		$validator->result = false;
		$this->assertFalse($validator->validate());
	}

	public function testValidateSkipsAnInvisibleOrDisabledValidator()
	{
		$validator = $this->createValidator();
		$validator->result = false;
		$raised = 0;
		$validator->attachEventHandler('OnValidate', function () use (&$raised) {
			$raised++;
		});

		$validator->setVisible(false);
		$this->assertTrue($validator->validate());
		$validator->setVisible(true);
		$validator->setEnabled(false);
		$this->assertTrue($validator->validate());
		$this->assertSame(0, $validator->evaluations);
		$this->assertSame(2, $raised, 'OnValidate is raised before the visibility and enabled checks.');
	}

	public function testValidateOfADisabledTargetSucceeds()
	{
		$validator = $this->createValidator();
		$validator->result = false;
		$this->box->setEnabled(false);
		$success = 0;
		$validator->attachEventHandler('OnValidationSuccess', function () use (&$success) {
			$success++;
		});

		$this->assertTrue($validator->validate());
		$this->assertSame(1, $validator->evaluations, 'The validator still evaluates a disabled target.');
		$this->assertSame(1, $success);
		$this->assertTrue($this->box->getIsValid());
	}

	public function testFocusElementId()
	{
		$validator = $this->createValidator();
		$this->assertSame($this->box->getClientID(), $validator->getFocusElementID());

		$list = new TCheckBoxList();
		$validator = $this->createValidator($list);
		$this->assertSame($list->getClientID(), $validator->getFocusElementID(), 'An empty list focuses the list.');
		$list->getItems()->add('One');
		$this->assertSame($list->getClientID() . '_c0', $validator->getFocusElementID());
	}

	public function testClientSideIsCreatedOnce()
	{
		$validator = new TBaseValidatorTestValidator();
		$clientSide = $validator->getClientSide();
		$this->assertInstanceOf(TValidatorClientSide::class, $clientSide);
		$this->assertSame($clientSide, $validator->getClientSide());
	}

	public function testClientScriptOptions()
	{
		$validator = $this->createValidator();
		$validator->setErrorMessage('Required');
		$validator->setValidationGroup('Group');
		$validator->setControlCssClass('error');
		$options = PradoUnit::invoke($validator, 'getClientScriptOptions');
		$this->assertSame([
			'ID' => $validator->getClientID(),
			'FormID' => 'Form',
			'Display' => TValidatorDisplayStyle::Fixed,
			'ErrorMessage' => 'Required',
			'ValidationGroup' => 'Group',
			'ControlCssClass' => 'error',
			'ControlToValidate' => $this->box->getClientID(),
			'ControlType' => 'TTextBox',
			'Enabled' => true,
		], $options);

		$validator->setFocusOnError(true);
		$validator->setEncode(true);
		$validator->setIsValid(false);
		$validator->getClientSide()->setObserveChanges(false);
		$options = PradoUnit::invoke($validator, 'getClientScriptOptions');
		$this->assertTrue($options['FocusOnError']);
		$this->assertSame($this->box->getClientID(), $options['FocusElementID']);
		$this->assertTrue($options['Encode']);
		$this->assertFalse($options['IsValid']);
		$this->assertFalse($options['ObserveChanges']);
	}

	public function testClientScriptOptionsOfADatePicker()
	{
		$picker = new TDatePicker();
		$picker->setDateFormat('d/M/yyyy');
		$options = PradoUnit::invoke($this->createValidator($picker), 'getClientScriptOptions');
		$this->assertSame('TDatePicker', $options['ControlType']);
		$this->assertSame('d/M/yyyy', $options['DateFormat']);
	}

	public static function provideControlTypes(): array
	{
		return [
			'check box' => [TCheckBox::class, 'TCheckBox'],
			'check box list' => [TCheckBoxList::class, 'TCheckBoxList'],
			'radio button list, a check box list' => [TRadioButtonList::class, 'TCheckBoxList'],
			'hidden field, by its class name' => [THiddenField::class, 'THiddenField'],
		];
	}

	/**
	 * @param string $class the target class.
	 * @param string $type the expected client control type.
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('provideControlTypes')]
	public function testClientControlType(string $class, string $type)
	{
		$options = PradoUnit::invoke($this->createValidator(new $class()), 'getClientScriptOptions');
		$this->assertSame($type, $options['ControlType']);
	}

	public function testRenderRegistersTheValidationManagerOnceAndTheValidator()
	{
		$validator = $this->createValidator();
		$page = $validator->getPage();
		$second = new TBaseValidatorTestValidator();
		$second->setID('Second');
		$second->setControlToValidate('Box');
		$page->getControls()->add($second);

		$this->render($validator);
		$this->render($second);
		$this->assertTrue(isset(PradoUnit::getProp($page->getClientScript(), '_registeredScripts')['validator']));
		$this->assertSame('new Prado.ValidationManager(' . TJavaScript::encode(['FormID' => 'Form']) . ');', $this->getEndScript($page, 'TBaseValidator:Form'));
		$this->assertSame('new Prado.WebUI.TTestValidator(' . TJavaScript::encode(PradoUnit::invoke($validator, 'getClientScriptOptions')) . ');', $this->getEndScript($page, 'prado:Valid'));
		$this->assertNotNull($this->getEndScript($page, 'prado:Second'));

		$script = $this->getEndScript($page, 'prado:Valid');
		$validator->setErrorMessage('Changed');
		$this->render($validator);
		$this->assertSame($script, $this->getEndScript($page, 'prado:Valid'), 'A validator registers its script once.');
	}

	public function testRenderWithoutClientScriptRegistersNothing()
	{
		$validator = $this->createValidator();
		$validator->setEnableClientScript(false);
		$page = $validator->getPage();
		$this->render($validator);
		$this->assertFalse(isset(PradoUnit::getProp($page->getClientScript(), '_registeredScripts')['validator']));
		$this->assertNull($this->getEndScript($page, 'TBaseValidator:Form'));
		$this->assertNull($this->getEndScript($page, 'prado:Valid'));
	}

	public static function provideDisplay(): array
	{
		return [
			'fixed, valid' => [TValidatorDisplayStyle::Fixed, true, true, 'visibility:hidden'],
			'fixed, invalid' => [TValidatorDisplayStyle::Fixed, false, true, null],
			'fixed, invalid and disabled' => [TValidatorDisplayStyle::Fixed, false, false, 'visibility:hidden'],
			'dynamic, valid' => [TValidatorDisplayStyle::Dynamic, true, true, 'display:none'],
			'dynamic, invalid' => [TValidatorDisplayStyle::Dynamic, false, true, null],
			'none, invalid' => [TValidatorDisplayStyle::None, false, true, 'display:none'],
		];
	}

	/**
	 * @param string $display the display style.
	 * @param bool $isValid whether the validator is valid.
	 * @param bool $enabled whether the validator is enabled.
	 * @param ?string $style the expected hiding style, or null when the message shows.
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('provideDisplay')]
	public function testRenderHidesAValidMessage(string $display, bool $isValid, bool $enabled, ?string $style)
	{
		$validator = $this->createValidator();
		$validator->setEnableClientScript(false);
		$validator->setDisplay($display);
		$validator->setEnabled($enabled);
		$validator->setIsValid($isValid);
		$html = $this->renderBeginTag($validator);
		$this->assertStringContainsString('id="Valid"', $html);
		$this->assertStringContainsString('role="alert"', $html);
		if ($style === null) {
			$this->assertStringNotContainsString('display:none', $html);
			$this->assertStringNotContainsString('visibility:hidden', $html);
		} else {
			$this->assertStringContainsString($style, $html);
		}
	}

	public function testRenderContents()
	{
		$validator = $this->createValidator();
		$validator->setErrorMessage('<b>Required</b>');
		$this->assertSame('<b>Required</b>', $this->renderContents($validator));
		$validator->setEncode(true);
		$this->assertSame('&lt;b&gt;Required&lt;/b&gt;', $this->renderContents($validator));
		$validator->setText('*');
		$this->assertSame('*', $this->renderContents($validator), 'Text replaces the error message.');

		$validator->setText('');
		$validator->setErrorMessage('');
		$literal = new TLiteral();
		$literal->setText('child');
		$validator->getControls()->add($literal);
		$this->assertSame('child', $this->renderContents($validator), 'Without a text, the child controls render.');
	}

	public static function provideControlCssClass(): array
	{
		return [
			'invalid adds the class' => ['input', false, true, 'input error'],
			'invalid adds the class once' => ['error input', false, true, 'input error'],
			'valid removes the class' => ['input error  other', true, true, 'input other'],
			'valid keeps the class of an invalid target' => ['input error', true, false, 'input error'],
		];
	}

	/**
	 * @param string $cssClass the target's CSS class.
	 * @param bool $isValid whether the validator is valid.
	 * @param bool $targetValid whether the target is valid.
	 * @param string $expected the target's CSS class after pre-rendering.
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('provideControlCssClass')]
	public function testPreRenderUpdatesTheControlCssClass(string $cssClass, bool $isValid, bool $targetValid, string $expected)
	{
		$validator = $this->createValidator();
		$validator->setControlCssClass('error');
		$this->box->setCssClass($cssClass);
		$this->box->setIsValid($targetValid);
		$validator->setIsValid($isValid);
		$validator->onPreRender(null);
		$this->assertSame($expected, $this->box->getCssClass());
	}

	public function testPreRenderWithoutControlCssClassKeepsTheClass()
	{
		$validator = $this->createValidator();
		$this->box->setCssClass('input');
		$validator->setIsValid(false);
		$validator->onPreRender(null);
		$this->assertSame('input', $this->box->getCssClass());
	}

	public function testPreRenderIgnoresATargetThatIsNotAWebControl()
	{
		$validator = $this->createValidator(new THiddenField());
		$validator->setControlCssClass('error');
		$validator->setIsValid(false);
		$validator->onPreRender(null);
		$this->assertFalse($validator->getIsValid());
	}

	public function testPreRenderOfAValidValidatorKeepsTheClassOfATargetThatIsNotValidatable()
	{
		$label = new \Prado\Web\UI\WebControls\TLabel();
		$validator = $this->createValidator($label);
		$validator->setControlCssClass('error');
		$label->setCssClass('label error');
		$validator->onPreRender(null);
		$this->assertSame('label error', $label->getCssClass());
		$validator->setIsValid(false);
		$validator->onPreRender(null);
		$this->assertSame('label error', $label->getCssClass());
	}
}
