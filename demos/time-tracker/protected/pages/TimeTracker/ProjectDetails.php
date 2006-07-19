<?php

class ProjectDetails extends TPage
{
	private $allUsers = null;
	
	public function onLoad($param)
	{
		if(!$this->IsPostBack)
		{
			$this->manager->DataSource = $this->getUsersWithRole('manager');
			$this->manager->dataBind();
			$this->members->DataSource = $this->getUsersWithRole('consultant');
			$this->members->dataBind();
		}
	}
	
	protected function getUsersWithRole($role)
	{
		if(is_null($this->allUsers))
		{
			$dao = $this->Application->Modules['daos']->getDao('UserDao');
			$this->allUsers = $dao->getAllUsers();		
		}
		$users = array();
		foreach($this->allUsers as $user)
		{
			if($user->isInRole($role))
				$users[] = $user->Name;
		}
		return $users;
	}
}

?>