<?php

/**
 * Harness page for TPage.CallbackViewTransition (#1408): every callback response
 * with client actions applies them in a view transition. "Update" sets the
 * status label; "Nothing" sends no client actions.
 */
class ViewTransitionPageTest extends TPage
{
	public function update($sender, $param)
	{
		$this->status->setText('updated');
	}

	public function nothing($sender, $param)
	{
	}
}
