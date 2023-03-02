<?php

class Issue516 extends TPage
{
	private $_data;

	protected function getData()
	{
		if ($this->_data === null) {
			$this->loadData();
		}
		return $this->_data;
	}

	protected function loadData()
	{
		// We use viewstate keep track of data.
		// In real applications, data should come from database using an SQL SELECT statement.
		// In the following tabular data, field 'ISBN' is the primary key.
		// All update and delete operations should come with an 'id' value in order to go through.
		if (($this->_data = $this->getViewState('Data', null)) === null) {
			$this->_data = [
				[
					'ISBN' => '0596007124',
					'title' => '',
				],
				[
					'ISBN' => '0201633612',
					'title' => 'Design Patterns: Elements of Reusable Object-Oriented Software',
				],
				[
					'ISBN' => '0321247140',
					'title' => 'Design Patterns Explained : A New Perspective on Object-Oriented Design',
				],
				[
					'ISBN' => '0201485672',
					'title' => 'Refactoring: Improving the Design of Existing Code',
				],
				[
					'ISBN' => '0321213351',
					'title' => 'Refactoring to Patterns',
				],
				[
					'ISBN' => '0735619670',
					'title' => 'Code Complete',
				],
				[
					'ISBN' => '0321278658 ',
					'title' => 'Extreme Programming Explained : Embrace Change',
				],
			];
			$this->saveData();
		}
	}

	protected function saveData()
	{
		$this->setViewState('Data', $this->_data);
	}

	protected function updateBook($isbn, $title)
	{
		// In real applications, data should be saved to database using an SQL UPDATE statement
		if ($this->_data === null) {
			$this->loadData();
		}
		$updateRow = null;
		foreach ($this->_data as $index => $row) {
			if ($row['ISBN'] === $isbn) {
				$updateRow = &$this->_data[$index];
			}
		}
		if ($updateRow !== null) {
			$updateRow['title'] = $title;
			$this->saveData();
		}
	}

	public function onLoad($param)
	{
		parent::onLoad($param);
		if (!$this->IsPostBack && !$this->IsCallBack) {
			$this->DataGrid->DataSource = $this->Data;
			$this->DataGrid->dataBind();
		}
	}

	public function editItem($sender, $param)
	{
		$this->DataGrid->EditItemIndex = $param->Item->ItemIndex;
		$this->DataGrid->DataSource = $this->Data;
		$this->DataGrid->dataBind();
	}

	public function saveItem($sender, $param)
	{
		$item = $param->Item;
		$this->updateBook(
			$this->DataGrid->DataKeys[$item->ItemIndex],    // ISBN
			$item->BookTitleColumn->TextBox->Text           // title
			);
		$this->DataGrid->EditItemIndex = -1;
		$this->DataGrid->DataSource = $this->Data;
		$this->DataGrid->dataBind();
	}

	public function cancelItem($sender, $param)
	{
		$this->DataGrid->EditItemIndex = -1;
		$this->DataGrid->DataSource = $this->Data;
		$this->DataGrid->dataBind();
	}
}
