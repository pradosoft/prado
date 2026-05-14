import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('CompareValidatorTestCase', async ({ page }) => {
  const h = genericHelper(page);
  const base = 'ctl0_Content_';
  await h.url('validators/index.php?page=CompareValidator');
  await h.assertSourceContains('Prado CompareValidator Tests');

  await h.type(`${base}text1`, 'qwe');
  await h.assertNotVisible(`${base}validator1`);
  await h.assertNotVisible(`${base}validator2`);

  await h.byXPath("//input[@type='submit' and @value='Test']").click();

  await h.type(`${base}text2`, '1234');
  await h.byXPath("//input[@type='submit' and @value='Test']").click();
  await h.assertVisible(`${base}validator1`);

  await h.type(`${base}text2`, 'qwe');
  await h.byXPath("//input[@type='submit' and @value='Test']").click();
  await h.assertNotVisible(`${base}validator1`);
  await h.assertNotVisible(`${base}validator2`);

  await h.type(`${base}text3`, '12312');
  await h.byXPath("//input[@type='submit' and @value='Test']").click();
  await h.pause(500);
  await h.assertVisible(`${base}validator2`);

  await h.type(`${base}text3`, '13/1/2005');
  await h.assertVisible(`${base}validator2`);

  await h.type(`${base}text3`, '12/1/2005');
  await h.byXPath("//input[@type='submit' and @value='Test']").click();

  await h.assertNotVisible(`${base}validator1`);
  await h.assertNotVisible(`${base}validator2`);
});
