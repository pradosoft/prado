<?php

class UserMan extends BlogPage
{
	protected function bindData()
	{
		$author=$this->User->ID;
		$offset=$this->UserGrid->CurrentPageIndex*$this->UserGrid->PageSize;
		$limit=$this->UserGrid->PageSize;
		$this->UserGrid->DataSource=$this->DataAccess->queryUsers('','ORDER BY status DESC, name ASC',"LIMIT $offset,$limit");
		$this->UserGrid->VirtualItemCount=$this->DataAccess->queryUserCount('');
		$this->UserGrid->dataBind();
	}

	public function onLoad($param)
	{
		parent::onLoad($param);
		if(!$this->IsPostBack)
			$this->bindData();
	}

	public function changePage($sender,$param)
	{
		$this->UserGrid->CurrentPageIndex=$param->NewPageIndex;
		$this->bindData();
	}

	public function pagerCreated($sender,$param)
	{
		$param->Pager->Controls->insertAt(0,'Page: ');
	}

	public function editItem($sender,$param)
	{
		$this->UserGrid->EditItemIndex=$param->Item->ItemIndex;
		$this->bindData();
	}

	public function saveItem($sender,$param)
	{
		$item=$param->Item;
		$userID=$this->UserGrid->DataKeys[$item->ItemIndex];
		$userRecord=$this->DataAccess->queryUserByID($userID);
		$userRecord->Role=TPropertyValue::ensureInteger($item->Cells[1]->UserRole->SelectedValue);
		$userRecord->Status=TPropertyValue::ensureInteger($item->Cells[2]->UserStatus->SelectedValue);
		$this->DataAccess->updateUser($userRecord);
		$this->UserGrid->EditItemIndex=-1;
		$this->bindData();
	}

	public function cancelItem($sender,$param)
	{
		$this->UserGrid->EditItemIndex=-1;
		$this->bindData();
	}
}

?>