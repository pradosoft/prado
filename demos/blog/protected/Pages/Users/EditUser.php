<?php

class EditUser extends BlogPage
{
	public function getCurrentUser()
	{
		if(($user=$this->DataAccess->queryUserByID($this->User->ID))!==null)
			return $user;
		else
			throw new BlogException('xxx');
	}

	public function onLoad($param)
	{
		parent::onLoad($param);
		if(!$this->IsPostBack)
		{
			$userRecord=$this->getCurrentUser();
			$this->Username->Text=$userRecord->Name;
			$this->FullName->Text=$userRecord->FullName;
			$this->Email->Text=$userRecord->Email;
			$this->Website->Text=$userRecord->Website;
		}
	}

	public function saveButtonClicked($sender,$param)
	{
		if($this->IsValid)
		{
			$userRecord=$this->getCurrentUser();
			if($this->Password->Text!=='')
				$userRecord->Password=md5($this->Password->Text);
			$userRecord->FullName=$this->FullName->Text;
			$userRecord->Email=$this->Email->Text;
			$userRecord->Website=$this->Website->Text;
			$this->DataAccess->updateUser($userRecord);
			$authManager=$this->Application->getModule('auth');
			$this->gotoPage('Users.ViewUser',array('id'=>$userRecord->ID));
		}
	}
}

?>