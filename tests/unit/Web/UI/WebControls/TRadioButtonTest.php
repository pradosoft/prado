<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\IO\TTextWriter;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TPanel;
use Prado\Web\UI\WebControls\TRadioButton;
use Prado\Web\UI\WebControls\TRadioButtonList;

class TRadioButtonTest extends TestCase
{
	private array $_savedStatics;

	protected function setUp(): void
	{
		parent::setUp();
		$this->_savedStatics = PradoUnit::snapshotStatic(TRadioButton::class);
		PradoUnit::setStaticProp(TRadioButton::class, '_activeButtons', []);
	}

	protected function tearDown(): void
	{
		PradoUnit::restoreStatic(TRadioButton::class, $this->_savedStatics);
		parent::tearDown();
	}

	private function createRadio(string $id = 'rb', ?\Prado\Web\UI\TControl $parent = null): TRadioButton
	{
		if ($parent === null) {
			$parent = new TPage();
		}
		$radio = new TRadioButton();
		$radio->setID($id);
		$parent->getControls()->add($radio);
		return $radio;
	}

	private function renderInput(TRadioButton $radio, string $clientID = 'rb', string $onclick = ''): string
	{
		$tw = new TTextWriter();
		PradoUnit::invoke($radio, 'renderInputTag', new THtmlWriter($tw), $clientID, $onclick);
		return $tw->flush();
	}

	public function testEachButtonHasItsOwnGlobalId()
	{
		$first = new TRadioButton();
		$second = new TRadioButton();
		$this->assertSame(PradoUnit::getProp($first, '_globalID') + 1, PradoUnit::getProp($second, '_globalID'));
	}

	public function testInitAndUnloadTrackTheActiveButtons()
	{
		$radio = $this->createRadio();
		$radio->onInit(null);
		$this->assertSame([$radio], array_values(PradoUnit::getStaticProp(TRadioButton::class, '_activeButtons')));
		$radio->onUnLoad(null);
		$this->assertSame([], PradoUnit::getStaticProp(TRadioButton::class, '_activeButtons'));
	}

	public function testRadioButtonsInGroupListsTheActiveButtonsOfTheGroup()
	{
		$page = new TPage();
		$a = $this->createRadio('a', $page);
		$b = $this->createRadio('b', $page);
		$c = $this->createRadio('c', $page);
		$a->setGroupName('g');
		$b->setGroupName('g');
		$c->setGroupName('h');
		foreach ([$a, $b, $c] as $radio) {
			$radio->onInit(null);
		}
		$this->assertSame([$a, $b], $a->getRadioButtonsInGroup());
		$this->assertSame([$c], $c->getRadioButtonsInGroup());
	}

	public function testPropertyDefaultsAndSetters()
	{
		$radio = new TRadioButton();
		$this->assertSame('', $radio->getGroupName());
		$this->assertTrue($radio->getEnableClientScript());
		$this->assertSame('Prado.WebUI.TRadioButton', PradoUnit::invoke($radio, 'getClientClassName'));
		$radio->setGroupName('g');
		$radio->setEnableClientScript('false');
		$this->assertSame('g', $radio->getGroupName());
		$this->assertFalse($radio->getEnableClientScript());
	}

	// --- UniqueGroupName ---

	public function testUniqueGroupNameIsTheUniqueIdWithoutAGroupName()
	{
		$this->assertSame('rb', $this->createRadio()->getUniqueGroupName());
	}

	public function testUniqueGroupNameIsEmptyWithoutAUniqueId()
	{
		$radio = new TRadioButton();
		$this->assertSame('', $radio->getUniqueGroupName());
		$radio->setGroupName('g');
		$this->assertSame('g', $radio->getUniqueGroupName());
	}

	public function testUniqueGroupNameIsTheGroupNameAtTopLevel()
	{
		$radio = $this->createRadio();
		$radio->setGroupName('g');
		$this->assertSame('g', $radio->getUniqueGroupName());
	}

	public function testUniqueGroupNamePrefixesTheNamingContainer()
	{
		$page = new TPage();
		$list = new TRadioButtonList();
		$list->setID('list');
		$page->getControls()->add($list);
		$panel = new \Prado\Web\UI\TTemplateControl();
		$panel->setID('tc');
		$page->getControls()->add($panel);
		$radio = $this->createRadio('rb', $panel);
		$this->assertSame('tc:rb', $radio->getUniqueGroupName());
		$radio->setGroupName('g');
		$this->assertSame('tc:g', $radio->getUniqueGroupName());
	}

	public function testUniqueGroupNameInARadioButtonListIsTheList()
	{
		$page = new TPage();
		$list = new TRadioButtonList();
		$list->setID('list');
		$page->getControls()->add($list);
		$radio = $this->createRadio('rb', $list);
		$this->assertSame('list', $radio->getUniqueGroupName());
	}

	public function testUniqueGroupNameFollowsAChangedUniqueId()
	{
		$radio = $this->createRadio();
		$this->assertSame('rb', $radio->getUniqueGroupName());
		$this->assertSame('rb', $radio->getUniqueGroupName());
		$radio->setID('other');
		$this->assertSame('other', $radio->getUniqueGroupName());
	}

	public function testSetUniqueGroupNameOverrides()
	{
		$radio = $this->createRadio();
		$radio->setUniqueGroupName('fixed');
		$this->assertSame('fixed', $radio->getUniqueGroupName());
	}

