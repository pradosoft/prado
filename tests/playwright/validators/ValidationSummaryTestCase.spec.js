import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('ValidationSummaryTestCase', async ({ page }) => {
  const h = genericHelper(page);
  const base = 'ctl0_Content_';
  await h.url('validators/index.php?page=ValidationSummary');
  await h.assertSourceContains('Validation Summary Test');

  await h.byXPath("//input[@type='submit' and @value='Create New Account']").click();
  await h.assertVisible(`${base}summary1`);
  await h.assertNotVisible(`${base}summary2`);

  await h.byXPath("//input[@type='submit' and @value='Sign In']").click();
  await h.assertNotVisible(`${base}summary1`);
  await h.assertVisible(`${base}summary2`);

  await h.type(`${base}Username`, 'qwe');
  await h.type(`${base}Password`, 'ewwq');
  await h.byXPath("//input[@type='submit' and @value='Sign In']").click();
  await h.assertNotVisible(`${base}summary1`);
  await h.assertVisible(`${base}summary2`);
});
