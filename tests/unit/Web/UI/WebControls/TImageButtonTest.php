<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Collections\TList;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TCommandEventParameter;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TImageClickEventParameter;
use Prado\Web\UI\WebControls\TPanel;
use Prado\Web\UI\WebControls\TImageButton;
use Prado\Web\UI\WebControls\TLoadingMode;

class TImageButtonTestBubbleParent extends \Prado\Web\UI\TControl
{
	public array $bubbled = [];

	public function bubbleEvent($sender, $param)
	{
		$this->bubbled[] = [$sender, $param];
		return true;
	}
}

class TImageButtonTest extends TestCase
{
	use TWebControlRenderTrait;

	private function createButton(): TImageButton
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_inFormRender', true);
		$button = new TImageButton();
		$button->setID('go');
		$button->setImageUrl('go.png');
		$page->getControls()->add($button);
		return $button;
	}

	public function testFormOverridesRender()
	{
		$button = $this->createButton();
		$button->setFormAction('/search');
		$button->setFormEncType('text/plain');
		$button->setFormTarget('results');
		$output = $this->render($button);
		$this->assertStringContainsString('type="image"', $output);
		$this->assertStringContainsString('formaction="/search"', $output);
		$this->assertStringContainsString('formenctype="text/plain"', $output);
		$this->assertStringContainsString('formtarget="results"', $output);
		$this->assertStringNotContainsString('formnovalidate', $output);
	}

	public function testNoValidationRendersFormNoValidate()
	{
		$button = $this->createButton();
		$button->setCausesValidation(false);
		$this->assertStringContainsString('formnovalidate="formnovalidate"', $this->render($button));
	}

	public function testRendersOnlyIntrinsicSizeOfImageLoadingProperties()
	{
		$button = $this->createButton();
		$button->setIntrinsicWidth(24);
		$button->setIntrinsicHeight(24);
		$button->setLoading(TLoadingMode::Lazy);
		$button->setSrcset('go@2x.png 2x');
		$output = $this->render($button);
		$this->assertStringContainsString('width="24" height="24"', $output);
		$this->assertStringNotContainsString('loading', $output);
		$this->assertStringNotContainsString('srcset', $output);
	}

	/**
	 * Returns a page whose validator count for any group is $validators, with a client script manager
	 * that records registerPostBackControl().
	 */
	private function createScriptedPage(int $validators = 0): array
	{
		$page = $this->getMockBuilder(TPage::class)->onlyMethods(['getValidators'])->getMock();
		$page->method('getValidators')->willReturn(new TList(array_fill(0, $validators, 'v')));
		PradoUnit::setProp($page, '_inFormRender', true);
		$cs = $this->getMockBuilder(TClientScriptManager::class)->setConstructorArgs([$page])->onlyMethods(['registerPostBackControl'])->getMock();
		PradoUnit::setProp($page, '_clientScript', $cs);
		return [$page, $cs];
	}

	private function createScriptedButton(int $validators = 0): array
	{
		[$page, $cs] = $this->createScriptedPage($validators);
		$button = new TImageButton();
		$button->setID('go');
		$page->getControls()->add($button);
		return [$button, $cs];
	}

	public function testPropertyDefaultsAndSetters()
	{
		$button = new TImageButton();
		$this->assertTrue($button->getEnableClientScript());
		$this->assertFalse($button->getIsDefaultButton());
		$this->assertTrue($button->getCausesValidation());
		$this->assertSame('', $button->getCommandName());
		$this->assertSame('', $button->getCommandParameter());
		$this->assertSame('', $button->getValidationGroup());
		$this->assertSame('Prado.WebUI.TImageButton', PradoUnit::invoke($button, 'getClientClassName'));

		$button->setEnableClientScript('false');
		$button->setIsDefaultButton('true');
		$button->setCommandName('Sort');
		$button->setCommandParameter('Name');
		$button->setValidationGroup('Group');
		$button->setText('Go');
		$this->assertFalse($button->getEnableClientScript());
		$this->assertTrue($button->getIsDefaultButton());
		$this->assertSame('Sort', $button->getCommandName());
		$this->assertSame('Name', $button->getCommandParameter());
		$this->assertSame('Group', $button->getValidationGroup());
		$this->assertSame('Go', $button->getText());
		$this->assertSame('Go', $button->getAlternateText());
	}

	public function testRenderHasNoContents()
	{
		$button = $this->createButton();
		$button->setText('Go');
		$this->assertSame('<input type="image" name="go" src="go.png" alt="Go" id="go" />', $this->render($button));
	}

	public function testDisabledParentRendersDisabled()
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_inFormRender', true);
		$panel = new TPanel();
		$panel->setEnabled(false);
		$page->getControls()->add($panel);
		$button = new TImageButton();
		$panel->getControls()->add($button);
		$this->assertStringContainsString('disabled="disabled"', $this->render($button));
	}

	public function testValidatorsRegisterThePostBackControl()
	{
		[$button, $cs] = $this->createScriptedButton(1);
		$button->setValidationGroup('Group');
		$cs->expects($this->once())->method('registerPostBackControl')->with('Prado.WebUI.TImageButton', [
			'ID' => 'go',
			'CausesValidation' => true,
			'ValidationGroup' => 'Group',
			'EventTarget' => 'go',
		]);
		$this->assertStringContainsString('id="go"', $this->render($button));
	}

	public function testDefaultButtonRegistersThePostBackControl()
	{
		[$button, $cs] = $this->createScriptedButton();
		$button->setIsDefaultButton(true);
		$cs->expects($this->once())->method('registerPostBackControl');
		$this->render($button);
	}

	public static function noPostBackScriptProvider(): array
	{
		// validators, causesValidation, enableClientScript
		return [
			'no validators' => [0, true, true],
			'no validation' => [1, false, true],
			'client script off' => [1, true, false],
		];
	}

	/**
	 * @dataProvider noPostBackScriptProvider
	 */
	public function testNoPostBackScript(int $validators, bool $causesValidation, bool $enableClientScript)
	{
		[$button, $cs] = $this->createScriptedButton($validators);
		$button->setCausesValidation($causesValidation);
		$button->setEnableClientScript($enableClientScript);
		$cs->expects($this->never())->method('registerPostBackControl');
		$this->render($button);
	}

	public function testLoadPostDataTargetsTheButtonWithBothCoordinates()
	{
		$button = $this->createButton();
		$page = $button->getPage();
		$this->assertFalse($button->loadPostData('go', ['go_x' => '12', 'go_y' => '34']));
		$this->assertTrue($button->getDataChanged());
		$this->assertSame($button, $page->getPostBackEventTarget());
		$button->raisePostDataChangedEvent();
	}

	public function testLoadPostDataKeepsAnotherTarget()
	{
		$button = $this->createButton();
		$page = $button->getPage();
		$other = new TImageButton();
		$page->setPostBackEventTarget($other);
		$button->loadPostData('go', ['go_x' => '1', 'go_y' => '2']);
		$this->assertSame($other, $page->getPostBackEventTarget());
	}

	public function testLoadPostDataNeedsBothCoordinates()
	{
		$button = $this->createButton();
		$this->assertFalse($button->loadPostData('go', ['go_x' => '1']));
		$this->assertFalse($button->getDataChanged());
		$this->assertNull($button->getPage()->getPostBackEventTarget());
	}

	public function testRaisePostBackEventValidatesAndRaisesClickAndCommand()
	{
		$page = $this->createMock(TPage::class);
		$page->expects($this->once())->method('validate')->with('Group');
		$button = new TImageButton();
		$button->setPage($page);
		$button->setValidationGroup('Group');
		$button->setCommandName('Sort');
		$button->setCommandParameter('Name');
		PradoUnit::setProp($button, '_x', 5);
		PradoUnit::setProp($button, '_y', 7);
		$events = [];
		$button->attachEventHandler('OnClick', function ($sender, $param) use (&$events) {
			$events[] = ['click', $param->getX(), $param->getY()];
		});
		$button->attachEventHandler('OnCommand', function ($sender, $param) use (&$events) {
			$events[] = ['command', $param->getCommandName(), $param->getCommandParameter()];
		});
		$button->raisePostBackEvent('');
		$this->assertSame([['click', 5, 7], ['command', 'Sort', 'Name']], $events);
	}

	public function testRaisePostBackEventWithoutValidation()
	{
		$page = $this->createMock(TPage::class);
		$page->expects($this->never())->method('validate');
		$button = new TImageButton();
		$button->setPage($page);
		$button->setCausesValidation(false);
		$button->raisePostBackEvent('');
	}

	public function testCommandBubblesToTheParent()
	{
		$parent = new TImageButtonTestBubbleParent();
		$button = new TImageButton();
		$parent->getControls()->add($button);
		$param = new TCommandEventParameter('Sort', 'Name');
		$button->onCommand($param);
		$this->assertSame([[$button, $param]], $parent->bubbled);
	}

	public function testOnPreRenderRequiresPostData()
	{
		$button = $this->createButton();
		$button->onPreRender(null);
		$this->assertArrayHasKey('go', PradoUnit::getProp($button->getPage(), '_controlsRegisteredForPostData'));
	}

	public function testImageClickEventParameter()
	{
		$param = new TImageClickEventParameter(3, 4);
		$this->assertSame(3, $param->getX());
		$this->assertSame(4, $param->getY());
		$param->setX('8');
		$param->setY('9');
		$this->assertSame(8, $param->getX());
		$this->assertSame(9, $param->getY());
	}

	public function testLoadPostDataWithoutTheXCoordinate()
	{
		$button = $this->createButton();
		$this->assertFalse($button->loadPostData('go', ['go_y' => '1']));
		$this->assertFalse($button->getDataChanged());
	}

	public function testDisabledRendersDisabledFromTheParentImplementation()
	{
		[$button, $cs] = $this->createScriptedButton(1);
		$button->setEnabled(false);
		$cs->expects($this->never())->method('registerPostBackControl');
		$this->assertSame(1, substr_count($this->render($button), 'disabled="disabled"'));
	}

	public static function withoutUniqueIdProvider(): array
	{
		// validators, enableClientScript, parentDisabled => registers, disabled
		return [
			'registers' => [1, true, false, true, false],
			'not needed' => [0, true, false, false, false],
			'client script off' => [1, false, false, false, false],
			'parent disabled' => [1, true, true, false, true],
		];
	}

	public function testDisabledWithoutUniqueId()
	{
		[$page, $cs] = $this->createScriptedPage(1);
		$button = new TImageButton();
		$button->setPage($page);
		$button->setEnabled(false);
		$cs->expects($this->never())->method('registerPostBackControl');
		$this->assertSame(1, substr_count($this->render($button), 'disabled="disabled"'));
	}

	/**
	 * @dataProvider withoutUniqueIdProvider
	 */
	public function testRenderWithoutUniqueId(int $validators, bool $clientScript, bool $parentDisabled, bool $registers, bool $disabled)
	{
		[$page, $cs] = $this->createScriptedPage($validators);
		$button = new TImageButton();
		if ($parentDisabled) {
			$panel = new TPanel();
			$panel->setEnabled(false);
			$panel->getControls()->add($button);
		}
		$button->setPage($page);
		$button->setEnableClientScript($clientScript);
		$cs->expects($registers ? $this->once() : $this->never())->method('registerPostBackControl');
		$output = $this->render($button);
		$this->assertStringNotContainsString('name=', $output);
		$this->assertSame($disabled, str_contains($output, 'disabled="disabled"'));
	}
}
