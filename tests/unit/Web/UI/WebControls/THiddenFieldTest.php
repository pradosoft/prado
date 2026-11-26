<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TNotSupportedException;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\THiddenField;

class THiddenFieldTest extends TestCase
{
	use TWebControlRenderTrait;

	private function createField(string $id = 'token'): THiddenField
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_inFormRender', true);
		$field = new THiddenField();
		if ($id !== '') {
			$field->setID($id);
		}
		$page->getControls()->add($field);
		return $field;
	}

	public function testValueIsTheDataAndValidationValue()
	{
		$field = new THiddenField();
		$this->assertSame('', $field->getValue());
		$field->setData('abc');
		$this->assertSame('abc', $field->getValue());
		$this->assertSame('abc', $field->getData());
		$this->assertSame('abc', $field->getValidationPropertyValue());
		$this->assertTrue($field->getIsValid());
		$field->setIsValid('false');
		$this->assertFalse($field->getIsValid());
		$this->assertSame('input', PradoUnit::invoke($field, 'getTagName'));
	}

	public function testRenderWritesNameIdValueAndAttributes()
	{
		$field = $this->createField();
		$field->setValue('a"b');
		$field->getAttributes()->add('data-x', '1');
		$this->assertSame('<input type="hidden" name="token" id="token" value="a&quot;b" data-x="1" />', $this->render($field));
	}

	public function testRenderWithoutIdOrValue()
	{
		$field = $this->createField('');
		$this->assertMatchesRegularExpression('/^<input type="hidden" name="ctl\d+" \/>$/', $this->render($field));
	}

	public function testRenderWithoutUniqueId()
	{
		$field = new THiddenField();
		$page = new TPage();
		PradoUnit::setProp($page, '_inFormRender', true);
		$field->setPage($page);
		$this->assertSame('<input type="hidden" />', $this->render($field));
	}

	public function testRenderOutsideTheFormThrows()
	{
		$field = new THiddenField();
		$page = new TPage();
		$page->getControls()->add($field);
		$this->expectException(TConfigurationException::class);
		$this->render($field);
	}

	public function testLoadPostDataChangesTheValue()
	{
		$field = new THiddenField();
		$this->assertTrue($field->loadPostData('token', ['token' => 'abc']));
		$this->assertSame('abc', $field->getValue());
		$this->assertTrue($field->getDataChanged());
		$this->assertFalse($field->loadPostData('token', ['token' => 'abc']));
	}

	public function testUnchangedPostDataIsNotAChange()
	{
		$field = new THiddenField();
		$field->setValue('abc');
		$this->assertFalse($field->loadPostData('token', ['token' => 'abc']));
		$this->assertFalse($field->getDataChanged());
	}

	public function testRaisePostDataChangedEventRaisesOnValueChanged()
	{
		$field = new THiddenField();
		$raised = [];
		$field->attachEventHandler('OnValueChanged', function ($sender, $param) use (&$raised) {
			$raised[] = [$sender, $param];
		});
		$field->raisePostDataChangedEvent();
		$this->assertSame([[$field, null]], $raised);
	}

	public function testThemingIsDisabled()
	{
		$this->assertFalse((new THiddenField())->getEnableTheming());
	}

	public static function unsupportedProvider(): array
	{
		return [
			'focus' => [fn (THiddenField $field) => $field->focus(), 'hiddenfield_focus_unsupported'],
			'EnableTheming' => [fn (THiddenField $field) => $field->setEnableTheming(true), 'hiddenfield_theming_unsupported'],
			'SkinID' => [fn (THiddenField $field) => $field->setSkinID('skin'), 'hiddenfield_skinid_unsupported'],
		];
	}

	/**
	 * @dataProvider unsupportedProvider
	 */
	public function testUnsupportedOperationsThrow(\Closure $operation, string $code)
	{
		try {
			$operation(new THiddenField());
			$this->fail('Expected TNotSupportedException');
		} catch (TNotSupportedException $e) {
			$this->assertSame($code, $e->getErrorCode());
		}
	}

	public function testLoadPostDataIgnoresAnArrayValue()
	{
		$field = new THiddenField();
		$field->setValue('abc');
		$this->assertFalse($field->loadPostData('token', ['token' => ['x']]));
		$this->assertSame('abc', $field->getValue());
		$this->assertFalse($field->getDataChanged());
	}
}
