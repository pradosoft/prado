/**
 * PradoTestHelper — Playwright equivalent of PradoGenericSelenium2Test.
 *
 * Wraps a Playwright `page` object and exposes the same method API that the
 * PHP/Selenium2 base-class exposed, so that converted spec files stay as
 * close as possible to their PHP originals.
 *
 * Method mapping (PHP Selenium → this helper):
 *
 *   $this->url(path)                → await h.url(path)
 *   $this->refresh()                → await h.refresh()
 *   $this->source()                 → await h.source()
 *   $this->byId(id)                 → h.byId(id)           ← returns Locator
 *   $this->byCssSelector(css)       → h.byCssSelector(css)
 *   $this->byXPath(xpath)           → h.byXPath(xpath)
 *   $this->byLinkText(text)         → h.byLinkText(text)
 *   $this->byName(name)             → h.byName(name)
 *   $this->click(id)                → await h.click(id)
 *   $this->type(id, txt)            → await h.type(id, txt)
 *   $this->typeSpecial(id, txt)     → await h.typeSpecial(id, txt)
 *   $this->select(id, label)        → await h.select(id, label)
 *   $this->addSelection(id, label)  → await h.addSelection(id, label)
 *   $this->getSelectedLabels(id)    → await h.getSelectedLabels(id)
 *   $this->getSelectOptions(id)     → await h.getSelectOptions(id)
 *   $this->moveto(locator)          → await h.moveto(locator)
 *   $this->keys(key)                → await h.keys(key)
 *   $this->executeScript(s, args)   → await h.executeScript(s, args)
 *   $this->pause(msec)              → await h.pause(msec)
 *   $this->active()                 → h.active()           ← returns Locator
 *   $this->waitForAjaxCalls()       → await h.waitForAjaxCalls()
 *   $this->assertTitle(title)       → await h.assertTitle(title)
 *   $this->assertText(id, txt)      → await h.assertText(id, txt)
 *   $this->assertValue(id, txt)     → await h.assertValue(id, txt)
 *   $this->assertVisible(id)        → await h.assertVisible(id)
 *   $this->assertNotVisible(id)     → await h.assertNotVisible(id)
 *   $this->assertElementPresent(id) → await h.assertElementPresent(id)
 *   $this->assertElementNotPresent(id) → await h.assertElementNotPresent(id)
 *   $this->assertSourceContains(t)  → await h.assertSourceContains(t)
 *   $this->assertSourceNotContains  → await h.assertSourceNotContains(t)
 *   $this->assertChecked(id)        → await h.assertChecked(id)
 *   $this->assertNotChecked(id)     → await h.assertNotChecked(id)
 *   $this->assertAttribute(id@a,v)  → await h.assertAttribute(id@attr, val)
 *   $this->assertSelected(id, lbl)  → await h.assertSelected(id, label)
 *   $this->assertSelectedMultiple   → await h.assertSelectedMultiple(id, [...])
 *   $this->assertNotSomethingSelected → await h.assertNotSomethingSelected(id)
 *   $this->assertSelectedValue      → await h.assertSelectedValue(id, val)
 *   $this->assertSelectedIndex      → await h.assertSelectedIndex(id, n)
 *   $this->alertText()              → h.alertText()        ← sync, returns string
 *   $this->acceptAlert()            → h.acceptAlert()      ← sync
 *   $this->dismissAlert()           → h.dismissAlert()     ← sync
 *   $this->assertAlertPresent()     → await h.assertAlertPresent()
 *   $this->assertAlertNotPresent()  → await h.assertAlertNotPresent()
 *
 * Alert handling:
 *   Playwright requires dialogs to be accepted/dismissed asynchronously inside
 *   the 'dialog' event handler.  This helper auto-accepts every dialog that
 *   appears and stores the message text in a FIFO queue.  alertText() peeks at
 *   the front of the queue; acceptAlert() / dismissAlert() shift it off.
 *   This preserves the sequential PHP pattern:
 *     click → pause → alertText() → acceptAlert()
 */

import { expect } from '@playwright/test';

// Port 8037 is the dedicated Playwright test server (started automatically by
// playwright.config.js via start-server.sh).  Both URL prefixes resolve under
// the same PHP built-in server rooted at the repository root.
export const GENERIC_BASE_URL = 'http://127.0.0.1:8037/tests/FunctionalTests/';
export const DEMOS_BASE_URL = 'http://127.0.0.1:8037/vendor/pradosoft/prado-demos/';

