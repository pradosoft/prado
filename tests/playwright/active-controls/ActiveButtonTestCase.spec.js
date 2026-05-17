import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('ActiveButtonTestCase', async ({ page }) => {
  const h = genericHelper(page);
  const base = 'ctl0_Content_';
  await h.url('active-controls/index.php?page=ActiveButtonTest');
  await h.assertSourceContains('TActiveButton Functional Test');
  await h.assertText(`${base}label1`, 'Label 1');
  await h.byId(`${base}button2`).click();
  await h.assertText(`${base}label1`, 'Button 1 was clicked using callback!');
});
