import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('ActiveControlExpressionTagTestCase', async ({ page }) => {
  const h = genericHelper(page);
  const base = 'ctl0_Content_';
  await h.url('active-controls/index.php?page=ActiveControlExpressionTag');
  await h.assertSourceContains('Active Control With Expression Tag Test');
  await h.assertSourceNotContains('Text box content:');

  await h.type(`${base}textbox1`, 'Hello world');
  await h.byId(`${base}button1`).click();

  await h.assertText('repeats', 'result - 1 result - two');
  await h.assertText('contents', 'Text box content: Hello world');
});
