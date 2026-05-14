import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket700TestCase', async ({ page }) => {
	const h = genericHelper(page);

	// page: Home
	await h.url('tickets/index700.php');
	await h.byId('ctl0_Logout').click();
	await h.pause(50);
	await h.byId('pageHome').click();
	await h.pause(50);
	await h.assertTitle('Home');
	await h.assertSourceContains('|Param1: Set at app config|');
	await h.assertSourceContains('|Param2: Set at root|');
	await h.assertSourceContains('|Param3: default 3|');
	await h.assertSourceContains('|Param4: default 4|');
	await h.assertSourceContains('|Param5: Set at root|');

	// page: admin.Home
	await h.byId('pageAdminHome').click();
	await h.pause(50);
	await h.assertTitle('UserLogin');
	await h.type('ctl0_Main_Username', 'AdminUser');
	await h.type('ctl0_Main_Password', 'demo');
	await h.byId('ctl0_Main_LoginButton').click();
	await h.pause(50);
	await h.byId('pageAdminHome').click();
	await h.pause(50);
	await h.assertTitle('admin.Home');
	await h.assertSourceContains('|Param1: Set at app config|');
	await h.assertSourceContains('|Param2: Set at admin|');
	await h.assertSourceContains('|Param3: Set at admin|');
	await h.assertSourceContains('|Param4: Set at app config|');
	await h.assertSourceContains('|Param5: Set at app config|');

	// page: admin.Home2
	await h.byId('pageAdminHome2').click();
	await h.pause(50);
	await h.assertTitle('admin.Home2');
	await h.byId('ctl0_Logout').click();
	await h.pause(50);
	await h.byId('pageAdminHome2').click();
	await h.pause(50);
	await h.assertTitle('admin.Home2');

	// page: admin.users.Home
	await h.byId('pageAdminUsersHome').click();
	await h.pause(50);
	await h.assertTitle('UserLogin');
	await h.type('ctl0_Main_Username', 'NormalUser');
	await h.type('ctl0_Main_Password', 'demo');
	await h.byId('ctl0_Main_LoginButton').click();
	await h.pause(50);
	await h.byId('pageAdminUsersHome').click();
	await h.pause(50);
	await h.assertTitle('UserLogin');
	await h.type('ctl0_Main_Username', 'AdminUser');
	await h.type('ctl0_Main_Password', 'demo');
	await h.byId('ctl0_Main_LoginButton').click();
	await h.pause(50);
	await h.byId('pageAdminUsersHome').click();
	await h.pause(50);
	await h.assertTitle('admin.users.Home');
	await h.assertSourceContains('|Param1: Set at admin|');
	await h.assertSourceContains('|Param2: Set at admin.users|');
	await h.assertSourceContains('|Param3: default 3|');
	await h.assertSourceContains('|Param4: Set at admin|');
	await h.assertSourceContains('|Param5: Set at app config|');

	// page: admin.users.Home2
	await h.byId('pageAdminUsersHome2').click();
	await h.pause(50);
	await h.assertTitle('admin.users.Home2');

	// page: content.Home
	await h.byId('pageContentHome').click();
	await h.pause(50);
	await h.assertTitle('content.Home');
	await h.assertSourceContains('|Param1: Set at app config|');
	await h.assertSourceContains('|Param2: Set at root|');
	await h.assertSourceContains('|Param3: default 3|');
	await h.assertSourceContains('|Param4: default 4|');
	await h.assertSourceContains('|Param5: Set at app config|');
	await h.byId('ctl0_Logout').click();
});
