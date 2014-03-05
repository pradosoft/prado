<?php
class Ticket700TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		// page: Home
		$this->url('tickets/index700.php');
		$this->byId('ctl0_Logout')->click();
		$this->byId('pageHome')->click();
		$this->assertEquals($this->title(), "Home");
		$this->assertContains('|Param1: Set at app config|', $this->source());
		$this->assertContains('|Param2: Set at root|', $this->source());
		$this->assertContains('|Param3: default 3|', $this->source());
		$this->assertContains('|Param4: default 4|', $this->source());
		$this->assertContains('|Param5: Set at root|', $this->source());

		// page: admin.Home
		$this->byId('pageAdminHome')->click();
		$this->assertEquals($this->title(), 'UserLogin');
		$this->type('ctl0_Main_Username','AdminUser');
		$this->type('ctl0_Main_Password','demo');
		$this->byId('ctl0_Main_LoginButton')->click();
		$this->byId('pageAdminHome')->click();
		$this->assertEquals($this->title(), 'admin.Home');
		$this->assertContains('|Param1: Set at app config|', $this->source());
		$this->assertContains('|Param2: Set at admin|', $this->source());
		$this->assertContains('|Param3: Set at admin|', $this->source());
		$this->assertContains('|Param4: Set at app config|', $this->source());
		$this->assertContains('|Param5: Set at app config|', $this->source());

		// page: admin.Home2
		$this->byId('pageAdminHome2')->click();
		$this->assertEquals($this->title(), 'admin.Home2');
		$this->byId('ctl0_Logout')->click();
		$this->byId('pageAdminHome2')->click();
		$this->assertEquals($this->title(), 'admin.Home2');

		// page: admin.users.Home
		$this->byId('pageAdminUsersHome')->click();
		$this->assertEquals($this->title(), 'UserLogin');
		$this->type('ctl0_Main_Username','NormalUser');
		$this->type('ctl0_Main_Password','demo');
		$this->byId('ctl0_Main_LoginButton')->click();
		$this->byId('pageAdminUsersHome')->click();
		$this->assertEquals($this->title(), 'UserLogin');
		$this->type('ctl0_Main_Username','AdminUser');
		$this->type('ctl0_Main_Password','demo');
		$this->byId('ctl0_Main_LoginButton')->click();
		$this->byId('pageAdminUsersHome')->click();
		$this->assertEquals($this->title(), 'admin.users.Home');
		$this->assertContains('|Param1: Set at admin|', $this->source());
		$this->assertContains('|Param2: Set at admin.users|', $this->source());
		$this->assertContains('|Param3: default 3|', $this->source());
		$this->assertContains('|Param4: Set at admin|', $this->source());
		$this->assertContains('|Param5: Set at app config|', $this->source());

		// page: admin.users.Home2
		$this->byId('pageAdminUsersHome2')->click();
		$this->assertEquals($this->title(), 'admin.users.Home2');

		// page: content.Home
		$this->byId('pageContentHome')->click();
		$this->assertEquals($this->title(), 'content.Home');
		$this->assertContains('|Param1: Set at app config|', $this->source());
		$this->assertContains('|Param2: Set at root|', $this->source());
		$this->assertContains('|Param3: default 3|', $this->source());
		$this->assertContains('|Param4: default 4|', $this->source());
		$this->assertContains('|Param5: Set at app config|', $this->source());
		$this->byId('ctl0_Logout')->click();
	}
}