import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('NestedActiveControlsTestCase', async ({ page }) => {
  const h = genericHelper(page);
  const base = 'ctl0_Content_';
  await h.url('active-controls/index.php?page=NestedActiveControls');
  await h.assertSourceContains('Nested Active Controls Test');
  await h.assertText(`${base}label1`, 'Label 1');
  await h.assertText(`${base}label2`, 'Label 2');
  await h.assertSourceNotContains('Label 3');

  await h.byId('div1').click();
  await h.assertSourceContains('Something lalala');
  await h.assertText(`${base}label3`, 'Label 3');

  await h.byId(`${base}button1`).click();
  await h.assertText(`${base}label1`, 'Label 1: Button 1 Clicked');
  await h.assertText(`${base}label2`, 'Label 2: Button 1 Clicked');
  await h.assertText(`${base}label3`, 'Label 3: Button 1 Clicked');
});
