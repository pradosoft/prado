import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('CustomValidatorTestCase', async ({ page }) => {
  const h = genericHelper(page);
  const base = 'ctl0_Content_';
  await h.url('validators/index.php?page=CustomValidator');
  await h.assertSourceContains('Prado CustomValidator Tests');
  await h.assertNotVisible(`${base}validator1`);

  await h.byXPath("//input[@type='submit' and @value='Test']").click();
  await h.assertVisible(`${base}validator1`);

  await h.type(`${base}text1`, 'Prado');
  await h.pause(250);
  await h.assertNotVisible(`${base}validator1`);
  await h.type(`${base}text1`, 'Testing');
  await h.pause(250);
  await h.assertVisible(`${base}validator1`);
  await h.type(`${base}text1`, 'Prado');
  await h.pause(250);
  await h.assertNotVisible(`${base}validator1`);
  await h.byXPath("//input[@type='submit' and @value='Test']").click();
  await h.assertNotVisible(`${base}validator1`);
});
