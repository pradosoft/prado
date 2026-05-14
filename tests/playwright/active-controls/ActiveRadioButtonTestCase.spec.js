import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('ActiveRadioButtonTestCase', async ({ page }) => {
  const h = genericHelper(page);
  const base = 'ctl0_Content_';
  await h.url('active-controls/index.php?page=ActiveRadioButtonTest');
  await h.assertSourceContains('Active Radio Button Test');
  await h.assertText(`${base}label1`, 'Label 1');

  await h.assertNotChecked(`${base}radio1`);
  await h.assertNotChecked(`${base}radio2`);
  await h.assertNotChecked(`${base}radio3`);

  await h.assertText(`${base}radio1_label`, 'Radio Button 1');
  await h.assertText(`${base}radio2_label`, 'Radio Button 2');
  await h.assertText(`${base}radio3_label`, 'Radio Button 3');

  await h.byId(`${base}change_text1`).click();
  await h.assertText(`${base}radio1_label`, 'Hello Radio Button 1');
  await h.assertText(`${base}radio2_label`, 'Radio Button 2');
  await h.assertText(`${base}radio3_label`, 'Radio Button 3');

  await h.byId(`${base}change_text2`).click();
  await h.assertText(`${base}radio1_label`, 'Hello Radio Button 1');
  await h.assertText(`${base}radio2_label`, 'Radio Button 2 World');
  await h.assertText(`${base}radio3_label`, 'Radio Button 3');

  await h.byId(`${base}change_radio1`).click();
  await h.assertChecked(`${base}radio1`);
  await h.assertNotChecked(`${base}radio2`);
  await h.assertNotChecked(`${base}radio3`);

  await h.byId(`${base}change_radio2`).click();
  await h.assertNotChecked(`${base}radio1`);
  await h.assertChecked(`${base}radio2`);
  await h.assertNotChecked(`${base}radio3`);

  await h.byId(`${base}radio3`).click();
  await h.assertNotChecked(`${base}radio1`);
  await h.assertChecked(`${base}radio2`);
  await h.assertChecked(`${base}radio3`);
  await h.assertText(`${base}label1`, 'Label 1:Radio Button 3 Checked');
});