/**
 * CSS.escape polyfill — the W3C `CSS` global is browser-only; Node.js (the
 * Playwright test runner) does not expose it.  Prado IDs only use ASCII word
 * characters and hyphens, but we implement the full spec subset anyway so
 * that getLocator('id=…') is safe for any input.
 *
 * @param {string} str
 * @returns {string}
 */
function cssEscape(str) {
	return str.replace(/([^\w-]|^(\d))/g, (ch, _m, digit) =>
		digit ? `\\3${digit} ` : `\\${ch}`
	);
}

/** Timeout (ms) used for polling / waiting in assertions — mirrors $timeout=5 */
const TIMEOUT = 5000;

/**
 * Set of Playwright key names that must be passed to keyboard.press() rather
 * than keyboard.type().  Anything not in this set (and not a single character,
 * and not containing '+') will be typed as literal text.
 */
const KEY_NAMES = new Set([
	'Enter', 'Return', 'Tab', 'Space', 'Escape', 'Backspace', 'Delete', 'Insert',
	'Home', 'End', 'PageUp', 'PageDown',
	'ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight',
	'F1', 'F2', 'F3', 'F4', 'F5', 'F6', 'F7', 'F8', 'F9', 'F10', 'F11', 'F12',
	'Shift', 'Control', 'Alt', 'Meta', 'CapsLock', 'NumLock', 'ScrollLock', 'Pause',
]);

export class PradoTestHelper {
	/**
	 * @param {import('@playwright/test').Page} page
	 * @param {string} baseUrl  GENERIC_BASE_URL or DEMOS_BASE_URL
	 */
	constructor(page, baseUrl = GENERIC_BASE_URL) {
		this.page = page;
		this.baseUrl = baseUrl;

		/** @type {string[]} FIFO queue of alert message texts */
		this._dialogQueue = [];

		/** When true, the next dialog will be dismissed (cancelled) instead of accepted */
		this._dismissNext = false;

		// Auto-accept every dialog and store its text.
		// If dismissNextAlert() was called before the dialog fired, dismiss it instead.
		page.on('dialog', async (dialog) => {
			this._dialogQueue.push(dialog.message());
			if (this._dismissNext) {
				this._dismissNext = false;
				await dialog.dismiss();
			} else {
				await dialog.accept();
			}
		});
	}

	// ── Navigation ──────────────────────────────────────────────────────────

	async url(path) {
		await this.page.goto(this.baseUrl + path);
	}

	async refresh() {
		await this.page.reload();
	}

	async source() {
		return await this.page.content();
	}

	// ── Element locators ────────────────────────────────────────────────────
	//
	// These return Playwright Locator objects so callers can chain .click(),
	// .fill(), etc. exactly as the PHP code chains ->click() on WebElement.
	//
	// ID-format resolution mirrors PradoGenericSelenium2Test::getElement():
	//   "id=foo"   → #foo
	//   "name=foo" → [name="foo"]
	//   "//..."    → xpath=...
	//   "a$b"      → [name="a$b"]  (Prado $ name convention)
	//   "foo"      → #foo          (default: by ID)

	getLocator(id) {
		if (typeof id === 'string') {
			if (id.startsWith('id=')) {
				return this.page.locator(`#${cssEscape(id.slice(3))}`);
			}
			if (id.startsWith('name=')) {
				return this.page.locator(`[name="${id.slice(5)}"]`);
			}
			if (id.startsWith('//')) {
				return this.page.locator(`xpath=${id}`);
			}
			if (id.includes('$')) {
				return this.page.locator(`[name="${id}"]`);
			}
		}
		return this.page.locator(`#${id}`);
	}

	byId(id) {
		return this.page.locator(`#${id}`);
	}

	byCssSelector(css) {
		return this.page.locator(css);
	}

	byXPath(xpath) {
		return this.page.locator(`xpath=${xpath}`);
	}

	byLinkText(text) {
		return this.page.getByRole('link', { name: text, exact: true });
	}

	byName(name) {
		return this.page.locator(`[name="${name}"]`);
	}

	// ── Actions ─────────────────────────────────────────────────────────────

	/** Mirrors PradoGenericSelenium2Test::click() — 50 ms pause before click */
	async click(id) {
		await this.pause(50);
		await this.getLocator(id).click();
	}

