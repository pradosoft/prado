<?php

namespace Prado\Test\Unit\Web\UI\JuiControls;

use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\JuiControls\TJuiSelectable;
use Prado\Web\UI\TTemplate;
use Prado\Web\UI\JuiControls\TJuiSelectableTemplate;
use Prado\Web\UI\WebControls\TRepeater;

/**
 * Unit tests for {@see TJuiSelectable}.
 */
class TJuiSelectableTest extends TJuiWidgetTestCase
{
	protected function controlClass(): string
	{
		return TJuiSelectable::class;
	}

	protected function widget(): string
	{
		return 'selectable';
	}

	protected function events(): array
	{
		return ['create', 'selected', 'selecting', 'start', 'stop', 'unselected', 'unselecting'];
	}

	protected function option(): array
	{
		return ['filter', 'li', '"li"'];
	}

	protected function widgetIDSuffix(): string
	{
		return '_0';
	}

	public function testSelectablesIsARepeaterChild(): void
	{
		$control = $this->newControl();
		$repeater = $control->getSelectables();
		$this->assertInstanceOf(TRepeater::class, $repeater);
		$this->assertSame($repeater, $control->getSelectables());
		$this->assertSame($control, $repeater->getParent());
		$this->assertInstanceOf(TJuiSelectableTemplate::class, $repeater->getHeaderTemplate());
		$this->assertInstanceOf(TJuiSelectableTemplate::class, $repeater->getFooterTemplate());
		$this->assertInstanceOf(TJuiSelectableTemplate::class, $repeater->getEmptyTemplate());
	}

	public function testRenderListsTheItems(): void
	{
		$output = $this->runPage($this->newPage(function ($control) {
			$control->setDataSource(['a', 'b']);
			$control->dataBind();
		}));
		$this->assertStringContainsString('<div id="Widget"><ul id="Widget_0"><li id="Widget_0_0" data-index="0">a</li><li id="Widget_0_1" data-index="1">b</li></ul></div>', $output);
	}

	/**
	 * Encode HTML-encodes the Data of the default items.
	 */
	public function testEncodeEncodesTheItemData(): void
	{
		$output = $this->runPage($this->newPage(function ($control) {
			$control->setEncode(true);
			$control->setDataSource(['<b>a</b>']);
			$control->dataBind();
		}));
		$this->assertStringContainsString('<li id="Widget_0_0" data-index="0">&lt;b&gt;a&lt;/b&gt;</li>', $output);
		$output = $this->runPage($this->newPage(function ($control) {
			$control->setDataSource(['<b>a</b>']);
			$control->dataBind();
		}));
		$this->assertStringContainsString('<li id="Widget_0_0" data-index="0"><b>a</b></li>', $output);
	}

	/**
	 * The client sends the `data-index` of the default items, and the `id` of the items of a custom template.
	 */
	public static function indexAttributeProvider(): array
	{
		return [
			'no list' => [null, '"data-index"'],
			'default items' => ['default', '"data-index"'],
			'custom item template' => ['item', '"id"'],
			'custom alternating item template' => ['alternating', '"id"'],
		];
	}

	/**
	 * @dataProvider indexAttributeProvider
	 * @param ?string $template the template set: null for no list, `default`, `item`, or `alternating`
	 * @param string $attribute the encoded attribute the client reads
	 */
	public function testIndexAttribute(?string $template, string $attribute): void
	{
		$control = $this->newControl();
		$control->attachEventHandler('OnStop', fn () => null);
		if ($template !== null) {
			$repeater = $control->getSelectables();
			if ($template === 'item') {
				$repeater->setItemTemplate(new TTemplate('<li id="x<%# $this->ItemIndex %>"></li>', null));
			} elseif ($template === 'alternating') {
				$repeater->setAlternatingItemTemplate(new TTemplate('<li id="y<%# $this->ItemIndex %>"></li>', null));
			}
		}
		$this->assertStringContainsString($attribute, (string) PradoUnit::invoke($control, 'getPostBackOptions')['stop']);
	}

	public function testRenderWithoutItemsUsesTheEmptyTemplate(): void
	{
		$output = $this->runPage($this->newPage(function ($control) {
			$control->setDataSource([]);
			$control->dataBind();
		}));
		$this->assertStringContainsString('<div id="Widget"><ul></ul></div>', $output);
	}

	/**
	 * The stop callback sends the indexes of the selected items as `ui.index`.
	 */
	public function testStopCallbackSendsTheSelectedItems(): void
	{
		$output = $this->runPage($this->newPage(function ($control) {
			$control->attachEventHandler('OnStop', fn () => null);
		}));
		$this->assertStringContainsString(
			"jQuery('#Widget_0').selectable({'stop':function( event, ui ) { ui.index = new Array(); jQuery('#Widget .ui-selected')"
			. ".each(function(idx, item){ ui.index.push(jQuery(item).attr(\"data-index\")) }); Prado.JuiCallback(\"Widget\", 'stop', event, ui, this); }});",
			$output
		);
	}
}
