<?php

/**
 * Harness page for the structure element functional tests.
 *
 * Renders TDescriptionList, THGroup, and TMenuList, a TCanvas with an OnDraw
 * handler in an active panel that a callback renders again, and a TCanvas with
 * focusable fallback content.
 */
class StructureTagsTest extends TPage
{
	public function getChartText()
	{
		return $this->getViewState('ChartText', 'Sales rose 20%');
	}

	public function menuClicked($sender, $param)
	{
		$this->status->setText('clicked: ' . $sender->getItems()->itemAt($param->getIndex())->getValue());
	}

	public function redraw($sender, $param)
	{
		$this->setViewState('ChartText', 'Sales rose 30%');
		$this->live->render($param->getNewWriter());
	}
}
