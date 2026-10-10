<?php

use Prado\Web\UI\WebControls\TListItem;

/**
 * Harness page for the option text of a list control updated during a callback.
 *
 * The page render and the callback reload add the same items, so the test
 * compares the option text the browser shows after each.
 */
class ListOptionTextTest extends TPage
{
	public const ITEMS = [
		'm' => '<img src=x onerror="window.__optionXss=1">',
		'b' => '<b>Bold</b>',
		'amp' => 'Tom &amp; Jerry',
		'raw' => 'Tom & Jerry',
		'nbsp' => '&nbsp;&nbsp;Child',
		'lt' => '&lt;i&gt;',
	];

	public function onLoad($param)
	{
		parent::onLoad($param);
		if (!$this->getIsPostBack()) {
			$this->addItems();
		}
	}

	public function reload_items($sender, $param)
	{
		$this->list->getItems()->clear();
		$this->addItems();
		$this->status->setText('reloaded');
	}

	private function addItems(): void
	{
		foreach (self::ITEMS as $value => $text) {
			$this->list->getItems()->add(new TListItem($text, $value));
		}
	}
}
