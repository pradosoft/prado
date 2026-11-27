<?php

namespace Prado\Test\Unit\Web\UI\JuiControls;

use Prado\Test\Unit\PradoUnit;
use Prado\Web\Javascripts\TJavaScriptLiteral;
use Prado\Web\UI\JuiControls\TJuiSortable;
use Prado\Web\UI\TTemplate;
use Prado\Web\UI\JuiControls\TJuiSortableTemplate;
use Prado\Web\UI\WebControls\TRepeater;

/**
 * Unit tests for {@see TJuiSortable}.
 */
class TJuiSortableTest extends TJuiWidgetTestCase
{
	protected function controlClass(): string
	{
		return TJuiSortable::class;
	}

	protected function widget(): string
	{
		return 'sortable';
	}

	protected function events(): array
	{
		return ['activate', 'beforeStop', 'change', 'create', 'deactivate', 'out', 'over', 'receive', 'remove', 'sort', 'start', 'stop', 'update'];
	}

	protected function option(): array
	{
		return ['axis', 'y', '"y"'];
	}

	protected function widgetIDSuffix(): string
	{
		return '_0';
	}

	public function testSortablesIsARepeaterChild(): void
	{
		$control = $this->newControl();
		$repeater = $control->getSortables();
		$this->assertInstanceOf(TRepeater::class, $repeater);
		$this->assertSame($repeater, $control->getSortables());
		$this->assertSame($control, $repeater->getParent());
		$this->assertInstanceOf(TJuiSortableTemplate::class, $repeater->getHeaderTemplate());
		$this->assertInstanceOf(TJuiSortableTemplate::class, $repeater->getFooterTemplate());
		$this->assertInstanceOf(TJuiSortableTemplate::class, $repeater->getEmptyTemplate());
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
			$repeater = $control->getSortables();
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
	 * The sort and stop callbacks send the item order as `ui.index`; the other events keep their callback.
	 */
	public function testSortAndStopCallbacksSendTheItemOrder(): void
	{
		$output = $this->runPage($this->newPage(function ($control) {
			$control->attachEventHandler('OnSort', fn () => null);
			$control->attachEventHandler('OnStop', fn () => null);
			$control->attachEventHandler('OnUpdate', fn () => null);
		}));
		$order = fn ($event) => "function( event, ui ) { ui.index = jQuery(this).sortable('toArray', { attribute: \"data-index\" }); Prado.JuiCallback(\"Widget\", '" . $event . "', event, ui, this); }";
		$this->assertStringContainsString(
			"jQuery('#Widget_0').sortable({'sort':" . $order('sort') . ",'stop':" . $order('stop') . ",'update':" . $this->eventCallback('update') . '});',
			$output
		);
	}

	/**
	 * The sort and stop callbacks are replaced; options and other callbacks are kept.
	 */
	public static function postBackOptionsProvider(): array
	{
		return [
			'none' => [[], [], []],
			'option' => [['axis'], [], []],
			'sort' => [[], ['sort'], ['sort']],
			'stop' => [[], ['stop'], ['stop']],
			'other event' => [[], ['update'], []],
			'option, events' => [['axis'], ['sort', 'stop', 'update'], ['sort', 'stop']],
		];
	}

	/**
	 * @dataProvider postBackOptionsProvider
	 * @param string[] $set the options set
	 * @param string[] $handled the events with a handler
	 * @param string[] $ordered the events whose callback sends the item order
	 */
	public function testPostBackOptions(array $set, array $handled, array $ordered): void
	{
		$control = $this->newControl();
		foreach ($set as $option) {
			$control->getOptions()->$option = 'y';
		}
		foreach ($handled as $event) {
			$control->attachEventHandler('On' . $event, fn () => null);
		}
		$options = PradoUnit::invoke($control, 'getPostBackOptions');
		$this->assertSame(array_merge($set, $handled), array_keys($options));
		foreach ($handled as $event) {
			$this->assertInstanceOf(TJavaScriptLiteral::class, $options[$event]);
			$this->assertSame(in_array($event, $ordered, true), str_contains((string) $options[$event], "sortable('toArray'"));
		}
	}

	/**
	 * Binding the data in a callback renders the control again and replaces its element on the client.
	 */
	public function testDataBindInACallbackReplacesTheList(): void
	{
		$configure = function ($control) {
			$control->attachEventHandler('OnUpdate', function ($sender) {
				$sender->setDataSource(['c']);
				$sender->dataBind();
			});
		};
		$this->runPage($this->newPage($configure));
		$this->runCallback($this->newPage($configure), 'Widget', ['event' => 'update']);
		$parts = $this->getCallbackParts();
		$replace = $this->replaceActions($parts);
		$this->assertCount(1, $replace);
		[$id, , $boundary, $self] = $replace[0];
		$this->assertSame('Widget', $id);
		$this->assertTrue($self);
		$this->assertSame('<div id="Widget"><ul id="Widget_0"><li id="Widget_0_0" data-index="0">c</li></ul></div>', $parts[$boundary]);
	}
}
