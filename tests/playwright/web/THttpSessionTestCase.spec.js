import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const BASE = GENERIC_BASE_URL + 'web/index.php?page=HttpSessionTest';

/** Read and JSON-parse the #session-info div (textContent auto-decodes HTML entities). */
async function readSession(page) {
	const text = await page.locator('#session-info').textContent();
	return JSON.parse(text);
}

test('THttpSessionTestCase', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);

	// ── Default state: session not explicitly started ──
	await page.goto(BASE + '&action=info');
	await h.assertSourceContains('THttpSession Test');

	const infoState = await readSession(page);
	expect(infoState.isStarted).toBe(false);
	expect(infoState.testkey).toBeNull();
	// sessionName is always available (from php.ini), even before open()
	expect(typeof infoState.sessionName).toBe('string');
	expect(infoState.sessionName.length).toBeGreaterThan(0);

	// ── Start session: isStarted true, sessionId is a non-empty string ──
	await page.goto(BASE + '&action=start');

	// Verify the session cookie exists in the browser's cookie jar.
	// PRADO may issue the PHPSESSID on any request in the lifecycle, so we check
	// the jar rather than a specific response's Set-Cookie header.
	const cookies = await page.context().cookies();
	const sessionCookie = cookies.find(c => c.name.toLowerCase() === 'phpsessid');
	expect(sessionCookie).toBeDefined();
	expect(sessionCookie.value.length).toBeGreaterThan(0);

	const startState = await readSession(page);
	expect(startState.isStarted).toBe(true);
	expect(typeof startState.sessionId).toBe('string');
	expect(startState.sessionId.length).toBeGreaterThan(0);
	expect(startState.testkey).toBe('testvalue');
	expect(startState.counter).toBe(1);

	const firstSessionId = startState.sessionId;

	// ── Cross-request persistence: THE functional test ──
	// The browser automatically sends the PHPSESSID cookie on the next request.
	// Unit tests cannot verify this — it requires a real HTTP cookie round-trip.
	await page.goto(BASE + '&action=read');

	const readState = await readSession(page);
	expect(readState.isStarted).toBe(true);
	expect(readState.sessionId).toBe(firstSessionId);   // same session
	expect(readState.testkey).toBe('testvalue');         // data survived the round-trip
	expect(readState.counter).toBe(1);

	// ── Write additional data, verify it also persists ──
	await page.goto(BASE + '&action=write&key=extrakey&val=extravalue');
	await page.goto(BASE + '&action=read');

	const afterWriteState = await readSession(page);
	expect(afterWriteState.testkey).toBe('testvalue');

	// ── regenerate(): old session ID returned, new one assigned, data preserved ──
	await page.goto(BASE + '&action=regenerate');

	const regenState = await readSession(page);
	expect(regenState.isStarted).toBe(true);
	expect(regenState.oldId).toBe(firstSessionId);      // oldId stored in session data
	expect(regenState.sessionId).not.toBe(firstSessionId); // new session ID issued
	expect(regenState.testkey).toBe('testvalue');        // data preserved after regen

	// ── destroy(): session data cleared, isStarted false ──
	await page.goto(BASE + '&action=destroy');

	const destroyState = await readSession(page);
	expect(destroyState.isStarted).toBe(false);
	expect(destroyState.testkey).toBeNull();

	// Next request starts fresh — the old session data is gone
	await page.goto(BASE + '&action=read');

	const afterDestroyState = await readSession(page);
	// Session re-opens (action=read calls open()), but data is gone
	expect(afterDestroyState.testkey).toBeNull();
	expect(afterDestroyState.counter).toBeNull();
});
