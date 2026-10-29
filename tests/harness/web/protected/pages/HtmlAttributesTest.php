<?php

/**
 * Harness page for the HTML attribute functional tests.
 *
 * Renders an autofocus text box, an indeterminate check box and a switch, a
 * table cell with Headers, a sandboxed srcdoc frame, a lazy image, a download
 * link, a manual popover, a hidden-until-found panel, a folder upload, a Cancel
 * button whose CausesValidation is false beside a required field, and an Export
 * button with a FormAction that posts back by script.
 */
class HtmlAttributesTest extends TPage
{
	public function onLoad($param)
	{
		parent::onLoad($param);
		$this->map->setSrcDoc('<p>Map &amp; directions</p>');
	}

	public function cancel($sender, $param)
	{
		$this->status->setText('cancelled');
	}

	public function export($sender, $param)
	{
		$this->status->setText('exported via ' . $this->getRequest()->itemAt('via'));
	}
}
