import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=TextBoxConstraintTest';
const BASE = 'ctl0_Content_';

/**
 * Returns the browser's constraint validity flags for a value of the text box.
 */
async function validity(page, id, value) {
	return page.locator(`#${BASE}${id}`).evaluate((el, v) => {
		el.value = v;
		const s = el.validity;
		return { valid: s.valid, valueMissing: s.valueMissing, rangeUnderflow: s.rangeUnderflow,
			rangeOverflow: s.rangeOverflow, stepMismatch: s.stepMismatch, patternMismatch: s.patternMismatch, typeMismatch: s.typeMismatch };
	}, value);
}

/**
 * The constraint attributes render, and the browser enforces them.
 */
test('TTextBoxConstraintTestCase: browser enforces the constraints', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await h.assertSourceContains('TextBox Constraint Test Case');

	expect(await validity(page, 'quantity', '')).toMatchObject({ valid: false, valueMissing: true });
	expect(await validity(page, 'quantity', '4')).toMatchObject({ valid: true });
	expect(await validity(page, 'quantity', '0')).toMatchObject({ valid: false, rangeUnderflow: true });
	expect(await validity(page, 'quantity', '12')).toMatchObject({ valid: false, rangeOverflow: true });
	expect(await validity(page, 'quantity', '5')).toMatchObject({ valid: false, stepMismatch: true });

	expect(await validity(page, 'emails', 'a@example.com,b@example.com')).toMatchObject({ valid: true });
	expect(await validity(page, 'emails', 'a@other.org')).toMatchObject({ valid: false, patternMismatch: true });

	expect(await validity(page, 'meeting', '09:30')).toMatchObject({ valid: true });
	expect(await validity(page, 'meeting', '09:15')).toMatchObject({ valid: false, stepMismatch: true });
	expect(await validity(page, 'meeting', '18:00')).toMatchObject({ valid: false, rangeOverflow: true });
});

/**
 * The label names each text box; Required is exposed and the placeholder is not the name.
 */
test('TTextBoxConstraintTestCase: labels name the text boxes', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	const quantity = page.getByRole('spinbutton', { name: 'Quantity' });
	await expect(quantity).toHaveAttribute('id', `${BASE}quantity`);
	await expect(quantity).toHaveAttribute('placeholder', 'Even number');
	await expect(page.getByRole('textbox', { name: 'Emails' })).toHaveAttribute('multiple', 'multiple');
});

/**
 * Chromium's accessibility tree marks the required text box and names it by its label.
 */
test('TTextBoxConstraintTestCase: browser accessibility tree', async ({ page, browserName }) => {
	test.skip(browserName !== 'chromium', 'reads the Chromium accessibility tree over CDP');
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	const cdp = await page.context().newCDPSession(page);
	const { root } = await cdp.send('DOM.getDocument');
	const { nodeId } = await cdp.send('DOM.querySelector', { nodeId: root.nodeId, selector: `#${BASE}quantity` });
	const { nodes } = await cdp.send('Accessibility.getPartialAXTree', { nodeId, fetchRelatives: false });
	const props = Object.fromEntries((nodes[0].properties || []).map((p) => [p.name, p.value.value]));
	expect(nodes[0].role.value).toBe('spinbutton');
	expect(nodes[0].name.value).toBe('Quantity');
	expect(props.required).toBe(true);
	expect(props.valuemin).toBe(2);
	expect(props.valuemax).toBe(10);
});

/**
 * TRangeValidator with a Week data type and Step rejects an off-step week on the
 * client and accepts an on-step week on the server.
 */
test('TTextBoxConstraintTestCase: range validator checks week steps', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	await page.locator(`#${BASE}quantity`).fill('4');
	await page.locator(`#${BASE}sprint`).fill('2026-W04');
	await page.locator(`#${BASE}save`).click();
	await expect(page.locator(`#${BASE}sprintRange`)).toBeVisible();
	await expect(page.locator(`#${BASE}status`)).toHaveText('none');

	await page.locator(`#${BASE}sprint`).fill('2026-W05');
	await Promise.all([page.waitForNavigation(), page.locator(`#${BASE}save`).click()]);
	await expect(page.locator(`#${BASE}status`)).toHaveText('saved 2026-W05');
});
