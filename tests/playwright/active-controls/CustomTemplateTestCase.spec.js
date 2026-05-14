import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('CustomTemplateTestCase', async ({ page }) => {
  const h = genericHelper(page);
  const base = 'ctl0_Content_';
  await h.url('active-controls/index.php?page=CustomTemplateControlTest');
  await h.assertSourceContains('Add Dynamic Custom TTemplateControl Test');
  await h.assertText(`${base}label1`, 'Label 1');

  await h.type(`${base}foo`, 'Foo Bar!');
  await h.byId(`${base}button2`).click();

  await h.assertVisible(`${base}ctl0_ThePanel`);
  await h.assertSourceContains(`Client ID: ${base}ctl0_ThePanel`);

  await h.assertText(`${base}label1`, 'Button 1 was clicked Foo Bar! using callback!... and this is the textbox text: Foo Bar!');
});
