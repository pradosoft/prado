<?php

/**
 * Harness page for the callback DOM morph functional tests (#1412).
 *
 * "Refresh" re-renders the morphed panel and the replaced panel. The server
 * uppercases the note and sets the other field, so a test tells the server
 * value from the typed one. "Slow refresh" waits before it answers, so a test
 * types while the callback is in flight. "Insert at top" and "Delete top" add
 * and remove the first repeater row; the grid buttons do the same for the
 * TActiveDataGrid, whose fields hold a value per record. "Big refresh"
 * re-renders a 500-row grid whose text changes on each refresh.
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
			$this->setViewState('GridRows', ['b', 'c', 'd']);
			$this->bindGrid();
			$this->bindBig();
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

	public function deleteTop($sender, $param)
	{
		$rows = $this->getViewState('Rows');
		array_shift($rows);
		$this->setViewState('Rows', $rows);
		$this->bindRows();
		$this->rows->render($param->getNewWriter());
	}

	protected function bindGrid()
	{
		$this->grid->setDataSource($this->getViewState('GridRows'));
		$this->grid->dataBind();
	}

	public function gridInsertTop($sender, $param)
	{
		$rows = $this->getViewState('GridRows');
		array_unshift($rows, 'a' . count($rows));
		$this->setViewState('GridRows', $rows);
		$this->bindGrid();
		$this->grid->render($param->getNewWriter());
	}

	public function gridDeleteTop($sender, $param)
	{
		$rows = $this->getViewState('GridRows');
		array_shift($rows);
		$this->setViewState('GridRows', $rows);
		$this->bindGrid();
		$this->grid->render($param->getNewWriter());
	}

	protected function bindBig()
	{
		$pass = $this->getViewState('BigPass', 0);
		$this->big->setDataSource(array_map(fn ($i) => "r{$i}-{$pass}", range(1, 500)));
		$this->big->dataBind();
	}

	public function bigRefresh($sender, $param)
	{
		$this->setViewState('BigPass', $this->getViewState('BigPass', 0) + 1);
		$this->bindBig();
		$this->big->render($param->getNewWriter());
	}
}
