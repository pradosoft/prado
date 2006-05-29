<?php

class NewUser extends BlogPage
{
	public function checkUsername($sender,$param)
	{
		$username=$this->Username->Text;
		$param->IsValid=$this->DataAccess->queryUserByName($username)===null;
	}

	public function createUser($sender,$param)
	{
		if($this->IsValid)
		{
			$userRecord=new UserRecord;
			$userRecord->Name=$this->Username->Text;
			$userRecord->FullName=$this->FullName->Text;
			$userRecord->Role=0;
			$userRecord->Password=md5($this->Password->Text);
			$userRecord->Email=$this->Email->Text;
			$userRecord->CreateTime=time();
			$userRecord->Website=$this->Website->Text;
			$this->DataAccess->insertUser($userRecord);
			$authManager=$this->Application->getModule('auth');
			$authManager->login($this->Username->Text,$this->Password->Text);
			$this->gotoDefaultPage();
		}
	}
}

?>