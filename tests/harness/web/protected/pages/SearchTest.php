<?php

/**
 * Harness page for TSearch functional tests.
 *
 * The site search renders the `<search>` element. The product filter renders
 * TagName `div`, which carries `role="search"`. Each landmark has a label.
 */
class SearchTest extends TPage
{
	public function search($sender, $param)
	{
		$this->status->setText('searched: ' . $this->query->getText());
	}
}
