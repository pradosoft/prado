import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('ActiveHyperLinkTestCase', async ({ page }) => {
  const h = genericHelper(page);
  const base = 'ctl0_Content_';
  await h.url('active-controls/index.php?page=ActiveHyperLinkTest');
  await h.assertSourceContains('Active HyperLink Test Case');

  await h.assertText(`${base}link1`, 'Link 1');

  await h.byId(`${base}button1`).click();
  await h.assertText(`${base}link1`, 'Prado framework');
});
