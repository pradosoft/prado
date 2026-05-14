import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('ActiveCheckBoxTestCase', async ({ page }) => {
  const h = genericHelper(page);
  const base = 'ctl0_Content_';
  await h.url('active-controls/index.php?page=ActiveCheckBoxTest');
  await h.assertSourceContains('Active CheckBox Test');

  await h.assertText(`${base}checkbox1_label`, 'CheckBox 1');
  await h.assertText(`${base}checkbox2_label`, 'CheckBox 2');
  await h.assertText(`${base}label1`, 'Label 1');

  await h.byId(`${base}change_text1`).click();
  await h.assertText(`${base}checkbox1_label`, 'Hello CheckBox 1');

  await h.byId(`${base}change_text2`).click();
  await h.assertText(`${base}checkbox2_label`, 'CheckBox 2 World');

  // check box 1
  await h.byId(`${base}change_checked1`).click();
  await h.assertChecked(`${base}checkbox1`);

  await h.byId(`${base}change_checked1`).click();
  await h.assertNotChecked(`${base}checkbox1`);

  // check box 2
  await h.byId(`${base}change_checked2`).click();
  await h.assertChecked(`${base}checkbox2`);

  await h.byId(`${base}change_checked2`).click();
  await h.assertNotChecked(`${base}checkbox2`);

  // click checkbox 1
  await h.byId(`${base}checkbox1`).click();
  await h.assertText(`${base}label1`, 'Label 1:Hello CheckBox 1 Checked');

  await h.byId(`${base}checkbox1`).click();
  await h.assertText(`${base}label1`, 'Label 1:Hello CheckBox 1 Not Checked');

  // click checkbox 2
  await h.byId(`${base}checkbox2`).click();
  await h.assertText(`${base}label1`, 'Label 1:CheckBox 2 World Checked');

  await h.byId(`${base}checkbox2`).click();
  await h.assertText(`${base}label1`, 'Label 1:CheckBox 2 World Not Checked');
});
