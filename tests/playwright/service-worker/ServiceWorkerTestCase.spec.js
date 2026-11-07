import { test, expect } from '@playwright/test';
import { GENERIC_BASE_URL } from '../helpers.js';

/**
 * TServiceWorkerModule end to end, in the tests/harness/service-worker app.
 *
 * The module alone configures the harness: pages get the registration and the
 * manifest link, the `default` worker controls the app, and the `admin` worker
 * controls index.php/admin/. The Control page stores the worker revision and
 * the disabled flag that HarnessServiceWorkerModule reads.
 *
 * Playwright drives service workers in Chromium.
 */

const BASE = GENERIC_BASE_URL + 'service-worker/';
const PATH = '/tests/harness/service-worker/';

test.skip(({ browserName }) => browserName !== 'chromium', 'Playwright drives service workers in Chromium.');

test.beforeEach(async ({ request }) => {
	await request.get(BASE + 'index.php/page,Control?reset=1');
});

async function controlled(page) {
	await page.waitForFunction(() => navigator.serviceWorker && navigator.serviceWorker.controller !== null);
}

async function cacheNames(page) {
	return page.evaluate(() => caches.keys());
}

async function cachedUrls(page, prefix) {
	return page.evaluate(async (start) => {
		const urls = [];
		for (const name of await caches.keys()) {
			if (name.startsWith(start)) {
				const cache = await caches.open(name);
				(await cache.keys()).forEach((request) => urls.push(request.url));
			}
		}
		return urls;
	}, prefix);
}

async function activateUpdate(page, request, query) {
	await page.evaluate(() => {
		document.addEventListener('prado:serviceworkerupdate', (event) => event.detail.activate());
	});
	await request.get(BASE + 'index.php/page,Control?' + query);
	const reloaded = page.waitForEvent('load');
	await page.evaluate(async () => (await navigator.serviceWorker.getRegistration()).update());
	await reloaded;
}

test('the default worker registers, links its manifest, caches assets, and answers messages', async ({ page }) => {
	await page.goto(BASE + 'index.php');
	await controlled(page);

	const scope = await page.evaluate(async () => (await navigator.serviceWorker.getRegistration()).scope);
	expect(new URL(scope).pathname).toBe(PATH);
	await expect(page.locator('link[rel="manifest"]')).toHaveAttribute('href', PATH + 'index.php/sw,manifest');
	await expect(page.locator('meta[name="theme-color"]')).toHaveAttribute('content', '#2b5797');

	const manifest = await (await page.request.get(BASE + 'index.php/sw,manifest')).json();
	expect(manifest.name).toBe('Service Worker Tests');
	expect(manifest.scope).toBe(PATH);

	expect(await page.evaluate(() => Prado.ServiceWorker.postMessage('revision'))).toBe(1);
	expect(await page.evaluate(() => Prado.ServiceWorker.postMessage('version'))).toMatch(/^[0-9a-f]{16}$/);
	await expect(page.evaluate(() => Prado.ServiceWorker.postMessage('nothing'))).rejects.toThrow(/Unknown message type/);

	await page.reload();
	await controlled(page);
	const names = await cacheNames(page);
	expect(names.some((name) => /^prado-default-[0-9a-f]{16}-precache$/.test(name))).toBe(true);
	const runtime = await cachedUrls(page, 'prado-default-');
	expect(runtime.some((url) => url.includes(PATH + 'assets/'))).toBe(true);
	expect(runtime.some((url) => url.endsWith('index.php/page,Offline'))).toBe(true);
});

test('callbacks and pages pass through the worker uncached', async ({ page }) => {
	await page.goto(BASE + 'index.php');
	await controlled(page);
	await page.reload();
	await controlled(page);
	await page.locator('input[value="Ping"]').click();
	await expect(page.locator('[id$="Pong"]')).toHaveText(/^pong \d+$/);
	const urls = await cachedUrls(page, 'prado-default-');
	expect(urls.filter((url) => !url.includes(PATH + 'assets/') && !url.endsWith('page,Offline'))).toEqual([]);
});

