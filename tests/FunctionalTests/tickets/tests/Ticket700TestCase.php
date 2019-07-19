<?php

class Ticket700TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		// page: Home
		$this->url('tickets/index700.php');
		$this->byId('ctl0_Logout')->click();
		$this->pause(50);
		$this->byId('pageHome')->click();
		$this->assertTitleEquals('Home');
		$this->assertSourceContains('|Param1: Set at app config|');
		$this->assertSourceContains('|Param2: Set at root|');
		$this->assertSourceContains('|Param3: default 3|');
		$this->assertSourceContains('|Param4: default 4|');
		$this->assertSourceContains('|Param5: Set at root|');

		// page: admin.Home
		$this->byId('pageAdminHome')->click();
		$this->assertTitleEquals('UserLogin');
		$this->type('ctl0_Main_Username', 'AdminUser');
		$this->type('ctl0_Main_Password', 'demo');
		$this->byId('ctl0_Main_LoginButton')->click();
		$this->pause(50);
		$this->byId('pageAdminHome')->click();
		$this->assertTitleEquals('admin.Home');
		$this->assertSourceContains('|Param1: Set at app config|');
		$this->assertSourceContains('|Param2: Set at admin|');
		$this->assertSourceContains('|Param3: Set at admin|');
		$this->assertSourceContains('|Param4: Set at app config|');
		$this->assertSourceContains('|Param5: Set at app config|');

		// page: admin.Home2
		$this->byId('pageAdminHome2')->click();
		$this->assertTitleEquals('admin.Home2');
		$this->byId('ctl0_Logout')->click();
		$this->pause(50);
		$this->byId('pageAdminHome2')->click();
		$this->assertTitleEquals('admin.Home2');

		// page: admin.users.Home
		$this->byId('pageAdminUsersHome')->click();
		$this->assertTitleEquals('UserLogin');
		$this->type('ctl0_Main_Username', 'NormalUser');
		$this->type('ctl0_Main_Password', 'demo');
		$this->byId('ctl0_Main_LoginButton')->click();
		$this->pause(50);
		$this->byId('pageAdminUsersHome')->click();
		$this->assertTitleEquals('UserLogin');
		$this->type('ctl0_Main_Username', 'AdminUser');
		$this->type('ctl0_Main_Password', 'demo');
		$this->byId('ctl0_Main_LoginButton')->click();
		$this->pause(50);
		$this->byId('pageAdminUsersHome')->click();
		$this->assertTitleEquals('admin.users.Home');
		$this->assertSourceContains('|Param1: Set at admin|');
		$this->assertSourceContains('|Param2: Set at admin.users|');
		$this->assertSourceContains('|Param3: default 3|');
		$this->assertSourceContains('|Param4: Set at admin|');
		$this->assertSourceContains('|Param5: Set at app config|');

		// page: admin.users.Home2
		$this->byId('pageAdminUsersHome2')->click();
		$this->assertTitleEquals('admin.users.Home2');

		// page: content.Home
		$this->byId('pageContentHome')->click();
		$this->assertTitleEquals('content.Home');
		$this->assertSourceContains('|Param1: Set at app config|');
		$this->assertSourceContains('|Param2: Set at root|');
		$this->assertSourceContains('|Param3: default 3|');
		$this->assertSourceContains('|Param4: default 4|');
		$this->assertSourceContains('|Param5: Set at app config|');
		$this->byId('ctl0_Logout')->click();
	}

	public function assertTitleEquals($title)
	{
		$this->pause(50);
		$this->assertEquals($this->title(), $title);
	}
}
