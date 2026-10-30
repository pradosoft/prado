import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=CallbackMorphTest';
const B = 'ctl0_Content_';

async function open(page) {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await h.assertSourceContains('Callback Morph Test Case');
	return h;
}

async function refresh(page, count) {
	await page.locator(`#${B}refresh`).click();
	await expect(page.locator(`#${B}count`)).toHaveText(String(count));
}

/**
 * A control with UpdateMode Morph loads idiomorph.
 */
test('TCallbackMorphTestCase: the morph package loads idiomorph', async ({ page }) => {
	await open(page);
	expect(await page.evaluate(() => typeof Idiomorph)).toBe('object');
});

/**
 * Morphed nodes keep their identity and listeners; a replaced panel gets new nodes.
 */
test('TCallbackMorphTestCase: morphed nodes keep listeners, replaced nodes do not', async ({ page }) => {
	await open(page);
	await page.evaluate(() => {
		window.__marker = document.getElementById('marker');
		window.__clicks = 0;
		window.__marker.addEventListener('click', () => window.__clicks++);
		window.__replaced = document.getElementById('replacedMarker');
	});
	await refresh(page, 1);
	const result = await page.evaluate(() => {
		document.getElementById('marker').click();
		return {
			sameMarker: document.getElementById('marker') === window.__marker,
			clicks: window.__clicks,
			sameReplaced: document.getElementById('replacedMarker') === window.__replaced,
			replacedText: document.getElementById('replacedMarker').textContent,
			other: document.getElementById('ctl0_Content_other').value,
		};
	});
	expect(result).toEqual({ sameMarker: true, clicks: 1, sameReplaced: false, replacedText: 'replaced 1', other: 'server 1' });
});

/**
 * SVG content keeps its namespace, references, and nodes while its attributes update.
 */
test('TCallbackMorphTestCase: SVG content morphs in place', async ({ page }) => {
	await open(page);
	await page.evaluate(() => { window.__dot = document.getElementById('art_dot'); });
	await refresh(page, 1);
	const svg = await page.evaluate(() => {
		const dot = document.getElementById('art_dot');
		const use = document.getElementById('art_use');
		return {
			sameDot: dot === window.__dot,
			r: dot.getAttribute('r'),
			ns: dot.namespaceURI,
			href: use.getAttributeNS('http://www.w3.org/1999/xlink', 'href'),
			useWidth: use.getBBox().width,
		};
	});
	expect(svg).toEqual({ sameDot: true, r: '6', ns: 'http://www.w3.org/2000/svg', href: '#art_r', useWidth: 10 });
});

/**
 * A changed inline script runs once; an unchanged one does not run again; a skipped subtree is kept.
 */
test('TCallbackMorphTestCase: scripts and the skip marker', async ({ page }) => {
	await open(page);
	await refresh(page, 1);
	await refresh(page, 2);
	const state = await page.evaluate(() => ({
		staticRuns: window.staticRuns,
		dynamicRuns: window.dynamicRuns,
		lastDynamic: window.lastDynamic,
		widget: document.getElementById('widget').textContent,
	}));
	expect(state).toEqual({ staticRuns: 1, dynamicRuns: 3, lastDynamic: 2, widget: 'server 0' });
});

/**
 * Text typed while a callback is in flight, the focus, and the caret survive the update.
 */
test('TCallbackMorphTestCase: typing during a callback is kept', async ({ page }) => {
	await open(page);
	const note = page.getByRole('textbox', { name: 'Note' });
	await note.click();
	await page.keyboard.type('abc');
	await page.evaluate(() => document.getElementById('ctl0_Content_slow').click());
	await page.waitForTimeout(150);
	await page.keyboard.type('def');
	await expect(page.locator(`#${B}count`)).toHaveText('1');
	await expect(note).toHaveValue('abcdef');
	await expect(note).toBeFocused();
	expect(await note.evaluate((el) => el.selectionStart)).toBe(6);
	await expect(page.getByRole('textbox', { name: 'Other' })).toHaveValue('server 1');
});

/**
 * A focused field the user has not changed since the callback was sent takes the server value.
 */
test('TCallbackMorphTestCase: an unchanged focused field takes the server value', async ({ page }) => {
	await open(page);
	const note = page.getByRole('textbox', { name: 'Note' });
	await note.click();
	await page.keyboard.type('abc');
	await page.evaluate(() => document.getElementById('ctl0_Content_slow').click());
	await expect(page.locator(`#${B}count`)).toHaveText('1');
	await expect(note).toHaveValue('ABC');
	await expect(note).toBeFocused();
});

/**
 * Client control wrappers registered again after a morph add no duplicate handlers.
 */
test('TCallbackMorphTestCase: one request per click after updates', async ({ page }) => {
	await open(page);
	await refresh(page, 1);
	await refresh(page, 2);
	let posts = 0;
	page.on('request', (r) => { if (r.method() === 'POST') posts++; });

	await page.locator(`#${B}inner`).click();
	await expect(page.locator(`#${B}innerCount`)).toHaveText('1');
	await page.waitForTimeout(500);
	expect(posts).toBe(1);

	posts = 0;
	await page.locator(`#${B}rating td`).nth(1).click();
	await expect(page.locator(`#${B}ratingCount`)).toHaveText('1');
	await page.waitForTimeout(500);
	expect(posts).toBe(1);
});

/**
 * A live region whose text is unchanged is not touched, so it is not announced again.
 */
