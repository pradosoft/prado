import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('ConditionalValidationTestCase', async ({ page }) => {
  const h = genericHelper(page);
  const base = 'ctl0_Content_';
  await h.url('validators/index.php?page=ConditionalValidation');
  await h.assertSourceContains('Conditional Validation (clientside + server side)');
  await h.assertNotVisible(`${base}validator1`);
  await h.assertNotVisible(`${base}validator2`);

  await h.byId(`${base}submit1`).click();
  await h.assertVisible(`${base}validator1`);
  await h.assertNotVisible(`${base}validator2`);

  await h.byId(`${base}check1`).click();
  await h.byId(`${base}submit1`).click();
  await h.assertVisible(`${base}validator1`);
  await h.assertVisible(`${base}validator2`);

  await h.byId(`${base}check1`).click();
  await h.byId(`${base}submit1`).click();
  await h.pause(50);
  await h.assertVisible(`${base}validator1`);
  await h.assertNotVisible(`${base}validator2`);

  await h.type(`${base}text1`, 'testing');
  await h.byId(`${base}submit1`).click();
  await h.assertNotVisible(`${base}validator1`);
  await h.assertNotVisible(`${base}validator2`);

  await h.type(`${base}text1`, '');
  await h.byId(`${base}check1`).click();
  await h.byId(`${base}submit1`).click();
  await h.assertVisible(`${base}validator1`);
  await h.assertVisible(`${base}validator2`);

  await h.type(`${base}text1`, 'test');
  await h.type(`${base}text2`, '123');
  await h.byId(`${base}submit1`).click();
  await h.assertNotVisible(`${base}validator1`);
  await h.assertNotVisible(`${base}validator2`);

  await h.byId(`${base}check1`).click();
  await h.type(`${base}text1`, '');
  await h.type(`${base}text2`, '');
  await h.byId(`${base}submit1`).click();
  await h.assertVisible(`${base}validator1`);
  await h.assertNotVisible(`${base}validator2`);
});
