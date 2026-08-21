import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=WebTemplateDomTest';

/**
 * Browser-engine behaviors of TWebTemplate that unit tests cannot prove:
 * declarative shadow DOM parsing, <tr> content stamped into a real table,
 * multi-root instances, insert positions, instanceOf() event delegation,
 * markup-injection safety, and TrackInstances="false".
 */
test('TWebTemplateDomTestCase', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await h.assertSourceContains('Web Template DOM Test Case');

	// ── Declarative shadow DOM: the parser attached an open shadow root and
	//    consumed the <template> element ──
	const open = await page.evaluate(() => {
		const host = document.getElementById('openHost');
		const p = host.shadowRoot && host.shadowRoot.querySelector('.dsd');
		return {
			hasShadowRoot: !!host.shadowRoot,
			templateRemains: !!host.querySelector('template'),
			text: p ? p.textContent : null,
			color: p ? getComputedStyle(p).color : null,
			// the shadow style must not leak to the light DOM
			lightDsd: document.querySelector('body .dsd') === null
		};
	});
	expect(open.hasShadowRoot).toBe(true);
	expect(open.templateRemains).toBe(false);
	expect(open.text).toBe('open shadow content');
	// the <style> inside the shadow root applied to its content
	expect(open.color).toBe('rgb(102, 51, 153)');

	// ── Closed mode: element consumed, root unreachable from script ──
	const closed = await page.evaluate(() => {
		const host = document.getElementById('closedHost');
		return {
			shadowRoot: host.shadowRoot,
			templateRemains: !!host.querySelector('template')
		};
	});
	expect(closed.shadowRoot).toBeNull();
	expect(closed.templateRemains).toBe(false);

	// ── Fragment reads: getContent / find / findAll / clone against the live
	//    inert fragment ──
	const reads = await page.evaluate(() => {
		const tpl = Prado.WebUI.TWebTemplate.get('ctl0_Content_rowTpl');
		const content = tpl.getContent();
		const clone = tpl.clone({ name: 'Cloned', qty: 9 });
		return {
			isFragment: content instanceof DocumentFragment,
			findCell: tpl.find('.cell') ? tpl.find('.cell').textContent : null,
			findMissing: tpl.find('.does-not-exist'),
			findAllCount: tpl.findAll('td').length,
			cloneIsFragment: clone instanceof DocumentFragment,
			cloneText: clone.querySelector('.cell').textContent,
			cloneDetached: !clone.querySelector('.cell').isConnected,
			// cloning with data must not consume the template's own placeholders
			originalIntact: tpl.find('.cell').textContent
		};
	});
	expect(reads.isFragment).toBe(true);
	expect(reads.findCell).toBe('{{name}}');
	expect(reads.findMissing).toBeNull();
	expect(reads.findAllCount).toBe(2);
	expect(reads.cloneIsFragment).toBe(true);
	expect(reads.cloneText).toBe('Cloned');
	expect(reads.cloneDetached).toBe(true);
	expect(reads.originalIntact).toBe('{{name}}');

	// ── <tr> template content stamps into a real table body ──
	await page.locator('#btnRows').click();
	await expect(page.locator('#tbl tbody tr.dataRow')).toHaveCount(2);
	await expect(page.locator('#tbl tbody tr .cell')).toHaveText(['Widget', 'Gadget']);
	// the rows are genuine table rows, not foster-parented text
	const rowsInTbody = await page.evaluate(
		() => document.getElementById('tblBody').children.length
	);
	expect(rowsInTbody).toBe(2);

	// ── Multi-root: both roots share one instance UID ──
	await page.locator('#btnPair').click();
	await expect(page.locator('#dict .term')).toHaveText('HTML');
	await expect(page.locator('#dict .def')).toHaveText('markup');
	const pairUids = await page
		.locator('#dict [data-prado-instance]')
		.evaluateAll((nodes) => nodes.map((n) => n.getAttribute('data-prado-instance')));
	expect(pairUids.length).toBe(2);
	expect(pairUids[0]).toBe(pairUids[1]);

	// ── instanceOf() event delegation: a click inside a copy resolves its data ──
	await page.locator('#dict .term').click();
	await expect(page.locator('#clickedTerm')).toHaveText('HTML');

	// ── prependTo / insertBefore / insertAfter land in the right positions ──
	await page.locator('#btnPositions').click();
	await expect(page.locator('#miscBody .who')).toHaveText(['First', 'Before', 'After']);
	const order = await page.evaluate(() =>
		[...document.getElementById('miscBody').children].map(
			(el) => el.id || el.querySelector('.who').textContent
		)
	);
	expect(order).toEqual(['First', 'Before', 'anchor', 'After']);
	// dotted-path placeholders resolved in the browser
	await expect(page.locator('#miscBody .what').nth(0)).toHaveText('f');

	// ── data cannot inject markup: the payload stays text, no handler runs ──
	await page.locator('#btnXss').click();
	await expect(page.locator('#miscBody .who').nth(3)).toHaveText('Mallory');
	const xss = await page.evaluate(() => ({
		fired: window.__xss === 1,
		imgs: document.querySelectorAll('#miscBody img').length,
		text: document.querySelectorAll('#miscBody .what')[3].textContent
	}));
	expect(xss.fired).toBe(false);
	expect(xss.imgs).toBe(0);
	expect(xss.text).toContain('<img');

	// ── TrackInstances="false": stamped, but no instance bookkeeping ──
	await page.locator('#btnUntracked').click();
	await expect(page.locator('#untrackedBody .untracked')).toHaveText('plain');
	const untracked = await page.evaluate(() => {
		const node = document.querySelector('#untrackedBody .untracked');
		return {
			hasAttr: node.hasAttribute('data-prado-instance'),
			instance: Prado.WebUI.TWebTemplate.instanceOf(node)
		};
	});
	expect(untracked.hasAttr).toBe(false);
	expect(untracked.instance).toBeNull();
});
