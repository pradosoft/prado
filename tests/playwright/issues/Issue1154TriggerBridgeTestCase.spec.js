/**
 * Functional coverage for the jQuery.fn.trigger → native dispatchEvent
 * bridge installed in prado.js. Exercises every branch in a real browser
 * (the unit suite at tests/js/prado/trigger-bridge.test.js covers jsdom).
 *
 * Bug context: PR #1154 changed Prado.WebUI.Control.observe from
 * jQuery(el).bind(...) to addEventListener(...). jQuery.trigger() does
 * not reach native addEventListener handlers for non-activation events
 * (see jquery/jquery#2476), so every downstream control still calling
 * jQuery(this.control).trigger('change') became a no-op. The bridge
 * fixes this; these tests prove it across all three browsers.
 *
 * The harness page is tests/harness/issues/protected/pages/Issue1154TriggerBridge.{page,php}.
 * It exposes a small window.__bridge API so each test can drive a single
 * scenario via page.evaluate().
 */
import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

async function gotoBridge(page) {
	const h = genericHelper(page);
	await h.url('issues/index.php?page=Issue1154TriggerBridge');
	// Wait for the setup script to finish registering klass instances.
	await page.waitForFunction(() => typeof window.__bridge !== 'undefined');
	return h;
}

// ─── 1. Factory marker (isPradoClass) ────────────────────────────────────────

test('Issue1154 — every klass constructor carries the isPradoClass marker', async ({ page }) => {
	await gotoBridge(page);
	const ids = ['probeKlass', 'probeText', 'probeMulti1', 'probeFocus',
		'probeButton', 'probeCustom', 'bridgeForm', 'probeSubmit'];
	for (const id of ids) {
		const marked = await page.evaluate((i) => window.__bridge.isPradoClassMarker(i), id);
		expect(marked, `klass marker missing on registry[${id}]`).toBe(true);
	}
});

// ─── 2. Prado.Class.prototype.trigger ────────────────────────────────────────

test('Issue1154 — Prado.Class.prototype.trigger dispatches CustomEvent on this.element', async ({ page }) => {
	await gotoBridge(page);
	const got = await page.evaluate(() => window.__bridge.probeKlass_triggerHelper('change'));
	expect(got).not.toBeNull();
	expect(got.type).toBe('change');
});

test('Issue1154 — Prado.Class.prototype.trigger forwards detail payload', async ({ page }) => {
	await gotoBridge(page);
	const got = await page.evaluate(() => window.__bridge.probeKlass_triggerHelper('mything', { v: 42 }));
	expect(got).toEqual({ type: 'mything', detail: { v: 42 } });
});

// ─── 3. The PR #1154 bug — the headline fix ──────────────────────────────────

test('Issue1154 — FIXED: jQuery.trigger("change") on a klass element fires the addEventListener handler', async ({ page }) => {
	await gotoBridge(page);
	const fired = await page.evaluate(() => window.__bridge.klassChange_fires());
	expect(fired).toBe(1);
});

test('Issue1154 — jQuery.on() handler also fires on a klass element when jQuery.trigger is used', async ({ page }) => {
	await gotoBridge(page);
	const fired = await page.evaluate(() => window.__bridge.klassChange_jq_on_fires());
	expect(fired).toBe(1);
});

test('Issue1154 — mixed handlers (addEventListener + jQuery.on) each fire exactly once', async ({ page }) => {
	await gotoBridge(page);
	const out = await page.evaluate(() => window.__bridge.klassChange_mixed());
	expect(out).toEqual({ native: 1, jq: 1 });
});

test('Issue1154 — handler dispatch order is native-then-jQuery for the same registration sequence', async ({ page }) => {
	await gotoBridge(page);
	// Both handlers go through addEventListener under the hood, so order
	// reflects registration order. Spec exists so any future regression in
	// dispatch order is caught immediately.
	const order = await page.evaluate(() => window.__bridge.handlerOrder());
	expect(order).toEqual(['native', 'jquery']);
});

