<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Web\UI\THtmlWriter;
use Prado\IO\TTextWriter;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\INamingContainer;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TControl;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TLabel;
use Prado\Web\UI\WebControls\TListItem;
use Prado\Web\UI\WebControls\TRadioButtonList;
use Prado\Web\UI\WebControls\TRatingList;
use Prado\Web\UI\WebControls\TRepeatDirection;
use Prado\Web\UI\WebControls\TRepeatLayout;

/**
 * Client script manager that records registrations without publishing assets.
 */
class TRatingListTestClientScript extends TClientScriptManager
{
	private array $_postBackControls = [];

	private array $_pradoScripts = [];

	private array $_styleSheetCalls = [];

	public function getPostBackControls(): array
	{
		return $this->_postBackControls;
	}

	public function getRegisteredPradoScripts(): array
	{
		return $this->_pradoScripts;
	}

	public function getStyleSheetCalls(): array
	{
		return $this->_styleSheetCalls;
	}

	public function registerPostBackControl($class, $options)
	{
		$this->_postBackControls[] = [$class, $options];
	}

	public function registerPradoScript($name)
	{
		$this->_pradoScripts[] = $name;
	}

	public function getPradoScriptAssetUrl($script = 'prado')
	{
		return '/assets/' . $script;
	}

	public function registerStyleSheetFile($key, $url, $media = '')
	{
		$this->_styleSheetCalls[] = [$key, $url];
		parent::registerStyleSheetFile($key, $url, $media);
	}
}

/**
 * Naming container used to scope caption control lookups.
 */
class TRatingListTestContainer extends TControl implements INamingContainer
{
}

class TRatingListTest extends TestCase
{
	use TWebControlRenderTrait;

