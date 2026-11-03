import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

const PAGE_URL = 'validators/index.php?page=PasswordStrengthValidator';
const base = 'ctl0_Content_';

async function open(page) {
	const h = genericHelper(page);
	await h.url(PAGE_URL);
	await h.assertSourceContains('Prado PasswordStrengthValidator Tests');
	return h;
}

/**
 * The client validator estimates the entropy and requires Medium by default.
 */
test('PasswordStrengthValidatorTestCase: client validation', async ({ page }) => {
	const h = await open(page);
	const input = page.locator(`#${base}text1`);
	await h.assertNotVisible(`${base}validator1`);

	for (const value of ['password', 'Password1', '密码密码密码']) {
		await input.fill(value);
		await page.locator(`#${base}submit1`).click();
		await h.assertVisible(`${base}validator1`);
		await expect(input).toHaveAttribute('aria-invalid', 'true');
		await expect(input).toHaveClass(/required/);
	}

	for (const value of ['Password1!', 'correct horse battery staple', 'Ünïcödé-Pässwörd']) {
		await input.fill(value);
		await input.dispatchEvent('change');
		await h.assertNotVisible(`${base}validator1`);
		await expect(input).toHaveAttribute('aria-invalid', 'false');
	}
});

/**
 * Relative luminance contrast of two "rgb(r, g, b)" colors.
 */
function contrast(a, b) {
	const lum = (rgb) => {
		const [r, g, bl] = rgb.match(/\d+(\.\d+)?/g).slice(0, 3).map(Number).map((c) => {
			c /= 255;
			return c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4;
		});
		return 0.2126 * r + 0.7152 * g + 0.0722 * bl;
	};
	const [hi, lo] = [lum(a), lum(b)].sort((x, y) => y - x);
	return (hi + 0.05) / (lo + 0.05);
}

/**
 * The meter fills notches and shifts from red to green as the password grows,
 * names the level, and keeps a 3:1 contrast against the page.
 */
test('PasswordStrengthValidatorTestCase: strength meter', async ({ page }) => {
	await open(page);
	const input = page.locator(`#${base}text1`);
	const meter = page.getByRole('meter', { name: 'Password strength' });
	await expect(meter).toHaveAttribute('aria-valuenow', '0');
	await expect(page.locator(`#${base}meter1 .prado-password-meter-notch`)).toHaveCount(20);

	// Computed colors may be oklch(); a canvas pixel converts them to sRGB.
	const read = () => page.evaluate((id) => {
		const toRgb = (color) => {
			const ctx = document.createElement('canvas').getContext('2d');
			ctx.fillStyle = color;
			ctx.fillRect(0, 0, 1, 1);
			const [r, g, b, a] = ctx.getImageData(0, 0, 1, 1).data;
			return a === 0 ? 'rgb(255, 255, 255)' : `rgb(${r}, ${g}, ${b})`;
		};
		const el = document.getElementById(id);
		const lit = el.querySelectorAll('.prado-password-meter-notch.lit');
		return {
			lit: lit.length,
			color: lit.length ? toRgb(getComputedStyle(lit[0]).backgroundColor) : null,
			text: el.querySelector('.prado-password-meter-text').textContent,
			valuetext: el.getAttribute('aria-valuetext'),
			page: toRgb(getComputedStyle(document.body).backgroundColor),
		};
	}, `${base}meter1`);

	const steps = [];
	for (const value of ['pass', 'password', 'Password1', 'Password1!', 'kX9#mQ2$vL7!pZ', 'correct horse battery staple']) {
		await input.fill(value);
		steps.push(await read());
	}
	expect(steps.map((s) => s.text)).toEqual(['Very weak', 'Very weak', 'Weak', 'Medium', 'Strong', 'Very strong']);
	expect(steps.map((s) => s.valuetext)).toEqual(steps.map((s) => s.text));
	for (let i = 1; i < steps.length; i++) {
		expect(steps[i].lit).toBeGreaterThan(steps[i - 1].lit);
	}
	const rgb = (s) => s.color.match(/\d+/g).map(Number);
	const [r0, g0] = rgb(steps[0]);
	const [r5, g5] = rgb(steps[5]);
	expect(r0).toBeGreaterThan(g0);
	expect(g5).toBeGreaterThan(r5);
	expect(steps[5].lit).toBe(20);
	for (const step of steps) {
		expect(contrast(step.color, step.page)).toBeGreaterThanOrEqual(3);
	}

	await input.fill('');
	await expect(page.locator(`#${base}meter1 .prado-password-meter-notch.lit`)).toHaveCount(0);
});

/**
 * A validator without client script checks the strength on the postback.
 */
test('PasswordStrengthValidatorTestCase: server validation', async ({ page }) => {
	const h = await open(page);
	await page.locator(`#${base}text2`).fill('kX9#mQ2$vL7!');
	await page.locator(`#${base}submit1`).click();
	await expect(page.locator(`#${base}status`)).toHaveText('Page invalid');
	await h.assertVisible(`${base}validator2`);

	await page.locator(`#${base}text2`).fill('kX9#mQ2$vL7!pZ');
	await page.locator(`#${base}submit1`).click();
	await expect(page.locator(`#${base}status`)).toHaveText('Page valid');
	await h.assertNotVisible(`${base}validator2`);
});

/**
 * The message is an alert linked to the field, and the field reports its validity.
 */
test('PasswordStrengthValidatorTestCase: accessibility', async ({ page }) => {
	await open(page);
	const input = page.locator(`#${base}text1`);
	await expect(page.locator(`#${base}validator1`)).toHaveAttribute('role', 'alert');
	await expect(input).toHaveAttribute('aria-describedby', new RegExp(`${base}validator1`));
	await expect(page.getByLabel('New password')).toHaveAttribute('type', 'password');
	await expect(input).toHaveAttribute('autocomplete', 'new-password');

	await input.fill('password');
	await page.locator(`#${base}submit1`).click();
	await expect(page.getByRole('alert').filter({ hasText: 'Use a longer password' })).toBeVisible();
	await expect(input).toHaveAttribute('aria-invalid', 'true');
});
