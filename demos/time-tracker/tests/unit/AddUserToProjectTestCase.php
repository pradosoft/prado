<?php

require_once(dirname(__FILE__).'/ProjectDaoTestCase.php');

class AddUserToProjectTestCase extends ProjectDaoTestCase
{		
	function testCanAddNewUserToProject()
	{
		$project = $this->createNewTestProject();
		
		$user = new TimeTrackerUser();
		$user->ID = 3;
		$user->Name = "test user 1";
		
		if(($conn = $this->connection) instanceof MockTSqlMapper)
		{
			$this->setupMockConnectionFor($project);
			$conn->setReturnReference('queryForObject', $user, array('GetUserByName', $user->Name));
			$conn->setReturnValue('queryForList', array(), array('GetProjectMembers', $project));
			
			$param['project'] = $project;
			$param['user'] = $user;
			
			$conn->setReturnValue('insert', true, array('AddNewUserToProject', $param));
			
			$conn->expectAtLeastOnce('insert');
			$conn->expectAtLeastOnce('queryForList'); 
		}
		
		$this->assertTrue($this->dao->createNewProject($project));
		$this->assertTrue($this->dao->addUserToProject($project, $user));
	}
}

?>