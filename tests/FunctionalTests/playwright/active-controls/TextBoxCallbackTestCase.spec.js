import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('TextBoxCallbackTestCase', async ({ page }) => {
  const h = genericHelper(page);
  const base = 'ctl0_Content_';
  await h.url('active-controls/index.php?page=ActiveTextBoxCallback');
  await h.assertSourceContains('ActiveTextBox Callback Test');
  await h.assertText(`${base}label1`, 'Label 1');

  await h.type(`${base}textbox1`, 'hello!');
  await h.assertText(`${base}label1`, 'Label 1: hello!');
});