	/**
	 * Mirrors PradoGenericSelenium2Test::type():
	 *   clear → type (character by character) → click body (triggers onblur/onchange)
	 *
	 * Uses pressSequentially() rather than fill() so that WebKit correctly marks
	 * the field as "user-dirty".  Playwright's fill() sets the value
	 * programmatically; WebKit does not fire the 'change' event on subsequent
	 * blur for programmatically-set values, meaning TActiveTextBox AutoPostBack
	 * callbacks never fire.  pressSequentially() dispatches real key events,
	 * which triggers 'change' reliably in all browsers.
	 */
	async type(id, txt = '') {
		await this.pause(50);
		const locator = this.getLocator(id);
		await locator.clear();
		if (txt !== '') {
			await locator.pressSequentially(txt);
		}
		// trigger onblur by clicking outside (avoid datepicker popups changing value)
		await this.page.locator('body').click();
	}

	/**
	 * Mirrors PradoGenericSelenium2Test::typeSpecial():
	 *   select all → delete → type text → Tab (triggers onblur / autopostback)
	 *
	 * Uses locator.selectText() to select all text (cross-platform: avoids the
	 * macOS quirk where Ctrl+A moves the cursor to the beginning rather than
	 * selecting all).  After selection, global keyboard events are used so the
	 * element stays focused without Playwright re-clicking it.
	 *
	 * Waits for the next page load event (full-page autopostback) that may be
	 * triggered by the Tab/blur.  The catch(() => {}) silences the timeout when
	 * no navigation occurs (e.g. client-side validation prevented the submit).
	 */
	async typeSpecial(id, txt = '') {
		const locator = this.getLocator(id);
		await locator.selectText();            // cross-platform select-all
		await this.page.keyboard.press('Backspace'); // delete selected text
		if (txt !== '') {
			await this.page.keyboard.type(txt); // type new value (stays focused)
		}
		await Promise.all([
			this.page.waitForEvent('load', { timeout: 3000 }).catch(() => {}),
			this.page.keyboard.press('Tab'),    // blur → change → autopostback
		]);
		await this.waitForAjaxCalls();
	}

	/**
	 * Mirrors PradoGenericSelenium2Test::select():
	 *   deselects all options on multi-select, then selects by visible label.
	 */
	async select(id, label) {
		const locator = this.getLocator(id);
		const isMultiple = await locator.evaluate((el) => el.multiple);
		if (isMultiple) {
			await locator.evaluate((el) => {
				for (const opt of el.options) {
					opt.selected = false;
				}
			});
		}
		await locator.selectOption({ label: String(label) });
	}

	/**
	 * Mirrors PradoGenericSelenium2Test::addSelection() — adds without clearing.
	 *
	 * Playwright's selectOption() replaces all existing selections when called
	 * with a single value on a <select multiple>.  We read the currently-selected
	 * values first and pass them all together so prior selections are preserved.
	 */
	async addSelection(id, label) {
		const locator = this.getLocator(id);
		const current = await locator.evaluate((el) =>
			Array.from(el.selectedOptions).map((o) => o.value)
		);
		await locator.selectOption([
			...current.map((v) => ({ value: v })),
			{ label: String(label) },
		]);
	}

	/** Returns array of text labels of all currently-selected options */
	async getSelectedLabels(id) {
		return await this.getLocator(id).evaluate((el) =>
			Array.from(el.selectedOptions).map((o) => o.text)
		);
	}

	/** Returns array of text labels of all options in the select */
	async getSelectOptions(id) {
		return await this.getLocator(id).evaluate((el) =>
			Array.from(el.options).map((o) => o.text)
		);
	}

	/** Mirrors PradoGenericSelenium2Test::moveto() — mouse hover */
	async moveto(locator) {
		await locator.hover();
	}

	/**
	 * Mirrors PradoGenericSelenium2Test::keys().
	 * Known Playwright key names are pressed via keyboard.press().
	 * Modifier combos (containing '+') are always pressed.
	 * Single characters are always pressed.
	 * Everything else (multi-char literal text like 'Joh') is typed via keyboard.type().
	 */
	async keys(key) {
		if (key.includes('+') || key.length === 1 || KEY_NAMES.has(key)) {
			await this.page.keyboard.press(key);
		} else {
			await this.page.keyboard.type(key);
		}
	}

	/**
	 * Mirrors PradoGenericSelenium2Test::executeScript().
	 * When args is an array, Selenium scripts reference args via arguments[0],
	 * arguments[1], … — recreate that behaviour with new Function() so the
	 * same script strings work unmodified in Playwright.
	 */
	async executeScript(script, args) {
		if (Array.isArray(args)) {
			return await this.page.evaluate(
				({ script, args }) => new Function('...args', script)(...args),
				{ script, args }
			);
		}
		return await this.page.evaluate(script, args);
	}

	async pause(msec) {
		await this.page.waitForTimeout(parseInt(msec, 10));
	}