	// --- Post data ---

	public function testLoadPostDataChecksTheButtonWithTheValue()
	{
		$radio = $this->createRadio();
		$radio->setValue('one');
		$this->assertTrue($radio->loadPostData('rb', ['rb' => 'one']));
		$this->assertTrue($radio->getChecked());
		$this->assertFalse($radio->loadPostData('rb', ['rb' => 'one']));
		$this->assertTrue($radio->getChecked());
	}

	public function testLoadPostDataUnchecksForAnotherValue()
	{
		$radio = $this->createRadio();
		$radio->setValue('one');
		$radio->setChecked(true);
		$this->assertFalse($radio->loadPostData('rb', ['rb' => 'two']));
		$this->assertFalse($radio->getChecked());
	}

	public function testLoadPostDataWithoutTheGroupLeavesAnUncheckedButton()
	{
		$radio = $this->createRadio();
		$this->assertFalse($radio->loadPostData('rb', []));
		$this->assertFalse($radio->getChecked());
	}

	public function testValueAttributeDefaultsToTheUniqueId()
	{
		$radio = $this->createRadio();
		$this->assertSame('rb', PradoUnit::invoke($radio, 'getValueAttribute'));
		$radio->setValue('one');
		$this->assertSame('one', PradoUnit::invoke($radio, 'getValueAttribute'));
	}

	// --- Rendering ---

	public function testRenderInputTag()
	{
		$radio = $this->createRadio();
		$radio->setGroupName('g');
		$radio->setValue('one');
		$radio->setChecked(true);
		$radio->setEnableClientScript(false);
		$this->assertSame('<input id="rb" type="radio" name="g" value="one" onclick="go()" checked="checked" />', $this->renderInput($radio, 'rb', 'go()'));
	}

	public function testRenderInputTagWithoutClientId()
	{
		$radio = $this->createRadio();
		$this->assertSame('<input type="radio" name="rb" value="rb" />', $this->renderInput($radio, ''));
	}

	public function testRenderInputTagRendersDisabledAccessKeyTabIndexAndInputAttributes()
	{
		$page = new TPage();
		$panel = new TPanel();
		$panel->setEnabled(false);
		$page->getControls()->add($panel);
		$radio = $this->createRadio('rb', $panel);
		$radio->setAccessKey('a');
		$radio->setTabIndex(2);
		$radio->getInputAttributes()->add('data-x', '1');
		$this->assertSame('<input id="rb" type="radio" name="rb" value="rb" disabled="disabled" accesskey="a" tabindex="2" data-x="1" />', $this->renderInput($radio));
	}

	private function mockClientScript(TPage $page): TClientScriptManager
	{
		$cs = $this->getMockBuilder(TClientScriptManager::class)->setConstructorArgs([$page])->onlyMethods(['registerPostBackControl'])->getMock();
		PradoUnit::setProp($page, '_clientScript', $cs);
		return $cs;
	}

	public function testAutoPostBackRegistersThePostBackControl()
	{
		$radio = $this->createRadio();
		$radio->setAutoPostBack(true);
		$this->mockClientScript($radio->getPage())->expects($this->once())->method('registerPostBackControl')->with('Prado.WebUI.TRadioButton', [
			'ID' => 'rb',
			'ValidationGroup' => '',
			'CausesValidation' => true,
			'EventTarget' => 'rb',
		]);
		$this->renderInput($radio);
	}

	public static function clientScriptProvider(): array
	{
		// enabled, enableClientScript, autoPostBack, javascript
		return [
			'no auto postback' => [true, true, false, true],
			'disabled' => [false, true, true, true],
			'client script off' => [true, false, true, true],
			'no javascript' => [true, true, true, false],
		];
	}

	/**
	 * @dataProvider clientScriptProvider
	 */
	public function testPostBackControlConditions(bool $enabled, bool $clientScript, bool $autoPostBack, bool $javascript)
	{
		$radio = $this->createRadio();
		$radio->setEnabled($enabled);
		$radio->setEnableClientScript($clientScript);
		$radio->setAutoPostBack($autoPostBack);
		$radio->getPage()->setClientSupportsJavaScript($javascript);
		$this->mockClientScript($radio->getPage())->expects($this->never())->method('registerPostBackControl');
		$this->renderInput($radio);
	}

	public function testRadioButtonsInGroupWithoutActiveButtons()
	{
		$this->assertSame([], $this->createRadio()->getRadioButtonsInGroup());
	}

	public function testRadioButtonsInGroupWithOneActiveButton()
	{
		$radio = $this->createRadio();
		$radio->onInit(null);
		$this->assertSame([$radio], $radio->getRadioButtonsInGroup());
		$other = $this->createRadio('other');
		$this->assertSame([], $other->getRadioButtonsInGroup());
	}

	public function testLoadPostDataForAnotherValueLeavesAnUncheckedButton()
	{
		$radio = $this->createRadio();
		$radio->setValue('one');
		$this->assertFalse($radio->loadPostData('rb', ['rb' => 'two']));
		$this->assertFalse($radio->getChecked());
	}

	public function testLoadPostDataWithoutTheGroupUnchecksTheButton()
	{
		$radio = $this->createRadio();
		$radio->setChecked(true);
		$this->assertFalse($radio->loadPostData('rb', []));
		$this->assertFalse($radio->getChecked());
	}
}