	private function createPage(): TPage
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_inFormRender', true);
		PradoUnit::setProp($page, '_clientScript', new TRatingListTestClientScript($page));
		return $page;
	}

	private function createPageList(int $count = 5): array
	{
		$page = $this->createPage();
		$list = new TRatingList();
		$list->setID('Rate');
		for ($i = 1; $i <= $count; ++$i) {
			$list->getItems()->add(new TListItem("Star $i", (string) $i));
		}
		$page->getControls()->add($list);
		return [$page, $list];
	}

	// ================================================================================
	// Basics
	// ================================================================================

	public function testExtendsRadioButtonList()
	{
		$this->assertInstanceOf(TRadioButtonList::class, new TRatingList());
	}

	public function testConstructorSetsHorizontalDirection()
	{
		$list = new TRatingList();
		$this->assertSame(TRepeatDirection::Horizontal, $list->getRepeatDirection());
	}

	public function testGetClientClassName()
	{
		$this->assertSame('Prado.WebUI.TRatingList', PradoUnit::invoke(new TRatingList(), 'getClientClassName'));
	}

	// ================================================================================
	// ReadOnly / AllowInput / Enabled
	// ================================================================================

	public function testReadOnlyAndAllowInput()
	{
		$list = new TRatingList();
		$this->assertFalse($list->getReadOnly());
		$this->assertTrue($list->getAllowInput());

		$list->setReadOnly('true');
		$this->assertTrue($list->getReadOnly());
		$this->assertFalse($list->getAllowInput());

		$list->setAllowInput('true');
		$this->assertFalse($list->getReadOnly());
		$list->setAllowInput(false);
		$this->assertTrue($list->getReadOnly());
	}

	public function testSetEnabledMapsToReadOnly()
	{
		$list = new TRatingList();
		$list->setEnabled('false');
		$this->assertTrue($list->getReadOnly());
		$this->assertTrue($list->getEnabled());
		$list->setEnabled(true);
		$this->assertFalse($list->getReadOnly());
	}

	// ================================================================================
	// RepeatLayout
	// ================================================================================

	public function testSetRepeatLayoutTable()
	{
		$list = new TRatingList();
		$list->setRepeatLayout(TRepeatLayout::Table);
		$this->assertSame(TRepeatLayout::Table, $list->getRepeatLayout());
	}

	public function testSetRepeatLayoutOtherThrows()
	{
		$list = new TRatingList();
		try {
			$list->setRepeatLayout(TRepeatLayout::Flow);
			$this->fail('TInvalidDataValueException expected.');
		} catch (TInvalidDataValueException $e) {
			$this->assertSame('ratinglist_table_layout_only', $e->getErrorCode());
		}
	}

	// ================================================================================
	// Rating
	// ================================================================================

	public function testRatingDefaultsToSelectedIndex()
	{
		[, $list] = $this->createPageList();
		$this->assertSame(0, $list->getRating());
		$list->getItems()->itemAt(2)->setSelected(true);
		$this->assertSame(3, $list->getRating());
	}

	public function testSetRatingStoresValueAndSelectsIndex()
	{
		[, $list] = $this->createPageList();
		$list->setRating('3');
		$this->assertSame(3.0, $list->getRating());
		$this->assertSame(2, $list->getSelectedIndex());
	}

	public function testSetRatingRoundsByHalfInterval()
	{
		[, $list] = $this->createPageList();
		$list->setRating(2.5);
		$this->assertSame(2.5, $list->getRating());
		$this->assertSame(1, $list->getSelectedIndex());

		$list->setRating(2.8);
		$this->assertSame(2, $list->getSelectedIndex());
	}

	public function testGetRatingIndexUsesHalfRatingInterval()
	{
		$list = new TRatingList();
		$this->assertSame(1, PradoUnit::invoke($list, 'getRatingIndex', 2.6));
		$this->assertSame(2, PradoUnit::invoke($list, 'getRatingIndex', 2.75));
		$list->setHalfRatingInterval([0.2, 0.4]);
		$this->assertSame(2, PradoUnit::invoke($list, 'getRatingIndex', 2.5));
		$this->assertSame(0, PradoUnit::invoke($list, 'getRatingIndex', 1.0));
	}

	public function testSetSelectedIndexSetsRating()
	{
		[, $list] = $this->createPageList();
		$list->setSelectedIndex(3);
		$this->assertSame(3, $list->getSelectedIndex());
		$this->assertSame(4.0, $list->getRating());
	}

	public function testOnSelectedIndexChangedTruncatesRating()
	{
		[, $list] = $this->createPageList();
		$list->setRating(3.6);
		$raised = [];
		$list->attachEventHandler('OnSelectedIndexChanged', function ($sender, $param) use (&$raised) {
			$raised[] = 'SelectedIndexChanged';
		});
		$list->attachEventHandler('OnTextChanged', function ($sender, $param) use (&$raised) {
			$raised[] = 'TextChanged';
		});
		$list->onSelectedIndexChanged(null);
		$this->assertSame(3.0, $list->getRating());
		$this->assertSame(2, $list->getSelectedIndex());
		$this->assertSame(['SelectedIndexChanged', 'TextChanged'], $raised);
	}

	public function testLoadPostDataUpdatesRating()
	{
		[, $list] = $this->createPageList();
		$this->assertTrue($list->loadPostData('Rate', ['Rate' => '4']));
		$this->assertSame(3, $list->getSelectedIndex());
		$this->assertSame(4.0, $list->getRating());
	}

	// ================================================================================
	// Style properties
	// ================================================================================

	public function testRatingStyle()
	{
		$list = new TRatingList();
		$this->assertSame('default', $list->getRatingStyle());
		$this->assertSame('TRatingList_default', PradoUnit::invoke($list, 'getRatingStyleCssClass'));
		$list->setRatingStyle('fancy');
		$this->assertSame('fancy', $list->getRatingStyle());
		$this->assertSame('TRatingList_fancy', PradoUnit::invoke($list, 'getRatingStyleCssClass'));
	}

	public function testHalfRatingInterval()
	{
		$list = new TRatingList();
		$this->assertSame([0.3, 0.7], $list->getHalfRatingInterval());
		$list->setHalfRatingInterval([0.25, 0.75]);
		$this->assertSame([0.25, 0.75], $list->getHalfRatingInterval());
	}

	// ================================================================================
	// Caption
	// ================================================================================

	public function testCaptionID()
	{
		$list = new TRatingList();
		$this->assertSame('', $list->getCaptionID());
		$list->setCaptionID('Cap');
		$this->assertSame('Cap', $list->getCaptionID());
	}

	public function testCaptionFoundOnPage()
	{
		[$page, $list] = $this->createPageList();
		$label = new TLabel();
		$label->setID('Cap');
		$label->setText('Rate it:');
		$page->getControls()->add($label);
		$list->setCaptionID('Cap');

		$this->assertSame($label, PradoUnit::invoke($list, 'getCaptionControl'));
		$this->assertSame('Rate it:', $list->getCaption());
		$list->setCaption('Score:');
		$this->assertSame('Score:', $label->getText());
	}

	public function testCaptionFoundInNamingContainer()
	{
		$page = $this->createPage();
		$container = new TRatingListTestContainer();
		$container->setID('Box');
		$page->getControls()->add($container);
		$label = new TLabel();
		$label->setID('Cap');
		$container->getControls()->add($label);
		$list = new TRatingList();
		$list->setCaptionID('Cap');
		$container->getControls()->add($list);

		$this->assertNull($page->findControl('Cap'));
		$this->assertSame($label, PradoUnit::invoke($list, 'getCaptionControl'));
	}

	public function testCaptionMissingControlThrows()
	{
		[, $list] = $this->createPageList();
		$list->setCaptionID('Nope');
		try {
			$list->getCaption();
			$this->fail('TInvalidDataValueException expected.');
		} catch (TInvalidDataValueException $e) {
			$this->assertSame('ratinglist_invalid_caption_id', $e->getErrorCode());
		}
	}

	public function testCaptionWithoutCaptionIDThrows()
	{
		[, $list] = $this->createPageList();
		$this->expectException(TInvalidDataValueException::class);
		$list->setCaption('x');
	}

	public function testGetCaptionControlID()
	{
		[$page, $list] = $this->createPageList();
		$this->assertSame('', PradoUnit::invoke($list, 'getCaptionControlID'));

		$list->setCaptionID('Elsewhere');
		$this->assertSame('Elsewhere', PradoUnit::invoke($list, 'getCaptionControlID'));

		$label = new TLabel();
		$label->setID('Cap');
		$page->getControls()->add($label);
		$list->setCaptionID('Cap');
		$this->assertSame($label->getClientID(), PradoUnit::invoke($list, 'getCaptionControlID'));

		$label->setVisible(false);
		$this->assertSame('', PradoUnit::invoke($list, 'getCaptionControlID'));
	}

	// ================================================================================
	// Client script
	// ================================================================================

	public function testGetPostBackOptions()
	{
		[, $list] = $this->createPageList(3);
		$list->setRating(2);
		$list->setReadOnly(true);
		$list->setCaptionID('CapID');
		$this->assertSame([
			'ID' => 'Rate',
			'ValidationGroup' => '',
			'CausesValidation' => true,
			'ListName' => 'Rate',
			'ItemCount' => 3,
			'AutoPostBack' => false,
			'ReadOnly' => true,
			'Style' => 'TRatingList_default',
			'CaptionID' => 'CapID',
			'SelectedIndex' => 1,
			'Rating' => 2.0,
			'HalfRating' => [0.3, 0.7],
		], PradoUnit::invoke($list, 'getPostBackOptions'));
	}

	public function testGetAssetUrl()
	{
		[, $list] = $this->createPageList();
		$this->assertSame('/assets/prado/ratings/', PradoUnit::invoke($list, 'getAssetUrl'));
		$this->assertSame('/assets/prado/ratings/x.css', PradoUnit::invoke($list, 'getAssetUrl', 'x.css'));
	}

	public function testPublishStyleRegistersOnce()
	{
		[$page, $list] = $this->createPageList();
		$url = '/assets/prado/ratings/fancy.css';
		$this->assertSame($url, PradoUnit::invoke($list, 'publishStyle', 'fancy'));
		$this->assertSame($url, PradoUnit::invoke($list, 'publishStyle', 'fancy'));
		$this->assertSame([[$url, $url]], $page->getClientScript()->getStyleSheetCalls());
		$this->assertTrue($page->getClientScript()->isStyleSheetFileRegistered($url));
	}

	public function testPublishImages()
	{
		[, $list] = $this->createPageList();
		$base = '/assets/prado/ratings/';
		$this->assertSame([
			'blank' => $base . 'fancy_blank.gif',
			'selected' => $base . 'fancy_selected.gif',
			'half' => $base . 'fancy_half.gif',
			'combined' => $base . 'fancy_combined.gif',
		], PradoUnit::invoke($list, 'publishImages', 'fancy'));
		$this->assertSame($base . 'fancy_blank.png', PradoUnit::invoke($list, 'publishImages', 'fancy', '.png')['blank']);
	}

	public function testOnPreRenderPublishesAndRegisters()
	{
		[$page, $list] = $this->createPageList(2);
		$list->onPreRender(null);
		$cs = $page->getClientScript();
		$this->assertSame(['ratings'], $cs->getRegisteredPradoScripts());
		$this->assertTrue($cs->isStyleSheetFileRegistered('/assets/prado/ratings/default.css'));
		$this->assertSame(
			['Rate$c0' => true, 'Rate$c1' => true],
			PradoUnit::getProp($page, '_controlsRegisteredForPostData')
		);
	}

	// ================================================================================
	// Rendering
	// ================================================================================

	public function testRender()
	{
		[$page, $list] = $this->createPageList(2);
		$list->setSelectedIndex(1);
		$output = $this->render($list);
		$this->assertMatchesRegularExpression('/^<table id="Rate"[^>]*role="radiogroup">/', $output);
		$this->assertStringNotContainsString('aria-readonly', $output);
		$this->assertStringContainsString('<input id="Rate_c1" type="radio" name="Rate" value="2" checked="checked" />', $output);
		$this->assertSame(1, substr_count($output, '<tr>'));
		$this->assertSame(
			[['Prado.WebUI.TRatingList', PradoUnit::invoke($list, 'getPostBackOptions')]],
			$page->getClientScript()->getPostBackControls()
		);
	}

	public function testRenderReadOnly()
	{
		[, $list] = $this->createPageList(2);
		$list->setReadOnly(true);
		$output = $this->render($list);
		$this->assertMatchesRegularExpression('/^<table id="Rate"[^>]*aria-readonly="true"[^>]*>/', $output);
	}

	public function testRenderWithoutItemsLeavesNoAttributesInWriter()
	{
		[, $list] = $this->createPageList(0);
		$list->setReadOnly(true);
		$textWriter = new TTextWriter();
		$writer = new THtmlWriter($textWriter);
		$list->renderControl($writer);
		$writer->renderBeginTag('div');
		$writer->renderEndTag();
		$this->assertSame('<div></div>', $textWriter->flush());
	}
}
