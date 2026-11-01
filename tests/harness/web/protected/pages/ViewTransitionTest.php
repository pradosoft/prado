<?php

/**
 * Harness page for the callback view transition functional tests (#1408).
 *
 * "Refresh" re-renders the results panel, whose ActiveControl.ViewTransition is
 * true, and sets the status label in the same response; the status reports the
 * value of the "seen" field the request posted, and the panel renders the new
 * count into it. "Plain refresh" re-renders a panel without a view transition.
 * "Handler transition" turns the view transition on from the callback handler
 * and only sets the status. "Broken action" sends a client function that does
 * not exist before the status update, inside a view transition.
 */
class ViewTransitionTest extends TPage
{
	public function getRefreshCount()
	{
		return $this->getViewState('RefreshCount', 0);
	}

	public function getPlainCount()
	{
		return $this->getViewState('PlainCount', 0);
	}

	public function refresh($sender, $param)
	{
		$this->setViewState('RefreshCount', $this->getRefreshCount() + 1);
		$this->status->setText('posted ' . $this->seen->getText());
		$this->count->setText($this->getRefreshCount());
		$this->seen->setText($this->getRefreshCount());
		$this->results->render($param->getNewWriter());
	}

	public function plainRefresh($sender, $param)
	{
		$this->setViewState('PlainCount', $this->getPlainCount() + 1);
		$this->other->render($param->getNewWriter());
	}

	public function handlerTransition($sender, $param)
	{
		$this->getCallbackClient()->setViewTransition(true);
		$this->status->setText('handler');
	}

	public function broken($sender, $param)
	{
		$this->getCallbackClient()->setViewTransition(true);
		$this->getCallbackClient()->callClientFunction('Prado.NoSuchObject.noSuchMethod');
		$this->status->setText('after broken');
	}
}
