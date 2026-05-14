import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('ValueTriggerCallbackTestCase', async ({ page }) => {
  const h = genericHelper(page);
  const base = 'ctl0_Content_';
  await h.url('active-controls/index.php?page=ValueTriggerCallbackTest');
  await h.assertSourceContains('Value Trigger Callback Test');

  await h.assertText(`${base}label1`, 'Label 1');

  await h.type(`${base}text1`, 'test');
  await h.pause(3000);
  await h.assertText(`${base}label1`, 'Old = : New Value = test');

  await h.type(`${base}text1`, 'more');
  await h.pause(3000);
  await h.assertText(`${base}label1`, 'Old = test : New Value = more');
});
