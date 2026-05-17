import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket463TestCase', async ({ page }) => {
	const h = genericHelper(page);
	await h.url('tickets/index.php?page=Ticket463');
	await h.assertTitle('Verifying Ticket 463');
	// clean the output from UTF8-encoded spaces
	// it has been noted that the date can contain characters
	// such as Narrow no-break space (U+202F) as separator between
	// the time and the AM/PM suffix
	const source = await h.source();
	const cleanSource = source.replace(/\s+/gu, ' ');
	expect(cleanSource).toContain('May 1, 2005 at 12:00:00 AM');
});
