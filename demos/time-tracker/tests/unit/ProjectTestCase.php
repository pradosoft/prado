<?php

//import Project class.
Prado::using('Application.APP_CODE.Project');

require_once(dirname(__FILE__).'/BaseTestCase.php');

class ProjectTestCase extends BaseTestCase
{
	function testProjectClassExists()
	{
		$project = new Project();
		$this->pass();
	}
	
	
}

?>