<?php

class Ticket900 extends TPage
{
	public function onLoad($param)
	{
		parent::onLoad($param);
		if (!$this->IsPostBack) {
			$this->DataGrid->DataSource = $this->Data;
			$this->DataGrid->dataBind();
		}
	}
 
 
	protected function getData()
	{
		return [
		['title' => 'Title A'],
		['title' => 'Title B'],
		['title' => 'Title C']
	];
	}
 
 
	public function editItem($sender, $param)
	{
		$this->CommandName->Text = 'edit';
		$this->DataGrid->EditItemIndex = $param->Item->ItemIndex;
		$this->DataGrid->DataSource = $this->Data;
		$this->DataGrid->dataBind();
	}
 
	public function saveItem($sender, $param)
	{
		$this->CommandName->Text = 'save';
		$this->DataGrid->EditItemIndex = -1;
		$this->DataGrid->DataSource = $this->Data;
		$this->DataGrid->dataBind();
	}
 
	public function cancelItem($sender, $param)
	{
		$this->CommandName->Text = 'cancel';
		$this->DataGrid->EditItemIndex = -1;
		$this->DataGrid->DataSource = $this->Data;
		$this->DataGrid->dataBind();
	}
 
	public function deleteItem($sender, $param)
	{
		$this->CommandName->Text = 'delete';
		$this->DataGrid->EditItemIndex = -1;
		$this->DataGrid->DataSource = $this->Data;
		$this->DataGrid->dataBind();
	}
}
