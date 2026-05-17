import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('CalculatorTestCase', async ({ page }) => {
  const h = genericHelper(page);
  const base = 'ctl0_Content_';
  await h.url('active-controls/index.php?page=Calculator');
  await h.assertSourceContains('Callback Enabled Calculator');
  await h.assertNotVisible(`${base}summary`);

  await h.byId(`${base}sum`).click();
  await h.assertVisible(`${base}summary`);

  await h.type(`${base}a`, '2');
  await h.type(`${base}b`, '5');

  await h.byId(`${base}sum`).click();
  await h.pause(500);

  await h.assertNotVisible(`${base}summary`);
  await h.assertValue(`${base}c`, '7');
});
