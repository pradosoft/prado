import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('ActivePanelTestCase', async ({ page }) => {
  const h = genericHelper(page);
  await h.url('active-controls/index.php?page=ActivePanelTest');
  await h.assertSourceContains('Active Panel replacement tests');
  await h.assertSourceNotContains('Something lalala');
  await h.byId('div1').click();
  await h.assertSourceContains('Something lalala');
  await h.assertText('panelScriptRuns', '1');
  expect(await page.evaluate(() => window.siblingScriptRuns)).toBe(1);
});
