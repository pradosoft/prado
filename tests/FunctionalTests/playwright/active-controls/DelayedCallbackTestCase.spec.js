import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('DelayedCallbackTestCase', async ({ page }) => {
  const h = genericHelper(page);
  const base = 'ctl0_Content_';
  await h.url('active-controls/index.php?page=DelayedCallback');
  await h.assertSourceContains('Delayed Callback Test');

  await h.assertText(`${base}status`, '');
  await h.byId(`${base}button1`).click();
  await h.byId(`${base}button2`).click();

  await h.pause(5000);
  await h.assertText(`${base}status`, 'Callback 1 returned after 4s');
  await h.pause(3000);
  await h.assertText(`${base}status`, 'Callback 2 delayed 2s');
});
