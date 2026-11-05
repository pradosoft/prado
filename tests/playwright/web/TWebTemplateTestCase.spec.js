import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=WebTemplateTest';

test('TWebTemplateTestCase', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await h.assertSourceContains('Web Template Test Case');

	// ── The template renders but its content stays inert ──
	await expect(page.locator('template#ctl0_Content_rowTemplate')).toHaveCount(1);
	// .row lives in the template's DocumentFragment, so it is not in the document
	await expect(page.locator('#listBody .row')).toHaveCount(0);
	await expect(page.locator('body > .row')).toHaveCount(0);

	// ── The client-side wrapper is registered under the ClientID ──
	const registered = await page.evaluate(
		() => typeof Prado.WebUI.TWebTemplate.get('ctl0_Content_rowTemplate') === 'object'
	);
	expect(registered).toBe(true);

	// ── Stamping a single copy with data substitution ──
	await page.locator('#btnStampOne').click();
	await expect(page.locator('#listBody .row')).toHaveCount(1);
	await expect(page.locator('#listBody .name')).toHaveText('Ada');
	// placeholders substitute inside attribute values too
	await expect(page.locator('#listBody .link')).toHaveAttribute('href', '/user/7');

	// ── repeatInto() replaces the content with one copy per item ──
	await page.locator('#btnStampMany').click();
	await expect(page.locator('#listBody .row')).toHaveCount(3);
	await expect(page.locator('#listBody .name')).toHaveText(['Ada', 'Grace', 'Alan']);
	await expect(page.locator('#listBody .link').nth(2)).toHaveAttribute('href', '/user/3');

	// ── The template content itself is never consumed by stamping ──
	const stillPlaceholder = await page.evaluate(() =>
		document.getElementById('ctl0_Content_rowTemplate').content.querySelector('.name').textContent
	);
	expect(stillPlaceholder).toBe('{{name}}');

	// ── attachShadowTo() stamps into a real shadow root ──
	await page.locator('#btnStampShadow').click();
	const shadowText = await page.evaluate(
		() => document.getElementById('shadowHost').shadowRoot.querySelector('.name').textContent
	);
	expect(shadowText).toBe('Shadowed');
});