test('TCallbackMorphTestCase: an unchanged live region is not touched', async ({ page }) => {
	await open(page);
	await page.evaluate(() => {
		window.__liveMutations = 0;
		const live = document.getElementById('live');
		window.__live = live;
		new MutationObserver((records) => { window.__liveMutations += records.length; })
			.observe(live, { childList: true, characterData: true, subtree: true });
	});
	await refresh(page, 1);
	const live = await page.evaluate(() => ({
		same: document.getElementById('live') === window.__live,
		mutations: window.__liveMutations,
	}));
	expect(live).toEqual({ same: true, mutations: 0 });
});

/**
 * Repeater item IDs follow the item index, so a morphed row node follows its position.
 */
test('TCallbackMorphTestCase: repeater rows pair by position', async ({ page }) => {
	await open(page);
	await page.evaluate(() => {
		window.__first = document.querySelector('.row');
	});
	await page.locator(`#${B}insertTop`).click();
	await expect(page.locator('.row')).toHaveCount(4);
	const rows = await page.evaluate(() => ({
		names: [...document.querySelectorAll('.row .name')].map((el) => el.textContent),
		firstKept: document.querySelector('.row') === window.__first,
		firstName: window.__first.querySelector('.name').textContent,
	}));
	expect(rows).toEqual({ names: ['a3', 'b', 'c', 'd'], firstKept: true, firstName: 'a3' });
});

/**
 * A repeater row deleted at the top: the remaining row nodes pair by position and the last is removed.
 */
test('TCallbackMorphTestCase: a repeater row deleted at the top', async ({ page }) => {
	await open(page);
	await page.evaluate(() => {
		window.__rows = [...document.querySelectorAll('.row')];
	});
	await page.locator(`#${B}deleteTop`).click();
	await expect(page.locator('.row')).toHaveCount(2);
	const rows = await page.evaluate(() => {
		const now = [...document.querySelectorAll('.row')];
		return {
			names: now.map((row) => row.querySelector('.name').textContent),
			kept: now.map((row, i) => row === window.__rows[i]),
			lastRemoved: !document.contains(window.__rows[2]),
		};
	});
	expect(rows).toEqual({ names: ['c', 'd'], kept: [true, true], lastRemoved: true });
});

/**
 * TActiveDataGrid rows inserted and deleted at the top: the grid and row nodes are kept by
 * position, a listener on the grid survives, and each field shows its record's server value.
 */
test('TCallbackMorphTestCase: grid rows inserted and deleted at the top', async ({ page }) => {
	await open(page);
	const snapshot = () => page.evaluate(() => {
		const rows = [...document.querySelectorAll('table.grid .gname')].map((el) => el.closest('tr'));
		return {
			names: rows.map((row) => row.querySelector('.gname').textContent),
			values: rows.map((row) => row.querySelector('.gvalue').value),
			kept: window.__gridRows.map((row, i) => row === rows[i]),
			sameGrid: document.querySelector('table.grid') === window.__grid,
		};
	});
	const remember = () => page.evaluate(() => {
		window.__gridRows = [...document.querySelectorAll('table.grid .gname')].map((el) => el.closest('tr'));
	});
	await page.evaluate(() => {
		window.__grid = document.querySelector('table.grid');
		window.__gridClicks = 0;
		window.__grid.addEventListener('click', () => window.__gridClicks++);
	});
	await remember();

	await page.locator(`#${B}gridInsertTop`).click();
	await expect(page.locator('table.grid .gname')).toHaveCount(4);
	expect(await snapshot()).toEqual({
		names: ['a3', 'b', 'c', 'd'],
		values: ['v-a3', 'v-b', 'v-c', 'v-d'],
		kept: [true, true, true],
		sameGrid: true,
	});
	await page.locator('table.grid .gname').first().click();
	expect(await page.evaluate(() => window.__gridClicks)).toBe(1);

	await remember();
	await page.locator(`#${B}gridDeleteTop`).click();
	await expect(page.locator('table.grid .gname')).toHaveCount(3);
	expect(await snapshot()).toEqual({
		names: ['b', 'c', 'd'],
		values: ['v-b', 'v-c', 'v-d'],
		kept: [true, true, true, false],
		sameGrid: true,
	});
	expect(await page.evaluate(() => !document.contains(window.__gridRows[3]))).toBe(true);
});

/**
 * A 500-row TActiveDataGrid morphs in one pass well inside a second; the time is annotated.
 */
test('TCallbackMorphTestCase: a 500-row grid morphs in time', async ({ page }) => {
	await open(page);
	await expect(page.locator('table.big .bname')).toHaveCount(500);
	await page.evaluate(() => {
		const morph = Prado.Element.morph;
		window.__morphMs = [];
		Prado.Element.morph = function (...args) {
			const start = performance.now();
			const result = morph.apply(this, args);
			window.__morphMs.push(performance.now() - start);
			return result;
		};
		window.__bigRow = document.querySelector('table.big .bname').closest('tr');
	});
	await page.locator(`#${B}bigRefresh`).click();
	await expect(page.locator('table.big .bname').last()).toHaveText('r500-1');
	const big = await page.evaluate(() => ({
		rows: document.querySelectorAll('table.big .bname').length,
		kept: document.querySelector('table.big .bname').closest('tr') === window.__bigRow,
		ms: window.__morphMs,
	}));
	test.info().annotations.push({ type: 'morph-ms', description: big.ms.map((ms) => ms.toFixed(1)).join(', ') });
	expect(big.rows).toBe(500);
	expect(big.kept).toBe(true);
	expect(big.ms).toHaveLength(1);
	expect(big.ms[0]).toBeLessThan(1000);
});
