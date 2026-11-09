<?php

/**
 * Harness page for the TDrawer functional tests (#1401).
 *
 * "nav" docks at 64rem and opens modally below it. "filters" is a modal End drawer
 * whose field posts back. "sheet" is a bottom sheet with scrolling content. "locked"
 * has ClosedBy None, so only its close button closes it.
 */
class DrawerTest extends TPage
{
	public function applyClicked($sender, $param)
	{
		$this->status->setText('Applied: ' . $this->term->getText());
	}
}
