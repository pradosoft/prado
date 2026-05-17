import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('CallbackOptionsTestCase', async ({ page }) => {
  const h = genericHelper(page);
  const base = 'ctl0_Content_';
  await h.url('active-controls/index.php?page=CallbackOptionsTest');
  await h.assertSourceContains('TCallbackOptions Test');

  await h.assertText('label1', 'Label 1');
  await h.assertText('label2', 'Label 2');
  await h.assertText('label3', 'Label 3');

  await h.byId(`${base}button1`).click();
  await h.assertText('label1', 'Button 1 has returned');
  await h.assertText('label2', 'Label 2');
  await h.assertText('label3', 'Label 3');

  await h.byId(`${base}button2`).click();
  await h.assertText('label1', 'Button 1 has returned');
  await h.assertText('label2', 'Button 2 has returned');
  await h.assertText('label3', 'Label 3');

  await h.byId(`${base}button3`).click();
  await h.assertText('label1', 'Button 1 has returned');
  await h.assertText('label2', 'Button 2 has returned');
  await h.assertText('label3', 'Button 3 has returned');
});
