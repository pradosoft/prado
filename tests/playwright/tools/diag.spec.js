import { test } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

test('diag', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url('web/index.php?page=ColorSchemeGalleryTest');
	const info = await page.evaluate(() => {
		const kb = document.querySelector('div.Keyboard');
		const line = kb.querySelector('div.Line');
		const keys = line.querySelectorAll('div.Key');
		const cs = getComputedStyle(kb);
		return {
			kbRect: kb.getBoundingClientRect().toJSON(),
			kbPosition: cs.position,
			kbVisibility: cs.visibility,
			kbOverflow: cs.overflow,
			lineRect: line.getBoundingClientRect().toJSON(),
			lineCount: kb.querySelectorAll('div.Line').length,
			keysInFirstLine: keys.length,
			lastKeyRight: keys.length ? keys[keys.length - 1].getBoundingClientRect().right : null,
			firstKeyLeft: keys.length ? keys[0].getBoundingClientRect().left : null,
		};
	});
	console.log(JSON.stringify(info, null, 1));
});
