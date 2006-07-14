<?php

//import Project class.
Prado::using('Application.APP_CODE.Project');

class ProjectTestCase extends UnitTestCase
{
	function testProjectClassExists()
	{
		$project = new Project();
		$this->pass();
	}
}

?>