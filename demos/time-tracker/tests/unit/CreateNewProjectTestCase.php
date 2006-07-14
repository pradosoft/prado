<?php

require_once(dirname(__FILE__).'/ProjectDaoTestCase.php');

class CreateNewProjectTestCase extends ProjectDaoTestCase
{
	function testProjectDaoCanCreateNewProject()
	{
		$project = $this->createNewTestProject();
	
		if(($conn = $this->connection) instanceof MockTSqlMapper)
		{
			$this->setupMockConnectionFor($project);
			$conn->expectMinimumCallCount('queryForObject', 3);
			$conn->expectAtLeastOnce('insert');
		}
		
		$this->assertProjectCreated($project);	
	}
	
	function testProjectExistsException()
	{
		$project = $this->createNewTestProject();
		
		if(($conn = $this->connection) instanceof MockTSqlMapper)
		{
			//make the project exist
			$conn->setReturnValue('queryForObject', 
					$project, array('GetProjectByName', 'Project 1'));
			$this->setupMockConnectionFor($project);
		}
		
		try
		{
			$this->assertProjectCreated($project);		
			$this->fail();
		}
		catch(TimeTrackerException $e)
		{
			$this->pass();
		}
	}
	function testProjectCustomerNotExistsException()
	{
		$project = $this->createNewTestProject();
		
		if(($conn = $this->connection) instanceof MockTSqlMapper)
		{
			//customer does not exist
			$conn->setReturnValue('queryForObject', 
					null, array('GetUserByName', 'Customer A'));
			$this->setupMockConnectionFor($project);	
		}
		
		try
		{
			$this->assertProjectCreated($project);
			$this->fail();
		}
		catch(TimeTrackerException $e)
		{
			$this->pass();
		}
	}
	
	function testProjectManagerNotExistsException()
	{
		$project = $this->createNewTestProject();
		
		if(($conn = $this->connection) instanceof MockTSqlMapper)
		{
			//manager does not exist
			$conn->setReturnValue('queryForObject', 
					null, array('GetUserByName', 'Manager A'));
			$this->setupMockConnectionFor($project);
		}
		
		try
		{
			$this->assertProjectCreated($project);
			$this->fail();
		}
		catch(TimeTrackerException $e)
		{
			$this->pass();
		}
	}
}

?>