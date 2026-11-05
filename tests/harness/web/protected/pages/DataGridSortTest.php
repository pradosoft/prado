<?php

use Prado\Web\UI\WebControls\TDataGridSortDirection;

/**
 * Harness page for the TDataGrid sort state functional tests.
 *
 * Each grid's OnSortCommand handler applies the requested sort to the grid's
 * SortExpression and SortDirection, sorts the rows, and rebinds. The postback
 * grid starts sorted by City; the active grid starts unsorted and sorts during
 * a callback.
 */
class DataGridSortTest extends TPage
{
	private const ROWS = [
		['name' => 'Bea', 'city' => 'Austin', 'note' => 'b'],
		['name' => 'Al', 'city' => 'Chicago', 'note' => 'a'],
		['name' => 'Cy', 'city' => 'Boston', 'note' => 'c'],
	];

	public function onLoad($param)
	{
		parent::onLoad($param);
		if (!$this->getIsPostBack() && !$this->getIsCallback()) {
			$this->grid->setSortExpression('city');
			$this->bindGrid($this->grid);
			$this->bindGrid($this->activeGrid);
		}
	}

	public function sortGrid($sender, $param)
	{
		$sender->setSortExpression($param->getSortExpression());
		$sender->setSortDirection($param->getSortDirection());
		$this->bindGrid($sender);
	}

	public function sortActiveGrid($sender, $param)
	{
		$this->sortGrid($sender, $param);
	}

	private function bindGrid($grid): void
	{
		$rows = self::ROWS;
		if (($key = $grid->getSortExpression()) !== '') {
			usort($rows, fn ($a, $b) => strcmp($a[$key], $b[$key]));
			if ($grid->getSortDirection() === TDataGridSortDirection::Descending) {
				$rows = array_reverse($rows);
			}
		}
		$grid->setDataSource($rows);
		$grid->dataBind();
	}
}
