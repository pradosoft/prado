<?php
class Ticket700TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		// page: Home
		$this->url('tickets/index700.php');
		$this->clickAndWait('ctl0_Logout');
		$this->clickAndWait('pageHome');
		$this->assertEquals($this->title(), "Home");
		$this->assertTextPresent('|Param1: Set at app config|');
		$this->assertTextPresent('|Param2: Set at root|');
		$this->assertTextPresent('|Param3: default 3|');
		$this->assertTextPresent('|Param4: default 4|');
		$this->assertTextPresent('|Param5: Set at root|');

		// page: admin.Home
		$this->clickAndWait('pageAdminHome');
		$this->assertEquals($this->title(), 'UserLogin');
		$this->type('ctl0_Main_Username','AdminUser');
		$this->type('ctl0_Main_Password','demo');
		$this->clickAndWait('ctl0_Main_LoginButton');
		$this->clickAndWait('pageAdminHome');
		$this->assertEquals($this->title(), 'admin.Home');
		$this->assertTextPresent('|Param1: Set at app config|');
		$this->assertTextPresent('|Param2: Set at admin|');
		$this->assertTextPresent('|Param3: Set at admin|');
		$this->assertTextPresent('|Param4: Set at app config|');
		$this->assertTextPresent('|Param5: Set at app config|');

		// page: admin.Home2
		$this->clickAndWait('pageAdminHome2');
		$this->assertEquals($this->title(), 'admin.Home2');
		$this->clickAndWait('ctl0_Logout');
		$this->clickAndWait('pageAdminHome2');
		$this->assertEquals($this->title(), 'admin.Home2');

		// page: admin.users.Home
		$this->clickAndWait('pageAdminUsersHome');
		$this->assertEquals($this->title(), 'UserLogin');
		$this->type('ctl0_Main_Username','NormalUser');
		$this->type('ctl0_Main_Password','demo');
		$this->clickAndWait('ctl0_Main_LoginButton');
		$this->clickAndWait('pageAdminUsersHome');
		$this->assertEquals($this->title(), 'UserLogin');
		$this->type('ctl0_Main_Username','AdminUser');
		$this->type('ctl0_Main_Password','demo');
		$this->clickAndWait('ctl0_Main_LoginButton');
		$this->clickAndWait('pageAdminUsersHome');
		$this->assertEquals($this->title(), 'admin.users.Home');
		$this->assertTextPresent('|Param1: Set at admin|');
		$this->assertTextPresent('|Param2: Set at admin.users|');
		$this->assertTextPresent('|Param3: default 3|');
		$this->assertTextPresent('|Param4: Set at admin|');
		$this->assertTextPresent('|Param5: Set at app config|');

		// page: admin.users.Home2
		$this->clickAndWait('pageAdminUsersHome2');
		$this->assertEquals($this->title(), 'admin.users.Home2');

		// page: content.Home
		$this->clickAndWait('pageContentHome');
		$this->assertEquals($this->title(), 'content.Home');
		$this->assertTextPresent('|Param1: Set at app config|');
		$this->assertTextPresent('|Param2: Set at root|');
		$this->assertTextPresent('|Param3: default 3|');
		$this->assertTextPresent('|Param4: default 4|');
		$this->assertTextPresent('|Param5: Set at app config|');
		$this->clickAndWait('ctl0_Logout');
	}
}