test('a navigation without network shows the offline page', async ({ page, context }) => {
	await page.goto(BASE + 'index.php');
	await controlled(page);
	await context.setOffline(true);
	await page.goto(BASE + 'index.php/page,Home');
	await expect(page.locator('h1')).toHaveText('You are offline');
	await context.setOffline(false);
});

test('an updated worker waits until activate() and then controls the reloaded page', async ({ page, request }) => {
	await page.goto(BASE + 'index.php');
	await controlled(page);
	const before = await page.evaluate(() => Prado.ServiceWorker.postMessage('version'));
	await activateUpdate(page, request, 'revision=2');
	await controlled(page);
	expect(await page.evaluate(() => Prado.ServiceWorker.postMessage('revision'))).toBe(2);
	expect(await page.evaluate(() => Prado.ServiceWorker.postMessage('version'))).not.toBe(before);
});

test('a disabled worker removes itself and its caches', async ({ page, request }) => {
	await page.goto(BASE + 'index.php');
	await controlled(page);
	await page.reload();
	await controlled(page);
	expect((await cacheNames(page)).some((name) => name.startsWith('prado-default-'))).toBe(true);

	await request.get(BASE + 'index.php/page,Control?disabled=1');
	const reloaded = page.waitForEvent('load');
	await page.evaluate(async () => (await navigator.serviceWorker.getRegistration()).update());
	await reloaded;
	await expect.poll(() => page.evaluate(async () => (await navigator.serviceWorker.getRegistrations()).length)).toBe(0);
	expect((await cacheNames(page)).some((name) => name.startsWith('prado-default-'))).toBe(false);
	expect(await page.evaluate(() => navigator.serviceWorker.controller)).toBeNull();
	// A disabled module adds neither the registration nor the manifest link.
	await expect(page.locator('link[rel="manifest"]')).toHaveCount(0);
	expect(await page.content()).not.toContain('Prado.ServiceWorker.register');
});

test('the admin worker controls its own pages and keeps its caches through a default update', async ({ page, request }) => {
	await page.goto(BASE + 'index.php');
	await controlled(page);

	await page.goto(BASE + 'index.php/admin/home');
	await controlled(page);
	const admin = await page.evaluate(() => navigator.serviceWorker.controller.scriptURL);
	expect(admin).toContain('index.php/sw,worker/w,admin');
	await expect(page.locator('link[rel="manifest"]')).toHaveAttribute('href', PATH + 'index.php/sw,manifest/w,admin');
	const scopes = await page.evaluate(async () => (await navigator.serviceWorker.getRegistrations()).map((registration) => new URL(registration.scope).pathname).sort());
	expect(scopes).toEqual([PATH, PATH + 'index.php/admin/']);
	await page.reload();
	await controlled(page);
	const adminCaches = (await cacheNames(page)).filter((name) => name.startsWith('prado-admin-'));
	expect(adminCaches.length).toBeGreaterThan(0);

	await page.goto(BASE + 'index.php');
	await controlled(page);
	expect(await page.evaluate(() => navigator.serviceWorker.controller.scriptURL)).not.toContain('w,admin');
	const defaultBefore = (await cacheNames(page)).filter((name) => name.startsWith('prado-default-'));
	await activateUpdate(page, request, 'revision=3');
	await controlled(page);
	await page.reload();
	await controlled(page);

	const names = await cacheNames(page);
	expect(names.filter((name) => name.startsWith('prado-admin-'))).toEqual(adminCaches);
	const defaultAfter = names.filter((name) => name.startsWith('prado-default-'));
	expect(defaultAfter.length).toBeGreaterThan(0);
	expect(defaultAfter.some((name) => defaultBefore.includes(name))).toBe(false);
});
