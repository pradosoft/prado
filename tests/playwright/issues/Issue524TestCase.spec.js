import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Issue524TestCase', async ({ page }) => {
  const h = genericHelper(page);
  await h.url('issues/index.php?page=Issue524');
  await h.assertSourceContains('Issue 524 Test');
  const base = 'ctl0_Content_';

  await h.byId(`${base}buttonOk`).click();
  await h.assertText(`${base}Validator`, 'fünf');
});
