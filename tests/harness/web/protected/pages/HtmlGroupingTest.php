<?php

use Prado\Web\UI\WebControls\TLinkTag;

/**
 * Harness page for the TFieldSet, TLegend, TSeparator, TNoScript, and TLinkTag
 * functional tests.
 *
 * "Disable shipping" disables the shipping fieldset on the server. "Submit" posts
 * the page and reports the gift wrap state, which the disabled group keeps. The
 * head carries a canonical TLinkTag.
 */
class HtmlGroupingTest extends TPage
{
	public function onLoad($param)
	{
		parent::onLoad($param);
		if (!$this->getIsPostBack()) {
			$link = new TLinkTag();
			$link->setRel('canonical');
			$link->setHref('https://example.com/grouping');
			$this->getHead()->getLinkTags()->add($link);
		}
	}

	public function disableShipping($sender, $param)
	{
		$this->shipping->setEnabled(false);
		$this->status->setText('disabled');
	}

	public function submit($sender, $param)
	{
		$this->status->setText('gift: ' . ($this->gift->getChecked() ? 'yes' : 'no') . ', street: ' . $this->street->getText());
	}
}
