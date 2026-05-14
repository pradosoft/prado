import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Issue504TestCase', async ({ page }) => {
  const h = genericHelper(page);
  await h.url('issues/index.php?page=Issue504');
  await h.assertSourceContains('Issue 504 Test');
  const base = 'ctl0_Content_';

  await h.byId(`${base}textbox1`).click();
  await h.keys('Enter');
  await h.pause(50);

  await h.assertText(`${base}label1`, 'buttonOkClick');
});
