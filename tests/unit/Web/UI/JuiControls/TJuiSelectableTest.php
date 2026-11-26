<?php

namespace Prado\Test\Unit\Web\UI\JuiControls;

use Prado\Web\UI\JuiControls\TJuiSelectable;
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
		$this->assertStringContainsString('<div id="Widget"><ul id="Widget_0"><li id="0">a</li><li id="1">b</li></ul></div>', $output);
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
	 * The stop callback sends the IDs of the selected items as `ui.index`.
	 */
	public function testStopCallbackSendsTheSelectedItems(): void
	{
		$output = $this->runPage($this->newPage(function ($control) {
			$control->attachEventHandler('OnStop', fn () => null);
		}));
		$this->assertStringContainsString(
			"jQuery('#Widget_0').selectable({'stop':function( event, ui ) { ui.index = new Array(); jQuery('#Widget .ui-selected')"
			. ".each(function(idx, item){ ui.index.push(item.id) }); Prado.JuiCallback(\"Widget\", 'stop', event, ui, this); }});",
			$output
		);
	}
}