	/** Returns a Locator for the currently-focused element */
	active() {
		return this.page.locator(':focus');
	}

	/**
	 * Waits for any in-progress page navigation to finish, then for all AJAX
	 * calls to settle.  Use after actions (Tab / autopostback, form submit) that
	 * may trigger a full-page reload rather than an AJAX callback.
	 */
	async waitForPageLoad() {
		await this.page.waitForLoadState('domcontentloaded');
		await this.waitForAjaxCalls();
	}

	/** Waits until jQuery.active === 0 (all AJAX calls complete) */
	async waitForAjaxCalls() {
		await this.page.waitForFunction(
			"typeof jQuery === 'undefined' || jQuery.active == 0",
			null,
			{ timeout: TIMEOUT }
		);
	}

	// ── Assertions ──────────────────────────────────────────────────────────

	async assertTitle(title) {
		await expect(this.page).toHaveTitle(title, { timeout: TIMEOUT });
	}

	async assertText(id, txt) {
		await expect(this.getLocator(id)).toHaveText(txt, { timeout: TIMEOUT });
	}

	async assertValue(id, txt) {
		await expect(this.getLocator(id)).toHaveValue(txt, { timeout: TIMEOUT });
	}

	async assertVisible(id) {
		await expect(this.getLocator(id)).toBeVisible({ timeout: TIMEOUT });
	}

	async assertNotVisible(id) {
		await this.pause(50);
		await expect(this.getLocator(id)).toBeHidden({ timeout: TIMEOUT });
	}

	async assertElementPresent(id) {
		// XPath locators may match multiple elements — use .first() to avoid strict-mode errors.
		const loc = (typeof id === 'string' && id.startsWith('//'))
			? this.getLocator(id).first()
			: this.getLocator(id);
		await expect(loc).toBeAttached({ timeout: TIMEOUT });
	}

	async assertElementNotPresent(id) {
		await expect(this.getLocator(id)).not.toBeAttached({ timeout: TIMEOUT });
	}

