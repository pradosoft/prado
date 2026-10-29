<?php

/**
 * Harness page for the callback DOM morph functional tests (#1412).
 *
 * "Refresh" re-renders the morphed panel and the replaced panel. The server
 * uppercases the note and sets the other field, so a test tells the server
 * value from the typed one. "Slow refresh" waits before it answers, so a test
 * types while the callback is in flight. "Insert at top" adds a repeater row
 * above the others.
 */
class CallbackMorphTest extends TPage
{
	public function getRefreshCount()
	{
		return $this->getViewState('RefreshCount', 0);
	}

	public function onLoad($param)
	{
		parent::onLoad($param);
		if (!$this->getIsPostBack() && !$this->getIsCallback()) {
			$this->setViewState('Rows', ['b', 'c', 'd']);
			$this->bindRows();
		}
		$this->count->setText($this->getRefreshCount());
	}

	protected function bindRows()
	{
		$this->rows->setDataSource($this->getViewState('Rows'));
		$this->rows->dataBind();
	}

	public function refreshPanels($sender, $param)
	{
		$this->setViewState('RefreshCount', $this->getRefreshCount() + 1);
		$this->count->setText($this->getRefreshCount());
		$this->note->setText(strtoupper($this->note->getText()));
		$this->other->setText('server ' . $this->getRefreshCount());
		$this->panel->render($param->getNewWriter());
		$this->replaced->render($param->getNewWriter());
	}

	public function slowRefresh($sender, $param)
	{
		usleep(600000);
		$this->refreshPanels($sender, $param);
	}

	public function innerClick($sender, $param)
	{
		$this->innerCount->setText($this->innerCount->getText() + 1);
	}

	public function ratingChanged($sender, $param)
	{
		$this->ratingCount->setText($this->ratingCount->getText() + 1);
	}

	public function insertTop($sender, $param)
	{
		$rows = $this->getViewState('Rows');
		array_unshift($rows, 'a' . count($rows));
		$this->setViewState('Rows', $rows);
		$this->bindRows();
		$this->rows->render($param->getNewWriter());
	}
}
