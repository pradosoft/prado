import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('ActiveImageButtonTestCase', async ({ page }) => {
  const h = genericHelper(page);
  const base = 'ctl0_Content_';
  await h.url('active-controls/index.php?page=ActiveImageButtonTest');
  await h.assertSourceContains('TActiveImageButton Functional Test');
  await h.assertText(`${base}label1`, 'Label 1');
  await h.byId(`${base}image1`).click();
  // unable to determine mouse position
  await h.assertSourceContains('Image clicked at x=');
});