// ─── 4. Non-klass passthrough ────────────────────────────────────────────────

test('Issue1154 — non-klass element keeps the original jQuery.trigger pipeline', async ({ page }) => {
	await gotoBridge(page);
	const fired = await page.evaluate(() => window.__bridge.nonKlass_passthrough());
	expect(fired).toBe(1);
});

test('Issue1154 — registry slot containing a non-klass object falls through to original trigger', async ({ page }) => {
	await gotoBridge(page);
	const fired = await page.evaluate(() => window.__bridge.registry_not_klass());
	expect(fired).toBe(1);
});

// ─── 5. CustomEvent payload ──────────────────────────────────────────────────

test('Issue1154 — extraParameters survive as event.detail', async ({ page }) => {
	await gotoBridge(page);
	const got = await page.evaluate(() => window.__bridge.customDetail({ a: 1, b: 'x' }));
	expect(got).toEqual({ a: 1, b: 'x' });
});

test('Issue1154 — dispatched event is a CustomEvent (not a plain Event)', async ({ page }) => {
	await gotoBridge(page);
	const ok = await page.evaluate(() => window.__bridge.usesCustomEvent());
	expect(ok).toBe(true);
});

test('Issue1154 — passing a real Event instance dispatches it verbatim', async ({ page }) => {
	await gotoBridge(page);
	const ok = await page.evaluate(() => window.__bridge.realEventPassthrough());
	expect(ok).toBe(true);
});

// ─── 6. Chainability & multi-element ─────────────────────────────────────────

test('Issue1154 — .trigger() returns the jQuery wrapper (chainable)', async ({ page }) => {
	await gotoBridge(page);
	const ok = await page.evaluate(() => window.__bridge.chainability());
	expect(ok).toBe(true);
});

test('Issue1154 — multi-element selector processes klass + non-klass independently', async ({ page }) => {
	await gotoBridge(page);
	const out = await page.evaluate(() => window.__bridge.multiset());
	expect(out).toEqual({ klassFired: 1, plainFired: 1 });
});

// ─── 7. Activation events (defaults must fire) ───────────────────────────────

test('Issue1154 — jQuery.trigger("focus") actually focuses the element', async ({ page }) => {
	await gotoBridge(page);
	const focused = await page.evaluate(() => window.__bridge.focusActivation());
	expect(focused).toBe(true);
});

test('Issue1154 — jQuery.trigger("blur") actually blurs the element', async ({ page }) => {
	await gotoBridge(page);
	const blurred = await page.evaluate(() => window.__bridge.blurActivation());
	expect(blurred).toBe(true);
});

// jQuery.trigger('submit') on a klass form must invoke form.submit() — the
// real-browser equivalent of "the form submits". We spy on form.submit()
// rather than observe real navigation because Prado wraps the entire page
// in its own outer form (nested forms are not honored by browsers).
test('Issue1154 — jQuery.trigger("submit") on a klass form invokes form.submit()', async ({ page }) => {
	await gotoBridge(page);
	const calls = await page.evaluate(() => window.__bridge.submitInvoked());
	expect(calls).toBe(1);
});

test('Issue1154 — the submit EVENT fires before form.submit() is invoked', async ({ page }) => {
	await gotoBridge(page);
	const order = await page.evaluate(() => window.__bridge.submitEventFires());
	// Event handler must observe the event before form.submit() is called,
	// so a handler can still preventDefault.
	expect(order).toEqual(['event', 'submit']);
});

// preventDefault must stop the submit — important for any client-side
// validation hooked into the submit event.
test('Issue1154 — preventDefault on submit prevents the form.submit() call', async ({ page }) => {
	await gotoBridge(page);
	const calls = await page.evaluate(() => window.__bridge.submitPrevented());
	expect(calls).toBe(0);
});

