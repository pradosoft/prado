import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('RegExpValidatorTestCase', async ({ page }) => {
  const h = genericHelper(page);
  const base = 'ctl0_Content_';
  await h.url('validators/index.php?page=RegularExpressionValidator');
  await h.assertSourceContains('Prado RegularExpressionValidator Tests');
  await h.assertNotVisible(`${base}validator1`);
  await h.assertNotVisible(`${base}validator2`);
  await h.type(`${base}text1`, '1');
  await h.type(`${base}text2`, '2');
  await h.byXPath("//input[@type='submit' and @value='Test']").click();
  await h.assertVisible(`${base}validator1`);
  await h.assertVisible(`${base}validator2`);
  await h.type(`${base}text1`, 'asdasd');
  await h.byXPath("//input[@type='submit' and @value='Test']").click();
  await h.assertVisible(`${base}validator1`);
  await h.type(`${base}text1`, '12345');
  await h.assertNotVisible(`${base}validator1`);
  await h.assertVisible(`${base}validator2`);
  await h.type(`${base}text2`, 'wei@gmail.com');
  await h.assertNotVisible(`${base}validator1`);
  await h.assertNotVisible(`${base}validator2`);
  await h.byXPath("//input[@type='submit' and @value='Test']").click();
  await h.assertNotVisible(`${base}validator1`);
  await h.assertNotVisible(`${base}validator2`);
});
