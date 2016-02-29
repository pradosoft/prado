<?php

Prado::using('Application.pages.ActiveControls.Samples.TActiveDataGrid.Sample2');

class Sample4 extends Sample2
{
	protected function sortData($data,$key)
	{
		usort($data, function($a, $b) use ($key) {
			if ($a[$key] == $b[$key]) {
				return 0;
			} else {
				return ($a[$key] > $b[$key]) ? 1 : -1;
			}
		});
		return $data;
	}

	public function sortDataGrid($sender,$param)
	{
		$this->DataGrid->DataSource=$this->sortData($this->Data,$param->SortExpression);
		$this->DataGrid->dataBind();
	}
}

