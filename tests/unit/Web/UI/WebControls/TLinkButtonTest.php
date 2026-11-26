<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Collections\TMap;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TCommandEventParameter;
use Prado\Web\UI\TControl;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TLinkButton;
use Prado\Web\UI\WebControls\TLiteral;
use Prado\Web\UI\WebControls\TPanel;

class TLinkButtonTestBubbleParent extends TControl
{
	public array $bubbled = [];

	public function bubbleEvent($sender, $param)
	{
		$this->bubbled[] = [$sender, $param];
		return true;
	}
}

class TLinkButtonTest extends TestCase
{
	use TWebControlRenderTrait;

	private function createButton(?TControl $parent = null): array
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_inFormRender', true);
		$cs = $this->getMockBuilder(TClientScriptManager::class)->setConstructorArgs([$page])->onlyMethods(['registerPostBackControl'])->getMock();
		PradoUnit::setProp($page, '_clientScript', $cs);
		$button = new TLinkButton();
		$button->setID('go');
		($parent ?? $page)->getControls()->add($button);
		if ($parent !== null) {
			$page->getControls()->add($parent);
		}
		return [$button, $cs];
	}

	public function testRenderContentsEncodesTextWhenEncode()
	{
		$button = new TLinkButton();
		$this->assertFalse($button->getEncode());
		$button->setText('<b>a & b</b>');
		$this->assertSame('<b>a & b</b>', $this->renderContents($button));

		$button->setEncode(true);
		$this->assertTrue($button->getEncode());
		$this->assertSame('&lt;b&gt;a & b&lt;/b&gt;', $this->renderContents($button));
	}

	public function testRenderContentsRendersTheChildrenWithoutText()
	{
		$button = new TLinkButton();
		$literal = new TLiteral();
		$literal->setText('<img src="go.png" />');
		$button->getControls()->add($literal);
		$this->assertSame('<img src="go.png" />', $this->renderContents($button));
	}

	public function testPropertyDefaultsAndSetters()
	{
		$button = new TLinkButton();
		$this->assertTrue($button->getEnableClientScript());
		$this->assertFalse($button->getIsDefaultButton());
		$this->assertSame('', $button->getText());
		$this->assertSame('', $button->getCommandName());
		$this->assertSame('', $button->getCommandParameter());
		$this->assertTrue($button->getCausesValidation());
		$this->assertSame('', $button->getValidationGroup());
		$this->assertSame('a', PradoUnit::invoke($button, 'getTagName'));
		$this->assertSame('Prado.WebUI.TLinkButton', PradoUnit::invoke($button, 'getClientClassName'));

		$button->setEnableClientScript('false');
		$button->setIsDefaultButton('true');
		$button->setData('Go');
		$button->setCommandName('Sort');
		$button->setCommandParameter('Name');
		$button->setCausesValidation('false');
		$button->setValidationGroup('Group');
		$this->assertFalse($button->getEnableClientScript());
		$this->assertTrue($button->getIsDefaultButton());
		$this->assertSame('Go', $button->getText());
		$this->assertSame('Go', $button->getData());
		$this->assertSame('Sort', $button->getCommandName());
		$this->assertSame('Name', $button->getCommandParameter());
		$this->assertFalse($button->getCausesValidation());
		$this->assertSame('Group', $button->getValidationGroup());
	}

	public function testRenderRegistersThePostBackControl()
	{
		[$button, $cs] = $this->createButton();
		$button->setText('Go');
		$button->setValidationGroup('Group');
		$cs->expects($this->once())->method('registerPostBackControl')->with('Prado.WebUI.TLinkButton', [
			'ID' => 'go',
			'EventTarget' => 'go',
			'CausesValidation' => true,
			'ValidationGroup' => 'Group',
			'StopEvent' => true,
		]);
		$this->assertSame('<a id="go" href="javascript:;//go">Go</a>', $this->render($button));
	}

	public function testDisabledParentRendersDisabled()
	{
		[$button, $cs] = $this->createButton(new TPanel());
		$button->getParent()->setEnabled(false);
		$cs->expects($this->never())->method('registerPostBackControl');
		$this->assertSame('<a id="go" role="link" aria-disabled="true"></a>', $this->render($button));
	}

	public function testDisabledRendersDisabledOnce()
	{
		[$button, $cs] = $this->createButton();
		$button->setEnabled(false);
		$cs->expects($this->never())->method('registerPostBackControl');
		$this->assertSame('<a id="go" role="link" aria-disabled="true"></a>', $this->render($button));
	}

	public function testWithoutClientScriptRendersASubmitButton()
	{
		[$button, $cs] = $this->createButton();
		$button->setEnableClientScript(false);
		$button->setText('Go');
		$button->setCssClass('link');
		$cs->expects($this->never())->method('registerPostBackControl');
		$this->assertSame('<button id="go" class="link" type="submit" name="go">Go</button>', $this->render($button));
	}

	public function testWithoutClientScriptDisabledRendersAnInertAnchor()
	{
		[$button, $cs] = $this->createButton();
		$button->setEnableClientScript(false);
		$button->setEnabled(false);
		$cs->expects($this->never())->method('registerPostBackControl');
		$this->assertSame('<a id="go" role="link" aria-disabled="true"></a>', $this->render($button));
	}

	public function testThePostedSubmitButtonNameIsThePostBackTarget()
	{
		[$button] = $this->createButton();
		$page = $button->getPage();
		$postData = new TMap([$button->getUniqueID() => '']);
		PradoUnit::setProp($page, '_postData', $postData);

		PradoUnit::invoke($page, 'processPostData', $postData, true);
		$this->assertSame($button->getUniqueID(), $postData[TPage::FIELD_POSTBACK_TARGET]);
	}

	public function testDisabledKeepsItsRole()
	{
		[$button] = $this->createButton();
		$button->setEnabled(false);
		$button->setRole('button');
		$this->assertSame('<a id="go" role="button" aria-disabled="true"></a>', $this->render($button));
	}

	public function testRaisePostBackEventValidatesAndRaisesClickAndCommand()
	{
		$page = $this->createMock(TPage::class);
		$page->expects($this->once())->method('validate')->with('Group');
		$button = new TLinkButton();
		$button->setPage($page);
		$button->setValidationGroup('Group');
		$button->setCommandName('Sort');
		$button->setCommandParameter('Name');
		$events = [];
		$button->attachEventHandler('OnClick', function ($sender, $param) use (&$events) {
			$events[] = ['click', $param];
		});
		$button->attachEventHandler('OnCommand', function ($sender, $param) use (&$events) {
			$events[] = ['command', $param->getCommandName(), $param->getCommandParameter()];
		});
		$button->raisePostBackEvent('');
		$this->assertSame([['click', null], ['command', 'Sort', 'Name']], $events);
	}

	public function testRaisePostBackEventWithoutValidation()
	{
		$page = $this->createMock(TPage::class);
		$page->expects($this->never())->method('validate');
		$button = new TLinkButton();
		$button->setPage($page);
		$button->setCausesValidation(false);
		$button->raisePostBackEvent('');
	}

	public function testCommandBubblesToTheParent()
	{
		$parent = new TLinkButtonTestBubbleParent();
		$button = new TLinkButton();
		$parent->getControls()->add($button);
		$param = new TCommandEventParameter('Sort', 'Name');
		$button->onCommand($param);
		$this->assertSame([[$button, $param]], $parent->bubbled);
	}
}
