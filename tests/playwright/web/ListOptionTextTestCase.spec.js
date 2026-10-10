import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=ListOptionTextTest';
const LIST = 'ctl0_Content_list';

/**
 * Returns the [value, text, child element count] of each option of a select.
 */
async function selectOptions(page, id) {
	return page.evaluate((listId) => [...document.getElementById(listId).options]
		.map((option) => [option.value, option.textContent, option.children.length]), id);
}

/**
 * Option text set during a callback matches the page render: markup shows as
 * text and runs nothing, and entities decode.
 */
test('ListOptionTextTestCase: callback option text matches the page render', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await h.assertSourceContains('List Option Text Test Case');

	const expected = [
		['m', '<img src=x onerror="window.__optionXss=1">', 0],
		['b', '<b>Bold</b>', 0],
		['amp', 'Tom & Jerry', 0],
		['raw', 'Tom & Jerry', 0],
		['nbsp', '  Child', 0],
		['lt', '<i>', 0],
	];
	const rendered = await selectOptions(page, LIST);
	expect(rendered).toEqual(expected);

	await h.click('ctl0_Content_btnReload');
	await h.waitForAjaxCalls();
	await expect(page.locator('#ctl0_Content_status')).toHaveText('reloaded');

	expect(await selectOptions(page, LIST)).toEqual(rendered);
	await expect(page.locator(`#${LIST} img, #${LIST} b, #${LIST} i`)).toHaveCount(0);
	expect(await page.evaluate(() => window.__optionXss)).toBeUndefined();
});
