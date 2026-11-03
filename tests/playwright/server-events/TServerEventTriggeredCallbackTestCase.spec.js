import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

/*
 * Server push through TServerEventService and TServerEventTriggeredCallback (#1408).
 *
 * The PHP built-in server serves one request at a time, so an open stream holds
 * every other request until it ends. The harness ends each stream after 2 seconds
 * (MaxLifetime) and the browser reconnects after 200 ms (RetryInterval); a callback
 * waits for the current stream, and an event reaches the page on the next stream
 * at the latest. The page has two channels, whose streams take turns on that server.
 * The generous timeouts cover those waits. A test publishes without waiting for a
 * stream to open: the page renders the latest event ID, so the stream starts after it.
 */

const APP = 'server-events/index.php';
const WAIT = { timeout: 20_000 };

async function open(page) {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(APP);
	await h.assertSourceContains('TServerEventTriggeredCallback');
	return h;
}

function streamUrl(page, id) {
	return page.evaluate((id) => Prado.Registry[id].options.StreamUrl, id);
}

test('TServerEventTriggeredCallback: a published event raises the callbacks of the subscribed controls', async ({ page }) => {
	await open(page);

	await page.locator('#PublishChanged').click();
	await expect(page.locator('#ChangedLabel')).toHaveText('changed 1', WAIT);
	await expect(page.locator('#AnyLabel')).toHaveText('changed 1', WAIT);

	// EventName="changed" ignores another event of the channel; the unfiltered control takes it.
	await page.locator('#PublishRemoved').click();
	await expect(page.locator('#AnyLabel')).toHaveText('removed 2', WAIT);
	await expect(page.locator('#ChangedLabel')).toHaveText('changed 1');

	// A later event arrives after the stream ended and the browser reconnected.
	await page.waitForTimeout(2_500);
	await page.locator('#PublishChanged').click();
	await expect(page.locator('#ChangedLabel')).toHaveText('changed 3', WAIT);
});

test('TServerEventTriggeredCallback: CallbackOnEvent false runs only the client handler, and every event reaches document', async ({ page }) => {
	await open(page);
	const clientCallbacks = [];
	page.on('request', (request) => {
		if (request.method() === 'POST' && (request.postData() || '').includes('PRADO_CALLBACK_TARGET=ClientWatch'))
			clientCallbacks.push(request.url());
	});

	await page.locator('#PublishChanged').click();
	await expect(page.locator('#ClientLabel')).toHaveText('changed 1', WAIT);
	await expect(page.locator('#DocumentLabel')).toHaveText('orders changed 1', WAIT);
	// The callback controls on the same stream still run; ClientWatch sends no request.
	await expect(page.locator('#AnyLabel')).toHaveText('changed 1', WAIT);
	// A ClientWatch callback would queue behind AnyWatch's; give the queue time to drain.
	await page.waitForTimeout(3_000);
	await expect(page.locator('#ClientCallbackLabel')).toHaveText('none');
	expect(clientCallbacks).toEqual([]);
});

test('TServerEventTriggeredCallback: controls on one channel share one EventSource', async ({ page }) => {
	await open(page);
	const streams = await page.evaluate(() => Object.fromEntries(
		Object.values(Prado.ServerEvents.streams).map((entry) => [new URL(entry.handle.url, location.href).searchParams.get('events'), entry.subscribers.map((s) => s.ID)])
	));
	expect(streams).toEqual({ orders: ['ChangedWatch', 'AnyWatch', 'ClientWatch'], panel: ['PanelWatch'] });
	expect(await streamUrl(page, 'ChangedWatch')).toBe(await streamUrl(page, 'AnyWatch'));
});

test('TServerEventTriggeredCallback: a re-rendered control keeps its stream, a removed one closes it', async ({ page }) => {
	await open(page);
	const panelUrl = await streamUrl(page, 'PanelWatch');
	await page.evaluate(() => { window.__panelSource = Object.values(Prado.ServerEvents.streams).find((e) => e.subscribers[0].ID === 'PanelWatch').handle.source; });

	await page.locator('#RefreshPanel').click();
	await page.waitForFunction(() => Prado.Registry.PanelWatch && !Prado.Registry.PanelWatch.isLingering() && document.getElementById('PanelWatch'), null, WAIT);
	expect(await page.evaluate((url) => Prado.ServerEvents.streams['sse ' + url].handle.source === window.__panelSource, panelUrl)).toBe(true);
	await page.locator('#PublishPanel').click();
	await expect(page.locator('#PanelLabel')).toHaveText('ping 1', WAIT);

	await page.locator('#RemoveWatch').click();
	await page.waitForFunction((url) => !(('sse ' + url) in Prado.ServerEvents.streams), panelUrl, WAIT);
	expect(await page.evaluate(() => window.__panelSource.readyState)).toBe(2);
	await page.locator('#PublishPanel').click();
	await page.locator('#PublishChanged').click();
	await expect(page.locator('#ChangedLabel')).toHaveText('changed 3', WAIT);
	await expect(page.locator('#PanelLabel')).toHaveText('ping 1');
});

test('TServerEventTriggeredCallback: the stream refuses a changed token and an invalid channel', async ({ page }) => {
	await open(page);
	const url = new URL(await streamUrl(page, 'ChangedWatch'), page.url());

	const stream = await page.request.get(url.href + '&lastEventId=');
	expect(stream.status()).toBe(200);
	expect(stream.headers()['content-type']).toBe('text/event-stream;charset=UTF-8');
	expect(stream.headers()['cache-control']).toBe('no-cache');
	expect(await stream.text()).toMatch(/^retry: 200\n\n/);

	const forged = new URL(url);
	forged.searchParams.set('events', 'panel');
	expect((await page.request.get(forged.href)).status()).toBe(403);

	const badToken = new URL(url);
	badToken.searchParams.set('token', '0'.repeat(64));
	expect((await page.request.get(badToken.href)).status()).toBe(403);

	const badChannel = new URL(url);
	badChannel.searchParams.set('events', 'no such channel');
	expect((await page.request.get(badChannel.href)).status()).toBe(404);
});
