<?php

require_once(dirname(__FILE__).'/common.php');
require_once(FRAMEWORK_DIR.'/TApplication.php');

class utApplication extends UnitTestCase
{
	public function testCreateApplication()
	{
		$dir=getcwd();
		chdir(dirname(__FILE__));
		$application=new TApplication(dirname(__FILE__).'/TestSystem/protected/application.xml');
		try
		{
			new TApplication(dirname(__FILE__).'/TestSystem/protected/application.xml');
		}
		catch(TInvalidOperationException $e)
		{
			$this->pass();
		}
		chdir($dir);
	}
}

?>