	async assertSourceContains(text) {
		const deadline = Date.now() + TIMEOUT;
		let lastContent = '';
		while (Date.now() < deadline) {
			try {
				lastContent = await this.page.content();
				// Normalize non-breaking spaces to regular spaces before comparison.
				// PHP's ICU formatter uses U+00A0 (older ICU) or U+202F narrow NBSP
				// (ICU 69+) between numbers and currency symbols.
				const normalized = lastContent.replace(/&nbsp;|&#8239;|&#x202[Ff];|[  ]/g, ' ');
				if (normalized.includes(text)) {
					return;
				}
			} catch (_e) {
				// page.content() throws while a navigation is in progress — retry
			}
			await this.page.waitForTimeout(200);
		}
		throw new Error(
			`assertSourceContains: "${text}" not found.\n` +
			`URL: ${this.page.url()}\n` +
			`Source preview (first 500 chars): ${lastContent.slice(0, 500)}`
		);
	}

	async assertSourceNotContains(text) {
		const deadline = Date.now() + TIMEOUT;
		let lastContent = '';
		while (Date.now() < deadline) {
			try {
				lastContent = await this.page.content();
				// Normalize non-breaking spaces to regular spaces before comparison.
				// PHP's ICU formatter uses U+00A0 (older ICU) or U+202F narrow NBSP
				// (ICU 69+) between numbers and currency symbols.
				const normalized = lastContent.replace(/&nbsp;|&#8239;|&#x202[Ff];|[  ]/g, ' ');
				if (!normalized.includes(text)) {
					return;
				}
			} catch (_e) {
				// page.content() throws while a navigation is in progress — retry
			}
			await this.page.waitForTimeout(200);
		}
		throw new Error(
			`assertSourceNotContains: "${text}" was found but should not be.\n` +
			`URL: ${this.page.url()}`
		);
	}

	async assertChecked(id) {
		await expect(this.getLocator(id)).toBeChecked({ timeout: TIMEOUT });
	}

	async assertNotChecked(id) {
		await expect(this.getLocator(id)).not.toBeChecked({ timeout: TIMEOUT });
	}

	/**
	 * Polls a Node-side async predicate until it returns true or TIMEOUT expires.
	 * Replaces expect.poll() which has known issues with async callbacks.
	 *
	 * @param {() => Promise<boolean>} predicate
	 * @param {string} message  Error message on timeout
	 */
	async _poll(predicate, message) {
		const deadline = Date.now() + TIMEOUT;
		while (Date.now() < deadline) {
			if (await predicate()) {
				return;
			}
			await this.page.waitForTimeout(100);
		}
		throw new Error(message);
	}

	/**
	 * Mirrors PradoGenericSelenium2Test::assertAttribute().
	 *
	 * idAttr format:  "elementId@attributeName"
	 * txt can be:
	 *   - null              → attribute must be absent
	 *   - "regexp:pattern"  → attribute value must match pattern
	 *   - any string        → attribute value must equal txt exactly
	 */
	async assertAttribute(idAttr, txt) {
		const atIndex = idAttr.lastIndexOf('@');
		const id = idAttr.slice(0, atIndex);
		const attr = idAttr.slice(atIndex + 1);
		const locator = this.getLocator(id);

		await this._poll(async () => {
			const value = await locator.getAttribute(attr);
			if (txt === null) {
				return value === null;
			}
			if (typeof txt === 'string' && txt.startsWith('regexp:')) {
				return new RegExp(txt.slice(7)).test(value ?? '');
			}
			return value === txt;
		}, `assertAttribute: ${idAttr} expected ${txt}`);
	}

	/** Selected option matches given visible label text */
	async assertSelected(id, label) {
		await this._poll(async () => {
			const text = await this.getLocator(id).evaluate(
				(el) => el.options[el.selectedIndex]?.text ?? ''
			);
			return text === String(label);
		}, `assertSelected: #${id} expected label "${label}"`);
	}

	/** All selected options match given array of label texts (order-sensitive) */
	async assertSelectedMultiple(id, labelsArr) {
		await this._poll(async () => {
			const selected = await this.getLocator(id).evaluate((el) =>
				Array.from(el.selectedOptions).map((o) => o.text)
			);
			return JSON.stringify(selected) === JSON.stringify(labelsArr);
		}, `assertSelectedMultiple: #${id} expected [${labelsArr.join(', ')}]`);
	}

	/** No option is selected */
	async assertNotSomethingSelected(id) {
		const count = await this.getLocator(id).evaluate(
			(el) => el.selectedOptions.length
		);
		expect(count).toBe(0);
	}

	/** First selected option's value attribute equals expected value */
	async assertSelectedValue(id, value) {
		const val = await this.byId(id).evaluate(
			(el) => el.options[el.selectedIndex]?.value ?? ''
		);
		expect(val).toBe(value);
	}

	/**
	 * Mirrors PradoGenericSelenium2Test::assertSelectedIndex().
	 * Finds the 0-based position of the currently-selected option in the full
	 * options list and asserts it equals expectedIndex.
	 */
	async assertSelectedIndex(id, expectedIndex) {
		const options = await this.getSelectOptions(id);
		const selectedText = await this.getLocator(id).evaluate(
			(el) => el.options[el.selectedIndex]?.text ?? ''
		);
		const idx = options.indexOf(selectedText);
		expect(idx).toBe(expectedIndex);
	}

	// ── Alert / dialog handling ──────────────────────────────────────────────
	//
	// Dialogs are auto-accepted as they appear (see constructor) and their
	// message text is pushed into _dialogQueue.  The test then calls
	// alertText() / acceptAlert() / dismissAlert() synchronously after any
	// pause() that gave the dialog time to fire.

	/** Returns the message text of the oldest unhandled dialog */
	alertText() {
		return this._dialogQueue[0] ?? '';
	}

	/** Removes the oldest dialog from the queue (it was already accepted) */
	acceptAlert() {
		this._dialogQueue.shift();
	}

	/** Removes the oldest dialog from the queue (it was already accepted) */
	dismissAlert() {
		this._dialogQueue.shift();
	}

	/**
	 * Marks that the NEXT dialog that fires should be dismissed (Cancel) rather
	 * than accepted (OK).  Call this immediately before the action that triggers
	 * the dialog.  The flag is automatically cleared after use.
	 */
	dismissNextAlert() {
		this._dismissNext = true;
	}

	async assertAlertPresent() {
		await this._poll(
			async () => this._dialogQueue.length > 0,
			'assertAlertPresent: no dialog appeared within timeout'
		);
	}

	async assertAlertNotPresent() {
		await this.pause(100);
		expect(this._dialogQueue.length).toBe(0);
	}
}

/**
 * Factory for tests that target the generic prado test app.
 * Usage inside a test:
 *   const h = genericHelper(page);
 */
export function genericHelper(page) {
	return new PradoTestHelper(page, GENERIC_BASE_URL);
}

/**
 * Factory for tests that target the prado-demos app.
 * Usage inside a test:
 *   const h = demosHelper(page);
 */
export function demosHelper(page) {
	return new PradoTestHelper(page, DEMOS_BASE_URL);
}
