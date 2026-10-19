<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TNotSupportedException;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\TEventParameter;
use Prado\Web\UI\IPostBackEventHandler;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TBulletedList;
use Prado\Web\UI\WebControls\TBulletedListDisplayMode;
use Prado\Web\UI\WebControls\TBulletedListEventParameter;
use Prado\Web\UI\WebControls\TBulletStyle;
use Prado\Web\UI\WebControls\TListItem;
use Prado\Web\UI\WebControls\TRequiredFieldValidator;

/**
 * Client script manager that records postback control registrations.
 */
class TBulletedListTestClientScript extends TClientScriptManager
{
	private array $_postBackControls = [];

	public function getPostBackControls(): array
	{
		return $this->_postBackControls;
	}

	public function registerPostBackControl($class, $options)
	{
		$this->_postBackControls[] = [$class, $options];
	}
}

class TBulletedListTest extends TestCase
{
	use TWebControlRenderTrait;

	private function createPageList(array $items = ['a' => 'A', 'b' => 'B']): array
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_inFormRender', true);
		PradoUnit::setProp($page, '_clientScript', new TBulletedListTestClientScript($page));
		$list = new TBulletedList();
		$list->setID('List');
		foreach ($items as $value => $text) {
			$list->getItems()->add(new TListItem($text, (string) $value));
		}
		$page->getControls()->add($list);
		return [$page, $list];
	}

	// ================================================================================
	// Basics
	// ================================================================================

	public function testImplementsPostBackEventHandler()
	{
		$this->assertInstanceOf(IPostBackEventHandler::class, new TBulletedList());
	}

	public function testGetClientClassName()
	{
		$this->assertSame('Prado.WebUI.TBulletedList', PradoUnit::invoke(new TBulletedList(), 'getClientClassName'));
	}

	public static function tagNameProvider(): array
	{
		return [
			[TBulletStyle::NotSet, 'ul'],
			[TBulletStyle::None, 'ul'],
			[TBulletStyle::Numbered, 'ol'],
			[TBulletStyle::LowerAlpha, 'ol'],
			[TBulletStyle::UpperAlpha, 'ol'],
			[TBulletStyle::LowerRoman, 'ol'],
			[TBulletStyle::UpperRoman, 'ol'],
			[TBulletStyle::Disc, 'ul'],
			[TBulletStyle::Circle, 'ul'],
			[TBulletStyle::Square, 'ul'],
			[TBulletStyle::CustomImage, 'ul'],
		];
	}

	/**
	 * @dataProvider tagNameProvider
	 */
	public function testGetTagName($style, $tag)
	{
		$list = new TBulletedList();
		$list->setBulletStyle($style);
		$this->assertSame($tag, PradoUnit::invoke($list, 'getTagName'));
	}

	// ================================================================================
	// Properties
	// ================================================================================

	public function testBulletImageUrl()
	{
		$list = new TBulletedList();
		$this->assertSame('', $list->getBulletImageUrl());
		$list->setBulletImageUrl('dot.png');
		$this->assertSame('dot.png', $list->getBulletImageUrl());
	}

	public function testBulletStyle()
	{
		$list = new TBulletedList();
		$this->assertSame(TBulletStyle::NotSet, $list->getBulletStyle());
		$list->setBulletStyle('Square');
		$this->assertSame(TBulletStyle::Square, $list->getBulletStyle());
	}

	public function testBulletStyleInvalid()
	{
		$list = new TBulletedList();
		$this->expectException(TInvalidDataValueException::class);
		$list->setBulletStyle('Star');
	}

	public function testDisplayMode()
	{
		$list = new TBulletedList();
		$this->assertSame(TBulletedListDisplayMode::Text, $list->getDisplayMode());
		$list->setDisplayMode('LinkButton');
		$this->assertSame(TBulletedListDisplayMode::LinkButton, $list->getDisplayMode());
	}

	public function testDisplayModeInvalid()
	{
		$list = new TBulletedList();
		$this->expectException(TInvalidDataValueException::class);
		$list->setDisplayMode('Button');
	}

	public function testFirstBulletNumber()
	{
		$list = new TBulletedList();
		$this->assertSame(1, $list->getFirstBulletNumber());
		$list->setFirstBulletNumber('5');
		$this->assertSame(5, $list->getFirstBulletNumber());
	}

	public function testTarget()
	{
		$list = new TBulletedList();
		$this->assertSame('', $list->getTarget());
		$list->setTarget('_blank');
		$this->assertSame('_blank', $list->getTarget());
	}

	// ================================================================================
	// Unsupported setters
	// ================================================================================

	public static function unsupportedProvider(): array
	{
		return [
			['setAutoPostBack', true, 'bulletedlist_autopostback_unsupported'],
			['setSelectedIndex', 0, 'bulletedlist_selectedindex_unsupported'],
			['setSelectedIndices', [0], 'bulletedlist_selectedindices_unsupported'],
			['setSelectedValue', 'a', 'bulletedlist_selectedvalue_unsupported'],
			['setSelectedValues', ['a'], 'bulletedlist_selectedvalue_unsupported'],
		];
	}

	/**
	 * @dataProvider unsupportedProvider
	 */
	public function testUnsupportedSetters($method, $value, $code)
	{
		$list = new TBulletedList();
		try {
			$list->$method($value);
			$this->fail('TNotSupportedException expected.');
		} catch (TNotSupportedException $e) {
			$this->assertSame($code, $e->getErrorCode());
		}
	}

	// ================================================================================
	// Events
	// ================================================================================

	public function testEventParameter()
	{
		$param = new TBulletedListEventParameter(3);
		$this->assertInstanceOf(TEventParameter::class, $param);
		$this->assertSame(3, $param->getIndex());
	}

	public function testOnClickRaisesEvent()
	{
		$list = new TBulletedList();
		$param = new TBulletedListEventParameter(1);
		$raised = [];
		$list->attachEventHandler('OnClick', function ($sender, $p) use (&$raised) {
			$raised[] = [$sender, $p];
		});
		$list->onClick($param);
		$this->assertSame([[$list, $param]], $raised);
	}

	public function testRaisePostBackEventValidatesAndRaisesClick()
	{
		[$page, $list] = $this->createPageList();
		$indices = [];
		$list->attachEventHandler('OnClick', function ($sender, $param) use (&$indices) {
			$indices[] = $param->getIndex();
		});
		$list->raisePostBackEvent('1');
		$this->assertSame([1], $indices);
		$this->assertTrue(PradoUnit::getProp($page, '_validated'));
	}

	public function testRaisePostBackEventWithoutValidation()
	{
		[$page, $list] = $this->createPageList();
		$list->setCausesValidation(false);
		$indices = [];
		$list->attachEventHandler('OnClick', function ($sender, $param) use (&$indices) {
			$indices[] = $param->getIndex();
		});
		$list->raisePostBackEvent('0');
		$this->assertSame([0], $indices);
		$this->assertFalse(PradoUnit::getProp($page, '_validated'));
	}

	// ================================================================================
	// canCauseValidation
	// ================================================================================

	public function testCanCauseValidation()
	{
		[$page, $list] = $this->createPageList();
		$this->assertFalse(PradoUnit::invoke($list, 'canCauseValidation'));

		$validator = new TRequiredFieldValidator();
		$validator->setValidationGroup('g');
		$page->getValidators()->add($validator);
		$this->assertTrue(PradoUnit::invoke($list, 'canCauseValidation'));

		$list->setValidationGroup('other');
		$this->assertFalse(PradoUnit::invoke($list, 'canCauseValidation'));

		$list->setValidationGroup('g');
		$this->assertTrue(PradoUnit::invoke($list, 'canCauseValidation'));
		$list->setCausesValidation(false);
		$this->assertFalse(PradoUnit::invoke($list, 'canCauseValidation'));
	}

	// ================================================================================
	// Rendering: control
	// ================================================================================

	public function testRenderWithoutItemsRendersNothing()
	{
		[, $list] = $this->createPageList([]);
		$this->assertSame('', $this->render($list));
	}

	public function testRenderDefault()
	{
		[, $list] = $this->createPageList(['a' => 'A', 'b' => 'B <">']);
		$this->assertSame(
			'<ul id="List">' . "\n" . '<li>A</li>' . "\n" . '<li>B &lt;&quot;&gt;</li>' . "\n" . '</ul>',
			$this->render($list)
		);
	}

	public static function bulletStyleProvider(): array
	{
		return [
			[TBulletStyle::None, 'ul', 'none', true],
			[TBulletStyle::Numbered, 'ol', 'decimal', true],
			[TBulletStyle::LowerAlpha, 'ol', 'lower-alpha', true],
			[TBulletStyle::UpperAlpha, 'ol', 'upper-alpha', true],
			[TBulletStyle::LowerRoman, 'ol', 'lower-roman', true],
			[TBulletStyle::UpperRoman, 'ol', 'upper-roman', true],
			[TBulletStyle::Disc, 'ul', 'disc', false],
			[TBulletStyle::Circle, 'ul', 'circle', false],
			[TBulletStyle::Square, 'ul', 'square', false],
		];
	}

	/**
	 * @dataProvider bulletStyleProvider
	 */
	public function testRenderBulletStyle($style, $tag, $cssType, $supportsStart)
	{
		[, $list] = $this->createPageList();
		$list->setBulletStyle($style);
		$output = $this->renderBeginTag($list);
		$this->assertStringStartsWith('<' . $tag . ' ', $output);
		$this->assertStringContainsString('style="list-style-type:' . $cssType . ';"', $output);
		$this->assertStringNotContainsString('start=', $output);

		$list->setFirstBulletNumber(3);
		$output = $this->renderBeginTag($list);
		if ($supportsStart) {
			$this->assertStringContainsString('start="3"', $output);
		} else {
			$this->assertStringNotContainsString('start=', $output);
		}
	}

	public function testRenderCustomImage()
	{
		[, $list] = $this->createPageList();
		$list->setBulletStyle(TBulletStyle::CustomImage);
		$list->setBulletImageUrl('dot.png');
		$list->setFirstBulletNumber(3);
		$output = $this->renderBeginTag($list);
		$this->assertStringContainsString('style="list-style-image:url(dot.png);"', $output);
		$this->assertStringNotContainsString('start=', $output);
	}

	public function testRenderNotSetHasNoListStyle()
	{
		[, $list] = $this->createPageList();
		$list->setFirstBulletNumber(3);
		$this->assertSame('<ul id="List">', $this->renderBeginTag($list));
	}

	// ================================================================================
	// Rendering: items
	// ================================================================================

	public function testRenderContentsItemAttributes()
	{
		[, $list] = $this->createPageList(['a' => 'A']);
		$list->getItems()->itemAt(0)->setAttribute('class', 'hot');
		$this->assertSame("\n" . '<li class="hot">A</li>' . "\n", $this->renderContents($list));
	}

	public function testRenderTextDisabledItem()
	{
		[, $list] = $this->createPageList(['a' => 'A', 'b' => 'B<']);
		$list->getItems()->itemAt(1)->setEnabled(false);
		$this->assertSame(
			"\n" . '<li>A</li>' . "\n" . '<li><span disabled="disabled">B&lt;</span></li>' . "\n",
			$this->renderContents($list)
		);
	}

	public function testRenderHyperLink()
	{
		[, $list] = $this->createPageList(['/a' => 'A<']);
		$list->setDisplayMode(TBulletedListDisplayMode::HyperLink);
		$this->assertSame("\n" . '<li><a href="/a">A&lt;</a></li>' . "\n", $this->renderContents($list));
	}

	public function testRenderHyperLinkTargetAndAccessKey()
	{
		[, $list] = $this->createPageList(['/a' => 'A']);
		$list->setDisplayMode(TBulletedListDisplayMode::HyperLink);
		$list->setTarget('_blank');
		$list->setAccessKey('k');
		$this->assertSame(
			"\n" . '<li><a href="/a" target="_blank" accesskey="k">A</a></li>' . "\n",
			$this->renderContents($list)
		);
	}

	public function testRenderHyperLinkDisabledItem()
	{
		[, $list] = $this->createPageList(['/a' => 'A']);
		$list->setDisplayMode(TBulletedListDisplayMode::HyperLink);
		$list->setTarget('_blank');
		$list->getItems()->itemAt(0)->setEnabled(false);
		$this->assertSame("\n" . '<li><a disabled="disabled">A</a></li>' . "\n", $this->renderContents($list));
	}

	public function testRenderHyperLinkDisabledList()
	{
		[, $list] = $this->createPageList(['/a' => 'A']);
		$list->setDisplayMode(TBulletedListDisplayMode::HyperLink);
		$list->setEnabled(false);
		$this->assertSame("\n" . '<li><a disabled="disabled">A</a></li>' . "\n", $this->renderContents($list));
	}

	public function testRenderLinkButton()
	{
		[$page, $list] = $this->createPageList(['a' => 'A', 'b' => 'B']);
		$list->setDisplayMode(TBulletedListDisplayMode::LinkButton);
		$list->setValidationGroup('g');
		$this->assertSame(
			"\n"
			. '<li><a id="List0" href="javascript:;//List0">A</a></li>' . "\n"
			. '<li><a id="List1" href="javascript:;//List1">B</a></li>' . "\n",
			$this->renderContents($list)
		);
		$options = [
			'ValidationGroup' => 'g',
			'CausesValidation' => true,
			'EventTarget' => 'List',
		];
		$this->assertSame([
			['Prado.WebUI.TBulletedList', $options + ['EventParameter' => 0, 'ID' => 'List0', 'StopEvent' => true]],
			['Prado.WebUI.TBulletedList', $options + ['EventParameter' => 1, 'ID' => 'List1', 'StopEvent' => true]],
		], $page->getClientScript()->getPostBackControls());
	}

	public function testRenderLinkButtonAccessKey()
	{
		[, $list] = $this->createPageList(['a' => 'A']);
		$list->setDisplayMode(TBulletedListDisplayMode::LinkButton);
		$list->setAccessKey('k');
		$this->assertStringContainsString('<a id="List0" href="javascript:;//List0" accesskey="k">A</a>', $this->renderContents($list));
	}

	public function testRenderLinkButtonDisabledItem()
	{
		[$page, $list] = $this->createPageList(['a' => 'A', 'b' => 'B']);
		$list->setDisplayMode(TBulletedListDisplayMode::LinkButton);
		$list->getItems()->itemAt(0)->setEnabled(false);
		$output = $this->renderContents($list);
		$this->assertStringContainsString('<li><a disabled="disabled">A</a></li>', $output);
		$this->assertStringContainsString('<li><a id="List1" href="javascript:;//List1">B</a></li>', $output);
		$this->assertCount(1, $page->getClientScript()->getPostBackControls());
	}

	public function testRenderLinkButtonDisabledList()
	{
		[$page, $list] = $this->createPageList(['a' => 'A']);
		$list->setDisplayMode(TBulletedListDisplayMode::LinkButton);
		$list->setEnabled(false);
		$this->assertSame("\n" . '<li><a disabled="disabled">A</a></li>' . "\n", $this->renderContents($list));
		$this->assertSame([], $page->getClientScript()->getPostBackControls());
	}
}
