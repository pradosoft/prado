import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('DataTypeValidatorTestCase', async ({ page }) => {
  const h = genericHelper(page);
  const base = 'ctl0_Content_';
  await h.url('validators/index.php?page=DataTypeValidator');
  await h.assertSourceContains('Data Type Validator Tests');
  await h.byXPath("//input[@type='submit' and @value='submit!']").click();
  await h.pause(500);

  await h.assertNotVisible(`${base}validator1`);
  await h.assertNotVisible(`${base}validator2`);
  await h.assertNotVisible(`${base}validator3`);

  await h.type(`${base}textbox1`, 'a');
  await h.type(`${base}textbox2`, 'b');
  await h.type(`${base}textbox3`, 'c');
  await h.byXPath("//input[@type='submit' and @value='submit!']").click();
  await h.pause(500);

  await h.assertVisible(`${base}validator1`);
  await h.assertVisible(`${base}validator2`);
  await h.assertVisible(`${base}validator3`);

  await h.type(`${base}textbox1`, '12');
  await h.type(`${base}textbox2`, '12.5');
  await h.type(`${base}textbox3`, '2/10/2005');
  await h.byXPath("//input[@type='submit' and @value='submit!']").click();

  await h.assertNotVisible(`${base}validator1`);
  await h.assertNotVisible(`${base}validator2`);
  await h.assertNotVisible(`${base}validator3`);

  await h.type(`${base}textbox1`, '12.2');
  await h.type(`${base}textbox2`, '-12.5');
  await h.type(`${base}textbox3`, '2/13/2005');
  await h.byXPath("//input[@type='submit' and @value='submit!']").click();
  await h.pause(500);

  await h.assertVisible(`${base}validator1`);
  await h.assertNotVisible(`${base}validator2`);
  await h.assertVisible(`${base}validator3`);
});
