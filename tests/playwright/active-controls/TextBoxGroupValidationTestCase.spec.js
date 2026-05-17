import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('TextBoxGroupValidationTestCase', async ({ page }) => {
  const h = genericHelper(page);
  const base = 'ctl0_Content_';
  await h.url('active-controls/index.php?page=TextBoxValidationCallback');
  await h.assertSourceContains('TextBox AutoPostBack With Group Validation');
  await h.assertNotVisible(`${base}validator1`);

  await h.type(`${base}ZipCode`, 'test');
  await h.assertVisible(`${base}validator1`);

  await h.type(`${base}Address`, 'Sydney');
  await h.type(`${base}ZipCode`, '2000');

  await h.assertNotVisible(`${base}validator1`);

  await h.assertValue(`${base}City`, 'City: Sydney Zip: 2000');
});
