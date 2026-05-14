import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Issue516TestCase', async ({ page }) => {
  const h = genericHelper(page);
  await h.url('issues/index.php?page=Issue516');
  await h.assertSourceContains('Issue 516 Test');
  const base = 'ctl0_Content_';
  const row1 = 'DataGrid_ctl1_';
  const row2 = 'DataGrid_ctl2_';

  // click "edit" and check for textbox
  await h.byId(`${base}${row1}ctl3`).click();
  await h.assertElementPresent(`${base}${row1}TextBox`);
  // click "save" and check for validator
  await h.byId(`${base}${row1}ctl3`).click();
  await h.assertText(`${base}${row1}ctl1`, 'Please provide a title.');
  // click "cancel" and ensure validator has disappeared
  await h.byId(`${base}${row1}ctl4`).click();
  await h.assertElementNotPresent(`${base}${row1}ctl1`);

  // click "edit" and check for textbox on the second row
  await h.byId(`${base}${row2}ctl3`).click();
  await h.assertElementPresent(`${base}${row2}TextBox`);
  // click "save" and ensure validation has been successful
  await h.byId(`${base}${row2}ctl3`).click();
  await h.assertElementNotPresent(`${base}${row2}ctl1`);
  await h.assertElementNotPresent(`${base}${row2}TextBox`);
  await h.assertText(`${base}${row2}ctl3`, 'Edit');
});
