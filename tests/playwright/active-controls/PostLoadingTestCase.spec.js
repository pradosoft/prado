import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('PostLoadingTestCase', async ({ page }) => {
  const h = genericHelper(page);
  const base = 'ctl0_Content_';
  await h.url('active-controls/index.php?page=PostLoadingTest');
  await h.assertSourceContains('PostLoading Test');

  await h.assertSourceNotContains('Hello World');

  await h.byId('div1').click();
  await h.pause(1000);
  await h.type(`${base}MyTextBox`, 'Hello World');
  // workaround for "stale element reference: element is not attached to the page document"
  await h.pause(1000);
  await h.byId(`${base}MyButton`);
  await h.byId(`${base}MyButton`).click();

  await h.assertSourceContains('Result is Hello World');
});
