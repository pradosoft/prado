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

	// ================================================================================
	// ReadOnly / AllowInput / Enabled
	// ================================================================================

	// ================================================================================
	// RepeatLayout
	// ================================================================================

	// ================================================================================
	// Rating
	// ================================================================================

	// ================================================================================
	// Style properties
	// ================================================================================

	// ================================================================================
	// Caption
	// ================================================================================

	// ================================================================================
	// Client script
	// ================================================================================

	// ================================================================================
	// Rendering
	// ================================================================================

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
