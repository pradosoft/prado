import { test } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

test('diag', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url('web/index.php?page=ColorSchemeGalleryTest');
	const info = await page.evaluate(() => {
		const gaps = (sliderSel) => {
			const s = document.querySelector(sliderSel);
			const t = s.querySelector('.Track');
			const sr = s.getBoundingClientRect();
			const tr = t.getBoundingClientRect();
			const cs = getComputedStyle(s);
			const bl = parseFloat(cs.borderLeftWidth), bt = parseFloat(cs.borderTopWidth);
			const br = parseFloat(cs.borderRightWidth), bb = parseFloat(cs.borderBottomWidth);
			return {
				left: +(tr.left - (sr.left + bl)).toFixed(2),
				right: +((sr.right - br) - tr.right).toFixed(2),
				top: +(tr.top - (sr.top + bt)).toFixed(2),
				bottom: +((sr.bottom - bb) - tr.bottom).toFixed(2),
			};
		};
		return {
			horizontal: gaps('.HorizontalSlider'),
			vertical: gaps('.VerticalSlider'),
		};
	});
	console.log(JSON.stringify(info));
});
