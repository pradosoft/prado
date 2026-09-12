import { writeFileSync } from 'node:fs';
import { test } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const OUT = '/private/tmp/claude-501/-Users-user-Code-prado-html-5-semantic/f0704ee5-1e4a-4f44-b62a-a9da50b52ab9/scratchpad';
const NAMES = ['normal', 'hover', 'focus', 'press'];

test.use({ deviceScaleFactor: 1 });

test('diag', async ({ page, context }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url('web/index.php?page=ColorSchemeGalleryTest');
	const cdp = await context.newCDPSession(page);
	await cdp.send('DOM.enable');
	await cdp.send('CSS.enable');
	const { root } = await cdp.send('DOM.getDocument');
	const { nodeIds } = await cdp.send('DOM.querySelectorAll', {
		nodeId: root.nodeId,
		selector: '#shot-tabpanel .tab-normal',
	});
	await cdp.send('CSS.forcePseudoState', { nodeId: nodeIds[1], forcedPseudoClasses: ['hover'] });
	await cdp.send('CSS.forcePseudoState', {
		nodeId: nodeIds[2],
		forcedPseudoClasses: ['focus', 'focus-visible'],
	});
	await cdp.send('CSS.forcePseudoState', {
		nodeId: nodeIds[3],
		forcedPseudoClasses: ['hover', 'active'],
	});
	for (const scheme of ['light', 'dark']) {
		await page.evaluate((s) => {
			document.documentElement.style.colorScheme = s;
		}, scheme);
		const tabs = page.locator('#shot-tabpanel .tab-normal');
		for (let i = 0; i < NAMES.length; i++) {
			writeFileSync(`${OUT}/t-${NAMES[i]}-${scheme}.png`, await tabs.nth(i).screenshot());
		}
	}
});
