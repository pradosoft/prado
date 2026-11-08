import { test, expect } from '@playwright/test';
import { GENERIC_BASE_URL } from '../helpers.js';

/**
 * Prado.SharedConnection across two tabs of one browser context: the first
 * tab holds the event stream and passes its events to the second; closing it
 * hands the stream to the second, which resumes from the last event ID.
 *
 * The harness stream (events.php) sends two numbered events per request and
 * ends, and the browser reconnects with Last-Event-ID, so the IDs a tab
 * receives are consecutive.
 */

const BASE = GENERIC_BASE_URL + 'service-worker/';
const SHARED = BASE + 'index.php/page,Shared';

function streamRequests(page) {
	const urls = [];
	page.on('request', (request) => {
		if (request.url().includes('/events.php')) {
			urls.push(request.url());
		}
	});
	return urls;
}

function consecutive(ids) {
	return ids.every((id, index) => index === 0 || id === ids[index - 1] + 1);
}

test('two tabs share one stream and hand it over without losing an event', async ({ context }) => {
	const one = await context.newPage();
	const oneRequests = streamRequests(one);
	await one.goto(SHARED);
	await expect(one.locator('#leader')).toHaveText('yes');

	const two = await context.newPage();
	const twoRequests = streamRequests(two);
	await two.goto(SHARED);
	await expect(two.locator('#leader')).toHaveText('no');

	await expect.poll(() => two.evaluate(() => window.received.length)).toBeGreaterThan(3);
	expect(oneRequests.length).toBeGreaterThan(0);
	expect(twoRequests).toEqual([]);
	expect(consecutive(await one.evaluate(() => window.received))).toBe(true);
	expect(consecutive(await two.evaluate(() => window.received))).toBe(true);

	const lastBeforeHandover = await two.evaluate(() => window.received[window.received.length - 1]);
	await one.close();
	await expect(two.locator('#leader')).toHaveText('yes');
	await expect.poll(() => twoRequests.length).toBeGreaterThan(0);
	const resumedFrom = Number(new URL(twoRequests[0]).searchParams.get('lastEventId'));
	expect(resumedFrom).toBeGreaterThanOrEqual(lastBeforeHandover);

	await expect.poll(() => two.evaluate(() => window.received[window.received.length - 1])).toBeGreaterThan(lastBeforeHandover + 2);
	expect(consecutive(await two.evaluate(() => window.received))).toBe(true);
});
