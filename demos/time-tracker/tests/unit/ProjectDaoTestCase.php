<?php

//formerly PradoDaoTestCase.php

Prado::using('Application.APP_CODE.*');
Prado::using('System.DataAccess.SQLMap.TSqlMapper');

Mock::generate('TSqlMapper');

class ProjectDaoTestCase extends UnitTestCase
{
	protected $dao;
	protected $connection;
	
	function setup()
	{
		$this->dao= new ProjectDao();
		$this->connection = new MockTSqlMapper($this);
		$this->dao->setConnection($this->connection);
	}

/*
  	//Simple test case, will not detect project existanc
  	//This case will clash with the more complete test case below. 
	function testProjectDaoCanCreateNewProject()
	{
		$project = new Project();
		$project->Name = "Project 1";
 
		if(($conn = $this->connection) instanceof MockTSqlMapper)
		{
			$conn->expectOnce('insert', array('CreateNewProject', $project));
			$conn->setReturnValue('insert', true);
			
			$conn->expectOnce('queryForObject', array('GetProjectByID', 1));
			$conn->setReturnReference('queryForObject', $project);
		}
 
		$this->assertTrue($this->dao->createNewProject($project));		
		$this->assertEqual($this->dao->getProjectByID(1), $project);
	}
*/
	function setupMockConnectionFor($project)
	{
		$customer = new TimeTrackerUser();
		$customer->ID = 1;
		$customer->Name = "Customer A";
		
		$manager = new TimeTrackerUser();
		$manager->ID = 2;
		$manager->Name = "Manager A";
		
		$conn = $this->connection;
		
		//return the customer and manager
		$conn->setReturnValue('queryForObject', 
					$customer, array('GetUserByName', 'Customer A'));
		$conn->setReturnValue('queryForObject', 
					$manager, array('GetUserByName', 'Manager A'));
		
		//project does not exist
		$conn->setReturnValue('queryForObject', 
					null, array('GetProjectByName', 'Project 1'));
		
		$param['project'] = $project;
		$param['creator'] = $customer->ID;
		$param['manager'] = $manager->ID; 
				
		$conn->setReturnValue('insert', 
					true, array('CreateNewProject', $param));
		$conn->setReturnReference('queryForObject', 
					$project, array('GetProjectByID', 1));	
	}
	
	function createNewTestProject()
	{
		$project = new Project();
		$project->Name = "Project 1";
		$project->CreatorUserName = "Customer A";
		$project->ManagerUserName = "Manager A";
		
		return $project;
	}

	function assertProjectCreated($project)
	{
		$this->assertTrue($this->dao->createNewProject($project));		
		$this->assertEqual($this->dao->getProjectByID(1), $project);
	}
	
}
?>