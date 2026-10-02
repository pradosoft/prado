import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

/**
 * End-to-end checks of the anti-bot controls: TProofOfWork, TFormGuard, and TCaptcha.
 * The AntiBot harness folder configures a file cache, so solutions and tokens are single-use.
 * A replay posts a captured form body again, as a bot reusing a solved page would.
 */

const POW_URL = 'web/index.php?page=AntiBot.ProofOfWorkTest';
const GUARD_URL = 'web/index.php?page=AntiBot.FormGuardTest';
const CAPTCHA_URL = 'web/index.php?page=AntiBot.CaptchaTest';

/** Clicks Send and returns the posted form body. */
async function sendAndCapture(page) {
	const posted = page.waitForRequest((request) => request.method() === 'POST');
	await page.locator('#ctl0_Content_Send').click();
	const body = (await posted).postData();
	await page.waitForLoadState('load');
	return body;
}

/** Posts a captured form body again and returns the response text. */
async function replay(page, url, body) {
	const response = await page.request.post(GENERIC_BASE_URL + url, {
		headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
		data: body
	});
	return response.text();
}

test('TProofOfWork: status region solves after focus and the solution is accepted once', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(POW_URL);
	await h.assertSourceContains('Proof of Work Test');

	const region = page.locator('#ctl0_Content_Work');
	await expect(region).toHaveAttribute('role', 'status');
	await expect(region).toHaveAttribute('aria-busy', 'false');
	await expect(region.locator('noscript')).toHaveCount(1);

	await page.locator('#ctl0_Content_Name').focus();
	await expect(region).toHaveText('Verified');
	await expect(region).toHaveAttribute('aria-busy', 'false');

	const body = await sendAndCapture(page);
	await expect(page.locator('#ctl0_Content_Result')).toHaveText('Accepted');
	expect(await replay(page, POW_URL, body)).toContain('Rejected');
});

test('TProofOfWork: a submission before the solution is ready waits for it', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(POW_URL);
	const region = page.locator('#ctl0_Content_Work');
	const before = await page.locator('#ctl0_Content_Send').boundingBox();
	await page.locator('#ctl0_Content_Send').click();
	await expect(page.locator('#ctl0_Content_Result')).toHaveText('Accepted');
	await expect(region).toHaveAttribute('aria-busy', 'false');
	const after = await page.locator('#ctl0_Content_Send').boundingBox();
	expect(after.y).toBe(before.y);
});

test('TFormGuard: rejects a fast submission and a filled honeypot, accepts a person', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	const result = page.locator('#ctl0_Content_Result');

	await h.url(GUARD_URL);
	await page.locator('#ctl0_Content_Send').click();
	await expect(result).toHaveText('Rejected: TooFast');

	await h.url(GUARD_URL);
	await page.locator('#ctl0_Content_Guard_hp').evaluate((input) => { input.value = 'https://spam.example'; });
	await page.waitForTimeout(2100);
	await page.locator('#ctl0_Content_Send').click();
	await expect(result).toHaveText('Rejected: Honeypot');

	await h.url(GUARD_URL);
	await page.locator('#ctl0_Content_Comment').fill('Hello');
	await page.waitForTimeout(2100);
	await page.locator('#ctl0_Content_Send').click();
	await expect(result).toHaveText('Accepted');
});

test('TFormGuard: the honeypot is hidden from assistive technology and the tab order', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(GUARD_URL);

	const honeypot = page.locator('#ctl0_Content_Guard_hp');
	await expect(honeypot).toHaveAttribute('tabindex', '-1');
	await expect(honeypot.locator('xpath=..')).toHaveAttribute('aria-hidden', 'true');
	await expect(page.getByRole('textbox', { name: 'Leave this field empty' })).toHaveCount(0);

	await page.locator('#ctl0_Content_Comment').focus();
	await page.keyboard.press('Tab');
	await expect(honeypot).not.toBeFocused();
});

test('TCaptcha: the image loads with alt text and a solved token passes once', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(CAPTCHA_URL);

	const image = page.locator('#ctl0_Content_Captcha');
	await expect(image).toHaveAttribute('alt', 'CAPTCHA image: type the characters shown');
	const response = await page.request.get(new URL(await image.getAttribute('src'), page.url()).href);
	expect(response.status()).toBe(200);
	expect(response.headers()['content-type']).toBe('image/png');
	expect(await image.evaluate((img) => img.complete && img.naturalWidth > 0)).toBe(true);

	await page.locator('#ctl0_Content_Answer').fill('wrong');
	await page.locator('#ctl0_Content_Send').click();
	await expect(page.locator('#ctl0_Content_Result')).toHaveText('Rejected');

	await page.locator('#ctl0_Content_Answer').fill(await page.locator('#ctl0_Content_Token').innerText());
	const body = await sendAndCapture(page);
	await expect(page.locator('#ctl0_Content_Result')).toHaveText('Accepted');
	expect(await replay(page, CAPTCHA_URL, body)).toContain('Rejected');
});
