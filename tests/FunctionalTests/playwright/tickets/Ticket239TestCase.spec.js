import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket239TestCase', async ({ page }) => {
	const h = genericHelper(page);
	await h.url('tickets/index.php?page=Ticket239');

	// view1
	await h.assertSourceContains('view1 is activated');
	await h.assertSourceNotContains('view1 is deactivated');
	await h.assertSourceNotContains('view2 is activated');
	await h.assertSourceNotContains('view2 is deactivated');
	await h.assertSourceNotContains('view3 is activated');
	await h.assertSourceNotContains('view3 is deactivated');

	// goto view2
	await h.byName('ctl0$Content$ctl1').click();
	await h.assertSourceNotContains('view1 is activated');
	await h.assertSourceContains('view1 is deactivated');
	await h.assertSourceContains('view2 is activated');
	await h.assertSourceNotContains('view2 is deactivated');
	await h.assertSourceNotContains('view3 is activated');
	await h.assertSourceNotContains('view3 is deactivated');

	// goto view3
	await h.byName('ctl0$Content$ctl3').click();
	await h.assertSourceNotContains('view1 is activated');
	await h.assertSourceNotContains('view1 is deactivated');
	await h.assertSourceNotContains('view2 is activated');
	await h.assertSourceContains('view2 is deactivated');
	await h.assertSourceContains('view3 is activated');
	await h.assertSourceNotContains('view3 is deactivated');

	// goto view2
	await h.byName('ctl0$Content$ctl4').click();
	await h.assertSourceNotContains('view1 is activated');
	await h.assertSourceNotContains('view1 is deactivated');
	await h.assertSourceContains('view2 is activated');
	await h.assertSourceNotContains('view2 is deactivated');
	await h.assertSourceNotContains('view3 is activated');
	await h.assertSourceContains('view3 is deactivated');

	// goto view1
	await h.byName('ctl0$Content$ctl2').click();
	await h.assertSourceContains('view1 is activated');
	await h.assertSourceNotContains('view1 is deactivated');
	await h.assertSourceNotContains('view2 is activated');
	await h.assertSourceContains('view2 is deactivated');
	await h.assertSourceNotContains('view3 is activated');
	await h.assertSourceNotContains('view3 is deactivated');
});