// jQuery.trigger('click') on a klass submit-button must invoke the native
// el.click() so the browser runs the default activation (which would in
// turn submit the owning form in real markup).
test('Issue1154 — jQuery.trigger("click") on a klass submit button invokes el.click()', async ({ page }) => {
	await gotoBridge(page);
	const calls = await page.evaluate(() => window.__bridge.clickActivation());
	expect(calls).toBe(1);
});

// ─── 8. Exactly-once dispatch: no overlap double-fire ────────────────────────
//
// These tests prove the bridge does NOT call originalTrigger after
// dispatchEvent on the klass path. If it did, every jQuery-bound handler
// would fire twice — once via jQuery's internal addEventListener
// registration that dispatchEvent reaches, once via originalTrigger
// walking the same handler list.

test('Issue1154 — one native handler fires exactly once', async ({ page }) => {
	await gotoBridge(page);
	expect(await page.evaluate(() => window.__bridge.exactlyOnce_native())).toBe(1);
});

test('Issue1154 — one jQuery.on handler fires exactly once', async ({ page }) => {
	await gotoBridge(page);
	expect(await page.evaluate(() => window.__bridge.exactlyOnce_jq())).toBe(1);
});

test('Issue1154 — three native handlers each fire exactly once', async ({ page }) => {
	await gotoBridge(page);
	expect(await page.evaluate(() => window.__bridge.exactlyOnce_threeNative())).toEqual([1, 1, 1]);
});

test('Issue1154 — three jQuery.on handlers each fire exactly once', async ({ page }) => {
	await gotoBridge(page);
	expect(await page.evaluate(() => window.__bridge.exactlyOnce_threeJq())).toEqual([1, 1, 1]);
});

test('Issue1154 — matrix: 2 native + 2 jQuery handlers → exactly 4 total calls', async ({ page }) => {
	await gotoBridge(page);
	const out = await page.evaluate(() => window.__bridge.exactlyOnce_matrix());
	expect(out).toEqual({ n1: 1, n2: 1, j1: 1, j2: 1, total: 4 });
});

test('Issue1154 — addEventListener deduplicates same-fn double registration (browser spec)', async ({ page }) => {
	await gotoBridge(page);
	expect(await page.evaluate(() => window.__bridge.exactlyOnce_addEventListenerDedupe())).toBe(1);
});

test('Issue1154 — jQuery.on same-fn double registration fires twice (jQuery behavior, NOT introduced by bridge)', async ({ page }) => {
	await gotoBridge(page);
	expect(await page.evaluate(() => window.__bridge.exactlyOnce_jqDoesNotDedupe())).toBe(2);
});

test('Issue1154 — three sequential trigger() calls fire each handler exactly three times (no leftover state)', async ({ page }) => {
	await gotoBridge(page);
	const out = await page.evaluate(() => window.__bridge.exactlyOnce_threeTriggers());
	expect(out).toEqual({ native: 3, jq: 3 });
});

test('Issue1154 — distinct event names do not bleed: triggering "change" does not call "input" handlers', async ({ page }) => {
	await gotoBridge(page);
	const out = await page.evaluate(() => window.__bridge.exactlyOnce_distinctEventNames());
	expect(out).toEqual({ change: 1, input: 0 });
});

test('Issue1154 — stopPropagation in a native handler stops jQuery-bound bubble handlers (proves single event flow path)', async ({ page }) => {
	await gotoBridge(page);
	const out = await page.evaluate(() => window.__bridge.exactlyOnce_stopPropagation());
	expect(out).toEqual({ child: 1, parent: 0 });
});

// ─── 9. Edge cases ───────────────────────────────────────────────────────────

test('Issue1154 — empty / undefined event name is a no-op (no throw, no handler fired)', async ({ page }) => {
	await gotoBridge(page);
	const fired = await page.evaluate(() => window.__bridge.emptyEventName());
	expect(fired).toBe(0);
});
