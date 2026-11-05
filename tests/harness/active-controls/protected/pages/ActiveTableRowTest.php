<?php

class ActiveTableRowTest extends TPage
{
	public function cellSelected($sender, $param)
	{
		$this->Result->setText($this->Result->getText() . 'cell:' . $param->getSelectedCellIndex() . ' ');
	}

	public function rowSelected($sender, $param)
	{
		$this->Result->setText($this->Result->getText() . 'row:' . $param->getSelectedRowIndex() . ' ');
	}
}
